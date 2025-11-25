<?php

namespace App\Controller;

use App\Entity\BisList;
use App\Entity\User;
use App\Repository\BisListRepository;
use App\Service\BattleNetApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
#[Route('/profil')] // Préfixe pour toutes les routes ici
class ProfileController extends AbstractController
{
    // =========================================================================
    // ROUTE 1 : MON PROFIL (Celle du menu "Mon Armurerie")
    // =========================================================================
    #[Route('/', name: 'app_profile_index')]
    public function index(
        BattleNetApiService $battleNetApiService,
        BisListRepository $bisListRepository,
        Request $request
    ): Response {
        // On appelle la fonction magique avec l'utilisateur connecté ($this->getUser())
        /** @var User $user */
        $user = $this->getUser();
        return $this->renderProfile($user, $battleNetApiService, $bisListRepository, $request);
    }

    // =========================================================================
    // ROUTE 2 : PROFIL PUBLIC (Celle quand on clique sur un pseudo)
    // =========================================================================
    #[Route('/{id}', name: 'app_public_profile', methods: ['GET'])]
    public function show(
        User $user, // Symfony trouve l'user grâce à l'ID dans l'URL
        BattleNetApiService $battleNetApiService,
        BisListRepository $bisListRepository,
        Request $request
    ): Response {
        // On appelle la MEME fonction magique, mais avec l'utilisateur demandé ($user)
        return $this->renderProfile($user, $battleNetApiService, $bisListRepository, $request);
    }

