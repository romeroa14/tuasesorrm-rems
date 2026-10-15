<?php

declare(strict_types=1);

namespace App\Libraries;

class InstagramReplyChunks
{
    public const MAX_LENGTH = 1000;
    public const MAX_CHUNKS = 3;

    /**
     * @return list<string>
     */
    public static function split(string $text): array
    {
        $remaining = trim($text);
        if ($remaining === '') {
            return [];
        }

        $chunks = [];
        while ($remaining !== '' && count($chunks) < self::MAX_CHUNKS) {
            $length = self::length($remaining);
            if ($length <= self::MAX_LENGTH) {
                $chunks[] = $remaining;
                break;
            }
            $window = self::cut($remaining, self::MAX_LENGTH);
            $boundary = self::boundary($window);
            $chunk = self::cut($remaining, $boundary);
            if ($chunk === '') {
                $chunk = $window;
                $boundary = self::MAX_LENGTH;
            }
            $chunks[] = $chunk;
            $remaining = ltrim(self::after($remaining, $boundary));
        }

        return $chunks;
    }

    /**
     * Send chunks in order. A failed chunk stops the rest and does not retry earlier chunks.
     *
     * @param callable(string): array<string, mixed> $send
     * @return list<array{chunk: string, result: array<string, mixed>}>
     */
    public static function sendUntilFailure(string $text, callable $send): array
    {
        $sent = [];
        foreach (self::split($text) as $chunk) {
            try {
                $result = $send($chunk);
            } catch (\Throwable $e) {
                log_message('error', 'InstagramReplyChunks send ' . $e->getMessage());
                break;
            }
            if (! is_array($result) || empty($result['ok'])) {
                break;
            }
            $sent[] = [
                'chunk' => $chunk,
                'result' => $result,
            ];
        }

        return $sent;
    }

    private static function boundary(string $window): int
    {
        $length = self::length($window);
        $start = max(0, $length - 200);
        $tail = self::after($window, $start);
        $paragraph = self::lastPos($tail, "\n\n");
        if ($paragraph !== null && $paragraph > 0) {
            return $start + $paragraph;
        }
        $newline = self::lastPos($tail, "\n");
        if ($newline !== null && $newline > 0) {
            return $start + $newline;
        }

        return $length;
    }

    private static function length(string $text): int
    {
        return function_exists('mb_strlen') ? mb_strlen($text) : strlen($text);
    }

    private static function cut(string $text, int $length): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($text, 0, $length);
        }

        return substr($text, 0, $length);
    }

    private static function after(string $text, int $start): string
    {
        if (function_exists('mb_substr')) {
            return mb_substr($text, $start);
        }

        return substr($text, $start);
    }

    private static function lastPos(string $haystack, string $needle): ?int
    {
        if (function_exists('mb_strrpos')) {
            $pos = mb_strrpos($haystack, $needle);
        } else {
            $pos = strrpos($haystack, $needle);
        }

        return $pos === false ? null : (int) $pos;
    }
}
