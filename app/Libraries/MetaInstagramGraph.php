<?php

namespace App\Libraries;

/**
 * Resolución de @username de la cuenta profesional de Instagram (receptor del webhook).
 * Usa Graph API y/o mapa opcional en META_IG_RECIPIENT_USERNAMES_JSON.
 */
class MetaInstagramGraph
{
    /** @var array<string, ?string> */
    private static array $linkedFacebookPageIdCache = [];

    /**
     * Devuelve username sin @ (o null si no se puede resolver).
     */
    public static function resolveRecipientUsername(string $recipientIgId): ?string
    {
        if ($recipientIgId === '') {
            return null;
        }

        $mapped = self::fromEnvMap($recipientIgId);
        if ($mapped !== null && $mapped !== '') {
            return ltrim($mapped, '@');
        }

        $token = getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN');
        if (empty($token)) {
            return null;
        }

        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';

        try {
            $client = \Config\Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
            $response = $client->get(
                'https://graph.facebook.com/' . $version . '/' . rawurlencode($recipientIgId),
                [
                    'query' => [
                        'fields'       => 'username,name',
                        'access_token' => $token,
                    ],
                ]
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($response->getStatusCode() !== 200 || empty($body['username'])) {
                return null;
            }

            return (string) $body['username'];
        } catch (\Throwable $e) {
            log_message('error', 'MetaInstagramGraph::resolveRecipientUsername ' . $e->getMessage());

            return null;
        }
    }

    /**
     * Mapa IG Business Account ID → username (env + cuentas vistas en BD).
     *
     * @return array<string, string>
     */
    public static function listRecipientAccounts(): array
    {
        $map = [];

        $json = getenv('META_IG_RECIPIENT_USERNAMES_JSON');
        if ($json !== false && $json !== '') {
            $decoded = json_decode($json, true);
            if (is_array($decoded)) {
                foreach ($decoded as $id => $username) {
                    $id = (string) $id;
                    if ($id !== '') {
                        $map[$id] = ltrim((string) $username, '@');
                    }
                }
            }
        }

        try {
            $db = \Config\Database::connect();
            $rows = $db->query("
                SELECT recipient_ig_id, recipient_ig_username
                FROM conversations
                WHERE channel = 'instagram' AND recipient_ig_id != ''
                GROUP BY recipient_ig_id, recipient_ig_username
                ORDER BY recipient_ig_username
            ")->getResultArray();

            foreach ($rows as $row) {
                $id = (string) ($row['recipient_ig_id'] ?? '');
                if ($id === '' || isset($map[$id])) {
                    continue;
                }
                $username = ltrim((string) ($row['recipient_ig_username'] ?? ''), '@');
                $map[$id] = $username !== '' ? $username : $id;
            }
        } catch (\Throwable $e) {
            log_message('warning', 'MetaInstagramGraph::listRecipientAccounts ' . $e->getMessage());
        }

        asort($map);

        return $map;
    }

    /**
     * JSON en .env: {"17841400xxx":"mi_cuenta","17841400yyy":"otra_cuenta"}
     */
    private static function fromEnvMap(string $recipientIgId): ?string
    {
        $json = getenv('META_IG_RECIPIENT_USERNAMES_JSON');
        if ($json === false || $json === '') {
            return null;
        }
        $map = json_decode($json, true);
        if (! is_array($map) || ! isset($map[$recipientIgId])) {
            return null;
        }

        return (string) $map[$recipientIgId];
    }

    /**
     * Perfil del participante en DM (usuario de Instagram que escribe).
     * Requiere PAGE ACCESS TOKEN vinculado al Instagram Business Account receptor.
     *
     * @return array{name: string, username: string, is_private: bool, profile_pic_url: ?string, followers_count: int}|null
     */
    public static function resolveParticipantProfile(string $participantIgScopedId, string $recipientIgId = ''): ?array
    {
        if ($participantIgScopedId === '') {
            return null;
        }

        $pageToken = self::pageAccessTokenForInstagramBusiness($recipientIgId);
        if ($pageToken === null || $pageToken === '') {
            log_message('error', 'MetaInstagramGraph::resolveParticipantProfile no page token for recipient_ig=' . $recipientIgId);

            return null;
        }

        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';

        $endpointUrl = 'https://graph.facebook.com/' . $version . '/' . rawurlencode($participantIgScopedId);

        try {
            $client = \Config\Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
            $response = $client->get(
                $endpointUrl,
                [
                    'query' => [
                        'fields'       => 'name,username',
                        'access_token' => $pageToken,
                    ],
                ]
            );
            $statusCode = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);

            if ($statusCode !== 200 || ! is_array($body) || isset($body['error'])) {
                log_message('error', 'MetaInstagramGraph::resolveParticipantProfile status=' . $statusCode
                    . ' url=' . $endpointUrl . ' id=' . $participantIgScopedId
                    . ' body=' . (string) $response->getBody());

                return null;
            }

            return [
                'name'            => isset($body['name']) ? (string) $body['name'] : '',
                'username'        => isset($body['username']) ? (string) $body['username'] : '',
                'is_private'      => false,
                'profile_pic_url' => null,
                'followers_count' => 0,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'MetaInstagramGraph::resolveParticipantProfile exception=' . $e::class
                . ' message=' . $e->getMessage() . ' url=' . $endpointUrl
                . ' id=' . $participantIgScopedId);

            return null;
        }
    }

    /**
     * Obtiene Page Access Token para el Facebook Page vinculado al Instagram Business Account.
     */
    public static function pageAccessTokenForInstagramBusiness(string $instagramBusinessAccountId): ?string
    {
        if ($instagramBusinessAccountId === '') {
            return null;
        }

        $pageId = self::linkedFacebookPageIdForInstagramBusiness($instagramBusinessAccountId);
        if ($pageId === null || $pageId === '') {
            return null;
        }

        return self::pageAccessTokenById($pageId);
    }

    /** @var array<string, ?string> */
    private static array $pageTokenCache = [];

    /**
     * Obtiene el access_token de un Facebook Page por su ID.
     */
    private static function pageAccessTokenById(string $pageId): ?string
    {
        if (array_key_exists($pageId, self::$pageTokenCache)) {
            return self::$pageTokenCache[$pageId];
        }

        $userToken = getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN');
        if (empty($userToken)) {
            self::$pageTokenCache[$pageId] = null;

            return null;
        }

        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';

        try {
            $client = \Config\Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
            $after = null;

            for ($page = 0; $page < 15; $page++) {
                $query = [
                    'fields'       => 'id,access_token',
                    'limit'        => '100',
                    'access_token' => $userToken,
                ];
                if ($after !== null && $after !== '') {
                    $query['after'] = $after;
                }

                $response = $client->get(
                    'https://graph.facebook.com/' . $version . '/me/accounts',
                    ['query' => $query]
                );
                $body = json_decode((string) $response->getBody(), true);
                if ($response->getStatusCode() !== 200 || ! is_array($body) || isset($body['error'])) {
                    self::$pageTokenCache[$pageId] = null;

                    return null;
                }

                foreach ($body['data'] ?? [] as $row) {
                    if (isset($row['id']) && (string) $row['id'] === $pageId && isset($row['access_token'])) {
                        $token = (string) $row['access_token'];
                        self::$pageTokenCache[$pageId] = $token;

                        return $token;
                    }
                }

                $after = $body['paging']['cursors']['after'] ?? null;
                if ($after === null || $after === '') {
                    break;
                }
            }

            self::$pageTokenCache[$pageId] = null;

            return null;
        } catch (\Throwable $e) {
            log_message('error', 'MetaInstagramGraph::pageAccessTokenById ' . $e->getMessage());
            self::$pageTokenCache[$pageId] = null;

            return null;
        }
    }

    /**
     * Page ID de Facebook enlazada al instagram_business_account de DM profesional.
     * Meta suele usar ese Page ID en sender/recipient del webhook aunque entry.id sea el IG Business Account ID.
     *
     * Orden: 1) /me/accounts (token de usuario con páginas). 2) campo legacy connected_facebook_page sobre el IG id.
     */
    public static function linkedFacebookPageIdForInstagramBusiness(string $instagramBusinessAccountId): ?string
    {
        if ($instagramBusinessAccountId === '') {
            return null;
        }

        if (array_key_exists($instagramBusinessAccountId, self::$linkedFacebookPageIdCache)) {
            return self::$linkedFacebookPageIdCache[$instagramBusinessAccountId];
        }

        $token = getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN');
        if (empty($token)) {
            self::$linkedFacebookPageIdCache[$instagramBusinessAccountId] = null;

            return null;
        }

        $viaAccounts = self::linkedFacebookPageIdViaMeAccounts($instagramBusinessAccountId, $token);
        if ($viaAccounts !== null && $viaAccounts !== '') {
            self::$linkedFacebookPageIdCache[$instagramBusinessAccountId] = $viaAccounts;

            return $viaAccounts;
        }

        $viaField = self::linkedFacebookPageIdViaConnectedPageField($instagramBusinessAccountId, $token);
        self::$linkedFacebookPageIdCache[$instagramBusinessAccountId] = $viaField;

        return $viaField;
    }

    private static function linkedFacebookPageIdViaMeAccounts(string $instagramBusinessAccountId, string $token): ?string
    {
        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';
        $baseUrl = 'https://graph.facebook.com/' . $version . '/me/accounts';

        try {
            $client = \Config\Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
            $after = null;

            for ($page = 0; $page < 15; $page++) {
                $query = [
                    'fields'       => 'id,instagram_business_account{id}',
                    'limit'        => '100',
                    'access_token' => $token,
                ];
                if ($after !== null && $after !== '') {
                    $query['after'] = $after;
                }

                $response = $client->get($baseUrl, ['query' => $query]);
                $body = json_decode((string) $response->getBody(), true);
                if ($response->getStatusCode() !== 200 || ! is_array($body) || isset($body['error'])) {
                    return null;
                }

                foreach ($body['data'] ?? [] as $row) {
                    $igId = $row['instagram_business_account']['id'] ?? null;
                    if ($igId !== null && (string) $igId === $instagramBusinessAccountId && isset($row['id'])) {
                        return (string) $row['id'];
                    }
                }

                $after = $body['paging']['cursors']['after'] ?? null;
                if ($after === null || $after === '') {
                    break;
                }
            }

            return null;
        } catch (\Throwable $e) {
            log_message('error', 'MetaInstagramGraph::linkedFacebookPageIdViaMeAccounts ' . $e->getMessage());

            return null;
        }
    }

    private static function linkedFacebookPageIdViaConnectedPageField(string $instagramBusinessAccountId, string $token): ?string
    {
        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';

        try {
            $client = \Config\Services::curlrequest(['timeout' => 15, 'http_errors' => false]);
            $response = $client->get(
                'https://graph.facebook.com/' . $version . '/' . rawurlencode($instagramBusinessAccountId),
                [
                    'query' => [
                        'fields'       => 'connected_facebook_page',
                        'access_token' => $token,
                    ],
                ]
            );
            $body = json_decode((string) $response->getBody(), true);
            if ($response->getStatusCode() !== 200 || ! is_array($body) || isset($body['error'])) {
                return null;
            }

            $fbPage = $body['connected_facebook_page'] ?? null;
            if (is_array($fbPage) && isset($fbPage['id'])) {
                $pageId = (string) $fbPage['id'];

                return $pageId !== '' ? $pageId : null;
            }

            return null;
        } catch (\Throwable $e) {
            log_message('error', 'MetaInstagramGraph::linkedFacebookPageIdViaConnectedPageField ' . $e->getMessage());

            return null;
        }
    }
}
