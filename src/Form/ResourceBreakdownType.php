<?php

namespace App\Form;

use App\Entity\MajorVersion;
use App\Entity\ResourceBreakdown;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ResourceBreakdownType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('majorVersion', EntityType::class, [
                'class' => MajorVersion::class,
                'choice_label' => 'name',
                'label' => 'Période majeure',
            ])
            ->add('name', TextType::class, [
                'label' => 'Origine',
                'attr' => ['placeholder' => 'Daily, abonnement, boutique…'],
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'Quantité',
                'empty_data' => '0',
                'attr' => ['min' => 0],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'Unité',
                'choices' => [
                    'Astrites' => ResourceBreakdown::UNIT_ASTRITES,
                    'Invocations' => ResourceBreakdown::UNIT_PULLS,
                    'Marées' => ResourceBreakdown::UNIT_TIDES,
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Remarques',
                'required' => false,
                'attr' => ['rows' => 2],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ResourceBreakdown::class,
        ]);
    }
}
