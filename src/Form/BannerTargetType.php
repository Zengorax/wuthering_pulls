<?php

namespace App\Form;

use App\Entity\BannerTarget;
use App\Entity\Version;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerTargetType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('version', EntityType::class, [
                'class' => Version::class,
                'choice_label' => static fn (Version $version): string => sprintf(
                    '%s · %s',
                    $version->getMajorVersion()?->getName(),
                    $version->getName(),
                ),
                'label' => 'Version',
                'placeholder' => 'Choisir une version',
            ])
            ->add('name', TextType::class, [
                'label' => 'Personnage / cible',
                'attr' => ['placeholder' => 'Lynae, Aemeath…'],
            ])
            ->add('pullsCharacter', IntegerType::class, [
                'label' => 'Pulls perso',
                'empty_data' => '0',
                'attr' => ['min' => 0, 'placeholder' => '0'],
            ])
            ->add('pullsWeapon', IntegerType::class, [
                'label' => 'Pulls arme sig.',
                'empty_data' => '0',
                'attr' => ['min' => 0, 'placeholder' => '0'],
            ])
            ->add('fiftyFiftyResult', ChoiceType::class, [
                'label' => '50/50 personnage',
                'placeholder' => 'Non renseigné',
                'required' => false,
                'choices' => [
                    'Gagné' => BannerTarget::FIFTY_FIFTY_WON,
                    'Perdu' => BannerTarget::FIFTY_FIFTY_LOST,
                ],
            ])
            ->add('isEstimate', CheckboxType::class, [
                'label' => 'Estimation',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Remarques',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'Détails facultatifs…'],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BannerTarget::class,
        ]);
    }
}
