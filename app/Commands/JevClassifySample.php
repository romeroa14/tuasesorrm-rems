<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\JevPipelineClassifier;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

class JevClassifySample extends BaseCommand
{
    protected $group = 'CRM';

    protected $name = 'crm:jev-sample';

    protected $usage = 'crm:jev-sample';

    protected $description = 'Clasifica cinco leads de ejemplo con Jev y ubica solo los que superan la confianza.';

    public function run(array $params)
    {
        $db = Database::connect();
        $used = [];
        $samples = [];
        $patterns = [
            'visita' => "content LIKE '%visita%' OR content LIKE '%agendar%' OR content LIKE '%cita%'",
            'precio' => "content LIKE '%precio%' OR content LIKE '%cuánto%' OR content LIKE '%cuanto%' OR content LIKE '%costo%'",
            'rechazo' => "content LIKE '%no me interesa%' OR content LIKE '%no gracias%' OR content LIKE '%no estoy interesado%'",
            'inmueble' => "content LIKE '%apartamento%' OR content LIKE '%habitacion%' OR content LIKE '%habitación%' OR content LIKE '%metros%'",
            'asesor' => "content LIKE '%asesor%' OR content LIKE '%inmobiliaria%' OR content LIKE '%colega%'",
        ];

        foreach ($patterns as $label => $cond) {
            $exclude = $used === [] ? '1=1' : 'l.id NOT IN (' . implode(',', array_map('intval', $used)) . ')';
            $row = $db->query(
                "SELECT l.id AS lead_id, c.id AS conversation_id, l.name AS lead_name,
                    (SELECT m.content FROM messages m
                     WHERE m.conversation_id = c.id AND m.direction = 'inbound' AND ({$cond})
                     ORDER BY m.id DESC LIMIT 1) AS snippet
                 FROM leads l
                 JOIN conversations c ON c.lead_id = l.id AND c.channel = 'instagram'
                 LEFT JOIN assignedclients ac ON ac.lead_id = l.id
                 WHERE ac.id IS NULL
                   AND (l.pipeline_status_id IS NULL OR l.pipeline_status_id = 0)
                   AND {$exclude}
                   AND EXISTS (
                       SELECT 1 FROM messages m
                       WHERE m.conversation_id = c.id AND m.direction = 'inbound' AND ({$cond})
                   )
                 ORDER BY c.last_message_at DESC
                 LIMIT 1"
            )->getRowArray();
            if ($row) {
                $row['label'] = $label;
                $samples[] = $row;
                $used[] = (int) $row['lead_id'];
            }
        }

        if ($samples === []) {
            CLI::write('No hay leads sin agente con esos ejemplos.', 'yellow');

            return;
        }

        $classifier = new JevPipelineClassifier();
        foreach ($samples as $sample) {
            $report = $classifier->report((int) $sample['lead_id'], (int) $sample['conversation_id'], true);
            $snippet = preg_replace('/\d{6,}/', '######', (string) $sample['snippet']) ?? '';
            $snippet = trim(preg_replace('/\s+/', ' ', $snippet) ?? '');
            if (strlen($snippet) > 90) {
                $snippet = substr($snippet, 0, 90) . '…';
            }
            $applied = $report['applied'] ? 'ubicado' : 'sin mover';
            CLI::write(sprintf(
                '%s | lead %d | %s | %s | confianza %.2f | %s | %s',
                $sample['label'],
                (int) $sample['lead_id'],
                (string) $sample['lead_name'],
                $report['status_name'] !== '' ? $report['status_name'] : '—',
                (float) $report['confidence'],
                $applied,
                $snippet
            ));
        }
    }
}
