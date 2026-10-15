<div class="container-fluid">
<style>
    #filterModal .select2-container { width: 100% !important; }
    #filterModal .select2-selection--single {
        height: calc(1.5em + .75rem + 2px);
        border: 1px solid #d1d3e2;
        border-radius: .35rem;
        padding: .375rem .75rem;
    }
    #filterModal .select2-selection__rendered {
        line-height: calc(1.5em + .75rem);
        padding-left: 0;
        color: #6e707e;
    }
    #filterModal .select2-selection__arrow { height: calc(1.5em + .75rem + 2px); }
    #filterModal .select2-dropdown { z-index: 10060 !important; }
    .filter-choice .btn {
        min-width: 7.5rem;
        border-radius: 999px;
        margin: .15rem .25rem .15rem 0;
    }
    .filter-chip {
        display: inline-flex;
        align-items: center;
        background: #eaecf4;
        color: #4e73df;
        border-radius: 999px;
        padding: .2rem .65rem;
        font-size: .8rem;
        margin: 0 .35rem .35rem 0;
    }
    .filter-chip button {
        border: 0;
        background: transparent;
        color: #4e73df;
        padding: 0 0 0 .35rem;
        line-height: 1;
    }
</style>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-stream text-primary"></i> <?= esc($title ?? 'Finanzas — Trazabilidad de cuotas') ?>
        </h1>
        <div>
            <a class="btn btn-outline-secondary btn-sm mr-2" href="<?= base_url('/app/finance/quotas') ?>">
                <i class="fas fa-file-invoice-dollar"></i> Planes y pagos
            </a>
            <?php if (! empty($can_draft)): ?>
            <a class="btn btn-primary btn-sm" href="<?= base_url('/app/finance/quotas') ?>">
                <i class="fas fa-plus"></i> Registrar pago
            </a>
            <?php endif; ?>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between align-items-center">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-receipt"></i> Movimientos de cuotas</h6>
            <button type="button" class="btn btn-primary btn-sm" data-toggle="modal" data-target="#filterModal">
                <i class="fas fa-sliders-h"></i> Filtros
                <span class="badge badge-light text-primary ml-1" id="filterCountBadge">0</span>
            </button>
        </div>
        <div class="card-body">
            <p class="small text-muted mb-2">
                Trazabilidad de pagos <strong>recibidos</strong> y <strong>entregados</strong>:
                recibos de oficina y abonos importados del Excel. No modifica el plan; solo consulta el histórico.
            </p>
            <div id="activeFilterChips" class="mb-3"></div>
            <div class="table-responsive">
                <table id="quotasTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Tipo</th>
                            <th>Nombre / Cliente</th>
                            <th>Entregada a</th>
                            <th>N° Recibo</th>
                            <th>Monto</th>
                            <th>Método de pago</th>
                            <th>Equiv. USD</th>
                            <th>Equiv. BS</th>
                            <th>Tasa</th>
                            <th>Período</th>
                            <th>Fecha pago</th>
                            <th>Fecha recep.</th>
                            <th>Correo</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="filterModal" tabindex="-1" role="dialog" aria-labelledby="filterModalTitle">
    <div class="modal-dialog modal-lg modal-dialog-centered" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="filterModalTitle">
                    <i class="fas fa-sliders-h"></i> Filtrar movimientos
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Cerrar">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <div class="form-group mb-4">
                    <label class="font-weight-bold text-gray-800 d-block mb-2">Tipo de movimiento</label>
                    <div class="filter-choice" id="filterTypeGroup">
                        <button type="button" class="btn btn-primary" data-filter-type="">Todos</button>
                        <button type="button" class="btn btn-outline-primary" data-filter-type="received">Recibidas</button>
                        <button type="button" class="btn btn-outline-primary" data-filter-type="delivered">Entregadas</button>
                    </div>
                </div>
                <div class="form-group mb-4">
                    <label class="font-weight-bold text-gray-800 d-block mb-2">Origen</label>
                    <div class="filter-choice" id="filterSourceGroup">
                        <button type="button" class="btn btn-primary" data-filter-source="">Todos</button>
                        <button type="button" class="btn btn-outline-primary" data-filter-source="registered">Oficina (recibo)</button>
                        <button type="button" class="btn btn-outline-primary" data-filter-source="excel">Histórico Excel</button>
                    </div>
                </div>
                <div class="form-group mb-0">
                    <label class="font-weight-bold text-gray-800" for="filterClient">Nombre / Cliente</label>
                    <select id="filterClient" class="form-control">
                        <option value="">Todos los clientes</option>
                    </select>
                    <small class="form-text text-muted">Escribe para buscar por cliente, proyecto o apartamento.</small>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-outline-secondary" id="btnClearFilters">
                    <i class="fas fa-eraser"></i> Limpiar
                </button>
                <button type="button" class="btn btn-primary" id="btnApplyFilters">
                    <i class="fas fa-check"></i> Aplicar filtros
                </button>
            </div>
        </div>
    </div>
