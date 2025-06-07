<?php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\Extension\Core\Type\EmailType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\PasswordType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Validator\Constraints\NotBlank;
use Symfony\Component\Validator\Constraints\Length;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Vich\UploaderBundle\Form\Type\VichImageType;
use Symfony\Component\Form\Extension\Core\Type\RepeatedType;

class RegistrationFormType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('username')
            ->add('email', EmailType::class)
            ->add('profileImageFile', VichImageType::class, [
                'label' => 'Image de profil',
                'required' => false,
                'allow_delete' => true,
                'download_uri' => false,
            ])
            ->add('plainPassword', RepeatedType::class, [
                'type' => PasswordType::class,
                'first_options'  => [
                    'label' => 'Mot de passe',
                    'attr' => [
                        'autocomplete' => 'new-password'
                    ]
                ],
                'second_options' => [
                    'label' => 'Confirmation du mot de passe',
                    'attr' => [
                        'autocomplete' => 'new-password'
                    ]
                ],
                'invalid_message' => 'Les deux mots de passe doivent correspondre.',
                'mapped' => false, // très important ! sinon Symfony veut le setter direct
                'required' => true,
            ])
            ->add('becomeOrganizer', CheckboxType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Je souhaite devenir organisateur'
            ])
            ->add('motivation', TextareaType::class, [
                'mapped' => false,
                'required' => false,
                'label' => 'Expliquez votre motivation',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
