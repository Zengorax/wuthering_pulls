<?php

namespace App\Form;

use App\Entity\Version;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VersionStockType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('startLuminite', IntegerType::class, ['label' => 'Luminite début', 'required' => false, 'empty_data' => null, 'attr' => ['min' => 0]])
            ->add('startAstrite', IntegerType::class, ['label' => 'Astrites début', 'required' => false, 'empty_data' => null, 'attr' => ['min' => 0]])
            ->add('startPulls', IntegerType::class, ['label' => 'Invocations début', 'required' => false, 'empty_data' => null, 'attr' => ['min' => 0]])
            ->add('estimatedEndLuminite', IntegerType::class, ['label' => 'Luminite fin estimée', 'required' => false, 'empty_data' => null, 'attr' => ['min' => 0]])
            ->add('estimatedEndAstrite', IntegerType::class, ['label' => 'Astrites fin estimées', 'required' => false, 'empty_data' => null, 'attr' => ['min' => 0]])
            ->add('estimatedEndPulls', IntegerType::class, ['label' => 'Invocations fin estimées', 'required' => false, 'empty_data' => null, 'attr' => ['min' => 0]])
            ->add('equivalence', TextType::class, ['label' => 'Équivalence (ex. S9R8)', 'required' => false]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Version::class,
        ]);
    }
}
