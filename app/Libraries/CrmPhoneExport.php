<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Builds the "phone numbers obtained" export: one row per number with who the
 * client is, who attended them and which account the chat came in through.
 */
final class CrmPhoneExport
{
    private const HEADER = ['Telefono', 'Nombre', 'Usuario Instagram', 'Atendido por', 'Cuenta', 'Fecha', 'Origen', 'Conversacion'];

    /**
     * @param list<array<string, mixed>>  $conversations rows with id, created_at, lead_name, lead_phone, instagram_username, external_username, recipient_ig_username
     * @param list<array<string, mixed>>  $inbound       client messages: conversation_id, content, created_at (in reading order)
     * @param array<int, list<string>>    $attribution   conversation id => advisors, from the selected report mode
     *
     * @return list<array<string, string>>
     */
    public static function rows(array $conversations, array $inbound, array $attribution): array
    {
        $byId  = array_column($conversations, null, 'id');
        $found = [];

        $add = static function (string $phone, array $c, string $date, string $source) use (&$found, $attribution): void {
            if (isset($found[$phone])) {
                return;
            }
            $handle  = trim((string) ($c['instagram_username'] ?? '')) ?: trim((string) ($c['external_username'] ?? ''));
            $name    = trim((string) ($c['lead_name'] ?? ''));
            $advisor = $attribution[(int) $c['id']] ?? [];

            $found[$phone] = [
                'phone'        => $phone,
                'name'         => ($name === '' || str_starts_with($name, 'Instagram User')) && $handle !== '' ? $handle : $name,
                'instagram'    => $handle,
                'attended_by'  => $advisor === [] ? 'Sin identificar' : implode(' / ', $advisor),
                'account'      => (string) ($c['recipient_ig_username'] ?? ''),
                'date'         => $date,
                'source'       => $source,
                'conversation' => (string) $c['id'],
            ];
        };

        foreach ($inbound as $m) {
            $c = $byId[$m['conversation_id']] ?? null;
            if ($c === null) {
                continue;
            }
            foreach (PhoneExtractor::extract((string) $m['content']) as $phone) {
                $add($phone, $c, (string) $m['created_at'], 'chat');
            }
        }
        foreach ($conversations as $c) {
            $raw = trim((string) ($c['lead_phone'] ?? ''));
            foreach ($raw === '' ? [] : PhoneExtractor::extract($raw) as $phone) {
                $add($phone, $c, (string) $c['created_at'], 'lead');
            }
        }

        return array_values($found);
    }

    /** @param list<array<string, string>> $rows */
    public static function csv(array $rows): string
    {
        $fh = fopen('php://temp', 'r+');
        fwrite($fh, "\xEF\xBB\xBF");
        fputcsv($fh, self::HEADER);
        foreach ($rows as $row) {
            fputcsv($fh, array_map([self::class, 'safe'], array_values($row)));
        }
        rewind($fh);
        $csv = (string) stream_get_contents($fh);
        fclose($fh);

        return $csv;
    }

    /** Neutralise spreadsheet formula injection. */
    private static function safe(string $value): string
    {
        return $value !== '' && strpbrk($value[0], "=+-@\t\r") !== false ? "'" . $value : $value;
    }
}
