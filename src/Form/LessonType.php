<?php

namespace App\Form;

use App\Entity\Lesson;
use App\Form\DataTransformer\CourseToIdTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class LessonType extends AbstractType
{
    public function __construct(
        private readonly CourseToIdTransformer $courseToIdTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('course', HiddenType::class)
            ->add('title')
            ->add('content', TextareaType::class, [
                'attr' => [
                    'rows' => 12,
                ],
            ])
            ->add('sortOrder', IntegerType::class)
        ;

        $builder->get('course')->addModelTransformer($this->courseToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Lesson::class,
        ]);
    }
}