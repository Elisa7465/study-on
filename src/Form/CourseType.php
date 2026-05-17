<?php

namespace App\Form;

use App\Entity\Course;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;

class CourseType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('symbolCode')
            ->add('title')
            ->add('description', TextareaType::class, [
                'label' => 'Описание курса',
                'required' => false,
                'attr' => [
                    'rows' => 6,
                ],
            ])
            ->add('type', ChoiceType::class, [
                'mapped' => false,
                'label' => 'Тип курса',
                'choices' => [
                    'Бесплатный' => 'free',
                    'Аренда' => 'rent',
                    'Покупка' => 'buy',
                ],
                'required' => true,
            ])
            ->add('price', MoneyType::class, [
                'mapped' => false,
                'label' => 'Стоимость',
                'required' => false,
                'currency' => 'RUB',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Course::class,
        ]);
    }
}

