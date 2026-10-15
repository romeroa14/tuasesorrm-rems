<?php

declare(strict_types=1);

namespace App\Libraries;

class AtcAgentHandoff
{
    public const LABEL = 'Lo atiende el agente';

    public static function assignedEmpty($assignedId): bool
    {
        return $assignedId === null || $assignedId === '' || (int) $assignedId === 0;
    }

    /**
     * @return array{agent_name: ?string, phone_ready: bool, assigned_id: ?int}
     */
    public static function present(?string $humanName, $assignedId, $phone, bool $hasAiMessage): array
    {
        $empty = self::assignedEmpty($assignedId);
        $name = $humanName;
        if ($empty && $hasAiMessage) {
            $name = self::LABEL;
        }

        return [
            'agent_name' => $name,
            'phone_ready' => $empty && trim((string) $phone) !== '',
            'assigned_id' => $empty ? null : (int) $assignedId,
        ];
    }

    /**
     * A collected phone writes the lead phone only. It does not assign anyone.
     *
     * @return array<string, string>
     */
    public static function phoneWrite(?string $currentPhone, string $collected): array
    {
        if (trim((string) $currentPhone) !== '' || trim($collected) === '') {
            return [];
        }

        return ['phone' => trim($collected)];
    }
}
