<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceMember;
use App\Models\User;

/**
 * Permisos financieros por perfil:
 * - full: Rosana / owner — todo
 * - approver: aprueba movimientos, ve ingresos/egresos
 * - loader: Julia — carga ingresos/egresos, no aprueba
 * - viewer: Liliana — solo reportes y dashboard
 */
class FinanceAuthorization
{
    /**
     * @var array<string, bool>
     */
    private const EMPTY_POLICY = [
        'access'            => false,
        'dashboard.view'    => false,
        'reports.view'      => false,
        'income.view'       => false,
        'expense.view'      => false,
        'catalog.manage'    => false,
        'legacy.write'      => false,
        'members.manage'    => false,
        'workflow.draft'    => false,
        'workflow.submit'   => false,
        'workflow.approve'  => false,
        'period.close'      => false,
        'wallets.manage'    => false,
    ];

    private FinanceMember $financeMemberModel;

    public function __construct(?FinanceMember $financeMemberModel = null)
    {
        $this->financeMemberModel = $financeMemberModel ?? new FinanceMember();
    }

    /**
     * @return array<string, bool>
     */
    public static function policyForRole(?string $memberRole, ?string $financeProfile = null): array
    {
        $profile = self::resolveProfile($memberRole, $financeProfile);

        return match ($profile) {
            'viewer'   => self::viewerPolicy(),
            'loader'   => self::loaderPolicy(),
            'approver' => self::approverPolicy(),
            default    => self::fullPolicy(),
        };
    }

