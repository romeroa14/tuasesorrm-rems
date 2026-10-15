<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\User;

/**
 * Acceso total a la plataforma (solo correos autorizados en PLATFORM_ADMIN_EMAILS).
 */
class PlatformAdmin
{
    /**
     * @return list<string>
     */
    public static function adminEmails(): array
    {
        $raw = self::env('PLATFORM_ADMIN_EMAILS')
            ?? 'superadmin@gmail.com,rossanamigliore@tuasesorrm.com.ve';

        $parts = preg_split('/[\s,;]+/', strtolower(trim($raw))) ?: [];

        return array_values(array_filter(array_unique($parts), static fn (string $e): bool => $e !== ''));
    }

    public static function isAdmin(?int $userId = null): bool
    {
        $email = strtolower(trim(self::emailForUser($userId)));

        return $email !== '' && in_array($email, self::adminEmails(), true);
    }

    public static function isProtectedAccount(?int $userId = null): bool
    {
        return self::isAdmin($userId);
    }

    /**
     * Súper usuarios operativos (menús ampliados, sin panel Usuarios ni historial global).
     *
     * @return list<string>
     */
    public static function delegateEmails(): array
    {
        $raw = self::env('PLATFORM_DELEGATE_EMAILS')
            ?? 'bmigliore@tuasesorrm.com.ve';

        $parts = preg_split('/[\s,;]+/', strtolower(trim($raw))) ?: [];

        return array_values(array_filter(array_unique($parts), static fn (string $e): bool => $e !== ''));
    }

    public static function isDelegate(?int $userId = null): bool
    {
        if (self::isAdmin($userId)) {
            return true;
        }

        $email = strtolower(trim(self::emailForUser($userId)));

        return $email !== '' && in_array($email, self::delegateEmails(), true);
    }

    private static function emailForUser(?int $userId): string
    {
        if ($userId === null) {
            $fromSession = session()->get('email');

            return is_string($fromSession) ? $fromSession : '';
        }

        if ((int) session()->get('id') === $userId) {
            $fromSession = session()->get('email');
            if (is_string($fromSession) && $fromSession !== '') {
                return $fromSession;
            }
        }

        try {
            $user = (new User())->find($userId);
            if (is_array($user) && ! empty($user['email'])) {
                return (string) $user['email'];
            }
        } catch (\Throwable) {
            // ignore
        }

        return '';
    }

    private static function env(string $key): ?string
    {
        if (function_exists('env')) {
            $value = env($key);
            if ($value !== null && $value !== false && $value !== '') {
                return (string) $value;
            }
        }

        $fromGetenv = getenv($key);

        return ($fromGetenv !== false && $fromGetenv !== '') ? (string) $fromGetenv : null;
    }
}
