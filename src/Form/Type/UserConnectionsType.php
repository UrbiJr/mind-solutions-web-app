<?php

namespace App\Form\Type;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ResetType;

class UserConnectionsType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('twitter', TextType::class, [
                'label' => 'Twitter (X):',
                'attr' => [
                    'placeholder' => 'MindSolution_',
                ],
                'required' => false,
            ])
            ->add('displayTwitterOnProfile', CheckboxType::class, [
                'label' => 'Display On Profile',
                'required' => false,
            ])
            ->add('threads', TextType::class, [
                'label' => 'Threads:',
                'attr' => [
                    'placeholder' => 'mindsolutions',
                ],
                'required' => false,
            ])
            ->add('displayThreadsOnProfile', CheckboxType::class, [
                'label' => 'Display On Profile',
                'required' => false,
            ])
            ->add('instagram', TextType::class, [
                'label' => 'Instagram:',
                'attr' => [
                    'placeholder' => 'mindsolutions',
                ],
                'required' => false,
            ])
            ->add('displayInstagramOnProfile', CheckboxType::class, [
                'label' => 'Display On Profile',
                'required' => false,
            ])
            ->add('youtube', TextType::class, [
                'label' => 'You Tube:',
                'attr' => [
                    'placeholder' => 'https://www.youtube.com/channel/mychannel',
                ],
                'required' => false,
            ])
            ->add('displayYouTubeOnProfile', CheckboxType::class, [
                'label' => 'Display On Profile',
                'required' => false,
            ])
            ->add('submit', SubmitType::class, [
                'label' => 'Submit',
            ])
            ->add('reset', ResetType::class, [
                'label' => 'Reset',
                'attr' => [
                    'class' => 'btn btn-soft-primary',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {        
        $resolver->setDefaults([
            // 'data_class' => User::class, // Bind form to User entity
        ]);
    }
}
