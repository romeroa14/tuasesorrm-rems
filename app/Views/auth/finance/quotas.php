<div class="container-fluid">
<style>
    #planModal .select2-container,
    #quotaModal .select2-container { width: 100% !important; }
    #planModal .select2-selection--single,
    #quotaModal .select2-selection--single {
        height: calc(1.5em + .75rem + 2px);
        border: 1px solid #d1d3e2;
        border-radius: .35rem;
        padding: .375rem .75rem;
    }
    #planModal .select2-selection__rendered,
    #quotaModal .select2-selection__rendered {
        line-height: calc(1.5em + .75rem);
        padding-left: 0;
        color: #6e707e;
    }
    #planModal .select2-selection__arrow,
    #quotaModal .select2-selection__arrow { height: calc(1.5em + .75rem + 2px); }
    #planModal .select2-dropdown,
    #quotaModal .select2-dropdown {
        border-color: #d1d3e2;
        z-index: 10060 !important;
    }
    #planModal .select2-search__field,
    #quotaModal .select2-search__field {
        border: 1px solid #d1d3e2 !important;
        border-radius: .35rem;
        padding: .375rem .75rem;
    }
    .finance-client-locked {
        border: 1px solid #d1d3e2;
        border-radius: .35rem;
        background: #f8f9fc;
        padding: .5rem .75rem;
    }
