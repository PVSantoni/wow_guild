<?php

namespace App\Controller;

use App\Entity\Inscription;
use App\Repository\InscriptionRepository;
use App\Entity\Evenement;
use App\Form\EvenementType;
use App\Repository\EvenementRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Repository\CategorieRepository;

#[Route('/evenement')]
final class EvenementController extends AbstractController
{

    #[Route('/', name: 'app_evenement_index', methods: ['GET'])]
    // Route pour la page filtrée (ex: /evenements/categorie/1)
    #[Route('/categorie/{id}', name: 'app_evenement_filter_category', methods: ['GET'])]
    public function index(
        EvenementRepository $evenementRepository,
        CategorieRepository $categorieRepository,
        int $id = null // Ce paramètre sera rempli par la 2ème route
    ): Response {
        // La logique PHP à l'intérieur ne change PAS
        $categories = $categorieRepository->findAll();

        if ($id) {
            // Si un ID est fourni, on ne cherche que les événements de cette catégorie
            $evenements = $evenementRepository->findBy(['categorie' => $id], ['dateDebut' => 'ASC']);
        } else {
            // Sinon, on prend tous les événements à venir
            $evenements = $evenementRepository->createQueryBuilder('e')
                ->where('e.dateDebut > :now')
                ->setParameter('now', new \DateTime())
                ->orderBy('e.dateDebut', 'ASC')
                ->getQuery()
                ->getResult();
        }

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'categories' => $categories,
        ]);
    }

    #[Route('/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenement);
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(Evenement $evenement): Response
    {
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
        ]);
    }

    #[Route('/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])] #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    #[Route('/{id}', name: 'app_evenement_delete', methods: ['POST'])] #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->getPayload()->getString('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }

    #[Route('/{id}/inscription', name: 'app_evenement_inscription', methods: ['POST'])]
    public function inscription(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        InscriptionRepository $inscriptionRepository
    ): Response {
        // 1. S'assurer que l'utilisateur est connecté
        $this->denyAccessUnlessGranted('IS_AUTHENTICATED_FULLY');
        $user = $this->getUser();

        // 2. Récupérer le statut envoyé par le formulaire
        $statut = $request->request->get('statut');

        // 3. Vérifier que le statut est valide
        if (!in_array($statut, ['Confirmé', 'Incertain', 'Absent'])) {
            // Gérer l'erreur, par exemple rediriger avec un message
            $this->addFlash('error', 'Statut non valide.');
            return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
        }

        // 4. Chercher si une inscription existe déjà pour cet utilisateur et cet événement
        $inscription = $inscriptionRepository->findOneBy([
            'user' => $user,
            'evenement' => $evenement
        ]);

        // Si le statut est "Absent" et qu'une inscription existe, on la supprime
        if ($statut === 'Absent') {
            if ($inscription) {
                $entityManager->remove($inscription);
                $this->addFlash('success', 'Votre désinscription a été prise en compte.');
            }
        } else {
            // Si l'inscription n'existe pas, on la crée
            if (!$inscription) {
                $inscription = new Inscription();
                $inscription->setUser($user);
                $inscription->setEvenement($evenement);
            }
            // On met à jour le statut
            $inscription->setStatut($statut);
            $entityManager->persist($inscription);
            $this->addFlash('success', 'Votre inscription a bien été enregistrée !');
        }

        // 5. On sauvegarde en base de données
        $entityManager->flush();

        // 6. On redirige vers la page de l'événement
        return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
    }
}
