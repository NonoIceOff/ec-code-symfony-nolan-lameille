<?php

namespace App\Form;

use App\Entity\Book;
use App\Entity\BookRead;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;

class BookReadModifyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('rating', ChoiceType::class, [
                'choices' => [
                    '0.0' => 0,
                    '0.5' => 0.5,
                    '1.0' => 1,
                    '1.5' => 1.5,
                    '2.0' => 2,
                    '2.5' => 2.5,
                    '3.0' => 3,
                    '3.5' => 3.5,
                    '4.0' => 4,
                    '4.5' => 4.5,
                    '5.0' => 5,
                ],
                'required' => true,
                'label' => 'Note (0 à 5)',
                'placeholder' => 'Sélectionnez une note',
            ])
            ->add('description')
            ->add('is_read', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Lu',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BookRead::class,
        ]);
    }
}
