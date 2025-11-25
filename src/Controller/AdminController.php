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

#[IsGranted('ROLE_ADMIN')]
final class AdminController extends AbstractController
{
    #[Route('/admin', name: 'app_admin_dashboard')]
    public function index(UserRepository $userRepository, EvenementRepository $evenementRepository): Response
    {
        // =============================================================
        // LA LOGIQUE DE CETTE MÉTHODE EST RESTAURÉE CORRECTEMENT ICI
        // =============================================================
        $totalUsers = $userRepository->count([]);

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
}
