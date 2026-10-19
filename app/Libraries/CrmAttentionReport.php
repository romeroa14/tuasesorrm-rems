<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Attention audit of DM conversations per advisor.
 *
 * Advisors answer from the native Instagram app, so messages carry no advisor id.
 * The advisor is identified from how they introduce themselves in outbound text
 * ("Por acá le habla Luis Rojas", "un placer Bárbara Migliore").
 */
final class CrmAttentionReport
{
    /** Words that, right before a name, mark a self-introduction (accent-free, lowercase). */
    private const INTRO_WORDS = 'habla|soy|saluda|atiende|atencion|placer|escribe|contacta|aca|asesor|asesora';

    /** @var array<string, string> advisor name => compiled regex */
    private array $patterns = [];

    /**
     * @param array<string, list<string>> $advisors advisor display name => aliases (full name, first name)
     */
    public function __construct(private readonly array $advisors)
    {
        foreach ($advisors as $name => $aliases) {
            $normalized = array_map([self::class, 'normalizeText'], $aliases);
            usort($normalized, static fn (string $a, string $b): int => strlen($b) <=> strlen($a));
            $alt = implode('|', array_map(static fn (string $a): string => preg_quote($a, '/'), $normalized));
            $this->patterns[$name] = '/\b(?:' . self::INTRO_WORDS . ')\s+(?:de\s+parte\s+de\s+)?(?:' . $alt . ')\b/u';
        }
    }

    /**
     * @return list<string> advisor names found in the text, ordered by position
     */
    public function advisorsIn(string $text): array
    {
        $haystack = self::normalizeText($text);
        $found    = [];
        foreach ($this->patterns as $name => $pattern) {
            if (preg_match($pattern, $haystack, $m, PREG_OFFSET_CAPTURE) === 1) {
                $found[$name] = $m[0][1];
            }
        }
        asort($found);

        return array_keys($found);
    }

    /**
     * @param list<array{id: int, human_replied: bool, outbound_texts: list<string>}> $conversations
     *
     * @return array{total: int, attended: int, unattended: int, shared: int, unidentified_human: int, advisors: array<string, array{total: int, first: int, exclusive: int, shared: int, share_pct: float}>}
     */
    public function summarize(array $conversations): array
    {
        $advisors = [];
        foreach (array_keys($this->advisors) as $name) {
            $advisors[$name] = ['total' => 0, 'first' => 0, 'exclusive' => 0, 'shared' => 0, 'share_pct' => 0.0];
        }

        $attended = $shared = $unidentified = 0;

        foreach ($conversations as $conversation) {
            $order = [];
            foreach ($conversation['outbound_texts'] as $text) {
                foreach ($this->advisorsIn($text) as $name) {
                    if (! in_array($name, $order, true)) {
                        $order[] = $name;
                    }
                }
            }

            if ($order === []) {
                if ($conversation['human_replied']) {
                    $unidentified++;
                }
                continue;
            }

            $attended++;
            $advisors[$order[0]]['first']++;
            $isShared = count($order) > 1;
            if ($isShared) {
                $shared++;
            }
            foreach ($order as $name) {
                $advisors[$name]['total']++;
                $advisors[$name][$isShared ? 'shared' : 'exclusive']++;
            }
        }

        foreach ($advisors as $name => $row) {
            $advisors[$name]['share_pct'] = $attended > 0 ? round($row['total'] / $attended * 100, 1) : 0.0;
        }

        return [
            'total'              => count($conversations),
            'attended'           => $attended,
            'unattended'         => count($conversations) - $attended,
            'shared'             => $shared,
            'unidentified_human' => $unidentified,
            'advisors'           => $advisors,
        ];
    }

    public static function normalizeText(string $text): string
    {
        $text = mb_strtolower($text, 'UTF-8');

        return strtr($text, [
            'á' => 'a', 'é' => 'e', 'í' => 'i', 'ó' => 'o', 'ú' => 'u', 'ü' => 'u', 'ñ' => 'n',
        ]);
    }
}
