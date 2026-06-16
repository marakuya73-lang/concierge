<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormEvent;
use Symfony\Component\Form\FormEvents;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BookingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('guestName', TextType::class, ['label' => 'Nome do hóspede'])
            ->add('guestWhatsapp', TextType::class, [
                'label' => 'WhatsApp do hóspede',
                'required' => false,
                'attr' => [
                    'placeholder' => '+55 (61) 99999-9999',
                    'inputmode' => 'tel',
                    'autocomplete' => 'tel',
                ],
                'help' => 'Com DDI. Gera link directo para conversa no WhatsApp.',
            ])
            ->add('checkIn', DateType::class, ['label' => 'Check-in', 'widget' => 'single_text'])
            ->add('checkOut', DateType::class, ['label' => 'Check-out', 'widget' => 'single_text'])
            ->add('guests', IntegerType::class, ['label' => 'Hóspedes'])
            ->add('stayPrice', NumberType::class, [
                'label' => 'Valor da estadia (R$)',
                'required' => false,
                'scale' => 2,
                'html5' => false,
                'grouping' => true,
                'attr' => [
                    'inputmode' => 'decimal',
                    'placeholder' => '0,00',
                    'autocomplete' => 'off',
                ],
                'help' => 'Use vírgula para centavos (ex.: 1.500,50).',
            ])
            ->add('source', ChoiceType::class, [
                'label' => 'Origem',
                'choices' => array_combine(Booking::sourceChoices(), Booking::sourceChoices()),
                'attr' => ['data-booking-form-target' => 'source'],
            ])
            ->add('status', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Confirmada' => Booking::STATUS_CONFIRMED,
                    'Cancelada' => Booking::STATUS_CANCELLED,
                    'Concluída' => Booking::STATUS_COMPLETED,
                ],
            ])
            ->add('notes', TextareaType::class, ['label' => 'Notas especiais', 'required' => false])
            ->add('rajaaramTherapy', ChoiceType::class, [
                'label' => 'Terapia Rajaaram',
                'choices' => Booking::rajaaramTherapyChoices(),
                'placeholder' => 'Selecionar terapia...',
                'required' => false,
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
            ])
            ->add('rajaaramTherapyTime', TextType::class, [
                'label' => 'Horário da terapia',
                'required' => false,
                'attr' => [
                    'placeholder' => '09:00',
                    'pattern' => '^([01]\\d|2[0-3]):[0-5]\\d$',
                    'inputmode' => 'numeric',
                ],
                'help' => 'Formato 24h (ex.: 09:00, 14:30).',
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
            ])
            ->add('rajaaramBreakfastIncluded', CheckboxType::class, [
                'label' => 'Café da manhã incluído',
                'required' => false,
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
            ]);

        if ($options['include_access_code']) {
            $builder->add('accessCode', TextType::class, [
                'label' => 'Código de acesso',
                'attr' => [
                    'maxlength' => 5,
                    'class' => 'access-code-input',
                    'autocomplete' => 'off',
                    'style' => 'text-transform: uppercase; letter-spacing: .25em; font-family: var(--font-serif); font-size: 1.5rem; font-weight: 600;',
                    'placeholder' => 'ABCDE',
                ],
                'help' => '5 caracteres (A-Z e 2-9). Será convertido para maiúsculas.',
            ]);
        }

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (isset($data['accessCode']) && \is_string($data['accessCode'])) {
                $data['accessCode'] = strtoupper(preg_replace('/\s+/', '', $data['accessCode']) ?? '');
            }
            if (isset($data['stayPrice']) && \is_string($data['stayPrice'])) {
                $data['stayPrice'] = self::normalizeMoneyInput($data['stayPrice']);
            }
            if (isset($data['rajaaramTherapyTime']) && \is_string($data['rajaaramTherapyTime'])) {
                $data['rajaaramTherapyTime'] = trim($data['rajaaramTherapyTime']) ?: null;
            }
            if (isset($data['guestWhatsapp']) && \is_string($data['guestWhatsapp'])) {
                $data['guestWhatsapp'] = trim($data['guestWhatsapp']) ?: null;
            }
            if (($data['source'] ?? null) !== Booking::SOURCE_RAJAARAM) {
                $data['rajaaramTherapy'] = null;
                $data['rajaaramTherapyTime'] = null;
                $data['rajaaramBreakfastIncluded'] = null;
            }
            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $booking = $event->getData();
            if (!$booking instanceof Booking || $booking->isRajaaram()) {
                return;
            }

            $booking->clearRajaaramDetails();
        });
    }

    private static function normalizeMoneyInput(string $value): string
    {
        $value = trim(str_replace(' ', '', $value));
        if ($value === '') {
            return $value;
        }

        $value = preg_replace('/^R\$\s*/', '', $value) ?? $value;

        if (str_contains($value, ',')) {
            return $value;
        }

        if (str_contains($value, '.') && substr_count($value, '.') === 1) {
            [$whole, $fraction] = explode('.', $value, 2);
            if (strlen($fraction) <= 2) {
                return $whole.','.$fraction;
            }
        }

        return $value;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Booking::class,
            'include_access_code' => false,
            'validation_groups' => function (FormInterface $form): array {
                $groups = ['Default', 'Booking'];

                if ($form->getConfig()->getOption('include_access_code')) {
                    $groups[] = 'with_access_code';
                }

                return $groups;
            },
        ]);
        $resolver->setAllowedTypes('include_access_code', 'bool');
    }
}
