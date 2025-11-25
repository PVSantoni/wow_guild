<?php

namespace App\Controller;

use App\Entity\Character;
use App\Form\CharacterType;
use App\Repository\CharacterClassRepository;
use App\Repository\SpecializationRepository;
use App\Service\BattleNetApiService;
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
        // On passe l'utilisateur pour éviter les soucis dans la vue
        return $this->render('character/index.html.twig', [
            'user' => $this->getUser()
        ]);
    }

    #[Route('/new', name: 'app_character_new', methods: ['GET', 'POST'])]
    public function new(
        Request $request,
        EntityManagerInterface $entityManager,
        BattleNetApiService $blizzardApi,
        CharacterClassRepository $classRepo,
        SpecializationRepository $specRepo
    ): Response {
        $user = $this->getUser();
        if (!$user instanceof \App\Entity\User) {
            throw $this->createAccessDeniedException();
        }

        $character = new Character();
        $form = $this->createForm(CharacterType::class, $character);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {

            // 1. Appel API
            $apiProfile = $blizzardApi->getCharacterProfileSummary(
                $character->getCharacterRealmSlug(),
                $character->getCharacterName(),
                $character->getCharacterRegion()
            );

            if (!$apiProfile) {
                $this->addFlash('danger', 'Personnage introuvable sur l\'Armurerie Blizzard. Vérifiez le royaume.');
                return $this->render('character/new.html.twig', ['form' => $form]);
            }

            // 2. Remplissage Auto
            $character->setCharacterName($apiProfile['name']);
            $character->setLevel((int) $apiProfile['level']);

            // 3. Classe
            if (isset($apiProfile['character_class']['name'])) {
                $classEntity = $classRepo->findOneBy(['name' => $apiProfile['character_class']['name']]);
                if ($classEntity) $character->setClass($classEntity);
            }

            // 4. Spécialisation
            if (isset($apiProfile['active_spec']['name']) && isset($classEntity)) {
                $specName = $apiProfile['active_spec']['name'];
                $specEntity = $specRepo->createQueryBuilder('s')
                    ->where('s.name LIKE :specName')
                    ->andWhere('s.characterClass = :class')
                    ->setParameter('specName', $specName . '%')
                    ->setParameter('class', $classEntity)
                    ->setMaxResults(1)
                    ->getQuery()
                    ->getOneOrNullResult();

                if ($specEntity) $character->setActiveSpec($specEntity);
            }

            // 5. Avatar
            $avatarUrl = $blizzardApi->getCharacterAvatar(
                $character->getCharacterRealmSlug(),
                $character->getCharacterName(),
                $character->getCharacterRegion()
            );
            $character->setThumbnail($avatarUrl);

            // 6. Sauvegarde
            $user->addCharacter($character);
            if ($user->getCharacters()->count() === 1) {
                $user->setActiveCharacter($character);
            }

            $entityManager->persist($character);
            $entityManager->flush();

            $this->addFlash('success', 'Personnage importé avec succès !');
            return $this->redirectToRoute('app_character_index');
        }

        return $this->render('character/new.html.twig', [
            'form' => $form,
        ]);
    }

    // C'EST CETTE ROUTE QUI TE MANQUAIT :
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

        $this->addFlash('success', $character->getCharacterName() . ' est maintenant actif.');
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
