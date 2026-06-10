<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-hand-holding-usd text-primary"></i> <?= esc($title ?? 'Finanzas — Cuotas') ?>
        </h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')">
            <i class="fas fa-plus"></i> Nueva Cuota
        </button>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i> Listado de Cuotas
            </h6>
            <div class="btn-group">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-filter"></i> Filtrar
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item filter-option" data-value="" href="#">Todas</a>
                    <a class="dropdown-item filter-option" data-value="received" href="#">Recibidas</a>
                    <a class="dropdown-item filter-option" data-value="delivered" href="#">Entregadas</a>
                </div>
            </div>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="quotasTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Nombre</th>
                            <th>N° Recibo</th>
                            <th>Monto</th>
                            <th>Equiv. USD</th>
                            <th>Moneda</th>
                            <th>Tasa</th>
                            <th>Fecha Recepción</th>
                            <th>Fecha Entrega</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="quotaModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-hand-holding-usd"></i> Cuota</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="quotaForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">

                    <div class="card bg-light mb-3">
                        <div class="card-header py-2"><i class="fas fa-info-circle text-primary"></i> <strong>Información General</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-arrows-alt-h text-muted"></i> Tipo <span class="text-danger">*</span></label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="received">Recibida</option>
                                            <option value="delivered">Entregada</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label><i class="fas fa-user text-muted"></i> Nombre <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required placeholder="Nombre de la persona">
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-coins text-muted"></i> Moneda <span class="text-danger">*</span></label>
                                        <select class="form-control" id="currency" name="currency" required>
                                            <option value="">Seleccionar...</option>
                                            <option value="USDT">USDT</option>
                                            <option value="BS">Bs.</option>
                                            <option value="ZELLE">Zelle</option>
                                            <option value="CASH">Efectivo</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4" id="exchange_rate_group" style="display:none;">
                                    <div class="form-group">
                                        <label><i class="fas fa-percentage text-muted"></i> Tasa de Cambio</label>
                                        <input type="number" step="0.0001" class="form-control" id="exchange_rate" name="exchange_rate" placeholder="0.0000">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-dollar-sign text-muted"></i> Monto <span class="text-danger">*</span></label>
                                        <input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="0" placeholder="0.00">
                                    </div>
                                </div>
                            </div>
                            <input type="hidden" id="currency_denomination" name="currency_denomination">
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-tag text-muted"></i> Denominación detectada</label>
                                        <input type="text" class="form-control" id="detected_denomination" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-dollar-sign text-muted"></i> Equivalente USD</label>
                                        <input type="text" class="form-control" id="amount_usd_display" readonly>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-money-bill-wave text-muted"></i> Equivalente BS</label>
                                        <input type="text" class="form-control" id="amount_bs_display" readonly>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-calendar text-muted"></i> Fecha Recepción <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="receipt_date" name="receipt_date" required>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-calendar-check text-muted"></i> Fecha Entrega</label>
                                        <input type="date" class="form-control" id="delivery_date" name="delivery_date">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label><i class="fas fa-receipt text-muted"></i> N° Recibo <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="receipt_number" name="receipt_number" required placeholder="REC-001">
                                    </div>
                                </div>
                            </div>
                            <div class="form-group">
                                <label><i class="fas fa-comment text-muted"></i> Notas</label>
                                <textarea class="form-control" id="notes" name="notes" rows="2" placeholder="Detalle..."></textarea>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
var dt;
var editMode = false;
var apiBase = '<?= base_url('/app/finance/quotas/api') ?>';
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';
var currentFilter = '<?= esc($current_type ?? '') ?>';
var currencyContext = {};

var typeLabels = { received: 'Recibida', delivered: 'Entregada' };
var currencyLabels = { USDT: 'USDT', BS: 'Bs.', ZELLE: 'Zelle', CASH: 'Efectivo' };

