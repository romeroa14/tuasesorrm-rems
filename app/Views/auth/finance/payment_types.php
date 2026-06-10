<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-credit-card text-primary"></i> Métodos de pago</h1>
        <?php if (!empty($can_manage_catalogs)): ?>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nuevo método</button>
        <?php endif; ?>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Catálogo de métodos de pago</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Código</th>
                            <th>Denominación por defecto</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="financeModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-credit-card"></i> Método de pago</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="name" name="name" required placeholder="Ej: Pago Móvil">
                    </div>
                    <div class="form-group">
                        <label>Código <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" id="code" name="code" required placeholder="Ej: pago_movil">
                    </div>
                    <div class="form-group">
                        <label>Denominación por defecto <span class="text-danger">*</span></label>
                        <select class="form-control" id="default_denomination" name="default_denomination" required>
                            <option value="USD">USD</option>
                            <option value="BS">BS</option>
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
var dt, apiBase='<?= base_url('/app/finance/api/payment_types') ?>';
var canManageCatalogs = <?= !empty($can_manage_catalogs) ? 'true' : 'false' ?>;

function loadTable() {
    $.post(apiBase, function(r) {
        var rows = [];
        if (r.status === 'success' && Array.isArray(r.data)) {
            r.data.forEach(function(d) {
                var badge = '<span class="badge badge-' + (d.default_denomination === 'BS' ? 'warning' : 'primary') + '">' + (d.default_denomination || 'USD') + '</span>';
                var actions = canManageCatalogs
                    ? '<button class="btn btn-info btn-sm mr-1" onclick="edit(' + d.id + ')"><i class="fas fa-edit"></i></button>' +
                      '<button class="btn btn-danger btn-sm" onclick="removeRecord(' + d.id + ')"><i class="fas fa-trash"></i></button>'
                    : '—';
                rows.push([d.id, d.name || '—', d.code || '—', badge, actions]);
            });
        }
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#dataTable').DataTable({
            data: rows,
            pageLength: 25,
            language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}
        });
    });
}

function showModal(mode, id) {
    if (!canManageCatalogs) return;
    $('#record_id').val('');
    $('#financeForm')[0].reset();
    $('#default_denomination').val('USD');
    $('#modalTitle').text(mode === 'create' ? 'Nuevo método de pago' : 'Editar método de pago');
    if (mode === 'edit' && id) {
        $.get(apiBase + '/' + id, function(r) {
            if (r.status === 'success') {
                var d = r.data;
                $('#record_id').val(d.id);
                $('#name').val(d.name);
                $('#code').val(d.code);
                $('#default_denomination').val(d.default_denomination || 'USD');
            }
        });
    }
    $('#financeModal').modal('show');
}

function edit(id) { showModal('edit', id); }

function removeRecord(id) {
    if (!canManageCatalogs || !confirm('¿Eliminar método de pago?')) return;
    $.post(apiBase + '/' + id + '/delete', function(r) {
        if (r.status === 'success') loadTable();
        else alert(r.message || 'No se pudo eliminar');
    });
}

$('#financeForm').submit(function(e) {
    e.preventDefault();
    if (!canManageCatalogs) return;
    var id = $('#record_id').val();
    $.ajax({
        url: apiBase + (id ? '/' + id : '/create'),
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(r) {
            if (r.status === 'success') {
                $('#financeModal').modal('hide');
                loadTable();
            } else {
                alert(r.message || 'Operación fallida');
            }
        },
        error: function() {
            alert('Error de conexión');
        }
    });
});

$(document).ready(function() { loadTable(); });
</script>
