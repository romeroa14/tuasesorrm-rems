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
    #planFilterBar .select2-container { width: 100% !important; }
</style>
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-hand-holding-usd text-primary"></i> <?= esc($title ?? 'Finanzas — Cuotas') ?>
        </h1>
        <div>
            <a class="btn btn-outline-secondary btn-sm mr-2" href="<?= base_url('/app/finance/quota-movements') ?>">
                <i class="fas fa-stream"></i> Trazabilidad
            </a>
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
    <div class="card shadow mb-3" id="planFilterBar">
        <div class="card-body py-3">
            <div class="form-row align-items-end">
                <div class="form-group col-md-4 mb-2 mb-md-0">
                    <label class="small text-muted mb-1">Proyecto</label>
                    <select id="planFilterProject" class="form-control">
                        <option value="">Todos los proyectos</option>
                    </select>
                </div>
                <div class="form-group col-md-8 mb-0">
                    <label class="small text-muted mb-1">Cliente</label>
                    <select id="planFilterClient" class="form-control">
                        <option value="">Selecciona un cliente...</option>
                    </select>
                </div>
            </div>
        </div>
    </div>
    <div class="row">
        <div class="col-12 mb-4">
            <div class="card shadow mb-3" id="planHeaderCard" style="display:none;">
                <div class="card-header py-3 bg-dark text-white d-flex justify-content-between align-items-center">
                    <h6 class="m-0 font-weight-bold"><i class="fas fa-table"></i> Plan de pago</h6>
                    <div>
                        <button type="button" class="btn btn-outline-light btn-sm mr-1" onclick="closePlanView()" title="Cerrar plan">
                            <i class="fas fa-times"></i> Cerrar
                        </button>
                        <button type="button" class="btn btn-warning btn-sm mr-1" id="btnEditPlan" onclick="openPlanEditor()" style="display:none;">
                            <i class="fas fa-edit"></i> Editar plan
                        </button>
                        <button type="button" class="btn btn-light btn-sm mr-1" id="btnPrintPlan" onclick="printSelectedPlan()">
                            <i class="fas fa-print"></i> Imprimir plan
                        </button>
                        <button type="button" class="btn btn-outline-light btn-sm" id="btnSendStatement" onclick="sendAccountStatement()">
                            <i class="fas fa-envelope"></i> Enviar estado de cuenta
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
                    <p class="mb-0">Elige un proyecto y un cliente en los filtros de arriba para ver el plan y las cuotas.</p>
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
                <input type="hidden" name="plan_id" id="plan_edit_id" value="">
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
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Fecha de venta</label><input type="date" class="form-control" name="sale_date" id="plan_sale_date"></div></div>
                        <div class="col-md-8"><div class="form-group"><label>Origen del cliente</label><select class="form-control" name="client_source" id="plan_client_source">
                            <option value="">Sin especificar</option>
                            <option value="ads_meta">Publicidad (Meta / ADS)</option>
                            <option value="referral">Referido</option>
                            <option value="agent_portfolio">Cartera agente (ej. Roxana)</option>
                            <option value="organic">Orgánico / walk-in</option>
                            <option value="repeat_client">Cliente recurrente</option>
                            <option value="other">Otro</option>
                        </select></div></div>
                    </div>
                    <div class="form-group"><label>Notas comisión / trazabilidad</label><textarea class="form-control" name="commission_notes" id="plan_commission_notes" rows="2" placeholder="Ej. comisión bruta, referidor, reparto..."></textarea></div>
                    <div class="form-group mb-0"><label>Notas del plan</label><textarea class="form-control" name="notes" rows="2"></textarea></div>
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
                            <div class="row" id="quotaNotifyRow">
                                <div class="col-md-6">
                                    <div class="alert alert-info py-2 mb-2">
                                        <i class="fas fa-envelope"></i>
                                        Al guardar, el recibo se envía <strong>automáticamente</strong> al correo del cliente en el CRM (si está configurado SMTP).
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <div class="custom-control custom-checkbox mb-2">
                                        <input type="checkbox" class="custom-control-input" id="send_receipt_whatsapp" name="send_receipt_whatsapp" value="1">
                                        <label class="custom-control-label" for="send_receipt_whatsapp">Aviso WhatsApp (Hilos, opcional)</label>
                                    </div>
                                </div>
                            </div>
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
var allPlansCache = [];
var portfolioLoaded = false;
var buildersCatalog = [];
var initialQuotasTab = '<?= esc($initial_view ?? 'manage') ?>';
var canDraftFinance = <?= ! empty($can_draft) ? 'true' : 'false' ?>;

