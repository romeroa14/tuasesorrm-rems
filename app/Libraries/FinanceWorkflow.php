<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceApprovalEvent;
use App\Models\FinanceMember;
use App\Models\FinanceMovement;
use App\Models\FinanceMovementLine;
use InvalidArgumentException;
use RuntimeException;

class FinanceWorkflow
{
    private FinanceLedger $ledger;
    private FinanceMovement $movementModel;
    private FinanceMovementLine $lineModel;
    private FinanceApprovalEvent $approvalEventModel;
    private FinanceMember $financeMemberModel;

    public function __construct(
        ?FinanceLedger $ledger = null,
        ?FinanceMovement $movementModel = null,
        ?FinanceMovementLine $lineModel = null,
        ?FinanceApprovalEvent $approvalEventModel = null,
        ?FinanceMember $financeMemberModel = null
    ) {
        $this->ledger = $ledger ?? new FinanceLedger();
        $this->movementModel = $movementModel ?? new FinanceMovement();
        $this->lineModel = $lineModel ?? new FinanceMovementLine();
        $this->approvalEventModel = $approvalEventModel ?? new FinanceApprovalEvent();
        $this->financeMemberModel = $financeMemberModel ?? new FinanceMember();
    }

    /**
     * @param array<string, mixed> $member
     *
     * @return array<string, mixed>
     */
    public static function resolveSubmissionDecision(array $member, $amount, int $activeMembersCount): array
    {
        $userId = self::nullableInt($member['user_id'] ?? null);
        $role = strtolower((string) ($member['member_role'] ?? ''));
        $limit = self::normalizeNullableAmount($member['approval_limit'] ?? null);
        $normalizedAmount = self::normalizeAmount($amount);
        $memberCount = max(1, $activeMembersCount);

        if ($role === 'assistant') {
            return [
                'status'               => 'pending_approval',
                'requires_approval'    => true,
                'approved_by'          => null,
                'reason'               => 'role_requires_approver',
                'approver_must_differ' => $memberCount > 1,
            ];
        }

        if ($limit !== null && self::compareAmounts($normalizedAmount, $limit) === 1) {
            if ($role === 'owner' && $memberCount === 1 && $userId !== null) {
                return [
                    'status'               => 'posted',
                    'requires_approval'    => false,
                    'approved_by'          => $userId,
                    'reason'               => 'owner_single_member_fallback',
                    'approver_must_differ' => false,
                ];
            }

            return [
                'status'               => 'pending_approval',
                'requires_approval'    => true,
                'approved_by'          => null,
                'reason'               => 'amount_exceeds_limit',
                'approver_must_differ' => $memberCount > 1,
            ];
        }

        return [
            'status'               => 'posted',
            'requires_approval'    => false,
            'approved_by'          => $userId,
            'reason'               => 'within_limit',
            'approver_must_differ' => false,
        ];
    }

