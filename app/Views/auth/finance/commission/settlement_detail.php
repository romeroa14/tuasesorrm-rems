<!-- Commission Settlement Detail — per-agent breakdown -->
<div class="container-fluid">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-calculator text-success"></i> <?= esc($title ?? 'Comisiones — Detalle de Liquidación') ?>
        </h1>
        <a href="<?= base_url('/app/finance/commission/settlements') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver a Liquidaciones
        </a>
    </div>

    <?php $settlement = $settlement ?? []; ?>

    <!-- Period Header -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-alt"></i> Información del Período</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="text-muted small">Período</div>
                    <strong><?= esc($settlement['period_start'] ?? '—') ?> → <?= esc($settlement['period_end'] ?? '—') ?></strong>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Estado</div>
                    <?php
                    $statusLbl = ['draft' => 'Borrador', 'finalized' => 'Finalizado', 'paid' => 'Pagado'];
                    $statusCls = ['draft' => 'secondary', 'finalized' => 'primary', 'paid' => 'success'];
                    $st = $settlement['status'] ?? 'draft';
                    ?>
                    <span class="badge badge-<?= $statusCls[$st] ?? 'secondary' ?>"><?= $statusLbl[$st] ?? $st ?></span>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Total Comisión</div>
                    <strong class="text-primary">$ <?= number_format((float)($settlement['total_commission'] ?? 0), 2, ',', '.') ?></strong>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Total Adelantos</div>
                    <strong class="text-warning">$ <?= number_format((float)($settlement['total_advances'] ?? 0), 2, ',', '.') ?></strong>
                </div>
                <div class="col-md-2">
                    <div class="text-muted small">Total Neto</div>
                    <strong class="text-success">$ <?= number_format((float)($settlement['total_net'] ?? 0), 2, ',', '.') ?></strong>
                </div>
                <?php if (!empty($settlement['finalized_at'])): ?>
                <div class="col-md-1">
                    <div class="text-muted small">Finalizado</div>
                    <small><?= esc($settlement['finalized_at'] ?? '') ?></small>
                </div>
                <?php endif; ?>
            </div>

            <?php if (($settlement['status'] ?? '') === 'draft'): ?>
            <hr>
            <div class="text-right">
                <button class="btn btn-warning mr-2" onclick="calculateSettlement(<?= $settlement['id'] ?? 0 ?>)">
                    <i class="fas fa-calculator"></i> Calcular
                </button>
                <button class="btn btn-success" onclick="finalizeSettlement(<?= $settlement['id'] ?? 0 ?>)">
                    <i class="fas fa-lock"></i> Finalizar Liquidación
                </button>
            </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Agent Detail Table -->
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users"></i> Desglose por Agente</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="detailTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>Agente</th>
                            <th>Comisión Bruta</th>
                            <th>Total Adelantos</th>
                            <th>Neto a Pagar</th>
                            <th>Alerta</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var baseUrl = '<?= base_url('/app/finance/commission') ?>';

function formatMoney(value) {
    if (!isFinite(value)) return '$ 0.00';
    return '$ ' + parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function loadDetailTable() {
    var rows = [];
    <?php foreach ($details ?? [] as $d): ?>
    var alertClass = <?= $d['negative_balance'] ?? 0 ?> ? 'table-danger' : '';
    var alertIcon = <?= $d['negative_balance'] ?? 0 ?> ? '<i class="fas fa-exclamation-triangle text-danger" title="Saldo negativo"></i>' : '<i class="fas fa-check-circle text-success"></i>';

    rows.push([
        '<?= esc($d['user_name'] ?? ('Usuario #' . ($d['user_id'] ?? 0)), 'js') ?>',
        formatMoney(<?= $d['gross_commission'] ?? 0 ?>),
        formatMoney(<?= $d['total_advances'] ?? 0 ?>),
        '<strong>' + formatMoney(<?= $d['net_payable'] ?? 0 ?>) + '</strong>',
        alertIcon
    ]);
    <?php endforeach; ?>

    $('#detailTable').DataTable({
        data: rows,
        columns: [
            {title: 'Agente'},
            {title: 'Comisión Bruta'},
            {title: 'Total Adelantos'},
            {title: 'Neto a Pagar'},
            {title: 'Alerta', orderable: false}
        ],
        pageLength: 50,
        language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
        dom: 'Bfrtip',
        buttons: ['excel', 'pdf'],
        createdRow: function(row, data, index) {
            if (data[0] && data[4].indexOf('exclamation-triangle') > -1) {
                $(row).addClass('table-danger');
            }
        }
    });
}

function calculateSettlement(id) {
    if (!confirm('¿Calcular esta liquidación? Se regenerarán todos los detalles.')) return;
    $.post(baseUrl + '/calculate-settlement/' + id, function(r) {
        if (r.success) location.reload();
        else alert('Error: ' + (r.error || 'Operación fallida'));
    });
}

function finalizeSettlement(id) {
    if (!confirm('¿Finalizar esta liquidación? Los registros quedarán bloqueados y se crearán transacciones financieras.')) return;
    $.post(baseUrl + '/finalize-settlement/' + id, function(r) {
        if (r.success) location.reload();
        else alert('Error: ' + (r.error || 'Operación fallida'));
    });
}

$(document).ready(function() { loadDetailTable(); });
</script>
