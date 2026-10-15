<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\MetaInstagramGraph;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class InstagramBackfillLeads extends BaseCommand
{
    protected $group       = 'Instagram';

    protected $name        = 'instagram:backfill-leads';

    protected $usage       = 'instagram:backfill-leads [--limit=N] [--dry-run]';

    protected $description = 'Vuelve a pedir el nombre de Instagram de los leads que quedaron como Instagram User.';

    protected $options     = [
        '--limit'   => 'Máximo de leads (default 40).',
        '--dry-run' => 'Muestra el resultado sin escribir.',
        '--photos'  => 'Solo descarga la foto de leads que ya tienen nombre.',
    ];

    public function run(array $params)
    {
        $limit  = (int) (CLI::getOption('limit') ?? ($params['limit'] ?? 40));
        $dryRun = CLI::getOption('dry-run') !== null || array_key_exists('dry-run', $params);
        if ($limit < 1) {
            $limit = 40;
        }
        if ($limit > 500) {
            $limit = 500;
        }

        $db = Database::connect();
        if (CLI::getOption('photos') !== null || array_key_exists('photos', $params)) {
            return $this->fetchPhotos($db, $limit, $dryRun);
        }
        $rows = $db->query(
            'SELECT l.id, l.name, c.external_id, c.recipient_ig_id, c.id AS conversation_id
             FROM leads l
             INNER JOIN conversations c ON c.id = (
                 SELECT c2.id FROM conversations c2
                 WHERE c2.lead_id = l.id AND c2.channel = ?
                 ORDER BY c2.last_message_at DESC, c2.id DESC
                 LIMIT 1
             )
             WHERE (l.name LIKE ? OR l.name = ? OR l.name IS NULL)
               AND (l.resolution_status IS NULL OR l.resolution_status IN (?, ?))
             ORDER BY c.last_message_at DESC
             LIMIT ' . $limit,
            ['instagram', 'Instagram User %', '', 'pending', 'failed']
        )->getResultArray();

        if ($rows === []) {
            CLI::write('No hay leads pendientes de nombre.', 'yellow');

            return 0;
        }

        $resolved = 0;
        $failed   = 0;
        $streak   = 0;

        foreach ($rows as $row) {
            $leadId      = (int) ($row['id'] ?? 0);
            $externalId  = (string) ($row['external_id'] ?? '');
            $recipientId = (string) ($row['recipient_ig_id'] ?? '');
            $currentName = (string) ($row['name'] ?? '');

            if ($leadId < 1 || $externalId === '' || $recipientId === '') {
                $failed++;
                continue;
            }

            $profile = MetaInstagramGraph::resolveParticipantProfile($externalId, $recipientId);
            if ($profile === null) {
                $failed++;
                $streak++;
                if ($streak >= 5) {
                    CLI::write('Meta limitó las consultas. Se detiene para no marcar mal los demás leads.', 'red');
                    break;
                }
                if (! $dryRun) {
                    $db->table('leads')->update(
                        [
                            'resolution_status'  => 'failed',
                            'last_resolution_at' => date('Y-m-d H:i:s'),
                        ],
                        ['id' => $leadId]
                    );
                }
                CLI::write("sin nombre lead {$leadId}", 'yellow');
                usleep(200000);
                continue;
            }

            $person = trim((string) ($profile['name'] ?? ''));
            $user   = trim((string) ($profile['username'] ?? ''));
            $handle = $user !== '' ? ('@' . ltrim($user, '@')) : '';
            $display = $person !== '' ? $person : $handle;

            if ($display === '') {
                $failed++;
                continue;
            }

            $streak = 0;
            $resolved++;
            CLI::write("lead {$leadId}: {$display}");
            if ($dryRun) {
                continue;
            }

            $patch = [
                'instagram_full_name' => $display,
                'last_resolution_at'  => date('Y-m-d H:i:s'),
                'resolution_status'   => 'resolved',
                'profile_pic'         => MetaInstagramGraph::cacheParticipantPhoto($leadId, $profile['profile_pic_url'] ?? null),
                'followers'           => $profile['followers_count'] ?? 0,
                'is_private'          => ! empty($profile['is_private']) ? 1 : 0,
            ];
            if ($handle !== '') {
                $patch['instagram_username'] = $handle;
            }
            if ($currentName === '' || str_starts_with($currentName, 'Instagram User ')) {
                $patch['name'] = $display;
            }
            $db->table('leads')->update($patch, ['id' => $leadId]);

            if ($handle !== '') {
                $db->table('conversations')->update(
                    ['external_username' => $handle],
                    ['id' => (int) $row['conversation_id'], 'external_username' => '']
                );
            }

            usleep(200000);
        }

        CLI::write("Con nombre: {$resolved}. Siguen sin nombre: {$failed}.", 'green');

        return 0;
    }


    private function fetchPhotos($db, int $limit, bool $dryRun): int
    {
        $rows = $db->query(
            'SELECT l.id, c.external_id, c.recipient_ig_id
             FROM leads l
             INNER JOIN conversations c ON c.id = (
                 SELECT c2.id FROM conversations c2
                 WHERE c2.lead_id = l.id AND c2.channel = ?
                 ORDER BY c2.last_message_at DESC, c2.id DESC
                 LIMIT 1
             )
             WHERE (l.profile_pic IS NULL OR l.profile_pic = ?)
               AND l.name NOT LIKE ?
               AND l.resolution_status = ?
             ORDER BY c.last_message_at DESC
             LIMIT ' . $limit,
            ['instagram', '', 'Instagram User %', 'resolved']
        )->getResultArray();

        if ($rows === []) {
            CLI::write('No hay leads con nombre pendientes de foto.', 'yellow');

            return 0;
        }

        $saved = 0;
        $miss  = 0;
        $streak = 0;
        foreach ($rows as $row) {
            $leadId = (int) ($row['id'] ?? 0);
            $externalId = (string) ($row['external_id'] ?? '');
            $recipientId = (string) ($row['recipient_ig_id'] ?? '');
            if ($leadId < 1 || $externalId === '' || $recipientId === '') {
                $miss++;
                continue;
            }
            $profile = MetaInstagramGraph::resolveParticipantProfile($externalId, $recipientId);
            if ($profile === null) {
                $miss++;
                $streak++;
                CLI::write("sin perfil lead {$leadId}", 'yellow');
                if ($streak >= 5) {
                    CLI::write('Meta limitó las consultas. Se detiene.', 'red');
                    break;
                }
                usleep(300000);
                continue;
            }
            $streak = 0;
            $local = $dryRun ? ($profile['profile_pic_url'] ?? null) : MetaInstagramGraph::cacheParticipantPhoto($leadId, $profile['profile_pic_url'] ?? null);
            if ($local === null || $local === '') {
                $miss++;
                CLI::write("sin foto lead {$leadId}", 'yellow');
                usleep(300000);
                continue;
            }
            $saved++;
            CLI::write("foto lead {$leadId}");
            if (! $dryRun) {
                $db->table('leads')->update([
                    'profile_pic' => $local,
                    'followers'   => $profile['followers_count'] ?? 0,
                ], ['id' => $leadId]);
            }
            usleep(300000);
        }

        CLI::write("Fotos guardadas: {$saved}. Sin foto: {$miss}.", 'green');

        return 0;
    }

}