    /**
     * @param array<string, mixed> $input
     * @param array<string, mixed> $decision
     *
     * @return array<string, mixed>
     */
    public static function buildMovementPayload(string $workflowType, array $input, int $actorUserId, array $decision): array
    {
        $normalizedType = self::normalizeWorkflowType($workflowType);
        $accountId = self::requiredInt($input, 'account_id');
        $offsetAccountId = self::requiredInt($input, 'offset_account_id');

        $entrySide = $normalizedType === 'ingreso' ? 'debit' : 'credit';
        $counterpartySide = $entrySide === 'debit' ? 'credit' : 'debit';

        $sharedDimensions = [
            'currency_id'  => self::nullableInt($input['currency_id'] ?? null),
            'rate_to_base' => self::normalizeRate($input['rate_to_base'] ?? 1),
            'category_id'  => self::nullableInt($input['category_id'] ?? null),
            'company_id'   => self::nullableInt($input['company_id'] ?? null),
            'project_id'   => self::nullableInt($input['project_id'] ?? null),
            'department_id' => self::nullableInt($input['department_id'] ?? null),
            'description'  => self::normalizeDescription($input['description'] ?? null),
        ];

        return [
            'workflow_type' => $normalizedType,
            'status'        => (string) ($decision['status'] ?? 'draft'),
            'occurred_on'   => self::requiredDate($input, 'occurred_on'),
            'actor_user_id' => $actorUserId,
            'approved_by'   => self::nullableInt($decision['approved_by'] ?? null),
            'currency_id'   => $sharedDimensions['currency_id'],
            'rate_to_base'  => $sharedDimensions['rate_to_base'],
            'notes'         => self::normalizeDescription($input['notes'] ?? $input['description'] ?? null),
            'posted_at'     => ($decision['status'] ?? 'draft') === 'posted' ? date('Y-m-d H:i:s') : null,
            'lines'         => [
                [
                    'line_number'   => 1,
                    'account_id'    => $accountId,
                    'side'          => $entrySide,
                    'amount'        => self::normalizeAmount($input['amount'] ?? 0),
                    'currency_id'   => $sharedDimensions['currency_id'],
                    'rate_to_base'  => $sharedDimensions['rate_to_base'],
                    'category_id'   => $sharedDimensions['category_id'],
                    'description'   => $sharedDimensions['description'],
                ],
                [
                    'line_number'   => 2,
                    'account_id'    => $offsetAccountId,
                    'side'          => $counterpartySide,
                    'amount'        => self::normalizeAmount($input['amount'] ?? 0),
                    'currency_id'   => $sharedDimensions['currency_id'],
                    'rate_to_base'  => $sharedDimensions['rate_to_base'],
                    'category_id'   => $sharedDimensions['category_id'],
                    'company_id'    => $sharedDimensions['company_id'],
                    'project_id'    => $sharedDimensions['project_id'],
                    'department_id' => $sharedDimensions['department_id'],
                    'description'   => $sharedDimensions['description'],
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $member
     * @param array<string, mixed> $input
     *
     * @return array<string, mixed>
     */
    public function createWorkflow(string $workflowType, array $input, array $member): array
    {
        $normalizedType = self::normalizeWorkflowType($workflowType);
        $actorUserId = self::nullableInt($member['user_id'] ?? null);

        if ($actorUserId === null) {
            throw new InvalidArgumentException('Finance workflow actor is required.');
        }

        $isDraft = filter_var($input['submit'] ?? true, FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) === false;
        $decision = $isDraft
            ? [
                'status'               => 'draft',
                'requires_approval'    => false,
                'approved_by'          => null,
                'reason'               => 'saved_as_draft',
                'approver_must_differ' => false,
            ]
            : self::resolveSubmissionDecision($member, $input['amount'] ?? 0, $this->countActiveMembers());

        $result = $this->ledger->postMovement(
            self::buildMovementPayload($normalizedType, $input, $actorUserId, $decision)
        );

        $movement = $result['movement'] ?? null;
        if (! is_array($movement) || ! isset($movement['id'])) {
            throw new RuntimeException('Finance workflow movement could not be created.');
        }

        $eventType = $decision['status'] === 'draft'
            ? 'drafted'
            : ($decision['status'] === 'posted' ? 'auto_posted' : 'submitted');

        $this->recordApprovalEvent(
            (int) $movement['id'],
            $normalizedType,
            $eventType,
            null,
            (string) $decision['status'],
            $actorUserId,
            $input['notes'] ?? $input['description'] ?? null,
            [
                'requires_approval' => $decision['requires_approval'] ?? false,
                'reason'            => $decision['reason'] ?? null,
            ]
        );

        $result['approval'] = $decision;

        return $result;
    }

    /**
     * @param array<string, mixed> $member
     *
     * @return array<string, mixed>
     */
    public function approveMovement(int $movementId, array $member, ?string $notes = null): array
    {
        $movement = $this->loadMovementWithLines($movementId);
        $approverUserId = self::nullableInt($member['user_id'] ?? null);
        $role = strtolower((string) ($member['member_role'] ?? ''));

        if ($approverUserId === null || ! FinanceAuthorization::roleCan($role, 'workflow.approve')) {
            throw new InvalidArgumentException('This finance member cannot approve workflow movements.');
        }

        $fromStatus = (string) ($movement['status'] ?? 'draft');
        if (! in_array($fromStatus, ['draft', 'pending_approval'], true)) {
            throw new InvalidArgumentException('Only draft or pending workflow movements can be approved.');
        }

        if ($this->countActiveMembers() > 1 && (int) ($movement['actor_user_id'] ?? 0) === $approverUserId) {
            throw new InvalidArgumentException('Approval must come from a different finance member when more than one member is active.');
        }

        $this->movementModel->update($movementId, [
            'status'      => 'posted',
            'approved_by' => $approverUserId,
            'posted_at'   => date('Y-m-d H:i:s'),
        ]);

        $this->ledger->refreshAccountBalances(
            array_map(
                static fn (array $line): int => (int) ($line['account_id'] ?? 0),
                $movement['lines']
            )
        );

        $this->recordApprovalEvent(
            $movementId,
            (string) ($movement['workflow_type'] ?? 'workflow'),
            'approved',
            $fromStatus,
            'posted',
            $approverUserId,
            $notes,
            ['approved_by_role' => $role]
        );

        return $this->loadMovementWithLines($movementId);
    }

    /**
     * @param array<string, mixed> $member
     *
     * @return array<string, mixed>
     */
    public function rejectMovement(int $movementId, array $member, ?string $notes = null): array
    {
        $movement = $this->loadMovementWithLines($movementId);
        $actorUserId = self::nullableInt($member['user_id'] ?? null);
        $role = strtolower((string) ($member['member_role'] ?? ''));

        if ($actorUserId === null || ! FinanceAuthorization::roleCan($role, 'workflow.approve')) {
            throw new InvalidArgumentException('This finance member cannot reject workflow movements.');
        }

        $fromStatus = (string) ($movement['status'] ?? 'draft');
        if (! in_array($fromStatus, ['draft', 'pending_approval'], true)) {
            throw new InvalidArgumentException('Only draft or pending workflow movements can be rejected.');
        }

        if ($this->countActiveMembers() > 1 && (int) ($movement['actor_user_id'] ?? 0) === $actorUserId) {
            throw new InvalidArgumentException('Rejection must come from a different finance member when more than one member is active.');
        }

        $this->movementModel->update($movementId, [
            'status'      => 'rejected',
            'approved_by' => $actorUserId,
        ]);

        $this->recordApprovalEvent(
            $movementId,
            (string) ($movement['workflow_type'] ?? 'workflow'),
            'rejected',
            $fromStatus,
            'rejected',
            $actorUserId,
            $notes,
            ['rejected_by_role' => $role]
        );

        return $this->loadMovementWithLines($movementId);
    }

    private function countActiveMembers(): int
    {
        try {
            return max(1, $this->financeMemberModel->where('is_active', 1)->countAllResults());
        } catch (\Throwable $exception) {
            return 1;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function loadMovementWithLines(int $movementId): array
    {
        $movement = $this->movementModel->find($movementId);
        if (! is_array($movement)) {
            throw new InvalidArgumentException('Finance workflow movement not found.');
        }

        $movement['lines'] = $this->lineModel
            ->where('movement_id', $movementId)
            ->orderBy('line_number', 'ASC')
            ->findAll();

        return $movement;
    }

    /**
     * @param array<string, mixed> $metadata
     */
    private function recordApprovalEvent(
        int $movementId,
        string $workflowType,
        string $eventType,
        ?string $fromStatus,
        string $toStatus,
        int $actorUserId,
        ?string $notes,
        array $metadata = []
    ): void {
        $this->approvalEventModel->insert([
            'movement_id'   => $movementId,
            'workflow_type' => $workflowType,
            'event_type'    => $eventType,
            'from_status'   => $fromStatus,
            'to_status'     => $toStatus,
            'actor_user_id' => $actorUserId,
            'notes'         => self::normalizeDescription($notes),
            'metadata_json' => $metadata === [] ? null : json_encode($metadata, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
        ]);
    }

    private static function normalizeWorkflowType(string $workflowType): string
    {
        $normalized = strtolower(trim($workflowType));

        if (! in_array($normalized, ['ingreso', 'egreso'], true)) {
            throw new InvalidArgumentException('Unsupported private finance workflow.');
        }

        return $normalized;
    }

    /**
     * @param array<string, mixed> $input
     */
    private static function requiredInt(array $input, string $key): int
    {
        $value = self::nullableInt($input[$key] ?? null);
        if ($value === null) {
            throw new InvalidArgumentException(sprintf('Field "%s" is required.', $key));
        }

        return $value;
    }

    /**
     * @param array<string, mixed> $input
     */
    private static function requiredDate(array $input, string $key): string
    {
        $value = trim((string) ($input[$key] ?? ''));
        if ($value === '') {
            throw new InvalidArgumentException(sprintf('Field "%s" is required.', $key));
        }

        return $value;
    }

    private static function normalizeDescription($value): ?string
    {
        $normalized = trim((string) $value);

        return $normalized === '' ? null : $normalized;
    }

    private static function normalizeAmount($value): string
    {
        $normalized = trim((string) $value);

        if ($normalized === '') {
            throw new InvalidArgumentException('Field "amount" is required.');
        }

        return number_format((float) $normalized, 2, '.', '');
    }

    private static function normalizeNullableAmount($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        return number_format((float) $value, 2, '.', '');
    }

    private static function normalizeRate($value): string
    {
        return number_format((float) $value, 6, '.', '');
    }

    private static function compareAmounts(string $left, string $right): int
    {
        return (float) $left <=> (float) $right;
    }

    private static function nullableInt($value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric((string) $value)) {
            return null;
        }

        return (int) $value;
    }
}
