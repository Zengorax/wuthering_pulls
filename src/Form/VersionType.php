<?php

namespace App\Form;

use App\Entity\MajorVersion;
use App\Entity\Version;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VersionType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'form.version.name',
                'attr' => ['placeholder' => '3.0'],
            ])
            ->add('majorVersion', EntityType::class, [
                'class' => MajorVersion::class,
                'choice_label' => 'name',
                'label' => 'form.version.major_version',
                'placeholder' => 'form.version.major_version_placeholder',
            ])
            ->add('startsAt', DateType::class, [
                'label' => 'form.version.starts_at',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ])
            ->add('endsAt', DateType::class, [
                'label' => 'form.version.ends_at',
                'required' => false,
                'widget' => 'single_text',
                'input' => 'datetime_immutable',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Version::class,
        ]);
    }
}
