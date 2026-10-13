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
                'label' => 'form.resource.major_version',
            ])
            ->add('name', TextType::class, [
                'label' => 'form.resource.name',
                'attr' => ['placeholder' => 'form.resource.name_placeholder'],
            ])
            ->add('amount', IntegerType::class, [
                'label' => 'form.resource.amount',
                'empty_data' => '0',
                'attr' => ['min' => 0, 'inputmode' => 'numeric'],
            ])
            ->add('unit', ChoiceType::class, [
                'label' => 'form.resource.unit',
                'choices' => [
                    'unit.astrites' => ResourceBreakdown::UNIT_ASTRITES,
                    'unit.pulls' => ResourceBreakdown::UNIT_PULLS,
                    'unit.tides' => ResourceBreakdown::UNIT_TIDES,
                ],
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'form.resource.notes',
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
