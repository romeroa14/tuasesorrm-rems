<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-hand-holding-usd text-primary"></i> <?= esc($title ?? 'Finanzas — Cuotas') ?>
        </h1>
        <div>
            <button class="btn btn-outline-primary btn-sm mr-2" onclick="showPlanModal()">
                <i class="fas fa-file-invoice-dollar"></i> Nuevo plan de pago
            </button>
            <button class="btn btn-primary btn-sm" onclick="showModal('create')">
                <i class="fas fa-plus"></i> Registrar pago
            </button>
        </div>
    </div>

    <div class="row">
        <div class="col-lg-4 mb-4">
            <div class="card shadow h-100">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users"></i> Clientes / planes</h6>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush" id="plansList">
                        <div class="list-group-item text-muted small">Cargando...</div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-8 mb-4">
            <div class="card shadow mb-3" id="planHeaderCard" style="display:none;">
                <div class="card-header py-3 bg-dark text-white">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-table"></i> Plan de pago</h6>
                </div>
                <div class="card-body">
                    <div class="row" id="planHeaderFields"></div>
                    <hr>
                    <div class="row text-center">
                        <div class="col-md-4"><div class="text-xs text-uppercase text-muted">Financiado</div><div class="h5" id="sumScheduled">$0.00</div></div>
                        <div class="col-md-4"><div class="text-xs text-uppercase text-success">Pagado</div><div class="h5 text-success" id="sumPaid">$0.00</div></div>
                        <div class="col-md-4"><div class="text-xs text-uppercase text-danger">Pendiente</div><div class="h5 text-danger" id="sumPending">$0.00</div></div>
                    </div>
                </div>
            </div>

            <div class="card shadow" id="planScheduleCard" style="display:none;">
                <div class="card-header py-3">
                    <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list-ol"></i> Tabla de amortización</h6>
                </div>
                <div class="card-body p-0">
                    <div class="table-responsive">
                        <table class="table table-sm table-bordered mb-0" id="scheduleTable">
                            <thead class="thead-light">
                                <tr>
                                    <th>N° Cuota</th>
                                    <th>Mes</th>
                                    <th class="text-right">Monto</th>
                                    <th class="text-right">Pagado</th>
                                    <th class="text-right">Pendiente</th>
                                    <th>Estado</th>
                                    <th></th>
                                </tr>
                            </thead>
                            <tbody></tbody>
                        </table>
                    </div>
                </div>
            </div>

            <div class="card shadow mt-3" id="planEmptyState">
                <div class="card-body text-center text-muted py-5">
                    <i class="fas fa-hand-pointer fa-2x mb-3"></i>
                    <p class="mb-0">Selecciona un cliente para ver el plan de pago y las cuotas pendientes.</p>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4 mt-2">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-receipt"></i> Movimientos de cuotas registrados</h6>
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
                            <th>Método de pago</th>
                            <th>Equiv. USD</th>
                            <th>Equiv. BS</th>
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

<!-- Modal plan de pago -->
<div class="modal fade" id="planModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <div class="modal-header bg-dark text-white">
                <h5 class="modal-title"><i class="fas fa-file-invoice-dollar"></i> Nuevo plan de pago</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="planForm">
                <div class="modal-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <div class="d-flex justify-content-between align-items-center mb-1">
                                    <label class="mb-0">Cliente <span class="text-danger">*</span></label>
                                    <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCreateClientModal()">
                                        <i class="fas fa-user-plus"></i> Crear cliente
                                    </button>
                                </div>
                                <select class="form-control" name="lead_id" id="plan_lead_id" style="width:100%" required>
                                    <option value=""></option>
                                </select>
                                <small class="form-text text-muted">Busca por nombre, teléfono o correo en el CRM.</small>
                            </div>
                        </div>
                        <div class="col-md-6"><div class="form-group"><label>Proyecto</label><input type="text" class="form-control" name="project_name" placeholder="SKY"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Ofic./Apart. <span class="text-danger">*</span></label><input type="text" class="form-control" name="unit_ref" required placeholder="1912"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Mtrs²</label><input type="number" step="0.01" class="form-control" name="square_meters"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Reserva acordada</label><input type="number" step="0.01" class="form-control" name="reservation_amount"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Precio <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" name="total_price" id="plan_total_price" required></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Inicial</label><input type="number" step="0.01" class="form-control" name="down_payment" id="plan_down_payment" value="0"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Financiamiento</label><input type="number" step="0.01" class="form-control" name="financing_amount" id="plan_financing_amount" readonly></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-3"><div class="form-group"><label>Cuotas <span class="text-danger">*</span></label><input type="number" class="form-control" name="installments" id="plan_installments" value="24" min="1" required></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Monto cuota normal</label><input type="number" step="0.01" class="form-control" name="installment_amount" id="plan_installment_amount" placeholder="Auto"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Inicio financiamiento <span class="text-danger">*</span></label><input type="date" class="form-control" name="start_date" required value="<?= date('Y-m-d') ?>"></div></div>
                        <div class="col-md-3"><div class="form-group"><label>Moneda</label><select class="form-control" name="currency_code"><option value="USD">USD</option></select></div></div>
                    </div>
                    <div class="form-group mb-0"><label>Notas</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-dark"><i class="fas fa-save"></i> Generar tabla</button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- Modal cuota / pago -->
