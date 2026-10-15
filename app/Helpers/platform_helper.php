<?php

use App\Libraries\PlatformAdmin;

if (! function_exists('is_platform_admin')) {
    function is_platform_admin(?int $userId = null): bool
    {
        return PlatformAdmin::isAdmin($userId);
    }
}
