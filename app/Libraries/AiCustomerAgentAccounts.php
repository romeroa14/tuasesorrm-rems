<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\AiCustomerAgent;

class AiCustomerAgentAccounts
{
    /**
     * Usernames from META_IG_RECIPIENT_USERNAMES_JSON only.
     *
     * @return list<array{recipient_ig_id: string, username: string}>
     */
    public static function list(): array
    {
        $accounts = [];
        foreach (self::map() as $id => $username) {
            $accounts[] = [
                'recipient_ig_id' => $id,
                'username' => $username,
            ];
        }

        return $accounts;
    }

    /**
     * @return array{recipient_ig_id: string, username: string}|null
     */
    public static function find(string $username): ?array
    {
        $wanted = AiCustomerAgent::normalizeUsername($username);
        if ($wanted === '') {
            return null;
        }
        foreach (self::map() as $id => $known) {
            if ($known === $wanted) {
                return [
                    'recipient_ig_id' => $id,
                    'username' => $known,
                ];
            }
        }

        return null;
    }

    public static function usernameFor(string $recipientIgId): ?string
    {
        if ($recipientIgId === '') {
            return null;
        }
        $map = self::map();

        return $map[$recipientIgId] ?? null;
    }

    /**
     * @return array<string, string>
     */
    private static function map(): array
    {
        $json = getenv('META_IG_RECIPIENT_USERNAMES_JSON');
        if ($json === false || $json === '') {
            return [];
        }
        $decoded = json_decode($json, true);
        if (! is_array($decoded)) {
            return [];
        }
        $map = [];
        foreach ($decoded as $id => $username) {
            $id = (string) $id;
            $name = AiCustomerAgent::normalizeUsername((string) $username);
            if ($id !== '' && $name !== '') {
                $map[$id] = $name;
            }
        }

        return $map;
    }
}