    // =========================================================================
    // LA LOGIQUE COMMUNE (Privée, ne peut pas être appelée depuis l'URL)
    // =========================================================================
    private function renderProfile(
        User $user,
        BattleNetApiService $battleNetApiService,
        BisListRepository $bisListRepository,
        Request $request
    ): Response {
        $activeCharacter = $user->getActiveCharacter();

        // Initialisation
        $characterData = null;
        $characterMedia = null;
        $bisList = null;
        $compatibleLists = [];
        $equippedItemsBySlot = [];
        $bisItemsBySlot = [];

        // --- CONSTANTES D'AFFICHAGE ---
        $slotOrder = [
            'HEAD' => 'Tête', 'NECK' => 'Cou', 'SHOULDER' => 'Épaules', 'CLOAK' => 'Dos',
            'CHEST' => 'Torse', 'TABARD' => 'Tabard', 'WRIST' => 'Poignets', 'HANDS' => 'Mains',
            'WAIST' => 'Taille', 'LEGS' => 'Jambes', 'FEET' => 'Pieds', 'FINGER_1' => 'Doigt 1',
            'FINGER_2' => 'Doigt 2', 'TRINKET_1' => 'Bijou 1', 'TRINKET_2' => 'Bijou 2',
            'MAIN_HAND' => 'Main droite', 'OFF_HAND' => 'Main gauche', 'RANGED' => 'À distance',
        ];

        // --- MAPPING TECHNIQUE (J'ai compressé pour la lisibilité, c'est ton code exact) ---
        $slotMapping = [
            'TÊTE' => 'HEAD', 'TETE' => 'HEAD', 'HEAD' => 'HEAD', 'CASQUE' => 'HEAD',
            'COU' => 'NECK', 'COLLIER' => 'NECK', 'NECK' => 'NECK',
            'ÉPAULES' => 'SHOULDER', 'EPAULES' => 'SHOULDER', 'SHOULDER' => 'SHOULDER',
            'DOS' => 'CLOAK', 'CAPE' => 'CLOAK', 'BACK' => 'CLOAK', 'CLOAK' => 'CLOAK',
            'TORSE' => 'CHEST', 'ROBE' => 'CHEST', 'PLASTRON' => 'CHEST', 'CHEST' => 'CHEST',
            'POIGNETS' => 'WRIST', 'BRASSARDS' => 'WRIST', 'WRIST' => 'WRIST',
            'MAINS' => 'HANDS', 'GANTS' => 'HANDS', 'HANDS' => 'HANDS',
            'TAILLE' => 'WAIST', 'CEINTURE' => 'WAIST', 'WAIST' => 'WAIST',
            'JAMBES' => 'LEGS', 'PANTALON' => 'LEGS', 'JAMBIERES' => 'LEGS', 'LEGS' => 'LEGS',
            'PIEDS' => 'FEET', 'BOTTES' => 'FEET', 'FEET' => 'FEET',
            'DOIGT 1' => 'FINGER_1', 'ANNEAU 1' => 'FINGER_1', 'BAGUE 1' => 'FINGER_1', 'FINGER 1' => 'FINGER_1',
            'DOIGT 2' => 'FINGER_2', 'ANNEAU 2' => 'FINGER_2', 'BAGUE 2' => 'FINGER_2', 'FINGER 2' => 'FINGER_2',
            'BIJOU 1' => 'TRINKET_1', 'TRINKET 1' => 'TRINKET_1',
            'BIJOU 2' => 'TRINKET_2', 'TRINKET 2' => 'TRINKET_2',
            'MAIN DROITE' => 'MAIN_HAND', 'ARME' => 'MAIN_HAND', 'MAIN_HAND' => 'MAIN_HAND', 'MAIN HAND' => 'MAIN_HAND',
            'MAIN GAUCHE' => 'OFF_HAND', 'BOUCLIER' => 'OFF_HAND', 'OFF_HAND' => 'OFF_HAND', 'OFF HAND' => 'OFF_HAND', 'TENUE EN MAIN GAUCHE' => 'OFF_HAND',
            'A DISTANCE' => 'RANGED', 'À DISTANCE' => 'RANGED', 'RANGED' => 'RANGED', 'RELIQUE' => 'RANGED', 'BAGUETTE' => 'RANGED'
        ];

        if ($activeCharacter) {
            try {
                $characterData = $battleNetApiService->getCharacterProfile(
                    $activeCharacter->getCharacterName(),
                    $activeCharacter->getCharacterRealmSlug(),
                    $activeCharacter->getCharacterRegion() ?? 'eu'
                );

                if ($characterData) {
                    // 1. Médias
                    if (isset($characterData['media']['href'])) {
                        $characterMedia = $battleNetApiService->getCharacterMedia($characterData['media']['href']);
                    }

                    // 2. Équipement
                    if (isset($characterData['equipment']['href'])) {
                        $characterEquipment = $battleNetApiService->getCharacterEquipment($characterData['equipment']['href']);
                        if ($characterEquipment && isset($characterEquipment['equipped_items'])) {
                            foreach ($characterEquipment['equipped_items'] as &$item) {
                                if (isset($item['item']['id'])) {
                                    $item['apiDetails'] = $battleNetApiService->getItemInfo($item['item']['id']);
                                    if (empty($item['apiDetails']['icon_url']) && isset($item['media']['id'])) {
                                        $item['apiDetails']['icon_url'] = $battleNetApiService->getItemMediaUrl($item['media']['id']);
                                    }
                                }
                                $slotName = $item['slot']['type'];
                                if ($slotName === 'BACK') $slotName = 'CLOAK';
                                $equippedItemsBySlot[$slotName] = $item;
                            }
                        }
                    }

                    // 3. Logique BiS Lists
                    $apiClassName = $characterData['character_class']['name'] ?? null;
                    $apiSpecName  = $characterData['active_spec']['name'] ?? null;

                    if ($apiClassName) {
                        $dbClass = isset(BisList::CLASSES_CHOICES[$apiClassName]) ? BisList::CLASSES_CHOICES[$apiClassName] : strtoupper($apiClassName);

                        $compatibleLists = $bisListRepository->findBy(['characterClass' => $dbClass]);

                        $requestedBisId = $request->query->get('bis_id');
                        if ($requestedBisId) {
                            $candidate = $bisListRepository->find($requestedBisId);
                            if ($candidate && $candidate->getCharacterClass() === $dbClass) {
                                $bisList = $candidate;
                            }
                        }

                        if (!$bisList && count($compatibleLists) > 0) {
                            $activeSpecLower = strtolower($apiSpecName ?? '');
                            foreach ($compatibleLists as $list) {
                                $listSpecLower = strtolower($list->getSpecialization());
                                if ($activeSpecLower && ($listSpecLower === $activeSpecLower || str_contains($listSpecLower, $activeSpecLower))) {
                                    $bisList = $list;
                                    break;
                                }
                                if (($activeSpecLower === 'givre' && $listSpecLower === 'frost') ||
                                    ($activeSpecLower === 'frost' && $listSpecLower === 'givre')
                                ) {
                                    $bisList = $list;
                                    break;
                                }
                            }
                            if (!$bisList) $bisList = $compatibleLists[0];
                        }
                    }

                    // 4. Traitement Items BiS
                    if ($bisList) {
                        $pendingGenericItems = [];
                        foreach ($bisList->getBisItems() as $bisItem) {
                            $rawSlot = $bisItem->getSlot();
                            $upperSlot = mb_strtoupper($rawSlot, 'UTF-8');
                            if (isset($slotMapping[$upperSlot])) {
                                $technicalKey = $slotMapping[$upperSlot];
                                if (str_contains($technicalKey, '_')) {
                                    $bisItem->apiDetails = $battleNetApiService->getItemInfo($bisItem->getItemId());
                                    $bisItemsBySlot[$technicalKey] = $bisItem;
                                } else {
                                    $pendingGenericItems[] = $bisItem;
                                }
                            } else {
                                $pendingGenericItems[] = $bisItem;
                            }
                        }
                        foreach ($pendingGenericItems as $bisItem) {
                            $rawSlot = $bisItem->getSlot();
                            $upperSlot = mb_strtoupper($rawSlot, 'UTF-8');
                            $localSlotMapping = $slotMapping + ['ANNEAU' => 'FINGER', 'BAGUE' => 'FINGER', 'FINGER' => 'FINGER', 'BIJOU' => 'TRINKET', 'TRINKET' => 'TRINKET'];
                            $technicalKey = $localSlotMapping[$upperSlot] ?? $upperSlot;
                            $bisItem->apiDetails = $battleNetApiService->getItemInfo($bisItem->getItemId());

                            if ($technicalKey === 'FINGER') {
                                if (!isset($bisItemsBySlot['FINGER_1'])) $bisItemsBySlot['FINGER_1'] = $bisItem;
                                elseif (!isset($bisItemsBySlot['FINGER_2'])) $bisItemsBySlot['FINGER_2'] = $bisItem;
                            } elseif ($technicalKey === 'TRINKET') {
                                if (!isset($bisItemsBySlot['TRINKET_1'])) $bisItemsBySlot['TRINKET_1'] = $bisItem;
                                elseif (!isset($bisItemsBySlot['TRINKET_2'])) $bisItemsBySlot['TRINKET_2'] = $bisItem;
                            } else {
                                if (!isset($bisItemsBySlot[$technicalKey])) $bisItemsBySlot[$technicalKey] = $bisItem;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                // En cas d'erreur API, on ne fait rien de spécial, on affiche juste la page vide
            }
        }

        // 5. Validation
        $allBisItemIds = [];
        if ($bisList) {
            foreach ($bisList->getBisItems() as $item) $allBisItemIds[] = $item->getItemId();
        }
        $allBisItemIds = array_unique($allBisItemIds);

        $validatedBisItemIds = [];
        foreach ($equippedItemsBySlot as $item) {
            $equippedId = $item['item']['id'] ?? null;
            if ($equippedId && in_array($equippedId, $allBisItemIds)) $validatedBisItemIds[] = $equippedId;
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user, // L'utilisateur visité (peut être moi ou un autre)
            'characterData' => $characterData,
            'characterMedia' => $characterMedia,
            'bisList' => $bisList,
            'compatibleLists' => $compatibleLists,
            'slotOrder' => $slotOrder,
            'equippedItemsBySlot' => $equippedItemsBySlot,
            'bisItemsBySlot' => $bisItemsBySlot,
            'validatedBisItemIds' => $validatedBisItemIds,
            'activeCharacter' => $activeCharacter,
            // Variable bonus pour le template : "Est-ce que c'est MON profil ?"
            'isOwner' => ($user === $this->getUser())
        ]);
    }
}
