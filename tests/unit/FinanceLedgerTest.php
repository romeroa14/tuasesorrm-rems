<?php

declare(strict_types=1);

namespace Tests\Unit;

use App\Libraries\FinanceLedger;
use CodeIgniter\Test\CIUnitTestCase;

final class FinanceLedgerTest extends CIUnitTestCase
{
    public function testSummarizeBalanceCheckAcceptsBalancedMovementLines(): void
    {
        $summary = FinanceLedger::summarizeBalanceCheck([
            ['side' => 'debit', 'amount' => '150.25'],
            ['side' => 'credit', 'amount' => '150.25'],
        ]);

        $this->assertTrue($summary['is_balanced']);
        $this->assertSame('150.25', $summary['debit_total']);
        $this->assertSame('150.25', $summary['credit_total']);
        $this->assertSame('0.00', $summary['difference']);
    }

    public function testSummarizeBalanceCheckFlagsUnbalancedMovementLines(): void
    {
        $summary = FinanceLedger::summarizeBalanceCheck([
            ['side' => 'debit', 'amount' => '150.25'],
            ['side' => 'credit', 'amount' => '149.00'],
        ]);

        $this->assertFalse($summary['is_balanced']);
        $this->assertSame('1.25', $summary['difference']);
    }

    public function testReverseMovementPayloadFlipsSidesAndKeepsTraceability(): void
    {
        $reversal = FinanceLedger::reverseMovementPayload([
            'id'            => 44,
            'workflow_type' => 'legacy_expense',
            'occurred_on'   => '2026-06-07',
            'currency_id'   => 1,
            'rate_to_base'  => '1.000000',
            'lines'         => [
                ['account_id' => 10, 'side' => 'debit', 'amount' => '35.00', 'currency_id' => 1],
                ['account_id' => 20, 'side' => 'credit', 'amount' => '35.00', 'currency_id' => 1],
            ],
        ], 7, '2026-06-08');

        $this->assertSame('posted', $reversal['status']);
        $this->assertSame(44, $reversal['reversal_of_id']);
        $this->assertSame(7, $reversal['actor_user_id']);
        $this->assertSame('2026-06-08', $reversal['occurred_on']);
        $this->assertStringContainsString('44', $reversal['notes']);
        $this->assertSame('credit', $reversal['lines'][0]['side']);
        $this->assertSame('debit', $reversal['lines'][1]['side']);
    }

    public function testMapLegacyTransactionPreservesSourceTraceability(): void
    {
        $payload = FinanceLedger::mapLegacyTransaction([
            'id'          => 12,
            'type'        => 'income',
            'amount'      => '90.00',
            'currency_id' => 2,
            'account_id'  => 4,
            'category_id' => 8,
            'user_id'     => 15,
            'description' => 'Cobro legacy',
            'date'        => '2026-06-01',
        ], 99);

        $this->assertSame('finance_transactions', $payload['source_table']);
        $this->assertSame(12, $payload['source_id']);
        $this->assertSame('posted', $payload['status']);
        $this->assertSame('legacy_transaction', $payload['workflow_type']);
        $this->assertSame(15, $payload['actor_user_id']);
        $this->assertCount(2, $payload['lines']);
        $this->assertSame(4, $payload['lines'][0]['account_id']);
        $this->assertSame('debit', $payload['lines'][0]['side']);
        $this->assertSame(99, $payload['lines'][1]['account_id']);
        $this->assertSame('credit', $payload['lines'][1]['side']);
    }

    public function testMapLegacyExpenseUsesStatusAndApproverTraceability(): void
    {
        $payload = FinanceLedger::mapLegacyExpense([
            'id'            => 21,
            'status'        => 'approved',
            'amount'        => '55.10',
            'currency_id'   => 1,
            'account_id'    => 6,
            'category_id'   => 9,
            'company_id'    => 3,
            'project_id'    => 4,
            'department_id' => 5,
            'created_by'    => 18,
            'approved_by'   => 2,
            'description'   => 'Pago legacy',
            'date'          => '2026-06-03',
        ], 101);

        $this->assertSame('finance_expenses', $payload['source_table']);
        $this->assertSame(21, $payload['source_id']);
        $this->assertSame('posted', $payload['status']);
        $this->assertSame(2, $payload['approved_by']);
        $this->assertSame('legacy_expense', $payload['workflow_type']);
        $this->assertSame('credit', $payload['lines'][0]['side']);
        $this->assertSame(6, $payload['lines'][0]['account_id']);
        $this->assertSame('debit', $payload['lines'][1]['side']);
        $this->assertSame(101, $payload['lines'][1]['account_id']);
        $this->assertSame(9, $payload['lines'][1]['category_id']);
    }

    public function testMapLegacyExpenseKeepsRejectedRowsOutOfPostedState(): void
    {
        $payload = FinanceLedger::mapLegacyExpense([
            'id'          => 22,
            'status'      => 'rejected',
            'amount'      => '55.10',
            'currency_id' => 1,
            'account_id'  => null,
            'created_by'  => 18,
            'description' => 'Gasto rechazado',
            'date'        => '2026-06-03',
        ], 101, 202);

        $this->assertSame('rejected', $payload['status']);
        $this->assertSame(202, $payload['lines'][0]['account_id']);
        $this->assertSame(101, $payload['lines'][1]['account_id']);
    }
}