</div>

<script src="<?= base_url('/js/finance-catalog-money.js') ?>"></script>
<script>
var dt, moneyHelper, allMovementRows = [];
var apiBase = '<?= base_url('/app/finance/quotas/api') ?>';
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';
var canDraft = <?= ! empty($can_draft) ? 'true' : 'false' ?>;
var draftFilters = { type: '', source: '', client: '' };
var appliedFilters = { type: '', source: '', client: '' };

function formatFinanceMoney(v) {
    return parseFloat(v || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, function(c) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
}

function formatEmailStatus(row) {
    if (row.type !== 'received') return '—';
    if (row.email_sent_at && row.email_sent_to) {
        return '<span class="text-success" title="Enviado el ' + escapeHtml(row.email_sent_at) + ' a ' + escapeHtml(row.email_sent_to) + '"><i class="fas fa-check-circle"></i> ' + escapeHtml(row.email_sent_to) + '</span>';
    }
    if (row.email_send_error) {
        return '<span class="text-danger" title="' + escapeHtml(row.email_send_error) + '"><i class="fas fa-times-circle"></i> Error</span>';
    }
    return '<span class="text-muted" title="Sin envío registrado"><i class="fas fa-minus-circle"></i> —</span>';
}

function formatPeriodLabel(row) {
    if (!row.period_month || !row.period_year) return '—';
    var months = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return (months[row.period_month] || row.period_month) + ' ' + row.period_year;
}

function paymentTypeLabel(id) {
    if (!moneyHelper) return '—';
    var paymentType = moneyHelper.getPaymentType(id);
    return paymentType ? (paymentType.name || paymentType.code || '—') : '—';
}

function showFinanceAlert(type, title, message) {
    if (typeof Swal === 'undefined') {
        alert((title ? title + ': ' : '') + message);
        return;
    }
    Swal.fire({
        icon: type,
        title: title,
        text: message,
        confirmButtonColor: type === 'success' ? '#1cc88a' : '#e74a3b'
    });
}

function showFinanceError(message, title) {
    showFinanceAlert('error', title || 'Error', message || 'Error de conexión');
}

function showFinanceSuccess(message, title) {
    showFinanceAlert('success', title || 'Listo', message);
}

function confirmFinanceAction(title, text, onConfirm) {
    if (typeof Swal === 'undefined') {
        if (confirm(text || title)) onConfirm();
        return;
    }
    Swal.fire({
        icon: 'warning',
        title: title,
        text: text,
        showCancelButton: true,
        confirmButtonColor: '#e74a3b',
        cancelButtonColor: '#858796',
        confirmButtonText: 'Sí, continuar',
        cancelButtonText: 'Cancelar'
    }).then(function(result) {
        if (result.isConfirmed) onConfirm();
    });
}

function quotaTableRow(row) {
    var isExcel = row.source === 'excel';
    var denomination = row.currency_denomination || 'USD';
    var typeLabel = row.type === 'received' ? 'Recibida' : 'Entregada';
    if (isExcel) {
        typeLabel = 'Recibida <span class="badge badge-secondary">Excel</span>';
    }
    var actions = '';
    if (row.financing_plan_id) {
        actions += '<a class="btn btn-outline-info btn-sm mr-1" href="<?= base_url('/app/finance/quotas') ?>?plan=' + encodeURIComponent(row.financing_plan_id) + '" title="Ver plan"><i class="fas fa-file-invoice-dollar"></i></a>';
    }
    if (isExcel) {
        actions += '<span class="text-muted small">Histórico</span>';
    } else {
        if (row.type === 'received') {
            actions += '<a class="btn btn-outline-primary btn-sm mr-1" href="/app/finance/quotas/receipt/' + row.id + '" target="_blank" title="Ver recibo"><i class="fas fa-file-pdf"></i></a>';
            actions += '<a class="btn btn-outline-secondary btn-sm mr-1" href="/app/finance/quotas/receipt/' + row.id + '/pdf" target="_blank" title="PDF"><i class="fas fa-download"></i></a>';
        }
        if (canDraft) {
            actions += '<button class="btn btn-danger btn-sm" onclick="removeMovement(' + row.id + ')"><i class="fas fa-trash"></i></button>';
        }
    }
    return [
        isExcel ? '—' : row.id,
        typeLabel,
        row.name || '—',
        row.type === 'delivered' ? (row.builder_name || '—') : '—',
        row.receipt_number || '—',
        moneyHelper.formatPrimaryAmount(row.amount, denomination),
        isExcel ? 'Excel' : paymentTypeLabel(row.payment_type_id),
        '$ ' + formatFinanceMoney(parseFloat(row.amount_usd || 0)),
        'Bs. ' + formatFinanceMoney(parseFloat(row.amount_bs || 0)),
        parseFloat(row.exchange_rate || 0).toFixed(4),
        formatPeriodLabel(row),
        row.payment_date || '—',
        row.receipt_date || '—',
        isExcel ? '<span class="text-muted">Histórico</span>' : formatEmailStatus(row),
        actions || '—'
    ];
}

function setChoiceGroup(groupId, attr, value) {
    $('#' + groupId + ' .btn').each(function() {
        var active = String($(this).data(attr) || '') === String(value || '');
        $(this).toggleClass('btn-primary', active).toggleClass('btn-outline-primary', !active);
    });
}

function initClientSelect() {
    var $sel = $('#filterClient');
    if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.select2('destroy');
    }
    var names = {};
    allMovementRows.forEach(function(row) {
        var name = (row.name || '').trim();
        if (name) names[name] = true;
    });
    var current = draftFilters.client || '';
    $sel.empty().append($('<option>').val('').text('Todos los clientes'));
    Object.keys(names).sort(function(a, b) { return a.localeCompare(b, 'es'); }).forEach(function(name) {
        $sel.append($('<option>').val(name).text(name));
    });
    $sel.val(current);
    $sel.select2({
        placeholder: 'Todos los clientes',
        allowClear: true,
        width: '100%',
        dropdownParent: $('#filterModal'),
        language: { noResults: function() { return 'Sin coincidencias'; } }
    });
}

