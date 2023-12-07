<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, TextType, ChoiceType, NumberType, SubmitType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarkItemAsNotListedType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            // Hidden fields
            ->add('id', HiddenType::class)
            ->add('status', HiddenType::class)
            ->add('quantity', HiddenType::class)
            ->add('platform', HiddenType::class)
            // Submit button
            ->add('submit', SubmitType::class, [
                'label' => 'Remove Listing',
                'attr' => [
                    'class' => 'btn btn-warning',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Define your default options here
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
