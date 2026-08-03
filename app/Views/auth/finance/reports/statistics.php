<?php
$stats = $stats ?? [];
$summary = $stats['summary'] ?? [];
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 flex-wrap">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-pie text-primary"></i> Reportes estadísticos
        </h1>
        <form class="form-inline" method="get" action="">
            <?php if (! empty($companies)): ?>
            <select name="company_id" id="reportCompany" class="form-control form-control-sm mr-2 mb-2" style="min-width:180px" onchange="switchCompany(this.value)">
                <option value="">Todas</option>
                <?php foreach ($companies as $co): ?>
                <option value="<?= (int) $co['id'] ?>" <?= ($active_company_id ?? null) == $co['id'] ? 'selected' : '' ?>><?= esc($co['name']) ?></option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <input type="date" name="date_from" class="form-control form-control-sm mr-2 mb-2" value="<?= esc($date_from) ?>">
            <input type="date" name="date_to" class="form-control form-control-sm mr-2 mb-2" value="<?= esc($date_to) ?>">
            <button type="submit" class="btn btn-primary btn-sm mb-2"><i class="fas fa-filter"></i> Aplicar</button>
            <a href="<?= base_url('/app/finance/export/profit_loss?date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to)) ?>"
               class="btn btn-outline-secondary btn-sm mb-2 ml-1" target="_blank"><i class="fas fa-download"></i> Exportar P&L</a>
        </form>
    </div>

    <?php foreach ($stats['alerts'] ?? [] as $alert): ?>
    <div class="alert alert-<?= esc($alert['level'] ?? 'warning') ?>"><?= esc($alert['message'] ?? '') ?></div>
    <?php endforeach; ?>

    <div class="row mb-4">
        <div class="col-md-2 col-6 mb-2"><div class="card border-left-success shadow py-2"><div class="card-body py-2"><div class="text-xs text-success font-weight-bold">INGRESOS</div><div class="h5 mb-0">$<?= number_format($summary['total_income'] ?? 0, 2) ?></div></div></div></div>
        <div class="col-md-2 col-6 mb-2"><div class="card border-left-danger shadow py-2"><div class="card-body py-2"><div class="text-xs text-danger font-weight-bold">EGRESOS</div><div class="h5 mb-0">$<?= number_format($summary['total_expense'] ?? 0, 2) ?></div></div></div></div>
        <div class="col-md-2 col-6 mb-2"><div class="card border-left-primary shadow py-2"><div class="card-body py-2"><div class="text-xs text-primary font-weight-bold">NETO</div><div class="h5 mb-0">$<?= number_format($summary['net_result'] ?? 0, 2) ?></div></div></div></div>
        <div class="col-md-2 col-6 mb-2"><div class="card border-left-info shadow py-2"><div class="card-body py-2"><div class="text-xs text-info font-weight-bold">MARGEN</div><div class="h5 mb-0"><?= number_format($summary['margin_pct'] ?? 0, 1) ?>%</div></div></div></div>
        <div class="col-md-2 col-6 mb-2"><div class="card border-left-warning shadow py-2"><div class="card-body py-2"><div class="text-xs text-warning font-weight-bold">PROPIEDADES</div><div class="h5 mb-0"><?= (int) ($stats['commissions']['properties'] ?? 0) ?></div></div></div></div>
        <div class="col-md-2 col-6 mb-2"><div class="card border-left-secondary shadow py-2"><div class="card-body py-2"><div class="text-xs text-secondary font-weight-bold">PEND. APROBAR</div><div class="h5 mb-0"><?= (int) ($stats['pending_approvals'] ?? 0) ?></div></div></div></div>
    </div>

    <div class="row">
        <div class="col-lg-8 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Tendencia mensual</strong></div>
            <div class="card-body"><canvas id="chartTrend" height="100"></canvas></div></div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Ventas por sector</strong></div>
            <div class="card-body"><canvas id="chartSector" height="180"></canvas></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-12 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Neto mensual</strong></div>
            <div class="card-body"><canvas id="chartNet" height="80"></canvas></div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Ingresos por categoría</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0"><thead><tr><th>Categoría</th><th class="text-right">Monto</th><th class="text-right">%</th></tr></thead><tbody>
                <?php foreach ($stats['income_by_type'] ?? [] as $row): if (($row['total'] ?? 0) <= 0) continue; ?>
                <tr><td><?= esc($row['name']) ?></td><td class="text-right">$<?= number_format($row['total'], 2) ?></td><td class="text-right"><?= $row['pct'] ?>%</td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div></div>
        </div>
        <div class="col-lg-6 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Egresos por categoría</strong></div>
            <div class="card-body p-0">
                <table class="table table-sm mb-0"><thead><tr><th>Categoría</th><th class="text-right">Monto</th><th class="text-right">%</th></tr></thead><tbody>
                <?php foreach ($stats['expense_by_type'] ?? [] as $row): if (($row['total'] ?? 0) <= 0) continue; ?>
                <tr><td><?= esc($row['name']) ?></td><td class="text-right">$<?= number_format($row['total'], 2) ?></td><td class="text-right"><?= $row['pct'] ?>%</td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div></div>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Comisiones por agente</strong></div>
            <div class="card-body p-0"><table class="table table-sm mb-0"><thead><tr><th>Agente</th><th class="text-right">Neto</th></tr></thead><tbody>
            <?php foreach ($stats['commissions']['by_agent'] ?? [] as $a): ?>
            <tr><td><?= esc($a['agent_name'] ?? ('#' . ($a['user_id'] ?? ''))) ?></td><td class="text-right">$<?= number_format((float)($a['net_payable'] ?? 0), 2) ?></td></tr>
            <?php endforeach; ?>
            </tbody></table></div></div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Adelantos</strong></div>
            <div class="card-body">
                <p>Total: <strong>$<?= number_format($stats['advances']['total'] ?? 0, 2) ?></strong></p>
                <p>Pendientes: <strong class="text-warning">$<?= number_format($stats['advances']['pending'] ?? 0, 2) ?></strong></p>
                <p>Liquidados: <strong class="text-success">$<?= number_format($stats['advances']['settled'] ?? 0, 2) ?></strong></p>
                <table class="table table-sm mt-2"><thead><tr><th>Agente</th><th class="text-right">Pend.</th></tr></thead><tbody>
                <?php foreach ($stats['advances']['by_agent'] ?? [] as $a): if (($a['pending'] ?? 0) <= 0) continue; ?>
                <tr><td><?= esc($a['agent_name'] ?? '') ?></td><td class="text-right">$<?= number_format($a['pending'], 2) ?></td></tr>
                <?php endforeach; ?>
                </tbody></table>
            </div></div>
        </div>
        <div class="col-lg-4 mb-4">
            <div class="card shadow"><div class="card-header py-3"><strong>Cuotas</strong></div>
            <div class="card-body">
                <p>Recibidas: <strong><?= (int) ($stats['quotas']['received'] ?? 0) ?></strong> — $<?= number_format($stats['quotas']['received_total'] ?? 0, 2) ?></p>
                <p>Entregadas: <strong><?= (int) ($stats['quotas']['delivered'] ?? 0) ?></strong> — $<?= number_format($stats['quotas']['delivered_total'] ?? 0, 2) ?></p>
                <p class="text-muted small mb-0">Volumen ventas comisiones: $<?= number_format($stats['commissions']['sale_volume'] ?? 0, 2) ?></p>
                <p class="text-muted small">Ingreso neto comisiones: $<?= number_format($stats['commissions']['net_income'] ?? 0, 2) ?></p>
            </div></div>
        </div>
    </div>
