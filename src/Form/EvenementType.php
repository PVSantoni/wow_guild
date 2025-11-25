<?php

namespace App\Form;

use App\Entity\Evenement;
use App\Entity\Categorie; // Assurez-vous que ce 'use' est présent
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class EvenementType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            ->add('categorie', EntityType::class, [
                // Entité à utiliser
                'class' => Categorie::class,
                // Propriété à afficher dans le menu déroulant
                'choice_label' => 'nom',
                'label' => 'Catégorie',
                // On dit à Symfony que ce champ ne peut pas être vide
                'required' => true,
                // On ajoute une première ligne vide pour forcer l'utilisateur à choisir
                'placeholder' => 'Choisissez une catégorie',
            ])
            ->add('description')
            ->add('dateDebut')
            ->add('nbPlacesMax')
        ;
    }

    // La méthode configureOptions n'est pas dans votre fichier, mais c'est une bonne pratique de l'ajouter
    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Evenement::class,
        ]);
    }
}
