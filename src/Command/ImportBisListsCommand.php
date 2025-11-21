<?php

namespace App\Command;

use App\Entity\BisList;
use App\Entity\BisItem;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Contracts\HttpClient\HttpClientInterface;
use Exception; // N'oublie pas d'importer Exception

#[AsCommand(
    name: 'app:import-bis-lists',
    description: 'Télécharge les listes BiS depuis le dépôt Wowsims et les importe en base de données.',
)]
class ImportBisListsCommand extends Command
{
    private EntityManagerInterface $entityManager;
    private HttpClientInterface $httpClient;

    // Le "mapper" qui va traduire les numéros de slot du JSON en noms standards
    // Ces noms (HEAD, NECK, etc.) correspondent à ceux utilisés par l'API de Blizzard.
    private const SLOT_MAP = [
        1 => 'HEAD',
        2 => 'NECK',
        3 => 'SHOULDER',
        5 => 'CHEST',
        16 => 'CLOAK',
        4 => 'SHIRT',
        19 => 'TABARD',
        6 => 'WAIST',
        7 => 'LEGS',
        8 => 'FEET',
        9 => 'WRIST',
        10 => 'HANDS',
        11 => 'FINGER', // Pour Finger 1
        12 => 'FINGER', // Pour Finger 2
        13 => 'TRINKET', // Pour Trinket 1
        14 => 'TRINKET', // Pour Trinket 2
        17 => 'MAIN_HAND',
        21 => 'MAIN_HAND', // Pour One-Hand
        22 => 'OFF_HAND',
        23 => 'OFF_HAND', // Pour Held In Off-hand
        15 => 'RANGED', // Pour Ranged/Relic
        28 => 'RANGED', // Pour Relic
    ];

    public function __construct(EntityManagerInterface $entityManager, HttpClientInterface $httpClient)
    {
        parent::__construct();
        $this->entityManager = $entityManager;
        $this->httpClient = $httpClient;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $io->title('Début de l\'importation des listes BiS (Phase 1 WotLK)');

        $dataSourceUrl = 'https://raw.githubusercontent.com/wowsims/wotlk/main/ui/core/data/gear_sets/p1.json';

        try {
            $io->text('Téléchargement des données depuis GitHub...');
            $response = $this->httpClient->request('GET', $dataSourceUrl);
            if ($response->getStatusCode() !== 200) {
                $io->error('Impossible de télécharger le fichier de données. Statut : ' . $response->getStatusCode());
                return Command::FAILURE;
            }
            $data = $response->toArray();
            $io->text('Données téléchargées avec succès.');
        } catch (Exception $e) {
            $io->error('Erreur lors du téléchargement ou de l\'analyse du JSON : ' . $e->getMessage());
            return Command::FAILURE;
        }

        $io->note('Suppression des anciennes listes pour éviter les doublons...');
        // On vide les tables pour s'assurer de repartir sur une base propre
        $this->entityManager->createQuery('DELETE FROM App\Entity\BisItem')->execute();
        $this->entityManager->createQuery('DELETE FROM App\Entity\BisList')->execute();

        $io->progressStart(count($data));

        foreach ($data as $listData) {
            // On s'assure que la liste a bien un nom et des items
            if (!isset($listData['name']) || !isset($listData['items'])) {
                continue;
            }

            // Ex: $listData['name'] = "P1 Frost Mage"
            // On veut extraire "MAGE" et "Frost"
            preg_match('/P\d+\s(.+)\s(.+)/', $listData['name'], $matches);
            $spec = $matches[1] ?? 'Unknown';
            $class = $matches[2] ?? 'Unknown';

            $bisList = new BisList();
            $bisList->setName($listData['name']);
            // On met en majuscules pour correspondre au format de l'API Blizzard
            $bisList->setCharacterClass(strtoupper($class));
            $bisList->setSpecialization($spec);
            $this->entityManager->persist($bisList);

            foreach ($listData['items'] as $itemData) {
                // On s'assure que l'item a un ID et un slot
                if (!isset($itemData['id']) || !isset($itemData['slot'])) {
                    continue;
                }

                $slotId = $itemData['slot'];
                // On utilise notre mapper pour traduire le numéro du slot
                $slotName = self::SLOT_MAP[$slotId] ?? null;

                // Si le slot n'est pas dans notre mapper, on l'ignore (ex: enchantements)
                if ($slotName === null) {
                    continue;
                }

                $bisItem = new BisItem();
                $bisItem->setItemId($itemData['id']);
                // Le nom n'est pas dans le JSON, on le laisse null pour l'instant
                // On pourrait l'ajouter plus tard avec l'API
                $bisItem->setItemName('Item ' . $itemData['id']);
                $bisItem->setSlot($slotName);

                // On lie l'item à la liste qu'on vient de créer
                $bisItem->setBisList($bisList);
                $this->entityManager->persist($bisItem);
            }
            $io->progressAdvance();
        }

        $io->text('Sauvegarde des données en base...');
        $this->entityManager->flush();
        $io->progressFinish();

        $io->success('Importation des listes BiS terminée avec succès !');

        return Command::SUCCESS;
    }
}