</div>
<script>
var statsData = <?= json_encode($stats, JSON_UNESCAPED_UNICODE) ?>;
function switchCompany(id) {
    $.post('<?= base_url('/app/finance/set-company') ?>', { company_id: id }, function() {
        location.href = location.pathname + '?date_from=<?= esc($date_from) ?>&date_to=<?= esc($date_to) ?>';
    });
}
$(function() {
    if (typeof Chart === 'undefined') return;
    var trend = statsData.monthly_trend || [];
    var sectorColors = ['#4e73df', '#1cc88a', '#36b9cc', '#f6c23e', '#858796', '#e74a3b'];

    new Chart(document.getElementById('chartTrend'), {
        type: 'line',
        data: {
            labels: trend.map(function(r) { return r.label; }),
            datasets: [
                {
                    label: 'Ingresos',
                    borderColor: '#1cc88a',
                    backgroundColor: 'rgba(28, 200, 138, 0.1)',
                    fill: false,
                    tension: 0.2,
                    data: trend.map(function(r) { return r.income; })
                },
                {
                    label: 'Egresos',
                    borderColor: '#e74a3b',
                    backgroundColor: 'rgba(231, 74, 59, 0.1)',
                    fill: false,
                    tension: 0.2,
                    data: trend.map(function(r) { return r.expense; })
                }
            ]
        },
        options: {
            responsive: true,
            plugins: { legend: { position: 'top' } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    var sector = statsData.sector_sales || [];
    new Chart(document.getElementById('chartSector'), {
        type: 'bar',
        data: {
            labels: sector.map(function(r) { return r.label; }),
            datasets: [{
                label: 'Ventas',
                data: sector.map(function(r) { return r.total; }),
                backgroundColor: sector.map(function(r, i) {
                    return sectorColors[i % sectorColors.length];
                }),
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: { beginAtZero: true }
            }
        }
    });

    var netValues = trend.map(function(r) { return r.net; });
    new Chart(document.getElementById('chartNet'), {
        type: 'bar',
        data: {
            labels: trend.map(function(r) { return r.label; }),
            datasets: [{
                label: 'Neto',
                data: netValues,
                backgroundColor: netValues.map(function(v) {
                    return v >= 0 ? '#4e73df' : '#e74a3b';
                }),
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            plugins: { legend: { display: false } },
            scales: {
                y: {
                    beginAtZero: false
                }
            }
        }
    });
});
</script>
