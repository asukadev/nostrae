<?php

namespace App\Form;

use App\Entity\Event;
use App\Entity\Location;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\Extension\Core\Type\DateTimeType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\NumberType;
use Symfony\Component\OptionsResolver\OptionsResolver;
use App\Entity\EventType as EventTypeEntity;
use Vich\UploaderBundle\Form\Type\VichImageType;

class EventType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('title', TextType::class)
            ->add('description', TextareaType::class, [
                'required' => false
            ])
            ->add('type', EntityType::class, [
                'class' => EventTypeEntity::class,
                'choice_label' => 'name',
                'label' => 'Type d\'événement',
            ])
            ->add('startAt', DateTimeType::class, [
                'widget' => 'single_text',
                'label' => 'Date de l\'événement',
            ])
            ->add('imageFile', VichImageType::class, [
                'label' => 'Image de l’événement',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
            ])
            ->add('location', EntityType::class, [
                'class' => Location::class,
                'choice_label' => function ($location) {
                    return $location->getName() . ' – ' . $location->getCity();
                },
                'placeholder' => 'Choisir un lieu',
                'label' => 'Lieu de l\'événement',
            ])
            ->add('capacity', NumberType::class)
            ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Event::class,
        ]);
    }
}
