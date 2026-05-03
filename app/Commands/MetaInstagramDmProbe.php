<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

/**
 * Lista páginas, instagram_business_account.id y conversaciones DM (orden reciente según Meta).
 *
 * Selección de cuenta (en orden):
 *   1. META_PROBE_INSTAGRAM_USERNAME=migliorerossana (sin @)
 *   2. META_PROBE_PAGE_ID=número
 *   3. Primera Page que **tenga** Instagram Business vinculado (ya no la primera lista ciega).
 *
 * Cantidad de hilos DM pedidos a Graph (default 3):
 *   META_PROBE_CONVERSATIONS_LIMIT=1  o  php spark meta:ig-dm-probe --limit=2
 *
 * Uso:
 *   php spark meta:ig-dm-probe
 *   php spark meta:ig-dm-probe --limit=1
 *
 * Nota: el CRM Inbox no llama a Graph al abrir un chat; muestra lo guardado por webhook en la BD.
 * Esta herramienta sí llama a Graph para comparar con instagram.com.
 */
class MetaInstagramDmProbe extends BaseCommand
{
    protected $group       = 'Meta';
    protected $name        = 'meta:ig-dm-probe';
    protected $usage       = 'meta:ig-dm-probe [--limit=N]';
    protected $description = 'Graph API: IG DM — últimas N conversaciones';

    public function run(array $params)
    {
        CLI::write(
            'Nota: el Inbox del CRM lee la BASE DE DATOS (mensajes que llegaron vía webhook). '
            . 'Instagram puede mostrar hilo vacío si borraron historial o cambió el contexto.',
            'cyan'
        );
        CLI::newLine();

        $limit = $this->resolveLimit();

        $token = getenv('META_GRAPH_ACCESS_TOKEN') ?: getenv('META_PAGE_ACCESS_TOKEN');
        $version = getenv('META_GRAPH_API_VERSION') ?: 'v21.0';

        if ($token === false || $token === '') {
            CLI::error('Falta META_GRAPH_ACCESS_TOKEN o META_PAGE_ACCESS_TOKEN en .env');

            return 1;
        }

        $client = Services::curlrequest(['timeout' => 60, 'http_errors' => false]);
        $base = 'https://graph.facebook.com/' . $version;

        CLI::write('GET /me/accounts → Page id + instagram_business_account.id (@username)', 'yellow');
        CLI::newLine();

        $r = $client->get($base . '/me/accounts', [
            'query' => [
                'fields'       => 'id,name,access_token,instagram_business_account{id,username,profile_picture_url}',
                'access_token' => $token,
            ],
        ]);

        $accountsJson = (string) $r->getBody();
        $accounts = json_decode($accountsJson, true);

        if ($r->getStatusCode() !== 200 || ! is_array($accounts)) {
            CLI::error('Error Graph API /me/accounts HTTP ' . $r->getStatusCode());
            CLI::write($accountsJson);

            return 1;
        }

        CLI::write('Webhook: cada evento trae entry[].id — suele coincidir con Page id o con instagram_business_account.id.', 'cyan');
        CLI::write('Para una sola cuenta: META_PROBE_INSTAGRAM_USERNAME=migliorerossana en .env', 'cyan');
        CLI::newLine();

        foreach ($accounts['data'] ?? [] as $page) {
            $pid = $page['id'] ?? '';
            $name = $page['name'] ?? '';
            CLI::write('[Page] id=' . $pid . ' · ' . $name, 'green');
            $ig = $page['instagram_business_account'] ?? null;
            if (is_array($ig)) {
                CLI::write('       instagram_business_account.id=' . ($ig['id'] ?? '') . '  @' . ($ig['username'] ?? ''));
            } else {
                CLI::write('       (sin cuenta Instagram Business en esta Page)', 'dark_gray');
            }
            CLI::newLine();
        }

        $resolved = $this->resolveProbeTarget($accounts['data'] ?? [], $token);

        if ($resolved['page_id'] === '') {
            CLI::write('Sin Page válida: define META_PROBE_PAGE_ID o META_PROBE_INSTAGRAM_USERNAME en .env.', 'yellow');

            return 0;
        }

        CLI::write(
            'Probando conversaciones → Page id=' . $resolved['page_id']
            . ' · @' . ($resolved['ig_username'] ?: '?')
            . ' · razón: ' . $resolved['reason'],
            'yellow'
        );
        CLI::newLine();

        $pageToken = $resolved['page_token'];

        CLI::write(
            'GET /' . $resolved['page_id'] . '/conversations?platform=instagram&limit=' . $limit
            . ' (últimos hilos por API; no es el contenido del CRM Inbox)',
            'yellow'
        );
        CLI::newLine();

        $query = [
            'platform'     => 'instagram',
            'limit'        => $limit,
            'fields'       => 'id,updated_time,senders{name,username}',
            'access_token' => $pageToken,
        ];

        $r2 = $client->get($base . '/' . $resolved['page_id'] . '/conversations', [
            'query' => $query,
        ]);

        $convHttp = $r2->getStatusCode();
        $convBody = (string) $r2->getBody();

        if ($convHttp !== 200) {
            unset($query['fields']);
            CLI::write('Reintentando sin fields=… (algunas cuentas rechazan el parámetro)', 'yellow');
            $r2 = $client->get($base . '/' . $resolved['page_id'] . '/conversations', [
                'query' => $query,
            ]);
            $convHttp = $r2->getStatusCode();
            $convBody = (string) $r2->getBody();
        }

        CLI::write('HTTP ' . $convHttp, $convHttp === 200 ? 'green' : 'red');
        CLI::write($convBody);

        $convJson = json_decode($convBody, true);

        if ($convHttp === 200 && is_array($convJson) && ! empty($convJson['data']) && is_array($convJson['data'])) {
            CLI::newLine();
            CLI::write('Resumen (orden devuelto por Meta):', 'green');
            $i = 1;
            foreach ($convJson['data'] as $row) {
                if (! is_array($row)) {
                    continue;
                }
                $cid = (string) ($row['id'] ?? '');
                $upd = (string) ($row['updated_time'] ?? '');
                $senderHint = '';
                $senders = $row['senders']['data'] ?? $row['senders'] ?? [];
                if (is_array($senders)) {
                    foreach ($senders as $s) {
                        if (! is_array($s)) {
                            continue;
                        }
                        $u = (string) ($s['username'] ?? '');
                        if ($u !== '') {
                            $senderHint .= ($senderHint !== '' ? ', ' : '') . '@' . $u;
                        }
                    }
                }
                CLI::write(
                    '  ' . $i . ') id=' . $cid . ($upd !== '' ? ' · actualizado ' . $upd : '')
                    . ($senderHint !== '' ? ' · ' . $senderHint : ''),
                    'white'
                );
                $i++;
            }
        }

        if (is_array($convJson) && isset($convJson['error']['error_subcode'])
            && (int) $convJson['error']['error_subcode'] === 2534084) {
            CLI::newLine();
            CLI::write(
                'Meta indica timeout por volumen de conversaciones con usuarios sin rol en la app. '
                . 'Solicita Advanced Access a instagram_manage_messages o reduce alcance.',
                'yellow'
            );
            CLI::write(
                'Para el CRM igual puedes mapear entry.id → @usuario en .env, p. ej.: '
                . 'META_IG_RECIPIENT_USERNAMES_JSON={"'
                . ($resolved['ig_business_id'] ?? '')
                . '":"'
                . ($resolved['ig_username'] ?? '')
                . '"}',
                'cyan'
            );
        }

        CLI::newLine();
        CLI::write('Curl equivalente:', 'cyan');
        CLI::write(
            'curl -sG "' . $base . '/' . $resolved['page_id'] . '/conversations"'
            . ' --data-urlencode "platform=instagram"'
            . ' --data-urlencode "limit=' . $limit . '"'
            . ' --data-urlencode "fields=id,updated_time,senders{name,username}"'
            . ' --data-urlencode "access_token=PAGE_ACCESS_TOKEN"',
            'white'
        );

        return $convHttp === 200 ? 0 : 1;
    }

