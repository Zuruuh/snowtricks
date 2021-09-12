<?php

namespace App\Form;

use App\Entity\Trick;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\HttpFoundation\File\File;
use Symfony\Component\Form\Extension\Core\Type\FileType;
use Symfony\Component\Form\Extension\Core\Type\TextType;

class TrickType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
            ->add('name', TextType::class, [
                'label' => 'Trick\'s name',
                "required" => true,
            ])
            ->add('overview')
            ->add('description', TextType::class, [
                'label' => 'Trick\'s description',
                "required" => true,
            ])
            ->add('thumbnail', FileType::class, [
                "required" => false
            ]) // // TODO Add thumbnail uploading
            ->add('images', FileType::class, [ // // TODO Add images uploading for images
                'multiple' => true, 
                'required' => false
            ])
            ->add('category')

            // // TODO Add dynamic fields for videos:
            // Let users add or remove fields by clicking on the +/- buttons
            ->add('videos', TextType::class, [
                "required" => false,
                "attr" => [
                    "value" => "[]",
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
