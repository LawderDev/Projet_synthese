<?php

namespace App\Form;

use App\Entity\Series;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class RatingFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options)
    {
        $builder
        -> add('notesArray', ChoiceType::class,
            ['choices' => [
                'note1' => '1',
                'note2' => '2',
                'note3' => '3',
                'note4' => '4',
                'note5' => '5'],
                'mapped' => false,
        'multiple'=>false,'expanded'=>true])
        ->add('comment', TextType::class)
        ->add('validationNote', SubmitType::class, ['label' => 'valider'])
        ;
    }

    public function configureOptions(OptionsResolver $resolver)
    {
        $resolver->setDefaults([
            //'data_class' => Series::class,
        ]);
    }
}
