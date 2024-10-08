<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{ChoiceType, EmailType, NumberType, DateType, SubmitType};
use Symfony\Component\OptionsResolver\OptionsResolver;

class MassEditInventoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
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
                'required' => false,
            ])
            ->add('retailer', ChoiceType::class, [
                'label' => 'Retailer:',
                'choices' => [
                    'Ticketmaster' => 'ticketmaster',
                    'Ticketone' => 'ticketone',
                ],
                'placeholder' => 'Select a retailer',
                'required' => false,
            ])
            ->add('individualTicketCost', NumberType::class, [
                'label' => 'Ticket Face Value:',
                'attr' => ['placeholder' => '0,00'],
                'scale' => 2,
                'required' => false,
            ])
            ->add('orderEmail', EmailType::class, [
                'label' => 'Email:',
                'attr' => ['placeholder' => 'example@mindsolutions.app'],
                'required' => false,
            ])
            ->add('purchaseDate', DateType::class, [
                'label' => 'Purchase Date:',
                'widget' => 'single_text', // to render as HTML5 date input
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Update Inventory',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Define your default options here if necessary
        ]);
    }

    public function getBlockPrefix() {
        return '';
    }
}
