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
                            <th>Denominación</th>
                            <th>Saldo Inicial</th>
                            <th>Ingresos</th>
                            <th>Egresos</th>
                            <th>Saldo Final</th>
                            <th>Saldo Final USD</th>
                            <th>Saldo Final BS</th>
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
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-success text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-cash-register"></i> Registro de Caja</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="cashForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="cash_date" id="cash_date" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label>Método de pago (denominación) <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_type_id" id="payment_type_id" required></select>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="currency_denomination" id="currency_denomination">
                    <input type="hidden" name="exchange_rate" id="exchange_rate">
                    <div class="row">
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Denominación detectada</label>
                                <input type="text" class="form-control" id="detected_denomination" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label>Tasa actual Bs/USD</label>
                                <input type="text" class="form-control" id="display_rate_to_base" readonly>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label id="opening_balance_label">Saldo Inicial <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="opening_balance" id="opening_balance" value="0" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label id="closing_balance_label">Saldo Final calculado</label>
                                <input type="text" class="form-control" id="closing_balance_display" readonly>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label id="total_income_label">Ingresos</label>
                                <input type="number" step="0.01" class="form-control" name="total_income" id="total_income" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label id="total_expense_label">Egresos</label>
                                <input type="number" step="0.01" class="form-control" name="total_expense" id="total_expense" value="0">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label>Notas</label>
                                <textarea class="form-control" name="notes" id="notes" rows="2"></textarea>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Saldo Inicial USD</label><input type="text" class="form-control" id="opening_balance_usd_display" readonly></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Ingresos USD</label><input type="text" class="form-control" id="total_income_usd_display" readonly></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Egresos USD</label><input type="text" class="form-control" id="total_expense_usd_display" readonly></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Saldo Final USD</label><input type="text" class="form-control" id="closing_balance_usd_display" readonly></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Saldo Inicial BS</label><input type="text" class="form-control" id="opening_balance_bs_display" readonly></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Ingresos BS</label><input type="text" class="form-control" id="total_income_bs_display" readonly></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Egresos BS</label><input type="text" class="form-control" id="total_expense_bs_display" readonly></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Saldo Final BS</label><input type="text" class="form-control" id="closing_balance_bs_display" readonly></div></div>
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
var dt, catalog = null, apiBase = '<?= base_url('/app/finance/daily_cash/api') ?>';
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';

function getContext() { return catalog && catalog.currency_context ? catalog.currency_context : {}; }
function getPaymentType(id) {
    var match = null;
    (catalog && catalog.payment_types ? catalog.payment_types : []).forEach(function(p) {
        if (String(p.id) === String(id)) match = p;
    });
    return match;
}

function syncDailyCashMoney() {
    var paymentType = getPaymentType($('#payment_type_id').val());
    var denomination = paymentType && paymentType.default_denomination ? paymentType.default_denomination : 'USD';
    var rate = parseFloat(getContext().latest_bs_rate || 0);
    var opening = parseFloat($('#opening_balance').val() || 0);
    var income = parseFloat($('#total_income').val() || 0);
    var expense = parseFloat($('#total_expense').val() || 0);
    var closing = opening + income - expense;
    var prefix = denomination === 'BS' ? 'Bs. ' : '$ ';

    $('#currency_denomination').val(denomination);
    $('#detected_denomination').val(denomination);
    $('#exchange_rate').val(denomination === 'BS' && rate > 0 ? rate.toFixed(6) : '1.000000');
    $('#display_rate_to_base').val(denomination === 'BS' && rate > 0 ? rate.toFixed(4) : '1.0000');
    $('#opening_balance_label').html(prefix + 'Saldo Inicial <span class="text-danger">*</span>');
    $('#total_income_label').text(prefix + 'Ingresos');
    $('#total_expense_label').text(prefix + 'Egresos');
    $('#closing_balance_label').text(prefix + 'Saldo Final calculado');
    $('#closing_balance_display').val(formatFinanceMoney(closing));

    function convert(value) {
        var usd = denomination === 'BS' ? (rate > 0 ? value / rate : 0) : value;
        var bs = denomination === 'BS' ? value : value * rate;
        return { usd: usd, bs: bs };
    }

    var openingEq = convert(opening);
    var incomeEq = convert(income);
    var expenseEq = convert(expense);
    var closingEq = convert(closing);

    $('#opening_balance_usd_display').val(formatFinanceMoney(openingEq.usd));
    $('#total_income_usd_display').val(formatFinanceMoney(incomeEq.usd));
    $('#total_expense_usd_display').val(formatFinanceMoney(expenseEq.usd));
    $('#closing_balance_usd_display').val(formatFinanceMoney(closingEq.usd));
    $('#opening_balance_bs_display').val(formatFinanceMoney(openingEq.bs));
    $('#total_income_bs_display').val(formatFinanceMoney(incomeEq.bs));
    $('#total_expense_bs_display').val(formatFinanceMoney(expenseEq.bs));
    $('#closing_balance_bs_display').val(formatFinanceMoney(closingEq.bs));
}

function populatePaymentTypes(selectedId) {
    var opts = '<option value="">Seleccionar...</option>';
    (catalog && catalog.payment_types ? catalog.payment_types : []).forEach(function(p) {
        opts += '<option value="' + p.id + '">' + (p.name || p.code || '—') + '</option>';
    });
    $('#payment_type_id').html(opts);
    if (selectedId) $('#payment_type_id').val(String(selectedId));
}

function loadCatalog(cb) {
    $.get(catalogUrl, function(r) {
        if (r.status === 'success') catalog = r.data;
        if (cb) cb();
    });
}

function loadTable() {
    $.post(apiBase + '/list', function(r) {
        var rows = [];
        if (r.status === 'success' && Array.isArray(r.data)) {
            r.data.forEach(function(d) {
                var denomination = d.currency_denomination || 'USD';
                var prefix = denomination === 'BS' ? 'Bs. ' : '$ ';
                var actions = '<button class="btn btn-info btn-sm mr-1" onclick="edit(' + d.id + ')"><i class="fas fa-edit"></i></button>' +
                              '<button class="btn btn-danger btn-sm" onclick="remove(' + d.id + ')"><i class="fas fa-trash"></i></button>';
                rows.push([
                    d.id, d.cash_date, denomination,
                    prefix + parseFloat(d.opening_balance).toFixed(2),
                    prefix + parseFloat(d.total_income).toFixed(2),
                    prefix + parseFloat(d.total_expense).toFixed(2),
                    prefix + parseFloat(d.closing_balance).toFixed(2),
                    parseFloat(d.closing_balance_usd || 0).toFixed(2),
                    parseFloat(d.closing_balance_bs || 0).toFixed(2),
                    d.notes || '—', actions
                ]);
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
                populatePaymentTypes(d.payment_type_id);
                $('#opening_balance').val(d.opening_balance);
                $('#total_income').val(d.total_income);
                $('#total_expense').val(d.total_expense);
                $('#notes').val(d.notes);
                syncDailyCashMoney();
            }
        });
    } else {
        populatePaymentTypes();
        syncDailyCashMoney();
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
    $.ajax({url: id ? apiBase + '/' + id : apiBase + '/create', method: 'POST', data: $(this).serialize(), dataType: 'json',
        success: function(r) { if (r.status === 'success') { $('#cashModal').modal('hide'); loadTable(); } else alert('Error: ' + r.message); },
        error: function(xhr) { alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error de conexión')); }
    });
});

$('#payment_type_id, #opening_balance, #total_income, #total_expense').on('change keyup', syncDailyCashMoney);
$(document).ready(function() { loadCatalog(function() { loadTable(); }); });
</script>
