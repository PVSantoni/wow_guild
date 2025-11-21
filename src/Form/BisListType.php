<?php

namespace App\Form;

use App\Entity\BisList;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType; // <-- Important
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
            // On remplace le champ de texte par un menu déroulant
            ->add('characterClass', ChoiceType::class, [
                'label' => 'Classe',
                'choices' => BisList::CLASSES_CHOICES, // On utilise notre tableau de constantes
                'placeholder' => 'Choisir une classe...', // Optionnel, ajoute une ligne vide au début
            ])
            // On fait de même pour la spécialisation
            ->add('specialization', ChoiceType::class, [
                'label' => 'Spécialisation',
                'choices' => BisList::SPECS_CHOICES,
                'placeholder' => 'Choisir une spécialisation...',
            ])
            ->add('wowsimsJson', TextareaType::class, [
                'label' => 'Coller le JSON de Wowsims ici',
                'mapped' => false,
                'attr' => ['rows' => 15],
                'help' => 'Copiez-collez l\'export JSON de votre liste BiS depuis Wowsims.', // Ajoute un texte d'aide
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => BisList::class,
        ]);
    }
}