</style>
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

    <ul class="nav nav-tabs mb-3" id="quotasMainTabs">
        <li class="nav-item">
            <a class="nav-link active" href="#" data-quotas-tab="manage"><i class="fas fa-tasks"></i> Gestión de planes</a>
        </li>
        <li class="nav-item">
            <a class="nav-link" href="#" data-quotas-tab="summary"><i class="fas fa-chart-bar"></i> Resumen cartera</a>
        </li>
    </ul>

    <div id="quotasTabManage">
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
                <div class="card-header py-3 bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-table"></i> Plan de pago</h6>
                    <div>
                        <button type="button" class="btn btn-outline-light btn-sm mr-1" onclick="closePlanView()" title="Volver a la lista">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                        <button type="button" class="btn btn-light btn-sm" id="btnPrintPlan" onclick="printSelectedPlan()">
                            <i class="fas fa-print"></i> Imprimir plan
                        </button>
                    </div>
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
    </div>

    <div id="quotasTabSummary" style="display:none;">
        <div class="row mb-3">
            <div class="col-md-3 col-6 mb-2">
                <div class="card border-left-primary shadow py-2"><div class="card-body py-2">
                    <div class="text-xs text-primary font-weight-bold text-uppercase">Unidades</div>
                    <div class="h5 mb-0" id="sumUnits">0</div>
                </div></div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card border-left-info shadow py-2"><div class="card-body py-2">
                    <div class="text-xs text-info font-weight-bold text-uppercase">Total financiado</div>
                    <div class="h5 mb-0" id="sumGrandScheduled">$0.00</div>
                </div></div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card border-left-success shadow py-2"><div class="card-body py-2">
                    <div class="text-xs text-success font-weight-bold text-uppercase">Pagado</div>
                    <div class="h5 mb-0 text-success" id="sumGrandPaid">$0.00</div>
                </div></div>
            </div>
            <div class="col-md-3 col-6 mb-2">
                <div class="card border-left-danger shadow py-2"><div class="card-body py-2">
                    <div class="text-xs text-danger font-weight-bold text-uppercase">Por cobrar</div>
                    <div class="h5 mb-0 text-danger" id="sumGrandPending">$0.00</div>
                </div></div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-building"></i> Por edificio / proyecto</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" id="summaryByProjectTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Proyecto</th>
                                <th class="text-center">Unidades</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Pagado</th>
                                <th class="text-right">Por cobrar</th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="5" class="text-muted text-center">Cargando...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-user"></i> Por cliente y propiedad</h6></div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-sm table-bordered mb-0" id="summaryByClientTable">
                        <thead class="thead-light">
                            <tr>
                                <th>Cliente</th>
                                <th>Proyecto</th>
                                <th>Apt/Ofic.</th>
                                <th class="text-center">Cuotas</th>
                                <th class="text-right">Total</th>
                                <th class="text-right">Pagado</th>
                                <th class="text-right">Por cobrar</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody><tr><td colspan="8" class="text-muted text-center">Cargando...</td></tr></tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4 mt-2" id="quotasMovementsCard">
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
                                <select name="lead_id" id="plan_lead_id" required>
                                    <option value=""></option>
                                </select>
                                <small class="form-text text-muted">Escribe para filtrar o abre la lista para ver clientes recientes.</small>
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
                                        <select class="form-control" id="type" name="type" required onchange="toggleQuotaTypeFields()">
                                            <option value="received">Recibida (genera ingreso)</option>
                                            <option value="delivered">Entregada a constructora</option>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-6" id="quotaClientCol">
                                    <div class="form-group mb-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="mb-0">Cliente <span class="text-danger" id="quotaClientRequired">*</span></label>
                                            <button type="button" class="btn btn-outline-primary btn-sm" id="quotaCreateClientBtn" onclick="openCreateClientModal()">
                                                <i class="fas fa-user-plus"></i> Crear cliente
                                            </button>
                                        </div>
                                        <input type="hidden" name="lead_id" id="quota_lead_id_field" value="">
                                        <div id="quota_client_picker">
                                            <select id="quota_lead_id">
                                                <option value=""></option>
                                            </select>
                                        </div>
                                        <div id="quota_client_locked" class="finance-client-locked d-none">
                                            <div class="font-weight-bold" id="quota_client_locked_name"></div>
                                            <div class="small text-muted" id="quota_client_locked_phone"></div>
                                        </div>
                                        <input type="hidden" id="name" name="name">
                                        <small class="form-text text-muted" id="quota_client_hint">Busca por nombre, teléfono o correo.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row" id="quotaBuilderRow" style="display:none;">
                                <div class="col-md-12">
                                    <div class="form-group mb-0">
                                        <div class="d-flex justify-content-between align-items-center mb-1">
                                            <label class="mb-0">Entregada a <span class="text-danger">*</span></label>
                                            <button type="button" class="btn btn-outline-primary btn-sm" onclick="openCreateBuilderModal()">
                                                <i class="fas fa-plus"></i> Crear constructora
                                            </button>
                                        </div>
                                        <select class="form-control" id="builder_id" name="builder_id">
                                            <option value="">Seleccione constructora...</option>
                                        </select>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Mes correspondiente <span class="text-danger" id="periodMonthRequired">*</span></label>
                                        <select class="form-control" id="period_month" name="period_month" required>
                                            <option value="">Seleccione...</option>
                                            <?php
                                            $monthNames = [
                                                1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
                                                5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
                                                9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
                                            ];
                                            foreach ($monthNames as $num => $label): ?>
                                                <option value="<?= $num ?>"><?= esc($label) ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Año del período <span class="text-danger" id="periodYearRequired">*</span></label>
                                        <input type="number" class="form-control" id="period_year" name="period_year" min="2000" max="2100" required value="<?= date('Y') ?>">
                                    </div>
                                </div>
                                <div class="col-md-4">
                                    <div class="form-group">
                                        <label>Fecha de pago <span class="text-danger">*</span></label>
                                        <input type="date" class="form-control" id="payment_date" name="payment_date" required value="<?= date('Y-m-d') ?>">
                                        <small class="form-text text-muted">Día en que el cliente realizó el pago.</small>
                                    </div>
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"><div class="form-group"><label>Fecha recepción <span class="text-danger">*</span></label><input type="date" class="form-control" id="receipt_date" name="receipt_date" required value="<?= date('Y-m-d') ?>"><small class="form-text text-muted">Día en que se registró en oficina.</small></div></div>
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

