<?php

declare(strict_types=1);

namespace App\Libraries;

/**
 * Esquemas de % sobre el precio de venta y perfil de reparto de la inmobiliaria.
 */
class FinanceCommissionPolicy
{
    /**
     * @return list<array{code: string, label: string, kind: string, default_pct: float, is_custom: bool}>
     */
    public static function schemes(): array
    {
        return [
            ['code' => 'primary_1',   'label' => 'Primario 1 — 3%',              'kind' => 'primary',   'default_pct' => 3.0,  'is_custom' => false],
            ['code' => 'primary_2',   'label' => 'Primario 2 — 1,5%',            'kind' => 'primary',   'default_pct' => 1.5,  'is_custom' => false],
            ['code' => 'secondary_1', 'label' => 'Secundario 1 — 5%',            'kind' => 'secondary', 'default_pct' => 5.0,  'is_custom' => false],
            ['code' => 'secondary_2', 'label' => 'Secundario 2 — 2,5%',          'kind' => 'secondary', 'default_pct' => 2.5,  'is_custom' => false],
            ['code' => 'custom',      'label' => 'Porcentaje personalizado',     'kind' => 'custom',    'default_pct' => 0.0,  'is_custom' => true],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function defaultProfile(): array
    {
        return [
            'id'           => 0,
            'name'         => 'Reparto estándar',
            'captador_pct' => 25.0,
            'closer_pct'   => 25.0,
            'company_pct'  => 50.0,
            'roles'        => [
                ['code' => 'coordinator', 'label' => 'Coordinadora', 'pct_of_company' => 8.0,  'requires_user' => 1, 'sort_order' => 1, 'active' => 1],
                ['code' => 'atc',         'label' => 'ATC',          'pct_of_company' => 5.0,  'requires_user' => 1, 'sort_order' => 2, 'active' => 1],
                ['code' => 'marketing',   'label' => 'Mercadeo',     'pct_of_company' => 10.0, 'requires_user' => 1, 'sort_order' => 3, 'active' => 1],
            ],
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public static function loadProfile(): array
    {
        $db = \Config\Database::connect();
        $fallback = self::defaultProfile();
        if (! $db->tableExists('finance_commission_profiles')) {
            return $fallback;
        }

        $row = $db->table('finance_commission_profiles')->orderBy('id', 'ASC')->get()->getRowArray();
        if (! is_array($row)) {
            return $fallback;
        }

        $roles = [];
        if ($db->tableExists('finance_commission_profile_roles')) {
            $roles = $db->table('finance_commission_profile_roles')
                ->where('profile_id', $row['id'])
                ->orderBy('sort_order', 'ASC')
                ->get()
                ->getResultArray();
        }
        if ($roles === []) {
            $roles = $fallback['roles'];
        }

        return [
            'id'           => (int) $row['id'],
            'name'         => (string) ($row['name'] ?? $fallback['name']),
            'captador_pct' => (float) ($row['captador_pct'] ?? 25),
            'closer_pct'   => (float) ($row['closer_pct'] ?? 25),
            'company_pct'  => (float) ($row['company_pct'] ?? 50),
            'roles'        => $roles,
        ];
    }

    /**
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public static function saveProfile(array $input): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('finance_commission_profiles')) {
            throw new \RuntimeException('Falta la tabla de perfil de comisiones.');
        }

        $captador = round((float) ($input['captador_pct'] ?? 25), 2);
        $closer = round((float) ($input['closer_pct'] ?? 25), 2);
        $company = round((float) ($input['company_pct'] ?? 50), 2);
        if (abs(($captador + $closer + $company) - 100) > 0.05) {
            throw new \InvalidArgumentException('Captador + closer + empresa debe sumar 100%.');
        }

        $payload = [
            'name'         => trim((string) ($input['name'] ?? '')) ?: 'Reparto estándar',
            'captador_pct' => $captador,
            'closer_pct'   => $closer,
            'company_pct'  => $company,
            'updated_at'   => date('Y-m-d H:i:s'),
        ];

        $existing = $db->table('finance_commission_profiles')->orderBy('id', 'ASC')->get()->getRowArray();
        if (is_array($existing)) {
            $db->table('finance_commission_profiles')->where('id', $existing['id'])->update($payload);
            $profileId = (int) $existing['id'];
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $db->table('finance_commission_profiles')->insert($payload);
            $profileId = (int) $db->insertID();
        }

        $roles = $input['roles'] ?? [];
        if (is_array($roles)) {
            foreach ($roles as $role) {
                $code = trim((string) ($role['code'] ?? ''));
                if ($code === '') {
                    continue;
                }
                $roleRow = [
                    'profile_id'      => $profileId,
                    'code'            => $code,
                    'label'           => trim((string) ($role['label'] ?? $code)),
                    'pct_of_company'  => round((float) ($role['pct_of_company'] ?? 0), 2),
                    'requires_user'   => ! empty($role['requires_user']) ? 1 : 1,
                    'sort_order'      => (int) ($role['sort_order'] ?? 0),
                    'active'          => isset($role['active']) ? (int) (bool) $role['active'] : 1,
                    'updated_at'      => date('Y-m-d H:i:s'),
                ];
                $found = $db->table('finance_commission_profile_roles')
                    ->where('profile_id', $profileId)
                    ->where('code', $code)
                    ->get()
                    ->getRowArray();
                if (is_array($found)) {
                    $db->table('finance_commission_profile_roles')->where('id', $found['id'])->update($roleRow);
                } else {
                    $roleRow['created_at'] = date('Y-m-d H:i:s');
                    $db->table('finance_commission_profile_roles')->insert($roleRow);
                }
            }
        }

        return self::loadProfile();
    }

    /**
     * Convierte % sobre la porción empresa a % sobre la comisión total.
     */
    public static function companyRoleShareOfCommission(float $companyPct, float $pctOfCompany): float
    {
        return round($companyPct * $pctOfCompany / 100, 4);
    }
}
