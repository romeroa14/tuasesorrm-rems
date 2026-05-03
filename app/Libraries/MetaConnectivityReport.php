<?php

namespace App\Libraries;

use Config\Database;
use Config\Services;

/**
 * Diagnóstico de acceso Meta Graph / Instagram (env + llamadas HTTP controladas).
 * No registra tokens completos en logs de éxito (solo longitud / estado HTTP).
 */
class MetaConnectivityReport
{
    /**
     * @return list<array{key: string, ok: bool, detail: string}>
     */
    public static function collect(bool $includeDb = true): array
    {
        $checks = [];

        $checks[] = self::checkEnvPresent('META_APP_ID', getenv('META_APP_ID') ?: getenv('FACEBOOK_APP_ID'));
        $checks[] = self::checkEnvPresent('META_APP_SECRET', getenv('META_APP_SECRET') ?: getenv('FACEBOOK_APP_SECRET'));
        $checks[] = self::checkEnvPresent(
            'META_GRAPH_ACCESS_TOKEN',
            getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN'),
            'Definir META_GRAPH_ACCESS_TOKEN o META_PAGE_ACCESS_TOKEN'
        );
        $checks[] = self::checkEnvPresent('INSTAGRAM_VERIFY_TOKEN', getenv('INSTAGRAM_VERIFY_TOKEN'), 'Webhook GET verify');

        $mapJson = getenv('META_IG_RECIPIENT_USERNAMES_JSON');
        if ($mapJson !== false && $mapJson !== '') {
            $decoded = json_decode($mapJson, true);
            $checks[] = [
                'key'    => 'META_IG_RECIPIENT_USERNAMES_JSON',
                'ok'     => is_array($decoded),
                'detail' => is_array($decoded)
                    ? 'JSON válido (' . count($decoded) . ' entradas)'
                    : 'JSON inválido',
            ];
        }

        $checks = array_merge($checks, self::checkGraphMe());
        $checks = array_merge($checks, self::checkDebugToken());
        $checks = array_merge($checks, self::checkOptionalIgNode());

        if ($includeDb) {
            $checks[] = self::checkDatabase();
        }

        return $checks;
    }

    /**
     * @return array{key: string, ok: bool, detail: string}
     */
    private static function checkEnvPresent(string $key, $value, string $hint = ''): array
    {
        $ok = $value !== false && $value !== null && $value !== '';

        return [
            'key'    => $key,
            'ok'     => $ok,
            'detail' => $ok
                ? 'definido (' . strlen((string) $value) . ' caracteres)'
                : ('vacío — ' . ($hint ?: 'añadir en .env')),
        ];
    }

