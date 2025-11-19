<?php

namespace App\Controller;

use App\Service\BattleNetApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Form\ProfileCharacterType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\HttpFoundation\Request;

#[IsGranted('ROLE_USER')] // Sécurise tout le contrôleur
class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile_index')]
    public function index(BattleNetApiService $battleNetApiService): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $characterData = null;
        $characterMedia = null;
        $characterEquipment = null; // On initialise la variable pour l'équipement

        if ($user->getCharacterName() && $user->getCharacterRealmSlug()) {
            // 1er Appel : Profil
            $characterData = $battleNetApiService->getCharacterProfile(
                $user->getCharacterName(),
                $user->getCharacterRealmSlug(),
                $user->getCharacterRegion() ?? 'eu'
            );

            if ($characterData) {
                // 2ème Appel : Médias (si l'URL existe)
                if (isset($characterData['media']['href'])) {
                    $characterMedia = $battleNetApiService->getCharacterMedia(
                        $characterData['media']['href']
                    );
                }

                // 3ème Appel : Équipement (si l'URL existe)
                if (isset($characterData['equipment']['href'])) {
                    $characterEquipment = $battleNetApiService->getCharacterEquipment(
                        $characterData['equipment']['href']
                    );
                }
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'characterData' => $characterData,
            'characterMedia' => $characterMedia,
            'characterEquipment' => $characterEquipment, // On envoie l'équipement au template
        ]);
    }

    #[Route('/profil/modifier', name: 'app_profile_edit')]
    public function edit(Request $request, EntityManagerInterface $entityManager): Response
    {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $form = $this->createForm(ProfileCharacterType::class, $user);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            // Le formulaire est valide, on sauvegarde les données
            $entityManager->flush();

            // On ajoute un message de succès
            $this->addFlash('success', 'Votre personnage a bien été mis à jour !');

            // On redirige vers la page de profil pour voir le résultat
            return $this->redirectToRoute('app_profile_index');
        }

        return $this->render('profile/edit.html.twig', [
            'profileForm' => $form->createView(),
        ]);
    }
}
