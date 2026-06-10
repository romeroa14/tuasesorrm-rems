<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-shield-alt text-warning"></i> <?= esc($title ?? 'Finanzas — Efectivo en resguardo') ?>
        </h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')">
            <i class="fas fa-plus"></i> Nuevo Registro
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Registros de Resguardo</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="custodyTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Fecha Ingreso</th>
                            <th>Monto</th>
                            <th>Equiv. USD</th>
                            <th>Equiv. BS</th>
                            <th>Método de pago</th>
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

<div class="modal fade" id="custodyModal" tabindex="-1">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-warning text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-shield-alt"></i> Resguardo</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="custodyForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Nombre <span class="text-danger">*</span></label>
                                <input type="text" class="form-control" name="name" id="name" required placeholder="Nombre de la persona">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha Ingreso <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="entry_date" id="entry_date" required>
                            </div>
                        </div>
                    </div>
                    <?php echo view('auth/finance/partials/payment_amount_fields'); ?>
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

<script src="<?= base_url('/js/finance-catalog-money.js') ?>"></script>
<script>
var dt, moneyHelper, apiBase = '<?= base_url('/app/finance/custody/api') ?>';
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';

function paymentTypeName(id) {
    var match = moneyHelper ? moneyHelper.getPaymentType(id) : null;
    return match ? (match.name || match.code || '—') : '—';
}

function loadTable() {
    $.post(apiBase + '/list', function(r) {
        var rows = [];
        if (r.status === 'success' && Array.isArray(r.data)) {
            r.data.forEach(function(d) {
                var denomination = d.currency_denomination || 'USD';
                var actions = '<button class="btn btn-info btn-sm mr-1" onclick="edit(' + d.id + ')"><i class="fas fa-edit"></i></button>' +
                              '<button class="btn btn-danger btn-sm" onclick="remove(' + d.id + ')"><i class="fas fa-trash"></i></button>';
                rows.push([
                    d.id, d.name, d.entry_date,
                    moneyHelper.formatPrimaryAmount(d.amount, denomination),
                    parseFloat(d.amount_usd || 0).toFixed(2),
                    parseFloat(d.amount_bs || 0).toFixed(2),
                    paymentTypeName(d.payment_type_id),
                    d.notes || '—', actions
                ]);
            });
        }
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#custodyTable').DataTable({data: rows, pageLength: 25, language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}, dom: 'Bfrtip', buttons: ['excel', 'pdf']});
    });
}

function showModal(mode, id) {
    $('#record_id').val('');
    $('#custodyForm')[0].reset();
    $('#modalTitle').text(mode === 'create' ? 'Nuevo Resguardo' : 'Editar Resguardo');
    if (mode === 'edit' && id) {
        $.get(apiBase + '/' + id, function(r) {
            if (r.status === 'success') {
                var d = r.data;
                $('#record_id').val(d.id);
                $('#name').val(d.name);
                $('#entry_date').val(d.entry_date);
                moneyHelper.populatePaymentTypes(d.payment_type_id);
                $('#amount').val(d.amount);
                $('#exchange_rate').val(d.exchange_rate || '');
                $('#notes').val(d.notes);
                moneyHelper.refresh();
            }
        });
    } else {
        moneyHelper.populatePaymentTypes();
        moneyHelper.refresh();
    }
    $('#custodyModal').modal('show');
}

function edit(id) { showModal('edit', id); }
function remove(id) {
    if (!confirm('¿Eliminar?')) return;
    $.post(apiBase + '/' + id + '/delete', function(r) { if (r.status === 'success') loadTable(); });
}

$('#custodyForm').submit(function(e) {
    e.preventDefault();
    var id = $('#record_id').val();
    $.ajax({url: id ? apiBase + '/' + id : apiBase + '/create', method: 'POST', data: $(this).serialize(), dataType: 'json',
        success: function(r) { if (r.status === 'success') { $('#custodyModal').modal('hide'); loadTable(); } else alert('Error: ' + (r.message || '')); },
        error: function(xhr) { alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error de conexión')); }
    });
});

$(document).ready(function() {
    moneyHelper = new FinanceCatalogMoney({ catalogUrl: catalogUrl, amountLabel: '#amount_label' });
    moneyHelper.bind();
    moneyHelper.loadCatalog(function() { loadTable(); });
});
</script>