    /**
     * @return list<array{key: string, ok: bool, detail: string}>
     */
    private static function checkGraphMe(): array
    {
        $token = getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN');
        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';

        if (empty($token)) {
            return [[
                'key'    => 'Graph GET /me',
                'ok'     => false,
                'detail' => 'omitido (sin token)',
            ]];
        }

        try {
            $client = Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
            $response = $client->get(
                'https://graph.facebook.com/' . $version . '/me',
                [
                    'query' => [
                        'fields'       => 'id,name',
                        'access_token' => $token,
                    ],
                ]
            );
            $code = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);
            if ($code === 200 && ! empty($body['id'])) {
                $name = isset($body['name']) ? ' · ' . $body['name'] : '';

                return [[
                    'key'    => 'Graph GET /me',
                    'ok'     => true,
                    'detail' => 'HTTP ' . $code . ' · id=' . $body['id'] . $name,
                ]];
            }
            $err = $body['error']['message'] ?? json_encode($body);

            return [[
                'key'    => 'Graph GET /me',
                'ok'     => false,
                'detail' => 'HTTP ' . $code . ' · ' . $err,
            ]];
        } catch (\Throwable $e) {
            return [[
                'key'    => 'Graph GET /me',
                'ok'     => false,
                'detail' => $e->getMessage(),
            ]];
        }
    }

    /**
     * Opcional: si hay APP_ID + SECRET, valida el token con debug_token.
     *
     * @return list<array{key: string, ok: bool, detail: string}>
     */
    private static function checkDebugToken(): array
    {
        $token = getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN');
        $appId = getenv('META_APP_ID') ?: getenv('FACEBOOK_APP_ID');
        $secret = getenv('META_APP_SECRET') ?: getenv('FACEBOOK_APP_SECRET');

        if (empty($token) || empty($appId) || empty($secret)) {
            return [[
                'key'    => 'Graph debug_token',
                'ok'     => true,
                'detail' => 'omitido (opcional: META_APP_ID + META_APP_SECRET para validar scopes)',
            ]];
        }

        try {
            $client = Services::curlrequest(['timeout' => 20, 'http_errors' => false]);
            $appAccessToken = $appId . '|' . $secret;
            $response = $client->get(
                'https://graph.facebook.com/debug_token',
                [
                    'query' => [
                        'input_token'   => $token,
                        'access_token'  => $appAccessToken,
                    ],
                ]
            );
            $code = $response->getStatusCode();
            $body = json_decode((string) $response->getBody(), true);
            $data = $body['data'] ?? null;

            if ($code === 200 && is_array($data)) {
                $valid = ! empty($data['is_valid']);
                $scopes = isset($data['scopes']) ? implode(', ', (array) $data['scopes']) : '—';
                $exp = isset($data['expires_at']) ? (string) $data['expires_at'] : '—';

                return [[
                    'key'    => 'Graph debug_token',
                    'ok'     => $valid,
                    'detail' => ($valid ? 'válido' : 'inválido') . ' · scopes: ' . $scopes . ' · expires_at: ' . $exp,
                ]];
            }

            $err = $body['error']['message'] ?? json_encode($body);

            return [[
                'key'    => 'Graph debug_token',
                'ok'     => false,
                'detail' => 'HTTP ' . $code . ' · ' . $err,
            ]];
        } catch (\Throwable $e) {
            return [[
                'key'    => 'Graph debug_token',
                'ok'     => false,
                'detail' => $e->getMessage(),
            ]];
        }
    }

    /**
     * Opcional: mismo endpoint que el pipeline para resolver @username (entry.id).
     *
     * @return list<array{key: string, ok: bool, detail: string}>
     */
    private static function checkOptionalIgNode(): array
    {
        $nodeId = getenv('META_TEST_IG_RECIPIENT_ID');
        if (empty($nodeId)) {
            return [[
                'key'    => 'Graph IG node (META_TEST_IG_RECIPIENT_ID)',
                'ok'     => true,
                'detail' => 'omitido (opcional: define META_TEST_IG_RECIPIENT_ID = entry.id del webhook)',
            ]];
        }

        $user = MetaInstagramGraph::resolveRecipientUsername((string) $nodeId);

        return [[
            'key'    => 'Graph IG node (META_TEST_IG_RECIPIENT_ID)',
            'ok'     => $user !== null && $user !== '',
            'detail' => $user !== null && $user !== ''
                ? 'username=' . $user
                : 'sin username (revisa token / permisos / mapa JSON)',
        ]];
    }

    /**
     * @return array{key: string, ok: bool, detail: string}
     */
    private static function checkDatabase(): array
    {
        try {
            $db = Database::connect();
            $db->query('SELECT 1');

            return [
                'key'    => 'MySQL',
                'ok'     => true,
                'detail' => 'conexión OK · ' . ($db->database ?? ''),
            ];
        } catch (\Throwable $e) {
            return [
                'key'    => 'MySQL',
                'ok'     => false,
                'detail' => $e->getMessage(),
            ];
        }
    }

    public static function allOk(array $checks): bool
    {
        foreach ($checks as $c) {
            if (empty($c['ok'])) {
                return false;
            }
        }

        return true;
    }
}
