<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\Conversation;
use App\Models\Message;
use Config\Services;

/**
 * Descarga hilos DM recientes desde Graph API y los fusiona en messages/conversations locales.
 * Requiere credenciales Meta iguales que meta:ig-dm-probe (META_GRAPH_ACCESS_TOKEN, META_PROBE_*).
 */
class InstagramDmGraphSync
{
    public static function apiVersion(): string
    {
        $v = getenv('META_GRAPH_API_VERSION');

        return ($v !== false && $v !== '') ? (string) $v : 'v21.0';
    }

    public static function baseUrl(): string
    {
        return 'https://graph.facebook.com/' . self::apiVersion();
    }

    public static function userToken(): ?string
    {
        $t = getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN');

        return ($t !== false && $t !== '') ? $t : null;
    }

    /**
     * @return array{ok: bool, threads_processed: int, messages_inserted: int, skipped_no_local_conv: int, graph_http?: int, graph_error?: mixed, detail?: string}
     */
    public static function syncRecentThreads(
        Conversation $conversationModel,
        Message $messageModel,
        int $threadLimit = 2,
        int $messagesPerThread = 10
    ): array {
        $empty = [
            'ok'                    => false,
            'threads_processed'     => 0,
            'messages_inserted'     => 0,
            'skipped_no_local_conv' => 0,
        ];

        $threadLimit = max(1, min(25, $threadLimit));
        $messagesPerThread = max(1, min(100, $messagesPerThread));

        $token = self::userToken();
        if ($token === null) {
            return $empty + ['detail' => 'Falta META_GRAPH_ACCESS_TOKEN'];
        }

        $accounts = self::fetchMeAccounts($token);
        if (! is_array($accounts) || isset($accounts['error'])) {
            return $empty + ['detail' => 'Error /me/accounts', 'graph_error' => $accounts['error'] ?? null];
        }

        $ctx = self::resolvePageContext($accounts['data'] ?? [], $token);
        if ($ctx['page_id'] === '') {
            return $empty + ['detail' => 'Sin Page Instagram resolvible (META_PROBE_*)'];
        }

        $convRes = self::fetchInstagramConversations($ctx['page_id'], $ctx['page_token'], $threadLimit);
        if (! $convRes['ok']) {
            return $empty + [
                'graph_http'  => $convRes['http'],
                'graph_error' => is_array($convRes['body']) ? ($convRes['body']['error'] ?? $convRes['body']) : null,
                'detail'      => 'Fallo al listar conversaciones Graph',
            ];
        }

        $body = $convRes['body'];
        if (! is_array($body) || ! empty($body['error'])) {
            return $empty + ['graph_error' => $body['error'] ?? null, 'detail' => 'Graph devolvió error en JSON'];
        }

        $rows = $body['data'] ?? [];
        if (! is_array($rows)) {
            return $empty + ['detail' => 'Sin data[] en respuesta Graph'];
        }

        $inserted = 0;
        $processed = 0;
        $skipped = 0;
        $bizIgId = $ctx['ig_business_id'];

        foreach ($rows as $row) {
            if (! is_array($row)) {
                continue;
            }
            $graphConvId = (string) ($row['id'] ?? '');
            if ($graphConvId === '') {
                continue;
            }

            if (! isset($row['senders']) || ! self::sendersHasParticipants($row['senders'])) {
                $sendersPatch = self::fetchConversationSenders($graphConvId, $ctx['page_token']);
                $row = array_merge($row, $sendersPatch);
            }

            $peerId = self::peerParticipantId($row, $bizIgId, $ctx['ig_username']);
            if ($peerId === null || $peerId === '') {
                $skipped++;

                continue;
            }

            $local = $conversationModel->where('channel', 'instagram')
                ->where('external_id', $peerId)
                ->where('recipient_ig_id', $bizIgId)
                ->orderBy('id', 'DESC')
                ->first();

            if (! $local && $bizIgId !== '') {
                $local = $conversationModel->where('channel', 'instagram')
                    ->where('external_id', $peerId)
                    ->where('recipient_ig_id', '')
                    ->orderBy('id', 'DESC')
                    ->first();
            }

            if (! $local) {
                $skipped++;

                continue;
            }

            $msgRes = self::fetchConversationMessages($graphConvId, $ctx['page_token'], $messagesPerThread);
            if (! $msgRes['ok'] || ! is_array($msgRes['body'])) {
                continue;
            }

            $msgs = $msgRes['body']['messages']['data'] ?? [];
            if (! is_array($msgs)) {
                continue;
            }

            usort($msgs, static function ($a, $b) {
                $ta = is_array($a) ? (string) ($a['created_time'] ?? '') : '';
                $tb = is_array($b) ? (string) ($b['created_time'] ?? '') : '';

                return strcmp($ta, $tb);
            });

            $processed++;
            $lastAt = null;

            foreach ($msgs as $m) {
                if (! is_array($m)) {
                    continue;
                }
                $mid = (string) ($m['id'] ?? '');
                if ($mid === '') {
                    continue;
                }

                $existing = $messageModel->where('external_message_id', $mid)->first();
                if ($existing) {
                    continue;
                }

                $from = $m['from'] ?? null;
                $fromId = is_array($from) ? (string) ($from['id'] ?? '') : '';
                $isInbound = ($fromId !== '' && $fromId === $peerId);
                $text = (string) ($m['message'] ?? '');
                if ($text === '' && empty($m['attachments'])) {
                    continue;
                }

                $created = self::graphTimeToSql((string) ($m['created_time'] ?? ''));
                if ($created === '') {
                    $created = date('Y-m-d H:i:s');
                }

                $messageModel->insert([
                    'conversation_id'       => (int) $local['id'],
                    'direction'             => $isInbound ? 'inbound' : 'outbound',
                    'sender_type'           => $isInbound ? 'lead' : 'agent',
                    'sender_id'             => null,
                    'content'               => $text !== '' ? $text : '[mensaje sin texto]',
                    'content_type'          => 'text',
                    'external_message_id'   => $mid,
                    'created_at'            => $created,
                ]);

                $inserted++;
                $lastAt = $created;
            }

            if ($lastAt !== null) {
                $conversationModel->update((int) $local['id'], ['last_message_at' => $lastAt]);
            }
        }

        return [
            'ok'                    => true,
            'threads_processed'     => $processed,
            'messages_inserted'     => $inserted,
            'skipped_no_local_conv' => $skipped,
        ];
    }

