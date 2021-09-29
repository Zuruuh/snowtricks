<?php

namespace App\Form;

use App\Entity\Trick;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\CollectionType;

class TrickFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name')
            ->add('description')
            ->add('overview')
            ->add('category')
            ->add('thumbnail', FileType::class, [
                'required' => false,
                'mapped' => false,
                "attr" => [
                    "data-preview" => "#trick-thumbnail-preview"
                ]
            ])
            ->add('images', FileType::class, [
                'required' => false,
                'multiple' => true,
                'mapped' => false,
            ])
            ->add('videos', CollectionType::class, [
                'entry_type' => TextType::class,
                'required' => false,
                'mapped' => false,
                'allow_add' => true,
                'delete_empty' => true,
                'entry_options' => [
                    'attr' => [
                        'placeholder' => 'https://www.youtube.com/watch?v=...'
                    ]
                ]
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            'data_class' => Trick::class,
        ]);
    }
}
