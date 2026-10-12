<?php

namespace App\Form;

use App\Entity\MajorVersion;
use App\Entity\Version;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VersionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Version',
                'attr' => ['placeholder' => '3.0'],
            ])
            ->add('majorVersion', EntityType::class, [
                'class' => MajorVersion::class,
                'choice_label' => 'name',
                'label' => 'Période majeure',
                'placeholder' => 'Choisir une période',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Version::class,
        ]);
    }
}
