<?php

namespace App\Form;

use App\Entity\Trick;
use App\Entity\Category;
use App\Service\TrickService;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Validator\Constraints\Image;

class TrickFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', Type\TextType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'You have to choose a name for your trick.'
                    ]),
                    new Length([
                        'min' => 3,
                        'max' => 128,
                        'minMessage' => ' Your trick\'s name must be at least {{ limit }} characters.',
                        'maxMessage' => ' Your trick\'s name cannot be longer than {{ limit }} characters.'
                    ]),
                ],
                'attr' => [
                    'maxlength' => 128
                ]
            ])
            ->add('description', Type\TextareaType::class, [
                'required' => true,
                'constraints' => [
                    new NotBlank([
                        'message' => 'You have to write a description for your trick.'
                    ]),
                    new Length([
                        'max' => 32768,
                        'maxMessage' => 'Your trick\'s description cannot be longer than {{ limit }} characters.'
                    ])
                ],
                'attr' => [
                    'maxlength' => 32768
                ]
            ])
            ->add('overview', Type\TextType::class, [
                'required' => false,
                'constraints' => [
                    new Length([
                        'max' => 255,
                        'maxMessage' => 'Your trick\'s overview cannot be longer than {{ limit }} characters'
                    ])
                ],
                'attr' => [
                    'maxlength' => 255
                ]
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'empty_data' => null,
                'placeholder' => 'None'
            ])
            ->add('thumbnail', Type\FileType::class, [
                'required' => false,
                'mapped' => false,
                'attr' => [
                    'data-preview' => '#trick-thumbnail-preview',
                ],
                'constraints' => [
                    new Image([
                        'mimeTypes' => TrickService::SUPPORTED_FORMATS,
                        'mimeTypesMessage' => TrickService::INVALID_FORMAT
                    ])
                ]
            ])
            ->add('images', Type\FileType::class, [
                'required' => false,
                'mapped' => false,
                'multiple' => true,
            ])
            ->add('videos', Type\CollectionType::class, [
                'entry_type' => Type\TextType::class,
                'required' => false,
                'mapped' => false,
                'allow_add' => true,
                'delete_empty' => true,
                'error_bubbling' => true,
                'entry_options' => [
                    'attr' => [
                        'placeholder' => 'https://www.youtube.com/watch?v=...',
                    ],
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
