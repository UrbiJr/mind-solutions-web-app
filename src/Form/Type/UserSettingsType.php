<?php

namespace App\Form\Type;

use App\Entity\CaptchaProvider;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use App\Entity\User; // Assuming User is your user entity class
use Symfony\Bridge\Doctrine\Form\Type\EntityType;

class UserSettingsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currency', ChoiceType::class, [
                'label' => 'Preferred Currency',
                'choices' => [
                    'EUR (€)' => 'EUR',
                    'GBP (£)' => 'GBP',
                    'USD ($)' => 'USD',
                    'CAD (C$)' => 'CAD',
                    'CHF (₣)' => 'CHF',
                ],
            ])
            ->add('captchaProvider', EntityType::class, [
                'label' => 'Captcha Solving Provider',
                'class' => CaptchaProvider::class, // Replace with your CaptchaProvider entity class
                'choice_label' => 'name',
                'choice_value' => 'id',
                'placeholder' => 'Select a Captcha Provider',
                'required' => false,
            ])
            ->add('captchaProviderApiKey', TextType::class, [
                'label' => 'API Key',
                'attr' => [
                    'placeholder' => 'API Key',
                ],
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Update Settings',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => User::class, // Bind form to User entity
        ]);
    }
}
