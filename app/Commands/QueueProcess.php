<?php

namespace App\Commands;

use App\Controllers\WebhookController;
use App\Libraries\DatabaseReconnect;
use App\Libraries\RedisQueue;
use CodeIgniter\CLI\BaseCommand;
use CodeIgniter\CLI\CLI;
use Config\Services;

class QueueProcess extends BaseCommand
{
    protected $group       = 'Queue';
    protected $name        = 'queue:process';
    protected $description = 'Process webhook messages from Redis queue.';

    public function run(array $params): void
    {
        $once   = CLI::getOption('once')   || in_array('--once', $params);
        $limit  = (int) (CLI::getOption('limit') ?: 0);
        $dead   = CLI::getOption('dead')   || in_array('--dead', $params);
        $retry  = CLI::getOption('retry')  || in_array('--retry', $params);

        $queue = new RedisQueue();

        if (!$queue->isAvailable()) {
            CLI::error('Redis no disponible.');
            return;
        }

        // Dead letter retry mode
        if ($dead || $retry) {
            CLI::write('Reintentando mensajes de webhook:dead...', 'yellow');
            $count = $queue->retryDeadLetter();
            CLI::write("{$count} mensajes movidos de dead a inbound.", 'green');
            if ($once) {
                return;
            }
        }

        CLI::write('Worker iniciado. Esperando mensajes...', 'green');
        $processed = 0;

        while (true) {
            $payload = $queue->dequeue(5); // timeout 5s, returns null if empty

            if ($payload === null) {
                if ($once || ($limit > 0 && $processed >= $limit)) {
                    break;
                }
                continue;
            }

            $messageId = $payload['message_id'] ?? 'unknown';
            $senderId  = $payload['sender_id'] ?? 'unknown';
            unset($payload['attempts']);

            try {
                DatabaseReconnect::ensureLive();

                // Fresh controller per message (prevents stale state across long-lived workers)
                $request  = Services::request();
                $response = Services::response();
                $logger   = Services::logger();
                $controller = new WebhookController();
                $controller->initController($request, $response, $logger);

                $controller->processIncomingMessage(
                    $payload['channel'] ?? 'instagram',
                    $senderId,
                    $payload['message_text'] ?? '',
                    $messageId,
                    $payload['content_type'] ?? 'text',
                    $payload['media_url'] ?? null,
                    $payload['timestamp'] ?? time(),
                    $payload['recipient_ig_id'] ?? '',
                    $payload['referral_source'] ?? '',
                    $payload['referral_ad_id'] ?? ''
                );

                CLI::write("✓ Procesado: {$senderId}", 'green');
            } catch (\Throwable $e) {
                $attempts = ($payload['attempts'] ?? 0) + 1;
                $payload['attempts'] = $attempts;
                CLI::error("✗ Error ({$attempts}/3): {$senderId} — {$e->getMessage()}");

                if ($attempts >= 3) {
                    $queue->deadLetter(json_encode($payload));
                    CLI::write("  → Enviado a dead letter queue.", 'red');
                } else {
                    $queue->enqueue($payload);
                }
            }

            $processed++;
            if ($once || ($limit > 0 && $processed >= $limit)) {
                break;
            }
        }

        $status = $queue->status();
        CLI::write("Procesados: {$processed} | Inbound: {$status['inbound']} | Dead: {$status['dead']}", 'blue');
    }
}
