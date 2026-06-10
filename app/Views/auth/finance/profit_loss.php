<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-chart-bar text-primary"></i> <?= esc($title ?? $report['sheet_title'] ?? 'Hoja Contable (Ganancias y Pérdidas)') ?>
        </h1>
        <button class="btn btn-outline-success btn-sm" onclick="window.print()">
            <i class="fas fa-print"></i> Imprimir / PDF
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Período Contable</h6>
        </div>
        <div class="card-body">
            <form method="GET" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2"><i class="fas fa-calendar"></i> Desde:</label>
                    <input type="date" class="form-control form-control-sm" name="date_from" value="<?= esc($date_from) ?>">
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2"><i class="fas fa-calendar"></i> Hasta:</label>
                    <input type="date" class="form-control form-control-sm" name="date_to" value="<?= esc($date_to) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm">
                    <i class="fas fa-search"></i> Actualizar
                </button>
            </form>
            <p class="text-muted small mt-2 mb-0">
                Período: <strong><?= esc($date_from) ?></strong> al <strong><?= esc($date_to) ?></strong>
                — Solo movimientos contabilizados (posted)
            </p>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-6">
            <div class="card shadow mb-4 border-left-success">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-success"><i class="fas fa-arrow-up"></i> Ingresos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="incomeSheet">
                            <thead class="thead-light">
                                <tr><th>Concepto</th><th class="text-right">Monto</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['income_rows'] as $row): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('/app/finance/income?type=' . urlencode($row['movement_type']) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to)) ?>">
                                            <?= esc($row['name']) ?>
                                        </a>
                                    </td>
                                    <td class="text-right"><?= number_format((float) $row['total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-success">
                                <tr>
                                    <th><strong>Total Ingresos</strong></th>
                                    <th class="text-right"><?= number_format((float) $report['total_income'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card shadow mb-4 border-left-danger">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-danger"><i class="fas fa-arrow-down"></i> Egresos</h6>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-sm table-hover" id="expenseSheet">
                            <thead class="thead-light">
                                <tr><th>Concepto</th><th class="text-right">Monto</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($report['expense_rows'] as $row): ?>
                                <tr>
                                    <td>
                                        <a href="<?= base_url('/app/finance/expenses_detail?type=' . urlencode($row['movement_type']) . '&date_from=' . urlencode($date_from) . '&date_to=' . urlencode($date_to)) ?>">
                                            <?= esc($row['name']) ?>
                                        </a>
                                    </td>
                                    <td class="text-right"><?= number_format((float) $row['total'], 2) ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                            <tfoot class="table-danger">
                                <tr>
                                    <th><strong>Total Egresos</strong></th>
                                    <th class="text-right"><?= number_format((float) $report['total_expense'], 2) ?></th>
                                </tr>
                            </tfoot>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered mb-0">
                <tbody>
                    <tr class="table-success">
                        <th>Total Ingresos</th>
                        <td class="text-right font-weight-bold"><?= number_format((float) $report['total_income'], 2) ?></td>
                    </tr>
                    <tr class="table-danger">
                        <th>Total Egresos</th>
                        <td class="text-right font-weight-bold"><?= number_format((float) $report['total_expense'], 2) ?></td>
                    </tr>
                    <tr class="table-<?= $report['is_profit'] ? 'success' : 'danger' ?>">
                        <th>Resultado Final (Ganancia / Pérdida)</th>
                        <td class="text-right font-weight-bold">
                            <?= number_format((float) $report['net_result'], 2) ?>
                            <?php if ($report['is_profit']): ?>
                            <span class="badge badge-success ml-2">GANANCIA</span>
                            <?php else: ?>
                            <span class="badge badge-danger ml-2">PÉRDIDA</span>
                            <?php endif; ?>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>
    </div>
</div>

<style>
@media print {
    .sidebar, .topbar, .btn, form { display: none !important; }
    .card { border: 1px solid #ddd !important; box-shadow: none !important; }
}
</style>

<script>
$(document).ready(function() {
    if ($.fn.DataTable) {
        $('#incomeSheet, #expenseSheet').each(function() {
            if (!$.fn.DataTable.isDataTable(this)) {
                $(this).DataTable({
                    paging: false,
                    searching: false,
                    info: false,
                    dom: 'Bfrtip',
                    buttons: ['excel']
                });
            }
        });
    }
});
</script>
