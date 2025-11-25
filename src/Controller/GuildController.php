<?php

// src/Controller/GuildController.php

namespace App\Controller;

use App\Repository\UserRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;
use Symfony\Component\Security\Http\Attribute\IsGranted;

#[IsGranted('ROLE_USER')]
class GuildController extends AbstractController
{
    #[Route('/roster', name: 'app_guild_roster')]
    public function roster(UserRepository $userRepository): Response
    {
        $members = $userRepository->findBy([], ['pseudo' => 'ASC']);

        return $this->render('guild/roster.html.twig', [
            'members' => $members,
        ]);
    }
}
