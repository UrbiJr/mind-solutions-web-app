<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, TextType, ChoiceType, DateTimeType, NumberType, SubmitType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarkItemAsSoldType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('platform', ChoiceType::class, [
                'label' => false,
                'attr' => [
                    'class' => 'selectCustomInput',
                ],
                'choices' => [
                    'Viagogo' => 'Viagogo',
                    'Stubhub' => 'Stubhub',
                    'eBay' => 'eBay',
                    'Facebook' => 'Facebook',
                    'MindSolutions' => 'MindSolutions',
                    'customOption' => 'Other',
                ],
                'placeholder' => 'Choose...',
            ])
            ->add('customPlatform', TextType::class, [
                'label' => false,
                'attr' => [
                    'style' => 'display: none;',
                    'disabled' => 'disabled',
                ],
            ])
            ->add('totalPayoutCurrency', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'EUR (€)' => 'EUR',
                    'GBP (£)' => 'GBP',
                    'USD ($)' => 'USD',
                    'CAD (C$)' => 'CAD',
                    'CHF (₣)' => 'CHF',
                ],
                'placeholder' => 'Select a currency',
            ])
            ->add('totalPayout', NumberType::class, [
                'label' => false,
            ])
            ->add('saleDate', DateTimeType::class, [
                'label' => 'Sale Date:',
                'widget' => 'single_text',
                'html5' => true,
                'data' => new \DateTime('now'),
            ])

            // Hidden fields
            ->add('id', HiddenType::class)
            ->add('status', HiddenType::class)
            ->add('quantity', HiddenType::class)
            ->add('quantityRemain', HiddenType::class)
            // Submit button
            ->add('submit', SubmitType::class, [
                'label' => 'Mark Sold',
                'attr' => [
                    'class' => 'btn btn-success',
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
