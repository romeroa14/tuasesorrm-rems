<!--
  Finance Expenses — CRUD with DataTable + Modal
-->
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-receipt text-success"></i> Gastos
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Listado de Gastos
            </h6>
            <button class="btn btn-primary btn-sm" onclick="showModal('create')">
                <i class="fas fa-plus"></i> Agregar Nuevo
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="expensesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Monto</th>
                            <th>Estado</th>
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
                <h5 class="modal-title" id="modalTitle">Agregar Gasto</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount">Monto</label>
                                <input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="0">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="amount_usd">Monto USD</label>
                                <input type="number" step="0.01" class="form-control" id="amount_usd" name="amount_usd" min="0">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="currency_id">Moneda</label>
                                <select class="form-control" id="currency_id" name="currency_id" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="payment_type_id">Método de Pago</label>
                                <select class="form-control" id="payment_type_id" name="payment_type_id" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="expense_type_id">Tipo de Gasto</label>
                                <select class="form-control" id="expense_type_id" name="expense_type_id" required></select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="category_id">Categoría</label>
                                <select class="form-control" id="category_id" name="category_id" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="company_id">Empresa</label>
                                <select class="form-control" id="company_id" name="company_id"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="account_id">Cuenta</label>
                                <select class="form-control" id="account_id" name="account_id"></select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="department_id">Departamento</label>
                                <select class="form-control" id="department_id" name="department_id"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="project_id">Proyecto</label>
                                <select class="form-control" id="project_id" name="project_id"></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="status">Estado</label>
                                <select class="form-control" id="status" name="status">
                                    <option value="pending">Pendiente</option>
                                    <option value="approved">Aprobado</option>
                                    <option value="rejected">Rechazado</option>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="date">Fecha</label>
                                <input type="date" class="form-control" id="date" name="date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="user_id">Usuario</label>
                                <input type="number" class="form-control" id="user_id" name="user_id" required value="<?= session()->get('id') ?>">
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
var dt, editMode = false;
var apiBase = '<?= base_url('/app/finance/api/expenses') ?>';
var currentUserId = <?= (int) session()->get('id') ?>;

function loadTable() {
    $.ajax({
        url: apiBase, method: 'POST', dataType: 'json',
        success: function(response) {
            var rows = [];
            if (response.status === 'success' && Array.isArray(response.data)) {
                response.data.forEach(function(row) {
                    var statusBadge = row.status === 'approved' ? 'success' : (row.status === 'rejected' ? 'danger' : 'warning');
                    var statusText = row.status === 'approved' ? 'Aprobado' : (row.status === 'rejected' ? 'Rechazado' : 'Pendiente');
                    rows.push([
                        row.id,
                        parseFloat(row.amount || 0).toLocaleString('es-VE', {minimumFractionDigits: 2}),
                        '<span class="badge badge-' + statusBadge + '">' + statusText + '</span>',
                        row.description ? row.description.substring(0, 80) : '—',
                        row.date || '—',
                        '<button class="btn btn-info btn-sm mr-1" onclick="showModal(\'edit\',' + row.id + ')"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn btn-danger btn-sm" onclick="deleteRecord(' + row.id + ')"><i class="fas fa-trash"></i></button>'
                    ]);
                });
            }
            if (dt) { dt.clear().rows.add(rows).draw(); }
            else {
                dt = $('#expensesTable').DataTable({
                    data: rows,
                    columns: [
                        {title: 'ID'}, {title: 'Monto'}, {title: 'Estado'},
                        {title: 'Descripción'}, {title: 'Fecha'}, {title: 'Acciones', orderable: false}
                    ],
                    pageLength: 25,
                    language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
                    dom: 'Bfrtip', buttons: ['excel', 'pdf']
                });
            }
        }
    });
}

function loadSelect(url, selector) {
    $.post(url, function(res) {
        var sel = $(selector).empty().append('<option value="">Seleccionar...</option>');
        if (res.status === 'success') {
            res.data.forEach(function(i) { sel.append('<option value="' + i.id + '">' + (i.name || i.code) + '</option>'); });
        }
    });
}

function loadDropdowns() {
    loadSelect('<?= base_url('/app/finance/api/currencies') ?>', '#currency_id');
    loadSelect('<?= base_url('/app/finance/api/payment_types') ?>', '#payment_type_id');
    loadSelect('<?= base_url('/app/finance/api/expense_types') ?>', '#expense_type_id');
    loadSelect('<?= base_url('/app/finance/api/categories') ?>', '#category_id');
    loadSelect('<?= base_url('/app/finance/api/companies') ?>', '#company_id');
    loadSelect('<?= base_url('/app/finance/api/accounts') ?>', '#account_id');
    loadSelect('<?= base_url('/app/finance/api/departments') ?>', '#department_id');
    loadSelect('<?= base_url('/app/finance/api/projects') ?>', '#project_id');
}

function showModal(mode, id) {
    editMode = mode === 'edit';
    $('#record_id').val('');
    $('#financeForm')[0].reset();
    $('#user_id').val(currentUserId);
    $('#modalTitle').text(mode === 'create' ? 'Agregar Gasto' : 'Editar Gasto');
    loadDropdowns();

    if (mode === 'edit' && id) {
        $.ajax({url: apiBase.replace('/expenses','/expenses/') + id, method: 'POST', dataType: 'json',
            success: function(res) {
                if (res.status === 'success') {
                    var d = res.data;
                    $('#record_id').val(d.id);
                    $('#amount').val(d.amount);
                    $('#amount_usd').val(d.amount_usd);
                    $('#description').val(d.description || '');
                    $('#date').val(d.date);
                    $('#user_id').val(d.user_id);
                    $('#status').val(d.status);
                    setTimeout(function() {
                        $('#currency_id').val(d.currency_id);
                        $('#payment_type_id').val(d.payment_type_id);
                        $('#expense_type_id').val(d.expense_type_id);
                        $('#category_id').val(d.category_id);
                        $('#company_id').val(d.company_id);
                        $('#account_id').val(d.account_id);
                        $('#department_id').val(d.department_id);
                        $('#project_id').val(d.project_id);
                    }, 600);
                }
            }
        });
    }
    $('#financeModal').modal('show');
}

$('#financeForm').submit(function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    if (!editMode) { data += '&created_by=' + currentUserId; }
    var url = editMode && $('#record_id').val()
        ? apiBase + '/' + $('#record_id').val()
        : apiBase + '/create';
    $.ajax({url: url, method: 'POST', data: data, dataType: 'json',
        success: function(res) {
            if (res.status === 'success') { $('#financeModal').modal('hide'); loadTable(); }
            else { alert('Error: ' + (res.message || 'Operación fallida')); }
        },
        error: function() { alert('Error de conexión'); }
    });
});

function deleteRecord(id) {
    if (!confirm('¿Eliminar este gasto?')) return;
    $.post(apiBase + '/' + id + '/delete', function(res) {
        if (res.status === 'success') loadTable();
        else alert('Error: ' + (res.message || 'No se pudo eliminar'));
    });
}

$(document).ready(function() { loadTable(); });
</script>
