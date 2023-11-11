<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\PasswordType as SymfonyPasswordType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;

class UserPasswordType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('currentPassword', SymfonyPasswordType::class, [
                'label' => 'Current Password',
                'mapped' => false,
            ])
            ->add('password', RepeatedType::class, [
                'type' => SymfonyPasswordType::class,
                'invalid_message' => 'The password fields must match.',
                'first_options' => ['label' => 'New Password'],
                'second_options' => ['label' => 'Confirm New Password'],
                // other options
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Update Password',
            ]);
    }

    // Optionally, if you bind this form to a data class
    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            // 'data_class' => User::class, // Bind form to User entity
        ]);
    }
}
