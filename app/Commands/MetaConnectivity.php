<?php

namespace App\Commands;

use App\Libraries\MetaConnectivityReport;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

/**
 * Verificación rápida de integración Meta / Instagram (usa .env actual).
 *
 * Uso:
 *   php spark meta:connectivity
 *   php spark meta:connectivity --no-db
 */
class MetaConnectivity extends BaseCommand
{
    protected $group       = 'Meta';
    protected $name        = 'meta:connectivity';
    protected $usage       = 'meta:connectivity [--no-db]';
    protected $arguments   = [];
    protected $options     = [
        '--no-db' => 'No probar conexión MySQL',
    ];
    protected $description = 'Verifica variables Meta/Instagram y llamadas a Graph API';

    public function run(array $params)
    {
        // Spark puede no pasar flags en $params; leer también argv
        $skipDb = in_array('--no-db', $params, true)
            || (isset($_SERVER['argv']) && in_array('--no-db', $_SERVER['argv'], true));

        CLI::write(
            $skipDb ? 'Comprobaciones Meta / Instagram (sin MySQL)' : 'Comprobaciones Meta / Instagram / BD',
            'yellow'
        );
        CLI::newLine();

        $checks = MetaConnectivityReport::collect(! $skipDb);

        foreach ($checks as $row) {
            $mark = $row['ok'] ? '[OK]  ' : '[FAIL] ';
            CLI::write($mark . $row['key'], $row['ok'] ? 'green' : 'red');
            CLI::write('       ' . $row['detail']);
        }

        CLI::newLine();

        if (! MetaConnectivityReport::allOk($checks)) {
            CLI::error('Hay comprobaciones fallidas.');

            return 1;
        }

        CLI::write('Todas las comprobaciones pasaron.', 'green');

        return 0;
    }
}
