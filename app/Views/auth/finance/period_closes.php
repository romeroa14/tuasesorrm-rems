<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-calendar-check text-secondary"></i> Cierres mensuales</h1>
        <?php if (! empty($can_close_period)): ?>
        <button class="btn btn-success btn-sm" onclick="runClose()"><i class="fas fa-lock"></i> Cerrar mes actual</button>
        <?php endif; ?>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <table class="table table-bordered">
                <thead class="thead-light"><tr><th>Periodo</th><th>Ingresos</th><th>Egresos</th><th>Neto</th><th>Cerrado</th></tr></thead>
                <tbody>
                <?php if (empty($closes)): ?>
                    <tr><td colspan="5" class="text-muted">No hay cierres registrados.</td></tr>
                <?php else: ?>
                    <?php foreach ($closes as $c): ?>
                    <tr>
                        <td><?= sprintf('%02d/%04d', $c['period_month'], $c['period_year']) ?></td>
                        <td>$<?= number_format((float) $c['total_income'], 2) ?></td>
                        <td>$<?= number_format((float) $c['total_expense'], 2) ?></td>
                        <td>$<?= number_format((float) $c['net_result'], 2) ?></td>
                        <td><?= esc($c['closed_at'] ?? '') ?></td>
                    </tr>
                    <?php endforeach; ?>
                <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>
<script>
function runClose() {
    if (!confirm('¿Cerrar el mes actual? Se guardará un snapshot del P&L.')) return;
    $.post('<?= base_url('/app/finance/period-closes/run') ?>', {
        year: <?= (int) date('Y') ?>,
        month: <?= (int) date('n') ?>
    }, function(r) {
        alert(r.status === 'success' ? 'Cierre registrado' : (r.message || 'Error'));
        if (r.status === 'success') location.reload();
    }, 'json');
}
</script>
