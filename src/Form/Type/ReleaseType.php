<?php

namespace App\Form\Type;

use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\{TextType, ChoiceType, DateTimeType, DateType, EmailType, NumberType, SubmitType, HiddenType, TextareaType, UrlType};
use Symfony\Component\Form\Extension\Core\Type\CountryType;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ReleaseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('countryCode', CountryType::class, [
                'label' => 'Country:',
                'placeholder' => 'Select a country'
            ])
            ->add('city', TextType::class, [
                'label' => 'City:'
            ])
            ->add('location', TextType::class, [
                'label' => 'Location:',
            ])
            ->add('description', TextType::class, [
                'label' => 'Event description:',
            ])
            ->add('eventDate', DateTimeType::class, [
                'label' => 'Event Date & Time:',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('releaseDate', DateTimeType::class, [
                'label' => 'Release Date & Time:',
                'widget' => 'single_text',
                'html5' => true,
            ])
            ->add('retailer', ChoiceType::class, [
                'label' => 'Retailer:',
                'choices' => [
                    'Ticketmaster' => 'ticketmaster',
                    'Ticketone' => 'ticketone',
                    // Add more retailers as needed
                ],
                'placeholder' => 'Select a retailer',
            ])
            ->add('earlyLink', UrlType::class, [
                'label' => 'Early link:'
            ])
            ->add('comments', TextareaType::class, [
                'label' => 'Comments:',
            ])
            ->add('author', EntityType::class, [
                'class' => User::class, // Replace with your CaptchaProvider entity class
                'choice_label' => 'username',
                'choice_value' => 'id',
            ])
            // Submit button
            ->add('submit', SubmitType::class, [
                'label' => 'Create Release',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // Define your default options here
        ]);
    }
}
