<?php

declare(strict_types=1);

namespace App\Libraries;

use Config\FinanceMenu;

class FinanceReportService
{
    private const AMOUNT_SCALE = 2;

    private function companyClause(?int $companyId, string $alias = 'ml'): string
    {
        if ($companyId === null) {
            return '';
        }

        return " AND ({$alias}.company_id = " . (int) $companyId . " OR {$alias}.company_id IS NULL)";
    }

    public function getProfitLoss(string $dateFrom, string $dateTo, ?int $companyId = null): array
    {
        $db = \Config\Database::connect();
        $companySql = $this->companyClause($companyId);

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
              {$companySql}
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
              {$companySql}
            GROUP BY c.movement_type, c.name
            ORDER BY c.name
        ", [$dateFrom, $dateTo])->getResultArray();

        $incomeTotal = array_sum(array_column($income, 'total'));
        $expenseTotal = array_sum(array_column($expenses, 'total'));

        return [
            'date_from'    => $dateFrom,
            'date_to'      => $dateTo,
            'company_id'   => $companyId,
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
            ->select('m.id, ml.amount, ml.description, ml.account_id, ml.currency_id, ml.category_id, m.occurred_on, m.workflow_type, m.status, m.notes, m.lead_id, c.name AS category_name, c.movement_type, l.name AS lead_name, l.phone AS lead_phone')
            ->join('finance_movements m', 'm.id = ml.movement_id')
            ->join('finance_categories c', 'c.id = ml.category_id')
            ->join('leads l', 'l.id = m.lead_id', 'left')
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
            ->select('m.id, ml.amount, ml.description, ml.account_id, ml.currency_id, ml.category_id, m.occurred_on, m.workflow_type, m.status, m.notes, m.lead_id, c.name AS category_name, c.movement_type, l.name AS lead_name, l.phone AS lead_phone')
            ->join('finance_movements m', 'm.id = ml.movement_id')
            ->join('finance_categories c', 'c.id = ml.category_id')
            ->join('leads l', 'l.id = m.lead_id', 'left')
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
                   ml.amount, ml.description AS line_description,
                   l.name AS lead_name, l.phone AS lead_phone
            FROM finance_movements m
            JOIN finance_movement_lines ml ON ml.movement_id = m.id AND ml.line_number = 1
            LEFT JOIN finance_categories c ON c.id = ml.category_id
            LEFT JOIN leads l ON l.id = m.lead_id
            WHERE m.status = 'pending_approval'
            ORDER BY m.occurred_on DESC, m.id DESC
        ")->getResultArray();
    }

    public function getAccountingSheet(string $dateFrom, string $dateTo, ?int $companyId = null): array
    {
        $report = $this->getProfitLoss($dateFrom, $dateTo, $companyId);

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

    /**
     * Dashboard ejecutivo: KPIs, tendencia 6 meses, comisiones, alertas.
     *
     * @return array<string, mixed>
     */
    public function getExecutiveDashboard(?int $companyId = null): array
    {
        $dateFrom = date('Y-m-01');
        $dateTo = date('Y-m-t');
        $current = $this->getProfitLoss($dateFrom, $dateTo, $companyId);

        $trend = [];
        for ($i = 5; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-{$i} months"));
            $monthEnd = date('Y-m-t', strtotime("-{$i} months"));
            $pl = $this->getProfitLoss($monthStart, $monthEnd, $companyId);
            $trend[] = [
                'label'   => date('M Y', strtotime($monthStart)),
                'income'  => (float) $pl['total_income'],
                'expense' => (float) $pl['total_expense'],
                'net'     => (float) $pl['net_result'],
            ];
        }

        $alerts = $this->buildAlerts($trend, $current);
        $commissions = $this->getCommissionSummary($dateFrom, $dateTo);
        $pendingCount = count($this->getPendingMovements());
        $otrosTotal = $this->sumMovementType($current, 'otros', 'income')
            + $this->sumMovementType($current, 'otros_servicios', 'expense');

        return [
            'period'           => ['from' => $dateFrom, 'to' => $dateTo],
            'company_id'       => $companyId,
            'kpis'             => [
                'total_income'  => $current['total_income'],
                'total_expense' => $current['total_expense'],
                'net_result'    => $current['net_result'],
                'is_profit'     => $current['is_profit'],
                'pending_count' => $pendingCount,
                'properties_sold' => $commissions['properties_count'],
                'commissions_due' => $commissions['total_net_payable'],
            ],
            'trend'            => $trend,
            'alerts'           => $alerts,
            'commissions'      => $commissions,
            'otros_highlight'  => number_format($otrosTotal, 2, '.', ''),
            'top_income_types' => array_slice($current['income'], 0, 5),
            'top_expense_types'=> array_slice($current['expenses'], 0, 5),
        ];
    }

    /**
     * @param list<array<string, mixed>> $trend
     * @param array<string, mixed> $current
     *
     * @return list<array<string, string>>
     */
    private function buildAlerts(array $trend, array $current): array
    {
        $alerts = [];
        $redMonths = 0;

        foreach ($trend as $row) {
            if (($row['net'] ?? 0) < 0) {
                $redMonths++;
            }
        }

        if ($redMonths >= 3) {
            $alerts[] = [
                'level'   => 'danger',
                'message' => "Hay {$redMonths} meses con resultado negativo en los últimos 6 meses.",
            ];
        }

        $otrosIncome = $this->sumMovementType($current, 'otros', 'income');
        if ($otrosIncome > 5000) {
            $alerts[] = [
                'level'   => 'warning',
                'message' => 'Ingresos en categoría "Otros" superan $5,000 — conviene desglosar.',
            ];
        }

        if ((float) ($current['total_income'] ?? 0) === 0.0 && (float) ($current['total_expense'] ?? 0) > 0) {
            $alerts[] = [
                'level'   => 'danger',
                'message' => 'Hay egresos registrados pero ingresos por venta en cero este mes.',
            ];
        }

        return $alerts;
    }

    /**
     * @param array<string, mixed> $report
     */
    private function sumMovementType(array $report, string $movementType, string $side): float
    {
        $rows = $side === 'income' ? ($report['income'] ?? []) : ($report['expenses'] ?? []);

        foreach ($rows as $row) {
            if (($row['movement_type'] ?? '') === $movementType) {
                return (float) ($row['total'] ?? 0);
            }
        }

        return 0.0;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCommissionSummary(string $dateFrom, string $dateTo): array
    {
        $db = \Config\Database::connect();

        if (! $db->tableExists('commission_properties')) {
            return [
                'properties_count'  => 0,
                'total_net_income'=> '0.00',
                'total_net_payable'=> '0.00',
                'by_agent'        => [],
            ];
        }

        $properties = $db->query("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(net_income), 0) AS net_income
            FROM commission_properties
            WHERE sale_date >= ? AND sale_date <= ?
        ", [$dateFrom, $dateTo])->getRowArray();

        $byAgent = [];
        if ($db->tableExists('commission_settlement_details')) {
            try {
                $byAgent = $db->query("
                    SELECT csd.user_id,
                           u.full_name AS agent_name,
                           SUM(csd.net_payable) AS net_payable
                    FROM commission_settlement_details csd
                    JOIN users u ON u.id = csd.user_id
                    JOIN commission_settlements cs ON cs.id = csd.settlement_id
                    WHERE cs.period_start >= ? AND cs.period_end <= ?
                    GROUP BY csd.user_id, u.full_name
                    ORDER BY net_payable DESC
                    LIMIT 10
                ", [$dateFrom, $dateTo])->getResultArray();
            } catch (\Throwable $e) {
                log_message('warning', 'FinanceReportService::getCommissionSummary — ' . $e->getMessage());
            }
        }

        $totalPayable = array_sum(array_column($byAgent, 'net_payable'));

        return [
            'properties_count'   => (int) ($properties['cnt'] ?? 0),
            'total_net_income'   => number_format((float) ($properties['net_income'] ?? 0), 2, '.', ''),
            'total_net_payable'  => number_format((float) $totalPayable, 2, '.', ''),
            'by_agent'           => $byAgent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function closePeriod(int $year, int $month, ?int $companyId, int $closedBy): array
    {
        $dateFrom = sprintf('%04d-%02d-01', $year, $month);
        $dateTo = date('Y-m-t', strtotime($dateFrom));
        $report = $this->getAccountingSheet($dateFrom, $dateTo, $companyId);

        $db = \Config\Database::connect();
        $payload = [
            'company_id'    => $companyId,
            'period_year'   => $year,
            'period_month'  => $month,
            'total_income'  => $report['total_income'],
            'total_expense' => $report['total_expense'],
            'net_result'    => $report['net_result'],
            'snapshot_json' => json_encode($report, JSON_UNESCAPED_UNICODE),
            'closed_by'     => $closedBy,
            'closed_at'     => date('Y-m-d H:i:s'),
            'updated_at'    => date('Y-m-d H:i:s'),
        ];

        $existing = $db->table('finance_period_closes')
            ->where('period_year', $year)
            ->where('period_month', $month)
            ->where('company_id', $companyId)
            ->get()
            ->getFirstRow('array');

        if ($existing) {
            $db->table('finance_period_closes')->where('id', $existing['id'])->update($payload);
            $id = (int) $existing['id'];
        } else {
            $payload['created_at'] = date('Y-m-d H:i:s');
            $db->table('finance_period_closes')->insert($payload);
            $id = (int) $db->insertID();
        }

        return array_merge(['id' => $id], $payload);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function listPeriodCloses(?int $companyId = null): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('finance_period_closes')) {
            return [];
        }

        $builder = $db->table('finance_period_closes')->orderBy('period_year', 'DESC')->orderBy('period_month', 'DESC');
        if ($companyId !== null) {
            $builder->where('company_id', $companyId);
        }

        return $builder->get()->getResultArray();
    }

    /**
     * Reporte estadístico completo (módulo dedicado, no dashboard).
     *
     * @return array<string, mixed>
     */
    public function getStatisticsReport(string $dateFrom, string $dateTo, ?int $companyId = null): array
    {
        $pl = $this->getProfitLoss($dateFrom, $dateTo, $companyId);

        return [
            'period'           => ['from' => $dateFrom, 'to' => $dateTo],
            'company_id'       => $companyId,
            'summary'          => [
                'total_income'  => (float) $pl['total_income'],
                'total_expense' => (float) $pl['total_expense'],
                'net_result'    => (float) $pl['net_result'],
                'is_profit'     => $pl['is_profit'],
                'margin_pct'    => $this->marginPercent((float) $pl['total_income'], (float) $pl['net_result']),
            ],
            'income_by_type'   => $this->formatBreakdownRows($pl['income']),
            'expense_by_type'  => $this->formatBreakdownRows($pl['expenses']),
            'sector_sales'     => $this->getSectorSales($dateFrom, $dateTo, $companyId),
            'monthly_trend'    => $this->getMonthlyTrend($dateFrom, $dateTo, $companyId, 12),
            'commissions'      => $this->getCommissionStatistics($dateFrom, $dateTo),
            'advances'         => $this->getAdvanceStatistics($dateFrom, $dateTo),
            'quotas'           => $this->getQuotaStatistics($dateFrom, $dateTo, $companyId),
            'pending_approvals'=> count($this->getPendingMovements()),
            'alerts'           => $this->buildAlerts(
                $this->getMonthlyTrend($dateFrom, $dateTo, $companyId, 6),
                $pl
            ),
        ];
    }

    /**
     * @param list<array<string, mixed>> $rows
     *
     * @return list<array<string, mixed>>
     */
    private function formatBreakdownRows(array $rows): array
    {
        $total = array_sum(array_column($rows, 'total'));
        $result = [];

        foreach ($rows as $row) {
            $amount = (float) ($row['total'] ?? 0);
            $result[] = [
                'movement_type' => $row['movement_type'] ?? '',
                'name'          => $row['name'] ?? '',
                'total'         => round($amount, 2),
                'pct'           => $total > 0 ? round(($amount / $total) * 100, 1) : 0,
            ];
        }

        usort($result, static fn ($a, $b) => $b['total'] <=> $a['total']);

        return $result;
    }

    private function marginPercent(float $income, float $net): float
    {
        if ($income <= 0) {
            return 0.0;
        }

        return round(($net / $income) * 100, 1);
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getSectorSales(string $dateFrom, string $dateTo, ?int $companyId = null): array
    {
        $types = [
            'ventas_primaria'    => 'Ventas primaria',
            'ventas_secundarias' => 'Ventas secundarias',
            'alquiler'           => 'Alquiler',
            'cuotas_financiamiento' => 'Cuotas financiamiento',
        ];

        $pl = $this->getProfitLoss($dateFrom, $dateTo, $companyId);
        $byType = [];
        foreach ($pl['income'] as $row) {
            $byType[$row['movement_type'] ?? ''] = (float) ($row['total'] ?? 0);
        }

        $result = [];
        foreach ($types as $key => $label) {
            $result[] = [
                'key'   => $key,
                'label' => $label,
                'total' => round($byType[$key] ?? 0, 2),
            ];
        }

        return $result;
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function getMonthlyTrend(string $dateFrom, string $dateTo, ?int $companyId, int $months = 6): array
    {
        $end = strtotime($dateTo);
        $start = strtotime($dateFrom);
        $spanMonths = max(1, (int) round(($end - $start) / (86400 * 30)));

        $count = min($months, max($spanMonths, $months));
        $trend = [];

        for ($i = $count - 1; $i >= 0; $i--) {
            $monthStart = date('Y-m-01', strtotime("-{$i} months", $end));
            $monthEnd = date('Y-m-t', strtotime($monthStart));
            $pl = $this->getProfitLoss($monthStart, $monthEnd, $companyId);
            $trend[] = [
                'label'   => date('M Y', strtotime($monthStart)),
                'income'  => (float) $pl['total_income'],
                'expense' => (float) $pl['total_expense'],
                'net'     => (float) $pl['net_result'],
            ];
        }

        return $trend;
    }

    /**
     * @return array<string, mixed>
     */
    public function getCommissionStatistics(string $dateFrom, string $dateTo): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('commission_properties')) {
            return ['properties' => 0, 'net_income' => 0, 'by_agent' => [], 'settlements' => 0];
        }

        $props = $db->query("
            SELECT COUNT(*) AS cnt, COALESCE(SUM(net_income), 0) AS net_income,
                   COALESCE(SUM(sale_price), 0) AS sale_volume
            FROM commission_properties
            WHERE sale_date >= ? AND sale_date <= ? AND status != 'cancelled'
        ", [$dateFrom, $dateTo])->getRowArray();

        $byAgent = $this->getCommissionSummary($dateFrom, $dateTo)['by_agent'] ?? [];

        $settlements = 0;
        if ($db->tableExists('commission_settlements')) {
            $settlements = (int) $db->table('commission_settlements')
                ->where('period_start >=', $dateFrom)
                ->where('period_end <=', $dateTo)
                ->where('status', 'finalized')
                ->countAllResults();
        }

        return [
            'properties'   => (int) ($props['cnt'] ?? 0),
            'net_income'   => round((float) ($props['net_income'] ?? 0), 2),
            'sale_volume'  => round((float) ($props['sale_volume'] ?? 0), 2),
            'settlements'  => $settlements,
            'by_agent'     => $byAgent,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getAdvanceStatistics(string $dateFrom, string $dateTo): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('commission_advances')) {
            return ['total' => 0, 'pending' => 0, 'settled' => 0, 'by_agent' => []];
        }

        $rows = $db->query("
            SELECT ca.user_id, u.full_name AS agent_name,
                   SUM(ca.amount) AS total,
                   SUM(CASE WHEN ca.settled = 0 THEN ca.amount ELSE 0 END) AS pending,
                   SUM(CASE WHEN ca.settled = 1 THEN ca.amount ELSE 0 END) AS settled,
                   COUNT(*) AS count
            FROM commission_advances ca
            LEFT JOIN users u ON u.id = ca.user_id
            WHERE ca.advance_date >= ? AND ca.advance_date <= ?
            GROUP BY ca.user_id, u.full_name
            ORDER BY total DESC
        ", [$dateFrom, $dateTo])->getResultArray();

        $total = array_sum(array_column($rows, 'total'));
        $pending = array_sum(array_column($rows, 'pending'));

        foreach ($rows as &$row) {
            $row['total'] = round((float) $row['total'], 2);
            $row['pending'] = round((float) $row['pending'], 2);
            $row['settled'] = round((float) $row['settled'], 2);
        }

        return [
            'total'    => round((float) $total, 2),
            'pending'  => round((float) $pending, 2),
            'settled'  => round(array_sum(array_column($rows, 'settled')), 2),
            'by_agent' => $rows,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function getQuotaStatistics(string $dateFrom, string $dateTo, ?int $companyId = null): array
    {
        $db = \Config\Database::connect();
        if (! $db->tableExists('finance_quotas')) {
            return ['received' => 0, 'delivered' => 0, 'received_total' => 0, 'delivered_total' => 0];
        }

        $builder = $db->table('finance_quotas')
            ->select('type, COUNT(*) AS cnt, COALESCE(SUM(amount_usd), SUM(amount)) AS total')
            ->where('receipt_date >=', $dateFrom)
            ->where('receipt_date <=', $dateTo)
            ->groupBy('type');

        if ($companyId !== null) {
            $builder->groupStart()->where('company_id', $companyId)->orWhere('company_id', null)->groupEnd();
        }

        $rows = $builder->get()->getResultArray();
        $stats = ['received' => 0, 'delivered' => 0, 'received_total' => 0.0, 'delivered_total' => 0.0];

        foreach ($rows as $row) {
            if (($row['type'] ?? '') === 'received') {
                $stats['received'] = (int) $row['cnt'];
                $stats['received_total'] = round((float) $row['total'], 2);
            } elseif (($row['type'] ?? '') === 'delivered') {
                $stats['delivered'] = (int) $row['cnt'];
                $stats['delivered_total'] = round((float) $row['total'], 2);
            }
        }

        return $stats;
    }
}
