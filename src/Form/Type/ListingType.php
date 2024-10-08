<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{CheckboxType, TextType, ChoiceType, NumberType, SubmitType, HiddenType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class ListingType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sectionList = $options['sectionList'] ?? [];

        $builder
            // first row
            ->add('quantityRemain', NumberType::class, ['label' => 'Quantity:'])
            ->add('section', ChoiceType::class, [
                'label' => 'Section:',
                'attr' => [
                    'class' => 'sectionSelect',
                ],
                'choices' => $sectionList,
                'placeholder' => 'Select a section',
                'choice_label' => function ($value) {    // use value as label
                    return $value;
                },
            ])
            ->add('customSection', TextType::class, [
                'label' => false,
                'mapped' => false,
                'required' => false,
                'attr' => [
                    'class' => 'hidden customSection',
                ],
            ])

            // second row
            ->add('row', TextType::class, ['label' => 'Row:'])
            ->add('seatFrom', TextType::class, ['label' => 'Seat From:'])
            ->add('seatTo', TextType::class, ['label' => 'Seat To:'])
            ->add('splitType', ChoiceType::class, [
                'mapped' => false,
            ])

            // third row
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
                'mapped' => false,
                'label' => false,
            ])
            ->add('ticketType', ChoiceType::class, [
                'label' => 'Ticket Type:',
                'choices' => [
                    'Paper' => '0',
                    'E-Ticket' => '1',
                    'AXS' => '9',
                    'Ticketmaster Mobile Ticket' => '10',
                    'Mobile Tickets' => '11',
                    'Mobile QR Code' => '13',
                ],
                'placeholder' => 'Select a type',
            ])

            // fourth row
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
            ])


            // Hidden fields
            ->add('id', HiddenType::class)
            ->add('status', HiddenType::class)
            ->add('platform', HiddenType::class)
            ->add('viagogoEventId', HiddenType::class)
            ->add('viagogoCategoryId', HiddenType::class)
            // Submit button
            ->add('submit', SubmitType::class, [
                'label' => 'Submit',
                'attr' => [
                    'class' => 'btn btn-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Define your default options here
            'sectionList' => [],
        ]);
    }

    public function getBlockPrefix()
    {
        return '';
    }
}
