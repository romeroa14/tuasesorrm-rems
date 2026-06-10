<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-exchange-alt text-info"></i> <?= esc($title ?? 'Finanzas — Canjes de efectivo') ?>
        </h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')">
            <i class="fas fa-plus"></i> Nuevo Canje
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Registro de Canjes</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="exchangesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Monto</th>
                            <th>Moneda Origen</th>
                            <th>Moneda Destino</th>
                            <th>Tasa</th>
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

<div class="modal fade" id="exchangeModal" tabindex="-1">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-info text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-exchange-alt"></i> Canje</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="exchangeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="name" required placeholder="Nombre / concepto">
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Moneda Origen <span class="text-danger">*</span></label>
                                <select class="form-control" name="source_currency" id="source_currency" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="USDT">USDT</option>
                                    <option value="BS">Bs.</option>
                                    <option value="ZELLE">Zelle</option>
                                    <option value="CASH">Efectivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Moneda Destino <span class="text-danger">*</span></label>
                                <select class="form-control" name="target_currency" id="target_currency" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="USDT">USDT</option>
                                    <option value="BS">Bs.</option>
                                    <option value="ZELLE">Zelle</option>
                                    <option value="CASH">Efectivo</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Tasa de Cambio</label>
                                <input type="number" step="0.0001" class="form-control" name="rate" id="rate" placeholder="0.0000">
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Monto <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="amount" id="amount" required min="0" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="exchange_date" id="exchange_date" required>
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
var dt, apiBase = '<?= base_url('/app/finance/exchanges/api') ?>';
var currencyLabels = {USDT:'USDT', BS:'Bs.', ZELLE:'Zelle', CASH:'Efectivo'};

function loadTable() {
    $.post(apiBase + '/list', function(r) {
        var rows = [];
        if (r.status === 'success' && Array.isArray(r.data)) {
            r.data.forEach(function(d) {
                var actions = '<button class="btn btn-info btn-sm mr-1" onclick="edit(' + d.id + ')"><i class="fas fa-edit"></i></button>' +
                              '<button class="btn btn-danger btn-sm" onclick="remove(' + d.id + ')"><i class="fas fa-trash"></i></button>';
                rows.push([d.id, d.name, parseFloat(d.amount).toFixed(2), currencyLabels[d.source_currency]||d.source_currency,
                    currencyLabels[d.target_currency]||d.target_currency, d.rate ? parseFloat(d.rate).toFixed(4) : '—', d.exchange_date, actions]);
            });
        }
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#exchangesTable').DataTable({data: rows, pageLength: 25, language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}, dom: 'Bfrtip', buttons: ['excel', 'pdf']});
    });
}

function showModal(mode, id) {
    $('#record_id').val('');
    $('#exchangeForm')[0].reset();
    $('#modalTitle').text(mode === 'create' ? 'Nuevo Canje' : 'Editar Canje');
    if (mode === 'edit' && id) {
        $.get(apiBase + '/' + id, function(r) {
            if (r.status === 'success') {
                var d = r.data;
                $('#record_id').val(d.id);
                $('#name').val(d.name);
                $('#source_currency').val(d.source_currency);
                $('#target_currency').val(d.target_currency);
                $('#rate').val(d.rate);
                $('#amount').val(d.amount);
                $('#exchange_date').val(d.exchange_date);
                $('#notes').val(d.notes);
            }
        });
    }
    $('#exchangeModal').modal('show');
}

function edit(id) { showModal('edit', id); }

function remove(id) {
    if (!confirm('¿Eliminar?')) return;
    $.post(apiBase + '/' + id + '/delete', function(r) { if (r.status === 'success') loadTable(); });
}

$('#exchangeForm').submit(function(e) {
    e.preventDefault();
    var id = $('#record_id').val();
    var url = id ? apiBase + '/' + id : apiBase + '/create';
    $.ajax({url: url, method: 'POST', data: $(this).serialize(), dataType: 'json',
        success: function(r) { if (r.status === 'success') { $('#exchangeModal').modal('hide'); loadTable(); } },
        error: function() { alert('Error'); }
    });
});

$(document).ready(function() { loadTable(); });
</script>