<div class="modal fade" id="quotaModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-hand-holding-usd"></i> Registrar pago</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="quotaForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <input type="hidden" id="financing_plan_id" name="financing_plan_id">
                    <input type="hidden" id="installment_id" name="installment_id">

                    <div class="alert alert-info py-2" id="installmentHint" style="display:none;"></div>

                    <div class="card bg-light mb-3">
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Tipo <span class="text-danger">*</span></label>
                                        <select class="form-control" id="type" name="type" required>
                                            <option value="received">Recibida (genera ingreso)</option>
                                            <option value="delivered">Entregada</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="form-group">
                                        <label>Nombre / Cliente <span class="text-danger">*</span></label>
                                        <input type="text" class="form-control" id="name" name="name" required>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"><div class="form-group"><label>Fecha recepción <span class="text-danger">*</span></label><input type="date" class="form-control" id="receipt_date" name="receipt_date" required value="<?= date('Y-m-d') ?>"></div></div>
                                <div class="col-md-4"><div class="form-group"><label>Fecha entrega</label><input type="date" class="form-control" id="delivery_date" name="delivery_date"></div></div>
                                <div class="col-md-4"><div class="form-group"><label>N° Recibo <span class="text-danger">*</span></label><input type="text" class="form-control" id="receipt_number" name="receipt_number" required></div></div>
                            </div>
                            <?php echo view('auth/finance/partials/payment_amount_fields'); ?>
                            <div class="form-group mb-0"><label>Notas</label><textarea class="form-control" id="notes" name="notes" rows="2"></textarea></div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar y validar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= view('auth/finance/partials/create_client_modal') ?>

<script src="<?= base_url('/js/finance-catalog-money.js') ?>"></script>
<script>
var dt, editMode = false, moneyHelper;
var lastClientSearch = '';
var apiBase = '<?= base_url('/app/finance/quotas/api') ?>';
var planApiBase = '<?= base_url('/app/finance/financing/api') ?>';
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';
var clientsSearchUrl = '<?= base_url('/app/finance/api/clients/search') ?>';
var clientsCreateUrl = '<?= base_url('/app/finance/api/clients/create') ?>';
var currentFilter = '<?= esc($current_type ?? '') ?>';
var selectedPlanId = null;
var selectedPlan = null;

