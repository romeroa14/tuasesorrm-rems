<?php

namespace App\Commands;

use App\Libraries\RedisQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;

class QueueDead extends BaseCommand
{
    protected $group       = 'Queue';
    protected $name        = 'queue:dead';
    protected $description = 'Inspect, monitor and retry dead letter queue.';

    protected $options = [
        '--retry' => 'Move dead messages back to inbound (resets attempt counter)',
        '--check' => 'Exit code 1 if dead queue is non-empty (for cron/monitoring)',
        '--limit' => 'Max messages to retry in one run (default: all)',
    ];

    public function run(array $params): void
    {
        $queue = new RedisQueue();

        if (!$queue->isAvailable()) {
            CLI::error('Redis no disponible.');
            if (CLI::getOption('check')) {
                exit(2);
            }

            return;
        }

        $status = $queue->status();
        $dead = (int) ($status['dead'] ?? 0);
        $inbound = (int) ($status['inbound'] ?? 0);

        CLI::write('=== Webhook queue status ===', 'yellow');
        CLI::write("Inbound: {$inbound}");
        CLI::write("Dead:    {$dead}");

        if (CLI::getOption('check')) {
            if ($dead > 0) {
                CLI::write("ALERT: {$dead} mensajes en dead letter.", 'red');
                exit(1);
            }
            CLI::write('OK: dead letter vacía.', 'green');
            exit(0);
        }

        if ($dead === 0) {
            CLI::write('No hay mensajes en dead letter.', 'green');

            return;
        }

        if (CLI::getOption('retry') || in_array('--retry', $params)) {
            $limit = (int) (CLI::getOption('limit') ?: 0);
            CLI::write('Reintentando mensajes...', 'yellow');
            $count = $queue->retryDeadLetter($limit);
            CLI::write("{$count} mensajes movidos a inbound.", 'green');
            $after = $queue->status();
            CLI::write("Quedan — Inbound: {$after['inbound']} | Dead: {$after['dead']}", 'blue');
        } else {
            CLI::write('Usa --retry para reintentar. --check para monitoreo (exit 1 si dead > 0).', 'blue');
        }
    }
}
