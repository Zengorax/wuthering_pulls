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
    private const STOCK_FIELDS = [
        'startLuminite',
        'startAstrite',
        'startPulls',
        'estimatedEndLuminite',
        'estimatedEndAstrite',
        'estimatedEndPulls',
    ];

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach (self::STOCK_FIELDS as $field) {
            $builder->add($field, IntegerType::class, [
                'label' => 'form.stock.'.$field,
                'required' => false,
                'empty_data' => null,
                'attr' => ['min' => 0, 'inputmode' => 'numeric'],
            ]);
        }

        $builder->add('equivalence', TextType::class, [
            'label' => 'form.stock.equivalence',
            'required' => false,
            'attr' => ['placeholder' => 'S9R8', 'maxlength' => 32],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Version::class,
        ]);
    }
}
