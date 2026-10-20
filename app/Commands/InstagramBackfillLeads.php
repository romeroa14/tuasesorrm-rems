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

    protected $description = 'Backfill Instagram profile enrichment for leads with pending/failed resolution.';

    protected $options     = [
        '--limit'   => 'Max leads to process (default: all)',
        '--dry-run' => 'Show what would be updated without writing to DB',
    ];

    public function run(array $params)
    {
        $limit   = (int) ($params['limit'] ?? 0);
        $dryRun  = array_key_exists('dry-run', $params) || in_array('--dry-run', $params);

        $db = Database::connect();

        $rows = $db->query(
            'SELECT l.id, l.name, l.instagram_username, c.external_id, c.recipient_ig_id
             FROM leads l
             JOIN conversations c ON c.lead_id = l.id AND c.channel = ?
             WHERE (l.resolution_status IS NULL OR l.resolution_status IN (?, ?))
             ' . ($limit > 0 ? 'LIMIT ' . (int) $limit : ''),
            ['instagram', 'pending', 'failed']
        )->getResultArray();

        if ($rows === []) {
            CLI::write('No leads to backfill.', 'yellow');

            return 0;
        }

        $resolved = 0;
        $failed   = 0;
        $skipped  = 0;

        foreach ($rows as $row) {
            $leadId     = (int) ($row['id'] ?? 0);
            $username   = (string) ($row['instagram_username'] ?? '');
            $externalId = (string) ($row['external_id'] ?? '');
            $recipient  = (string) ($row['recipient_ig_id'] ?? '');
            $leadName   = (string) ($row['name'] ?? '');

            if ($leadId < 1 || $externalId === '' || $recipient === '') {
                $skipped++;

                continue;
            }

            $profile = MetaInstagramGraph::resolveParticipantProfile($externalId, $recipient);
            if ($profile === null) {
                $failed++;

                if ($dryRun) {
                    CLI::write("[dry-run] Lead {$leadId} ({$username}): would mark as failed");
                } else {
                    $db->table('leads')->update(
                        [
                            'resolution_status'  => 'failed',
                            'last_resolution_at' => date('Y-m-d H:i:s'),
                        ],
                        ['id' => $leadId]
                    );
                }

                continue;
            }

            $resolved++;
            $name   = trim((string) ($profile['name'] ?? ''));
            $handle = trim((string) ($profile['username'] ?? ''));
            $handle = $handle !== '' ? '@' . ltrim($handle, '@') : '';

            $patch = [
                'instagram_full_name' => $name !== '' ? $name : ($handle !== '' ? $handle : null),
                'profile_pic'         => $profile['profile_pic_url'] ?? null,
                'followers'           => $profile['followers_count'] ?? 0,
                'is_private'          => $profile['is_private'] ? 1 : 0,
                'last_resolution_at'  => date('Y-m-d H:i:s'),
                'resolution_status'   => 'resolved',
            ];
            if ($handle !== '' && $username === '') {
                $patch['instagram_username'] = $handle;
            }
            // Only replace the auto-generated placeholder, never a name an advisor typed.
            if ($leadName === '' || str_starts_with($leadName, 'Instagram User ')) {
                $display = $name !== '' ? $name : $handle;
                if ($display !== '') {
                    $patch['name'] = $display;
                }
            }

            if ($dryRun) {
                CLI::write("[dry-run] Lead {$leadId}: would set name='" . ($patch['name'] ?? $leadName) . "', resolved");
            } else {
                $db->table('leads')->update($patch, ['id' => $leadId]);
            }

            if (! $dryRun) {
                sleep(1);
            }
        }

        CLI::write("Resolved: {$resolved}, Failed: {$failed}, Skipped: {$skipped}", 'green');

        return 0;
    }
}