function closePlanView() {
    selectedPlanId = null;
    selectedPlan = null;
    $('#planHeaderCard, #planScheduleCard').hide();
    $('#planEmptyState').show();
    if ($('#planFilterClient').val()) {
        $('#planFilterClient').val(null).trigger('change.select2');
    }
    loadTable();
}

function toggleQuotaTypeFields() {
    var isDelivered = $('#type').val() === 'delivered';
    $('#quotaBuilderRow').toggle(isDelivered);
    $('#quotaClientCol').toggle(!isDelivered || !!$('#financing_plan_id').val());
    $('#quotaClientRequired').toggleClass('d-none', isDelivered && !$('#financing_plan_id').val());
    $('#period_month, #period_year').prop('required', !isDelivered);
    $('#periodMonthRequired, #periodYearRequired').toggleClass('d-none', isDelivered);
    $('#quotaNotifyRow').toggle(!isDelivered);
    if (isDelivered) {
        $('#quota_client_hint').text('Opcional: referencia del cliente asociado al pago.');
    } else {
        $('#quota_client_hint').text('Busca por nombre, teléfono o correo.');
        $('#builder_id').val('');
    }
}

function setPeriodFromDueDate(dueDate) {
    if (!dueDate) return;
    var str = String(dueDate);
    var parts = str.split('-');
    if (parts.length >= 2 && /^\d{4}$/.test(parts[0])) {
        var year = parseInt(parts[0], 10);
        var month = parseInt(parts[1], 10);
        if (month >= 1 && month <= 12) $('#period_month').val(String(month));
        if (year >= 2000) $('#period_year').val(String(year));
        return;
    }
    var monthMap = { ene: 1, feb: 2, mar: 3, abr: 4, may: 5, jun: 6, jul: 7, ago: 8, sep: 9, oct: 10, nov: 11, dic: 12 };
    var match = str.toLowerCase().match(/(ene|feb|mar|abr|may|jun|jul|ago|sep|oct|nov|dic)[a-z]*[\s/-]+(\d{4})/);
    if (match) {
        $('#period_month').val(String(monthMap[match[1].substring(0, 3)] || ''));
        $('#period_year').val(match[2]);
    }
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

function sendAccountStatement() {
    if (!selectedPlanId) {
        showFinanceError('Selecciona un plan de pago.');
        return;
    }
    var email = selectedPlan && selectedPlan.lead_email ? selectedPlan.lead_email : '';
    var promptMsg = email
        ? 'Se enviará el estado de cuenta en PDF a: ' + email
        : 'El cliente no tiene correo en CRM. Actualiza el lead antes de enviar.';
    if (!email) {
        showFinanceError(promptMsg);
        return;
    }
    confirmFinanceAction('Enviar estado de cuenta', promptMsg, function() {
        $.post(planApiBase + '/' + selectedPlanId + '/send-statement', { send_email: 1 }, function(res) {
            if (res.status === 'success') {
                showFinanceSuccess(res.data.message || 'Estado de cuenta enviado.', 'Correo');
            } else {
                showFinanceError(res.message || 'No se pudo enviar.');
            }
        }).fail(function(xhr) {
            var msg = xhr.responseJSON && xhr.responseJSON.message ? xhr.responseJSON.message : 'Error de conexión';
            showFinanceError(msg);
        });
    });
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
    if (typeof Swal !== 'undefined' && message.indexOf('<a ') !== -1) {
        Swal.fire({
            icon: 'success',
            title: title || 'Listo',
            html: message,
            confirmButtonColor: '#1cc88a'
        });
        return;
    }
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

function planProjectLabel(p) {
    var name = (p && p.project_name) ? String(p.project_name).trim() : '';
    return name || 'Sin proyecto';
}

function fillProjectFilter() {
    var current = $('#planFilterProject').val() || '';
    var names = {};
    allPlansCache.forEach(function(p) {
        names[planProjectLabel(p)] = true;
    });
    var $sel = $('#planFilterProject');
    $sel.empty().append($('<option>').val('').text('Todos los proyectos'));
    Object.keys(names).sort(function(a, b) { return a.localeCompare(b, 'es'); }).forEach(function(name) {
        $sel.append($('<option>').val(name).text(name));
    });
    if (current && names[current]) {
        $sel.val(current);
    }
}

function planOptionLabel(p) {
    var unit = p.unit_ref || p.property_ref || '—';
    return (p.client_name || 'Sin cliente') + ' · Apt ' + unit;
}

function filteredPlans() {
    var proj = $('#planFilterProject').val() || '';
    return allPlansCache.filter(function(p) {
        return !proj || planProjectLabel(p) === proj;
    }).sort(function(a, b) {
        return planOptionLabel(a).localeCompare(planOptionLabel(b), 'es');
    });
}

function fillClientFilter() {
    var plans = filteredPlans();
    var current = selectedPlanId ? String(selectedPlanId) : ($('#planFilterClient').val() || '');
    var $sel = $('#planFilterClient');
    if ($sel.hasClass('select2-hidden-accessible')) {
        $sel.select2('destroy');
    }
    $sel.empty().append($('<option>').val('').text('Selecciona un cliente...'));
    plans.forEach(function(p) {
        $sel.append($('<option>').val(String(p.id)).text(planOptionLabel(p)));
    });
    if (current && $sel.find('option[value="' + current + '"]').length) {
        $sel.val(current);
    } else {
        $sel.val('');
    }
    $sel.select2({
        placeholder: 'Selecciona un cliente...',
        allowClear: true,
        width: '100%',
        language: { noResults: function() { return 'Sin coincidencias'; } }
    });
}

function renderPlansList() {
    fillClientFilter();
}

function loadPlans() {
    $.post(planApiBase + '/list')
        .done(function(res) {
            allPlansCache = (res && res.status === 'success' && Array.isArray(res.data)) ? res.data : [];
            fillProjectFilter();
            renderPlansList();
            var planFromUrl = new URLSearchParams(window.location.search).get('plan');
            if (planFromUrl) selectPlan(planFromUrl);
        })
        .fail(function(xhr) {
            var msg = (xhr.responseJSON && xhr.responseJSON.message) ? xhr.responseJSON.message : 'No se pudo cargar los planes.';
            showFinanceError(msg, 'Planes de pago');
        });
}

function selectPlan(id) {
    selectedPlanId = id;
    if ($('#planFilterClient').val() !== String(id)) {
        $('#planFilterClient').val(String(id)).trigger('change.select2');
    }
    $.get(planApiBase + '/' + id, function(res) {
        if (res.status !== 'success') return;
        selectedPlan = res.data;
        renderPlan(selectedPlan);
        loadTable();
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
        ['Fecha venta', plan.sale_date || '—'],
        ['Origen', plan.client_source_label || plan.client_source || '—'],
        ['Inicio fin.', plan.start_date || '—'],
        ['Final', plan.end_date || '—']
    ];

    var headerHtml = '';
    fields.forEach(function(f) {
        headerHtml += '<div class="col-md-4 col-6 mb-2"><div class="text-xs text-uppercase text-muted">' + f[0] + '</div><div class="font-weight-bold">' + f[1] + '</div></div>';
    });
    if (canDraftFinance) {
        $('#btnEditPlan').show();
    } else {
        $('#btnEditPlan').hide();
    }
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
            '<td>' + (canPay ? '<button class="btn btn-success btn-sm mr-1" onclick="payInstallment(' + row.id + ')"><i class="fas fa-dollar-sign"></i></button>' : '') +
            (canDraftFinance && row.status !== 'paid' ? '<button class="btn btn-outline-secondary btn-sm" onclick="editInstallmentDueDate(' + row.id + ', \'' + (row.due_date || '') + '\')" title="Cambiar fecha"><i class="fas fa-calendar-alt"></i></button>' : (canPay ? '' : '—')) + '</td>' +
            '</tr>';
    });
    $('#scheduleTable tbody').html(rows);
}

function payInstallment(installmentId) {
    if (!selectedPlan) return;
    var row = (selectedPlan.installment_schedule || []).find(function(r) { return String(r.id) === String(installmentId); });
    if (!row) return;

    editMode = false;
    $('#record_id').val('');
    $('#quotaForm')[0].reset();
    resetQuotaPeriodFields();
    showQuotaClientPicker();
    resetQuotaLeadSelect();
    $('#type').val('received');
    toggleQuotaTypeFields();
    $('#financing_plan_id').val(selectedPlan.id);
    $('#installment_id').val(installmentId);
    if (selectedPlan.lead_id) {
        lockQuotaClient(selectedPlan.lead_id, selectedPlan.client_name, selectedPlan.lead_phone);
    } else {
        $('#name').val(selectedPlan.client_name || '');
    }
    $('#amount').val(row.pending_amount || row.amount);
    setPeriodFromDueDate(row.due_date || row.month_label);
    $('#payment_date').val(new Date().toISOString().slice(0, 10));
    $('#receipt_date').val(new Date().toISOString().slice(0, 10));
    $('#installmentHint').show().html(
        'Cuota <strong>#' + row.installment_number + '</strong> — ' + (row.month_label || row.due_date) +
        ' — Programada: ' + moneyFmt(row.amount) + ' — Pendiente: <strong>' + moneyFmt(row.pending_amount) + '</strong>'
    );
    $('#quotaModal').one('shown.bs.modal', function() {
        initQuotaLeadSelect();
        setPeriodFromDueDate(row.due_date || row.month_label);
    });
    moneyHelper.populatePaymentTypes();
    moneyHelper.refresh();
    $('#quotaModal').modal('show');
}

function paymentTypeLabel(id) {
    var paymentType = moneyHelper.getPaymentType(id);
    return paymentType ? (paymentType.name || paymentType.code || '—') : '—';
}


function setPlanModalMode(mode) {
    var isEdit = mode === 'edit';
    $('#plan_edit_id').val(isEdit && selectedPlan ? selectedPlan.id : '');
    $('#planModal .modal-title').html(isEdit
        ? '<i class="fas fa-edit"></i> Editar plan de pago'
        : '<i class="fas fa-file-invoice-dollar"></i> Nuevo plan de pago');
    $('#planForm button[type="submit"]').html(isEdit
        ? '<i class="fas fa-save"></i> Guardar cambios'
        : '<i class="fas fa-save"></i> Generar tabla');
    $('#plan_installments, #plan_total_price').prop('readonly', isEdit);
}

function openPlanEditor() {
    if (!selectedPlan) return;
    $('#planForm')[0].reset();
    resetPlanLeadSelect();
    setPlanModalMode('edit');
    var p = selectedPlan;
    if (p.lead_id) setPlanLeadSelection(p.lead_id, p.client_name || '');
    $('#planForm [name="project_name"]').val(p.project_name || '');
    $('#planForm [name="unit_ref"]').val(p.unit_ref || p.property_ref || '');
    $('#planForm [name="square_meters"]').val(p.square_meters || '');
    $('#planForm [name="reservation_amount"]').val(p.reservation_amount || '');
    $('#planForm [name="total_price"]').val(p.total_price || '');
    $('#planForm [name="down_payment"]').val(p.down_payment || '');
    $('#planForm [name="financing_amount"]').val(p.financing_amount || '');
    $('#plan_installments').val(p.installments || p.installment_count || '');
    $('#planForm [name="start_date"]').val(p.start_date || '');
    $('#plan_sale_date').val(p.sale_date || '');
    $('#plan_client_source').val(p.client_source || '');
    $('#plan_commission_notes').val(p.commission_notes || '');
    $('#planForm [name="notes"]').val(p.notes || '');
    recalcFinancing();
    $('#planModal').one('shown.bs.modal', function() { initPlanLeadSelect(); });
    $('#planModal').modal('show');
}

function editInstallmentDueDate(installmentId, currentDue) {
    var next = window.prompt('Nueva fecha de vencimiento (AAAA-MM-DD):', currentDue || '');
    if (!next) return;
    $.post(planApiBase + '/installment/' + installmentId, { due_date: next }, function(res) {
        if (res.status === 'success') {
            selectedPlan = res.data;
            renderPlan(selectedPlan);
            showFinanceSuccess('Fecha de cuota actualizada.');
        } else showFinanceError(res.message || 'No se pudo actualizar.');
    }).fail(function(xhr) {
        showFinanceError((xhr.responseJSON && xhr.responseJSON.message) || 'Error de conexión');
    });
}

function showPlanModal() {
    setPlanModalMode('create');
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
    var editId = $('#plan_edit_id').val();
    var url = editId ? (planApiBase + '/' + editId) : (planApiBase + '/create');
    $.post(url, $(this).serialize(), function(res) {
        if (res.status === 'success') {
            $('#planModal').modal('hide');
            selectPlan(res.data.id);
            loadPlans();
            showFinanceSuccess(editId ? 'Plan actualizado.' : 'Plan de pago generado correctamente.');
        } else showFinanceError(res.message || 'No se pudo guardar el plan.');
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

function quotaTableRow(row) {
    var isExcel = row.source === 'excel';
    var denomination = row.currency_denomination || 'USD';
    var typeLabel = row.type === 'received' ? 'Recibida' : 'Entregada';
    if (isExcel) {
        typeLabel = 'Recibida <span class="badge badge-secondary">Excel</span>';
    }
    var actions = '<span class="text-muted small">Histórico</span>';
    if (!isExcel) {
        actions = (row.type === 'received'
            ? '<a class="btn btn-outline-primary btn-sm mr-1" href="/app/finance/quotas/receipt/' + row.id + '" target="_blank" title="Ver recibo"><i class="fas fa-file-pdf"></i></a>'
            + '<a class="btn btn-outline-secondary btn-sm mr-1" href="/app/finance/quotas/receipt/' + row.id + '/pdf" target="_blank" title="PDF"><i class="fas fa-download"></i></a>'
            : '') +
        '<button class="btn btn-danger btn-sm" onclick="remove(' + row.id + ')"><i class="fas fa-trash"></i></button>';
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
        actions
    ];
}

function loadTable() {
    if (!$('#quotasTable').length) return;
    $.post(apiBase + '/list', { type: currentFilter })
        .done(function(response) {
            var rows = [];
            if (response.status === 'success' && Array.isArray(response.data)) {
                response.data.forEach(function(row) {
                    try {
                        rows.push(quotaTableRow(row));
                    } catch (err) {
                        console.error('No se pudo armar la fila de movimiento', row && row.id, err);
                    }
                });
            }
            if ($('#quotasMovementsHint').length) {
                $('#quotasMovementsHint').html(
                    'Todos los pagos <strong>recibidos</strong> y <strong>entregados</strong>: los registrados con Registrar pago y los abonos importados del Excel.'
                );
            }
            if (dt) dt.clear().rows.add(rows).draw();
            else dt = $('#quotasTable').DataTable({
                data: rows,
                pageLength: 10,
                language: {
                    emptyTable: 'No hay movimientos de cuotas.',
                    zeroRecords: 'Sin resultados',
                    search: 'Buscar:',
                    lengthMenu: 'Mostrar _MENU_',
                    info: '_START_ a _END_ de _TOTAL_',
                    infoEmpty: 'Sin registros',
                    paginate: { first: 'Primero', last: 'Último', next: 'Siguiente', previous: 'Anterior' }
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
                var msg = 'Pago registrado y validado correctamente.';
                if (res.data && res.data.receipt_url && $('#type').val() === 'received') {
                    msg += ' <a href="' + res.data.receipt_url + '" target="_blank">Ver recibo</a>';
                    if (res.data.receipt_pdf_url) {
                        msg += ' · <a href="' + res.data.receipt_pdf_url + '" target="_blank">PDF</a>';
                    }
                }
                if (res.data && res.data.notifications) {
                    var n = res.data.notifications;
                    if (n.email && n.email.sent) {
                        msg += '<br><strong class="text-success"><i class="fas fa-envelope"></i> Recibo enviado a ' + escapeHtml(n.email.to || 'correo del cliente') + '</strong>';
                    } else if (n.email && n.email.skipped && n.email.reason) {
                        msg += '<br><span class="text-warning"><i class="fas fa-envelope-open"></i> ' + escapeHtml(n.email.reason) + '</span>';
                    } else if (n.email && n.email.error) {
                        msg += '<br><strong class="text-danger"><i class="fas fa-exclamation-triangle"></i> Correo no enviado:</strong> ' + escapeHtml(n.email.error).substring(0, 180);
                    }
                    if (n.whatsapp && n.whatsapp.sent) {
                        msg += '<br><small>WhatsApp enviado.</small>';
                    } else if (n.whatsapp && n.whatsapp.reason) {
                        msg += '<br><small>WhatsApp: ' + escapeHtml(n.whatsapp.reason) + '</small>';
                    }
                }
                showFinanceSuccess(msg, 'Listo');
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
    $('#planFilterProject').on('change', function() {
        selectedPlanId = null;
        selectedPlan = null;
        $('#planHeaderCard, #planScheduleCard').hide();
        $('#planEmptyState').show();
        renderPlansList();
        loadTable();
    });
    $('#planFilterClient').on('change', function() {
        var id = $(this).val();
        if (id) selectPlan(id);
        else {
            selectedPlanId = null;
            selectedPlan = null;
            $('#planHeaderCard, #planScheduleCard').hide();
            $('#planEmptyState').show();
            loadTable();
        }
    });
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
