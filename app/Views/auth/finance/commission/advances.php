<!-- Commission Advances — DataTable list -->
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
            <i class="fas fa-hand-holding-usd text-warning"></i> <?= esc($title ?? 'Comisiones — Adelantos') ?>
        </h1>
        <a href="<?= base_url('/app/finance/commission/advance-form') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Agregar Adelanto
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filtrar por Agente</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-4">
                    <select class="form-control" id="agentFilter">
                        <option value="">Todos los agentes</option>
                        <?php foreach ($users ?? [] as $user): ?>
                            <option value="<?= esc($user['id']) ?>"><?= esc($user['full_name'] ?? $user['name'] ?? ('Usuario #' . $user['id'])) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Listado de Adelantos</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="advancesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Agente</th>
                            <th>Monto</th>
                            <th>Fecha</th>
                            <th>Motivo</th>
                            <th>Estado</th>
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
var advanceData = <?= json_encode($advances ?? []) ?>;

function formatMoney(value) {
    if (!isFinite(value)) return '$ 0.00';
    return '$ ' + parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function buildRows(filterUserId) {
    var rows = [];
    advanceData.forEach(function(row) {
        if (filterUserId && String(row.user_id) !== String(filterUserId)) return;

        var statusLabel = row.settled == 1 ? 'Liquidado' : 'Pendiente';
        var statusClass = row.settled == 1 ? 'success' : 'warning';
        var actions = row.settled == 1
            ? '—'
            : '<a href="' + baseUrl + '/advance-form/' + row.id + '" class="btn btn-info btn-sm mr-1"><i class="fas fa-edit"></i></a>' +
              '<button class="btn btn-danger btn-sm" onclick="deleteAdvance(' + row.id + ')"><i class="fas fa-trash"></i></button>';

        rows.push([
            row.id,
            row.user_name || ('Usuario #' + row.user_id),
            formatMoney(row.amount),
            row.advance_date || '—',
            row.reason || '—',
            '<span class="badge badge-' + statusClass + '">' + statusLabel + '</span>',
            actions
        ]);
    });
    return rows;
}

function loadTable() {
    var filterVal = $('#agentFilter').val();
    var rows = buildRows(filterVal);

    if (dt) dt.clear().rows.add(rows).draw();
    else {
        dt = $('#advancesTable').DataTable({
            data: rows,
            columns: [
                {title: 'ID'},
                {title: 'Agente'},
                {title: 'Monto'},
                {title: 'Fecha'},
                {title: 'Motivo'},
                {title: 'Estado'},
                {title: 'Acciones', orderable: false}
            ],
            pageLength: 25,
            language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
            dom: 'Bfrtip',
            buttons: ['excel', 'pdf']
        });
    }
}

function deleteAdvance(id) {
    if (!confirm('¿Eliminar este adelanto?')) return;
    $.post(baseUrl + '/delete-advance/' + id, function() {
        location.reload();
    });
}

$('#agentFilter').on('change', loadTable);

$(document).ready(function() {
    $('#agentFilter').select2({width: '100%', placeholder: 'Todos los agentes', allowClear: true});
    loadTable();
});
</script>
