<?php

namespace App\Form;

use App\Entity\BisList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class BisListType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Nom de la liste (ex: Mage Givre PVE T14)',
            ])
            // 1. Les Menus Déroulants (C'est parfait ici)
            ->add('characterClass', ChoiceType::class, [
                'label' => 'Classe',
                'choices' => BisList::CLASSES_CHOICES,
                'placeholder' => 'Choisir une classe...',
            ])
            ->add('specialization', ChoiceType::class, [
                'label' => 'Spécialisation',
                'choices' => BisList::SPECS_CHOICES,
                'placeholder' => 'Choisir une spécialisation...',
            ])
            // 2. Le champ JSON (On le garde, ça peut servir)
            ->add('wowsimsJson', TextareaType::class, [
                'label' => 'Import JSON Wowsims (Optionnel)',
                'mapped' => false,
                'required' => false,
                'attr' => ['rows' => 5],
                'help' => 'Si vous collez un JSON ici, les champs ci-dessous seront ignorés.',
            ]);

        // 3. LA BOUCLE MANQUANTE POUR LES ITEMS
        // Sans ça, ta grille d'équipement est vide !
        $slots = [
            'head' => 'Tête',
            'neck' => 'Cou',
            'shoulder' => 'Épaules',
            'cloak' => 'Dos',
            'chest' => 'Torse',
            'wrist' => 'Poignets',
            'hands' => 'Mains',
            'waist' => 'Taille',
            'legs' => 'Jambes',
            'feet' => 'Pieds',
            'finger_1' => 'Doigt 1',
            'finger_2' => 'Doigt 2',
            'trinket_1' => 'Bijou 1',
            'trinket_2' => 'Bijou 2',
            'main_hand' => 'Main Droite',
            'off_hand' => 'Main Gauche',
            'ranged' => 'À Distance'
        ];

        foreach ($slots as $key => $label) {
            $builder->add($key, IntegerType::class, [
                'label' => $label,
                'mapped' => false,   // Important : ce n'est pas lié direct à BisList
                'required' => false, // On peut laisser vide
                'attr' => ['placeholder' => 'ID item'],
            ]);
        }
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BisList::class,
        ]);
    }
}
