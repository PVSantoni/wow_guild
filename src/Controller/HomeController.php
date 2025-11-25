<?php

namespace App\Controller;

use App\Repository\EvenementRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request; // <--- N'oublie pas cette ligne
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Annotation\Route;

class HomeController extends AbstractController
{
    #[Route('/', name: 'app_home')]
    public function index(EvenementRepository $evenementRepository, Request $request): Response
    {
        // 1. Récupérer le décalage de semaine (0 = cette semaine, 1 = semaine prochaine, -1 = semaine dernière)
        $weekOffset = $request->query->getInt('week', 0);

        // 2. Créer une date de référence basée sur aujourd'hui + le décalage
        $anchorDate = new \DateTime();
        if ($weekOffset !== 0) {
            // Exemple : si offset est 1, ça fait "+1 weeks", si -1 ça fait "-1 weeks"
            $anchorDate->modify(sprintf('%+d weeks', $weekOffset));
        }

        // 3. Trouver le Lundi et le Dimanche de cette semaine calculée
        // On clone pour ne pas modifier l'original par erreur
        $startOfWeek = (clone $anchorDate)->modify('monday this week')->setTime(0, 0, 0);
        $endOfWeek = (clone $startOfWeek)->modify('sunday this week')->setTime(23, 59, 59);

        // 4. Récupérer les événements (Ta logique existante)
        $eventsOfWeek = $evenementRepository->createQueryBuilder('e')
            ->where('e.dateDebut BETWEEN :start AND :end')
            ->setParameter('start', $startOfWeek)
            ->setParameter('end', $endOfWeek)
            ->orderBy('e.dateDebut', 'ASC')
            ->getQuery()
            ->getResult();

        // 5. Organiser les événements par jour (Ta logique existante)
        $calendarEvents = [];
        foreach ($eventsOfWeek as $event) {
            $day = $event->getDateDebut()->format('Y-m-d');
            if (!isset($calendarEvents[$day])) {
                $calendarEvents[$day] = [];
            }
            $calendarEvents[$day][] = $event;
        }

        // 6. Créer le tableau des jours de la semaine (Ta logique, mais basée sur le nouveau $startOfWeek)
        $weekDays = [];
        $currentDay = clone $startOfWeek;
        for ($i = 0; $i < 7; $i++) {
            $weekDays[] = clone $currentDay;
            $currentDay->modify('+1 day');
        }

        return $this->render('home/index.html.twig', [
            'weekDays' => $weekDays,
            'calendarEvents' => $calendarEvents,
        ]);
    }
}
