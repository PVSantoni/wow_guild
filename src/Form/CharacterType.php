<?php

namespace App\Form;

use App\Entity\Character;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('characterName', TextType::class, [
                'label' => 'Nom du personnage',
                'attr' => ['class' => 'wow-input', 'placeholder' => 'Ex: Illidan']
            ])
            ->add('characterRealmSlug', TextType::class, [
                'label' => 'Royaume (Slug)',
                'help' => 'Exemple : hyjal, archimonde, ysondre',
                'attr' => ['class' => 'wow-input', 'placeholder' => 'hyjal']
            ])
            ->add('characterRegion', TextType::class, [
                'label' => 'Région',
                'attr' => ['class' => 'wow-input', 'value' => 'eu', 'readonly' => true] // Souvent bloqué sur EU
            ])
            // ON SUPPRIME : Level, Class, ActiveSpec, Thumbnail
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
        ]);
    }
}
