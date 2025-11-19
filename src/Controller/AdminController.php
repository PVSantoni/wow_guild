<?php

namespace App\Controller;

use Symfony\Component\Security\Http\Attribute\IsGranted;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;
use App\Repository\EvenementRepository;
use App\Repository\UserRepository;
use Doctrine\ORM\EntityManagerInterface;
use App\Entity\User;
use App\Service\BattleNetApiService;


#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(UserRepository $userRepository, EvenementRepository $evenementRepository): Response
    {
        // Récupérer le nombre total d'utilisateurs
        $totalUsers = $userRepository->count([]);

        // Récupérer le nombre d'événements dont la date est dans le futur
        $upcomingEvents = $evenementRepository->createQueryBuilder('e')
            ->select('count(e.id)')
            ->where('e.dateDebut > :now')
            ->setParameter('now', new \DateTime())
            ->getQuery()
            ->getSingleScalarResult();

        return $this->render('admin/index.html.twig', [
            'totalUsers' => $totalUsers,
            'upcomingEvents' => $upcomingEvents,
        ]);
    }

    #[Route('/admin/users', name: 'app_admin_users')]
    public function listUsers(UserRepository $userRepository): Response
    {
        // On récupère tous les utilisateurs
        $users = $userRepository->findAll();

        return $this->render('admin/users.html.twig', [
            'users' => $users,
        ]);
    }

    #[Route('/admin/users/{id}/promote', name: 'app_admin_user_promote')]
    public function promoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        // On ajoute le rôle ADMIN
        $user->setRoles(['ROLE_ADMIN']);
        $entityManager->flush();

        // On ajoute un message flash pour confirmer le succès
        $this->addFlash('success', "L'utilisateur " . $user->getPseudo() . " a bien été promu administrateur.");

        return $this->redirectToRoute('app_admin_users');
    }

    #[Route('/admin/users/{id}/demote', name: 'app_admin_user_demote')]
    public function demoteUser(User $user, EntityManagerInterface $entityManager): Response
    {
        // Sécurité : on ne peut pas se rétrograder soi-même
        if ($this->getUser() === $user) {
            $this->addFlash('error', 'Vous не pouvez pas vous rétrograder vous-même.');
            return $this->redirectToRoute('app_admin_users');
        }

        // On remet le rôle USER (par défaut)
        $user->setRoles(['ROLE_USER']);
        $entityManager->flush();

        $this->addFlash('success', "L'utilisateur " . $user->getPseudo() . " a bien été rétrogradé.");

        return $this->redirectToRoute('app_admin_users');
    }
}


