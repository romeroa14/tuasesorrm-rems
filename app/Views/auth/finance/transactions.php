<!--
  Finance Transactions — CRUD with DataTable + Modal
-->
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exchange-alt text-primary"></i> Transacciones
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Listado de Transacciones
            </h6>
            <button class="btn btn-primary btn-sm" onclick="showModal('create')">
                <i class="fas fa-plus"></i> Agregar Nuevo
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="transactionsTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Monto</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="financeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Agregar Transacción</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="type">Tipo</label>
                                <select class="form-control" id="type" name="type" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="income">Ingreso</option>
                                    <option value="expense">Gasto</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Monto</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="currency_id">Moneda</label>
                                <select class="form-control" id="currency_id" name="currency_id" required>
                                    <option value="">Cargando...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="account_id">Cuenta</label>
                                <select class="form-control" id="account_id" name="account_id">
                                    <option value="">Seleccionar...</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="category_id">Categoría</label>
                                <select class="form-control" id="category_id" name="category_id">
                                    <option value="">Seleccionar...</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date">Fecha</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label for="description">Descripción</label>
                        <textarea class="form-control" id="description" name="description" rows="3"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var dt;
var editMode = false;
var apiBase = '<?= base_url('/app/finance/api/transactions') ?>';

function loadTable() {
    $.ajax({
        url: apiBase,
        method: 'POST',
        dataType: 'json',
        success: function(response) {
            var rows = [];
            if (response.status === 'success' && Array.isArray(response.data)) {
                response.data.forEach(function(row) {
                    rows.push([
                        row.id,
                        '<span class="badge badge-' + (row.type === 'income' ? 'success' : 'danger') + '">' + (row.type === 'income' ? 'Ingreso' : 'Gasto') + '</span>',
                        parseFloat(row.amount || 0).toLocaleString('es-VE', {minimumFractionDigits: 2}),
                        row.description || '—',
                        row.date || '—',
                        '<button class="btn btn-info btn-sm mr-1" onclick="showModal(\'edit\',' + row.id + ')"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn btn-danger btn-sm" onclick="deleteRecord(' + row.id + ')"><i class="fas fa-trash"></i></button>'
                    ]);
                });
            }
            if (dt) {
                dt.clear().rows.add(rows).draw();
            } else {
                dt = $('#transactionsTable').DataTable({
                    data: rows,
                    columns: [
                        {title: 'ID'},
                        {title: 'Tipo'},
                        {title: 'Monto'},
                        {title: 'Descripción'},
                        {title: 'Fecha'},
                        {title: 'Acciones', orderable: false}
                    ],
                    pageLength: 25,
                    language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
                    dom: 'Bfrtip',
                    buttons: ['excel', 'pdf']
                });
            }
        }
    });
}

function loadDropdowns() {
    // Load currencies
    $.post('<?= base_url('/app/finance/api/currencies') ?>', function(res) {
        var sel = $('#currency_id').empty().append('<option value="">Seleccionar...</option>');
        if (res.status === 'success') {
            res.data.forEach(function(c) { sel.append('<option value="' + c.id + '">' + c.name + ' (' + c.code + ')</option>'); });
        }
    });
    // Load accounts
    $.post('<?= base_url('/app/finance/api/accounts') ?>', function(res) {
        var sel = $('#account_id').empty().append('<option value="">Seleccionar...</option>');
        if (res.status === 'success') {
            res.data.forEach(function(a) { sel.append('<option value="' + a.id + '">' + a.name + '</option>'); });
        }
    });
    // Load categories
    $.post('<?= base_url('/app/finance/api/categories') ?>', function(res) {
        var sel = $('#category_id').empty().append('<option value="">Seleccionar...</option>');
        if (res.status === 'success') {
            res.data.forEach(function(c) { sel.append('<option value="' + c.id + '">' + c.name + '</option>'); });
        }
    });
}

function showModal(mode, id) {
    editMode = mode === 'edit';
    $('#record_id').val('');
    $('#financeForm')[0].reset();
    $('#modalTitle').text(mode === 'create' ? 'Agregar Transacción' : 'Editar Transacción');
    loadDropdowns();

    if (mode === 'edit' && id) {
        $.post(apiBase.replace('/transactions','/transactions/' + id), function(res) {
            if (res.status === 'success') {
                var d = res.data;
                $('#record_id').val(d.id);
                $('#type').val(d.type);
                $('#amount').val(d.amount);
                $('#description').val(d.description || '');
                $('#date').val(d.date);
                // Set dropdowns after they load
                setTimeout(function() {
                    $('#currency_id').val(d.currency_id);
                    $('#account_id').val(d.account_id);
                    $('#category_id').val(d.category_id);
                }, 500);
            }
        });
    }

    $('#financeModal').modal('show');
}

$('#financeForm').submit(function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    var url = editMode && $('#record_id').val()
        ? apiBase + '/' + $('#record_id').val()
        : apiBase + '/create';

    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#financeModal').modal('hide');
                loadTable();
            } else {
                alert('Error: ' + (res.message || 'Operación fallida'));
            }
        },
        error: function() { alert('Error de conexión'); }
    });
});

function deleteRecord(id) {
    if (!confirm('¿Eliminar esta transacción?')) return;
    $.post(apiBase + '/' + id + '/delete', function(res) {
        if (res.status === 'success') loadTable();
        else alert('Error: ' + (res.message || 'No se pudo eliminar'));
    });
}

$(document).ready(function() { loadTable(); });
</script>
