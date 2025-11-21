<?php

namespace App\Service;

use Exception;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Contracts\Cache\CacheInterface;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\HttpClient\HttpClientInterface;

class BattleNetApiService
{
    private const TOKEN_URL = 'https://eu.battle.net/oauth/token';
    private const API_BASE_URL = 'https://%s.api.blizzard.com';

    private HttpClientInterface $httpClient;
    private CacheInterface $cache;
    private string $clientId;
    private string $clientSecret;
    private string $region;

    public function __construct(
        HttpClientInterface $httpClient,
        CacheInterface $cache,
        string $clientId,
        string $clientSecret,
        string $region = 'eu'
    ) {
        $this->httpClient = $httpClient;
        $this->cache = $cache;
        $this->clientId = $clientId;
        $this->clientSecret = $clientSecret;
        $this->region = $region;
    }

    private function getAccessToken(): ?string
    {
        // Clé de cache unique par région
        return $this->cache->get('blizzard_access_token_' . $this->region, function (ItemInterface $item) {
            $item->expiresAfter(3600 * 23);
            try {
                $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                    'auth_basic' => [$this->clientId, $this->clientSecret],
                    'body' => ['grant_type' => 'client_credentials'],
                ]);
                if ($response->getStatusCode() !== Response::HTTP_OK) {
                    return null;
                }
                return $response->toArray()['access_token'];
            } catch (Exception $e) {
                return null;
            }
        });
    }

    public function getCharacterProfile(string $characterName, string $realmSlug, string $region = null): ?array
    {
        $targetRegion = $region ?? $this->region;
        $token = $this->getAccessToken();
        if (!$token) return null;

        $apiBaseUrl = sprintf(self::API_BASE_URL, $targetRegion);
        // Note : Ici je laisse profile-classic car tu sembles jouer sur MoP/Classic.
        // Si tu joues sur Retail, remplace par : $namespace = 'profile-' . $targetRegion;
        $namespace = 'profile-classic-' . $targetRegion;

        $characterUrl = "{$apiBaseUrl}/profile/wow/character/{$realmSlug}/" . strtolower($characterName);
        try {
            $response = $this->httpClient->request('GET', $characterUrl, [
                'auth_bearer' => $token,
                'query' => ['namespace' => $namespace, 'locale' => 'fr_FR'],
            ]);
            return $response->getStatusCode() === 200 ? $response->toArray() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getCharacterMedia(string $mediaUrl): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;
        try {
            $response = $this->httpClient->request('GET', $mediaUrl, ['auth_bearer' => $token]);
            return $response->getStatusCode() === 200 ? $response->toArray() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getCharacterEquipment(string $equipmentUrl): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        // Ici aussi, je garde profile-classic pour ton personnage MoP
        $namespace = 'profile-classic-' . $this->region;

        try {
            $response = $this->httpClient->request('GET', $equipmentUrl, [
                'auth_bearer' => $token,
                'query' => ['namespace' => $namespace, 'locale' => 'fr_FR'],
            ]);
            return $response->getStatusCode() === 200 ? $response->toArray() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getItemMediaUrl(int $mediaId): ?string
    {
        // Changement de clé de cache pour éviter les conflits avec l'ancien code
        $cacheKey = 'item_media_static_eu_' . $mediaId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($mediaId) {
            $item->expiresAfter(3600 * 24 * 30);
            $token = $this->getAccessToken();
            if (!$token) return null;

            $apiBaseUrl = sprintf(self::API_BASE_URL, $this->region);

            // MODIFICATION DEMANDÉE : Utilisation de static-eu (Retail)
            $namespace = 'static-' . $this->region;

            $mediaUrl = "{$apiBaseUrl}/data/wow/media/item/{$mediaId}";
            try {
                $response = $this->httpClient->request('GET', $mediaUrl, [
                    'auth_bearer' => $token,
                    'query' => ['namespace' => $namespace, 'locale' => 'fr_FR'],
                ]);
                if ($response->getStatusCode() !== 200) return null;
                return $response->toArray()['assets'][0]['value'] ?? null;
            } catch (Exception $e) {
                return null;
            }
        });
    }

    public function getItemInfo(int $itemId): ?array
    {
        // On met 'v3' pour être sûr de forcer l'invalidation du cache
        $cacheKey = 'item_info_v3_static_eu_' . $itemId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($itemId) {
            $item->expiresAfter(3600 * 24 * 30);

            $token = $this->getAccessToken();
            if (!$token) return null;

            $apiBaseUrl = sprintf(self::API_BASE_URL, $this->region);
            $namespace = 'static-' . $this->region;
            $itemApiUrl = "{$apiBaseUrl}/data/wow/item/{$itemId}";

            try {
                $response = $this->httpClient->request('GET', $itemApiUrl, [
                    'auth_bearer' => $token,
                    'query' => ['namespace' => $namespace, 'locale' => 'fr_FR'],
                ]);

                if ($response->getStatusCode() !== 200) {
                    return null;
                }

                $data = $response->toArray();
                $iconUrl = $this->getItemIconUrl($itemId, $namespace, $token);

                // =======================================================
                // BLOC DE RETOUR CORRIGÉ GRÂCE À VOTRE JSON
                // =======================================================
                return [
                    'id'        => $data['id'],
                    'name'      => $data['name'] ?? 'Nom inconnu',
                    // On utilise le chemin complet et plus fiable
                    'ilvl'      => $data['preview_item']['level']['value'] ?? $data['level'] ?? 0,
                    'quality'   => $data['quality']['name'] ?? 'Commun',
                    'icon_url'  => $iconUrl,
                    // On utilise le chemin complet et correct pour la difficulté
                    'difficulty' => $data['preview_item']['name_description']['display_string'] ?? null,
                ];
                // =======================================================

            } catch (Exception $e) {
                return null;
            }
        });
    }

    // Fonction Helper privée
    private function getItemIconUrl(int $itemId, string $namespace, string $token): ?string
    {
        $apiBaseUrl = sprintf(self::API_BASE_URL, $this->region);
        $mediaUrl = "{$apiBaseUrl}/data/wow/media/item/{$itemId}";

        try {
            $response = $this->httpClient->request('GET', $mediaUrl, [
                'auth_bearer' => $token,
                'query' => ['namespace' => $namespace, 'locale' => 'fr_FR'],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                foreach ($data['assets'] as $asset) {
                    if ($asset['key'] === 'icon') {
                        return $asset['value'];
                    }
                }
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }
}
