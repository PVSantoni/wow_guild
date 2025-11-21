<?php

namespace App\Controller;

use App\Entity\BisList;
use App\Entity\BisItem;
use App\Form\BisListType;
use App\Repository\BisListRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[Route('/admin/bislist')] // On préfixe toutes les routes par /admin/bislist
#[IsGranted('ROLE_ADMIN')] // On sécurise tout le contrôleur
class BisListController extends AbstractController
{
    #[Route('/', name: 'app_bis_list_index', methods: ['GET'])]
    public function index(BisListRepository $bisListRepository): Response
    {
        return $this->render('bis_list/index.html.twig', [
            'bis_lists' => $bisListRepository->findAll(),
        ]);
    }

    #[Route('/new', name: 'app_bis_list_new', methods: ['GET', 'POST'])]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $bisList = new BisList();
        $form = $this->createForm(BisListType::class, $bisList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $jsonString = $form->get('wowsimsJson')->getData();
            if ($jsonString) {
                $this->processWowsimsJson($jsonString, $bisList, $entityManager);
            }

            $entityManager->persist($bisList);
            $entityManager->flush();

            $this->addFlash('success', 'La nouvelle liste BiS a été créée avec succès.');
            return $this->redirectToRoute('app_bis_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bis_list/new.html.twig', [
            'bis_list' => $bisList,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bis_list_show', methods: ['GET'])]
    public function show(BisList $bisList): Response
    {
        return $this->render('bis_list/show.html.twig', [
            'bis_list' => $bisList,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_bis_list_edit', methods: ['GET', 'POST'])]
    public function edit(Request $request, BisList $bisList, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(BisListType::class, $bisList);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            foreach ($bisList->getBisItems() as $item) {
                $entityManager->remove($item);
            }
            $entityManager->flush();

            $jsonString = $form->get('wowsimsJson')->getData();
            if ($jsonString) {
                $this->processWowsimsJson($jsonString, $bisList, $entityManager);
            }

            $entityManager->flush();

            $this->addFlash('success', 'La liste BiS a été mise à jour avec succès.');
            return $this->redirectToRoute('app_bis_list_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('bis_list/edit.html.twig', [
            'bis_list' => $bisList,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_bis_list_delete', methods: ['POST'])]
    public function delete(Request $request, BisList $bisList, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $bisList->getId(), $request->request->get('_token'))) {
            $entityManager->remove($bisList);
            $entityManager->flush();
            $this->addFlash('success', 'La liste BiS a été supprimée.');
        }

        return $this->redirectToRoute('app_bis_list_index', [], Response::HTTP_SEE_OTHER);
    }

    private function processWowsimsJson(string $jsonString, BisList $bisList, EntityManagerInterface $entityManager): void
    {
        $data = json_decode($jsonString, true);
        if (!$data || !isset($data['player']['equipment']['items'])) {
            return;
        }
        $items = $data['player']['equipment']['items'];
        $slotMap = [
            0 => 'HEAD',
            1 => 'NECK',
            2 => 'SHOULDER',
            3 => 'CLOAK',
            4 => 'CHEST',
            5 => 'WRIST',
            6 => 'HANDS',
            7 => 'WAIST',
            8 => 'LEGS',
            9 => 'FEET',
            10 => 'FINGER_1',  // Correction
            11 => 'FINGER_2',  // Correction
            12 => 'TRINKET_1', // Correction
            13 => 'TRINKET_2', // Correction
            14 => 'MAIN_HAND',
            15 => 'OFF_HAND'
        ];
        foreach ($items as $index => $itemData) {
            if (isset($itemData['id']) && isset($slotMap[$index])) {
                $bisItem = new BisItem();
                $bisItem->setItemId($itemData['id']);
                $bisItem->setSlot($slotMap[$index]);
                $bisItem->setItemName('Item ' . $itemData['id']);
                $bisItem->setBisList($bisList);
                $entityManager->persist($bisItem);
            }
        }
    }
}
