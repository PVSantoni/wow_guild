<?php

namespace App\Controller;

use App\Entity\Evenement;
use App\Entity\Inscription;
use App\Form\EvenementType;
use App\Repository\CategorieRepository;
use App\Repository\CharacterClassRepository;
use App\Repository\EvenementRepository;
use App\Repository\InscriptionRepository;
use App\Repository\SpecializationRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

// NOTE : Le préfixe de route global a été supprimé pour éviter les conflits.
// Chaque méthode définit sa route complète.
final class EvenementController extends AbstractController
{
    /**
     * Affiche la liste des événements, potentiellement filtrée par catégorie.
     */
    #[Route('/evenements', name: 'app_evenement_index', methods: ['GET'])]
    #[Route('/evenements/categorie/{id}', name: 'app_evenement_filter_category', methods: ['GET'])]
    public function index(
        EvenementRepository $evenementRepository,
        CategorieRepository $categorieRepository,
        int $id = null
    ): Response {
        $categories = $categorieRepository->findAll();

        if ($id) {
            $evenements = $evenementRepository->findBy(['categorie' => $id], ['dateDebut' => 'ASC']);
        } else {
            // Affiche tous les événements, passés et futurs, pour la liste publique
            $evenements = $evenementRepository->findBy([], ['dateDebut' => 'ASC']);
        }

        return $this->render('evenement/index.html.twig', [
            'evenements' => $evenements,
            'categories' => $categories,
        ]);
    }

    /**
     * Gère la création d'un nouvel événement (accessible aux admins).
     */
    #[Route('/evenement/new', name: 'app_evenement_new', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function new(Request $request, EntityManagerInterface $entityManager): Response
    {
        $evenement = new Evenement();
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->persist($evenement);
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été créé avec succès.');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/new.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    /**
     * Affiche la page de détail d'un événement.
     */
    #[Route('/evenement/{id}', name: 'app_evenement_show', methods: ['GET'])]
    public function show(
        Evenement $evenement,
        CharacterClassRepository $classRepository,
        SpecializationRepository $specRepository
    ): Response {
        return $this->render('evenement/show.html.twig', [
            'evenement' => $evenement,
            'classes' => $classRepository->findBy([], ['name' => 'ASC']),
            'specializations' => $specRepository->findAll(),
        ]);
    }

    /**
     * Gère la modification d'un événement existant (accessible aux admins).
     */
    #[Route('/evenement/{id}/edit', name: 'app_evenement_edit', methods: ['GET', 'POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function edit(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        $form = $this->createForm(EvenementType::class, $evenement);
        $form->handleRequest($request);

        if ($form->isSubmitted() && $form->isValid()) {
            $entityManager->flush();

            $this->addFlash('success', 'L\'événement a été mis à jour.');
            return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
        }

        return $this->render('evenement/edit.html.twig', [
            'evenement' => $evenement,
            'form' => $form,
        ]);
    }

    /**
     * Gère la suppression d'un événement (accessible aux admins).
     */
    #[Route('/evenement/{id}', name: 'app_evenement_delete', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function delete(Request $request, Evenement $evenement, EntityManagerInterface $entityManager): Response
    {
        if ($this->isCsrfTokenValid('delete' . $evenement->getId(), $request->request->get('_token'))) {
            $entityManager->remove($evenement);
            $entityManager->flush();
            $this->addFlash('success', 'L\'événement a été supprimé.');
        }

        return $this->redirectToRoute('app_evenement_index', [], Response::HTTP_SEE_OTHER);
    }

