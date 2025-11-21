<?php

namespace App\Controller;

use App\Entity\Character;
use App\Form\CharacterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/character')]
#[IsGranted('ROLE_USER')]
class CharacterController extends AbstractController
{
    #[Route('/', name: 'app_character_index', methods: ['GET'])]
    public function index(): Response
    {
        return $this->render('character/index.html.twig');
    }

    #[Route('/new', name: 'app_character_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */ // CORRECTION 1: On s'assure que PHP sait que $user est notre entité User
        $user = $this->getUser();

        $character = new Character();
        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // CORRECTION 2: On ajoute le personnage à la collection de l'utilisateur AVANT de sauvegarder
            // Cela met à jour la relation des deux côtés en même temps.
            $user->addCharacter($character);

            // Si c'est le premier personnage dans la collection, on le définit comme actif
            if ($user->getCharacters()->count() === 1) {
                $user->setActiveCharacter($character);
            }

            // On a seulement besoin de persister l'utilisateur, Doctrine s'occupera du personnage
            // grâce à la cascade que nous avons configurée. Mais persister les deux ne fait pas de mal.
            $entityManager->persist($character);
            $entityManager->flush();

            $this->addFlash('success', 'Le personnage a été ajouté avec succès.');
            return $this->redirectToRoute('app_character_index');
        }

        return $this->render('character/new.html.twig', [
            'form' => $form,
        ]);
    }

    #[Route('/{id}/set-active', name: 'app_character_set_active', methods: ['POST'])]
    public function setActive(Character $character, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($character->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        $user->setActiveCharacter($character);
        $entityManager->flush();

        $this->addFlash('success', $character->getCharacterName() . ' est maintenant votre personnage actif.');
        return $this->redirectToRoute('app_character_index');
    }

    #[Route('/{id}/edit', name: 'app_character_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, Character $character, EntityManagerInterface $entityManager): Response
    {
        if ($character->getUser() !== $this->getUser()) {
            throw $this->createAccessDeniedException();
        }

        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();
            $this->addFlash('success', 'Le personnage a été mis à jour.');
            return $this->redirectToRoute('app_character_index');
        }

        return $this->render('character/edit.html.twig', [
            'character' => $character,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_character_delete', methods: ['POST'])]
    public function delete(Request $request, Character $character, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        if ($character->getUser() !== $user) {
            throw $this->createAccessDeniedException();
        }

        if ($this->isCsrfTokenValid('delete' . $character->getId(), $request->request->get('_token'))) {
            if ($user->getActiveCharacter() === $character) {
                $user->setActiveCharacter(null);
            }
            $entityManager->remove($character);
            $entityManager->flush();
            $this->addFlash('success', 'Le personnage a été supprimé.');
        }

        return $this->redirectToRoute('app_character_index');
    }
}