    /**
     * @return array<string, bool>
     */
    private static function fullPolicy(): array
    {
        return [
            'access'            => true,
            'dashboard.view'    => true,
            'reports.view'      => true,
            'income.view'       => true,
            'expense.view'      => true,
            'catalog.manage'    => true,
            'legacy.write'      => false,
            'members.manage'    => true,
            'workflow.draft'    => true,
            'workflow.submit'   => true,
            'workflow.approve'  => true,
            'period.close'      => true,
            'wallets.manage'    => true,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function approverPolicy(): array
    {
        return [
            'access'            => true,
            'dashboard.view'    => true,
            'reports.view'      => true,
            'income.view'       => true,
            'expense.view'      => true,
            'catalog.manage'    => false,
            'legacy.write'      => false,
            'members.manage'    => false,
            'workflow.draft'    => true,
            'workflow.submit'   => true,
            'workflow.approve'  => true,
            'period.close'      => true,
            'wallets.manage'    => false,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function loaderPolicy(): array
    {
        return [
            'access'            => true,
            'dashboard.view'    => true,
            'reports.view'      => false,
            'income.view'       => true,
            'expense.view'      => true,
            'catalog.manage'    => false,
            'legacy.write'      => false,
            'members.manage'    => false,
            'workflow.draft'    => true,
            'workflow.submit'   => true,
            'workflow.approve'  => false,
            'period.close'      => false,
            'wallets.manage'    => false,
        ];
    }

    /**
     * @return array<string, bool>
     */
    private static function viewerPolicy(): array
    {
        return [
            'access'            => true,
            'dashboard.view'    => true,
            'reports.view'      => true,
            'income.view'       => false,
            'expense.view'      => false,
            'catalog.manage'    => false,
            'legacy.write'      => false,
            'members.manage'    => false,
            'workflow.draft'    => false,
            'workflow.submit'   => false,
            'workflow.approve'  => false,
            'period.close'      => false,
            'wallets.manage'    => false,
        ];
    }

    private static function resolveProfile(?string $memberRole, ?string $financeProfile): string
    {
        if ($financeProfile !== null && $financeProfile !== '') {
            return $financeProfile;
        }

        return match ($memberRole) {
            'assistant' => 'loader',
            'admin'     => 'approver',
            'owner'     => 'full',
            default     => 'full',
        };
    }

    public static function roleCan(?string $memberRole, string $ability, ?string $financeProfile = null): bool
    {
        $policy = self::policyForRole($memberRole, $financeProfile);

        return $policy[$ability] ?? false;
    }

    public function currentMembership(): ?array
    {
        $userId = session()->get('id');

        return is_numeric($userId) ? $this->membershipForUser((int) $userId) : null;
    }

    public function currentRole(): ?string
    {
        $membership = $this->currentMembership();

        return $membership['member_role'] ?? null;
    }

    public function currentProfile(): ?string
    {
        $membership = $this->currentMembership();

        return self::resolveProfile(
            $membership['member_role'] ?? null,
            $membership['finance_profile'] ?? null
        );
    }

    public function can(string $ability, ?int $userId = null): bool
    {
        $membership = $this->membershipForUser($userId);

        return self::roleCan(
            $membership['member_role'] ?? null,
            $ability,
            $membership['finance_profile'] ?? null
        );
    }

    public function canAccess(?int $userId = null): bool
    {
        return $this->can('access', $userId);
    }

    public function canViewDashboard(?int $userId = null): bool
    {
        return $this->can('dashboard.view', $userId);
    }

    public function canViewReports(?int $userId = null): bool
    {
        return $this->can('reports.view', $userId);
    }

    public function canViewIncome(?int $userId = null): bool
    {
        return $this->can('income.view', $userId);
    }

    public function canViewExpense(?int $userId = null): bool
    {
        return $this->can('expense.view', $userId);
    }

    public function canManageCatalogs(?int $userId = null): bool
    {
        return $this->can('catalog.manage', $userId);
    }

    public function canWriteLegacy(?int $userId = null): bool
    {
        return $this->can('legacy.write', $userId);
    }

    public function canManageMembers(?int $userId = null): bool
    {
        return $this->can('members.manage', $userId);
    }

    public function canDraftWorkflow(?int $userId = null): bool
    {
        return $this->can('workflow.draft', $userId);
    }

    public function canSubmitWorkflow(?int $userId = null): bool
    {
        return $this->can('workflow.submit', $userId);
    }

    public function canApproveWorkflow(?int $userId = null): bool
    {
        return $this->can('workflow.approve', $userId);
    }

    public function canClosePeriod(?int $userId = null): bool
    {
        return $this->can('period.close', $userId);
    }

    public function canManageWallets(?int $userId = null): bool
    {
        return $this->can('wallets.manage', $userId);
    }

    public function roleForUser(?int $userId = null): ?string
    {
        $membership = $this->membershipForUser($userId);

        return $membership['member_role'] ?? null;
    }

    public function membershipForUser(?int $userId = null): ?array
    {
        $resolvedUserId = $userId ?? (is_numeric(session()->get('id')) ? (int) session()->get('id') : null);
        if ($resolvedUserId === null) {
            return null;
        }

        if ($this->isMembershipTableReady()) {
            $member = $this->financeMemberModel->findActiveByUserId($resolvedUserId);
            if ($member !== null) {
                return $member;
            }
        }

        return $this->fallbackMembership($resolvedUserId);
    }

    private function isMembershipTableReady(): bool
    {
        try {
            $db = db_connect();
            if (! $db->tableExists('finance_members')) {
                return false;
            }

            return $db->table('finance_members')->countAllResults() > 0;
        } catch (\Throwable $exception) {
            return false;
        }
    }

    private function fallbackMembership(int $userId): ?array
    {
        $roleId = null;

        if ((int) session()->get('id') === $userId && is_numeric(session()->get('id_fk_rol'))) {
            $roleId = (int) session()->get('id_fk_rol');
        } else {
            try {
                $db = db_connect();
                if ($db->tableExists('users')) {
                    $user = (new User())->find($userId);
                    if (is_array($user) && isset($user['id_fk_rol'])) {
                        $roleId = (int) $user['id_fk_rol'];
                    }
                }
            } catch (\Throwable $exception) {
                return null;
            }
        }

        if ($roleId === 1) {
            return [
                'user_id'            => $userId,
                'member_role'        => 'owner',
                'finance_profile'    => 'full',
                'is_active'          => 1,
                'can_manage_members' => 1,
            ];
        }

        if ($roleId === 2) {
            return [
                'user_id'            => $userId,
                'member_role'        => 'admin',
                'finance_profile'    => 'approver',
                'is_active'          => 1,
                'can_manage_members' => 0,
            ];
        }

        return null;
    }
}
