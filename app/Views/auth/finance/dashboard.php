<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-coins text-primary"></i> Finanzas — Inicio
        </h1>
        <a href="<?= base_url('/app/finance/profit_loss') ?>" class="btn btn-warning btn-sm">
            <i class="fas fa-chart-bar"></i> Ver Hoja Contable
        </a>
    </div>

    <div class="row mb-4">
        <div class="col-md-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Total Ingresos (mes)</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800"><?= number_format((float) ($report['total_income'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-danger shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-danger text-uppercase mb-1">Total Egresos (mes)</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800"><?= number_format((float) ($report['total_expense'] ?? 0), 2) ?></div>
                </div>
            </div>
        </div>
        <div class="col-md-4">
            <div class="card border-left-<?= ($report['is_profit'] ?? true) ? 'success' : 'danger' ?> shadow h-100 py-2">
                <div class="card-body">
                    <div class="text-xs font-weight-bold text-uppercase mb-1">Resultado Final</div>
                    <div class="h4 mb-0 font-weight-bold text-gray-800">
                        <?= number_format((float) ($report['net_result'] ?? 0), 2) ?>
                        <span class="badge badge-<?= ($report['is_profit'] ?? true) ? 'success' : 'danger' ?> ml-1">
                            <?= ($report['is_profit'] ?? true) ? 'GANANCIA' : 'PÉRDIDA' ?>
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-sitemap"></i> Secciones del módulo financiero</h6>
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
