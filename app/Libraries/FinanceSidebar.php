<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\FinanceMenu;

class FinanceSidebar
{
    /**
     * @return list<array<string, mixed>>
     */
    public static function modules(): array
    {
        return FinanceMenu::sidebarModules();
    }

    public static function isItemActive(array $item, ?string $currentPath, ?string $currentType): bool
    {
        if (! isset($item['url'])) {
            return false;
        }

        $path = parse_url($item['url'], PHP_URL_PATH) ?: '';
        $query = [];
        parse_str(parse_url($item['url'], PHP_URL_QUERY) ?: '', $query);

        if ($currentPath !== ltrim($path, '/')) {
            return false;
        }

        if (isset($query['type'])) {
            return ($currentType ?? '') === $query['type'];
        }

        return ($currentType ?? '') === '';
    }

    public static function isModuleActive(array $module, ?string $currentPath, ?string $currentType): bool
    {
        if (isset($module['url'])) {
            $path = ltrim(parse_url($module['url'], PHP_URL_PATH) ?: '', '/');

            return $currentPath === $path;
        }

        foreach ($module['items'] ?? [] as $item) {
            if (self::isItemActive($item, $currentPath, $currentType)) {
                return true;
            }
        }

        return false;
    }
}
