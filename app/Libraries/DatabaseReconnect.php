<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Database;

/**
 * Ensures a live MySQL connection before long-running queue workers process a message.
 */
class DatabaseReconnect
{
    public static function ensureLive(): void
    {
        $db = Database::connect();

        try {
            $db->query('SELECT 1');
        } catch (\Throwable $e) {
            log_message('warning', 'DatabaseReconnect: reviving connection — ' . $e->getMessage());
            if (method_exists($db, 'reconnect')) {
                $db->reconnect();
            } else {
                $db->close();
                Database::connect(null, false);
            }
        }
    }
}