function moneyFmt(v) {
    return '$' + parseFloat(v || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatClientOptionLabel(client) {
    var label = client.name || 'Sin nombre';
    if (client.phone) label += ' — ' + client.phone;
    return label;
}

function setPlanLeadSelection(id, text) {
    if (!$('#plan_lead_id').length) return;
    var option = new Option(text, id, true, true);
    $('#plan_lead_id').append(option).trigger('change');
}

function openCreateClientModal() {
    $('#createClientForm')[0].reset();
    var prefill = (lastClientSearch || '').trim();
    if (prefill && !/^\d/.test(prefill)) {
        $('#create_client_name').val(prefill);
    } else if (prefill) {
        $('#create_client_phone').val(prefill);
    }
    $('#createClientModal').modal('show');
}

function initPlanLeadSelect() {
    if (!$('#plan_lead_id').length) return;
    if ($('#plan_lead_id').hasClass('select2-hidden-accessible')) return;

    $('#plan_lead_id').select2({
        dropdownParent: $('#planModal'),
        placeholder: 'Buscar cliente...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 2,
        language: {
            inputTooShort: function() { return 'Escribe al menos 2 caracteres'; },
            noResults: function() { return 'Sin resultados — usa "Crear cliente"'; },
            searching: function() { return 'Buscando...'; }
        },
        ajax: {
            url: clientsSearchUrl,
            dataType: 'json',
            delay: 300,
            data: function(params) {
                lastClientSearch = params.term || '';
                return { q: params.term || '' };
            },
            processResults: function(response) {
                var items = (response.data || []).map(function(c) {
                    return { id: c.id, text: formatClientOptionLabel(c) };
                });
                return { results: items };
            }
        }
    });
}

function resetPlanLeadSelect() {
    if ($('#plan_lead_id').hasClass('select2-hidden-accessible')) {
        $('#plan_lead_id').val(null).trigger('change');
    }
}

function recalcFinancing() {
    var price = parseFloat($('#plan_total_price').val() || 0);
    var inicial = parseFloat($('#plan_down_payment').val() || 0);
    $('#plan_financing_amount').val(Math.max(0, price - inicial).toFixed(2));
}

$('#plan_total_price, #plan_down_payment').on('input', recalcFinancing);

function statusBadge(status) {
    var map = { pending: 'secondary', partial: 'warning', paid: 'success', overdue: 'danger' };
    var labels = { pending: 'Pendiente', partial: 'Parcial', paid: 'Pagada', overdue: 'Vencida' };
    return '<span class="badge badge-' + (map[status] || 'secondary') + '">' + (labels[status] || status) + '</span>';
}

function loadPlans() {
    $.post(planApiBase + '/list', function(res) {
        var html = '';
        if (res.status !== 'success' || !res.data.length) {
            html = '<div class="list-group-item text-muted">No hay planes. Crea uno con "Nuevo plan de pago".</div>';
        } else {
            res.data.forEach(function(p) {
                var active = String(selectedPlanId) === String(p.id) ? ' active' : '';
                html += '<a href="#" class="list-group-item list-group-item-action' + active + '" data-plan-id="' + p.id + '">' +
                    '<div class="font-weight-bold">' + (p.client_name || 'Sin cliente') + '</div>' +
                    '<div class="small text-muted">' + (p.lead_phone || '') + '</div>' +
                    '<div class="small">' + (p.project_name || '—') + ' · Apt ' + (p.unit_ref || '—') + '</div>' +
                    '<div class="small text-danger">Pendiente: ' + moneyFmt(p.pending_total) + '</div></a>';
            });
        }
        $('#plansList').html(html);
        $('#plansList a').on('click', function(e) {
            e.preventDefault();
            selectPlan($(this).data('plan-id'));
        });
    });
}

function selectPlan(id) {
    selectedPlanId = id;
    $.get(planApiBase + '/' + id, function(res) {
        if (res.status !== 'success') return;
        selectedPlan = res.data;
        renderPlan(selectedPlan);
        loadPlans();
    });
}

function renderPlan(plan) {
    $('#planEmptyState').hide();
    $('#planHeaderCard, #planScheduleCard').show();

    var fields = [
        ['Cliente', plan.client_name || '—'],
        ['Teléfono', plan.lead_phone || '—'],
        ['Correo', plan.lead_email || '—'],
        ['Proyecto', plan.project_name || '—'],
        ['Ofic./Apart.', plan.unit_ref || plan.property_ref || '—'],
        ['Precio', moneyFmt(plan.total_price)],
        ['Inicial', moneyFmt(plan.down_payment)],
        ['Mtrs²', plan.square_meters || '—'],
        ['Reserva', plan.reservation_amount ? moneyFmt(plan.reservation_amount) : '—'],
        ['Financiamiento', moneyFmt(plan.financing_amount)],
        ['Cuotas', plan.installments],
        ['Inicio', plan.start_date || '—'],
        ['Final', plan.end_date || '—']
    ];

    var headerHtml = '';
    fields.forEach(function(f) {
        headerHtml += '<div class="col-md-4 col-6 mb-2"><div class="text-xs text-uppercase text-muted">' + f[0] + '</div><div class="font-weight-bold">' + f[1] + '</div></div>';
    });
    $('#planHeaderFields').html(headerHtml);
    $('#sumScheduled').text(moneyFmt(plan.totals.scheduled));
    $('#sumPaid').text(moneyFmt(plan.totals.paid));
    $('#sumPending').text(moneyFmt(plan.totals.pending));

    var rows = '';
    (plan.installment_schedule || []).forEach(function(row) {
        var canPay = row.status !== 'paid';
        rows += '<tr>' +
            '<td>' + row.installment_number + '</td>' +
            '<td>' + (row.month_label || row.due_date) + '</td>' +
            '<td class="text-right">' + moneyFmt(row.amount) + '</td>' +
            '<td class="text-right">' + moneyFmt(row.paid_amount) + '</td>' +
            '<td class="text-right">' + moneyFmt(row.pending_amount) + '</td>' +
            '<td>' + statusBadge(row.status) + '</td>' +
            '<td>' + (canPay ? '<button class="btn btn-success btn-sm" onclick="payInstallment(' + row.id + ')"><i class="fas fa-dollar-sign"></i></button>' : '—') + '</td>' +
            '</tr>';
    });
    $('#scheduleTable tbody').html(rows);
}

function payInstallment(installmentId) {
    if (!selectedPlan) return;
    var row = (selectedPlan.installment_schedule || []).find(function(r) { return String(r.id) === String(installmentId); });
    if (!row) return;

    showModal('create');
    $('#financing_plan_id').val(selectedPlan.id);
    $('#installment_id').val(installmentId);
    $('#type').val('received');
    $('#name').val(selectedPlan.client_name || '');
    $('#amount').val(row.pending_amount || row.amount);
    $('#installmentHint').show().html(
        'Cuota <strong>#' + row.installment_number + '</strong> — ' + (row.month_label || row.due_date) +
        ' — Programada: ' + moneyFmt(row.amount) + ' — Pendiente: <strong>' + moneyFmt(row.pending_amount) + '</strong>'
    );
    moneyHelper.populatePaymentTypes();
    moneyHelper.refresh();
}

function paymentTypeLabel(id) {
    var paymentType = moneyHelper.getPaymentType(id);
    return paymentType ? (paymentType.name || paymentType.code || '—') : '—';
}

function showPlanModal() {
    $('#planForm')[0].reset();
    resetPlanLeadSelect();
    recalcFinancing();
    initPlanLeadSelect();
    $('#planModal').modal('show');
}

$('#planForm').submit(function(e) {
    e.preventDefault();
    if (!$('#plan_lead_id').val()) {
        alert('Selecciona un cliente del CRM.');
        return;
    }
    $.post(planApiBase + '/create', $(this).serialize(), function(res) {
        if (res.status === 'success') {
            $('#planModal').modal('hide');
            selectPlan(res.data.id);
            loadPlans();
        } else alert('Error: ' + (res.message || ''));
    }).fail(function(xhr) {
        alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) || 'Error de conexión'));
    });
});

