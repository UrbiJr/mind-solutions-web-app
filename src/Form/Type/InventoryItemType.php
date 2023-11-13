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
        $builder
            // Event Details
            ->add('eventName', TextType::class, [
                'label' => 'Name:',
            ])
            ->add('country', CountryType::class, [
                'label' => 'Country:',
                'placeholder' => 'Select a country'
            ])
            ->add('eventDate', DateTimeType::class, [
                'label' => 'Event Date & Time:',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('ticketGenre', ChoiceType::class, [
                'label' => 'Genre:',
                'choices' => [
                    'Concert Tickets' => 'Concert Tickets',
                    'Sports Tickets' => 'Sports Tickets',
                    'Theatre Tickets' => 'Theatre Tickets',
                ],
                'placeholder' => 'Select a genre',
            ])
            // Venue
            ->add('city', TextType::class, ['label' => 'City:'])
            ->add('location', TextType::class, ['label' => 'Location:'])
            // Billing Information
            ->add('orderEmail', EmailType::class, ['label' => 'Email:'])
            ->add('orderNumber', TextType::class, ['label' => 'Order Number:'])
            ->add('purchaseDate', DateTimeType::class, [
                'label' => 'Purchase Date:',
                'widget' => 'single_text',
                'html5' => true,
            ])
            // Ticket(s) Details
            ->add('ticketCost', NumberType::class, ['label' => 'Ticket Face Value:'])
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
                'choices' => [
                    // Populate with sections or leave for dynamic JS population
                ],
                'placeholder' => 'Select a section',
            ])
            ->add('customSection', TextType::class, [
                'label' => false,
                'attr' => [
                    'style' => 'display: none;'
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
            ->add('eventId', HiddenType::class)
            ->add('categoryId', HiddenType::class)
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
        ]);
    }
}
