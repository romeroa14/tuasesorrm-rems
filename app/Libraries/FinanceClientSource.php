<?php

declare(strict_types=1);

namespace App\Libraries;

class FinanceClientSource
{
    /**
     * @return array<string, string>
     */
    public static function options(): array
    {
        return [
            ''                => 'Sin especificar',
            'ads_meta'        => 'Publicidad (Meta / ADS)',
            'referral'        => 'Referido',
            'agent_portfolio' => 'Cartera agente (ej. Roxana)',
            'organic'         => 'Orgánico / walk-in',
            'repeat_client'   => 'Cliente recurrente',
            'other'           => 'Otro',
        ];
    }

    public static function label(?string $code): string
    {
        $code = trim((string) $code);
        $opts = self::options();

        return $opts[$code] ?? ($code !== '' ? $code : '—');
    }

    /**
     * @return list<string>
     */
    public static function validCodes(): array
    {
        return array_values(array_filter(array_keys(self::options()), static fn ($k) => $k !== ''));
    }
}
