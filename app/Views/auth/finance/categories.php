<!--
  Finance Categories — CRUD with DataTable + Modal
-->
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-tags text-warning"></i> Categorías
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Listado de Categorías
            </h6>
            <button class="btn btn-primary btn-sm" onclick="showModal('create')">
                <i class="fas fa-plus"></i> Agregar Nueva
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="financeTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Acciones</th></tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- Modal -->
<div class="modal fade" id="financeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle">Agregar Categoría</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="form-group">
                        <label for="name">Nombre</label>
                        <input type="text" class="form-control" id="name" name="name" required>
                    </div>
                    <div class="form-group">
                        <label for="type">Tipo</label>
                        <select class="form-control" id="type" name="type" required>
                            <option value="">Seleccionar...</option>
                            <option value="income">Ingreso</option>
                            <option value="expense">Gasto</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="parent_id">Categoría Padre</label>
                        <select class="form-control" id="parent_id" name="parent_id">
                            <option value="">Ninguna (raíz)</option>
                        </select>
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
var apiBase = '<?= base_url('/app/finance/api/categories') ?>';

function loadTable() {
    $.ajax({url: apiBase, method: 'POST', dataType: 'json',
        success: function(response) {
            var rows = [];
            if (response.status === 'success' && Array.isArray(response.data)) {
                response.data.forEach(function(row) {
                    rows.push([
                        row.id, row.name,
                        '<span class="badge badge-' + (row.type === 'income' ? 'success' : 'danger') + '">' + (row.type === 'income' ? 'Ingreso' : 'Gasto') + '</span>',
                        '<button class="btn btn-info btn-sm mr-1" onclick="showModal(\'edit\',' + row.id + ')"><i class="fas fa-edit"></i></button>' +
                        '<button class="btn btn-danger btn-sm" onclick="deleteRecord(' + row.id + ')"><i class="fas fa-trash"></i></button>'
                    ]);
                });
            }
            if (dt) { dt.clear().rows.add(rows).draw(); }
            else {
                dt = $('#financeTable').DataTable({
                    data: rows,
                    columns: [{title: 'ID'}, {title: 'Nombre'}, {title: 'Tipo'}, {title: 'Acciones', orderable: false}],
                    pageLength: 25,
                    language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
                    dom: 'Bfrtip', buttons: ['excel', 'pdf']
                });
            }
        }
    });
}

function loadParentOptions(excludeId) {
    $.post(apiBase, function(res) {
        var sel = $('#parent_id').empty().append('<option value="">Ninguna (raíz)</option>');
        if (res.status === 'success') {
            res.data.forEach(function(c) {
                if (c.id != excludeId) sel.append('<option value="' + c.id + '">' + c.name + '</option>');
            });
        }
    });
}

function showModal(mode, id) {
    editMode = mode === 'edit';
    $('#record_id').val('');
    $('#financeForm')[0].reset();
    $('#modalTitle').text(mode === 'create' ? 'Agregar Categoría' : 'Editar Categoría');
    loadParentOptions(id || null);

    if (mode === 'edit' && id) {
        $.post(apiBase + '/' + id, function(res) {
            if (res.status === 'success') {
                var d = res.data;
                $('#record_id').val(d.id);
                $('#name').val(d.name);
                $('#type').val(d.type);
                setTimeout(function() { $('#parent_id').val(d.parent_id); }, 400);
            }
        });
    }
    $('#financeModal').modal('show');
}

$('#financeForm').submit(function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    var url = editMode && $('#record_id').val() ? apiBase + '/' + $('#record_id').val() : apiBase + '/create';
    $.ajax({url: url, method: 'POST', data: data, dataType: 'json',
        success: function(res) { if (res.status === 'success') { $('#financeModal').modal('hide'); loadTable(); } else { alert('Error: ' + (res.message || 'Operación fallida')); } },
        error: function() { alert('Error de conexión'); }
    });
});

function deleteRecord(id) {
    if (!confirm('¿Eliminar esta categoría?')) return;
    $.post(apiBase + '/' + id + '/delete', function(res) { if (res.status === 'success') loadTable(); else alert('Error'); });
}

$(document).ready(function() { loadTable(); });
</script>
