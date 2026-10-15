<style>
#incomeModal .select2-container { width: 100% !important; }
#incomeModal .select2-container--default .select2-selection--single {
    height: 38px;
    border: 1px solid #d1d3e2;
    border-radius: .35rem;
}
#incomeModal .select2-container--default .select2-selection--single .select2-selection__rendered {
    line-height: 36px;
    padding-left: 12px;
}
#incomeModal .select2-container--default .select2-selection--single .select2-selection__arrow {
    height: 36px;
}
#incomeModal .select2-dropdown {
    z-index: 2060;
    border-color: #d1d3e2;
}
#incomeModal .select2-search--dropdown .select2-search__field {
    border: 1px solid #d1d3e2;
    border-radius: .25rem;
    padding: .35rem .5rem;
}
#incomeModal .plan-of-client-card {
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: .35rem;
    padding: .75rem 1rem;
}
#incomeModal .form-label-fixed {
    min-height: 1.25rem;
    margin-bottom: .35rem;
    display: block;
    line-height: 1.25rem;
    white-space: nowrap;
}
#incomeModal .form-control,
#incomeModal select.form-control {
    height: 38px;
}
#incomeModal textarea.form-control {
    height: auto;
}
#incomeModal .income-readonly {
    background: #f8f9fc;
    font-weight: 700;
    color: #1cc88a;
}
#incomeModal .scheme-line {
    display: flex;
    align-items: center;
    gap: .5rem;
}
#incomeModal .scheme-line select {
    flex: 1 1 auto;
    min-width: 0;
}
#incomeModal .custom-pct-wrap {
    display: none;
    align-items: center;
    gap: .3rem;
    flex: 0 0 auto;
}
#incomeModal .custom-pct-wrap.is-visible {
    display: flex;
}
#incomeModal .custom-pct-wrap input {
    width: 68px;
    height: 38px;
    padding: .25rem .4rem;
}
#incomeModal .income-fx-strip {
    display: grid;
    grid-template-columns: repeat(4, minmax(0, 1fr));
    gap: .75rem;
    background: #f8f9fc;
    border: 1px solid #e3e6f0;
    border-radius: .35rem;
    padding: .65rem .85rem;
    margin-bottom: 1rem;
}
#incomeModal .income-fx-strip .fx-k {
    font-size: .7rem;
    text-transform: uppercase;
    letter-spacing: .03em;
    color: #858796;
    margin-bottom: .1rem;
}
#incomeModal .income-fx-strip .fx-v {
    font-weight: 600;
    color: #3a3b45;
    min-height: 1.25rem;
}
#incomeModal .income-section {
    margin-bottom: .25rem;
}
</style>
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-money-bill-wave text-success"></i> <?= esc($title ?? 'Finanzas — Ingresos') ?>
        </h1>
        <div>
            <?php if ($can_draft ?? false): ?>
            <button class="btn btn-success btn-sm mr-2" onclick="showModal()">
                <i class="fas fa-plus"></i> Nuevo Ingreso
            </button>
            <?php endif; ?>
            <div class="btn-group d-inline-block">
                <button class="btn btn-outline-primary btn-sm dropdown-toggle" data-toggle="dropdown">
                    <i class="fas fa-filter"></i> Filtrar por tipo
                </button>
                <div class="dropdown-menu">
                    <a class="dropdown-item" href="<?= base_url('/app/finance/income') ?>">Todos</a>
                    <?php foreach ($income_types as $key => $label): ?>
                    <a class="dropdown-item <?= ($current_type ?? '') === $key ? 'active' : '' ?>"
                       href="<?= base_url('/app/finance/income?type=' . $key) ?>">
                        <?= esc($label) ?>
                    </a>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Rango de Fechas</h6>
        </div>
        <div class="card-body">
            <form id="dateFilterForm" class="form-inline">
                <div class="form-group mr-3">
                    <label class="mr-2">Desde:</label>
                    <input type="date" class="form-control form-control-sm" id="date_from" value="<?= esc($date_from) ?>">
                </div>
                <div class="form-group mr-3">
                    <label class="mr-2">Hasta:</label>
                    <input type="date" class="form-control form-control-sm" id="date_to" value="<?= esc($date_to) ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-sm"><i class="fas fa-search"></i> Filtrar</button>
            </form>
        </div>
    </div>

    <?php if ($can_approve ?? false): ?>
    <div class="card shadow mb-4 border-left-warning" id="pendingCard" style="display:none;">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-warning"><i class="fas fa-clock"></i> Ingresos Pendientes de Aprobación</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm" id="pendingTable">
                    <thead><tr><th>ID</th><th>Tipo</th><th>Monto</th><th>Fecha</th><th>Acciones</th></tr></thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary">
                <i class="fas fa-list"></i>
                <?= $current_type ? esc($income_types[$current_type] ?? 'Desconocido') : 'Todos los Ingresos' ?>
            </h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="incomeTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Cliente</th>
                            <th>Pago total</th>
                            <th>Comisión</th>
                            <th>Descripción</th>
                            <th>Fecha</th>
                            <th>Estado</th>
                            <th></th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="incomeModal" tabindex="-1">
    <div class="modal-dialog modal-xl">
        <div class="modal-content">
            <div class="modal-header bg-gradient-success text-white">
                <h5 class="modal-title" id="incomeModalTitle"><i class="fas fa-plus-circle"></i> Nuevo Ingreso</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="incomeForm">
                <input type="hidden" name="id" id="income_id" value="">
                <div class="modal-body">
                    <div class="form-group">
                        <div class="d-flex justify-content-between align-items-center mb-1">
                            <label class="mb-0">Cliente <span class="text-danger">*</span></label>
                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCreateClientModal()">
                                <i class="fas fa-user-plus"></i> Crear cliente
                            </button>
                        </div>
                        <select class="form-control" name="lead_id" id="lead_id" style="width:100%">
                            <option value=""></option>
                        </select>
                        <small class="form-text text-muted" id="client_plan_hint">Elige el cliente. Con él se cargan origen y comisión de la venta.</small>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-fixed">Origen del cliente</label>
                                <select class="form-control" name="client_source" id="client_source">
                                    <?php foreach (($client_source_options ?? []) as $code => $label): ?>
                                    <option value="<?= esc($code) ?>"><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-fixed">Tipo de ingreso <span class="text-danger">*</span></label>
                                <select class="form-control" name="movement_type" id="movement_type" required>
                                    <option value="">Seleccionar...</option>
                                    <?php
                                    $selectedIncomeType = (($current_type ?? '') === 'ventas_secundarias')
                                        ? 'ventas_secundarias'
                                        : 'ventas_primaria';
                                    foreach ($income_types as $key => $label):
                                    ?>
                                    <option value="<?= esc($key) ?>" <?= $selectedIncomeType === $key ? 'selected' : '' ?>><?= esc($label) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label-fixed">Cuenta <span class="text-danger">*</span></label>
                                <select class="form-control" name="account_id" id="account_id" required></select>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label-fixed">Fecha <span class="text-danger">*</span></label>
                                <input type="date" class="form-control" name="occurred_on" id="occurred_on" required value="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label-fixed">Método de pago <span class="text-danger">*</span></label>
                                <select class="form-control" name="payment_type_id" id="payment_type_id" required></select>
                            </div>
                        </div>
                    </div>
                    <div class="row" id="regularAmountRow" style="display:none;">
                        <div class="col-md-4">
                            <div class="form-group">
                                <label class="form-label-fixed" id="amount_label">Monto <span class="text-danger">*</span></label>
                                <input type="number" step="0.01" class="form-control" name="amount" id="amount" required min="0.01">
                            </div>
                        </div>
                    </div>
                    <div id="saleAmountBlock">
                        <div class="row align-items-start">
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label-fixed">Monto total (USD) <span class="text-danger">*</span></label>
                                    <input type="number" step="0.01" class="form-control" name="sale_price" id="sale_price" min="0">
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label-fixed">Tipo de comisión</label>
                                    <div class="scheme-line">
                                        <select class="form-control" name="scheme_code" id="scheme_code">
                                            <option value="">Seleccionar...</option>
                                        </select>
                                        <div id="customPctWrap" class="custom-pct-wrap">
                                            <input type="number" step="0.01" class="form-control" name="commission_pct" id="commission_pct" min="0" placeholder="0">
                                            <span class="small text-muted">%</span>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="form-group">
                                    <label class="form-label-fixed">Comisión empresa</label>
                                    <input type="text" class="form-control income-readonly" id="company_commission_display" readonly title="Monto total × %. Ese valor es el ingreso que se contabiliza.">
                                </div>
                            </div>
                        </div>
                    </div>
                    <input type="hidden" name="currency_id" id="currency_id">
                    <input type="hidden" name="rate_to_base" id="rate_to_base">
                    <input type="hidden" name="currency_denomination" id="currency_denomination">
                    <div class="income-fx-strip">
                        <div>
                            <div class="fx-k">Denominación</div>
                            <div class="fx-v" id="detected_denomination"></div>
                        </div>
                        <div>
                            <div class="fx-k">Tasa Bs/USD</div>
                            <div class="fx-v" id="display_rate_to_base"></div>
                        </div>
                        <div>
                            <div class="fx-k">Equivalente USD</div>
                            <div class="fx-v" id="equivalent_usd"></div>
                        </div>
                        <div>
                            <div class="fx-k">Equivalente Bs</div>
                            <div class="fx-v" id="equivalent_bs"></div>
                        </div>
                    </div>
                    <div class="row">
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-fixed">Descripción</label>
                                <input type="text" class="form-control" name="description" id="description" placeholder="Detalle del ingreso">
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label class="form-label-fixed">Notas</label>
                                <input type="text" class="form-control" name="notes" id="notes" placeholder="Opcional">
                            </div>
                        </div>
                    </div>

                    <input type="hidden" name="belongs_to_plan" id="belongs_to_plan" value="0">
                    <input type="hidden" name="financing_plan_id" id="financing_plan_id" value="">

                    <div class="card border-left-warning mb-0" id="commissionCard">
                        <div class="card-body py-3">
                            <h6 class="font-weight-bold text-warning mb-2">Comisión de vendedores</h6>
                            <input type="hidden" name="create_commission" id="create_commission" value="0">
                            <input type="hidden" name="commission_reference" id="commission_reference" value="">
                            <input type="hidden" name="sale_date" id="sale_date" value="">
                            <input type="hidden" name="registration_fee" id="registration_fee" value="0">
                            <div id="sellersCommissionWrap">
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label-fixed">Captó la venta <span class="text-success" id="captador_share_label"></span></label>
                                            <select class="form-control" name="captador_user_id" id="captador_user_id"></select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label-fixed">Closer <span class="text-success" id="closer_share_label"></span></label>
                                            <select class="form-control" name="closer_user_id" id="closer_user_id"></select>
                                        </div>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label-fixed">Perfil</label>
                                            <select class="form-control" name="company_role_code" id="company_role_code"></select>
                                        </div>
                                    </div>
                                    <div class="col-md-6">
                                        <div class="form-group">
                                            <label class="form-label-fixed">Persona <span class="text-success" id="company_role_share_label"></span></label>
                                            <select class="form-control" name="company_role_user_id" id="company_role_user_id"></select>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success" id="incomeSubmitBtn"><i class="fas fa-save"></i> Registrar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<?= view('auth/finance/partials/create_client_modal') ?>

