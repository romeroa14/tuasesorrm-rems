<?php

declare(strict_types=1);

namespace App\Commands;

use App\Controllers\WebhookController;
use App\Libraries\InstagramMessageMapper;
use App\Models\Conversation;
use App\Models\Funnels;
use App\Models\Leads;
use App\Models\Message;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

/**
 * Recovers Instagram DMs that never reached the webhook, straight from the Graph API.
 *
 * Side-effect free on purpose: it only inserts missing leads, conversations and messages.
 * It never triggers AI replies, never sends messages and skips any message already stored.
 */
class InstagramBackfillMessages extends BaseCommand
{
    protected $group       = 'CRM';
    protected $name        = 'instagram:backfill-messages';
    protected $description = 'Importa desde la Graph API los mensajes de Instagram que no entraron por el webhook.';
    protected $usage       = 'instagram:backfill-messages --since YYYY-MM-DD [--until YYYY-MM-DD] [--accounts user1,user2] [--dry-run]';
    protected $options     = [
        '--since'    => 'Fecha inicial (UTC, incluida). Obligatoria.',
        '--until'    => 'Fecha final (UTC, excluida). Por defecto mañana.',
        '--accounts' => 'Usuarios de Instagram receptores. Por defecto los que ya tienen conversaciones.',
        '--dry-run'  => 'Solo cuenta lo que importaría, sin escribir.',
    ];

    private string $version = 'v21.0';

