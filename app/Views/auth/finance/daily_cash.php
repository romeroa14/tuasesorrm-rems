<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-cash-register text-success"></i> <?= esc($title ?? 'Finanzas — Caja chica diaria') ?>
        </h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')">
            <i class="fas fa-plus"></i> Nuevo Registro
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Registro Diario</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dailyCashTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Fecha</th>
                            <th>Saldo Inicial</th>
                            <th>Ingresos</th>
                            <th>Egresos</th>
                            <th>Saldo Final</th>
                            <th>Notas</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="cashModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-success text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-cash-register"></i> Registro de Caja</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="cashForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="form-group">
                        <label>Fecha <span class="text-danger">*</span></label>
                        <input type="date" class="form-control" name="cash_date" id="cash_date" required>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Saldo Inicial <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" id="opening_balance" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Ingresos</label>
                                <input type="number" step="0.01" class="form-control" name="total_income" id="total_income" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Egresos</label>
                                <input type="number" step="0.01" class="form-control" name="total_expense" id="total_expense" value="0">
                            </div>
                        </div>
                    </div>
                    <div class="form-group">
                        <label>Notas</label>
                        <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
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
var dt, apiBase = '<?= base_url('/app/finance/daily_cash/api') ?>';

function loadTable() {
    $.post(apiBase + '/list', function(r) {
        var rows = [];
        if (r.status === 'success' && Array.isArray(r.data)) {
            r.data.forEach(function(d) {
                var actions = '<button class="btn btn-info btn-sm mr-1" onclick="edit(' + d.id + ')"><i class="fas fa-edit"></i></button>' +
                              '<button class="btn btn-danger btn-sm" onclick="remove(' + d.id + ')"><i class="fas fa-trash"></i></button>';
                rows.push([d.id, d.cash_date, parseFloat(d.opening_balance).toFixed(2), parseFloat(d.total_income).toFixed(2),
                    parseFloat(d.total_expense).toFixed(2), parseFloat(d.closing_balance).toFixed(2), d.notes || '—', actions]);
            });
        }
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#dailyCashTable').DataTable({data: rows, pageLength: 25, language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}, dom: 'Bfrtip', buttons: ['excel', 'pdf']});
    });
}

function showModal(mode, id) {
    $('#record_id').val('');
    $('#cashForm')[0].reset();
    $('#modalTitle').text(mode === 'create' ? 'Nuevo Registro de Caja' : 'Editar Registro de Caja');
    if (mode === 'edit' && id) {
        $.get(apiBase + '/' + id, function(r) {
            if (r.status === 'success') {
                var d = r.data;
                $('#record_id').val(d.id);
                $('#cash_date').val(d.cash_date);
                $('#opening_balance').val(d.opening_balance);
                $('#total_income').val(d.total_income);
                $('#total_expense').val(d.total_expense);
                $('#notes').val(d.notes);
            }
        });
    }
    $('#cashModal').modal('show');
}

function edit(id) { showModal('edit', id); }

function remove(id) {
    if (!confirm('¿Eliminar registro?')) return;
    $.post(apiBase + '/' + id + '/delete', function(r) { if (r.status === 'success') loadTable(); });
}

$('#cashForm').submit(function(e) {
    e.preventDefault();
    var id = $('#record_id').val();
    var url = id ? apiBase + '/' + id : apiBase + '/create';
    $.ajax({url: url, method: 'POST', data: $(this).serialize(), dataType: 'json',
        success: function(r) { if (r.status === 'success') { $('#cashModal').modal('hide'); loadTable(); } else alert('Error: ' + r.message); },
        error: function() { alert('Error de conexión'); }
    });
});

$(document).ready(function() { loadTable(); });
</script>
