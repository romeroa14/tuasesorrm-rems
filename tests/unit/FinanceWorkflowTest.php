<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FinanceWorkflow;
use CodeIgniter\Test\CIUnitTestCase;

final class FinanceWorkflowTest extends CIUnitTestCase
{
    public function testAssistantSubmissionRequiresApproval(): void
    {
        $decision = FinanceWorkflow::resolveSubmissionDecision([
            'user_id'        => 8,
            'member_role'    => 'assistant',
            'approval_limit' => null,
        ], '150.00', 2);

        $this->assertSame('pending_approval', $decision['status']);
        $this->assertTrue($decision['requires_approval']);
        $this->assertNull($decision['approved_by']);
        $this->assertSame('role_requires_approver', $decision['reason']);
        $this->assertTrue($decision['approver_must_differ']);
    }

    public function testOwnerSingleMemberFallbackAllowsImmediatePosting(): void
    {
        $decision = FinanceWorkflow::resolveSubmissionDecision([
            'user_id'        => 1,
            'member_role'    => 'owner',
            'approval_limit' => '100.00',
        ], '250.00', 1);

        $this->assertSame('posted', $decision['status']);
        $this->assertFalse($decision['requires_approval']);
        $this->assertSame(1, $decision['approved_by']);
        $this->assertSame('owner_single_member_fallback', $decision['reason']);
    }

    public function testBuildIngresoPayloadCreatesDebitAndCreditLines(): void
    {
        $payload = FinanceWorkflow::buildMovementPayload('ingreso', [
            'occurred_on'       => '2026-06-07',
            'amount'            => '99.50',
            'currency_id'       => 2,
            'rate_to_base'      => '36.500000',
            'account_id'        => 10,
            'offset_account_id' => 20,
            'category_id'       => 30,
            'description'       => 'Cobro de honorarios',
        ], 7, [
            'status'            => 'posted',
            'requires_approval' => false,
            'approved_by'       => 7,
        ]);

        $this->assertSame('ingreso', $payload['workflow_type']);
        $this->assertSame('posted', $payload['status']);
        $this->assertSame(7, $payload['actor_user_id']);
        $this->assertSame(7, $payload['approved_by']);
        $this->assertSame('debit', $payload['lines'][0]['side']);
        $this->assertSame(10, $payload['lines'][0]['account_id']);
        $this->assertSame('credit', $payload['lines'][1]['side']);
        $this->assertSame(20, $payload['lines'][1]['account_id']);
        $this->assertSame(30, $payload['lines'][0]['category_id']);
    }

    public function testBuildEgresoPayloadCreatesPendingApprovalMovement(): void
    {
        $payload = FinanceWorkflow::buildMovementPayload('egreso', [
            'occurred_on'       => '2026-06-07',
            'amount'            => '40.00',
            'currency_id'       => 1,
            'rate_to_base'      => '1.000000',
            'account_id'        => 11,
            'offset_account_id' => 21,
            'company_id'        => 31,
            'project_id'        => 41,
            'department_id'     => 51,
            'description'       => 'Pago operativo',
        ], 9, [
            'status'            => 'pending_approval',
            'requires_approval' => true,
            'approved_by'       => null,
        ]);

        $this->assertSame('egreso', $payload['workflow_type']);
        $this->assertSame('pending_approval', $payload['status']);
        $this->assertNull($payload['approved_by']);
        $this->assertSame('credit', $payload['lines'][0]['side']);
        $this->assertSame(11, $payload['lines'][0]['account_id']);
        $this->assertSame('debit', $payload['lines'][1]['side']);
        $this->assertSame(21, $payload['lines'][1]['account_id']);
        $this->assertSame(31, $payload['lines'][1]['company_id']);
        $this->assertSame(41, $payload['lines'][1]['project_id']);
        $this->assertSame(51, $payload['lines'][1]['department_id']);
    }
}
