<?php

declare(strict_types=1);

namespace App\Commands;

use App\Libraries\CrmPipelineEnrollment;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Da de alta en assignedclients los leads que ya tienen conversación Instagram pero no están en el Pipeline.
 *
 * Uso: php spark crm:enroll-instagram-pipeline
 */

class EnrollInstagramPipeline extends BaseCommand
{
    protected $group       = 'CRM';

    protected $name        = 'crm:enroll-instagram-pipeline';

    protected $usage       = 'crm:enroll-instagram-pipeline';

    protected $description = 'Backfill Pipeline para leads IG sin fila en assignedclients';

    public function run(array $params)
    {
        $db = Database::connect();

        $rows = $db->query(
            'SELECT DISTINCT c.lead_id AS lid
             FROM conversations c
             WHERE c.channel = ?
             AND NOT EXISTS (
                 SELECT 1 FROM assignedclients ac WHERE ac.lead_id = c.lead_id
             )',
            ['instagram']
        )->getResultArray();

        $n = 0;
        foreach ($rows as $row) {
            $lid = (int) ($row['lid'] ?? 0);
            if ($lid < 1) {
                continue;
            }
            CrmPipelineEnrollment::ensureLeadOnPipeline($lid);
            $n++;
        }

        CLI::write('Leads enrollados en el pipeline (sin fila previa): ' . $n, 'green');

        return 0;
    }
}
