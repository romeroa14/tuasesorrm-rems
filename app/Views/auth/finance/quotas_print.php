<?php
/** @var array<string, mixed> $plan */
$plan = $plan ?? [];
$installments = $plan['installment_schedule'] ?? [];
$totals = $plan['totals'] ?? ['scheduled' => 0, 'paid' => 0, 'pending' => 0];

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
    <title>Plan de pago — <?= esc($plan['client_name'] ?? '') ?> — <?= esc($plan['unit_ref'] ?? '') ?></title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: Arial, Helvetica, sans-serif; color: #222; margin: 24px; font-size: 12px; }
        .toolbar { margin-bottom: 16px; }
        .toolbar button { padding: 8px 16px; cursor: pointer; }
        .header-grid { display: grid; grid-template-columns: 120px 1fr 1fr; gap: 12px; margin-bottom: 18px; align-items: start; }
        .logo { text-align: center; font-weight: bold; font-size: 11px; }
        .logo img { max-width: 90px; }
        .title { text-align: center; font-size: 22px; font-weight: bold; margin: 0 0 12px; }
        .fields { display: grid; grid-template-columns: 1fr 1fr; gap: 4px 16px; }
        .field label { font-weight: bold; display: inline-block; min-width: 130px; }
        table { width: 100%; border-collapse: collapse; margin-top: 8px; }
        th, td { border: 1px solid #333; padding: 6px 8px; }
        th { background: #f0f0f0; text-align: left; }
        td.num { text-align: right; white-space: nowrap; }
        .summary-row { margin-top: 12px; display: flex; gap: 24px; justify-content: flex-end; font-weight: bold; }
        .project-side { writing-mode: vertical-rl; transform: rotate(180deg); text-align: center; font-size: 28px; font-weight: bold; color: #666; }
        @media print {
            .toolbar { display: none; }
            body { margin: 10mm; }
        }
    </style>
</head>
<body>
    <div class="toolbar">
        <button type="button" onclick="window.print()">Imprimir / Guardar PDF</button>
        <button type="button" onclick="window.close()">Cerrar</button>
    </div>

    <div class="header-grid">
        <div class="logo">
            <img src="<?= base_url('img/logo/circle-logo.png') ?>" alt="REMS"><br>
            ASESORES RM
        </div>
        <div>
            <h1 class="title">Plan de Pago</h1>
            <div class="fields">
                <div><label>Cliente:</label> <?= esc($plan['client_name'] ?? '—') ?></div>
                <div><label>Proyecto:</label> <?= esc($plan['project_name'] ?? '—') ?></div>
                <div><label>Ofic./Apart.:</label> <?= esc($plan['unit_ref'] ?? $plan['property_ref'] ?? '—') ?></div>
                <div><label>Precio:</label> <?= $fmt($plan['total_price'] ?? 0) ?></div>
                <div><label>Inicial:</label> <?= $fmt($plan['down_payment'] ?? 0) ?></div>
                <div><label>Mtrs²:</label> <?= esc($plan['square_meters'] ?? '—') ?></div>
                <div><label>Reserva acordada:</label> <?= isset($plan['reservation_amount']) ? $fmt($plan['reservation_amount']) : '—' ?></div>
                <div><label>Financiamiento:</label> <?= $fmt($plan['financing_amount'] ?? 0) ?></div>
                <div><label>Cuotas:</label> <?= (int) ($plan['installment_count'] ?? $plan['installments'] ?? 0) ?></div>
                <div><label>Inicio:</label> <?= ! empty($plan['start_date']) ? date('d/m/Y', strtotime($plan['start_date'])) : '—' ?></div>
                <div><label>Final:</label> <?= ! empty($plan['end_date']) ? date('d/m/Y', strtotime($plan['end_date'])) : '—' ?></div>
                <?php if (! empty($plan['lead_phone'])): ?>
                <div><label>Teléfono:</label> <?= esc($plan['lead_phone']) ?></div>
                <?php endif; ?>
            </div>
        </div>
        <div class="project-side"><?= esc($plan['project_name'] ?? '') ?></div>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width:70px">N° Cuota</th>
                <th>Mes</th>
                <th class="num">Monto cuota</th>
                <th class="num">Pagado</th>
                <th class="num">Pendiente</th>
                <th style="width:90px">Estado</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($installments as $row): ?>
            <tr>
                <td><?= (int) ($row['installment_number'] ?? 0) ?></td>
                <td><?= esc($row['month_label'] ?? $row['due_date'] ?? '') ?></td>
                <td class="num"><?= $fmt($row['amount'] ?? 0) ?></td>
                <td class="num"><?= $fmt($row['paid_amount'] ?? 0) ?></td>
                <td class="num"><?= $fmt($row['pending_amount'] ?? 0) ?></td>
                <td><?= esc($statusLabels[$row['status'] ?? 'pending'] ?? $row['status'] ?? '') ?></td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

    <div class="summary-row">
        <span>Total financiamiento: <?= $fmt($totals['scheduled'] ?? 0) ?></span>
        <span>Pagado: <?= $fmt($totals['paid'] ?? 0) ?></span>
        <span>Por cobrar: <?= $fmt($totals['pending'] ?? 0) ?></span>
    </div>

    <p style="margin-top:24px; font-size:10px; color:#666;">Impreso: <?= esc($printed_at ?? date('d/m/Y H:i')) ?></p>
</body>
</html>
