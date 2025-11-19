<?php
// src/Form/ProfileCharacterType.php

namespace App\Form;

use App\Entity\User;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProfileCharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('characterName', TextType::class, [
                'label' => 'Nom du personnage',
                'required' => true,
            ])
            ->add('characterRealmSlug', TextType::class, [
                'label' => 'Slug du serveur (ex: pyrewood-village)',
                'required' => true,
            ])
            ->add('characterRegion', ChoiceType::class, [
                'label' => 'Région',
                'choices' => [
                    'Europe' => 'eu',
                    'Amérique du Nord' => 'us',
                    'Corée' => 'kr',
                    'Taïwan' => 'tw',
                ],
                'required' => true,
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => User::class,
        ]);
    }
}