<div class="modal fade" id="createBuilderModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-hard-hat"></i> Nueva constructora</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="createBuilderForm">
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="create_builder_name" required placeholder="Ej: Inversiones SKY CA">
                    </div>
                    <div class="form-group mb-0">
                        <label>Proyecto</label>
                        <input type="text" class="form-control" name="project_name" id="create_builder_project" placeholder="Ej: SKY">
                    </div>
                    <input type="hidden" name="status" value="active">
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear y seleccionar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script src="<?= base_url('/js/finance-catalog-money.js') ?>"></script>
<script>
var dt, editMode = false, moneyHelper;
var lastClientSearch = '';
var apiBase = '<?= base_url('/app/finance/quotas/api') ?>';
var planApiBase = '<?= base_url('/app/finance/financing/api') ?>';
var planPrintBase = '<?= base_url('/app/finance/financing/print') ?>';
var catalogUrl = '<?= base_url('/app/finance/api/catalog') ?>';
var clientsSearchUrl = '<?= base_url('/app/finance/api/clients/search') ?>';
var clientsCreateUrl = '<?= base_url('/app/finance/api/clients/create') ?>';
var buildersApiUrl = '<?= base_url('/app/finance/api/builders') ?>';
var currentFilter = '<?= esc($current_type ?? '') ?>';
var selectedPlanId = null;
var selectedPlan = null;
var portfolioLoaded = false;
var buildersCatalog = [];
var initialQuotasTab = '<?= esc($initial_view ?? 'manage') ?>';

function closePlanView() {
    selectedPlanId = null;
    selectedPlan = null;
    $('#planHeaderCard, #planScheduleCard').hide();
    $('#planEmptyState').show();
    $('#plansList .list-group-item').removeClass('active');
}

function toggleQuotaTypeFields() {
    var isDelivered = $('#type').val() === 'delivered';
    $('#quotaBuilderRow').toggle(isDelivered);
    $('#quotaClientCol').toggle(!isDelivered || !!$('#financing_plan_id').val());
    $('#quotaClientRequired').toggleClass('d-none', isDelivered && !$('#financing_plan_id').val());
    $('#period_month, #period_year').prop('required', !isDelivered);
    $('#periodMonthRequired, #periodYearRequired').toggleClass('d-none', isDelivered);
    if (isDelivered) {
        $('#quota_client_hint').text('Opcional: referencia del cliente asociado al pago.');
    } else {
        $('#quota_client_hint').text('Busca por nombre, teléfono o correo.');
        $('#builder_id').val('');
    }
}

function setPeriodFromDueDate(dueDate) {
    if (!dueDate) return;
    var parts = String(dueDate).split('-');
    if (parts.length < 2) return;
    var year = parseInt(parts[0], 10);
    var month = parseInt(parts[1], 10);
    if (month >= 1 && month <= 12) $('#period_month').val(String(month));
    if (year >= 2000) $('#period_year').val(String(year));
}

function formatPeriodLabel(row) {
    if (!row.period_month || !row.period_year) return '—';
    var months = ['', 'Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
    return (months[row.period_month] || row.period_month) + ' ' + row.period_year;
}

function resetQuotaPeriodFields() {
    var today = new Date();
    $('#period_month').val('');
    $('#period_year').val(String(today.getFullYear()));
    $('#payment_date').val(today.toISOString().slice(0, 10));
    $('#receipt_date').val(today.toISOString().slice(0, 10));
}

function populateBuildersSelect(selectedId) {
    var html = '<option value="">Seleccione constructora...</option>';
    buildersCatalog.forEach(function(b) {
        if ((b.status || 'active') !== 'active') return;
        var sel = String(selectedId || '') === String(b.id) ? ' selected' : '';
        html += '<option value="' + b.id + '"' + sel + '>' + (b.name || 'Sin nombre') +
            (b.project_name ? ' (' + b.project_name + ')' : '') + '</option>';
    });
    $('#builder_id').html(html);
}

function loadBuildersCatalog(callback) {
    $.post(buildersApiUrl, function(res) {
        if (res.status === 'success' && Array.isArray(res.data)) {
            buildersCatalog = res.data;
            populateBuildersSelect();
        }
        if (typeof callback === 'function') callback();
    }).fail(function() {
        if (typeof callback === 'function') callback();
    });
}

function printSelectedPlan(planId) {
    var id = planId || selectedPlanId;
    if (!id) return;
    window.open(planPrintBase + '/' + id, '_blank');
}

function switchQuotasTab(tab) {
    $('#quotasMainTabs .nav-link').removeClass('active');
    $('#quotasMainTabs .nav-link[data-quotas-tab="' + tab + '"]').addClass('active');
    if (tab === 'summary') {
        $('#quotasTabManage, #quotasMovementsCard').hide();
        $('#quotasTabSummary').show();
        if (!portfolioLoaded) loadPortfolioSummary();
    } else {
        $('#quotasTabSummary').hide();
        $('#quotasTabManage, #quotasMovementsCard').show();
    }
}

function loadPortfolioSummary() {
    $('#summaryByProjectTable tbody').html('<tr><td colspan="5" class="text-muted text-center">Cargando...</td></tr>');
    $('#summaryByClientTable tbody').html('<tr><td colspan="8" class="text-muted text-center">Cargando...</td></tr>');

    $.post(planApiBase + '/summary')
        .done(function(res) {
            if (res.status !== 'success' || !res.data) {
                showFinanceError(res.message || 'No se pudo cargar el resumen.');
                return;
            }
            portfolioLoaded = true;
            renderPortfolioSummary(res.data);
        })
        .fail(function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'Error de conexión';
            showFinanceError(msg, 'Resumen cartera');
        });
}

