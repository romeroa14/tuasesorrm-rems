<!-- Header con botón de regreso -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="text-primary mb-1">
            <i class="fas fa-file-invoice-dollar mr-2"></i>Gestionar Ficha de Comisión #<?= $commission_id ?>
        </h4>
        <p class="text-muted mb-0">Visualiza y edita los datos de la ficha de comisión seleccionada</p>
    </div>
    <a href="<?= base_url('/app/commission_sheets/all') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i>Volver al Listado
    </a>
</div>

<!-- Alertas de notificación -->
<?php if (session()->getFlashdata('info')): ?>
    <div class="alert alert-info alert-dismissible fade show" role="alert">
        <i class="fas fa-info-circle"></i> <?= session()->getFlashdata('info') ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('error')): ?>
    <div class="alert alert-danger alert-dismissible fade show" role="alert">
        <i class="fas fa-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<?php if (session()->getFlashdata('success')): ?>
    <div class="alert alert-success alert-dismissible fade show" role="alert">
        <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
        <button type="button" class="close" data-dismiss="alert" aria-label="Close">
            <span aria-hidden="true">&times;</span>
        </button>
    </div>
<?php endif; ?>

<!-- Información de Tabla de Actividades Aplicada (si existe) -->
<?php if (!empty($commission['activities_applied_log'])): ?>
<div class="card mb-4 border-success">
    <div class="card-header bg-success text-white">
        <h5 class="mb-0">
            <i class="fas fa-tasks mr-2"></i>Tabla de Actividades Aplicada
        </h5>
    </div>
    <div class="card-body">
        <div class="alert alert-success">
            <i class="fas fa-check-circle mr-2"></i>
            <strong>Esta ficha ya tiene tabla de actividades aplicada.</strong>
        </div>
        
        <?php if (!empty($commission['activities_selected_data'])): ?>
            <?php 
                $activitiesData = json_decode($commission['activities_selected_data'], true);
                if ($activitiesData):
            ?>
            <div class="row mb-3">
                <div class="col-md-12">
                    <div class="card border-info">
                        <div class="card-header bg-info text-white">
                            <h6 class="mb-0">
                                <i class="fas fa-info-circle mr-1"></i>Última Aplicación
                            </h6>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6">
                                    <p><strong>Fecha:</strong> <?= $activitiesData['applied_date'] ?? 'N/A' ?></p>
                                    <p><strong>Captador:</strong> <?= $activitiesData['acquisition_percentage'] ?? 0 ?>% de cumplimiento</p>
                                    <p><strong>Actividades Captador:</strong> <?= count($activitiesData['acquisition_activities'] ?? []) ?> seleccionadas</p>
                                </div>
                                <div class="col-md-6">
                                    <p><strong>Cerrador:</strong> <?= $activitiesData['closing_percentage'] ?? 0 ?>% de cumplimiento</p>
                                    <p><strong>Actividades Cerrador:</strong> <?= count($activitiesData['closing_activities'] ?? []) ?> seleccionadas</p>
                                    <p><strong>Total Evaluadas:</strong> <?= count($activitiesData['acquisition_activities'] ?? []) + count($activitiesData['closing_activities'] ?? []) ?></p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        <?php endif; ?>
        
        <div class="row">
            <?php if ($commission['acquisition_agent_is_internal'] == '1'): ?>
            <div class="col-md-6">
                <div class="card border-success">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 text-success">
                            <i class="fas fa-search mr-1"></i>Agente Captador
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Comisión Final:</strong> 
                           $<?= number_format($commission['acquisition_agent_commission'], 2) ?>
                        </p>
                        <p><small class="text-muted">Comisión ajustada por cumplimiento de actividades</small></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
            
            <?php if ($commission['closing_agent_is_internal'] == '1'): ?>
            <div class="col-md-6">
                <div class="card border-warning">
                    <div class="card-header bg-light">
                        <h6 class="mb-0 text-warning">
                            <i class="fas fa-handshake mr-1"></i>Agente Cerrador
                        </h6>
                    </div>
                    <div class="card-body">
                        <p><strong>Comisión Final:</strong> 
                           $<?= number_format($commission['closing_agent_commission'], 2) ?>
                        </p>
                        <p><small class="text-muted">Comisión ajustada por cumplimiento de actividades</small></p>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
        
        <div class="mt-3">
            <div class="alert alert-info mb-0">
                <i class="fas fa-info-circle mr-2"></i>
                <strong>Nota:</strong> Las comisiones mostradas ya reflejan los ajustes por tabla de actividades. 
                Para reaplicar o modificar la evaluación, usa el botón "Aplicar T.A." en la tabla principal.
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<!-- Formulario principal -->
<div class="card shadow">
    <div class="card-header bg-primary text-white">
        <h5 class="mb-0">
            <i class="fas fa-edit mr-2"></i>Editar Ficha de Comisión
        </h5>
    </div>
    <div class="card-body p-4">
        <form action="<?= base_url('/app/commission_sheets/update/' . $commission_id) ?>" method="post" enctype="multipart/form-data" id="commission-form">
            <?= csrf_field() ?>
            
            <!-- SECCIÓN 1: INFORMACIÓN GENERAL -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-info-circle mr-2"></i>Información General
                    </h6>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="reservation_date">Fecha de Reserva</label>
                        <input type="date" class="form-control form-control-sm" name="reservation_date" value="<?= $commission['reservation_date'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="registry_signing_date">Fecha de Firma de Registro</label>
                        <input type="date" class="form-control form-control-sm" name="registry_signing_date" value="<?= $commission['registry_signing_date'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="payment_date">Fecha de Pago</label>
                        <input type="date" class="form-control form-control-sm" name="payment_date" value="<?= $commission['payment_date'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="status">Estado <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="status" required>
                            <option value="">Seleccionar...</option>
                            <option value="pending" <?= ($commission['status'] ?? '') == 'pending' ? 'selected' : '' ?>>Pendiente</option>
                            <option value="approved" <?= ($commission['status'] ?? '') == 'approved' ? 'selected' : '' ?>>Aprobado</option>
                            <option value="paid" <?= ($commission['status'] ?? '') == 'paid' ? 'selected' : '' ?>>Pagado</option>
                            <option value="cancelled" <?= ($commission['status'] ?? '') == 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                        </select>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: INFORMACIÓN DE LA PROPIEDAD -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-home mr-2"></i>Información de la Propiedad
                    </h6>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="origin_ownership">Origen de Propiedad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="origin_ownership" placeholder="Ej: Interna, Externa, Referido" value="<?= $commission['origin_ownership'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="property_name">Nombre de Propiedad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="property_name" placeholder="Ej: Casa en Vista Alegre" value="<?= $commission['property_name'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="business_type">Tipo de Negocio <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="business_type" required>
                            <option value="">Seleccionar...</option>
                            <option value="sale" <?= ($commission['business_type'] ?? '') == 'sale' ? 'selected' : '' ?>>Venta</option>
                            <option value="purchase" <?= ($commission['business_type'] ?? '') == 'purchase' ? 'selected' : '' ?>>Compra</option>
                            <option value="rental" <?= ($commission['business_type'] ?? '') == 'rental' ? 'selected' : '' ?>>Alquiler</option>
                            <option value="other" <?= ($commission['business_type'] ?? '') == 'other' ? 'selected' : '' ?>>Otro</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="property_address">Dirección de Propiedad <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="property_address" placeholder="Ej: Calle 45 entre 23 y 24" value="<?= $commission['property_address'] ?? '' ?>" required>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 3: PROPIETARIO Y COMPRADOR -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-users mr-2"></i>Propietario y Comprador/Inquilino
                    </h6>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="owner_full_name">Nombre del Propietario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="owner_full_name" placeholder="Ej: Juan Pérez" value="<?= $commission['owner_full_name'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="owner_phone">Teléfono del Propietario <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="owner_phone" placeholder="Ej: +584120000000" value="<?= $commission['owner_phone'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="buyer_full_name">Nombre del Comprador/Inquilino <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="buyer_full_name" placeholder="Ej: María García" value="<?= $commission['buyer_full_name'] ?? '' ?>" required>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="buyer_phone">Teléfono del Comprador/Inquilino <span class="text-danger">*</span></label>
                        <input type="text" class="form-control form-control-sm" name="buyer_phone" placeholder="Ej: +584120000000" value="<?= $commission['buyer_phone'] ?? '' ?>" required>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 4: AGENTES -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-user-tie mr-2"></i>Información de Agentes
                    </h6>
                </div>
                
                <!-- AGENTE CAPTADOR -->
                <div class="col-md-6">
                    <h6 class="text-secondary mb-3"><i class="fas fa-search mr-1"></i> Agente Captador</h6>
                    
                    <div class="form-group">
                        <label for="acquisition_agent_is_internal">Tipo de Agente <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="acquisition_agent_is_internal" id="acquisition_agent_is_internal" required>
                            <option value="">Seleccionar...</option>
                            <option value="1" <?= ($commission['acquisition_agent_is_internal'] ?? '') == '1' ? 'selected' : '' ?>>Agente Interno</option>
                            <option value="0" <?= ($commission['acquisition_agent_is_internal'] ?? '') == '0' ? 'selected' : '' ?>>Agente Externo</option>
                        </select>
                    </div>
                    
                    <!-- Campos para Agente Interno (Captador) -->
                    <div id="acquisition_internal_fields" style="display: <?= ($commission['acquisition_agent_is_internal'] ?? '') == '1' ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label for="acquisition_agent_id">Seleccionar Agente</label>
                            <select class="form-control form-control-sm" name="acquisition_agent_id" id="acquisition_agent_id">
                                <option value="">Seleccionar agente...</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?= $agent['id'] ?>" <?= ($commission['acquisition_agent_id'] ?? '') == $agent['id'] ? 'selected' : '' ?>><?= ucfirst($agent['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Campos para Agente Externo (Captador) -->
                    <div id="acquisition_external_fields" style="display: <?= ($commission['acquisition_agent_is_internal'] ?? '') == '0' ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label for="external_acquisition_agent_name">Nombre del Agente</label>
                            <input type="text" class="form-control form-control-sm" name="external_acquisition_agent_name" id="external_acquisition_agent_name" placeholder="Nombre completo" value="<?= $commission['external_acquisition_agent_name'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="external_acquisition_agent_phone">Teléfono del Agente</label>
                            <input type="text" class="form-control form-control-sm" name="external_acquisition_agent_phone" id="external_acquisition_agent_phone" placeholder="+584120000000" value="<?= $commission['external_acquisition_agent_phone'] ?? '' ?>">
                        </div>
                    </div>
                </div>
                
                <!-- AGENTE CERRADOR -->
                <div class="col-md-6">
                    <h6 class="text-secondary mb-3"><i class="fas fa-handshake mr-1"></i> Agente Cerrador</h6>
                    
                    <div class="form-group">
                        <label for="closing_agent_is_internal">Tipo de Agente <span class="text-danger">*</span></label>
                        <select class="form-control form-control-sm" name="closing_agent_is_internal" id="closing_agent_is_internal" required>
                            <option value="">Seleccionar...</option>
                            <option value="1" <?= ($commission['closing_agent_is_internal'] ?? '') == '1' ? 'selected' : '' ?>>Agente Interno</option>
                            <option value="0" <?= ($commission['closing_agent_is_internal'] ?? '') == '0' ? 'selected' : '' ?>>Agente Externo</option>
                        </select>
                    </div>
                    
                    <!-- Campos para Agente Interno (Cerrador) -->
                    <div id="closing_internal_fields" style="display: <?= ($commission['closing_agent_is_internal'] ?? '') == '1' ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label for="closing_agent_id">Seleccionar Agente</label>
                            <select class="form-control form-control-sm" name="closing_agent_id" id="closing_agent_id">
                                <option value="">Seleccionar agente...</option>
                                <?php foreach ($agents as $agent): ?>
                                    <option value="<?= $agent['id'] ?>" <?= ($commission['closing_agent_id'] ?? '') == $agent['id'] ? 'selected' : '' ?>><?= ucfirst($agent['name']) ?></option>
                                <?php endforeach ?>
                            </select>
                        </div>
                    </div>
                    
                    <!-- Campos para Agente Externo (Cerrador) -->
                    <div id="closing_external_fields" style="display: <?= ($commission['closing_agent_is_internal'] ?? '') == '0' ? 'block' : 'none' ?>;">
                        <div class="form-group">
                            <label for="external_closing_agent_name">Nombre del Agente</label>
                            <input type="text" class="form-control form-control-sm" name="external_closing_agent_name" id="external_closing_agent_name" placeholder="Nombre completo" value="<?= $commission['external_closing_agent_name'] ?? '' ?>">
                        </div>
                        <div class="form-group">
                            <label for="external_closing_agent_phone">Teléfono del Agente</label>
                            <input type="text" class="form-control form-control-sm" name="external_closing_agent_phone" id="external_closing_agent_phone" placeholder="+584120000000" value="<?= $commission['external_closing_agent_phone'] ?? '' ?>">
                        </div>
                    </div>
                </div>
                
                <!-- INFORMACIÓN DE REFERIDO -->
                <div class="col-md-12 mt-3">
                    <div class="form-group">
                        <label for="referral_info">Información de Referido</label>
                        <input type="text" class="form-control form-control-sm" name="referral_info" placeholder="Información adicional sobre referidos" value="<?= $commission['referral_info'] ?? '' ?>">
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 5: CONFIGURACIÓN DE PORCENTAJES -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-percentage mr-2"></i>Configuración de Porcentajes y Cálculo Automático
                    </h6>
                    <div class="alert alert-info mb-3">
                        <i class="fas fa-info-circle mr-2"></i>
                        <strong>Cálculo Automático:</strong> Ingrese el <strong>monto negociado final</strong> y el porcentaje total de comisión. 
                        Los montos se calcularán automáticamente basándose en los porcentajes configurados sobre el valor real de la transacción.
                    </div>
                </div>
                
                <!-- VALOR BASE Y PORCENTAJE TOTAL -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="property_amount">Valor de la Propiedad</label>
                        <input step="0.01" type="number" class="form-control form-control-sm" name="property_amount" id="property_amount" placeholder="150000.00" value="<?= $commission['property_amount'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="negotiated_amount" class="font-weight-bold">Monto Negociado Final <span class="text-danger">*</span></label>
                        <input step="0.01" type="number" class="form-control form-control-sm font-weight-bold" name="negotiated_amount" id="negotiated_amount" placeholder="150000.00" style="background-color: #e3f2fd;" value="<?= $commission['negotiated_amount'] ?? '' ?>" required>
                        <small class="text-success"><i class="fas fa-calculator mr-1"></i>Base para cálculos de comisión</small>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="total_commission_percentage" class="font-weight-bold">% Total de Comisión <span class="text-danger">*</span></label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm font-weight-bold" name="total_commission_percentage" id="total_commission_percentage" placeholder="5.00" min="0" max="100" style="background-color: #e3f2fd;" value="<?= $commission['total_commission_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- DISTRIBUCIÓN PRINCIPAL -->
                <div class="col-12 mt-3">
                    <h6 class="text-secondary mb-3"><i class="fas fa-chart-pie mr-1"></i> Distribución Principal de Comisiones</h6>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="acquisition_agent_percentage">% Agente Captador</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="acquisition_agent_percentage" id="acquisition_agent_percentage" placeholder="25.00" min="0" max="100" value="<?= $commission['acquisition_agent_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="closing_agent_percentage">% Agente Cerrador</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="closing_agent_percentage" id="closing_agent_percentage" placeholder="25.00" min="0" max="100" value="<?= $commission['closing_agent_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="company_percentage">% Inmobiliaria</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="company_percentage" id="company_percentage" placeholder="50.00" min="0" max="100" value="<?= $commission['company_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="referral_percentage">% Referidos</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="referral_percentage" id="referral_percentage" placeholder="0.00" min="0" max="100" value="<?= $commission['referral_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- DISTRIBUCIÓN INTERNA DE LA INMOBILIARIA -->
                <div class="col-12 mt-3">
                    <h6 class="text-secondary mb-3"><i class="fas fa-building mr-1"></i> Distribución Interna de la Inmobiliaria</h6>
                    <small class="text-muted">Los siguientes porcentajes se aplican sobre el monto de la inmobiliaria calculado arriba.</small>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="customer_service_percentage">% ATC</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="customer_service_percentage" id="customer_service_percentage" placeholder="20.00" min="0" max="100" value="<?= $commission['customer_service_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="visit_percentage">% Visitas</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="visit_percentage" id="visit_percentage" placeholder="20.00" min="0" max="100" value="<?= $commission['visit_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="coordinator_percentage">% Coordinador</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="coordinator_percentage" id="coordinator_percentage" placeholder="30.00" min="0" max="100" value="<?= $commission['coordinator_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="manager_percentage">% Gerente</label>
                        <div class="input-group">
                            <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="manager_percentage" id="manager_percentage" placeholder="30.00" min="0" max="100" value="<?= $commission['manager_percentage'] ?? '' ?>">
                            <div class="input-group-append">
                                <span class="input-group-text">%</span>
                            </div>
                        </div>
                    </div>
                </div>
                
                <!-- INDICADORES DE TOTAL -->
                <div class="col-md-6">
                    <div class="alert alert-secondary">
                        <strong>Total Distribución Principal:</strong> <span id="total_main_percentage">0.00</span>%
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="alert alert-secondary">
                        <strong>Total Distribución Inmobiliaria:</strong> <span id="total_company_percentage">0.00</span>%
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 6: MONTOS CALCULADOS -->
            <div class="row mb-4">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-calculator mr-2"></i>Montos Calculados Automáticamente
                    </h6>
                </div>
                
                <!-- MONTOS PRINCIPALES -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="reservation_amount">Monto de Reserva</label>
                        <input step="0.01" type="number" class="form-control form-control-sm" name="reservation_amount" placeholder="0.00" value="<?= $commission['reservation_amount'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="total_commission_amount" class="font-weight-bold">Total de Comisiones</label>
                        <input step="0.01" type="number" class="form-control form-control-sm font-weight-bold calculated-field" name="total_commission_amount" id="total_commission_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['total_commission_amount'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="external_agent_commission">Comisión Agente Externo</label>
                        <input step="0.01" type="number" class="form-control form-control-sm" name="external_agent_commission" placeholder="0.00" value="<?= $commission['external_agent_commission'] ?? '' ?>">
                    </div>
                </div>
                
                <!-- COMISIONES CALCULADAS -->
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="acquisition_agent_commission">Comisión Agente Captador</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="acquisition_agent_commission" id="acquisition_agent_commission" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['acquisition_agent_commission'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="closing_agent_commission">Comisión Agente Cerrador</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="closing_agent_commission" id="closing_agent_commission" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['closing_agent_commission'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group">
                        <label for="referral_commission">Comisión por Referido</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="referral_commission" id="referral_commission" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['referral_commission'] ?? '' ?>">
                    </div>
                </div>
                
                <!-- DISTRIBUCIÓN INMOBILIARIA CALCULADA -->
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="customer_service_amount">Monto ATC</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="customer_service_amount" id="customer_service_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['customer_service_amount'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="visit_amount">Monto por Visitas</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="visit_amount" id="visit_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['visit_amount'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="coordinator_amount">Monto para Coordinador</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="coordinator_amount" id="coordinator_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['coordinator_amount'] ?? '' ?>">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="manager_amount">Monto para Gerente</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="manager_amount" id="manager_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['manager_amount'] ?? '' ?>">
                    </div>
                </div>
                
                <div class="col-md-12">
                    <div class="form-group">
                        <label for="external_amount">Monto Externo / Resto Inmobiliaria</label>
                        <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="external_amount" id="external_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;" value="<?= $commission['external_amount'] ?? '' ?>">
                    </div>
                </div>
            </div>
            
            <!-- SECCIÓN 7: NOTAS -->
            <div class="row">
                <div class="col-12">
                    <h6 class="text-primary border-bottom pb-2 mb-3">
                        <i class="fas fa-sticky-note mr-2"></i>Notas Adicionales
                    </h6>
                    <div class="form-group">
                        <textarea class="form-control" rows="3" name="notes" placeholder="Observaciones y notas adicionales sobre la ficha de comisión..."><?= $commission['notes'] ?? '' ?></textarea>
                    </div>
                </div>
            </div>
        </form>
    </div>
    <div class="card-footer bg-light d-flex justify-content-between">
        <a href="<?= base_url('/app/commission_sheets/all') ?>" class="btn btn-secondary">
            <i class="fas fa-arrow-left mr-1"></i>Cancelar y Volver
        </a>
        <div>
            <button type="button" id="calculate-btn" class="btn btn-info mr-2">
                <i class="fas fa-calculator mr-1"></i>Calcular Comisiones
            </button>
            <button type="submit" form="commission-form" class="btn btn-success">
                <i class="fas fa-save mr-1"></i>Actualizar Ficha
            </button>
        </div>
    </div>
</div>

<!-- JavaScript personalizado para la gestión -->
<script>
// El JavaScript del formulario de comisión se cargará automáticamente desde commission_form.js
// ya que la página tiene "Fichas de Comisiones" en el título
</script> 