<script>
var dt, catalog = null;
var lastClientSearch = '';
var apiBase = '<?= base_url('/app/finance/income/api') ?>';
var canDraftIncome = <?= ! empty($can_draft) ? 'true' : 'false' ?>;
var incomeRowsCache = {};
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';
var clientsSearchUrl = '<?= base_url('/app/finance/api/clients/search') ?>';
var clientsCreateUrl = '<?= base_url('/app/finance/api/clients/create') ?>';
var pendingUrl = '<?= base_url('/app/finance/api/pending') ?>';
var workflowUrl = '<?= base_url('/app/finance/workflows') ?>';
var planApiBase = '<?= base_url('/app/finance/financing/api') ?>';
var currentType = '<?= $current_type ?? '' ?>';
var dateFrom = '<?= esc($date_from) ?>';
var dateTo = '<?= esc($date_to) ?>';
var canApprove = <?= ($can_approve ?? false) ? 'true' : 'false' ?>;
var plansCache = [];
var hydratingForm = false;

function getCurrencyContext() {
    return catalog && catalog.currency_context ? catalog.currency_context : {};
}

function formatMoney(n) {
    return (parseFloat(n || 0)).toLocaleString('en-US', {minimumFractionDigits: 2, maximumFractionDigits: 2});
}

function getPaymentType(id) {
    if (!catalog || !catalog.payment_types) return null;
    return catalog.payment_types.find(function(p) { return String(p.id) === String(id); }) || null;
}

