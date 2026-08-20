<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-hard-hat text-primary"></i> Constructoras</h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nueva constructora</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">Desarrolladores / constructoras</h6>
        </div>
        <div class="card-body">
            <p class="text-muted small">Registra las constructoras a las que se entregan pagos de cuotas (tipo Entregada).</p>
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Proyecto</th>
                            <th>Contacto</th>
                            <th>Teléfono</th>
                            <th>Estado</th>
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
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-hard-hat"></i> Constructora</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" required placeholder="Ej: Inversiones SKY CA">
                    </div>
                    <div class="form-group">
                        <label>Proyecto asociado</label>
                        <input type="text" class="form-control" name="project_name" placeholder="Ej: SKY">
                    </div>
                    <div class="form-group">
                        <label>Contacto</label>
                        <input type="text" class="form-control" name="contact_person">
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Teléfono</label><input type="text" class="form-control" name="phone"></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Email</label><input type="email" class="form-control" name="email"></div></div>
                    </div>
                    <div class="form-group mb-0">
                        <label>Notas</label>
                        <textarea class="form-control" name="notes" rows="2"></textarea>
                    </div>
                    <input type="hidden" name="status" value="active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var dt, apiBase = '<?= base_url('/app/finance/api/builders') ?>';

function loadTable() {
    $.post(apiBase, function(r) {
        var rows = [];
        if (r.status === 'success') {
            (r.data || []).forEach(function(d) {
                rows.push([
                    d.id,
                    d.name || '—',
                    d.project_name || '—',
                    d.contact_person || '—',
                    d.phone || '—',
                    d.status === 'active' ? 'Activa' : 'Inactiva',
                    '<button class="btn btn-info btn-sm mr-1" onclick="edit(' + d.id + ')"><i class="fas fa-edit"></i></button>' +
                    '<button class="btn btn-danger btn-sm" onclick="remove(' + d.id + ')"><i class="fas fa-trash"></i></button>'
                ]);
            });
        }
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#dataTable').DataTable({ data: rows, pageLength: 25, language: { url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' } });
    });
}

function showModal(mode, id) {
    $('#record_id').val('');
    $('#financeForm')[0].reset();
    $('input[name="status"]').val('active');
    $('#modalTitle').text(mode === 'create' ? 'Nueva constructora' : 'Editar constructora');
    if (mode === 'edit' && id) {
        $.get(apiBase + '/' + id, function(r) {
            if (r.status === 'success') {
                var d = r.data;
                Object.keys(d).forEach(function(k) {
                    var el = $('[name="' + k + '"]');
                    if (el.length) el.val(d[k]);
                });
                $('#record_id').val(d.id);
            }
        });
    }
    $('#financeModal').modal('show');
}

function edit(id) { showModal('edit', id); }
function remove(id) {
    if (!confirm('¿Eliminar esta constructora?')) return;
    $.post(apiBase + '/' + id + '/delete', function(r) {
        if (r.status === 'success') loadTable();
    });
}

$('#financeForm').submit(function(e) {
    e.preventDefault();
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
            } else alert(r.message || 'Error al guardar');
        }
    });
});

$(document).ready(function() { loadTable(); });
</script>
