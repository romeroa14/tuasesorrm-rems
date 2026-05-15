<!--
  Finance Dashboard
  Summary cards + quick links + latest exchange rates
-->
<div class="container-fluid">
    <!-- Page Heading -->
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-coins text-primary"></i> Finanzas — Dashboard
        </h1>
    </div>

    <!-- Summary Cards -->
    <div class="row">
        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-primary shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">
                                Transacciones
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($total_transactions ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-exchange-alt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-success shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-success text-uppercase mb-1">
                                Gastos
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= number_format($total_expenses ?? 0) ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-receipt fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-info shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-info text-uppercase mb-1">
                                Cuentas Activas
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="active-accounts-count">
                                ...
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-university fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-xl-3 col-md-6 mb-4">
            <div class="card border-left-warning shadow h-100 py-2">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">
                                Tasas Recientes
                            </div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800">
                                <?= is_countable($latest_rates ?? []) ? count($latest_rates ?? []) : 0 ?>
                            </div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-dollar-sign fa-2x text-gray-300"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Quick Links -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-link"></i> Acceso Rápido
                    </h6>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/transactions') ?>" class="btn btn-outline-primary btn-block text-left">
                                <i class="fas fa-exchange-alt mr-2"></i> Transacciones
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/expenses') ?>" class="btn btn-outline-success btn-block text-left">
                                <i class="fas fa-receipt mr-2"></i> Gastos
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/accounts') ?>" class="btn btn-outline-info btn-block text-left">
                                <i class="fas fa-university mr-2"></i> Cuentas
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/categories') ?>" class="btn btn-outline-secondary btn-block text-left">
                                <i class="fas fa-tags mr-2"></i> Categorías
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/budgets') ?>" class="btn btn-outline-warning btn-block text-left">
                                <i class="fas fa-chart-line mr-2"></i> Presupuestos
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/exchange_rates') ?>" class="btn btn-outline-danger btn-block text-left">
                                <i class="fas fa-dollar-sign mr-2"></i> Tasas de Cambio
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/companies') ?>" class="btn btn-outline-dark btn-block text-left">
                                <i class="fas fa-building mr-2"></i> Empresas
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/departments') ?>" class="btn btn-outline-primary btn-block text-left">
                                <i class="fas fa-sitemap mr-2"></i> Departamentos
                            </a>
                        </div>
                        <div class="col-md-3 mb-2">
                            <a href="<?= base_url('/app/finance/projects') ?>" class="btn btn-outline-success btn-block text-left">
                                <i class="fas fa-tasks mr-2"></i> Proyectos
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Latest Exchange Rates Table -->
    <div class="row">
        <div class="col-12">
            <div class="card shadow mb-4">
                <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                    <h6 class="m-0 font-weight-bold text-primary">
                        <i class="fas fa-dollar-sign"></i> Últimas Tasas de Cambio
                    </h6>
                    <a href="<?= base_url('/app/finance/exchange_rates') ?>" class="btn btn-sm btn-primary">
                        <i class="fas fa-list"></i> Ver Todas
                    </a>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-bordered table-hover table-sm" width="100%" cellspacing="0">
                            <thead class="thead-light">
                                <tr>
                                    <th>Fecha</th>
                                    <th>Tasa</th>
                                    <th>Fuente</th>
                                    <th>Automático</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (! empty($latest_rates ?? [])): ?>
                                    <?php foreach ($latest_rates as $rate): ?>
                                    <tr>
                                        <td><?= esc($rate['rate_date'] ?? '') ?></td>
                                        <td><?= esc(number_format($rate['rate'] ?? 0, 4)) ?></td>
                                        <td>
                                            <span class="badge badge-<?= ($rate['source'] ?? '') === 'oficial' ? 'success' : (($rate['source'] ?? '') === 'paralelo' ? 'danger' : 'info') ?>">
                                                <?= esc(ucfirst($rate['source'] ?? '')) ?>
                                            </span>
                                        </td>
                                        <td>
                                            <?= ($rate['is_auto'] ?? 0) ? '<i class="fas fa-check-circle text-success"></i>' : '<i class="fas fa-hand-paper text-warning"></i>' ?>
                                        </td>
                                    </tr>
                                    <?php endforeach; ?>
                                <?php else: ?>
                                    <tr>
                                        <td colspan="4" class="text-center text-muted">No hay tasas de cambio registradas.</td>
                                    </tr>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    // Load active accounts count
    $.ajax({
        url: '<?= base_url('/app/finance/api/accounts') ?>',
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            if (response.status === 'success') {
                var activeCount = Array.isArray(response.data) ? response.data.length : 0;
                $('#active-accounts-count').text(activeCount.toLocaleString());
            }
        },
        error: function() {
            $('#active-accounts-count').text('—');
        }
    });
});
</script>