function formatPrimaryAmount(amount, currencyId) {
    var context = getCurrencyContext();
    var denomination = String(currencyId) === String(context.bs_currency_id) ? 'BS' : 'USD';
    var prefix = denomination === 'BS' ? 'Bs. ' : '$ ';
    return prefix + formatMoney(parseFloat(amount || 0));
}

function refreshCurrencyDetection() {
    var context = getCurrencyContext();
    var paymentType = getPaymentType($('#payment_type_id').val());
    var denomination = paymentType && paymentType.default_denomination ? paymentType.default_denomination : 'USD';
    var bsRate = parseFloat(context.latest_bs_rate || 0);
    var amount = parseFloat($('#amount').val() || 0);

    $('#currency_denomination').val(denomination);
    $('#detected_denomination').text(denomination);
    $('#currency_id').val(denomination === 'BS' ? (context.bs_currency_id || '') : (context.usd_currency_id || ''));
    $('#rate_to_base').val(denomination === 'BS' ? (bsRate > 0 ? bsRate.toFixed(6) : '') : '1');
    $('#display_rate_to_base').text(bsRate > 0 ? bsRate.toFixed(4) : 'Sin tasa');
    $('#amount_label').html((denomination === 'BS' ? 'Monto en Bs.' : 'Monto en USD') + ' <span class="text-danger">*</span>');

    if (!isFinite(amount) || amount <= 0) {
        $('#equivalent_usd').text('—');
        $('#equivalent_bs').text('—');
        return;
    }

    var equivalentUsd = denomination === 'BS'
        ? (bsRate > 0 ? amount / bsRate : 0)
        : amount;
    var equivalentBs = denomination === 'BS'
        ? amount
        : (bsRate > 0 ? amount * bsRate : 0);

    $('#equivalent_usd').text(formatMoney(equivalentUsd));
    $('#equivalent_bs').text(formatMoney(equivalentBs));
}

