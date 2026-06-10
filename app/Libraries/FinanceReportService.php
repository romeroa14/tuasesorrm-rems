<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\FinanceMenu;

class FinanceReportService
{
    private const AMOUNT_SCALE = 2;

    public function getProfitLoss(string $dateFrom, string $dateTo): array
    {
        $db = \Config\Database::connect();

        $income = $db->query("
            SELECT c.movement_type, c.name, COALESCE(SUM(ml.amount), 0) AS total
            FROM finance_movement_lines ml
            JOIN finance_movements m ON m.id = ml.movement_id
            JOIN finance_categories c ON c.id = ml.category_id
            WHERE m.status = 'posted'
              AND ml.line_number = 1
              AND c.type = 'income'
              AND m.occurred_on >= ?
              AND m.occurred_on <= ?
            GROUP BY c.movement_type, c.name
            ORDER BY c.name
        ", [$dateFrom, $dateTo])->getResultArray();

        $expenses = $db->query("
            SELECT c.movement_type, c.name, COALESCE(SUM(ml.amount), 0) AS total
            FROM finance_movement_lines ml
            JOIN finance_movements m ON m.id = ml.movement_id
            JOIN finance_categories c ON c.id = ml.category_id
            WHERE m.status = 'posted'
              AND ml.line_number = 1
              AND c.type = 'expense'
              AND m.occurred_on >= ?
              AND m.occurred_on <= ?
            GROUP BY c.movement_type, c.name
            ORDER BY c.name
        ", [$dateFrom, $dateTo])->getResultArray();

        $incomeTotal = array_sum(array_column($income, 'total'));
        $expenseTotal = array_sum(array_column($expenses, 'total'));

        return [
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'income'       => $income,
            'expenses'     => $expenses,
            'total_income' => number_format((float) $incomeTotal, 2, '.', ''),
            'total_expense'=> number_format((float) $expenseTotal, 2, '.', ''),
            'net_result'   => number_format((float) ($incomeTotal - $expenseTotal), 2, '.', ''),
            'is_profit'    => $incomeTotal >= $expenseTotal,
        ];
    }

    public function getIncomeByType(?string $movementType = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('finance_movement_lines ml')
            ->select('m.id, ml.amount, ml.description, ml.account_id, ml.currency_id, ml.category_id, m.occurred_on, m.workflow_type, m.status, m.notes, c.name AS category_name, c.movement_type')
            ->join('finance_movements m', 'm.id = ml.movement_id')
            ->join('finance_categories c', 'c.id = ml.category_id')
            ->where('ml.line_number', 1)
            ->where('c.type', 'income');

        if ($movementType) {
            $builder->where('c.movement_type', $movementType);
        }
        if ($dateFrom) {
            $builder->where('m.occurred_on >=', $dateFrom);
        }
        if ($dateTo) {
            $builder->where('m.occurred_on <=', $dateTo);
        }

        return $builder->orderBy('m.occurred_on', 'DESC')->get()->getResultArray();
    }

    public function getExpenseByType(?string $movementType = null, ?string $dateFrom = null, ?string $dateTo = null): array
    {
        $db = \Config\Database::connect();

        $builder = $db->table('finance_movement_lines ml')
            ->select('m.id, ml.amount, ml.description, ml.account_id, ml.currency_id, ml.category_id, m.occurred_on, m.workflow_type, m.status, m.notes, c.name AS category_name, c.movement_type')
            ->join('finance_movements m', 'm.id = ml.movement_id')
            ->join('finance_categories c', 'c.id = ml.category_id')
            ->where('ml.line_number', 1)
            ->where('c.type', 'expense');

        if ($movementType) {
            $builder->where('c.movement_type', $movementType);
        }
        if ($dateFrom) {
            $builder->where('m.occurred_on >=', $dateFrom);
        }
        if ($dateTo) {
            $builder->where('m.occurred_on <=', $dateTo);
        }

        return $builder->orderBy('m.occurred_on', 'DESC')->get()->getResultArray();
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getPendingMovements(): array
    {
        $db = \Config\Database::connect();

        return $db->query("
            SELECT m.*, c.name AS category_name, c.movement_type, c.type AS category_type,
                   ml.amount, ml.description AS line_description
            FROM finance_movements m
            JOIN finance_movement_lines ml ON ml.movement_id = m.id AND ml.line_number = 1
            LEFT JOIN finance_categories c ON c.id = ml.category_id
            WHERE m.status = 'pending_approval'
            ORDER BY m.occurred_on DESC, m.id DESC
        ")->getResultArray();
    }

    public function getAccountingSheet(string $dateFrom, string $dateTo): array
    {
        $report = $this->getProfitLoss($dateFrom, $dateTo);

        $incomeTypes = FinanceMenu::incomeTypes();
        $expenseTypes = FinanceMenu::expenseTypes();

        $incomeByType = [];
        foreach ($report['income'] as $row) {
            $incomeByType[$row['movement_type'] ?? ''] = $row;
        }

        $expenseByType = [];
        foreach ($report['expenses'] as $row) {
            $expenseByType[$row['movement_type'] ?? ''] = $row;
        }

        $incomeRows = [];
        foreach ($incomeTypes as $key => $label) {
            $incomeRows[] = [
                'movement_type' => $key,
                'name'          => $label,
                'total'         => number_format((float) ($incomeByType[$key]['total'] ?? 0), 2, '.', ''),
            ];
        }

        $expenseRows = [];
        foreach ($expenseTypes as $key => $label) {
            $expenseRows[] = [
                'movement_type' => $key,
                'name'          => $label,
                'total'         => number_format((float) ($expenseByType[$key]['total'] ?? 0), 2, '.', ''),
            ];
        }

        return array_merge($report, [
            'income_rows'  => $incomeRows,
            'expense_rows' => $expenseRows,
            'sheet_title'  => 'Hoja Contable — Ganancias y Pérdidas',
        ]);
    }
}
