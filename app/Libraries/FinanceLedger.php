<?php

declare(strict_types=1);

namespace App\Libraries;

use App\Models\FinanceAccount;
use App\Models\FinanceMovement;
use App\Models\FinanceMovementLine;
use InvalidArgumentException;
use RuntimeException;

class FinanceLedger
{
    private const AMOUNT_SCALE = 2;
    private const RATE_SCALE = 6;

    private FinanceMovement $movementModel;
    private FinanceMovementLine $lineModel;
    private FinanceAccount $accountModel;

    public function __construct(
        ?FinanceMovement $movementModel = null,
        ?FinanceMovementLine $lineModel = null,
        ?FinanceAccount $accountModel = null
    ) {
        $this->movementModel = $movementModel ?? new FinanceMovement();
        $this->lineModel = $lineModel ?? new FinanceMovementLine();
        $this->accountModel = $accountModel ?? new FinanceAccount();
    }

    /**
     * @param array<int, array<string, mixed>> $lines
     *
     * @return array<string, mixed>
     */
    public static function summarizeBalanceCheck(array $lines): array
    {
        $debitTotal = 0;
        $creditTotal = 0;

        foreach ($lines as $line) {
            $amount = self::decimalToInt($line['amount'] ?? 0, self::AMOUNT_SCALE);
            $side = self::normalizeSide($line['side'] ?? null);

            if ($side === 'debit') {
                $debitTotal += $amount;
            } elseif ($side === 'credit') {
                $creditTotal += $amount;
            }
        }

        $difference = abs($debitTotal - $creditTotal);

        return [
            'debit_total'  => self::formatDecimal($debitTotal, self::AMOUNT_SCALE),
            'credit_total' => self::formatDecimal($creditTotal, self::AMOUNT_SCALE),
            'difference'   => self::formatDecimal($difference, self::AMOUNT_SCALE),
            'is_balanced'  => $debitTotal === $creditTotal,
        ];
    }

    /**
     * @param array<string, mixed> $movement
     *
     * @return array<string, mixed>
     */
    public static function reverseMovementPayload(array $movement, int $actorUserId, ?string $occurredOn = null): array
    {
        $reversalDate = $occurredOn ?? date('Y-m-d');
        $reversalId = self::nullableInt($movement['id'] ?? null);
        $lines = [];

        foreach (($movement['lines'] ?? []) as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $lines[] = [
                'line_number'   => $index + 1,
                'account_id'    => self::nullableInt($line['account_id'] ?? null),
                'side'          => self::flipSide($line['side'] ?? null),
                'amount'        => self::normalizeAmount($line['amount'] ?? 0),
                'currency_id'   => self::nullableInt($line['currency_id'] ?? ($movement['currency_id'] ?? null)),
                'rate_to_base'  => self::normalizeRate($line['rate_to_base'] ?? ($movement['rate_to_base'] ?? 1)),
                'category_id'   => self::nullableInt($line['category_id'] ?? null),
                'company_id'    => self::nullableInt($line['company_id'] ?? null),
                'project_id'    => self::nullableInt($line['project_id'] ?? null),
                'department_id' => self::nullableInt($line['department_id'] ?? null),
                'description'   => $line['description'] ?? null,
            ];
        }

        return [
            'workflow_type'  => (string) ($movement['workflow_type'] ?? 'reversal'),
            'status'         => 'posted',
            'occurred_on'    => $reversalDate,
            'actor_user_id'  => $actorUserId,
            'approved_by'    => self::nullableInt($movement['approved_by'] ?? null),
            'source_table'   => $movement['source_table'] ?? null,
            'source_id'      => self::nullableInt($movement['source_id'] ?? null),
            'currency_id'    => self::nullableInt($movement['currency_id'] ?? null),
            'payment_type_id'=> self::nullableInt($movement['payment_type_id'] ?? null),
            'rate_to_base'   => self::normalizeRate($movement['rate_to_base'] ?? 1),
            'reversal_of_id' => $reversalId,
            'notes'          => trim('Reversal of movement #' . ($reversalId ?? 'unknown')),
            'posted_at'      => date('Y-m-d H:i:s'),
            'lines'          => $lines,
        ];
    }

