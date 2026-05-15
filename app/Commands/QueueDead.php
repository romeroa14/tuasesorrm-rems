<?php

namespace App\Commands;

use App\Libraries\RedisQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class QueueDead extends BaseCommand
{
    protected $group       = 'Queue';
    protected $name        = 'queue:dead';
    protected $description = 'Inspect and retry dead letter queue.';

    public function run(array $params): void
    {
        $queue = new RedisQueue();

        if (!$queue->isAvailable()) {
            CLI::error('Redis no disponible.');
            return;
        }

        $status = $queue->status();

        CLI::write("=== Dead Letter Queue ===", 'yellow');
        CLI::write("Inbound: {$status['inbound']}");
        CLI::write("Dead:    {$status['dead']}");

        if ($status['dead'] === 0) {
            CLI::write('No hay mensajes en dead letter.', 'green');
            return;
        }

        if (CLI::getOption('retry') || in_array('--retry', $params)) {
            CLI::write('Reintentando mensajes...', 'yellow');
            $count = $queue->retryDeadLetter();
            CLI::write("{$count} mensajes movidos a inbound.", 'green');
        } else {
            CLI::write("Usa --retry para reintentar los mensajes en dead letter.", 'blue');
        }
    }
}
