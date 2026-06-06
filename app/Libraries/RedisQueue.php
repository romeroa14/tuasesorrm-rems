<?php

namespace App\Libraries;

class RedisQueue
{
    private ?\Predis\Client $client = null;
    private bool $available = false;

    private const QUEUE_INBOUND  = 'webhook:inbound';
    private const QUEUE_DEAD     = 'webhook:dead';

    public function __construct()
    {
        try {
            $host     = getenv('REDIS_HOST') ?: 'rems-redis';
            $port     = getenv('REDIS_PORT') ?: '6379';
            $password = getenv('REDIS_PASSWORD') ?: null;
            $database = getenv('REDIS_DATABASE') ?: '0';

            $params = [
                'scheme'   => 'tcp',
                'host'     => $host,
                'port'     => (int) $port,
                'timeout'  => 2.0,
                'database' => (int) $database,
            ];
            if (is_string($password) && $password !== '') {
                $params['password'] = $password;
            }

            $this->client = new \Predis\Client($params);
            $this->client->connect();
            $this->available = true;
        } catch (\Throwable $e) {
            log_message('error', 'RedisQueue: connection failed — ' . $e->getMessage());
            $this->available = false;
        }
    }

    public function isAvailable(): bool
    {
        return $this->available;
    }

    public function enqueue(array $payload): bool
    {
        if (!$this->available) {
            return false;
        }

        try {
            $json = json_encode($payload, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
            $this->client->lpush(self::QUEUE_INBOUND, [$json]);
            return true;
        } catch (\Throwable $e) {
            log_message('error', 'RedisQueue::enqueue failed — ' . $e->getMessage());
            $this->available = false;
            return false;
        }
    }

    public function dequeue(int $timeout = 0): ?array
    {
        if (!$this->available) {
            return null;
        }

        try {
            $result = $this->client->blpop([self::QUEUE_INBOUND], $timeout);
            if ($result === null) {
                return null;
            }
            // blpop returns [key, value]
            $payload = json_decode($result[1], true);
            if (!is_array($payload)) {
                return null;
            }
            return $payload;
        } catch (\Throwable $e) {
            log_message('error', 'RedisQueue::dequeue failed — ' . $e->getMessage());
            return null;
        }
    }

    public function deadLetter(string $payload): void
    {
        if (!$this->available) {
            return;
        }
        try {
            $this->client->rpush(self::QUEUE_DEAD, [$payload]);
        } catch (\Throwable $e) {
            log_message('error', 'RedisQueue::deadLetter failed — ' . $e->getMessage());
        }
    }

    public function retryDeadLetter(): int
    {
        if (!$this->available) {
            return 0;
        }
        $count = 0;
        try {
            while (($item = $this->client->lpop(self::QUEUE_DEAD)) !== null) {
                $this->client->lpush(self::QUEUE_INBOUND, [$item]);
                $count++;
            }
        } catch (\Throwable $e) {
            log_message('error', 'RedisQueue::retryDeadLetter failed — ' . $e->getMessage());
        }
        return $count;
    }

    public function status(): array
    {
        if (!$this->available) {
            return [
                'available' => false,
                'inbound'   => 0,
                'dead'      => 0,
            ];
        }
        try {
            return [
                'available' => true,
                'inbound'   => $this->client->llen(self::QUEUE_INBOUND),
                'dead'      => $this->client->llen(self::QUEUE_DEAD),
            ];
        } catch (\Throwable $e) {
            return [
                'available' => false,
                'inbound'   => 0,
                'dead'      => 0,
            ];
        }
    }
}
