<?php

namespace App\Controller;

use App\Service\BattleNetApiService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\BisListRepository;
use Symfony\Component\HttpFoundation\Request; // <--- J'ai réactivé ça, c'est obligatoire

#[IsGranted('ROLE_USER')]
class ProfileController extends AbstractController
{
    #[Route('/profil', name: 'app_profile_index')]
    public function index(
        BattleNetApiService $battleNetApiService,
        BisListRepository $bisListRepository,
        Request $request // <--- Ajouté ici pour récupérer le choix de l'utilisateur
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();

        $activeCharacter = $user->getActiveCharacter();

        $characterData = null;
        $characterMedia = null;

        $bisList = null;       // La liste active (celle qu'on affiche)
        $compatibleLists = []; // Toutes les listes dispos pour ce perso (pour le menu déroulant)

        $equippedItemsBySlot = [];
        $bisItemsBySlot = [];

        // --- TES TABLEAUX DE MAPPING (Je n'y touche pas) ---
        $slotOrder = [
            'HEAD' => 'Tête',
            'NECK' => 'Cou',
            'SHOULDER' => 'Épaules',
            'CLOAK' => 'Dos',
            'CHEST' => 'Torse',
            'TABARD' => 'Tabard',
            'WRIST' => 'Poignets',
            'HANDS' => 'Mains',
            'WAIST' => 'Taille',
            'LEGS' => 'Jambes',
            'FEET' => 'Pieds',
            'FINGER_1' => 'Doigt 1',
            'FINGER_2' => 'Doigt 2',
            'TRINKET_1' => 'Bijou 1',
            'TRINKET_2' => 'Bijou 2',
            'MAIN_HAND' => 'Main droite',
            'OFF_HAND' => 'Main gauche',
            'RANGED' => 'À distance',
        ];
        $slotMapping = [
            'TÊTE' => 'HEAD',
            'TETE' => 'HEAD',
            'HEAD' => 'HEAD',
            'CASQUE' => 'HEAD',
            'COU' => 'NECK',
            'COLLIER' => 'NECK',
            'NECK' => 'NECK',
            'ÉPAULES' => 'SHOULDER',
            'EPAULES' => 'SHOULDER',
            'SHOULDER' => 'SHOULDER',
            'DOS' => 'CLOAK',
            'CAPE' => 'CLOAK',
            'BACK' => 'CLOAK',
            'CLOAK' => 'CLOAK',
            'TORSE' => 'CHEST',
            'ROBE' => 'CHEST',
            'PLASTRON' => 'CHEST',
            'CHEST' => 'CHEST',
            'POIGNETS' => 'WRIST',
            'BRASSARDS' => 'WRIST',
            'WRIST' => 'WRIST',
            'MAINS' => 'HANDS',
            'GANTS' => 'HANDS',
            'HANDS' => 'HANDS',
            'TAILLE' => 'WAIST',
            'CEINTURE' => 'WAIST',
            'WAIST' => 'WAIST',
            'JAMBES' => 'LEGS',
            'PANTALON' => 'LEGS',
            'JAMBIERES' => 'LEGS',
            'LEGS' => 'LEGS',
            'PIEDS' => 'FEET',
            'BOTTES' => 'FEET',
            'FEET' => 'FEET',
            'DOIGT 1' => 'FINGER_1',
            'ANNEAU 1' => 'FINGER_1',
            'BAGUE 1' => 'FINGER_1',
            'FINGER 1' => 'FINGER_1',
            'DOIGT 2' => 'FINGER_2',
            'ANNEAU 2' => 'FINGER_2',
            'BAGUE 2' => 'FINGER_2',
            'FINGER 2' => 'FINGER_2',
            'BIJOU 1' => 'TRINKET_1',
            'TRINKET 1' => 'TRINKET_1',
            'BIJOU 2' => 'TRINKET_2',
            'TRINKET 2' => 'TRINKET_2',
            'MAIN DROITE' => 'MAIN_HAND',
            'ARME' => 'MAIN_HAND',
            'MAIN_HAND' => 'MAIN_HAND',
            'MAIN HAND' => 'MAIN_HAND',
            'MAIN GAUCHE' => 'OFF_HAND',
            'BOUCLIER' => 'OFF_HAND',
            'OFF_HAND' => 'OFF_HAND',
            'OFF HAND' => 'OFF_HAND',
            'TENUE EN MAIN GAUCHE' => 'OFF_HAND',
            'A DISTANCE' => 'RANGED',
            'À DISTANCE' => 'RANGED',
            'RANGED' => 'RANGED',
            'RELIQUE' => 'RANGED',
            'BAGUETTE' => 'RANGED'
        ];

        if ($activeCharacter) {
            $characterData = $battleNetApiService->getCharacterProfile(
                $activeCharacter->getCharacterName(),
                $activeCharacter->getCharacterRealmSlug(),
                $activeCharacter->getCharacterRegion() ?? 'eu'
            );

            if ($characterData) {
                if (isset($characterData['media']['href'])) {
                    $characterMedia = $battleNetApiService->getCharacterMedia($characterData['media']['href']);
                }

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
                        unset($item);
                    }
                }

                $class = strtoupper($characterData['character_class']['name'] ?? null);
                $spec = $characterData['active_spec']['name'] ?? null;

                if ($class && $spec) {
                    // === NOUVELLE LOGIQUE DE SÉLECTION DE LA LISTE ===

                    // 1. On cherche TOUTES les listes compatibles avec la classe/spé du perso
                    $compatibleLists = $bisListRepository->findBy([
                        'characterClass' => $class,
                        'specialization' => $spec
                    ]);

                    // 2. L'utilisateur a-t-il cliqué sur une liste précise ?
                    $requestedBisId = $request->query->get('bis_id');

                    if ($requestedBisId) {
                        $candidate = $bisListRepository->find($requestedBisId);
                        // Sécurité : on vérifie que la liste demandée correspond bien à la classe du perso
                        if ($candidate && ($candidate->getCharacterClass() === $class && $candidate->getSpecialization() === $spec)) {
                            $bisList = $candidate;
                        }
                    }

                    // 3. Fallback : Si pas de choix (ou choix invalide), on prend la première dispo
                    if (!$bisList && count($compatibleLists) > 0) {
                        $bisList = $compatibleLists[0];
                    }

                    // === FIN NOUVELLE LOGIQUE ===

                    // Le reste est ton code d'origine qui traite $bisList
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
                                if (!isset($bisItemsBySlot['FINGER_1'])) {
                                    $bisItemsBySlot['FINGER_1'] = $bisItem;
                                } elseif (!isset($bisItemsBySlot['FINGER_2'])) {
                                    $bisItemsBySlot['FINGER_2'] = $bisItem;
                                }
                            } elseif ($technicalKey === 'TRINKET') {
                                if (!isset($bisItemsBySlot['TRINKET_1'])) {
                                    $bisItemsBySlot['TRINKET_1'] = $bisItem;
                                } elseif (!isset($bisItemsBySlot['TRINKET_2'])) {
                                    $bisItemsBySlot['TRINKET_2'] = $bisItem;
                                }
                            } else {
                                if (!isset($bisItemsBySlot[$technicalKey])) {
                                    $bisItemsBySlot[$technicalKey] = $bisItem;
                                }
                            }
                        }
                    }
                }
            }
        }

        $allBisItemIds = [];
        if ($bisList) {
            foreach ($bisList->getBisItems() as $item) {
                $allBisItemIds[] = $item->getItemId();
            }
        }
        $allBisItemIds = array_unique($allBisItemIds);

        $validatedBisItemIds = [];
        foreach ($equippedItemsBySlot as $item) {
            $equippedId = $item['item']['id'] ?? null;
            if ($equippedId && in_array($equippedId, $allBisItemIds)) {
                $validatedBisItemIds[] = $equippedId;
            }
        }

        return $this->render('profile/index.html.twig', [
            'user' => $user,
            'characterData' => $characterData,
            'characterMedia' => $characterMedia,
            'bisList' => $bisList,
            'compatibleLists' => $compatibleLists, // <--- AJOUT IMPORTANT POUR LE TWIG
            'slotOrder' => $slotOrder,
            'equippedItemsBySlot' => $equippedItemsBySlot,
            'bisItemsBySlot' => $bisItemsBySlot,
            'validatedBisItemIds' => $validatedBisItemIds,
            'activeCharacter' => $activeCharacter,
        ]);
    }
}
