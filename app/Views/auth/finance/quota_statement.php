<?php
/** @var array<string, mixed> $plan */
/** @var list<array<string, mixed>> $payments */
$plan = $plan ?? [];
$payments = $payments ?? [];
$installments = $plan['installment_schedule'] ?? [];
$totals = $plan['totals'] ?? ['scheduled' => 0, 'paid' => 0, 'pending' => 0];
$forPdf = $forPdf ?? false;

$fmt = static function ($value): string {
    return number_format((float) $value, 2, ',', '.');
};

$statusLabels = [
    'pending' => 'Pendiente',
    'partial' => 'Parcial',
    'paid'    => 'Pagada',
    'overdue' => 'Vencida',
];
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="utf-8">
    <title><?= esc($statement_title ?? 'Estado de cuenta') ?></title>
    <style>
        @page { margin: 14mm 16mm; }
        body { font-family: "DejaVu Serif", "Times New Roman", Times, serif; font-size: 11pt; color: #222; margin: 0; }
        .toolbar { margin-bottom: 16px; text-align: right; }
        .title { text-align: center; font-size: 18pt; font-weight: bold; margin: 0 0 8px; }
        .subtitle { text-align: center; font-size: 10pt; color: #555; margin-bottom: 20px; }
        .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; margin-bottom: 16px; }
        .field label { font-weight: bold; display: inline-block; min-width: 120px; }
        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 10pt; }
        th, td { border: 1px solid #333; padding: 5px 6px; }
        th { background: #f0f0f0; }
        td.num { text-align: right; white-space: nowrap; }
        .summary { margin-top: 14px; text-align: right; font-weight: bold; }
        h3 { font-size: 12pt; margin: 18px 0 8px; }
        @media print { .toolbar { display: none; } }
    </style>
</head>
<body>
<?php if (! $forPdf): ?>
<div class="toolbar">
    <button type="button" onclick="window.print()">Imprimir</button>
    <a href="<?= esc(base_url('/app/finance/financing/statement/' . ($plan['id'] ?? ''))) ?>/pdf" target="_blank">Descargar PDF</a>
</div>
<?php endif; ?>

<h1 class="title">Estado de Cuenta</h1>
<div class="subtitle">Generado el <?= esc($generated_at ?? date('d/m/Y H:i')) ?> · ASESORES RM</div>

<div class="fields">
    <div><label>Cliente:</label> <?= esc($plan['client_name'] ?? '—') ?></div>
    <div><label>Proyecto:</label> <?= esc($plan['project_name'] ?? '—') ?></div>
    <div><label>Unidad:</label> <?= esc($plan['unit_ref'] ?? $plan['property_ref'] ?? '—') ?></div>
    <div><label>Teléfono:</label> <?= esc($plan['lead_phone'] ?? '—') ?></div>
    <div><label>Correo:</label> <?= esc($plan['lead_email'] ?? '—') ?></div>
    <div><label>Precio total:</label> <?= $fmt($plan['total_price'] ?? 0) ?></div>
    <div><label>Inicial:</label> <?= $fmt($plan['down_payment'] ?? 0) ?></div>
    <div><label>Financiamiento:</label> <?= $fmt($plan['financing_amount'] ?? 0) ?></div>
</div>

<h3>Plan de cuotas</h3>
<table>
    <thead>
        <tr>
            <th>N°</th>
            <th>Mes</th>
            <th>Vencimiento</th>
            <th class="num">Monto</th>
            <th class="num">Pagado</th>
            <th class="num">Pendiente</th>
            <th>Estado</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($installments as $row): ?>
        <tr>
            <td><?= (int) ($row['installment_number'] ?? 0) ?></td>
            <td><?= esc($row['month_label'] ?? '') ?></td>
            <td><?= ! empty($row['due_date']) ? date('d/m/Y', strtotime($row['due_date'])) : '—' ?></td>
            <td class="num"><?= $fmt($row['amount'] ?? 0) ?></td>
            <td class="num"><?= $fmt($row['paid_amount'] ?? 0) ?></td>
            <td class="num"><?= $fmt($row['pending_amount'] ?? 0) ?></td>
            <td><?= esc($statusLabels[$row['status'] ?? ''] ?? $row['status'] ?? '') ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<div class="summary">
    Financiado: $ <?= $fmt($totals['scheduled'] ?? 0) ?> &nbsp;|&nbsp;
    Pagado: $ <?= $fmt($totals['paid'] ?? 0) ?> &nbsp;|&nbsp;
    Pendiente: $ <?= $fmt($totals['pending'] ?? 0) ?>
</div>

<?php if ($payments !== []): ?>
<h3>Historial de pagos registrados</h3>
<table>
    <thead>
        <tr>
            <th>Recibo</th>
            <th>Período</th>
            <th>Fecha pago</th>
            <th class="num">Monto USD</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($payments as $payment): ?>
        <tr>
            <td><?= esc($payment['receipt_number'] ?? '—') ?></td>
            <td><?= esc($payment['period_label'] ?? '—') ?></td>
            <td><?= ! empty($payment['payment_date']) ? date('d/m/Y', strtotime($payment['payment_date'])) : '—' ?></td>
            <td class="num"><?= $fmt($payment['amount_usd'] ?? $payment['amount'] ?? 0) ?></td>
        </tr>
    <?php endforeach; ?>
    </tbody>
</table>
<?php endif; ?>
</body>
</html>
