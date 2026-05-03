<?php

namespace App\Libraries;

/**
 * Resolución de @username de la cuenta profesional de Instagram (receptor del webhook).
 * Usa Graph API y/o mapa opcional en META_IG_RECIPIENT_USERNAMES_JSON.
 */
class MetaInstagramGraph
{
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
}
