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
        return $this->cache->get('blizzard_access_token_' . $this->region, function (ItemInterface $item) {
            $item->expiresAfter(3600 * 23);
            try {
                $response = $this->httpClient->request('POST', self::TOKEN_URL, [
                    'auth_basic' => [$this->clientId, $this->clientSecret],
                    'body' => ['grant_type' => 'client_credentials'],
                ]);
                return $response->getStatusCode() === Response::HTTP_OK ? $response->toArray()['access_token'] : null;
            } catch (Exception $e) {
                return null;
            }
        });
    }

    // =========================================================================
    // PARTIE 1 : MÉTHODES POUR L'IMPORTATION (CharacterController)
    // =========================================================================

    public function getCharacterProfileSummary(string $realmSlug, string $characterName, string $region = 'eu'): ?array
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $apiBaseUrl = sprintf(self::API_BASE_URL, $region);
        // Si MoP Classic : 'profile-classic-'. Si Retail : 'profile-'
        $namespace = 'profile-classic-' . $region;

        $url = "{$apiBaseUrl}/profile/wow/character/{$realmSlug}/" . strtolower($characterName);

        try {
            $response = $this->httpClient->request('GET', $url, [
                'auth_bearer' => $token,
                'query' => ['namespace' => $namespace, 'locale' => 'fr_FR'],
            ]);
            return $response->getStatusCode() === 200 ? $response->toArray() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    public function getCharacterAvatar(string $realmSlug, string $characterName, string $region = 'eu'): ?string
    {
        $token = $this->getAccessToken();
        if (!$token) return null;

        $apiBaseUrl = sprintf(self::API_BASE_URL, $region);
        $namespace = 'profile-classic-' . $region;

        $url = "{$apiBaseUrl}/profile/wow/character/{$realmSlug}/" . strtolower($characterName) . "/character-media";

        try {
            $response = $this->httpClient->request('GET', $url, [
                'auth_bearer' => $token,
                'query' => ['namespace' => $namespace, 'locale' => 'fr_FR'],
            ]);

            if ($response->getStatusCode() === 200) {
                $data = $response->toArray();
                foreach ($data['assets'] as $asset) {
                    if ($asset['key'] === 'avatar') {
                        return $asset['value'];
                    }
                }
            }
            return null;
        } catch (Exception $e) {
            return null;
        }
    }

    // =========================================================================
    // PARTIE 2 : MÉTHODES POUR L'ARMURERIE (ProfileController)
    // =========================================================================

    /**
     * Récupère les médias via l'URL brute fournie par l'API (nécessaire pour ProfileController)
     */
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

        $query = ['locale' => 'fr_FR'];
        if (!str_contains($equipmentUrl, 'namespace=')) {
            $query['namespace'] = 'profile-classic-' . $this->region;
        }

        try {
            $response = $this->httpClient->request('GET', $equipmentUrl, [
                'auth_bearer' => $token,
                'query' => $query,
            ]);
            return $response->getStatusCode() === 200 ? $response->toArray() : null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Récupère l'URL d'une icône d'item via son Media ID (nécessaire pour ProfileController)
     */
    public function getItemMediaUrl(int $mediaId): ?string
    {
        $cacheKey = 'item_media_static_eu_' . $mediaId;

        return $this->cache->get($cacheKey, function (ItemInterface $item) use ($mediaId) {
            $item->expiresAfter(3600 * 24 * 30);
            $token = $this->getAccessToken();
            if (!$token) return null;

            $apiBaseUrl = sprintf(self::API_BASE_URL, $this->region);
            $namespace = 'static-' . $this->region; // Static est souvent commun

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

                if ($response->getStatusCode() !== 200) return null;

                $data = $response->toArray();
                $iconUrl = $this->getItemIconUrl($itemId, $namespace, $token);

                return [
                    'id'        => $data['id'],
                    'name'      => $data['name'] ?? 'Nom inconnu',
                    'ilvl'      => $data['preview_item']['level']['value'] ?? $data['level'] ?? 0,
                    'quality'   => $data['quality']['name'] ?? 'Commun',
                    'icon_url'  => $iconUrl,
                    'difficulty' => $data['preview_item']['name_description']['display_string'] ?? null,
                ];
            } catch (Exception $e) {
                return null;
            }
        });
    }

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
                return $data['assets'][0]['value'] ?? null;
            }
        } catch (Exception $e) {
            return null;
        }
        return null;
    }
}
