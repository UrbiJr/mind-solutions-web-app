<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class EventFilterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('query', SearchType::class, [
                'label' => 'By Event Name',
                'required' => false,
                'attr' => ['placeholder' => 'Insert a query...']
            ])
            ->add('country', CountryType::class, [
                'label' => 'By Country',
                'placeholder' => 'Select a country',
                'required' => false,
            ])
            ->add('genre', ChoiceType::class, [
                'label' => 'By Genre',
                'choices' => [
                    'Theater Tickets' => 1,
                    'Sports Tickets' => 2,
                    'Concert Tickets' => 3,
                ],
                'placeholder' => 'Select a genre',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Filter',
                'attr' => ['class' => 'btn btn-primary']
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Set your default options here if needed
        ]);
    }
}
