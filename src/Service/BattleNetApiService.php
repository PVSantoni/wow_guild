<?php

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\HttpClient\HttpClientInterface;

/**
 * Service pour communiquer avec l'API Battle.net de Blizzard.
 * Gère l'authentification et la récupération des données de personnages WoW.
 */
class BattleNetApiService
{
    // On définit les URLs de l'API comme des constantes pour la propreté du code.
    private const TOKEN_URL = 'https://oauth.battle.net/token';
    private const API_BASE_URL = 'https://%s.api.blizzard.com'; // Le %s sera remplacé par la région (eu, us, etc.)

    private HttpClientInterface $httpClient;
    private string $clientId;
    private string $clientSecret;
    private ?string $accessToken = null;

    public function __construct(HttpClientInterface $httpClient, string $clientId, string $clientSecret)
    {
        $this->httpClient = $httpClient;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
    }

    /**
     * Récupère les informations de base d'un personnage WoW Classic.
     *
     * @param string $characterName Le nom du personnage (ex: "Grommash")
     * @param string $realmSlug     Le "slug" du serveur (ex: "pyrewood-village")
     * @param string $region        La région (ex: "eu")
     *
     * @return array|null Les données du personnage ou null en cas d'erreur.
     */
    public function getCharacterProfile(string $characterName, string $realmSlug, string $region = 'eu'): ?array
    {
        try {
            $token = $this->getAccessToken();

            // L'URL de base de l'API est construite dynamiquement avec la région.
            $apiBaseUrl = sprintf(self::API_BASE_URL, $region);

            // Le namespace est spécifique à WoW Classic et utilise aussi la région.
            $namespace = 'profile-classic-' . $region;

            // L'URL finale pour le personnage.
            $characterUrl = "{$apiBaseUrl}/profile/wow/character/{$realmSlug}/{$characterName}";

            $response = $this->httpClient->request('GET', $characterUrl, [
                'auth_bearer' => $token,
                'query' => [
                    'namespace' => $namespace,
                    'locale'    => 'fr_FR',
                ],
            ]);

            if ($response->getStatusCode() !== Response::HTTP_OK) {
                return null; // Le personnage n'existe pas, le serveur est incorrect, etc.
            }

            return $response->toArray();
        } catch (Exception $e) {
            // En cas d'erreur plus grave (jeton invalide, API inaccessible...), on retourne null.
            // Pour un débogage, on pourrait logger l'erreur ici.
            return null;
        }
    }

    /**
     * S'authentifie auprès de l'API Blizzard pour obtenir un jeton d'accès.
     * Le jeton est mis en cache dans la propriété $accessToken pour la durée de la requête.
     *
     * @return string Le jeton d'accès.
     * @throws Exception Si l'authentification échoue.
     */
    private function getAccessToken(): string
    {
        if ($this->accessToken) {
            return $this->accessToken;
        }

        $response = $this->httpClient->request('POST', self::TOKEN_URL, [
            'auth_basic' => [$this->clientId, $this->clientSecret],
            'body' => [
                'grant_type' => 'client_credentials',
            ],
        ]);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            throw new Exception('Impossible de récupérer le jeton d\'accès de l\'API Battle.net.');
        }

        $data = $response->toArray();
        $this->accessToken = $data['access_token'];

        return $this->accessToken;
    }

    public function getCharacterMedia(string $mediaUrl): ?array
{
    try {
        $token = $this->getAccessToken();

        $response = $this->httpClient->request('GET', $mediaUrl, [
            'auth_bearer' => $token,
        ]);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return null;
        }

        return $response->toArray();

    } catch (Exception $e) {
        return null;
    }
}

public function getCharacterEquipment(string $equipmentUrl): ?array
{
    try {
        $token = $this->getAccessToken();

        $response = $this->httpClient->request('GET', $equipmentUrl, [
            'auth_bearer' => $token,
            'query' => [
                // Il faut aussi spécifier le namespace pour les sous-appels
                'namespace' => 'profile-classic-eu',
                'locale'    => 'fr_FR',
            ],
        ]);

        if ($response->getStatusCode() !== Response::HTTP_OK) {
            return null;
        }

        return $response->toArray();

    } catch (Exception $e) {
        return null;
    }
}
}
