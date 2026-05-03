<?php

declare(strict_types=1);

namespace App\Commands;

use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Database;

/**
 * Elimina mensajes y conversaciones CRM cuya canal ≠ instagram ( irreversible ).
 *
 * Opcional `--purge-orphan-leads`: borra también registros en `leads` que no tengan
 * ninguna conversación Instagram DM (muy destructivo en BD con leads ATC legacy).
 *
 * Uso:
 *   php spark crm:cleanup-non-instagram --dry-run
 *   php spark crm:cleanup-non-instagram --yes
 *   php spark crm:cleanup-non-instagram --yes --purge-orphan-leads
 */
class CleanupNonInstagramCrm extends BaseCommand
{
    protected $group       = 'CRM';

    protected $name        = 'crm:cleanup-non-instagram';

    protected $usage       = 'crm:cleanup-non-instagram [--dry-run] [--yes] [--purge-orphan-leads]';

    protected $arguments   = [];

    protected $options     = [
        '--dry-run'            => 'Solo muestra conteos, no borra',
        '--yes'                => 'Confirma borrado de conversaciones/mensajes no Instagram',
        '--purge-orphan-leads' => 'Además borrar leads sin ningún DM Instagram (destructivo)',
    ];

    protected $description = 'Quita conversaciones CRM fuera de Instagram DM';

    public function run(array $params)
    {
        $dry = in_array('--dry-run', $params, true)
            || (isset($_SERVER['argv']) && in_array('--dry-run', $_SERVER['argv'], true));
        $yes = in_array('--yes', $params, true)
            || (isset($_SERVER['argv']) && in_array('--yes', $_SERVER['argv'], true));
        $purge = in_array('--purge-orphan-leads', $params, true)
            || (isset($_SERVER['argv']) && in_array('--purge-orphan-leads', $_SERVER['argv'], true));

        if (! $dry && ! $yes) {
            CLI::error('Esta operación borra datos. Usa --dry-run para simular o --yes para ejecutar.');

            return 1;
        }

        $db = Database::connect();

        $cntMsgs = (int) ($db->query(
            'SELECT COUNT(*) AS n FROM messages m
             INNER JOIN conversations c ON c.id = m.conversation_id
             WHERE c.channel <> ?',
            ['instagram']
        )->getRow()->n ?? 0);

        $cntConv = (int) ($db->query(
            'SELECT COUNT(*) AS n FROM conversations WHERE channel <> ?',
            ['instagram']
        )->getRow()->n ?? 0);

        $orphanSql = 'SELECT l.id FROM leads l
            WHERE NOT EXISTS (
                SELECT 1 FROM conversations c WHERE c.lead_id = l.id AND c.channel = ?
            )';

        $orphanIds = array_map(
            static fn ($row) => (int) $row['id'],
            $db->query($orphanSql, ['instagram'])->getResultArray()
        );
        $cntOrphans = count($orphanIds);

        CLI::write('Mensajes ligados a conversaciones NO Instagram: ' . $cntMsgs, 'cyan');
        CLI::write('Conversaciones NO Instagram: ' . $cntConv, 'cyan');
        CLI::write(
            'Leads sin DM Instagram (registro completo; borrar solo con --purge-orphan-leads): ' . $cntOrphans,
            'cyan'
        );

        if ($dry) {
            CLI::write('Dry-run: no se modificó la base de datos.', 'green');

            return 0;
        }

        $db->transStart();

        try {
            if ($cntMsgs > 0) {
                $db->query(
                    'DELETE m FROM messages m
                     INNER JOIN conversations c ON c.id = m.conversation_id
                     WHERE c.channel <> ?',
                    ['instagram']
                );
            }

            if ($cntConv > 0) {
                $db->query('DELETE FROM conversations WHERE channel <> ?', ['instagram']);
            }

            if ($purge && $cntOrphans > 0) {
                $ids = implode(',', array_map('intval', $orphanIds));
                $db->query('DELETE FROM intention_logs WHERE lead_id IN (' . $ids . ')');
                $db->query('DELETE FROM assignedclients WHERE lead_id IN (' . $ids . ')');
                $db->query('DELETE FROM leads WHERE id IN (' . $ids . ')');
            }

            if ($db->transComplete() === false) {
                CLI::error('Transacción revertida.');

                return 1;
            }
        } catch (\Throwable $e) {
            $db->transRollback();
            CLI::error($e->getMessage());

            return 1;
        }

        CLI::write('Conversaciones/mensajes fuera de Instagram eliminados.', 'green');
        if ($purge) {
            CLI::write('Paso opcional aplicado: leads sin DM Instagram eliminados del CRM.', 'green');
        } else {
            CLI::write(
                'No se borraron leads. Para borrar registros en leads sin ningún DM Instagram: --purge-orphan-leads',
                'yellow'
            );
        }

        return 0;
    }
}
