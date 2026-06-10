<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-money-bill-wave text-success"></i> <?= esc($title ?? 'Finanzas — Ingresos') ?>
        </h1>
        <div>
            <?php if ($can_draft ?? false): ?>
            <button class="btn btn-success btn-sm mr-2" onclick="showModal()">
                <i class="fas fa-plus"></i> Nuevo Ingreso
            </button>
            <?php endif; ?>
            <div class="btn-group d-inline-block">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-filter"></i> Filtrar por tipo
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="<?= base_url('/app/finance/income') ?>">Todos</a>
                    <?php foreach ($income_types as $key => $label): ?>
                    <a class="dropdown-item <?= ($current_type ?? '') === $key ? 'active' : '' ?>"
                       href="<?= base_url('/app/finance/income?type=' . $key) ?>">
                        <?= esc($label) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Rango de Fechas</h6>
        </div>
        <div class="card-body">
            <form id="dateFilterForm" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Desde:</label>
                    <input type="date" class="form-control form-control-sm" id="date_from" value="<?= esc($date_from) ?>">
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2">Hasta:</label>
                    <input type="date" class="form-control form-control-sm" id="date_to" value="<?= esc($date_to) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filtrar</button>
            </form>
        </div>
    </div>

    <?php if ($can_approve ?? false): ?>
    <div class="card shadow mb-4 border-left-warning" id="pendingCard" style="display:none;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-clock"></i> Ingresos Pendientes de Aprobación</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm" id="pendingTable">
                    <thead><tr><th>ID</th><th>Tipo</th><th>Monto</th><th>Fecha</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i>
                <?= $current_type ? esc($income_types[$current_type] ?? 'Desconocido') : 'Todos los Ingresos' ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="incomeTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="incomeModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-gradient-success text-white">
                <h5 class="modal-title"><i class="fas fa-plus-circle"></i> Nuevo Ingreso</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="incomeForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Tipo de Ingreso <span class="text-danger">*</span></label>
                                <select class="form-control" name="movement_type" id="movement_type" required>
                                    <option value="">Seleccionar...</option>
                                    <?php foreach ($income_types as $key => $label): ?>
                                    <option value="<?= esc($key) ?>" <?= ($current_type ?? '') === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Cuenta <span class="text-danger">*</span></label>
                                <select class="form-control" name="account_id" id="account_id" required></select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Monto <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="amount" required min="0.01">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="occurred_on" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Moneda</label>
                                <select class="form-control" name="currency_id" id="currency_id"></select>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Descripción</label>
                        <input type="text" class="form-control" name="description" placeholder="Detalle del ingreso">
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var dt, catalog = null;
var apiBase = '<?= base_url('/app/finance/income/api') ?>';
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';
var pendingUrl = '<?= base_url('/app/finance/api/pending') ?>';
var workflowUrl = '<?= base_url('/app/finance/workflows') ?>';
var currentType = '<?= $current_type ?? '' ?>';
var dateFrom = '<?= esc($date_from) ?>';
var dateTo = '<?= esc($date_to) ?>';
var canApprove = <?= ($can_approve ?? false) ? 'true' : 'false' ?>;

function loadCatalog(cb) {
    $.get(catalogUrl, function(r) {
        if (r.status === 'success') {
            catalog = r.data;
            var accOpts = '<option value="">Seleccionar...</option>';
            (catalog.accounts || []).forEach(function(a) {
                accOpts += '<option value="' + a.id + '">' + a.name + '</option>';
            });
            $('#account_id').html(accOpts);

            var curOpts = '<option value="">—</option>';
            (catalog.currencies || []).forEach(function(c) {
                curOpts += '<option value="' + c.id + '">' + (c.code || c.name) + '</option>';
            });
            $('#currency_id').html(curOpts);
        }
        if (cb) cb();
    });
}

function loadTable() {
    $.post(apiBase + '/list', { type: currentType, date_from: dateFrom, date_to: dateTo }, function(response) {
        var rows = [];
        if (response.status === 'success' && Array.isArray(response.data)) {
            response.data.forEach(function(row) {
                rows.push([
                    row.id,
                    row.category_name || '—',
                    parseFloat(row.amount || 0).toLocaleString('es-VE', {minimumFractionDigits: 2}),
                    row.description || row.notes || '—',
                    row.occurred_on || '—',
                    '<span class="badge badge-' + (row.status === 'posted' ? 'success' : (row.status === 'pending_approval' ? 'warning' : 'secondary')) + '">' + (row.status || '—') + '</span>'
                ]);
            });
        }
        if (dt) dt.clear().rows.add(rows).draw();
        else {
            dt = $('#incomeTable').DataTable({
                data: rows,
                pageLength: 25,
                language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
                dom: 'Bfrtip',
                buttons: ['excel', 'pdf']
            });
        }
    });
}

function loadPending() {
    if (!canApprove) return;
    $.post(pendingUrl, function(r) {
        var rows = (r.data || []).filter(function(m) { return m.workflow_type === 'ingreso'; });
        if (rows.length === 0) { $('#pendingCard').hide(); return; }
        $('#pendingCard').show();
        var html = '';
        rows.forEach(function(m) {
            html += '<tr><td>' + m.id + '</td><td>' + (m.category_name || '—') + '</td><td>' + parseFloat(m.amount).toFixed(2) + '</td><td>' + m.occurred_on + '</td>' +
                '<td><button class="btn btn-success btn-sm mr-1" onclick="approve(' + m.id + ')"><i class="fas fa-check"></i></button>' +
                '<button class="btn btn-danger btn-sm" onclick="reject(' + m.id + ')"><i class="fas fa-times"></i></button></td></tr>';
        });
        $('#pendingTable tbody').html(html);
    });
}

function showModal() {
    $('#incomeForm')[0].reset();
    if (currentType) $('#movement_type').val(currentType);
    $('#incomeModal').modal('show');
}

function approve(id) {
    $.post(workflowUrl + '/' + id + '/approve', function(r) {
        if (r.status === 'success') { loadTable(); loadPending(); }
        else alert('Error: ' + (r.message || ''));
    });
}

function reject(id) {
    if (!confirm('¿Rechazar este ingreso?')) return;
    $.post(workflowUrl + '/' + id + '/reject', function(r) {
        if (r.status === 'success') { loadTable(); loadPending(); }
        else alert('Error: ' + (r.message || ''));
    });
}

$('#dateFilterForm').submit(function(e) {
    e.preventDefault();
    dateFrom = $('#date_from').val();
    dateTo = $('#date_to').val();
    loadTable();
});

$('#incomeForm').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: apiBase + '/create',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(r) {
            if (r.status === 'success') {
                $('#incomeModal').modal('hide');
                loadTable();
                loadPending();
            } else alert('Error: ' + (r.message || 'Operación fallida'));
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            alert('Error: ' + msg);
        }
    });
});

$(document).ready(function() {
    loadCatalog(function() { loadTable(); loadPending(); });
});
</script>
