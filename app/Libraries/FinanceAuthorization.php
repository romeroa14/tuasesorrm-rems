<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceMember;
use App\Models\User;

class FinanceAuthorization
{
    /**
     * @var array<string, bool>
     */
    private const EMPTY_POLICY = [
        'access'         => false,
        'dashboard.view' => false,
        'catalog.manage' => false,
        'legacy.write'   => false,
        'members.manage' => false,
        'workflow.draft' => false,
        'workflow.submit' => false,
        'workflow.approve' => false,
    ];

    private FinanceMember $financeMemberModel;

    public function __construct(?FinanceMember $financeMemberModel = null)
    {
        $this->financeMemberModel = $financeMemberModel ?? new FinanceMember();
    }

    /**
     * @return array<string, bool>
     */
    public static function policyForRole(?string $memberRole): array
    {
        $policy = self::EMPTY_POLICY;

        if ($memberRole === 'owner') {
            $policy['access'] = true;
            $policy['dashboard.view'] = true;
            $policy['catalog.manage'] = true;
            $policy['members.manage'] = true;
            $policy['workflow.draft'] = true;
            $policy['workflow.submit'] = true;
            $policy['workflow.approve'] = true;

            return $policy;
        }

        if ($memberRole === 'admin') {
            $policy['access'] = true;
            $policy['dashboard.view'] = true;
            $policy['catalog.manage'] = true;
            $policy['workflow.draft'] = true;
            $policy['workflow.submit'] = true;
            $policy['workflow.approve'] = true;

            return $policy;
        }

        if ($memberRole === 'assistant') {
            $policy['access'] = true;
            $policy['dashboard.view'] = true;
            $policy['workflow.draft'] = true;
            $policy['workflow.submit'] = true;
        }

        return $policy;
    }

    public static function roleCan(?string $memberRole, string $ability): bool
    {
        $policy = self::policyForRole($memberRole);

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

    public function can(string $ability, ?int $userId = null): bool
    {
        return self::roleCan($this->roleForUser($userId), $ability);
    }

    public function canAccess(?int $userId = null): bool
    {
        return $this->can('access', $userId);
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
                'is_active'          => 1,
                'can_manage_members' => 1,
            ];
        }

        if ($roleId === 2) {
            return [
                'user_id'            => $userId,
                'member_role'        => 'admin',
                'is_active'          => 1,
                'can_manage_members' => 0,
            ];
        }

        return null;
    }
}