function syncDraftFromApplied() {
    draftFilters = {
        type: appliedFilters.type || '',
        source: appliedFilters.source || '',
        client: appliedFilters.client || ''
    };
    setChoiceGroup('filterTypeGroup', 'filter-type', draftFilters.type);
    setChoiceGroup('filterSourceGroup', 'filter-source', draftFilters.source);
    initClientSelect();
}

function activeFilterCount() {
    var n = 0;
    if (appliedFilters.type) n += 1;
    if (appliedFilters.source) n += 1;
    if (appliedFilters.client) n += 1;
    return n;
}

function renderFilterChips() {
    var chips = [];
    if (appliedFilters.type) {
        chips.push({ key: 'type', label: appliedFilters.type === 'received' ? 'Recibidas' : 'Entregadas' });
    }
    if (appliedFilters.source) {
        chips.push({ key: 'source', label: appliedFilters.source === 'excel' ? 'Histórico Excel' : 'Oficina (recibo)' });
    }
    if (appliedFilters.client) {
        chips.push({ key: 'client', label: appliedFilters.client });
    }
    var html = '';
    if (!chips.length) {
        html = '<span class="text-muted small">Sin filtros. Pulsa <strong>Filtros</strong> para acotar por tipo, origen o cliente.</span>';
    } else {
        chips.forEach(function(chip) {
            html += '<span class="filter-chip">' + escapeHtml(chip.label) +
                ' <button type="button" title="Quitar" onclick="clearAppliedFilter(\'' + chip.key + '\')">&times;</button></span>';
        });
    }
    $('#activeFilterChips').html(html);
    $('#filterCountBadge').text(String(activeFilterCount()));
}

