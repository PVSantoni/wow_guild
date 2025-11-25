<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Categorie; // Assurez-vous que ce 'use' est présent
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('categorie', EntityType::class, [
                'class' => Categorie::class,
                'choice_label' => 'nom',
                'required' => true,
                'placeholder' => 'Choisissez une catégorie',
                // --> AJOUT : Un ID pour le JavaScript
                'attr' => ['id' => 'event_category']
            ])
            ->add('description')
            ->add('dateDebut')
            ->add('nbPlacesMax')
            ->add('tanksRequis', IntegerType::class, [
                'attr' => ['id' => 'event_tanks'] // --> AJOUT ID
            ])
            ->add('soigneursRequis', IntegerType::class, [
                'attr' => ['id' => 'event_heals'] // --> AJOUT ID
            ])
            ->add('dpsRequis', IntegerType::class, [
                'attr' => ['id' => 'event_dps'] // --> AJOUT ID
            ]);
    }
}