    public function run(array $params)
    {
        $since  = (string) (CLI::getOption('since') ?? '');
        $until  = (string) (CLI::getOption('until') ?? gmdate('Y-m-d', strtotime('+1 day')));
        $dryRun = CLI::getOption('dry-run') !== null;
        if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $since) !== 1 || preg_match('/^\d{4}-\d{2}-\d{2}$/', $until) !== 1) {
            CLI::error('Usa --since YYYY-MM-DD (y opcionalmente --until YYYY-MM-DD), separados por espacio.');

            return EXIT_ERROR;
        }
        $from = $since . ' 00:00:00';
        $to   = $until . ' 00:00:00';

        $this->version = getenv('META_GRAPH_API_VERSION') ?: $this->version;
        $userToken     = (string) getenv('META_GRAPH_ACCESS_TOKEN');
        if ($userToken === '') {
            CLI::error('Falta META_GRAPH_ACCESS_TOKEN en el .env.');

            return EXIT_ERROR;
        }

        $accounts = array_filter(array_map('trim', explode(',', (string) (CLI::getOption('accounts') ?? ''))));
        if ($accounts === []) {
            $accounts = array_column(db_connect()->query(
                "SELECT DISTINCT recipient_ig_username FROM conversations WHERE recipient_ig_username IS NOT NULL AND recipient_ig_username <> ''"
            )->getResultArray(), 'recipient_ig_username');
        }

        $pages = $this->graph('me/accounts', ['fields' => 'id,name,access_token,instagram_business_account{id,username}', 'limit' => 100], $userToken)['data'] ?? [];
        $totals = ['conversations' => 0, 'new_conversations' => 0, 'new_messages' => 0, 'skipped' => 0];

        foreach ($pages as $page) {
            $ig = $page['instagram_business_account'] ?? null;
            if (! is_array($ig) || ! in_array((string) ($ig['username'] ?? ''), $accounts, true)) {
                continue;
            }
            $stats = $this->importAccount((string) $page['id'], (string) $page['access_token'], (string) $ig['id'], (string) $ig['username'], $from, $to, $dryRun);
            CLI::write(sprintf('%-18s conversaciones=%d nuevas=%d mensajes_nuevos=%d ya_existian=%d', $ig['username'], $stats['conversations'], $stats['new_conversations'], $stats['new_messages'], $stats['skipped']));
            foreach ($totals as $k => $v) {
                $totals[$k] = $v + $stats[$k];
            }
        }

        CLI::write(sprintf('%sTOTAL conversaciones=%d nuevas=%d mensajes_nuevos=%d ya_existian=%d', $dryRun ? '[dry-run] ' : '', ...array_values($totals)), 'green');

        return EXIT_SUCCESS;
    }

    /** @return array{conversations: int, new_conversations: int, new_messages: int, skipped: int} */
    private function importAccount(string $pageId, string $pageToken, string $igId, string $igUsername, string $from, string $to, bool $dryRun): array
    {
        $stats    = ['conversations' => 0, 'new_conversations' => 0, 'new_messages' => 0, 'skipped' => 0];
        $messages = new Message();
        $convs    = new Conversation();

        $next = $this->graphUrl($pageId . '/conversations', ['platform' => 'instagram', 'fields' => 'id,updated_time,participants', 'limit' => 50], $pageToken);
        while ($next !== null) {
            $res  = $this->getJson($next);
            $next = $res['paging']['next'] ?? null;
            foreach ($res['data'] ?? [] as $thread) {
                if (gmdate('Y-m-d H:i:s', (int) strtotime((string) $thread['updated_time'])) < $from) {
                    $next = null; // conversations come newest first
                    break;
                }
                $stats['conversations']++;
                $customer = null;
                foreach ($thread['participants']['data'] ?? [] as $p) {
                    if ((string) $p['id'] !== $igId) {
                        $customer = $p;
                    }
                }
                if ($customer === null) {
                    continue;
                }

                $rows = [];
                $url  = $this->graphUrl((string) $thread['id'] . '/messages', ['fields' => 'id,created_time,from,to,message', 'limit' => 50], $pageToken);
                while ($url !== null) {
                    $page = $this->getJson($url);
                    $url  = $page['paging']['next'] ?? null;
                    foreach ($page['data'] ?? [] as $m) {
                        $row = InstagramMessageMapper::toRow($m, $igId);
                        if ($row !== null && $row['created_at'] >= $from && $row['created_at'] < $to) {
                            $rows[] = $row;
                        }
                    }
                }
                if ($rows === []) {
                    continue;
                }
                usort($rows, static fn (array $a, array $b): int => strcmp($a['created_at'], $b['created_at']));

                $missing = [];
                foreach ($rows as $row) {
                    if ($messages->where('external_message_id', $row['external_message_id'])->first() !== null) {
                        $stats['skipped']++;
                    } else {
                        $missing[] = $row;
                    }
                }
                if ($missing === []) {
                    continue;
                }

                $conversation = $convs->findByExternalId('instagram', (string) $customer['id'], $igId);
                if ($conversation === null) {
                    $stats['new_conversations']++;
                }
                $stats['new_messages'] += count($missing);
                if ($dryRun) {
                    continue;
                }

                $conversationId = $conversation !== null
                    ? (int) $conversation['id']
                    : $this->createConversation((string) $customer['id'], (string) ($customer['username'] ?? ''), $igId, $igUsername, $missing);

                foreach ($missing as $row) {
                    $messages->insert([
                        'conversation_id'     => $conversationId,
                        'direction'           => $row['direction'],
                        'sender_type'         => $row['sender_type'],
                        'sender_id'           => null,
                        'content'             => $row['content'],
                        'content_type'        => $row['content_type'],
                        'external_message_id' => $row['external_message_id'],
                        'created_at'          => $row['created_at'],
                    ]);
                }
                $last = end($missing);
                if ($conversation === null || $last['created_at'] > (string) $conversation['last_message_at']) {
                    $convs->update($conversationId, ['last_message_at' => $last['created_at']]);
                }
            }
        }

        return $stats;
    }

    /** @param list<array<string, string>> $rows */
    private function createConversation(string $customerId, string $username, string $igId, string $igUsername, array $rows): int
    {
        $funnel   = (new Funnels())->where('name LIKE', '%Instagram DM%')->first();
        $handle   = $username !== '' ? '@' . ltrim($username, '@') : null;
        $leadId   = (new Leads())->insert([
            'name'               => WebhookController::buildInstagramLeadName($handle !== null ? ['username' => $username] : null, $customerId),
            'phone'              => null,
            'email'              => null,
            'instagram_username' => $handle,
            'last_resolution_at' => gmdate('Y-m-d H:i:s'),
            'resolution_status'  => $handle !== null ? 'resolved' : 'failed',
            'id_user'            => null,
            'id_funnel'          => $funnel ? $funnel['id'] : 33,
            'id_housingtype'     => 1,
            'id_businessmodel'   => 1,
            'observation'        => 'Lead recuperado desde Instagram DM (backfill)',
            'status'             => 'Activo',
            'intention_score'    => 0,
            'intention_label'    => 'frio',
        ]);
        $last = end($rows);

        $conversationId = (int) (new Conversation())->insert([
            'lead_id'               => $leadId,
            'channel'               => 'instagram',
            'external_id'           => $customerId,
            'external_username'     => $username,
            'recipient_ig_id'       => $igId,
            'recipient_ig_username' => $igUsername,
            'status'                => 'open',
            'last_message_at'       => $last['created_at'],
            'unread_count'          => $last['direction'] === 'inbound' ? 1 : 0,
            'ai_auto_reply'         => 0,
        ]);
        // The model stamps created_at with "now"; keep the real start of the chat so date filters place it right.
        db_connect()->table('conversations')->where('id', $conversationId)->update(['created_at' => $rows[0]['created_at']]);

        return $conversationId;
    }

    /** @param array<string, mixed> $query */
    private function graph(string $path, array $query, string $token): array
    {
        return $this->getJson($this->graphUrl($path, $query, $token));
    }

    /** @param array<string, mixed> $query */
    private function graphUrl(string $path, array $query, string $token): string
    {
        return 'https://graph.facebook.com/' . $this->version . '/' . $path . '?' . http_build_query($query + ['access_token' => $token]);
    }

    private function getJson(string $url): array
    {
        $response = Services::curlrequest(['timeout' => 60, 'http_errors' => false])->get($url);
        $json     = json_decode((string) $response->getBody(), true) ?: [];
        if (isset($json['error'])) {
            throw new \RuntimeException('Graph API: ' . ($json['error']['message'] ?? 'error'));
        }

        return $json;
    }
}
