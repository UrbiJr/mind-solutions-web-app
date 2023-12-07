<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{TextType, ChoiceType, DateTimeType, DateType, EmailType, NumberType, SubmitType, HiddenType};
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class InventoryItemType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $sectionList = $options['sectionList'] ?? [];
        $individualTicketCostCurrency = $options['individualTicketCost']['currency'];
        $individualTicketCostAmount = $options['individualTicketCost']['amount'];

        $builder
            // Event Details
            ->add('eventName', TextType::class, [
                'mapped' => false,
                'label' => 'Name:',
            ])
            ->add('country', CountryType::class, [
                'mapped' => false,
                'label' => 'Country:',
                'placeholder' => 'Select a country'
            ])
            ->add('eventDate', DateTimeType::class, [
                'mapped' => false,
                'label' => 'Event Date & Time:',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('ticketGenre', ChoiceType::class, [
                'label' => 'Genre:',
                'mapped' => false,
                'choices' => [
                    'Concert Tickets' => 'Concert Tickets',
                    'Sports Tickets' => 'Sports Tickets',
                    'Theatre Tickets' => 'Theatre Tickets',
                ],
                'placeholder' => 'Select a genre',
            ])
            // Venue
            ->add('city', TextType::class, [
                'mapped' => false,
                'label' => 'City:'
            ])
            ->add('location', TextType::class, [
                'label' => 'Location:',
                'mapped' => false,
            ])
            // Billing Information
            ->add('orderEmail', EmailType::class, ['label' => 'Email:'])
            ->add('orderNumber', TextType::class, [
                'label' => 'Order Number:',
                'required' => false,
            ])
            ->add('purchaseDate', DateTimeType::class, [
                'label' => 'Purchase Date:',
                'widget' => 'single_text',
                'html5' => true,
                'data' => new \DateTime('now'),
            ])
            // Ticket(s) Details
            ->add('individualTicketCostCurrency', ChoiceType::class, [
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
                'data' => $individualTicketCostCurrency,
            ])
            ->add('individualTicketCost', NumberType::class, [
                'label' => 'Ticket Face Value:',
                'mapped' => false,
                'data' => $individualTicketCostAmount,
            ])
            ->add('quantity', NumberType::class, ['label' => 'Quantity:'])
            ->add('quantityRemain', HiddenType::class)
            ->add('retailer', ChoiceType::class, [
                'label' => 'Retailer:',
                'choices' => [
                    'Ticketmaster' => 'ticketmaster',
                    'Ticketone' => 'ticketone',
                    // Add more retailers as needed
                ],
                'placeholder' => 'Select a retailer',
            ])
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
                'mapped' => false,
                'label' => false,
                'required' => false,
                'attr' => [
                    'class' => 'hidden customSection',
                ],
            ])
            ->add('row', TextType::class, ['label' => 'Row:'])
            ->add('seatFrom', TextType::class, ['label' => 'Seat From:'])
            ->add('seatTo', TextType::class, ['label' => 'Seat To:'])
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
            // Hidden fields
            ->add('eventId', HiddenType::class, [
                'mapped' => false,
            ])
            ->add('categoryId', HiddenType::class, [
                'mapped' => false,
            ])
            // Submit button
            ->add('submit', SubmitType::class, [
                'label' => 'Submit',
                'attr' => [
                    'class' => 'btn btn-primary action-button float-end',
                    'style' => 'margin: 8px;'
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Define your default options here
            'sectionList' => [],
            'individualTicketCost' => ['amount' => 0, 'currency' => 'EUR']
        ]);
    }
}
