<?php

declare(strict_types=1);

namespace App\Libraries;

class AiAgentAccess
{
    public static function allows(int $roleId): bool
    {
        return $roleId === 2;
    }
}