    /**
     * @param array<string, mixed> $legacyTransaction
     *
     * @return array<string, mixed>
     */
    public static function mapLegacyTransaction(array $legacyTransaction, int $clearingAccountId): array
    {
        $type = strtolower((string) ($legacyTransaction['type'] ?? 'income'));
        $amount = self::normalizeAmount($legacyTransaction['amount'] ?? 0);
        $primaryAccountId = self::nullableInt($legacyTransaction['account_id'] ?? null) ?? $clearingAccountId;
        $currencyId = self::nullableInt($legacyTransaction['currency_id'] ?? null);

        $primarySide = $type === 'expense' ? 'credit' : 'debit';
        $counterpartySide = $primarySide === 'debit' ? 'credit' : 'debit';

        return [
            'workflow_type' => 'legacy_transaction',
            'status'        => 'posted',
            'occurred_on'   => (string) ($legacyTransaction['date'] ?? date('Y-m-d')),
            'actor_user_id' => self::nullableInt($legacyTransaction['user_id'] ?? null),
            'approved_by'   => null,
            'source_table'  => 'finance_transactions',
            'source_id'     => self::nullableInt($legacyTransaction['id'] ?? null),
            'currency_id'   => $currencyId,
            'rate_to_base'  => self::normalizeRate($legacyTransaction['rate_to_base'] ?? 1),
            'notes'         => self::legacyNote('finance_transactions', $legacyTransaction),
            'posted_at'     => date('Y-m-d H:i:s'),
            'lines'         => [
                [
                    'line_number'  => 1,
                    'account_id'   => $primaryAccountId,
                    'side'         => $primarySide,
                    'amount'       => $amount,
                    'currency_id'  => $currencyId,
                    'rate_to_base' => self::normalizeRate($legacyTransaction['rate_to_base'] ?? 1),
                    'category_id'  => self::nullableInt($legacyTransaction['category_id'] ?? null),
                    'description'  => $legacyTransaction['description'] ?? null,
                ],
                [
                    'line_number'  => 2,
                    'account_id'   => $clearingAccountId,
                    'side'         => $counterpartySide,
                    'amount'       => $amount,
                    'currency_id'  => $currencyId,
                    'rate_to_base' => self::normalizeRate($legacyTransaction['rate_to_base'] ?? 1),
                    'description'  => $legacyTransaction['description'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $legacyExpense
     *
     * @return array<string, mixed>
     */
    public static function mapLegacyExpense(array $legacyExpense, int $clearingAccountId, ?int $fallbackAccountId = null): array
    {
        $amount = self::normalizeAmount($legacyExpense['amount'] ?? 0);
        $currencyId = self::nullableInt($legacyExpense['currency_id'] ?? null);
        $status = self::normalizeLegacyExpenseStatus($legacyExpense['status'] ?? null);
        $primaryAccountId = self::nullableInt($legacyExpense['account_id'] ?? null) ?? $fallbackAccountId ?? $clearingAccountId;

        return [
            'workflow_type' => 'legacy_expense',
            'status'        => $status,
            'occurred_on'   => (string) ($legacyExpense['date'] ?? date('Y-m-d')),
            'actor_user_id' => self::nullableInt($legacyExpense['created_by'] ?? ($legacyExpense['user_id'] ?? null)),
            'approved_by'   => self::nullableInt($legacyExpense['approved_by'] ?? null),
            'source_table'  => 'finance_expenses',
            'source_id'     => self::nullableInt($legacyExpense['id'] ?? null),
            'currency_id'   => $currencyId,
            'payment_type_id' => self::nullableInt($legacyExpense['payment_type_id'] ?? null),
            'rate_to_base'  => self::normalizeRate($legacyExpense['exchange_rate'] ?? 1),
            'notes'         => self::legacyNote('finance_expenses', $legacyExpense),
            'posted_at'     => $status === 'posted' ? date('Y-m-d H:i:s') : null,
            'lines'         => [
                [
                    'line_number'   => 1,
                    'account_id'    => $primaryAccountId,
                    'side'          => 'credit',
                    'amount'        => $amount,
                    'currency_id'   => $currencyId,
                    'rate_to_base'  => self::normalizeRate($legacyExpense['exchange_rate'] ?? 1),
                    'company_id'    => self::nullableInt($legacyExpense['company_id'] ?? null),
                    'project_id'    => self::nullableInt($legacyExpense['project_id'] ?? null),
                    'department_id' => self::nullableInt($legacyExpense['department_id'] ?? null),
                    'description'   => $legacyExpense['description'] ?? null,
                ],
                [
                    'line_number'   => 2,
                    'account_id'    => $clearingAccountId,
                    'side'          => 'debit',
                    'amount'        => $amount,
                    'currency_id'   => $currencyId,
                    'rate_to_base'  => self::normalizeRate($legacyExpense['exchange_rate'] ?? 1),
                    'category_id'   => self::nullableInt($legacyExpense['category_id'] ?? null),
                    'company_id'    => self::nullableInt($legacyExpense['company_id'] ?? null),
                    'project_id'    => self::nullableInt($legacyExpense['project_id'] ?? null),
                    'department_id' => self::nullableInt($legacyExpense['department_id'] ?? null),
                    'description'   => $legacyExpense['description'] ?? null,
                ],
            ],
        ];
    }

    /**
     * @param array<string, mixed> $movement
     *
     * @return array<string, mixed>
     */
    public function postMovement(array $movement): array
    {
        $lines = [];
        foreach (($movement['lines'] ?? []) as $index => $line) {
            if (! is_array($line)) {
                continue;
            }

            $lines[] = $this->normalizeLine($line, $index + 1, self::nullableInt($movement['currency_id'] ?? null), $movement['rate_to_base'] ?? 1);
        }

        $summary = self::summarizeBalanceCheck($lines);
        if (! $summary['is_balanced']) {
            throw new InvalidArgumentException('Movement lines must be balanced before posting.');
        }

        $movementRow = [
            'workflow_type'  => (string) ($movement['workflow_type'] ?? 'manual'),
            'status'         => (string) ($movement['status'] ?? 'posted'),
            'occurred_on'    => (string) ($movement['occurred_on'] ?? date('Y-m-d')),
            'actor_user_id'  => self::nullableInt($movement['actor_user_id'] ?? null),
            'approved_by'    => self::nullableInt($movement['approved_by'] ?? null),
            'source_table'   => $movement['source_table'] ?? null,
            'source_id'      => self::nullableInt($movement['source_id'] ?? null),
            'lead_id'        => self::nullableInt($movement['lead_id'] ?? null),
            'currency_id'    => self::nullableInt($movement['currency_id'] ?? ($lines[0]['currency_id'] ?? null)),
            'payment_type_id'=> self::nullableInt($movement['payment_type_id'] ?? null),
            'rate_to_base'   => self::normalizeRate($movement['rate_to_base'] ?? 1),
            'reversal_of_id' => self::nullableInt($movement['reversal_of_id'] ?? null),
            'notes'          => $movement['notes'] ?? null,
            'posted_at'      => ($movement['status'] ?? 'posted') === 'posted'
                ? ($movement['posted_at'] ?? date('Y-m-d H:i:s'))
                : ($movement['posted_at'] ?? null),
        ];

        $db = db_connect();
        $db->transBegin();

        if ($this->movementModel->insert($movementRow) === false) {
            $db->transRollback();

            throw new RuntimeException('Unable to insert finance movement.');
        }

        $movementId = (int) $this->movementModel->getInsertID();
        $accountIds = [];

        foreach ($lines as $line) {
            $line['movement_id'] = $movementId;

            if ($this->lineModel->insert($line) === false) {
                $db->transRollback();

                throw new RuntimeException('Unable to insert finance movement line.');
            }

            if ($line['account_id'] !== null) {
                $accountIds[] = (int) $line['account_id'];
            }
        }

        if ($movementRow['status'] === 'posted') {
            $this->refreshAccountBalances($accountIds);
        }

        if (! $db->transStatus()) {
            $db->transRollback();

            throw new RuntimeException('Finance ledger transaction failed.');
        }

        $db->transCommit();

        return [
            'movement'      => $this->movementModel->find($movementId),
            'lines'         => $this->lineModel->where('movement_id', $movementId)->orderBy('line_number', 'ASC')->findAll(),
            'balance_check' => $summary,
        ];
    }

    /**
     * @param array<int, int> $accountIds
     */
    public function refreshAccountBalances(array $accountIds): void
    {
        $uniqueIds = array_values(array_unique(array_filter(array_map('intval', $accountIds))));

        foreach ($uniqueIds as $accountId) {
            $account = $this->accountModel->find($accountId);
            if (! is_array($account)) {
                continue;
            }

            $openingBalance = $account['initial_balance'] ?? ($account['balance'] ?? '0.00');
            $row = $this->lineModel
                ->select(
                    "COALESCE(SUM(CASE WHEN finance_movement_lines.side = 'debit' THEN finance_movement_lines.amount ELSE -finance_movement_lines.amount END), 0) AS net_total",
                    false
                )
                ->join('finance_movements', 'finance_movements.id = finance_movement_lines.movement_id')
                ->where('finance_movement_lines.account_id', $accountId)
                ->where('finance_movements.status', 'posted')
                ->get()
                ->getFirstRow('array');

            $currentBalance = self::formatDecimal(
                self::decimalToInt($openingBalance, self::AMOUNT_SCALE)
                + self::decimalToInt($row['net_total'] ?? 0, self::AMOUNT_SCALE),
                self::AMOUNT_SCALE
            );

            $this->accountModel->update($accountId, [
                'balance'         => $currentBalance,
                'current_balance' => $currentBalance,
            ]);
        }
    }

    public function reverseMovement(int $movementId, int $actorUserId, ?string $occurredOn = null): array
    {
        $movement = $this->movementModel->find($movementId);
        if (! is_array($movement)) {
            throw new InvalidArgumentException('Movement not found for reversal.');
        }

        $movement['lines'] = $this->lineModel
            ->where('movement_id', $movementId)
            ->orderBy('line_number', 'ASC')
            ->findAll();

        return $this->postMovement(self::reverseMovementPayload($movement, $actorUserId, $occurredOn));
    }

    /**
     * @return array<string, int>
     */
    public function backfillLegacyRecords(int $clearingAccountId, ?int $fallbackExpenseAccountId = null): array
    {
        $summary = [
            'transactions' => 0,
            'expenses'     => 0,
            'skipped'      => 0,
        ];

        $db = db_connect();

        if ($db->tableExists('finance_transactions')) {
            $transactions = $db->table('finance_transactions')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($transactions as $transaction) {
                $sourceId = self::nullableInt($transaction['id'] ?? null);
                if ($sourceId === null || $this->movementExists('finance_transactions', $sourceId)) {
                    $summary['skipped']++;
                    continue;
                }

                $this->postMovement(self::mapLegacyTransaction($transaction, $clearingAccountId));
                $summary['transactions']++;
            }
        }

        if ($db->tableExists('finance_expenses')) {
            $expenses = $db->table('finance_expenses')
                ->orderBy('id', 'ASC')
                ->get()
                ->getResultArray();

            foreach ($expenses as $expense) {
                $sourceId = self::nullableInt($expense['id'] ?? null);
                if ($sourceId === null || $this->movementExists('finance_expenses', $sourceId)) {
                    $summary['skipped']++;
                    continue;
                }

                $this->postMovement(self::mapLegacyExpense($expense, $clearingAccountId, $fallbackExpenseAccountId));
                $summary['expenses']++;
            }
        }

        return $summary;
    }

    private function movementExists(string $sourceTable, int $sourceId): bool
    {
        return $this->movementModel
            ->where('source_table', $sourceTable)
            ->where('source_id', $sourceId)
            ->first() !== null;
    }

    /**
     * @param array<string, mixed> $line
     *
     * @return array<string, mixed>
     */
    private function normalizeLine(array $line, int $lineNumber, ?int $defaultCurrencyId, $defaultRate): array
    {
        return [
            'line_number'   => self::nullableInt($line['line_number'] ?? null) ?? $lineNumber,
            'account_id'    => self::nullableInt($line['account_id'] ?? null),
            'side'          => self::normalizeSide($line['side'] ?? null),
            'amount'        => self::normalizeAmount($line['amount'] ?? 0),
            'currency_id'   => self::nullableInt($line['currency_id'] ?? $defaultCurrencyId),
            'rate_to_base'  => self::normalizeRate($line['rate_to_base'] ?? $defaultRate),
            'category_id'   => self::nullableInt($line['category_id'] ?? null),
            'company_id'    => self::nullableInt($line['company_id'] ?? null),
            'project_id'    => self::nullableInt($line['project_id'] ?? null),
            'department_id' => self::nullableInt($line['department_id'] ?? null),
            'description'   => $line['description'] ?? null,
        ];
    }

    private static function normalizeLegacyExpenseStatus($status): string
    {
        $normalized = strtolower(trim((string) $status));

        if ($normalized === 'approved') {
            return 'posted';
        }

        if ($normalized === 'rejected') {
            return 'rejected';
        }

        return 'draft';
    }

    private static function normalizeSide($side): string
    {
        $normalized = strtolower(trim((string) $side));

        return $normalized === 'credit' ? 'credit' : 'debit';
    }

    private static function flipSide($side): string
    {
        return self::normalizeSide($side) === 'debit' ? 'credit' : 'debit';
    }

    private static function normalizeAmount($amount): string
    {
        return self::formatDecimal(self::decimalToInt($amount, self::AMOUNT_SCALE), self::AMOUNT_SCALE);
    }

    private static function normalizeRate($rate): string
    {
        return self::formatDecimal(self::decimalToInt($rate, self::RATE_SCALE), self::RATE_SCALE);
    }

    private static function nullableInt($value): ?int
    {
        if ($value === null || $value === '' || ! is_numeric((string) $value)) {
            return null;
        }

        return (int) $value;
    }

    private static function decimalToInt($value, int $scale): int
    {
        $normalized = trim((string) $value);
        if ($normalized === '') {
            return 0;
        }

        $negative = false;
        if (substr($normalized, 0, 1) === '-') {
            $negative = true;
            $normalized = substr($normalized, 1);
        }

        $normalized = preg_replace('/[^0-9.]/', '', $normalized) ?? '0';
        if ($normalized === '') {
            $normalized = '0';
        }

        $parts = explode('.', $normalized, 2);
        $whole = (int) ($parts[0] ?? 0);
        $fraction = str_pad(substr($parts[1] ?? '', 0, $scale), $scale, '0');
        $factor = 10 ** $scale;
        $result = ($whole * $factor) + (int) $fraction;

        return $negative ? -$result : $result;
    }

    private static function formatDecimal(int $value, int $scale): string
    {
        $negative = $value < 0;
        $absolute = abs($value);
        $factor = 10 ** $scale;
        $whole = intdiv($absolute, $factor);
        $fraction = $absolute % $factor;

        return sprintf(
            '%s%d.%s',
            $negative ? '-' : '',
            $whole,
            str_pad((string) $fraction, $scale, '0', STR_PAD_LEFT)
        );
    }

    /**
     * @param array<string, mixed> $row
     */
    private static function legacyNote(string $sourceTable, array $row): string
    {
        $id = self::nullableInt($row['id'] ?? null);
        $description = trim((string) ($row['description'] ?? ''));

        $base = 'Backfilled from ' . $sourceTable . ($id !== null ? ' #' . $id : '');

        return $description === '' ? $base : $base . ' - ' . $description;
    }
}
