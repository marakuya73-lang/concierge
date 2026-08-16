<?php

namespace App\Form;

use App\Entity\Property;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\TimeType;
use Symfony\Component\Form\Extension\Core\Type\UrlType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PropertyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('namePt', TextType::class, ['label' => 'Nome (PT)'])
            ->add('nameEn', TextType::class, ['label' => 'Nome (EN)'])
            ->add('taglinePt', TextareaType::class, ['label' => 'Tagline (PT)'])
            ->add('taglineEn', TextareaType::class, ['label' => 'Tagline (EN)'])
            ->add('descriptionPt', TextareaType::class, ['label' => 'Descrição (PT)'])
            ->add('descriptionEn', TextareaType::class, ['label' => 'Descrição (EN)'])
            ->add('checkInInstructionsPt', TextareaType::class, ['label' => 'Instruções check-in (PT)'])
            ->add('checkInInstructionsEn', TextareaType::class, ['label' => 'Instruções check-in (EN)'])
            ->add('checkOutInstructionsPt', TextareaType::class, ['label' => 'Instruções check-out (PT)'])
            ->add('checkOutInstructionsEn', TextareaType::class, ['label' => 'Instruções check-out (EN)'])
            ->add('locationDetailsPt', TextareaType::class, ['label' => 'Localização (PT)'])
            ->add('locationDetailsEn', TextareaType::class, ['label' => 'Localização (EN)'])
            ->add('addressPt', TextareaType::class, ['label' => 'Endereço (PT)'])
            ->add('addressEn', TextareaType::class, ['label' => 'Endereço (EN)'])
            ->add('arrivalInstructionsPt', TextareaType::class, ['label' => 'Como chegar (PT)'])
            ->add('arrivalInstructionsEn', TextareaType::class, ['label' => 'Como chegar (EN)'])
            ->add('wifiName', TextType::class, ['label' => 'Wi-Fi principal'])
            ->add('wifiPassword', TextType::class, ['label' => 'Senha Wi-Fi'])
            ->add('wifiSecondaryName', TextType::class, ['label' => 'Wi-Fi alternativo', 'required' => false])
            ->add('wifiSecondaryPassword', TextType::class, ['label' => 'Senha Wi-Fi alternativo', 'required' => false])
            ->add('checkInTime', TimeType::class, [
                'label' => 'Horário check-in (início)',
                'input' => 'string',
                'input_format' => 'H:i',
                'widget' => 'single_text',
                'with_seconds' => false,
                'html5' => true,
                'help' => 'Início da recepção pessoal. Hóspedes não podem indicar chegada antes deste horário.',
            ])
            ->add('checkInTimeEnd', TimeType::class, [
                'label' => 'Horário check-in (fim)',
                'input' => 'string',
                'input_format' => 'H:i',
                'widget' => 'single_text',
                'with_seconds' => false,
                'html5' => true,
                'help' => 'Fim da janela de check-in. Aparece na concierge e no aviso ao hóspede.',
            ])
            ->add('checkOutTime', TimeType::class, [
                'label' => 'Horário check-out',
                'input' => 'string',
                'input_format' => 'H:i',
                'widget' => 'single_text',
                'with_seconds' => false,
                'html5' => true,
            ])
            ->add('domeEntranceCode', TextType::class, [
                'label' => 'Código da porta do domo',
                'attr' => ['inputmode' => 'numeric', 'autocomplete' => 'off'],
                'help' => 'Código de entrada física do domo. Aparece no concierge do hóspede.',
            ])
            ->add('petsPolicy', TextType::class, ['label' => 'Política pets'])
            ->add('smokingPolicy', TextType::class, ['label' => 'Política fumo'])
            ->add('silencePolicy', TextType::class, ['label' => 'Política silêncio'])
            ->add('visitsPolicy', TextType::class, ['label' => 'Política visitas'])
            ->add('rating', NumberType::class, ['label' => 'Avaliação'])
            ->add('bedrooms', NumberType::class, ['label' => 'Quartos'])
            ->add('bathrooms', NumberType::class, ['label' => 'Banheiros'])
            ->add('maxGuests', NumberType::class, ['label' => 'Máx. hóspedes'])
            ->add('mapUrl', UrlType::class, ['label' => 'URL Google Maps', 'default_protocol' => 'https'])
            ->add('latitude', TextType::class, ['label' => 'Latitude'])
            ->add('longitude', TextType::class, ['label' => 'Longitude'])
            ->add('pixKey', TextType::class, ['label' => 'Chave Pix'])
            ->add('contactPhone', TextType::class, ['label' => 'WhatsApp'])
            ->add('contactEmail', TextType::class, ['label' => 'E-mail'])
            ->add('instagramHandle', TextType::class, ['label' => 'Instagram'])
            ->add('airbnbIcalUrl', UrlType::class, ['label' => 'URL iCal Airbnb', 'required' => false, 'default_protocol' => 'https'])
            ->add('googleCalendarId', TextType::class, [
                'label' => 'ID Google Calendar (Domo)',
                'required' => false,
                'help' => 'Opcional se GOOGLE_CALENDAR_DOMO_ID estiver definido no .env (mesmo calendário usado no projecto Rajaaram).',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Property::class]);
    }
}
