<?php

declare(strict_types=1);

namespace App\Models;

use CodeIgniter\Model;

class AiCustomerAgent extends Model
{
    protected $table = 'ai_customer_agents';
    protected $primaryKey = 'id';
    protected $returnType = 'array';
    protected $useTimestamps = false;
    protected $allowedFields = [
        'soul',
        'enabled',
        'instagram_username',
        'recipient_ig_id',
        'updated_at',
    ];

    public static function normalizeUsername(string $username): string
    {
        $username = str_replace('@', '', $username);
        $username = preg_replace('/\s+/u', '', $username) ?? '';

        return strtolower($username);
    }

    public static function usernamesMatch(string $left, string $right): bool
    {
        $left = self::normalizeUsername($left);
        $right = self::normalizeUsername($right);

        return $left !== '' && $left === $right;
    }

    /**
     * @param array<string, mixed>|null $agent
     */
    public static function insertAutoReplyFlag(?array $agent, string $recipientUsername): int
    {
        if ($agent === null || (int) ($agent['enabled'] ?? 0) !== 1) {
            return 0;
        }
        if (! self::usernamesMatch((string) ($agent['instagram_username'] ?? ''), $recipientUsername)) {
            return 0;
        }

        return 1;
    }

    /**
     * @param array<string, mixed>|null $agent
     */
    public static function liveSendAllowed(?array $agent, string $recipientUsername, $aiAutoReply, $assignedId, string $inboundText): bool
    {
        if ($agent === null || (int) ($agent['enabled'] ?? 0) !== 1) {
            return false;
        }
        if (! self::usernamesMatch((string) ($agent['instagram_username'] ?? ''), $recipientUsername)) {
            return false;
        }
        if ((int) $aiAutoReply !== 1) {
            return false;
        }
        if ($assignedId !== null && $assignedId !== '' && (int) $assignedId !== 0) {
            return false;
        }

        return trim($inboundText) !== '';
    }

    /**
     * @return array<string, mixed>|null
     */
    public function row(): ?array
    {
        $row = $this->orderBy('id', 'ASC')->first();

        return is_array($row) ? $row : null;
    }

    /**
     * @return array<string, mixed>|null
     */
    public function saveConfiguration(string $soul, string $username, int $enabled, ?string $recipientIgId): ?array
    {
        $current = $this->row();
        if ($current === null) {
            return null;
        }

        $this->update((int) $current['id'], [
            'soul' => $soul,
            'enabled' => $enabled === 1 ? 1 : 0,
            'instagram_username' => self::normalizeUsername($username),
            'recipient_ig_id' => $recipientIgId,
            'updated_at' => date('Y-m-d H:i:s'),
        ]);

        return $this->row();
    }
}