function renderPortfolioSummary(data) {
    var totals = data.totals || {};
    $('#sumUnits').text(totals.units || 0);
    $('#sumGrandScheduled').text(moneyFmt(totals.total_scheduled || 0));
    $('#sumGrandPaid').text(moneyFmt(totals.total_paid || 0));
    $('#sumGrandPending').text(moneyFmt(totals.total_pending || 0));

    var projectRows = '';
    (data.by_project || []).forEach(function(row) {
        projectRows += '<tr>' +
            '<td><strong>' + (row.project_name || '—') + '</strong></td>' +
            '<td class="text-center">' + (row.units || 0) + '</td>' +
            '<td class="text-right">' + moneyFmt(row.total_scheduled) + '</td>' +
            '<td class="text-right text-success">' + moneyFmt(row.total_paid) + '</td>' +
            '<td class="text-right text-danger">' + moneyFmt(row.total_pending) + '</td>' +
            '</tr>';
    });
    if (!projectRows) projectRows = '<tr><td colspan="5" class="text-muted text-center">Sin planes registrados.</td></tr>';
    $('#summaryByProjectTable tbody').html(projectRows);

    var clientRows = '';
    (data.by_plan || []).forEach(function(row) {
        var paidLabel = (row.installments_paid || 0) + '/' + (row.installment_count || 0);
        clientRows += '<tr>' +
            '<td>' + (row.client_name || '—') + '</td>' +
            '<td>' + (row.project_name || '—') + '</td>' +
            '<td>' + (row.unit_ref || '—') + '</td>' +
            '<td class="text-center">' + paidLabel + '</td>' +
            '<td class="text-right">' + moneyFmt(row.total_scheduled) + '</td>' +
            '<td class="text-right text-success">' + moneyFmt(row.total_paid) + '</td>' +
            '<td class="text-right text-danger">' + moneyFmt(row.total_pending) + '</td>' +
            '<td><button type="button" class="btn btn-outline-secondary btn-sm" onclick="printSelectedPlan(' + row.plan_id + ')" title="Imprimir"><i class="fas fa-print"></i></button> ' +
            '<button type="button" class="btn btn-outline-primary btn-sm" onclick="openPlanFromSummary(' + row.plan_id + ')" title="Ver plan"><i class="fas fa-eye"></i></button></td>' +
            '</tr>';
    });
    if (!clientRows) clientRows = '<tr><td colspan="8" class="text-muted text-center">Sin planes registrados.</td></tr>';
    $('#summaryByClientTable tbody').html(clientRows);
}

function openPlanFromSummary(planId) {
    switchQuotasTab('manage');
    selectPlan(planId);
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
    showFinanceAlert('error', title || 'Error', parseFinanceErrorMessage(message));
}

