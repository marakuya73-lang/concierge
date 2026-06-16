<?php

namespace App\Form;

use App\Entity\Extra;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ExtraType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('namePt', TextType::class, ['label' => 'Nome (PT)'])
            ->add('nameEn', TextType::class, ['label' => 'Nome (EN)'])
            ->add('descriptionPt', TextareaType::class, ['label' => 'Descrição (PT)', 'required' => false])
            ->add('descriptionEn', TextareaType::class, ['label' => 'Descrição (EN)', 'required' => false])
            ->add('price', NumberType::class, ['label' => 'Preço', 'scale' => 2])
            ->add('category', ChoiceType::class, [
                'label' => 'Categoria',
                'choices' => [
                    'Transfer' => 'transfer',
                    'Alimentação' => 'alimentação',
                    'Bem-estar' => 'bem-estar',
                    'Experiências' => 'experiências',
                    'Acomodação' => 'acomodação',
                    'Outro' => 'outro',
                ],
            ])
            ->add('icon', ChoiceType::class, [
                'label' => 'Ícone',
                'choices' => [
                    'Carro' => 'car',
                    'Café' => 'coffee',
                    'Coração' => 'heart',
                    'Montanha' => 'mountain',
                    'Chave' => 'key',
                    'Estrela' => 'star',
                ],
            ])
            ->add('minGuests', NumberType::class, ['label' => 'Mín. hóspedes', 'html5' => true])
            ->add('maxGuests', NumberType::class, ['label' => 'Máx. hóspedes (vazio = sem limite)', 'required' => false, 'html5' => true])
            ->add('leadTimeHours', NumberType::class, [
                'label' => 'Antecedência mínima (horas)',
                'required' => false,
                'html5' => true,
                'help' => 'Vazio = sem limite. Ex.: 24 para exigir reserva 24h antes do check-in.',
            ])
            ->add('active', CheckboxType::class, ['label' => 'Ativo', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults(['data_class' => Extra::class]);
    }
}
