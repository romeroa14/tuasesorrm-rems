<?php

namespace App\Libraries;

/**
 * Pure validation for actions the agent proposes. Authoritative at approval time:
 * the Python side only stores the raw proposal.
 */
class AiPendingActionRules
{
    public const ACTION_PHONE = 'registrar_telefono';
    public const ACTION_VISIT = 'crear_visita';

    /** Venezuelan mobile in 58412XXXXXXX form, or null when it does not look like one. */
    public static function normalizePhone(string $raw): ?string
    {
        $digits = ltrim(preg_replace('/\D/', '', $raw) ?? '', '0');

        if (strlen($digits) === 10 && $digits[0] === '4') {
            return '58' . $digits;
        }
        if (strlen($digits) === 12 && str_starts_with($digits, '58') && $digits[2] === '4') {
            return $digits;
        }

        return null;
    }

    /**
     * @param array<string, mixed> $payload date_at, property_label?, notes?
     *
     * @return array{ok: bool, date_at?: string, description?: string, error?: string}
     */
    public static function buildVisit(array $payload, string $leadName, int $now): array
    {
        $ts = strtotime(trim((string) ($payload['date_at'] ?? '')));
        if ($ts === false) {
            return ['ok' => false, 'error' => 'Fecha de visita inválida.'];
        }
        if ($ts <= $now) {
            return ['ok' => false, 'error' => 'La fecha de la visita ya pasó.'];
        }

        $parts = ['Visita con ' . trim($leadName)];
        foreach (['property_label' => 'Inmueble', 'notes' => 'Notas'] as $key => $label) {
            $value = trim((string) ($payload[$key] ?? ''));
            if ($value !== '') {
                $parts[] = $label . ': ' . $value;
            }
        }

        return ['ok' => true, 'date_at' => date('Y-m-d H:i:s', $ts), 'description' => implode(' · ', $parts)];
    }
}
