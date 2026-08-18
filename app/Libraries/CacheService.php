<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\Cache as CacheConfig;

class CacheService
{
    private static function normalizeKey(string $key): string
    {
        $reserved = (string) (config(CacheConfig::class)->reservedCharacters ?? '{}()/\\@:');
        if ($reserved === '') {
            return $key;
        }

        $replacements = [];
        foreach (str_split($reserved) as $char) {
            $replacements[$char] = '_';
        }

        return strtr($key, $replacements);
    }

    /**
     * @template T
     *
     * @param callable(): T $callback
     *
     * @return T
     */
    public static function remember(string $key, int $ttl, callable $callback)
    {
        $key = self::normalizeKey($key);
        $cache = cache();
        $cached = $cache->get($key);

        if ($cached !== null) {
            return $cached;
        }

        $value = $callback();
        $cache->save($key, $value, $ttl);

        return $value;
    }

    public static function bust(string $prefix): void
    {
        $prefix = self::normalizeKey($prefix);
        $cache = cache();
        $cache->delete($prefix);

        $pattern = $prefix . '*';
        $cachePrefix = (string) (config(CacheConfig::class)->prefix ?? '');

        if (self::deleteRedisKeys($cachePrefix . $pattern)) {
            return;
        }

        if (self::deleteRedisKeys($pattern)) {
            return;
        }

        self::deleteFileCacheByPrefix($pattern);
    }

    private static function deleteRedisKeys(string $pattern): bool
    {
        $redis = self::redisConnection();
        if ($redis === null) {
            return false;
        }

        $iterator = null;
        $deleted = false;

        do {
            $keys = $redis->scan($iterator, $pattern, 100);
            if ($keys === false) {
                break;
            }

            foreach ($keys as $key) {
                $redis->del($key);
                $deleted = true;
            }
        } while ($iterator !== 0 && $iterator !== null);

        if (! $deleted) {
            $keys = $redis->keys($pattern);
            if (is_array($keys)) {
                foreach ($keys as $key) {
                    $redis->del($key);
                    $deleted = true;
                }
            }
        }

        return $deleted;
    }

    private static function redisConnection(): ?\Redis
    {
        if (! class_exists(\Redis::class)) {
            return null;
        }

        try {
            $config = config(CacheConfig::class)->redis;
            $redis = new \Redis();
            $host = (string) ($config['host'] ?? '127.0.0.1');
            $port = (int) ($config['port'] ?? 6379);

            if (! $redis->connect($host, $port, 1.5)) {
                return null;
            }

            $password = $config['password'] ?? null;
            if (is_string($password) && $password !== '') {
                $redis->auth($password);
            }

            if (isset($config['database'])) {
                $redis->select((int) $config['database']);
            }

            return $redis;
        } catch (\Throwable) {
            return null;
        }
    }

    private static function deleteFileCacheByPrefix(string $pattern): void
    {
        $storePath = WRITEPATH . 'cache/';
        if (! is_dir($storePath)) {
            return;
        }

        $needle = rtrim(str_replace('*', '', $pattern), '_');
        if ($needle === '') {
            return;
        }

        foreach (glob($storePath . '*') ?: [] as $file) {
            $contents = @file_get_contents($file);
            if ($contents !== false && str_contains($contents, $needle)) {
                @unlink($file);
            }
        }
    }
}