function showFinanceSuccess(message, title) {
    showFinanceAlert('success', title || 'Listo', message);
}

function parseFinanceErrorMessage(raw) {
    if (!raw) return 'Error de conexión';
    if (raw.indexOf('Duplicate entry') !== -1 && raw.indexOf('receipt_number') !== -1) {
        return 'El número de recibo ya está registrado. Usa uno diferente.';
    }
    return raw;
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

function moneyFmt(v) {
    return '$' + parseFloat(v || 0).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function formatClientOptionLabel(client) {
    var label = client.name || 'Sin nombre';
    if (client.phone) label += ' — ' + client.phone;
    return label;
}

function setPlanLeadSelection(id, text) {
    var $el = $('#plan_lead_id');
    if (!$el.length) return;
    $el.find('option').not(':first').remove();
    $el.append(new Option(text, id, true, true)).trigger('change');
}

function setQuotaLeadSelection(id, text) {
    $('#quota_lead_id_field').val(id);
    var $el = $('#quota_lead_id');
    if (!$el.length) return;
    $el.find('option').not(':first').remove();
    $el.append(new Option(text, id, true, true)).trigger('change');
    syncQuotaClientName();
}

function syncQuotaClientName() {
    var text = $('#quota_lead_id option:selected').text() || $('#quota_client_locked_name').text() || '';
    var name = text.split(' — ')[0].trim();
    $('#name').val(name);
    if ($('#quota_client_picker').is(':visible')) {
        $('#quota_lead_id_field').val($('#quota_lead_id').val() || '');
    }
}

function resetQuotaLeadSelect() {
    $('#quota_lead_id_field').val('');
    if ($('#quota_lead_id').hasClass('select2-hidden-accessible')) {
        $('#quota_lead_id').val(null).trigger('change');
    } else {
        $('#quota_lead_id').val('');
    }
    $('#name').val('');
}

function showQuotaClientPicker() {
    $('#quota_client_locked').addClass('d-none');
    $('#quota_client_picker').removeClass('d-none');
    $('#quotaCreateClientBtn').removeClass('d-none');
    $('#quota_client_hint').text('Busca por nombre, teléfono o correo.');
}

function lockQuotaClient(id, name, phone) {
    $('#quota_lead_id_field').val(id);
    $('#quota_client_locked_name').text(name || 'Sin nombre');
    $('#quota_client_locked_phone').text(phone || '');
    $('#quota_client_picker').addClass('d-none');
    $('#quota_client_locked').removeClass('d-none');
    $('#quotaCreateClientBtn').addClass('d-none');
    $('#quota_client_hint').text('Cliente tomado del plan de pago.');
    syncQuotaClientName();
}

function leadSelectConfig(dropdownParent) {
    return {
        dropdownParent: dropdownParent,
        placeholder: 'Seleccionar cliente...',
        allowClear: true,
        width: '100%',
        minimumInputLength: 0,
        language: {
            inputTooShort: function() { return 'Escribe para filtrar la lista'; },
            noResults: function() { return 'Sin resultados — usa "Crear cliente"'; },
            searching: function() { return 'Buscando...'; }
        },
        ajax: {
            url: clientsSearchUrl,
            dataType: 'json',
            delay: 250,
            data: function(params) {
                lastClientSearch = params.term || '';
                return { q: params.term || '' };
            },
            processResults: function(response) {
                var items = (response.data || []).map(function(c) {
                    return { id: c.id, text: formatClientOptionLabel(c) };
                });
                return { results: items };
            },
            cache: true
        }
    };
}

function initPlanLeadSelect() {
    if (!$('#plan_lead_id').length || typeof $.fn.select2 !== 'function') return;
    if ($('#plan_lead_id').hasClass('select2-hidden-accessible')) return;
    $('#plan_lead_id').select2(leadSelectConfig($('#planModal')));
}

function initQuotaLeadSelect() {
    if (!$('#quota_lead_id').length || typeof $.fn.select2 !== 'function') return;
    if ($('#quota_lead_id').hasClass('select2-hidden-accessible')) {
        return;
    }
    $('#quota_lead_id').select2(leadSelectConfig($('#quotaModal'))).on('change', syncQuotaClientName);
}

function resetPlanLeadSelect() {
    if ($('#plan_lead_id').hasClass('select2-hidden-accessible')) {
        $('#plan_lead_id').val(null).trigger('change');
    }
}

function openCreateBuilderModal() {
    $('#createBuilderForm')[0].reset();
    if (selectedPlan && selectedPlan.project_name) {
        $('#create_builder_project').val(selectedPlan.project_name);
    }
    $('#createBuilderModal').modal('show');
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
    $('#plansList').html('<div class="list-group-item text-muted small">Cargando...</div>');
    $.post(planApiBase + '/list')
        .done(function(res) {
            var html = '';
            var plans = (res && res.status === 'success' && Array.isArray(res.data)) ? res.data : [];
            if (!plans.length) {
                html = '<div class="list-group-item text-muted">No hay planes. Crea uno con "Nuevo plan de pago".</div>';
            } else {
                plans.forEach(function(p) {
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
        })
        .fail(function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo cargar los planes.';
            $('#plansList').html('<div class="list-group-item text-danger small">' + msg + '</div>');
            showFinanceError(msg, 'Planes de pago');
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
    if (selectedPlan.lead_id) {
        lockQuotaClient(selectedPlan.lead_id, selectedPlan.client_name, selectedPlan.lead_phone);
    } else {
        showQuotaClientPicker();
        resetQuotaLeadSelect();
        $('#name').val(selectedPlan.client_name || '');
    }
    $('#amount').val(row.pending_amount || row.amount);
    setPeriodFromDueDate(row.due_date);
    $('#payment_date').val(new Date().toISOString().slice(0, 10));
    $('#receipt_date').val(new Date().toISOString().slice(0, 10));
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
    $('#planModal').one('shown.bs.modal', function() {
        initPlanLeadSelect();
    });
    $('#planModal').modal('show');
}

$('#planForm').submit(function(e) {
    e.preventDefault();
    if (!$('#plan_lead_id').val()) {
        showFinanceError('Selecciona un cliente del CRM.');
        return;
    }
    $.post(planApiBase + '/create', $(this).serialize(), function(res) {
        if (res.status === 'success') {
            $('#planModal').modal('hide');
            selectPlan(res.data.id);
            loadPlans();
            showFinanceSuccess('Plan de pago generado correctamente.');
        } else showFinanceError(res.message || 'No se pudo crear el plan.');
    }).fail(function(xhr) {
        showFinanceError((xhr.responseJSON && xhr.responseJSON.message) || 'Error de conexión');
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
                showFinanceError(r.message || 'No se pudo crear el cliente');
                return;
            }
            var client = r.data;
            if ($('#quotaModal').hasClass('show')) {
                setQuotaLeadSelection(client.id, formatClientOptionLabel(client));
            } else {
                setPlanLeadSelection(client.id, formatClientOptionLabel(client));
            }
            $('#createClientModal').modal('hide');
            if (client.existing) {
                showFinanceSuccess(client.message || 'Cliente existente seleccionado.', 'Cliente');
            }
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            showFinanceError(msg);
        }
    });
});

$('#createBuilderForm').submit(function(e) {
    e.preventDefault();
    $.ajax({
        url: buildersApiUrl + '/create',
        method: 'POST',
        data: $(this).serialize(),
        dataType: 'json',
        success: function(r) {
            if (r.status !== 'success' || !r.data) {
                showFinanceError(r.message || 'No se pudo crear la constructora');
                return;
            }
            var builder = r.data;
            buildersCatalog.push(builder);
            populateBuildersSelect(builder.id);
            $('#builder_id').val(String(builder.id));
            $('#createBuilderModal').modal('hide');
            showFinanceSuccess('Constructora registrada.', 'Listo');
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            showFinanceError(msg);
        }
    });
});

function loadTable() {
    $.post(apiBase + '/list', { type: currentFilter })
        .done(function(response) {
            var rows = [];
            if (response.status === 'success' && Array.isArray(response.data)) {
                response.data.forEach(function(row) {
                    var denomination = row.currency_denomination || 'USD';
                    rows.push([
                        row.id,
                        row.type === 'received' ? 'Recibida' : 'Entregada',
                        row.name || '—',
                        row.type === 'delivered' ? (row.builder_name || '—') : '—',
                        row.receipt_number || '—',
                        moneyHelper.formatPrimaryAmount(row.amount, denomination),
                        paymentTypeLabel(row.payment_type_id),
                        '$ ' + formatFinanceMoney(parseFloat(row.amount_usd || 0)),
                        'Bs. ' + formatFinanceMoney(parseFloat(row.amount_bs || 0)),
                        parseFloat(row.exchange_rate || 0).toFixed(4),
                        formatPeriodLabel(row),
                        row.payment_date || '—',
                        row.receipt_date || '—',
                        '<button class="btn btn-danger btn-sm" onclick="remove(' + row.id + ')"><i class="fas fa-trash"></i></button>'
                    ]);
                });
            }
            if (dt) dt.clear().rows.add(rows).draw();
            else dt = $('#quotasTable').DataTable({
                data: rows,
                pageLength: 10,
                language: {
                    url: 'https://cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
                }
            });
        })
        .fail(function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo cargar los movimientos.';
            showFinanceError(msg, 'Movimientos');
        });
}

function showModal(mode, id) {
    editMode = mode === 'edit';
    $('#record_id, #financing_plan_id, #installment_id').val('');
    $('#installmentHint').hide();
    $('#quotaForm')[0].reset();
    resetQuotaPeriodFields();
    showQuotaClientPicker();
    resetQuotaLeadSelect();
    $('#type').val('received');
    toggleQuotaTypeFields();
    $('#quotaModal').one('shown.bs.modal', function() {
        initQuotaLeadSelect();
    });
    if (mode !== 'edit' || !id) {
        moneyHelper.populatePaymentTypes();
        moneyHelper.refresh();
    }
    $('#quotaModal').modal('show');
}

function remove(id) {
    confirmFinanceAction('Eliminar cuota', '¿Eliminar este movimiento de cuota?', function() {
        $.post(apiBase + '/' + id + '/delete', function(r) {
            if (r.status === 'success') {
                loadTable();
                if (selectedPlanId) selectPlan(selectedPlanId);
                showFinanceSuccess('Cuota eliminada.');
            } else showFinanceError(r.message || 'No se pudo eliminar.');
        });
    });
}

$('#quotaForm').submit(function(e) {
    e.preventDefault();
    syncQuotaClientName();
    var isDelivered = $('#type').val() === 'delivered';
    if (isDelivered) {
        if (!$('#builder_id').val()) {
            showFinanceError('Selecciona la constructora a la que se entrega el pago.');
            return;
        }
    } else if (!$('#quota_lead_id_field').val()) {
        showFinanceError('Selecciona un cliente del CRM.');
        return;
    }
    if (!$('#payment_type_id').val()) {
        showFinanceError('Selecciona un método de pago.');
        return;
    }
    if (!isDelivered && !$('#period_month').val()) {
        showFinanceError('Selecciona el mes correspondiente al pago.');
        return;
    }
    syncQuotaClientName();
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
                portfolioLoaded = false;
                if (selectedPlanId) selectPlan(selectedPlanId);
                showFinanceSuccess('Pago registrado y validado correctamente.');
            } else showFinanceError(res.message || 'No se pudo guardar el pago.');
        },
        error: function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            showFinanceError(msg);
        }
    });
});

$(document).ready(function() {
    moneyHelper = new FinanceCatalogMoney({ catalogUrl: catalogUrl, amountLabel: '#amount_label' });
    moneyHelper.bind();
    loadBuildersCatalog();
    loadPlans();
    moneyHelper.loadCatalog(function() {
        loadTable();
    });

    $('#quotasMainTabs .nav-link').on('click', function(e) {
        e.preventDefault();
        switchQuotasTab($(this).data('quotas-tab'));
    });

    if (initialQuotasTab === 'summary') {
        switchQuotasTab('summary');
    }
});
</script>
