<?php

namespace App\Form;

use App\Entity\Booking;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
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
            ->add('guestName', TextType::class, [
                'label' => 'Quem reservou (acesso)',
                'help' => 'Pessoa que reservou e recebe o código do concierge. Pode não ser quem faz a terapia.',
            ])
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
            ->add('guestLocale', ChoiceType::class, [
                'label' => 'Idioma do hóspede',
                'choices' => Booking::guestLocaleChoices(),
                'help' => 'Define o idioma da mensagem de boas-vindas e do concierge privado.',
            ])
            ->add('checkIn', DateType::class, ['label' => 'Check-in', 'widget' => 'single_text'])
            ->add('checkOut', DateType::class, ['label' => 'Check-out', 'widget' => 'single_text'])
            ->add('guests', IntegerType::class, [
                'label' => 'N.º de hóspedes',
                'help' => 'Total na estadia, incluindo quem reservou e hóspedes extra.',
            ])
            ->add('extraGuestNames', CollectionType::class, [
                'label' => 'Hóspedes extra',
                'entry_type' => TextType::class,
                'entry_options' => [
                    'label' => false,
                    'required' => false,
                    'attr' => ['placeholder' => 'Nome do hóspede extra'],
                ],
                'allow_add' => true,
                'allow_delete' => true,
                'delete_empty' => true,
                'required' => false,
                'by_reference' => false,
                'empty_data' => [],
                'help' => 'Pessoas na estadia além de quem reservou. Não precisam ser as mesmas da terapia.',
            ])
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
            ->add('rajaaramIsDuo', ChoiceType::class, [
                'label' => 'Tipo de terapia',
                'choices' => [
                    'Individual' => false,
                    'Duo' => true,
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'row_attr' => [
                    'data-booking-form-target' => 'rajaaramField rajaaramIsDuo',
                    'data-action' => 'change->booking-form#sync',
                ],
            ])
            ->add('rajaaramTherapy', ChoiceType::class, [
                'label' => 'Terapia',
                'choices' => Booking::rajaaramTherapyChoices(),
                'placeholder' => 'Selecionar terapia...',
                'required' => false,
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
            ])
            ->add('rajaaramTherapyDate', DateType::class, [
                'label' => 'Data da terapia',
                'required' => false,
                'widget' => 'single_text',
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
            ])
            ->add('rajaaramTherapyTime', TimeType::class, [
                'label' => 'Horário da terapia',
                'required' => false,
                'input' => 'string',
                'input_format' => 'H:i',
                'widget' => 'single_text',
                'with_seconds' => false,
                'html5' => true,
                'help' => 'Formato 24h (ex.: 09:00, 14:30).',
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
            ])
            ->add('rajaaramGuest1Name', TextType::class, [
                'label' => 'Nome na terapia',
                'required' => false,
                'help' => 'Quem recebe esta sessão. Pode ser diferente de quem reservou.',
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
            ])
            ->add('rajaaramGuest2Name', TextType::class, [
                'label' => 'Nome na terapia 2',
                'required' => false,
                'row_attr' => ['data-booking-form-target' => 'rajaaramField rajaaramDuoField'],
            ])
            ->add('rajaaramTherapy2', ChoiceType::class, [
                'label' => 'Terapia 2',
                'choices' => Booking::rajaaramTherapyChoices(),
                'placeholder' => 'Selecionar terapia...',
                'required' => false,
                'row_attr' => ['data-booking-form-target' => 'rajaaramField rajaaramDuoField'],
            ])
            ->add('rajaaramTherapy2Date', DateType::class, [
                'label' => 'Data da terapia 2',
                'required' => false,
                'widget' => 'single_text',
                'row_attr' => ['data-booking-form-target' => 'rajaaramField rajaaramDuoField'],
            ])
            ->add('rajaaramTherapy2Time', TimeType::class, [
                'label' => 'Horário da terapia 2',
                'required' => false,
                'input' => 'string',
                'input_format' => 'H:i',
                'widget' => 'single_text',
                'with_seconds' => false,
                'html5' => true,
                'help' => 'Formato 24h (ex.: 09:00, 14:30).',
                'row_attr' => ['data-booking-form-target' => 'rajaaramField rajaaramDuoField'],
            ])
            ->add('rajaaramBreakfastIncluded', ChoiceType::class, [
                'label' => 'Café da manhã',
                'choices' => [
                    'Incluído' => '1',
                    'Não incluído' => '0',
                ],
                'expanded' => true,
                'multiple' => false,
                'required' => false,
                'placeholder' => false,
                'row_attr' => ['data-booking-form-target' => 'rajaaramField'],
                'help' => 'Fica gravado nesta reserva. Não muda sozinho depois de salvar.',
            ]);

        $builder->get('rajaaramBreakfastIncluded')->addModelTransformer(new CallbackTransformer(
            static function (mixed $value): ?string {
                return match ($value) {
                    true, 1, '1' => '1',
                    false, 0, '0' => '0',
                    default => null,
                };
            },
            static function (mixed $value): ?bool {
                return match ($value) {
                    '1', 1, true => true,
                    '0', 0, false => false,
                    default => null,
                };
            },
        ));

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

        $builder->addEventListener(FormEvents::PRE_SET_DATA, static function (FormEvent $event): void {
            $booking = $event->getData();
            if (!$booking instanceof Booking || !$booking->hasRajaaramSession()) {
                return;
            }

            if (null === $booking->getRajaaramIsDuo()) {
                $booking->setRajaaramIsDuo(false);
            }
        });

        $builder->addEventListener(FormEvents::PRE_SUBMIT, function (FormEvent $event): void {
            $data = $event->getData();
            if (isset($data['accessCode']) && \is_string($data['accessCode'])) {
                $data['accessCode'] = strtoupper(preg_replace('/\s+/', '', $data['accessCode']) ?? '');
            }
            if (isset($data['stayPrice']) && \is_string($data['stayPrice'])) {
                $data['stayPrice'] = self::normalizeMoneyInput($data['stayPrice']);
            }
            if (isset($data['rajaaramTherapyTime']) && \is_string($data['rajaaramTherapyTime'])) {
                $data['rajaaramTherapyTime'] = self::normalizeTimeInput($data['rajaaramTherapyTime']);
            }
            if (isset($data['rajaaramTherapy2Time']) && \is_string($data['rajaaramTherapy2Time'])) {
                $data['rajaaramTherapy2Time'] = self::normalizeTimeInput($data['rajaaramTherapy2Time']);
            }
            if (isset($data['guestWhatsapp']) && \is_string($data['guestWhatsapp'])) {
                $data['guestWhatsapp'] = trim($data['guestWhatsapp']) ?: null;
            }
            if (!isset($data['extraGuestNames']) || !\is_array($data['extraGuestNames'])) {
                $data['extraGuestNames'] = [];
            }
            $source = $data['source'] ?? null;
            $isDuo = ($data['rajaaramIsDuo'] ?? null) === '1' || ($data['rajaaramIsDuo'] ?? null) === true;

            if ($source !== Booking::SOURCE_RAJAARAM) {
                $data['rajaaramTherapy'] = null;
                $data['rajaaramTherapyDate'] = null;
                $data['rajaaramTherapyTime'] = null;
                $data['rajaaramIsDuo'] = null;
                $data['rajaaramGuest1Name'] = null;
                $data['rajaaramGuest2Name'] = null;
                $data['rajaaramTherapy2'] = null;
                $data['rajaaramTherapy2Date'] = null;
                $data['rajaaramTherapy2Time'] = null;
                $data['rajaaramBreakfastIncluded'] = null;
            } else {
                if (!$isDuo) {
                    $data['rajaaramGuest2Name'] = null;
                    $data['rajaaramTherapy2'] = null;
                    $data['rajaaramTherapy2Date'] = null;
                    $data['rajaaramTherapy2Time'] = null;
                }

                if (!array_key_exists('rajaaramBreakfastIncluded', $data) || '' === $data['rajaaramBreakfastIncluded']) {
                    $booking = $event->getForm()->getData();
                    if ($booking instanceof Booking) {
                        $data['rajaaramBreakfastIncluded'] = match ($booking->getRajaaramBreakfastIncluded()) {
                            true => '1',
                            false => '0',
                            default => null,
                        };
                    }
                }
            }
            $event->setData($data);
        });

        $builder->addEventListener(FormEvents::POST_SUBMIT, function (FormEvent $event): void {
            $booking = $event->getData();
            if (!$booking instanceof Booking) {
                return;
            }

            if (!$booking->isRajaaram()) {
                $booking->clearRajaaramDetails();
            } elseif (!$booking->isRajaaramDuo()) {
                $booking->setRajaaramGuest2Name(null);
                $booking->setRajaaramTherapy2(null);
                $booking->setRajaaramTherapy2Date(null);
                $booking->setRajaaramTherapy2Time(null);
            }

            $minGuests = 1 + \count($booking->getExtraGuestNames());
            if ($booking->getGuests() < $minGuests) {
                $booking->setGuests($minGuests);
            }
        });
    }

    private static function normalizeTimeInput(string $value): ?string
    {
        $value = trim($value);
        if ($value === '') {
            return null;
        }

        if (preg_match('/^([01]\d|2[0-3]):([0-5]\d)(?::[0-5]\d)?$/', $value, $matches)) {
            return sprintf('%s:%s', $matches[1], $matches[2]);
        }

        if (preg_match('/^\d{3,4}$/', $value)) {
            $digits = str_pad($value, 4, '0', STR_PAD_LEFT);
            $hour = (int) substr($digits, 0, 2);
            $minute = (int) substr($digits, 2, 2);
            if ($hour <= 23 && $minute <= 59) {
                return sprintf('%02d:%02d', $hour, $minute);
            }
        }

        if (preg_match('/^\d{1,2}$/', $value)) {
            $hour = (int) $value;
            if ($hour <= 23) {
                return sprintf('%02d:00', $hour);
            }
        }

        return $value;
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