    /**
     * Default 1 conversación; máx. 25 (Graph).
     */
    private function resolveLimit(): int
    {
        $env = getenv('META_PROBE_CONVERSATIONS_LIMIT');
        if ($env !== false && $env !== '' && ctype_digit(trim((string) $env))) {
            return max(1, min(25, (int) trim((string) $env)));
        }

        foreach ($_SERVER['argv'] ?? [] as $arg) {
            if (preg_match('/^--limit=(\d+)$/', (string) $arg, $m)) {
                return max(1, min(25, (int) $m[1]));
            }
        }

        return 1;
    }

    /**
     * @param list<array<string, mixed>> $pages
     *
     * @return array{page_id: string, page_token: string, ig_username: string, ig_business_id: string, reason: string}
     */
    private function resolveProbeTarget(array $pages, string $fallbackToken): array
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
                    'reason'         => 'META_PROBE_INSTAGRAM_USERNAME=@' . ($ig['username'] ?? ''),
                ];
            }

            CLI::write(
                'META_PROBE_INSTAGRAM_USERNAME=@' . $wantUser . ' no coincide con ninguna Page listada.',
                'red'
            );
            CLI::newLine();
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

            CLI::write('META_PROBE_PAGE_ID no coincide con ninguna cuenta devuelta por /me/accounts.', 'red');
            CLI::newLine();
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
                'reason'         => 'primera Page con Instagram Business (define META_PROBE_INSTAGRAM_USERNAME para fijar una)',
            ];
        }

        return $empty;
    }
}
