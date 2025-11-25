<?php

namespace App\Controller;

use App\Entity\User;
use App\Repository\EvenementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;
use App\Entity\Evenement;

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(UserRepository $userRepository, EvenementRepository $evenementRepository): Response
    {
        $totalUsers = $userRepository->count([]);

        // --> MODIFICATION 1 : On renomme la variable pour plus de clarté
        // On récupère les 5 derniers événements créés/à venir
        $recentEvents = $evenementRepository->createQueryBuilder('e')
            // --> MODIFICATION 2 : On supprime le filtre de date '.where()'
            ->orderBy('e.dateDebut', 'DESC') // <-- On trie par date décroissante (le plus récent en premier)
            ->setMaxResults(5)
            ->getQuery()
            ->getResult();

        // On garde le calcul spécifique pour la carte "Événements à Venir"
        $upcomingEventsCount = $evenementRepository->createQueryBuilder('e')
            ->select('count(e.id)')
            ->where('e.dateDebut >= :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/index.html.twig', [
            'totalUsers' => $totalUsers,
            'recentEvents' => $recentEvents, // <-- On envoie la nouvelle variable
            'upcomingEventsCount' => $upcomingEventsCount, // <-- On envoie le compte correct
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function listUsers(UserRepository $userRepository): Response
    {
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/promote', name: 'app_admin_user_promote')]
    public function promoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        $this->addFlash('success', "L'utilisateur " . $user->getPseudo() . " a bien été promu administrateur.");

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/users/{id}/demote', name: 'app_admin_user_demote')]
    public function demoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        if ($this->getUser() === $user) {
            $this->addFlash('error', 'Vous ne pouvez pas vous rétrograder vous-même.');
            return $this->redirectToRoute('app_admin_users');
        }

        $user->setRoles(['ROLE_USER']);
        $entityManager->flush();

        $this->addFlash('success', "L'utilisateur " . $user->getPseudo() . " a bien été rétrogradé.");

        return $this->redirectToRoute('app_admin_users');
    }

    // =========================================================
    // NOTRE NOUVELLE MÉTHODE EST BIEN PRÉSENTE À LA FIN
    // =========================================================
    #[Route('/admin/users/{id}/update-rank', name: 'app_admin_user_update_rank', methods: ['POST'])]
    public function updateGuildRank(Request $request, User $user, EntityManagerInterface $entityManager): Response
    {
        $newRank = $request->request->get('guildRank');

        if ($newRank && in_array($newRank, User::RANKS)) {
            $user->setGuildRank($newRank);
            $entityManager->flush();
            $this->addFlash('success', 'Le rang de ' . $user->getPseudo() . ' a été mis à jour.');
        } else {
            $this->addFlash('error', 'Le rang sélectionné est invalide.');
        }

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/evenement/{id}/roster', name: 'app_admin_evenement_roster')]
    public function rosterManagement(Evenement $evenement): Response
    {
        // On récupère les inscriptions et on les trie par statut
        $inscriptions = $evenement->getInscriptions()->toArray();
        usort($inscriptions, function ($a, $b) {
            $order = ['Confirmé' => 1, 'En attente' => 2, 'Incertain' => 3];
            return $order[$a->getStatut()] <=> $order[$b->getStatut()];
        });

        return $this->render('admin/roster_management.html.twig', [
            'evenement' => $evenement,
            'inscriptions' => $inscriptions,
        ]);
    }

    #[Route('/admin/evenements', name: 'app_admin_evenement_list')]
    #[Route('/admin/evenements', name: 'app_admin_evenement_list')]
    public function listEvenements(EvenementRepository $evenementRepository): Response
    {
        // On récupère TOUS les événements, triés du plus récent au plus ancien
        $evenements = $evenementRepository->createQueryBuilder('e')
            // --> On supprime le filtre de date '.where()'
            ->orderBy('e.dateDebut', 'DESC') // <-- On trie par date décroissante
            ->getQuery()
            ->getResult();

        return $this->render('admin/evenement_list.html.twig', [
            'evenements' => $evenements,
        ]);
    }
}
