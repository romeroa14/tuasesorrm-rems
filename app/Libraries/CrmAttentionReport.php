<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Attention audit of DM conversations per advisor, in two modes:
 *
 * - By conversation: advisors answer from the native Instagram app, so messages carry
 *   no advisor id. The advisor is read from how they sign or introduce themselves
 *   ("Valery Mendoza | Asesores RM", "Odra Cedeño del Equipo de Asesores RM",
 *   "por acá le habla Luis Rojas"). No fixed advisor list: any name is accepted.
 * - By CRM data: the participants are the CRM users who replied from the CRM.
 */
final class CrmAttentionReport
{
    private const NAME = '(\p{Lu}\p{Ll}+(?:[ \t]+\p{Lu}\p{Ll}+){0,2})';

    /** Capitalised words that are greetings or brand words, never part of a name. */
    private const STOP = [
        'Equipo', 'Soy', 'Hola', 'Buen', 'Buena', 'Buenas', 'Buenos', 'Noches', 'Tardes', 'Dias', 'Días', 'Día', 'Dia',
        'Mercedes', 'Asesores', 'Asesor', 'Asesora', 'Por', 'Le', 'Un', 'Una', 'Muy', 'Gracias', 'Saludos', 'Onyx',
        'Torre', 'Las', 'Los', 'De', 'Del', 'Usted', 'Señor', 'Señora', 'Sr', 'Sra', 'Rm', 'RM',
    ];

    /** @return list<string> */
    private static function patterns(): array
    {
        $n = self::NAME;

        return [
            '/' . $n . '\s*\|\s*(?i:asesores)/u',
            '/' . $n . '\s+(?i:del\s+equipo)/u',
            '/' . $n . '\s+(?i:de\s+(?:parte\s+de(?:l\s+equipo\s+de)?\s+)?asesores)/u',
            '/(?i:habla|soy|placer|ac[aá]|saluda|escribe|atiende|mi\s+nombre\s+es|me\s+llamo)\s+(?i:de\s+parte\s+de\s+)?' . $n . '/u',
        ];
    }

    /**
     * @param list<array{id: int, human_replied: bool, outbound_texts: list<string>}> $conversations
     *
     * @return array<string, mixed> summary plus 'conversations' => [id => list<advisor>]
     */
    public function summarizeConversations(array $conversations): array
    {
        // Pass 1: raw name hits per conversation, in reading order.
        $hits      = [];
        $fullNames = []; // first name => [full name => count]
        foreach ($conversations as $c) {
            $hits[$c['id']] = [];
            foreach ($c['outbound_texts'] as $text) {
                foreach (self::nameHits($text) as $tokens) {
                    $hits[$c['id']][] = $tokens;
                    if (count($tokens) >= 2) {
                        $full                       = $tokens[0] . ' ' . $tokens[1];
                        $fullNames[$tokens[0]][$full] = ($fullNames[$tokens[0]][$full] ?? 0) + 1;
                    } else {
                        $fullNames[$tokens[0]] ??= [];
                    }
                }
            }
        }

        // Pass 2: one canonical name per advisor (most used "First Last"). A reversed
        // duplicate ("Flores Alejandro" next to "Alejandro Flores") folds into the more used one.
        $counts = [];
        foreach ($fullNames as $fulls) {
            foreach ($fulls as $full => $n) {
                $counts[$full] = $n;
            }
        }
        $alias = [];
        foreach ($counts as $full => $n) {
            [$x, $y]  = explode(' ', $full);
            $reversed = $y . ' ' . $x;
            if (isset($counts[$reversed]) && $counts[$reversed] > $n) {
                $alias[$x] = $reversed;
            }
        }
        $canonical = [];
        $byLast    = [];
        foreach ($fullNames as $first => $fulls) {
            foreach (array_keys($fulls) as $full) {
                if (isset($alias[explode(' ', $full)[0]])) {
                    unset($fulls[$full]);
                }
            }
            arsort($fulls);
            if ($fulls !== []) {
                $canonical[$first]                         = (string) array_key_first($fulls);
                $byLast[explode(' ', $canonical[$first])[1]] = $canonical[$first];
            }
        }

        $participants = [];
        foreach ($conversations as $c) {
            $order = [];
            foreach ($hits[$c['id']] as $tokens) {
                $name = $alias[$tokens[0]] ?? $canonical[$tokens[0]] ?? $byLast[$tokens[0]] ?? $tokens[0];
                if (! in_array($name, $order, true)) {
                    $order[] = $name;
                }
            }
            $participants[] = ['id' => $c['id'], 'human_replied' => $c['human_replied'], 'participants' => $order];
        }

        return $this->summarizeParticipants($participants);
    }

    /**
     * @param list<array{id: int, human_replied: bool, participants: list<string>}> $conversations
     *
     * @return array<string, mixed>
     */
    public function summarizeParticipants(array $conversations): array
    {
        $advisors = [];
        $byConv   = [];
        $attended = $shared = $unidentified = 0;

        foreach ($conversations as $c) {
            $order               = array_values(array_unique($c['participants']));
            $byConv[$c['id']]    = $order;
            if ($order === []) {
                $unidentified += $c['human_replied'] ? 1 : 0;
                continue;
            }

            $attended++;
            $isShared = count($order) > 1;
            $shared += $isShared ? 1 : 0;
            foreach ($order as $i => $name) {
                $advisors[$name] ??= ['total' => 0, 'first' => 0, 'exclusive' => 0, 'shared' => 0];
                $advisors[$name]['total']++;
                $advisors[$name]['first'] += $i === 0 ? 1 : 0;
                $advisors[$name][$isShared ? 'shared' : 'exclusive']++;
            }
        }

        $rows = [];
        foreach ($advisors as $name => $row) {
            $rows[] = ['advisor' => $name] + $row + ['share_pct' => round($row['total'] / $attended * 100, 1)];
        }
        usort($rows, static fn (array $a, array $b): int => [$b['total'], $a['advisor']] <=> [$a['total'], $b['advisor']]);

        return [
            'total'              => count($conversations),
            'attended'           => $attended,
            'unattended'         => count($conversations) - $attended,
            'shared'             => $shared,
            'unidentified_human' => $unidentified,
            'advisors'           => $rows,
            'conversations'      => $byConv,
        ];
    }

    /**
     * @return list<list<string>> name token lists (max two tokens) in order of appearance
     */
    private static function nameHits(string $text): array
    {
        $found = [];
        foreach (self::patterns() as $pattern) {
            if (preg_match_all($pattern, $text, $m, PREG_OFFSET_CAPTURE) > 0) {
                foreach ($m[1] as [$name, $offset]) {
                    $tokens = array_values(array_filter(
                        preg_split('/[ \t]+/u', $name),
                        static fn (string $t): bool => ! in_array($t, self::STOP, true)
                    ));
                    if ($tokens !== []) {
                        $found[$offset] = array_slice($tokens, 0, 2);
                    }
                }
            }
        }
        ksort($found);

        return array_values($found);
    }
}
