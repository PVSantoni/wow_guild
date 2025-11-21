<?php

namespace App\Form;

use App\Entity\Character;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CharacterType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('characterName', null, [
                'label' => 'Nom du personnage',
                'attr' => ['placeholder' => 'Ex: Styxylul']
            ])
            ->add('characterRealmSlug', null, [
                'label' => 'Slug du serveur',
                'attr' => ['placeholder' => 'Ex: archimonde']
            ])
            ->add('characterRegion', ChoiceType::class, [
                'label' => 'Région',
                'choices'  => [
                    'Europe' => 'eu',
                    'Amérique du Nord' => 'us',
                    'Corée' => 'kr',
                    'Taïwan' => 'tw',
                ],
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Character::class,
        ]);
    }
}
