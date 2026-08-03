<!DOCTYPE html>
<html lang="es"><head><meta charset="utf-8"><title><?= esc($report['sheet_title'] ?? 'Hoja contable') ?></title>
<style>body{font-family:Arial,sans-serif;margin:24px}table{border-collapse:collapse;width:100%}th,td{border:1px solid #ccc;padding:8px}th{background:#f5f5f5}.total{font-weight:bold}</style>
</head><body>
<h1><?= esc($report['sheet_title'] ?? 'Hoja contable') ?></h1>
<p>Periodo: <?= esc($report['date_from'] ?? '') ?> — <?= esc($report['date_to'] ?? '') ?></p>
<h2>Ingresos</h2>
<table><tr><th>Concepto</th><th>Total USD</th></tr>
<?php foreach ($report['income_rows'] ?? [] as $row): ?>
<tr><td><?= esc($row['name']) ?></td><td><?= esc($row['total']) ?></td></tr>
<?php endforeach; ?>
<tr class="total"><td>Total ingresos</td><td><?= esc($report['total_income'] ?? '0') ?></td></tr>
</table>
<h2>Egresos</h2>
<table><tr><th>Concepto</th><th>Total USD</th></tr>
<?php foreach ($report['expense_rows'] ?? [] as $row): ?>
<tr><td><?= esc($row['name']) ?></td><td><?= esc($row['total']) ?></td></tr>
<?php endforeach; ?>
<tr class="total"><td>Total egresos</td><td><?= esc($report['total_expense'] ?? '0') ?></td></tr>
</table>
<h2>Resultado: <?= esc($report['net_result'] ?? '0') ?> (<?= ($report['is_profit'] ?? true) ? 'GANANCIA' : 'PÉRDIDA' ?>)</h2>
</body></html>