function formatClientOptionLabel(client) {
    var label = client.name || 'Sin nombre';
    if (client.phone) label += ' — ' + client.phone;
    return label;
}

function setLeadSelection(id, text) {
    if (!$('#lead_id').length) return;
    var option = new Option(text, id, true, true);
    $('#lead_id').append(option).trigger('change');
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

function formatClientLabel(row) {
    if (!row.lead_name) return '—';
    var label = row.lead_name;
    if (row.lead_phone) label += ' (' + row.lead_phone + ')';
    return label;
}

function initLeadSelect() {
    if (!$('#lead_id').length) return;
    if ($('#lead_id').hasClass('select2-hidden-accessible')) {
        return;
    }
    $('#lead_id').select2({
        dropdownParent: $('#incomeModal'),
        placeholder: 'Escribe para filtrar o abre la lista...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        language: {
            noResults: function() { return 'Sin resultados — usa "Crear cliente"'; },
            searching: function() { return 'Buscando...'; }
        },
        ajax: {
            url: clientsSearchUrl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                lastClientSearch = params.term || '';
                return { q: params.term || '', limit: 10 };
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

function loadCatalog(cb) {
    $.get(catalogUrl, function(r) {
        if (r.status === 'success') {
            catalog = r.data;
            var accOpts = '<option value="">Seleccionar...</option>';
            (catalog.accounts || []).forEach(function(a) {
                accOpts += '<option value="' + a.id + '">' + a.name + '</option>';
            });
            $('#account_id').html(accOpts);

            var payOpts = '<option value="">Seleccionar...</option>';
            (catalog.payment_types || []).forEach(function(p) {
                payOpts += '<option value="' + p.id + '">' + (p.name || p.code || '—') + '</option>';
            });
            $('#payment_type_id').html(payOpts);
            var schemeOpts = '<option value="">Seleccionar...</option>';
            (catalog.commission_schemes || []).forEach(function(s) {
                schemeOpts += '<option value="' + s.code + '">' + s.label + '</option>';
            });
            $('#scheme_code').html(schemeOpts);
            refreshCurrencyDetection();
            fillCommissionUsers();
            refreshCommissionPreview();
        }
        if (cb) cb();
    });
}

function userOptionsHtml(selected) {
    var opts = '<option value="">Seleccionar persona...</option>';
    ((catalog && catalog.users) || []).forEach(function(u) {
        var sel = String(u.id) === String(selected || '') ? ' selected' : '';
        opts += '<option value="' + u.id + '"' + sel + '>' + (u.full_name || u.name || ('Usuario #' + u.id)) + '</option>';
    });
    return opts;
}

function companyRoleOptionsHtml(selected) {
    var opts = '<option value="">Seleccionar perfil...</option>';
    (commissionProfile().roles || []).forEach(function(role) {
        if (parseInt(role.active, 10) === 0) return;
        var pct = parseFloat(role.pct_of_company || 0);
        var sel = String(role.code) === String(selected || '') ? ' selected' : '';
        opts += '<option value="' + role.code + '"' + sel + '>' +
            (role.label || role.code) + (pct > 0 ? ' — ' + pct + '%' : '') + '</option>';
    });
    return opts;
}

function selectedCompanyRoleFromMap(selectedMap) {
    var codes = ['coordinator', 'atc', 'marketing'];
    for (var i = 0; i < codes.length; i++) {
        if (selectedMap[codes[i]]) return codes[i];
    }
    return $('#company_role_code').val() || '';
}

function bindCompanyRoleUserName(code) {
    $('#company_role_user_id').attr('name', code ? ('role_user[' + code + ']') : 'company_role_user_id');
}

function fillCommissionUsers(selectedMap) {
    selectedMap = selectedMap || {};
    var captadorVal = selectedMap.captador || $('#captador_user_id').val();
    var closerVal = selectedMap.closer || $('#closer_user_id').val();
    $('#captador_user_id').html(userOptionsHtml(captadorVal));
    $('#closer_user_id').html(userOptionsHtml(closerVal));
    var roleCode = selectedCompanyRoleFromMap(selectedMap);
    $('#company_role_code').html(companyRoleOptionsHtml(roleCode));
    var userVal = (roleCode && selectedMap[roleCode]) || $('#company_role_user_id').val() || '';
    $('#company_role_user_id').html(userOptionsHtml(userVal));
    bindCompanyRoleUserName(roleCode);
}

function planLabel(p) {
    return [p.project_name, p.unit_ref].filter(Boolean).join(' — ') || ('Plan #' + p.id);
}

function normalizePersonName(value) {
    return String(value || '')
        .split('—')[0]
        .split(' - ')[0]
        .replace(/\s+/g, ' ')
        .trim()
        .toUpperCase();
}

function selectedClientName() {
    var text = $('#lead_id option:selected').text() || '';
    return normalizePersonName(text);
}

function clientPlans() {
    var leadId = $('#lead_id').val();
    var leadName = selectedClientName();
    if (!leadId && !leadName) return [];
    return plansCache.filter(function(p) {
        if (leadId && p.lead_id && String(p.lead_id) === String(leadId)) return true;
        if (leadName && normalizePersonName(p.client_name) === leadName) return true;
        return false;
    });
}

function loadPlans() {
    return $.post(planApiBase + '/list').then(function(res) {
        plansCache = (res && res.status === 'success' && Array.isArray(res.data)) ? res.data : [];
        return plansCache;
    });
}

function applyClientContext() {
    var leadId = $('#lead_id').val();
    if (!leadId) {
        $('#client_plan_hint').text('Elige el cliente. Con él se cargan origen y comisión de la venta.');
        return;
    }
    $('#client_plan_hint').text('Cliente seleccionado.');
    togglePlanAndCommission();
}

function renderClientPlanPicker(selectedPlanId) {
    var plans = clientPlans();
    var $sel = $('#plan_of_client');
    if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.select2('destroy');
    }
    $sel.empty();
    plans.forEach(function(p) {
        $sel.append($('<option>').val(String(p.id)).text(planLabel(p)));
    });
    var planId = '';
    if (selectedPlanId && plans.some(function(p) { return String(p.id) === String(selectedPlanId); })) {
        planId = String(selectedPlanId);
    } else if (plans.length === 1) {
        planId = String(plans[0].id);
    }
    $sel.val(planId);
    $('#financing_plan_id').val(planId);
    $('#planClientEmpty').toggle(plans.length === 0);
    $('#planClientReady').toggle(plans.length > 0);
    $('#planSingleWrap').toggle(plans.length === 1);
    $('#planMultiWrap').toggle(plans.length > 1);
    if (plans.length === 1) {
        $('#planSingleLabel').text(planLabel(plans[0]));
        $('#planClientSummary').text('');
        return;
    }
    var chosen = plans.find(function(p) { return String(p.id) === planId; });
    $('#planClientSummary').text(chosen ? ('Se cruzará con ' + planLabel(chosen) + '.') : 'Elige el proyecto / unidad de este cliente.');
}

function isSaleIncomeType() {
    var type = $('#movement_type').val() || '';
    return type === 'ventas_primaria' || type === 'ventas_secundarias';
}

function defaultSchemeForType() {
    return $('#movement_type').val() === 'ventas_secundarias' ? 'secondary_1' : 'primary_1';
}

function togglePlanAndCommission() {
    var usesSale = isSaleIncomeType();
    $('#belongs_to_plan').val('0');
    $('#financing_plan_id').val('');
    $('#saleAmountBlock').toggle(usesSale);
    $('#regularAmountRow').toggle(!usesSale);
    $('#commissionCard').toggle(usesSale);
    $('#amount').prop('readonly', usesSale);
    $('#sale_price').prop('required', usesSale);
    if (usesSale) {
        $('#amount').attr('title', 'Se calcula solo: precio de venta × %');
    } else {
        $('#amount').removeAttr('title');
        $('#company_commission_display').val('');
    }
    if (usesSale && !$('#scheme_code').val()) {
        $('#scheme_code').val(defaultSchemeForType());
        applySchemePct();
    }
    toggleCustomPct();
    $('#sale_date').val($('#sale_date').val() || $('#occurred_on').val() || '');
    refreshCommissionPreview();
}

function commissionSchemes() {
    return (catalog && catalog.commission_schemes) ? catalog.commission_schemes : [];
}

function commissionProfile() {
    return (catalog && catalog.commission_profile) ? catalog.commission_profile : {captador_pct: 25, closer_pct: 25, company_pct: 50, roles: []};
}

function isCustomScheme() {
    var code = $('#scheme_code').val();
    var scheme = commissionSchemes().find(function(s) { return s.code === code; });
    return !!(scheme && scheme.is_custom);
}

function toggleCustomPct() {
    var custom = isCustomScheme();
    $('#customPctWrap').toggleClass('is-visible', custom).toggle(custom);
    if (!custom) applySchemePct();
}

function applySchemePct() {
    var code = $('#scheme_code').val();
    var scheme = commissionSchemes().find(function(s) { return s.code === code; });
    if (!scheme || scheme.is_custom) return;
    $('#commission_pct').val(scheme.default_pct);
}

function refreshCommissionPreview() {
    var price = parseFloat($('#sale_price').val() || 0);
    var pct = parseFloat($('#commission_pct').val() || 0);
    var base = price > 0 && pct > 0 ? roundMoney(price * pct / 100) : 0;
    $('#create_commission').val(base > 0 ? '1' : '0');
    $('#company_commission_display').val(base > 0 ? '$ ' + formatMoney(base) : '');
    $('#sellersCommissionWrap').toggle(isSaleIncomeType());
    syncIncomeAmountFromSale(base);
    if (base <= 0) {
        $('#captador_share_label').text('');
        $('#closer_share_label').text('');
        $('#company_role_share_label').text('');
        return;
    }
    var profile = commissionProfile();
    var company = base * (parseFloat(profile.company_pct || 50) / 100);
    var captador = base * (parseFloat(profile.captador_pct || 25) / 100);
    var closer = base * (parseFloat(profile.closer_pct || 25) / 100);
    $('#captador_share_label').text('$' + formatMoney(captador));
    $('#closer_share_label').text('$' + formatMoney(closer));
    if (!$('#captador_user_id option').length) {
        fillCommissionUsers();
    }
    var roleCode = $('#company_role_code').val();
    var role = (profile.roles || []).find(function(r) { return String(r.code) === String(roleCode); });
    var roleShare = role ? company * (parseFloat(role.pct_of_company || 0) / 100) : 0;
    $('#company_role_share_label').text(roleShare > 0 ? '$' + formatMoney(roleShare) : '');
}

function roundMoney(n) {
    return Math.round((n + Number.EPSILON) * 100) / 100;
}

function syncIncomeAmountFromSale(base) {
    if (hydratingForm) return;
    if (!isSaleIncomeType()) return;
    if (!(base > 0)) return;
    var denomination = $('#currency_denomination').val() || 'USD';
    var context = getCurrencyContext();
    var bsRate = parseFloat(context.latest_bs_rate || 0);
    var amount = denomination === 'BS' && bsRate > 0 ? roundMoney(base * bsRate) : base;
    if (String($('#amount').val()) !== String(amount.toFixed(2))) {
        $('#amount').val(amount.toFixed(2));
        refreshCurrencyDetection();
    }
}

function applyPlanToForm(planId, opts) {
    if (!planId) return;
    opts = opts || {};
    $.get(planApiBase + '/' + planId, function(res) {
        if (!res || res.status !== 'success' || !res.data) return;
        var p = res.data;
        $('#financing_plan_id').val(p.id);
        if (!opts.keepClient && p.lead_id && p.client_name) {
            setLeadSelection(p.lead_id, formatClientOptionLabel({name: p.client_name, phone: p.lead_phone}));
        }
        if (!hydratingForm) {
            if (p.client_source) $('#client_source').val(p.client_source);
            var desc = [p.project_name, p.unit_ref || p.property_ref].filter(Boolean).join(' ');
            if (desc && !$('#description').val()) $('#description').val('Venta ' + desc);
            if (p.sale_date) $('#sale_date').val(p.sale_date);
            $('#commission_reference').val(desc);
            if (p.total_price && !$('#sale_price').val()) $('#sale_price').val(p.total_price);
        }
        $('#planClientSummary').text('Se cruzará con ' + planLabel(p) + '.');
        togglePlanAndCommission();
        refreshCommissionPreview();
    });
}

function setModalMode(mode) {
    var isEdit = mode === 'edit';
    $('#incomeModalTitle').html(isEdit
        ? '<i class="fas fa-edit"></i> Editar ingreso'
        : '<i class="fas fa-plus-circle"></i> Nuevo Ingreso');
    $('#incomeSubmitBtn').html(isEdit
        ? '<i class="fas fa-save"></i> Guardar cambios'
        : '<i class="fas fa-save"></i> Registrar');
    $('#incomeModal .modal-header').toggleClass('bg-gradient-success', !isEdit).toggleClass('bg-gradient-primary', isEdit);
}

function resetIncomeForm() {
    $('#incomeForm')[0].reset();
    $('#income_id').val('');
    $('#create_commission').val('0');
    $('#belongs_to_plan').val('0');
    $('#financing_plan_id').val('');
    $('#client_plan_hint').text('Elige el cliente. Con él se cargan origen y comisión de la venta.');
    $('#commission_pct').val('3');
    $('#registration_fee').val('0');
    if ($('#lead_id').hasClass('select2-hidden-accessible')) {
        $('#lead_id').val(null).trigger('change');
    }
    var createType = currentType === 'ventas_secundarias' ? 'ventas_secundarias' : 'ventas_primaria';
    $('#movement_type').val(createType);
    if (isSaleIncomeType()) {
        $('#scheme_code').val(defaultSchemeForType());
        applySchemePct();
    }
    fillCommissionUsers();
    $('#occurred_on').val(new Date().toISOString().slice(0, 10));
    setModalMode('create');
    togglePlanAndCommission();
    refreshCurrencyDetection();
}

function fillIncomeForm(row) {
    hydratingForm = true;
    setModalMode('edit');
    $('#income_id').val(row.id);
    $('#movement_type').val(row.movement_type || '');
    $('#account_id').val(row.account_id || '');
    $('#amount').val(row.amount || '');
    $('#occurred_on').val(row.occurred_on || '');
    $('#payment_type_id').val(row.payment_type_id || '');
    $('#notes').val(row.notes || '');
    $('#description').val(row.description || '');
    $('#client_source').val(row.client_source || '');
    if (row.lead_id && row.lead_name) {
        setLeadSelection(row.lead_id, formatClientOptionLabel({name: row.lead_name, phone: row.lead_phone}));
    }
    $('#belongs_to_plan').val('0');
    $('#financing_plan_id').val('');
    var commission = row.commission || {};
    $('#commission_reference').val(row.commission_reference || commission.reference || '');
    $('#sale_date').val(row.sale_date || commission.sale_date || '');
    $('#sale_price').val(row.sale_price || commission.sale_price || '');
    $('#scheme_code').val(row.scheme_code || commission.scheme_code || defaultSchemeForType());
    $('#commission_pct').val(row.commission_pct || commission.commission_pct || '');
    var people = row.participants || commission.participants || [];
    var selectedMap = {};
    people.forEach(function(p) {
        if (p.role === 'cap') selectedMap.captador = p.user_id;
        if (p.role === 'cerrador') selectedMap.closer = p.user_id;
        if (p.role === 'coordinator') selectedMap.coordinator = p.user_id;
        if (p.role === 'atc') selectedMap.atc = p.user_id;
        if (p.role === 'marketing') selectedMap.marketing = p.user_id;
    });
    fillCommissionUsers(selectedMap);
    togglePlanAndCommission();
    hydratingForm = false;
    refreshCurrencyDetection();
}

function editIncome(id) {
    $.get(apiBase + '/' + id, function(r) {
        if (r.status !== 'success' || !r.data) {
            alert(r.message || 'No se pudo cargar el ingreso.');
            return;
        }
        resetIncomeForm();
        fillIncomeForm(r.data);
        $('#incomeModal').modal('show');
    }).fail(function(xhr) {
        alert((xhr.responseJSON && xhr.responseJSON.message) || 'Error al cargar el ingreso');
    });
}

function loadTable() {
    $.post(apiBase + '/list', { type: currentType, date_from: dateFrom, date_to: dateTo }, function(response) {
        var rows = [];
        if (response.status === 'success' && Array.isArray(response.data)) {
            response.data.forEach(function(row) {
                incomeRowsCache[row.id] = row;
                var actions = '';
                if (canDraftIncome && row.status === 'posted') {
                    actions = '<button class="btn btn-outline-primary btn-sm" onclick="editIncome(' + row.id + ')"><i class="fas fa-edit"></i></button>';
                }
                var salePrice = parseFloat(row.sale_price || 0);
                var commissionAmt = parseFloat(row.amount || 0);
                var pagoTotal = salePrice > 0
                    ? formatPrimaryAmount(salePrice, row.currency_id)
                    : formatPrimaryAmount(commissionAmt, row.currency_id);
                var comisionCol = salePrice > 0
                    ? formatPrimaryAmount(commissionAmt, row.currency_id)
                    : '—';
                rows.push([
                    row.id,
                    row.category_name || '—',
                    formatClientLabel(row),
                    pagoTotal,
                    comisionCol,
                    row.description || row.notes || '—',
                    row.occurred_on || '—',
                    '<span class="badge badge-' + (row.status === 'posted' ? 'success' : (row.status === 'pending_approval' ? 'warning' : 'secondary')) + '">' + (row.status || '—') + '</span>',
                    actions
                ]);
            });
        }
        if (dt) {
            dt.destroy();
            dt = null;
            $('#incomeTable tbody').empty();
        }
        dt = $('#incomeTable').DataTable({
            data: rows,
            pageLength: 25,
            language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
            dom: 'Bfrtip',
            buttons: ['excel', 'pdf']
        });
    });
}

function loadPending() {
    if (!canApprove) return;
    $.post(pendingUrl, function(r) {
        var rows = (r.data || []).filter(function(m) { return m.workflow_type === 'ingreso'; });
        if (rows.length === 0) { $('#pendingCard').hide(); return; }
        $('#pendingCard').show();
        var html = '';
        rows.forEach(function(m) {
            html += '<tr><td>' + m.id + '</td><td>' + (m.category_name || '—') + '</td><td>' + formatPrimaryAmount(m.amount, m.currency_id) + '</td><td>' + m.occurred_on + '</td>' +
                '<td><button class="btn btn-success btn-sm mr-1" onclick="approve(' + m.id + ')"><i class="fas fa-check"></i></button>' +
                '<button class="btn btn-danger btn-sm" onclick="reject(' + m.id + ')"><i class="fas fa-times"></i></button></td></tr>';
        });
        $('#pendingTable tbody').html(html);
    });
}

function showModal() {
    resetIncomeForm();
    applyClientContext();
    $('#incomeModal').modal('show');
}

function approve(id) {
    $.post(workflowUrl + '/' + id + '/approve', function(r) {
        if (r.status === 'success') { loadTable(); loadPending(); }
        else alert('Error: ' + (r.message || ''));
    });
}

function reject(id) {
    if (!confirm('¿Rechazar este ingreso?')) return;
    $.post(workflowUrl + '/' + id + '/reject', function(r) {
        if (r.status === 'success') { loadTable(); loadPending(); }
        else alert('Error: ' + (r.message || ''));
    });
}

$('#dateFilterForm').submit(function(e) {
    e.preventDefault();
    dateFrom = $('#date_from').val();
    dateTo = $('#date_to').val();
    loadTable();
});

$('#payment_type_id, #amount').on('change keyup', refreshCurrencyDetection);
$('#movement_type').on('change', togglePlanAndCommission);
$('#sale_price, #commission_pct').on('change keyup', refreshCommissionPreview);
$('#company_role_code').on('change', function() {
    bindCompanyRoleUserName($(this).val());
    refreshCommissionPreview();
});
$('#scheme_code').on('change', function() {
    if (isCustomScheme() && !hydratingForm) {
        $('#commission_pct').val('');
        $('#company_commission_display').val('');
    }
    toggleCustomPct();
    applySchemePct();
    refreshCommissionPreview();
});
$(document).on('change', '#lead_id', function() {
    if (hydratingForm) return;
    applyClientContext();
});
$('#incomeForm').submit(function(e) {
    e.preventDefault();
    if (!$('#lead_id').val()) {
        alert('Selecciona el cliente primero.');
        return;
    }
    var id = $('#income_id').val();
    var url = id ? (apiBase + '/' + id) : (apiBase + '/create');
    $.ajax({
        url: url,
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(r) {
            if (r.status === 'success') {
                $('#incomeModal').modal('hide');
                loadTable();
                loadPending();
            } else alert('Error: ' + (r.message || 'Operación fallida'));
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            alert('Error: ' + msg);
        }
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
            setLeadSelection(client.id, formatClientOptionLabel(client));
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

$(document).ready(function() {
    initLeadSelect();
    loadCatalog(function() { loadTable(); loadPending(); });
});
</script>
