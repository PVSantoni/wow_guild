<?php

namespace App\Form;

use App\Entity\Evenement;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use App\Entity\Categorie; 

class EvenementType extends AbstractType
{
    
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('nom')
            // Ajout du champ pour la catégorie
            ->add('categorie', EntityType::class, [
                // Entité à utiliser
                'class' => Categorie::class,
                // Propriété à afficher dans le menu déroulant
                'choice_label' => 'nom',
                'label' => 'Catégorie'
            ])
            ->add('description')
            ->add('dateDebut')
            ->add('nbPlacesMax')
        ;
    }
}
