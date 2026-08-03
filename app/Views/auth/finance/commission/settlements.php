<!-- Commission Settlements — DataTable list -->
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
            <i class="fas fa-calculator text-success"></i> <?= esc($title ?? 'Comisiones — Liquidaciones') ?>
        </h1>
        <a href="<?= base_url('/app/finance/commission/settlement-form') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Nuevo Período
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Listado de Liquidaciones</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="settlementsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Período Inicio</th>
                            <th>Período Fin</th>
                            <th>Estado</th>
                            <th>Total Comisión</th>
                            <th>Total Adelantos</th>
                            <th>Total Neto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var dt;
var baseUrl = '<?= base_url('/app/finance/commission') ?>';

function formatMoney(value) {
    if (!isFinite(value)) return '$ 0.00';
    return '$ ' + parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function loadTable() {
    var rows = [];
    var statusLbl = {'draft': 'Borrador', 'finalized': 'Finalizado', 'paid': 'Pagado'};
    var statusCls = {'draft': 'secondary', 'finalized': 'primary', 'paid': 'success'};

    <?php foreach ($settlements ?? [] as $row): ?>
    var actions = '';
    <?php if (($row['status'] ?? '') === 'draft'): ?>
    actions += '<button class="btn btn-warning btn-sm mr-1" onclick="calculateSettlement(<?= $row['id'] ?? 0 ?>)"><i class="fas fa-calculator"></i> Calcular</button>';
    <?php endif; ?>
    <?php if (($row['status'] ?? '') === 'draft'): ?>
    actions += '<button class="btn btn-success btn-sm mr-1" onclick="finalizeSettlement(<?= $row['id'] ?? 0 ?>)"><i class="fas fa-lock"></i> Finalizar</button>';
    <?php endif; ?>
    actions += '<a href="' + baseUrl + '/settlement-detail/<?= $row['id'] ?? 0 ?>" class="btn btn-info btn-sm"><i class="fas fa-eye"></i></a>';

    rows.push([
        <?= $row['id'] ?? 0 ?>,
        '<?= esc($row['period_start'] ?? '—', 'js') ?>',
        '<?= esc($row['period_end'] ?? '—', 'js') ?>',
        '<span class="badge badge-' + (statusCls['<?= esc($row['status'] ?? 'draft', 'js') ?>'] || 'secondary') + '">' + (statusLbl['<?= esc($row['status'] ?? '', 'js') ?>'] || '<?= esc($row['status'] ?? '—', 'js') ?>') + '</span>',
        formatMoney(<?= $row['total_commission'] ?? 0 ?>),
        formatMoney(<?= $row['total_advances'] ?? 0 ?>),
        formatMoney(<?= $row['total_net'] ?? 0 ?>),
        actions
    ]);
    <?php endforeach; ?>

    if (dt) dt.clear().rows.add(rows).draw();
    else {
        dt = $('#settlementsTable').DataTable({
            data: rows,
            columns: [
                {title: 'ID'},
                {title: 'Período Inicio'},
                {title: 'Período Fin'},
                {title: 'Estado'},
                {title: 'Total Comisión'},
                {title: 'Total Adelantos'},
                {title: 'Total Neto'},
                {title: 'Acciones', orderable: false}
            ],
            pageLength: 25,
            language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
            dom: 'Bfrtip',
            buttons: ['excel', 'pdf']
        });
    }
}

function calculateSettlement(id) {
    if (!confirm('¿Calcular esta liquidación? Se regenerarán todos los detalles.')) return;
    $.post(baseUrl + '/calculate-settlement/' + id, function(r) {
        if (r.success) {
            alert('Cálculo completado. ' + (r.detail_count || 0) + ' agentes procesados.');
            location.reload();
        } else {
            alert('Error: ' + (r.error || 'Operación fallida'));
        }
    });
}

function finalizeSettlement(id) {
    if (!confirm('¿Finalizar esta liquidación? Se crearán las transacciones financieras y los registros quedarán bloqueados.')) return;
    $.post(baseUrl + '/finalize-settlement/' + id, function(r) {
        if (r.success) {
            alert('Liquidación finalizada correctamente.');
            location.reload();
        } else {
            alert('Error: ' + (r.error || 'Operación fallida'));
        }
    });
}

$(document).ready(function() { loadTable(); });
</script>
