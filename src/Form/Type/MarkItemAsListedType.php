<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, TextType, ChoiceType, NumberType, SubmitType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class MarkItemAsListedType extends AbstractType
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
                'help' => "Where did you list this item? We will calculate fees accordingly.",
            ])
            ->add('customPlatform', TextType::class, [
                'label' => false,
                'attr' => [
                    'style' => 'display: none;',
                    'disabled' => 'disabled',
                ],
            ])
            ->add('splitType', ChoiceType::class, [
                'mapped' => false,
            ])
            ->add('yourPricePerTicketCurrency', ChoiceType::class, [
                'mapped' => false,
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
            ->add('yourPricePerTicket', NumberType::class, [
                'label' => false,
                'help' => 'Listing price',
            ])
            ->add('noRestrictions', CheckboxType::class, [
                'label' => 'No restrictions',
                'value' => 'true',
                'required' => false,
                'mapped' => false,
            ])
            ->add('restrictions', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Concession ticket - child' => '12',
                    'Wheelchair user only' => '18',
                    'Under 18 Ticket' => '73',
                    'Original Purchaser\'s ID must be shown' => '74',
                    'Concession ticket - student' => '13',
                    'Under 21 Ticket' => '70',
                    'Over 18 Ticket' => '100',
                    'Meetup with Seller' => '101',
                    'Under 15s accompanied by an adult' => '106',
                    'Concession ticket - senior citizen' => '14',
                    '21 and over Ticket' => '71',
                    'No Under 14s' => '87',
                    'Resale not allowed' => '102',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'help' => "If any of the following conditions apply to your tickets, please select them from the list below.",
            ])

            // Fifth row
            ->add('ticketDetails', ChoiceType::class, [
                'label' => false,
                'choices' => [
                    'Limited or restricted view' => '0',
                    'Includes VIP pass' => '11',
                    'Alcohol free area' => '43',
                    'Access to VIP Lounge' => '93',
                    'Ticket and meal package' => '2',
                    'Includes parking' => '32',
                    'Standing Only' => '72',
                    'Aisle seat' => '10',
                    'Side or rear view' => '42',
                    'Restricted legroom' => '756',
                ],
                'expanded' => true,
                'multiple' => true,
                'required' => false,
                'mapped' => false,
                'help' => "If any of the following conditions apply to your tickets, you must select the corresponding options below.",
            ])


            // Hidden fields
            ->add('id', HiddenType::class)
            ->add('status', HiddenType::class)
            ->add('quantity', HiddenType::class)
            ->add('quantityRemain', HiddenType::class)
            // Submit button
            ->add('submit', SubmitType::class, [
                'label' => 'Mark Listed',
                'attr' => [
                    'class' => 'btn btn-primary',
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