$('#createClientForm').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: clientsCreateUrl,
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(r) {
            if (r.status !== 'success' || !r.data) {
                alert('Error: ' + (r.message || 'No se pudo crear el cliente'));
                return;
            }
            var client = r.data;
            setPlanLeadSelection(client.id, formatClientOptionLabel(client));
            $('#createClientModal').modal('hide');
            if (client.existing) {
                alert(client.message || 'Cliente existente seleccionado.');
            }
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            alert('Error: ' + msg);
        }
    });
});

function loadTable() {
    $.post(apiBase + '/list', { type: currentFilter }, function(response) {
        var rows = [];
        if (response.status === 'success' && Array.isArray(response.data)) {
            response.data.forEach(function(row) {
                var denomination = row.currency_denomination || 'USD';
                rows.push([
                    row.id,
                    row.type === 'received' ? 'Recibida' : 'Entregada',
                    row.name || '—',
                    row.receipt_number || '—',
                    moneyHelper.formatPrimaryAmount(row.amount, denomination),
                    paymentTypeLabel(row.payment_type_id),
                    '$ ' + formatFinanceMoney(parseFloat(row.amount_usd || 0)),
                    'Bs. ' + formatFinanceMoney(parseFloat(row.amount_bs || 0)),
                    parseFloat(row.exchange_rate || 0).toFixed(4),
                    row.receipt_date || '—',
                    '<button class="btn btn-danger btn-sm" onclick="remove(' + row.id + ')"><i class="fas fa-trash"></i></button>'
                ]);
            });
        }
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#quotasTable').DataTable({ data: rows, pageLength: 10, language: { url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json' } });
    });
}

function showModal(mode, id) {
    editMode = mode === 'edit';
    $('#record_id, #financing_plan_id, #installment_id').val('');
    $('#installmentHint').hide();
    $('#quotaForm')[0].reset();
    $('#type').val('received');
    if (mode !== 'edit' || !id) {
        moneyHelper.populatePaymentTypes();
        moneyHelper.refresh();
    }
    $('#quotaModal').modal('show');
}

function remove(id) {
    if (!confirm('¿Eliminar esta cuota?')) return;
    $.post(apiBase + '/' + id + '/delete', function(r) {
        if (r.status === 'success') { loadTable(); if (selectedPlanId) selectPlan(selectedPlanId); }
        else alert('Error: ' + (r.message || ''));
    });
}

$('#quotaForm').submit(function(e) {
    e.preventDefault();
    if (!$('#payment_type_id').val()) {
        alert('Selecciona un método de pago.');
        return;
    }
    var id = $('#record_id').val();
    $.ajax({
        url: id ? apiBase + '/' + id : apiBase + '/create',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(res) {
            if (res.status === 'success') {
                $('#quotaModal').modal('hide');
                loadTable();
                if (selectedPlanId) selectPlan(selectedPlanId);
            } else alert('Error: ' + (res.message || ''));
        },
        error: function(xhr) {
            alert('Error: ' + ((xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error de conexión'));
        }
    });
});

$(document).ready(function() {
    moneyHelper = new FinanceCatalogMoney({ catalogUrl: catalogUrl, amountLabel: '#amount_label' });
    moneyHelper.bind();
    initPlanLeadSelect();
    moneyHelper.loadCatalog(function() {
        loadPlans();
        loadTable();
    });
});
</script>