function filteredMovements() {
    return allMovementRows.filter(function(row) {
        if (appliedFilters.type && row.type !== appliedFilters.type) return false;
        if (appliedFilters.source === 'excel' && row.source !== 'excel') return false;
        if (appliedFilters.source === 'registered' && row.source === 'excel') return false;
        if (appliedFilters.client && String(row.name || '') !== appliedFilters.client) return false;
        return true;
    });
}

function renderMovementsTable() {
    var rows = [];
    filteredMovements().forEach(function(row) {
        try {
            rows.push(quotaTableRow(row));
        } catch (err) {
            console.error('No se pudo armar la fila de movimiento', row && row.id, err);
        }
    });
    renderFilterChips();
    if (dt) dt.clear().rows.add(rows).draw();
    else dt = $('#quotasTable').DataTable({
        data: rows,
        pageLength: 25,
        order: [[12, 'desc']],
        language: {
            emptyTable: 'No hay movimientos con esos filtros.',
            zeroRecords: 'Sin resultados',
            search: 'Buscar:',
            lengthMenu: 'Mostrar _MENU_',
            info: '_START_ a _END_ de _TOTAL_',
            infoEmpty: 'Sin registros',
            paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
        }
    });
}

function applyDraftFilters() {
    appliedFilters = {
        type: draftFilters.type || '',
        source: draftFilters.source || '',
        client: $('#filterClient').val() || ''
    };
    draftFilters.client = appliedFilters.client;
    $('#filterModal').modal('hide');
    renderMovementsTable();
}

function clearDraftFilters() {
    draftFilters = { type: '', source: '', client: '' };
    setChoiceGroup('filterTypeGroup', 'filter-type', '');
    setChoiceGroup('filterSourceGroup', 'filter-source', '');
    $('#filterClient').val('').trigger('change');
}

function clearAppliedFilter(key) {
    appliedFilters[key] = '';
    renderMovementsTable();
}

function loadMovements() {
    $.post(apiBase + '/list', { type: '' })
        .done(function(response) {
            allMovementRows = (response.status === 'success' && Array.isArray(response.data)) ? response.data : [];
            renderMovementsTable();
        })
        .fail(function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo cargar los movimientos.';
            showFinanceError(msg, 'Trazabilidad');
        });
}

function removeMovement(id) {
    confirmFinanceAction('Eliminar movimiento', '¿Eliminar este movimiento de cuota registrado en oficina?', function() {
        $.post(apiBase + '/' + id + '/delete', function(r) {
            if (r.status === 'success') {
                loadMovements();
                showFinanceSuccess('Movimiento eliminado.');
            } else showFinanceError(r.message || 'No se pudo eliminar.');
        });
    });
}

$(document).ready(function() {
    moneyHelper = new FinanceCatalogMoney({ catalogUrl: catalogUrl });
    $('#filterTypeGroup').on('click', '.btn', function() {
        draftFilters.type = String($(this).data('filter-type') || '');
        setChoiceGroup('filterTypeGroup', 'filter-type', draftFilters.type);
    });
    $('#filterSourceGroup').on('click', '.btn', function() {
        draftFilters.source = String($(this).data('filter-source') || '');
        setChoiceGroup('filterSourceGroup', 'filter-source', draftFilters.source);
    });
    $('#filterModal').on('show.bs.modal', syncDraftFromApplied);
    $('#btnApplyFilters').on('click', applyDraftFilters);
    $('#btnClearFilters').on('click', clearDraftFilters);
    moneyHelper.loadCatalog(function() {
        loadMovements();
    });
});
</script>