    /**
     * @return array<string, mixed>|null
     */
    private static function fetchMeAccounts(string $token): ?array
    {
        try {
            $client = Services::curlrequest(['timeout' => 60, 'http_errors' => false]);
            $r = $client->get(self::baseUrl() . '/me/accounts', [
                'query' => [
                    'fields'       => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url}',
                    'access_token' => $token,
                ],
            ]);
            $decoded = json_decode((string) $r->getBody(), true);

            return is_array($decoded) ? $decoded : null;
        } catch (\Throwable $e) {
            log_message('error', 'InstagramDmGraphSync::fetchMeAccounts ' . $e->getMessage());

            return null;
        }
    }

    /**
     * @param list<array<string, mixed>> $pages
     *
     * @return array{page_id: string, page_token: string, ig_username: string, ig_business_id: string, reason: string}
     */
    private static function resolvePageContext(array $pages, string $fallbackToken): array
    {
        $empty = [
            'page_id'        => '',
            'page_token'     => $fallbackToken,
            'ig_username'    => '',
            'ig_business_id' => '',
            'reason'         => '',
        ];

        $wantUser = getenv('META_PROBE_INSTAGRAM_USERNAME');
        $wantUser = $wantUser !== false && $wantUser !== ''
            ? strtolower(ltrim(trim((string) $wantUser), '@'))
            : '';

        if ($wantUser !== '') {
            foreach ($pages as $page) {
                $ig = $page['instagram_business_account'] ?? null;
                if (! is_array($ig)) {
                    continue;
                }
                $u = strtolower((string) ($ig['username'] ?? ''));
                if ($u !== $wantUser) {
                    continue;
                }

                return [
                    'page_id'        => (string) ($page['id'] ?? ''),
                    'page_token'     => ! empty($page['access_token']) ? (string) $page['access_token'] : $fallbackToken,
                    'ig_username'    => (string) ($ig['username'] ?? ''),
                    'ig_business_id' => (string) ($ig['id'] ?? ''),
                    'reason'         => 'META_PROBE_INSTAGRAM_USERNAME',
                ];
            }
        }

        $explicitPage = getenv('META_PROBE_PAGE_ID');
        if ($explicitPage !== false && $explicitPage !== '') {
            foreach ($pages as $page) {
                if ((string) ($page['id'] ?? '') !== (string) $explicitPage) {
                    continue;
                }
                $ig = $page['instagram_business_account'] ?? null;

                return [
                    'page_id'        => (string) $explicitPage,
                    'page_token'     => ! empty($page['access_token']) ? (string) $page['access_token'] : $fallbackToken,
                    'ig_username'    => is_array($ig) ? (string) ($ig['username'] ?? '') : '',
                    'ig_business_id' => is_array($ig) ? (string) ($ig['id'] ?? '') : '',
                    'reason'         => 'META_PROBE_PAGE_ID',
                ];
            }
        }

        foreach ($pages as $page) {
            $ig = $page['instagram_business_account'] ?? null;
            if (! is_array($ig)) {
                continue;
            }

            return [
                'page_id'        => (string) ($page['id'] ?? ''),
                'page_token'     => ! empty($page['access_token']) ? (string) $page['access_token'] : $fallbackToken,
                'ig_username'    => (string) ($ig['username'] ?? ''),
                'ig_business_id' => (string) ($ig['id'] ?? ''),
                'reason'         => 'primera Page con Instagram Business',
            ];
        }

        return $empty;
    }

    /**
     * @return array{ok: bool, http: int, body: mixed}
     */
    private static function fetchInstagramConversations(string $pageId, string $pageToken, int $limit): array
    {
        try {
            $client = Services::curlrequest(['timeout' => 90, 'http_errors' => false]);
            $baseQuery = [
                'platform'     => 'instagram',
                'limit'        => $limit,
                'access_token' => $pageToken,
            ];

            // Solo `id` en el listado: expandir senders aquí dispara error Meta código 1 ("reduce amount of data").
            $attempts = [
                array_merge($baseQuery, ['fields' => 'id']),
                $baseQuery,
            ];

            $r = null;
            $body = null;

            foreach ($attempts as $query) {
                $r = $client->get(self::baseUrl() . '/' . rawurlencode($pageId) . '/conversations', ['query' => $query]);
                $body = json_decode((string) $r->getBody(), true);
                if ($r->getStatusCode() === 200 && is_array($body) && empty($body['error'])) {
                    return ['ok' => true, 'http' => $r->getStatusCode(), 'body' => $body];
                }
            }

            return ['ok' => false, 'http' => $r !== null ? $r->getStatusCode() : 0, 'body' => $body];
        } catch (\Throwable $e) {
            log_message('error', 'InstagramDmGraphSync::fetchInstagramConversations ' . $e->getMessage());

            return ['ok' => false, 'http' => 0, 'body' => null];
        }
    }

    /**
     * Senders por nodo conversación (petición pequeña frente a incluirlos en /conversations).
     *
     * @return array{senders?: mixed}
     */
    private static function fetchConversationSenders(string $graphConversationId, string $pageToken): array
    {
        foreach (['senders{id}', 'senders{id,username}'] as $fields) {
            try {
                $client = Services::curlrequest(['timeout' => 60, 'http_errors' => false]);
                $r = $client->get(self::baseUrl() . '/' . rawurlencode($graphConversationId), [
                    'query' => [
                        'fields'       => $fields,
                        'access_token' => $pageToken,
                    ],
                ]);
                $body = json_decode((string) $r->getBody(), true);
                if ($r->getStatusCode() === 200 && is_array($body) && empty($body['error']) && isset($body['senders'])) {
                    return ['senders' => $body['senders']];
                }
            } catch (\Throwable $e) {
                log_message('error', 'InstagramDmGraphSync::fetchConversationSenders ' . $e->getMessage());
            }
        }

        return [];
    }

    /**
     * @param mixed $senders Campo Graph senders (array o object con data[])
     */
    private static function sendersHasParticipants($senders): bool
    {
        if (! is_array($senders)) {
            return false;
        }
        $list = $senders['data'] ?? $senders;
        if (! is_array($list)) {
            return false;
        }
        foreach ($list as $item) {
            if (is_array($item)) {
                return true;
            }
        }

        return false;
    }

    /**
     * @return array{ok: bool, http: int, body: mixed}
     */
    private static function fetchConversationMessages(string $graphConversationId, string $pageToken, int $limit): array
    {
        try {
            $client = Services::curlrequest(['timeout' => 90, 'http_errors' => false]);
            $fields = 'messages.limit(' . $limit . '){id,message,created_time,from{id}}';
            $r = $client->get(self::baseUrl() . '/' . rawurlencode($graphConversationId), [
                'query' => [
                    'fields'       => $fields,
                    'access_token' => $pageToken,
                ],
            ]);
            $body = json_decode((string) $r->getBody(), true);

            return [
                'ok'   => $r->getStatusCode() === 200 && is_array($body),
                'http' => $r->getStatusCode(),
                'body' => $body,
            ];
        } catch (\Throwable $e) {
            log_message('error', 'InstagramDmGraphSync::fetchConversationMessages ' . $e->getMessage());

            return ['ok' => false, 'http' => 0, 'body' => null];
        }
    }

    /**
     * Identifica el participante que no es la cuenta profesional (PSID/IGSID del cliente).
     *
     * @param array<string, mixed> $convRow
     */
    private static function peerParticipantId(array $convRow, string $igBusinessId, string $bizUsername): ?string
    {
        $senders = $convRow['senders']['data'] ?? $convRow['senders'] ?? [];
        if (! is_array($senders)) {
            return null;
        }

        $bizUserLower = strtolower(ltrim(trim($bizUsername), '@'));

        foreach ($senders as $s) {
            if (! is_array($s)) {
                continue;
            }
            $id = (string) ($s['id'] ?? '');
            if ($id === '') {
                continue;
            }
            if ($igBusinessId !== '' && $id === $igBusinessId) {
                continue;
            }
            $uname = strtolower(ltrim((string) ($s['username'] ?? ''), '@'));
            if ($igBusinessId === '' && $bizUserLower !== '' && $uname !== '' && $uname === $bizUserLower) {
                continue;
            }

            return $id;
        }

        return null;
    }

    private static function graphTimeToSql(string $iso): string
    {
        if ($iso === '') {
            return '';
        }
        $ts = strtotime($iso);

        return $ts !== false ? date('Y-m-d H:i:s', $ts) : '';
    }
}
