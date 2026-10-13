<?php

namespace App\Form;

use App\Catalog\KnownResonators;
use App\Entity\BannerTarget;
use App\Entity\Version;
use App\Enum\Element;
use App\Repository\BannerTargetRepository;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BannerTargetType extends AbstractType
{
    public function __construct(private readonly BannerTargetRepository $targets)
    {
    }

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
                'label' => 'form.target.version',
                'placeholder' => 'form.target.version_placeholder',
            ])
            ->add('name', TextType::class, [
                'label' => 'form.target.name',
                'block_prefix' => 'resonator_name',
                'attr' => ['placeholder' => 'form.target.name_placeholder', 'autocomplete' => 'off'],
            ])
            ->add('rarity', ChoiceType::class, [
                'label' => 'form.target.rarity',
                'expanded' => true,
                'choices' => ['★★★★★' => 5, '★★★★' => 4],
                'choice_translation_domain' => false,
            ])
            ->add('element', EnumType::class, [
                'class' => Element::class,
                'label' => 'form.target.element',
                'required' => false,
                'placeholder' => 'form.target.element_none',
                'choice_label' => static fn (Element $element): string => $element->label(),
                'choice_translation_domain' => false,
            ])
            ->add('pullsCharacter', IntegerType::class, [
                'label' => 'form.target.pulls_character',
                'empty_data' => '0',
                'attr' => ['min' => 0, 'inputmode' => 'numeric', 'placeholder' => '0'],
            ])
            ->add('pullsWeapon', IntegerType::class, [
                'label' => 'form.target.pulls_weapon',
                'empty_data' => '0',
                'attr' => ['min' => 0, 'inputmode' => 'numeric', 'placeholder' => '0'],
            ])
            ->add('fiftyFiftyResult', ChoiceType::class, [
                'label' => 'form.target.fifty_fifty',
                'placeholder' => 'form.target.fifty_fifty_none',
                'required' => false,
                'expanded' => true,
                'choices' => [
                    'form.target.fifty_fifty_won' => BannerTarget::FIFTY_FIFTY_WON,
                    'form.target.fifty_fifty_lost' => BannerTarget::FIFTY_FIFTY_LOST,
                ],
            ])
            ->add('isEstimate', CheckboxType::class, [
                'label' => 'form.target.is_estimate',
                'required' => false,
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'form.target.notes',
                'required' => false,
                'attr' => ['rows' => 2, 'placeholder' => 'form.target.notes_placeholder'],
            ]);
    }

    public function finishView(FormView $view, FormInterface $form, array $options): void
    {
        $view->vars['known_names'] = KnownResonators::merged($this->targets->findKnownNames());
        $view['name']->vars['attr']['list'] = $view['name']->vars['id'].'_list';
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BannerTarget::class,
        ]);
    }
}