function formatMoney(value) {
    if (!isFinite(value)) return '';
    return value.toLocaleString('es-VE', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function getDenominationFromCurrency(currency) {
    return String(currency || '').toUpperCase() === 'BS' ? 'BS' : 'USD';
}

function syncQuotaMoney() {
    var currency = $('#currency').val();
    var denomination = getDenominationFromCurrency(currency);
    var latestRate = parseFloat(currencyContext.latest_bs_rate || 0);
    var currentRate = parseFloat($('#exchange_rate').val() || 0);
    var rate = denomination === 'BS' ? (currentRate > 0 ? currentRate : latestRate) : 1;
    var amount = parseFloat($('#amount').val() || 0);

    $('#exchange_rate_group').toggle(denomination === 'BS');
    $('#currency_denomination').val(denomination);
    $('#detected_denomination').val(denomination);

    if (denomination === 'BS') {
        $('#exchange_rate').val(rate > 0 ? rate.toFixed(4) : '');
    } else {
        $('#exchange_rate').val('1.0000');
    }

    if (!isFinite(amount) || amount <= 0) {
        $('#amount_usd_display').val('');
        $('#amount_bs_display').val('');
        return;
    }

    var amountUsd = denomination === 'BS' ? (rate > 0 ? amount / rate : 0) : amount;
    var amountBs = denomination === 'BS' ? amount : amount * rate;
    $('#amount_usd_display').val(formatMoney(amountUsd));
    $('#amount_bs_display').val(formatMoney(amountBs));
}

function loadContext(cb) {
    $.get(catalogUrl, function(response) {
        if (response.status === 'success' && response.data && response.data.currency_context) {
            currencyContext = response.data.currency_context;
        }
        if (cb) cb();
    });
}

$('.filter-option').click(function(e) {
    e.preventDefault();
    currentFilter = $(this).data('value');
    loadTable();
});

function loadTable() {
    $.post(apiBase + '/list', { type: currentFilter }, function(response) {
        var rows = [];
        if (response.status === 'success' && Array.isArray(response.data)) {
            response.data.forEach(function(row) {
                var actions = '<button class="btn btn-info btn-sm mr-1" onclick="edit(' + row.id + ')"><i class="fas fa-edit"></i></button>' +
                              '<button class="btn btn-danger btn-sm" onclick="remove(' + row.id + ')"><i class="fas fa-trash"></i></button>';
                rows.push([
                    row.id,
                    '<span class="badge badge-' + (row.type === 'received' ? 'success' : 'warning') + '">' + (typeLabels[row.type] || row.type) + '</span>',
                    row.name || '—',
                    row.receipt_number || '—',
                    parseFloat(row.amount || 0).toLocaleString('es-VE', {minimumFractionDigits: 2}),
                    parseFloat(row.amount_usd || 0).toLocaleString('es-VE', {minimumFractionDigits: 2}),
                    currencyLabels[row.currency] || row.currency || '—',
                    row.exchange_rate ? parseFloat(row.exchange_rate).toFixed(4) : '—',
                    row.receipt_date || '—',
                    row.delivery_date || '—',
                    actions
                ]);
            });
        }
        if (dt) {
            dt.clear().rows.add(rows).draw();
        } else {
            dt = $('#quotasTable').DataTable({
                data: rows,
                columns: [
                    {title: 'ID'},
                    {title: 'Tipo'},
                    {title: 'Nombre'},
                    {title: 'N° Recibo'},
                    {title: 'Monto'},
                    {title: 'Equiv. USD'},
                    {title: 'Moneda'},
                    {title: 'Tasa'},
                    {title: 'Fecha Recepción'},
                    {title: 'Fecha Entrega'},
                    {title: 'Acciones', orderable: false}
                ],
                pageLength: 25,
                language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
                dom: 'Bfrtip',
                buttons: ['excel', 'pdf']
            });
        }
    });
}

function showModal(mode, id) {
    editMode = mode === 'edit';
    $('#record_id').val('');
    $('#quotaForm')[0].reset();
    $('#exchange_rate').val('');
    $('#exchange_rate_group').hide();
    $('#modalTitle').text(mode === 'create' ? 'Nueva Cuota' : 'Editar Cuota');

    if (mode === 'edit' && id) {
        $.get(apiBase + '/' + id, function(res) {
            if (res.status === 'success') {
                var d = res.data;
                $('#record_id').val(d.id);
                $('#type').val(d.type);
                $('#name').val(d.name);
                $('#currency').val(d.currency).trigger('change');
                if (d.currency === 'BS' && d.exchange_rate) {
                    $('#exchange_rate').val(d.exchange_rate);
                }
                $('#amount').val(d.amount);
                $('#receipt_date').val(d.receipt_date);
                $('#delivery_date').val(d.delivery_date);
                $('#receipt_number').val(d.receipt_number);
                $('#notes').val(d.notes);
                $('#currency_denomination').val(d.currency_denomination || getDenominationFromCurrency(d.currency));
                syncQuotaMoney();
            }
        });
    }

    syncQuotaMoney();
    $('#quotaModal').modal('show');
}

function edit(id) { showModal('edit', id); }

function remove(id) {
    if (!confirm('¿Eliminar esta cuota?')) return;
    $.post(apiBase + '/' + id + '/delete', function(r) {
        if (r.status === 'success') loadTable();
        else alert('Error: ' + (r.message || 'No se pudo eliminar'));
    });
}

$('#quotaForm').submit(function(e) {
    e.preventDefault();
    var data = $(this).serialize();
    var id = $('#record_id').val();
    var url = id ? apiBase + '/' + id : apiBase + '/create';

    $.ajax({
        url: url,
        method: 'POST',
        data: data,
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#quotaModal').modal('hide');
                loadTable();
            } else {
                alert('Error: ' + (res.message || 'Operación fallida'));
            }
        },
        error: function() { alert('Error de conexión'); }
    });
});

$('#currency, #exchange_rate, #amount').on('change keyup', syncQuotaMoney);

$(document).ready(function() {
    loadContext(function() {
        syncQuotaMoney();
        loadTable();
    });
});
</script>
