<?php

// src/Form/Type/UserType.php
namespace App\Form\Type;

use App\Entity\User;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\Regex;
use Symfony\Component\Validator\Constraints\Callback;
use Symfony\Component\Validator\Constraints\IsTrue;
use Symfony\Component\Validator\Context\ExecutionContext;

class UserType extends AbstractType
{
    public function configureOptions(OptionsResolver $resolver): void
    {
        // Explicitly specify the class that this form should hold
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username', EmailType::class, [
                'constraints' => new Regex('/^([a-z\d\.-]+)@([a-z\d-]+)\.([a-z]{2,8})(\.[a-z]{2,8})?$/', 'Please provide a valid email.'),
                'label' => 'Your Email',
            ])
            ->add('password', PasswordType::class, [
                'mapped' => false,
                'constraints' => new Regex('/^[\d\w@!-]{8,20}$/i', 'Password must include letters and numbers, 8 - 20 characters (@, _, !, - are allowed).'),
                'label' => 'Password',
            ])
            ->add('confirmPassword', PasswordType::class, [
                'constraints' => new Callback(['callback' => function ($value, ExecutionContext $ec) {
                    if ($ec->getRoot()->get('password')->getData() !== $value) {
                        $ec->addViolation('Passwords do not match.');
                    }
                }]),
                'label' => 'Confirm Password',
                'mapped' => false  // This tells Symfony not to bind this field to the entity.
            ])
            ->add('terms', CheckboxType::class, [
                'label' => 'Agree to terms and conditions',
                'mapped' => false,  // This tells Symfony not to bind this field to the entity.
                'constraints' => [
                    new IsTrue([
                        'message' => 'You should agree to our terms.',
                    ]),
                ],
            ])
            ->add('save', SubmitType::class, ['label' => 'Sign Up']);
    }
}
