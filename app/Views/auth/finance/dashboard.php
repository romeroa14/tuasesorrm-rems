<?php
$report = $report ?? [];
?>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4 flex-wrap">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-coins text-primary"></i> Finanzas — Inicio
        </h1>
        <div class="d-flex flex-wrap">
            <?php if (! empty($companies)): ?>
            <select id="financeCompanySelect" class="form-control form-control-sm mr-2 mb-2" style="min-width:200px">
                <option value="">Todas las empresas</option>
                <?php foreach ($companies as $co): ?>
                <option value="<?= (int) $co['id'] ?>" <?= ($active_company_id ?? null) == $co['id'] ? 'selected' : '' ?>>
                    <?= esc($co['name'] ?? '') ?>
                </option>
                <?php endforeach; ?>
            </select>
            <?php endif; ?>
            <a href="<?= base_url('/app/finance/reports/statistics') ?>" class="btn btn-primary btn-sm mr-2 mb-2">
                <i class="fas fa-chart-pie"></i> Reportes estadísticos
            </a>
            <a href="<?= base_url('/app/finance/profit_loss') ?>" class="btn btn-warning btn-sm mb-2">
                <i class="fas fa-chart-bar"></i> Hoja contable
            </a>
        </div>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Ingresos (mes)</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">$<?= number_format((float) ($report['total_income'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Egresos (mes)</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">$<?= number_format((float) ($report['total_expense'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-<?= ($report['is_profit'] ?? true) ? 'success' : 'danger' ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Resultado del mes</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">
                        $<?= number_format((float) ($report['net_result'] ?? 0), 2) ?>
                        <span class="badge badge-<?= ($report['is_profit'] ?? true) ? 'success' : 'danger' ?> ml-1">
                            <?= ($report['is_profit'] ?? true) ? 'GANANCIA' : 'PÉRDIDA' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="alert alert-light border">
        <i class="fas fa-info-circle text-primary"></i>
        Los análisis detallados (por categoría, sector, comisiones, adelantos, cuotas y tendencias) están en
        <a href="<?= base_url('/app/finance/reports/statistics') ?>"><strong>Reportes estadísticos</strong></a>.
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sitemap"></i> Secciones del módulo</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <?php foreach ($modules as $module): ?>
                    <?php if (isset($module['url'])): ?>
                        <div class="col-lg-4 col-md-6 mb-3">
                            <a href="<?= base_url($module['url']) ?>" class="btn btn-outline-primary btn-block text-left py-3">
                                <i class="<?= esc($module['icon']) ?> mr-2"></i>
                                <strong><?= esc($module['label']) ?></strong>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="col-12 mb-3">
                            <h6 class="font-weight-bold text-gray-800 mb-2">
                                <i class="<?= esc($module['icon']) ?>"></i> <?= esc($module['label']) ?>
                            </h6>
                            <div class="row">
                                <?php foreach ($module['items'] as $item): ?>
                                    <div class="col-lg-4 col-md-6 mb-2">
                                        <a href="<?= base_url($item['url']) ?>" class="btn btn-light btn-block text-left border">
                                            <?= esc($item['label']) ?>
                                        </a>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    <?php endif; ?>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
<script>
$('#financeCompanySelect').on('change', function() {
    $.post('<?= base_url('/app/finance/set-company') ?>', { company_id: $(this).val() }, function() {
        location.reload();
    });
});
</script>