    /**
     * Gère l'inscription, la désinscription et le changement de statut d'un utilisateur à un événement.
     */
    #[Route('/evenement/{id}/inscription', name: 'app_evenement_inscription', methods: ['POST'])]
    #[IsGranted('ROLE_USER')]
    public function inscription(
        Request $request,
        Evenement $evenement,
        EntityManagerInterface $entityManager,
        InscriptionRepository $inscriptionRepository,
        SpecializationRepository $specRepository
    ): Response {
        /** @var \App\Entity\User $user */
        $user = $this->getUser();
        $statut = $request->request->get('statut');
        $inscription = $inscriptionRepository->findOneBy(['user' => $user, 'evenement' => $evenement]);

        if ($statut === 'Absent') {
            if ($inscription) {
                $entityManager->remove($inscription);
                $this->addFlash('success', 'Votre désinscription a été prise en compte.');
            }
        } else {
            if (!$inscription) {
                $inscription = new Inscription();
                $inscription->setUser($user);
                $inscription->setEvenement($evenement);
            }

            if ($statut === 'Incertain') {
                $inscription->setStatut('Incertain');
                $inscription->setPlayedRole(null);
                $inscription->setSpecialization(null);
                $this->addFlash('info', 'Votre statut a été mis à jour en "Incertain".');
            }

            if ($statut === 'Confirmé') {
                $role = $request->request->get('role');
                $specializationId = $request->request->get('specialization');

                if (!in_array($role, Inscription::ROLES)) {
                    $this->addFlash('error', 'Le rôle sélectionné est invalide.');
                    return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
                }

                $specEntity = $specRepository->find($specializationId);
                if (!$specEntity) {
                    $this->addFlash('error', 'La spécialisation sélectionnée est invalide.');
                    return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
                }

                $confirmedCount = $inscriptionRepository->countConfirmedByRole($evenement, $role, $user);
                $requiredPlaces = 0;
                switch ($role) {
                    case 'Tank':
                        $requiredPlaces = $evenement->getTanksRequis();
                        break;
                    case 'Soigneur':
                        $requiredPlaces = $evenement->getSoigneursRequis();
                        break;
                    case 'DPS':
                        $requiredPlaces = $evenement->getDpsRequis();
                        break;
                }

                if ($confirmedCount < $requiredPlaces) {
                    $inscription->setStatut('Confirmé');
                    $this->addFlash('success', 'Votre inscription en tant que ' . $role . ' a bien été enregistrée !');
                } else {
                    $inscription->setStatut('En attente');
                    $this->addFlash('info', 'Le rôle ' . $role . ' est complet. Vous avez été placé(e) sur le banc de touche.');
                }
                $inscription->setPlayedRole($role);
                $inscription->setSpecialization($specEntity);
            }
            $entityManager->persist($inscription);
        }

        $entityManager->flush();
        return $this->redirectToRoute('app_evenement_show', ['id' => $evenement->getId()]);
    }

    /**
     * Permet à un admin de changer le statut d'une inscription (Confirmer, Mettre en attente).
     */
    #[Route('/inscription/{id}/update-status', name: 'app_inscription_update_status', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function updateInscriptionStatus(Request $request, Inscription $inscription, EntityManagerInterface $entityManager): Response
    {
        $newStatus = $request->request->get('status');
        $evenementId = $inscription->getEvenement()->getId();

        if (in_array($newStatus, Inscription::STATUTS)) {
            $inscription->setStatut($newStatus);
            $entityManager->flush();
            $this->addFlash('success', 'Le statut de ' . $inscription->getUser()->getPseudo() . ' a été mis à jour.');
        } else {
            $this->addFlash('error', 'Statut invalide.');
        }

        return $this->redirectToRoute('app_evenement_show', ['id' => $evenementId]);
    }

    /**
     * Permet à un admin de supprimer l'inscription d'un joueur.
     */
    #[Route('/inscription/{id}/remove', name: 'app_inscription_remove', methods: ['POST'])]
    #[IsGranted('ROLE_ADMIN')]
    public function removeInscription(Inscription $inscription, EntityManagerInterface $entityManager): Response
    {
        $evenementId = $inscription->getEvenement()->getId();
        $pseudo = $inscription->getUser()->getPseudo();

        $entityManager->remove($inscription);
        $entityManager->flush();

        $this->addFlash('success', 'L\'inscription de ' . $pseudo . ' a bien été supprimée.');

        return $this->redirectToRoute('app_evenement_show', ['id' => $evenementId]);
    }
}
