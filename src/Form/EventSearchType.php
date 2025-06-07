<?php

namespace App\Form;

use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\DateType;
use Symfony\Component\Form\Extension\Core\Type\SearchType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\EventType as EventTypeEntity;

class EventSearchType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('q', SearchType::class, [
                'label' => 'Recherche',
                'required' => false,
            ])
            ->add('date', DateType::class, [
                'required' => false,
                'widget' => 'single_text', // important pour Flatpickr
                'label' => 'Date',
            ])
            ->add('location', TextType::class, [
                'label' => 'Lieu',
                'required' => false,
            ])
            ->add('type', EntityType::class, [
                'class' => EventTypeEntity::class,
                'choice_label' => 'name',
                'label' => 'Type',
                'required' => false,
                'placeholder' => 'Tous les types'
            ])
            ->add('onlyAvailable', CheckboxType::class, [
                'label' => 'Afficher uniquement les événements avec des places disponibles',
                'required' => false,
                'label_attr' => [
                    'class' => 'checkbox-switch',
                ],
            ]);
    }

    public function getBlockPrefix(): string
    {
        return ''; // 🧼 évite le prefix ?event_search[q]
    }
}
