
<!-- Botón para abrir modal -->
<div class="header-right ">
    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#addcommission_modalform">
        <i class="fa fa-plus" aria-hidden="true"></i> Registrar Ficha de Comisión
    </button>
</div>

<!-- Modal para registrar ficha de comisión -->
<div class="modal fade" id="addcommission_modalform" tabindex="-1" role="dialog" aria-labelledby="commissionModalLabel" aria-hidden="true" data-backdrop="static" data-keyboard="false">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="commissionModalLabel">
                    <i class="fas fa-file-invoice-dollar mr-2"></i>Registrar Nueva Ficha de Comisión
                    <small class="d-block text-light mt-1" style="font-size: 0.75rem;">
                        <i class="fas fa-shield-alt mr-1"></i>Modal protegido contra cierre accidental
                    </small>
                </h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close" title="Cerrar formulario">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-4">
                <form action="<?= base_url('/app/commission_sheets/create') ?>" method="post" enctype="multipart/form-data">
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
                                <input type="date" class="form-control form-control-sm" name="reservation_date">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="registry_signing_date">Fecha de Firma de Registro</label>
                                <input type="date" class="form-control form-control-sm" name="registry_signing_date">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="payment_date">Fecha de Pago</label>
                                <input type="date" class="form-control form-control-sm" name="payment_date">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="status">Estado <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="status" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="pending">Pendiente</option>
                                    <option value="approved">Aprobado</option>
                                    <option value="paid">Pagado</option>
                                    <option value="cancelled">Cancelado</option>
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
                                <input type="text" class="form-control form-control-sm" name="origin_ownership" placeholder="Ej: Interna, Externa, Referido" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="property_name">Nombre de Propiedad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="property_name" placeholder="Ej: Casa en Vista Alegre" required>
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="business_type">Tipo de Negocio <span class="text-danger">*</span></label>
                                <select class="form-control form-control-sm" name="business_type" required>
                                    <option value="">Seleccionar...</option>
                                    <option value="sale">Venta</option>
                                    <option value="purchase">Compra</option>
                                    <option value="rental">Alquiler</option>
                                    <option value="other">Otro</option>
                                </select>
                            </div>
                        </div>
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="property_address">Dirección de Propiedad <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="property_address" placeholder="Ej: Calle 45 entre 23 y 24" required>
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
                                <input type="text" class="form-control form-control-sm" name="owner_full_name" placeholder="Ej: Juan Pérez" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="owner_phone">Teléfono del Propietario <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="owner_phone" placeholder="Ej: +584120000000" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="buyer_full_name">Nombre del Comprador/Inquilino <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="buyer_full_name" placeholder="Ej: María García" required>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div class="form-group">
                                <label for="buyer_phone">Teléfono del Comprador/Inquilino <span class="text-danger">*</span></label>
                                <input type="text" class="form-control form-control-sm" name="buyer_phone" placeholder="Ej: +584120000000" required>
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
                                    <option value="1">Agente Interno</option>
                                    <option value="0">Agente Externo</option>
                                </select>
                            </div>
                            
                            <!-- Campos para Agente Interno (Captador) -->
                            <div id="acquisition_internal_fields" style="display: none;">
                                <div class="form-group">
                                    <label for="acquisition_agent_id">Seleccionar Agente</label>
                                    <select class="form-control form-control-sm" name="acquisition_agent_id" id="acquisition_agent_id">
                                        <option value="">Seleccionar agente...</option>
                                        <?php foreach ($agents as $agent): ?>
                                            <option value="<?= $agent['id'] ?>"><?= ucfirst($agent['name']) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Campos para Agente Externo (Captador) -->
                            <div id="acquisition_external_fields" style="display: none;">
                                <div class="form-group">
                                    <label for="external_acquisition_agent_name">Nombre del Agente</label>
                                    <input type="text" class="form-control form-control-sm" name="external_acquisition_agent_name" id="external_acquisition_agent_name" placeholder="Nombre completo">
                                </div>
                                <div class="form-group">
                                    <label for="external_acquisition_agent_phone">Teléfono del Agente</label>
                                    <input type="text" class="form-control form-control-sm" name="external_acquisition_agent_phone" id="external_acquisition_agent_phone" placeholder="+584120000000">
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
                                    <option value="1">Agente Interno</option>
                                    <option value="0">Agente Externo</option>
                                </select>
                            </div>
                            
                            <!-- Campos para Agente Interno (Cerrador) -->
                            <div id="closing_internal_fields" style="display: none;">
                                <div class="form-group">
                                    <label for="closing_agent_id">Seleccionar Agente</label>
                                    <select class="form-control form-control-sm" name="closing_agent_id" id="closing_agent_id">
                                        <option value="">Seleccionar agente...</option>
                                        <?php foreach ($agents as $agent): ?>
                                            <option value="<?= $agent['id'] ?>"><?= ucfirst($agent['name']) ?></option>
                                        <?php endforeach ?>
                                    </select>
                                </div>
                            </div>
                            
                            <!-- Campos para Agente Externo (Cerrador) -->
                            <div id="closing_external_fields" style="display: none;">
                                <div class="form-group">
                                    <label for="external_closing_agent_name">Nombre del Agente</label>
                                    <input type="text" class="form-control form-control-sm" name="external_closing_agent_name" id="external_closing_agent_name" placeholder="Nombre completo">
                                </div>
                                <div class="form-group">
                                    <label for="external_closing_agent_phone">Teléfono del Agente</label>
                                    <input type="text" class="form-control form-control-sm" name="external_closing_agent_phone" id="external_closing_agent_phone" placeholder="+584120000000">
                                </div>
                            </div>
                        </div>
                        
                        <!-- INFORMACIÓN DE REFERIDO -->
                        <div class="col-md-12 mt-3">
                            <div class="form-group">
                                <label for="referral_info">Información de Referido</label>
                                <input type="text" class="form-control form-control-sm" name="referral_info" placeholder="Información adicional sobre referidos">
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
                                <input step="0.01" type="number" class="form-control form-control-sm" name="property_amount" id="property_amount" placeholder="150000.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="negotiated_amount" class="font-weight-bold">Monto Negociado Final <span class="text-danger">*</span></label>
                                <input step="0.01" type="number" class="form-control form-control-sm font-weight-bold" name="negotiated_amount" id="negotiated_amount" placeholder="150000.00" style="background-color: #e3f2fd;" required>
                                <small class="text-success"><i class="fas fa-calculator mr-1"></i>Base para cálculos de comisión</small>
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_commission_percentage" class="font-weight-bold">% Total de Comisión <span class="text-danger">*</span></label>
                                <div class="input-group">
                                    <input step="0.01" type="number" class="form-control form-control-sm font-weight-bold" name="total_commission_percentage" id="total_commission_percentage" placeholder="5.00" min="0" max="100" style="background-color: #e3f2fd;">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="acquisition_agent_percentage" id="acquisition_agent_percentage" placeholder="25.00" min="0" max="100">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="closing_agent_percentage" id="closing_agent_percentage" placeholder="25.00" min="0" max="100">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="company_percentage" id="company_percentage" placeholder="50.00" min="0" max="100">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm percentage-field" name="referral_percentage" id="referral_percentage" placeholder="0.00" min="0" max="100">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="customer_service_percentage" id="customer_service_percentage" placeholder="20.00" min="0" max="100">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="visit_percentage" id="visit_percentage" placeholder="20.00" min="0" max="100">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="coordinator_percentage" id="coordinator_percentage" placeholder="30.00" min="0" max="100">
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
                                    <input step="0.01" type="number" class="form-control form-control-sm company-percentage-field" name="manager_percentage" id="manager_percentage" placeholder="30.00" min="0" max="100">
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
                                <input step="0.01" type="number" class="form-control form-control-sm" name="reservation_amount" placeholder="0.00">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="total_commission_amount" class="font-weight-bold">Total de Comisiones</label>
                                <input step="0.01" type="number" class="form-control form-control-sm font-weight-bold calculated-field" name="total_commission_amount" id="total_commission_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="external_agent_commission">Comisión Agente Externo</label>
                                <input step="0.01" type="number" class="form-control form-control-sm" name="external_agent_commission" placeholder="0.00">
                            </div>
                        </div>
                        
                        <!-- COMISIONES CALCULADAS -->
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="acquisition_agent_commission">Comisión Agente Captador</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="acquisition_agent_commission" id="acquisition_agent_commission" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="closing_agent_commission">Comisión Agente Cerrador</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="closing_agent_commission" id="closing_agent_commission" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-4">
                            <div class="form-group">
                                <label for="referral_commission">Comisión por Referido</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="referral_commission" id="referral_commission" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        
                        <!-- DISTRIBUCIÓN INMOBILIARIA CALCULADA -->
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="customer_service_amount">Monto ATC</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="customer_service_amount" id="customer_service_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="visit_amount">Monto por Visitas</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="visit_amount" id="visit_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="coordinator_amount">Monto para Coordinador</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="coordinator_amount" id="coordinator_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="form-group">
                                <label for="manager_amount">Monto para Gerente</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="manager_amount" id="manager_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;">
                            </div>
                        </div>
                        
                        <div class="col-md-12">
                            <div class="form-group">
                                <label for="external_amount">Monto Externo / Resto Inmobiliaria</label>
                                <input step="0.01" type="number" class="form-control form-control-sm calculated-field" name="external_amount" id="external_amount" placeholder="0.00" readonly style="background-color: #f8f9fa;">
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
                                <textarea class="form-control" rows="3" name="notes" placeholder="Observaciones y notas adicionales sobre la ficha de comisión..."></textarea>
                            </div>
                        </div>
                    </div>
                </form>
            </div>
            <div class="modal-footer bg-light">
                <button type="button" class="btn btn-secondary" data-dismiss="modal" id="cancel-btn">
                    <i class="fas fa-times mr-1"></i>Cancelar
                </button>
                <button type="button" id="calculate-btn" class="btn btn-info">
                    <i class="fas fa-calculator mr-1"></i>Calcular Comisiones
                </button>
                <button type="submit" form="commission-form" class="btn btn-success">
                    <i class="fas fa-save mr-1"></i>Registrar Ficha
                </button>
            </div>
        </div>
    </div>
</div>

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

<?= view('shared/datatable/datatable'); ?>

<!-- PLANTILLA PDF DE VISTA PREVIA -->
<div class="row mt-5">
    <div class="col-12">
        <div class="card shadow-lg">
            <div class="card-header bg-gradient-primary text-white">
                <h5 class="mb-0">
                    <i class="fas fa-file-pdf mr-2"></i>Vista Previa del Formato PDF
                    <small class="float-right">
                        <i class="fas fa-eye mr-1"></i>Así se verá tu ficha al descargar
                    </small>
                </h5>
            </div>
            <div class="card-body p-0">
                <!-- Contenedor del PDF -->
                <div id="pdf-preview-container" style="background: #f8f9fa; padding: 20px;">
                    <div id="pdf-template" style="background: white; margin: 0 auto; max-width: 8.5in; min-height: 11in; padding: 0.75in; box-shadow: 0 4px 8px rgba(0,0,0,0.1); font-family: 'Segoe UI', Tahoma, Geneva, Verdana, sans-serif;">
                        
                        <!-- HEADER PRINCIPAL -->
                        <div style="text-align: center; margin-bottom: 30px; border-bottom: 3px solid #0066cc; padding-bottom: 20px;">
                            <div style="display: flex; align-items: center; justify-content: space-between;">
                                <div style="flex: 1;">
                                    <img src="<?= base_url('img/logo/logo.png') ?>" alt="Logo" style="height: 60px; object-fit: contain;">
                                </div>
                                <div style="flex: 2; text-align: center;">
                                    <h1 style="font-size: 28px; font-weight: bold; color: #0066cc; margin: 0; text-transform: uppercase; letter-spacing: 1px;">
                                        ASESORES RM
                                    </h1>
                                    <h2 style="font-size: 18px; color: #333; margin: 5px 0; font-weight: 600;">
                                        FICHA DE COMISIÓN INMOBILIARIA
                                    </h2>
                                    <div style="background: #0066cc; color: white; padding: 8px 16px; border-radius: 20px; display: inline-block; font-weight: bold; font-size: 14px;">
                                        FICHA No. 000001
                                    </div>
                                </div>
                                <div style="flex: 1; text-align: right; font-size: 11px; color: #666;">
                                    <div><strong>Generado:</strong></div>
                                    <div id="pdf-date"><?= date('d/m/Y H:i:s') ?></div>
                                    <div style="margin-top: 8px;">
                                        <span style="background: #28a745; color: white; padding: 4px 8px; border-radius: 12px; font-size: 10px;">
                                            APROBADO
                                        </span>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- INFORMACIÓN GENERAL Y PROPIEDAD -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                            <!-- INFORMACIÓN GENERAL -->
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #0066cc;">
                                <h3 style="font-size: 14px; color: #0066cc; margin: 0 0 12px 0; font-weight: bold; text-transform: uppercase;">
                                    📋 INFORMACIÓN GENERAL
                                </h3>
                                <div style="font-size: 11px; line-height: 1.6;">
                                    <div style="margin-bottom: 6px;"><strong>Reserva:</strong> 15/01/2025</div>
                                    <div style="margin-bottom: 6px;"><strong>Firma:</strong> 20/01/2025</div>
                                    <div style="margin-bottom: 6px;"><strong>Pago:</strong> 25/01/2025</div>
                                    <div style="margin-bottom: 6px;"><strong>Tipo:</strong> <span style="background: #17a2b8; color: white; padding: 2px 6px; border-radius: 4px; font-size: 10px;">VENTA</span></div>
                                    <div><strong>Origen:</strong> Propiedad Interna</div>
                                </div>
                            </div>

                            <!-- INFORMACIÓN DE PROPIEDAD -->
                            <div style="background: #f8f9fa; padding: 15px; border-radius: 8px; border-left: 4px solid #28a745;">
                                <h3 style="font-size: 14px; color: #28a745; margin: 0 0 12px 0; font-weight: bold; text-transform: uppercase;">
                                    🏠 INFORMACIÓN DE PROPIEDAD
                                </h3>
                                <div style="font-size: 11px; line-height: 1.6;">
                                    <div style="margin-bottom: 6px; font-weight: bold; color: #0066cc;">Apartamento Vista Hermosa</div>
                                    <div style="margin-bottom: 6px; font-size: 10px; color: #666;">Av. Principal, Torre A, Piso 12, Apto 12-B</div>
                                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 8px; margin-top: 8px;">
                                        <div><strong>Valor Lista:</strong><br><span style="color: #666;">$180,000.00</span></div>
                                        <div><strong>Monto Final:</strong><br><span style="color: #28a745; font-weight: bold; font-size: 12px;">$175,000.00</span></div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- PROPIETARIO Y COMPRADOR -->
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 25px;">
                            <div style="background: #fff3cd; padding: 15px; border-radius: 8px; border: 1px solid #ffeaa7;">
                                <h4 style="font-size: 13px; color: #856404; margin: 0 0 10px 0; font-weight: bold;">
                                    👤 PROPIETARIO
                                </h4>
                                <div style="font-size: 11px;">
                                    <div style="font-weight: bold; margin-bottom: 4px;">Carlos Eduardo Martínez</div>
                                    <div style="color: #666;">📱 +58 412-1234567</div>
                                    <div style="color: #666; font-size: 10px;">📧 carlos.martinez@email.com</div>
                                </div>
                            </div>
                            
                            <div style="background: #d4edda; padding: 15px; border-radius: 8px; border: 1px solid #c3e6cb;">
                                <h4 style="font-size: 13px; color: #155724; margin: 0 0 10px 0; font-weight: bold;">
                                    🛒 COMPRADOR/INQUILINO
                                </h4>
                                <div style="font-size: 11px;">
                                    <div style="font-weight: bold; margin-bottom: 4px;">María Alejandra González</div>
                                    <div style="color: #666;">📱 +58 424-7654321</div>
                                    <div style="color: #666; font-size: 10px;">📧 maria.gonzalez@email.com</div>
                                </div>
                            </div>
                        </div>

                        <!-- INFORMACIÓN DE AGENTES -->
                        <div style="background: linear-gradient(135deg, #667eea 0%, #764ba2 100%); color: white; padding: 15px; border-radius: 8px; margin-bottom: 25px;">
                            <h3 style="font-size: 14px; margin: 0 0 15px 0; font-weight: bold; text-transform: uppercase; text-align: center;">
                                🤝 EQUIPO DE AGENTES
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
                                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 8px;">
                                        🔍 AGENTE CAPTADOR
                                    </div>
                                    <div style="font-size: 11px;">
                                        <div style="font-weight: bold;">Roberto Silva</div>
                                        <div style="opacity: 0.9;">Agente Interno</div>
                                        <div style="background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 12px; display: inline-block; margin-top: 4px; font-size: 10px;">
                                            ID: AG-001
                                        </div>
                                    </div>
                                </div>
                                
                                <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
                                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 8px;">
                                        🤝 AGENTE CERRADOR
                                    </div>
                                    <div style="font-size: 11px;">
                                        <div style="font-weight: bold;">Ana Patricia López</div>
                                        <div style="opacity: 0.9;">Agente Interno</div>
                                        <div style="background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 12px; display: inline-block; margin-top: 4px; font-size: 10px;">
                                            ID: AG-015
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <!-- DESGLOSE DE COMISIONES -->
                        <div style="margin-bottom: 25px;">
                            <h3 style="font-size: 16px; color: #0066cc; margin: 0 0 15px 0; font-weight: bold; text-transform: uppercase; text-align: center; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                                💰 DESGLOSE DETALLADO DE COMISIONES
                            </h3>
                            
                            <!-- Comisión Total -->
                            <div style="background: linear-gradient(135deg, #28a745, #20c997); color: white; padding: 15px; border-radius: 8px; margin-bottom: 15px; text-align: center;">
                                <div style="font-size: 12px; opacity: 0.9; margin-bottom: 5px;">COMISIÓN TOTAL</div>
                                <div style="font-size: 24px; font-weight: bold;">$8,750.00</div>
                                <div style="font-size: 11px; opacity: 0.9;">5.00% sobre $175,000.00</div>
                            </div>

                            <!-- Distribución Principal -->
                            <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden; margin-bottom: 15px;">
                                <div style="background: #0066cc; color: white; padding: 8px 12px; font-size: 12px; font-weight: bold;">
                                    📊 DISTRIBUCIÓN PRINCIPAL
                                </div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <thead>
                                        <tr style="background: #f8f9fa;">
                                            <th style="padding: 8px 12px; text-align: left; font-size: 11px; border-bottom: 1px solid #dee2e6;">CONCEPTO</th>
                                            <th style="padding: 8px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #dee2e6;">PORCENTAJE</th>
                                            <th style="padding: 8px 12px; text-align: right; font-size: 11px; border-bottom: 1px solid #dee2e6;">MONTO</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 11px; border-bottom: 1px solid #f1f3f4;">🔍 Agente Captador</td>
                                            <td style="padding: 8px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #f1f3f4; font-weight: bold; color: #dc3545;">25.00%</td>
                                            <td style="padding: 8px 12px; text-align: right; font-size: 11px; border-bottom: 1px solid #f1f3f4; font-weight: bold; color: #28a745;">$2,187.50</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 11px; border-bottom: 1px solid #f1f3f4;">🤝 Agente Cerrador</td>
                                            <td style="padding: 8px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #f1f3f4; font-weight: bold; color: #dc3545;">25.00%</td>
                                            <td style="padding: 8px 12px; text-align: right; font-size: 11px; border-bottom: 1px solid #f1f3f4; font-weight: bold; color: #28a745;">$2,187.50</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 11px; border-bottom: 1px solid #f1f3f4;">🏢 Inmobiliaria</td>
                                            <td style="padding: 8px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #f1f3f4; font-weight: bold; color: #dc3545;">50.00%</td>
                                            <td style="padding: 8px 12px; text-align: right; font-size: 11px; border-bottom: 1px solid #f1f3f4; font-weight: bold; color: #28a745;">$4,375.00</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>

                            <!-- Distribución Interna Inmobiliaria -->
                            <div style="background: white; border: 1px solid #dee2e6; border-radius: 8px; overflow: hidden;">
                                <div style="background: #7c3aed; color: white; padding: 8px 12px; font-size: 12px; font-weight: bold;">
                                    🏢 DISTRIBUCIÓN INTERNA INMOBILIARIA
                                </div>
                                <table style="width: 100%; border-collapse: collapse;">
                                    <tbody>
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 11px; border-bottom: 1px solid #f1f3f4;">📞 Atención al Cliente (ATC)</td>
                                            <td style="padding: 8px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #f1f3f4; color: #dc3545;">20.00%</td>
                                            <td style="padding: 8px 12px; text-align: right; font-size: 11px; border-bottom: 1px solid #f1f3f4; color: #28a745;">$875.00</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 11px; border-bottom: 1px solid #f1f3f4;">🏠 Coordinación de Visitas</td>
                                            <td style="padding: 8px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #f1f3f4; color: #dc3545;">20.00%</td>
                                            <td style="padding: 8px 12px; text-align: right; font-size: 11px; border-bottom: 1px solid #f1f3f4; color: #28a745;">$875.00</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 11px; border-bottom: 1px solid #f1f3f4;">👨‍💼 Coordinador</td>
                                            <td style="padding: 8px 12px; text-align: center; font-size: 11px; border-bottom: 1px solid #f1f3f4; color: #dc3545;">30.00%</td>
                                            <td style="padding: 8px 12px; text-align: right; font-size: 11px; border-bottom: 1px solid #f1f3f4; color: #28a745;">$1,312.50</td>
                                        </tr>
                                        <tr>
                                            <td style="padding: 8px 12px; font-size: 11px; font-weight: bold;">🎯 Gerencia</td>
                                            <td style="padding: 8px 12px; text-align: center; font-size: 11px; color: #dc3545; font-weight: bold;">30.00%</td>
                                            <td style="padding: 8px 12px; text-align: right; font-size: 11px; color: #28a745; font-weight: bold;">$1,312.50</td>
                                        </tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <!-- TABLA DE ACTIVIDADES APLICADA -->
                        <div style="background: #e8f5e8; border: 1px solid #c3e6cb; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                            <h3 style="font-size: 14px; color: #155724; margin: 0 0 15px 0; font-weight: bold; text-align: center;">
                                ✅ TABLA DE ACTIVIDADES APLICADA
                            </h3>
                            <div style="background: rgba(21,87,36,0.1); padding: 10px; border-radius: 6px; margin-bottom: 15px; text-align: center; font-size: 11px;">
                                <strong>Evaluación aplicada el:</strong> 27/07/2025 14:30 hrs
                            </div>
                            
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 15px;">
                                <!-- Actividades Captador -->
                                <div style="background: white; padding: 12px; border-radius: 6px; border-left: 4px solid #28a745;">
                                    <h4 style="font-size: 12px; color: #155724; margin: 0 0 10px 0; font-weight: bold;">
                                        🔍 AGENTE CAPTADOR - Roberto Silva
                                    </h4>
                                    <div style="font-size: 10px; margin-bottom: 8px;">
                                        <strong>Comisión Original:</strong> $2,187.50 | 
                                        <strong style="color: #28a745;">Comisión Final:</strong> $1,531.25 | 
                                        <strong style="color: #dc3545;">Cumplimiento:</strong> 70%
                                    </div>
                                    <div style="font-size: 10px; line-height: 1.4;">
                                        <div style="margin-bottom: 3px;">✓ Realizó la visita inicial <span style="color: #666;">(20%)</span></div>
                                        <div style="margin-bottom: 3px;">✓ Presentó documentos <span style="color: #666;">(25%)</span></div>
                                        <div style="margin-bottom: 3px;">✓ Coordinó firmas <span style="color: #666;">(25%)</span></div>
                                        <div style="color: #dc3545;">✗ Seguimiento post-venta <span style="color: #666;">(30%)</span></div>
                                    </div>
                                </div>

                                <!-- Actividades Cerrador -->
                                <div style="background: white; padding: 12px; border-radius: 6px; border-left: 4px solid #ffc107;">
                                    <h4 style="font-size: 12px; color: #856404; margin: 0 0 10px 0; font-weight: bold;">
                                        🤝 AGENTE CERRADOR - Ana Patricia López
                                    </h4>
                                    <div style="font-size: 10px; margin-bottom: 8px;">
                                        <strong>Comisión Original:</strong> $2,187.50 | 
                                        <strong style="color: #28a745;">Comisión Final:</strong> $1,968.75 | 
                                        <strong style="color: #dc3545;">Cumplimiento:</strong> 90%
                                    </div>
                                    <div style="font-size: 10px; line-height: 1.4;">
                                        <div style="margin-bottom: 3px;">✓ Negociación efectiva <span style="color: #666;">(30%)</span></div>
                                        <div style="margin-bottom: 3px;">✓ Cierre de contrato <span style="color: #666;">(30%)</span></div>
                                        <div style="margin-bottom: 3px;">✓ Documentación legal <span style="color: #666;">(30%)</span></div>
                                        <div style="color: #dc3545;">✗ Entrega de llaves <span style="color: #666;">(10%)</span></div>
                                    </div>
                                </div>
                            </div>

                            <div style="background: rgba(21,87,36,0.1); padding: 8px; border-radius: 4px; margin-top: 10px; text-align: center; font-size: 10px; font-style: italic;">
                                * Las comisiones mostradas ya reflejan los ajustes por cumplimiento de actividades según tabla establecida.
                            </div>
                        </div>

                        <!-- RESUMEN FINANCIERO -->
                        <div style="background: linear-gradient(135deg, #1e3c72, #2a5298); color: white; padding: 20px; border-radius: 8px; margin-bottom: 25px;">
                            <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: bold; text-transform: uppercase; text-align: center;">
                                📈 RESUMEN FINANCIERO FINAL
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr 1fr; gap: 15px; text-align: center;">
                                <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
                                    <div style="font-size: 11px; opacity: 0.9; margin-bottom: 4px;">TOTAL AGENTES</div>
                                    <div style="font-size: 18px; font-weight: bold;">$3,500.00</div>
                                    <div style="font-size: 10px; opacity: 0.8;">40% de la comisión</div>
                                </div>
                                <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
                                    <div style="font-size: 11px; opacity: 0.9; margin-bottom: 4px;">TOTAL INMOBILIARIA</div>
                                    <div style="font-size: 18px; font-weight: bold;">$4,375.00</div>
                                    <div style="font-size: 10px; opacity: 0.8;">50% de la comisión</div>
                                </div>
                                <div style="background: rgba(255,255,255,0.1); padding: 12px; border-radius: 6px;">
                                    <div style="font-size: 11px; opacity: 0.9; margin-bottom: 4px;">AJUSTE POR ACTIVIDADES</div>
                                    <div style="font-size: 18px; font-weight: bold; color: #ffc107;">-$875.00</div>
                                    <div style="font-size: 10px; opacity: 0.8;">Descuento por incumplimiento</div>
                                </div>
                            </div>
                        </div>

                        <!-- NOTAS ADICIONALES -->
                        <div style="background: #fff3cd; border: 1px solid #ffeaa7; border-radius: 8px; padding: 15px; margin-bottom: 25px;">
                            <h4 style="font-size: 13px; color: #856404; margin: 0 0 10px 0; font-weight: bold;">
                                📝 NOTAS ADICIONALES
                            </h4>
                            <div style="font-size: 11px; color: #856404; line-height: 1.5;">
                                Cliente requiere entrega inmediata. Se coordinó con área legal para acelerar trámites. 
                                Propietario muy satisfecho con el servicio brindado. Se recomienda hacer seguimiento 
                                post-venta para futuras referencias.
                            </div>
                        </div>

                        <!-- FIRMAS Y AUTORIZACIONES -->
                        <div style="margin-bottom: 20px;">
                            <h3 style="font-size: 14px; color: #0066cc; margin: 0 0 20px 0; font-weight: bold; text-transform: uppercase; text-align: center; background: #f8f9fa; padding: 10px; border-radius: 8px;">
                                ✍️ FIRMAS Y AUTORIZACIONES
                            </h3>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                                <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center;">
                                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #28a745;">
                                        🔍 AGENTE CAPTADOR
                                    </div>
                                    <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                                    <div style="font-size: 10px; font-weight: bold;">Roberto Silva</div>
                                    <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                                </div>
                                <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center;">
                                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #ffc107;">
                                        🤝 AGENTE CERRADOR
                                    </div>
                                    <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                                    <div style="font-size: 10px; font-weight: bold;">Ana Patricia López</div>
                                    <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                                </div>
                            </div>
                            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
                                <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center;">
                                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #6f42c1;">
                                        👨‍💼 COORDINADOR/SUPERVISOR
                                    </div>
                                    <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                                    <div style="font-size: 10px; font-weight: bold;">Nombre: _______________</div>
                                    <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                                </div>
                                <div style="border: 1px solid #dee2e6; border-radius: 8px; padding: 15px; text-align: center;">
                                    <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #dc3545;">
                                        🎯 GERENCIA
                                    </div>
                                    <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                                    <div style="font-size: 10px; font-weight: bold;">Nombre: _______________</div>
                                    <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                                </div>
                            </div>
                        </div>

                        <!-- FOOTER CORPORATIVO -->
                        <div style="text-align: center; padding-top: 20px; border-top: 2px solid #dee2e6; font-size: 10px; color: #666;">
                            <div style="margin-bottom: 8px;">
                                <strong style="color: #0066cc; font-size: 12px;">ASESORES RM - SERVICIOS INMOBILIARIOS INTEGRALES</strong>
                            </div>
                            <div style="margin-bottom: 6px;">
                                📧 info@asesoresrm.com | 📱 +58 412-0000000 | 🌐 www.asesoresrm.com
                            </div>
                            <div style="font-size: 9px; color: #999; font-style: italic;">
                                Este documento constituye la ficha oficial de comisión para la operación inmobiliaria descrita. 
                                Todas las comisiones están sujetas a los términos y condiciones establecidos en los contratos correspondientes.
                            </div>
                            <div style="margin-top: 8px; font-size: 8px; color: #aaa;">
                                Documento generado automáticamente el <?= date('d/m/Y H:i:s') ?> | Sistema ASESORES RM v2.0
                            </div>
                        </div>

                    </div>
                </div>
                
                <!-- Botones de acción -->
                <div class="card-footer bg-light text-center">
                    <div class="btn-group" role="group">
                        <button type="button" class="btn btn-outline-primary" onclick="togglePDFPreview()">
                            <i class="fas fa-eye mr-1"></i>Mostrar/Ocultar Vista Previa
                        </button>
                        <button type="button" class="btn btn-info" onclick="downloadExamplePDF()">
                            <i class="fas fa-download mr-1"></i>Descargar PDF de Ejemplo
                        </button>
                        <button type="button" class="btn btn-success" onclick="printPDFPreview()">
                            <i class="fas fa-print mr-1"></i>Imprimir Vista Previa
                        </button>
                    </div>
                    <div class="mt-2">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Esta es una vista previa del formato. Los datos reales se cargarán dinámicamente desde la base de datos.
                        </small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- JavaScript para la vista previa -->
<script>
function togglePDFPreview() {
    const container = document.getElementById('pdf-preview-container');
    if (container.style.display === 'none') {
        container.style.display = 'block';
    } else {
        container.style.display = 'none';
    }
}

function printPDFPreview() {
    const content = document.getElementById('pdf-template');
    const newWin = window.open('', '_blank');
    newWin.document.write(`
        <html>
        <head>
            <title>Vista Previa - Ficha de Comisión</title>
            <style>
                body { margin: 0; padding: 20px; font-family: Arial, sans-serif; }
                @page { size: legal; margin: 0.5in; }
            </style>
        </head>
        <body>
            ${content.innerHTML}
        </body>
        </html>
    `);
    newWin.document.close();
    newWin.print();
}

// Función para descargar PDF de ejemplo sin llamadas al servidor
function downloadExamplePDF() {
    console.log('🎯 Generando PDF de ejemplo con HTML simplificado...');
    
    // Verificar que html2pdf esté disponible
    if (typeof html2pdf === 'undefined') {
        console.error('❌ html2pdf no está disponible');
        alert('Error: Librería PDF no disponible');
        return;
    }
    
    // Mostrar indicador de carga
    showPDFLoadingExample();
    
    // Crear HTML simplificado compatible con html2canvas
    const simpleHTML = `
        <div style="width: 100%; max-width: 800px; font-family: Arial, sans-serif; font-size: 12px; line-height: 1.4; color: #333; padding: 20px; background: white;">
            
            <!-- HEADER -->
            <div style="text-align: center; margin-bottom: 30px; border-bottom: 2px solid #0066cc; padding-bottom: 15px;">
                <h1 style="font-size: 24px; color: #0066cc; margin: 0 0 10px 0; font-weight: bold;">ASESORES RM</h1>
                <h2 style="font-size: 18px; color: #333; margin: 0 0 10px 0;">FICHA DE COMISION INMOBILIARIA</h2>
                <div style="background: #0066cc; color: white; padding: 8px 16px; border-radius: 15px; display: inline-block; font-size: 14px; font-weight: bold;">
                    FICHA No. 000001
                </div>
                <div style="font-size: 10px; color: #666; margin-top: 10px;">
                    Generado el: ${new Date().toLocaleDateString('es-ES')} ${new Date().toLocaleTimeString('es-ES')}
                </div>
            </div>

            <!-- INFORMACION GENERAL -->
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 14px; color: #0066cc; margin: 0 0 15px 0; font-weight: bold; background: #f0f8ff; padding: 8px; border-left: 4px solid #0066cc;">
                    INFORMACION GENERAL
                </h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9; width: 25%;">Estado:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">APROBADO</td>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9; width: 25%;">Fecha Reserva:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">15/01/2025</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Tipo:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">VENTA</td>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Fecha Firma:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">20/01/2025</td>
                    </tr>
                </table>
            </div>

            <!-- PROPIEDAD -->
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 14px; color: #28a745; margin: 0 0 15px 0; font-weight: bold; background: #f0fff4; padding: 8px; border-left: 4px solid #28a745;">
                    INFORMACION DE LA PROPIEDAD
                </h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Propiedad:</td>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; color: #0066cc;" colspan="3">Apartamento Vista Hermosa</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Direccion:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;" colspan="3">Av. Principal, Torre A, Piso 12, Apto 12-B</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Valor Lista:</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: right;">$180,000.00</td>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Monto Final:</td>
                        <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; color: #28a745;">$175,000.00</td>
                    </tr>
                </table>
            </div>

            <!-- PROPIETARIO Y COMPRADOR -->
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 14px; color: #6f42c1; margin: 0 0 15px 0; font-weight: bold; background: #f8f4ff; padding: 8px; border-left: 4px solid #6f42c1;">
                    PROPIETARIO Y COMPRADOR
                </h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 12px; width: 50%; vertical-align: top;">
                            <strong style="color: #6f42c1;">PROPIETARIO</strong><br>
                            <strong>Nombre:</strong> Carlos Eduardo Martinez<br>
                            <strong>Telefono:</strong> +58 412-1234567<br>
                            <strong>Email:</strong> carlos.martinez@email.com
                        </td>
                        <td style="border: 1px solid #ddd; padding: 12px; width: 50%; vertical-align: top;">
                            <strong style="color: #28a745;">COMPRADOR</strong><br>
                            <strong>Nombre:</strong> Maria Alejandra Gonzalez<br>
                            <strong>Telefono:</strong> +58 424-7654321<br>
                            <strong>Email:</strong> maria.gonzalez@email.com
                        </td>
                    </tr>
                </table>
            </div>

            <!-- AGENTES -->
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 14px; color: #dc3545; margin: 0 0 15px 0; font-weight: bold; background: #fff5f5; padding: 8px; border-left: 4px solid #dc3545;">
                    EQUIPO DE AGENTES
                </h3>
                <table style="width: 100%; border-collapse: collapse; font-size: 11px;">
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Agente Captador:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">Roberto Silva (Interno)</td>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">ID:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">AG-001</td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">Agente Cerrador:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">Ana Patricia Lopez (Interno)</td>
                        <td style="border: 1px solid #ddd; padding: 8px; font-weight: bold; background: #f9f9f9;">ID:</td>
                        <td style="border: 1px solid #ddd; padding: 8px;">AG-015</td>
                    </tr>
                </table>
            </div>

            <!-- DESGLOSE DE COMISIONES -->
            <div style="margin-bottom: 25px;">
                <h3 style="font-size: 16px; color: #0066cc; margin: 0 0 15px 0; font-weight: bold; text-align: center; background: #f0f8ff; padding: 12px; border: 2px solid #0066cc;">
                    DESGLOSE DETALLADO DE COMISIONES
                </h3>
                
                <!-- Comision Total -->
                <div style="background: #28a745; color: white; padding: 15px; text-align: center; margin-bottom: 15px; border-radius: 8px;">
                    <div style="font-size: 12px; margin-bottom: 5px;">COMISION TOTAL</div>
                    <div style="font-size: 24px; font-weight: bold;">$8,750.00</div>
                    <div style="font-size: 11px;">5.00% sobre $175,000.00</div>
                </div>

                <!-- Distribucion Principal -->
                <table style="width: 100%; border-collapse: collapse; margin-bottom: 15px;">
                    <thead>
                        <tr style="background: #0066cc; color: white;">
                            <th style="border: 1px solid #0066cc; padding: 8px; text-align: left; font-size: 12px;">CONCEPTO</th>
                            <th style="border: 1px solid #0066cc; padding: 8px; text-align: center; font-size: 12px;">PORCENTAJE</th>
                            <th style="border: 1px solid #0066cc; padding: 8px; text-align: right; font-size: 12px;">MONTO</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-size: 11px;">Agente Captador</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold; color: #dc3545; font-size: 11px;">25.00%</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; color: #28a745; font-size: 11px;">$2,187.50</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-size: 11px;">Agente Cerrador</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold; color: #dc3545; font-size: 11px;">25.00%</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; color: #28a745; font-size: 11px;">$2,187.50</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-size: 11px;">Inmobiliaria</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; font-weight: bold; color: #dc3545; font-size: 11px;">50.00%</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right; font-weight: bold; color: #28a745; font-size: 11px;">$4,375.00</td>
                        </tr>
                    </tbody>
                </table>

                <!-- Distribucion Interna -->
                <table style="width: 100%; border-collapse: collapse;">
                    <thead>
                        <tr style="background: #6f42c1; color: white;">
                            <th style="border: 1px solid #6f42c1; padding: 8px; text-align: left; font-size: 12px;" colspan="3">DISTRIBUCION INTERNA INMOBILIARIA</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-size: 11px;">Atencion al Cliente (ATC)</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #dc3545; font-size: 11px;">20.00%</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #28a745; font-size: 11px;">$875.00</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-size: 11px;">Coordinacion de Visitas</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #dc3545; font-size: 11px;">20.00%</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #28a745; font-size: 11px;">$875.00</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-size: 11px;">Coordinador</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #dc3545; font-size: 11px;">30.00%</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #28a745; font-size: 11px;">$1,312.50</td>
                        </tr>
                        <tr>
                            <td style="border: 1px solid #ddd; padding: 8px; font-size: 11px; font-weight: bold;">Gerencia</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: center; color: #dc3545; font-size: 11px; font-weight: bold;">30.00%</td>
                            <td style="border: 1px solid #ddd; padding: 8px; text-align: right; color: #28a745; font-size: 11px; font-weight: bold;">$1,312.50</td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TABLA DE ACTIVIDADES -->
            <div style="background: #e8f5e8; border: 1px solid #c3e6cb; padding: 15px; margin-bottom: 25px; border-radius: 8px;">
                <h3 style="font-size: 14px; color: #155724; margin: 0 0 15px 0; font-weight: bold; text-align: center;">
                    TABLA DE ACTIVIDADES APLICADA
                </h3>
                <div style="background: #d4edda; padding: 10px; text-align: center; font-size: 11px; margin-bottom: 15px; border-radius: 5px;">
                    <strong>Evaluacion aplicada el:</strong> 27/07/2025 14:30 hrs
                </div>
                
                <table style="width: 100%; border-collapse: collapse; font-size: 10px;">
                    <tr>
                        <td style="width: 50%; vertical-align: top; padding: 10px;">
                            <strong style="color: #155724;">AGENTE CAPTADOR - Roberto Silva</strong><br>
                            <strong>Comision Original:</strong> $2,187.50<br>
                            <strong style="color: #28a745;">Comision Final:</strong> $1,531.25<br>
                            <strong style="color: #dc3545;">Cumplimiento:</strong> 70%<br><br>
                            <div style="line-height: 1.4;">
                                ✓ Realizo la visita inicial (20%)<br>
                                ✓ Presento documentos (25%)<br>
                                ✓ Coordino firmas (25%)<br>
                                ✗ Seguimiento post-venta (30%)
                            </div>
                        </td>
                        <td style="width: 50%; vertical-align: top; padding: 10px; border-left: 1px solid #c3e6cb;">
                            <strong style="color: #856404;">AGENTE CERRADOR - Ana Patricia Lopez</strong><br>
                            <strong>Comision Original:</strong> $2,187.50<br>
                            <strong style="color: #28a745;">Comision Final:</strong> $1,968.75<br>
                            <strong style="color: #dc3545;">Cumplimiento:</strong> 90%<br><br>
                            <div style="line-height: 1.4;">
                                ✓ Negociacion efectiva (30%)<br>
                                ✓ Cierre de contrato (30%)<br>
                                ✓ Documentacion legal (30%)<br>
                                ✗ Entrega de llaves (10%)
                            </div>
                        </td>
                    </tr>
                </table>
                
                <div style="background: #d4edda; padding: 8px; text-align: center; font-size: 9px; margin-top: 10px; border-radius: 5px; font-style: italic;">
                    * Las comisiones mostradas ya reflejan los ajustes por cumplimiento de actividades segun tabla establecida.
                </div>
            </div>

            <!-- RESUMEN FINANCIERO -->
            <div style="background: #1e3c72; color: white; padding: 20px; margin-bottom: 25px; border-radius: 8px;">
                <h3 style="font-size: 16px; margin: 0 0 15px 0; font-weight: bold; text-align: center;">
                    RESUMEN FINANCIERO FINAL
                </h3>
                <table style="width: 100%; color: white;">
                    <tr>
                        <td style="width: 33.33%; text-align: center; padding: 10px;">
                            <div style="font-size: 11px; margin-bottom: 4px;">TOTAL AGENTES</div>
                            <div style="font-size: 18px; font-weight: bold;">$3,500.00</div>
                            <div style="font-size: 10px;">40% de la comision</div>
                        </td>
                        <td style="width: 33.33%; text-align: center; padding: 10px;">
                            <div style="font-size: 11px; margin-bottom: 4px;">TOTAL INMOBILIARIA</div>
                            <div style="font-size: 18px; font-weight: bold;">$4,375.00</div>
                            <div style="font-size: 10px;">50% de la comision</div>
                        </td>
                        <td style="width: 33.33%; text-align: center; padding: 10px;">
                            <div style="font-size: 11px; margin-bottom: 4px;">AJUSTE POR ACTIVIDADES</div>
                            <div style="font-size: 18px; font-weight: bold; color: #ffc107;">-$875.00</div>
                            <div style="font-size: 10px;">Descuento por incumplimiento</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- NOTAS -->
            <div style="background: #fff3cd; border: 1px solid #ffeaa7; padding: 15px; margin-bottom: 25px; border-radius: 8px;">
                <h4 style="font-size: 13px; color: #856404; margin: 0 0 10px 0; font-weight: bold;">
                    NOTAS ADICIONALES
                </h4>
                <div style="font-size: 11px; color: #856404; line-height: 1.5;">
                    Cliente requiere entrega inmediata. Se coordino con area legal para acelerar tramites. 
                    Propietario muy satisfecho con el servicio brindado. Se recomienda hacer seguimiento 
                    post-venta para futuras referencias.
                </div>
            </div>

            <!-- FIRMAS -->
            <div style="margin-bottom: 20px;">
                <h3 style="font-size: 14px; color: #0066cc; margin: 0 0 20px 0; font-weight: bold; text-align: center; background: #f0f8ff; padding: 10px; border: 2px solid #0066cc;">
                    FIRMAS Y AUTORIZACIONES
                </h3>
                <table style="width: 100%; border-collapse: collapse;">
                    <tr>
                        <td style="width: 50%; border: 1px solid #ddd; padding: 15px; text-align: center;">
                            <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #28a745;">
                                AGENTE CAPTADOR
                            </div>
                            <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                            <div style="font-size: 10px; font-weight: bold;">Roberto Silva</div>
                            <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                        </td>
                        <td style="width: 50%; border: 1px solid #ddd; padding: 15px; text-align: center;">
                            <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #ffc107;">
                                AGENTE CERRADOR
                            </div>
                            <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                            <div style="font-size: 10px; font-weight: bold;">Ana Patricia Lopez</div>
                            <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                        </td>
                    </tr>
                    <tr>
                        <td style="border: 1px solid #ddd; padding: 15px; text-align: center;">
                            <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #6f42c1;">
                                COORDINADOR/SUPERVISOR
                            </div>
                            <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                            <div style="font-size: 10px; font-weight: bold;">Nombre: _______________</div>
                            <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                        </td>
                        <td style="border: 1px solid #ddd; padding: 15px; text-align: center;">
                            <div style="font-size: 12px; font-weight: bold; margin-bottom: 15px; color: #dc3545;">
                                GERENCIA
                            </div>
                            <div style="height: 50px; border-bottom: 1px solid #ccc; margin-bottom: 8px;"></div>
                            <div style="font-size: 10px; font-weight: bold;">Nombre: _______________</div>
                            <div style="font-size: 9px; color: #666;">Fecha: _______________</div>
                        </td>
                    </tr>
                </table>
            </div>

            <!-- FOOTER -->
            <div style="text-align: center; padding-top: 20px; border-top: 2px solid #ddd; font-size: 10px; color: #666;">
                <div style="margin-bottom: 8px;">
                    <strong style="color: #0066cc; font-size: 12px;">ASESORES RM - SERVICIOS INMOBILIARIOS INTEGRALES</strong>
                </div>
                <div style="margin-bottom: 6px;">
                    Email: info@asesoresrm.com | Telefono: +58 412-0000000 | Web: www.asesoresrm.com
                </div>
                <div style="font-size: 9px; color: #999; font-style: italic;">
                    Este documento constituye la ficha oficial de comision para la operacion inmobiliaria descrita. 
                    Todas las comisiones estan sujetas a los terminos y condiciones establecidos en los contratos correspondientes.
                </div>
                <div style="margin-top: 8px; font-size: 8px; color: #aaa;">
                    Documento generado automaticamente el ${new Date().toLocaleDateString('es-ES')} | Sistema ASESORES RM v2.0
                </div>
            </div>

        </div>
    `;
    
    console.log('📋 Generando PDF con HTML simplificado...');
    
    // Crear contenedor temporal VISIBLE para html2canvas
    const tempContainer = document.createElement('div');
    tempContainer.innerHTML = simpleHTML;
    
    // 🚨 HACER VISIBLE TEMPORALMENTE - html2canvas NO funciona con elementos ocultos
    tempContainer.style.position = 'fixed';
    tempContainer.style.top = '0';
    tempContainer.style.left = '0';
    tempContainer.style.width = '800px';
    tempContainer.style.minHeight = '1200px';
    tempContainer.style.backgroundColor = '#ffffff';
    tempContainer.style.padding = '20px';
    tempContainer.style.fontFamily = 'Arial, sans-serif';
    tempContainer.style.fontSize = '12px';
    tempContainer.style.lineHeight = '1.4';
    tempContainer.style.color = '#333';
    tempContainer.style.zIndex = '999999';
    tempContainer.style.overflow = 'visible';
    
    // Agregar overlay para ocultar el contenido durante la captura
    const overlay = document.createElement('div');
    overlay.style.position = 'fixed';
    overlay.style.top = '0';
    overlay.style.left = '0';
    overlay.style.width = '100%';
    overlay.style.height = '100%';
    overlay.style.backgroundColor = 'rgba(0,0,0,0.8)';
    overlay.style.zIndex = '999998';
    overlay.style.display = 'flex';
    overlay.style.alignItems = 'center';
    overlay.style.justifyContent = 'center';
    overlay.style.color = 'white';
    overlay.style.fontSize = '18px';
    overlay.innerHTML = '<div><i class="fas fa-file-pdf"></i> Generando PDF...</div>';
    
    document.body.appendChild(overlay);
    document.body.appendChild(tempContainer);
    
    console.log('📊 Contenedor VISIBLE creado, verificando contenido...');
    console.log('📏 Alto del contenedor:', tempContainer.scrollHeight + 'px');
    console.log('📄 Contenido presente:', tempContainer.innerHTML.length > 100 ? 'SÍ' : 'NO');
    
    // Configuración optimizada para captura visible
    const opt = {
        margin: 0.5,
        filename: `Ejemplo_Ficha_Comision_${new Date().toISOString().split('T')[0]}.pdf`,
        image: { type: 'jpeg', quality: 0.98 },
        html2canvas: { 
            scale: 1.5,
            useCORS: true,
            allowTaint: true,
            backgroundColor: '#ffffff',
            logging: true, // Activar logging para debug
            width: 800,
            height: tempContainer.scrollHeight || 1200,
            scrollX: 0,
            scrollY: 0,
            x: 0,
            y: 0
        },
        jsPDF: { 
            unit: 'mm',
            format: 'a4',
            orientation: 'portrait' 
        }
    };
    
    // ⏰ ESPERAR A QUE SE RENDERICE COMPLETAMENTE
    setTimeout(() => {
        console.log('⚡ Iniciando captura de contenedor VISIBLE...');
        console.log('📊 Verificación final - Alto:', tempContainer.scrollHeight + 'px');
        console.log('📍 Posición del contenedor:', tempContainer.getBoundingClientRect());
        
        // Generar PDF
        html2pdf()
            .set(opt)
            .from(tempContainer)
            .save()
            .then(() => {
                console.log('✅ PDF generado exitosamente desde contenedor visible');
                // Limpiar elementos temporales
                document.body.removeChild(tempContainer);
                document.body.removeChild(overlay);
                hidePDFLoadingExample();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'success',
                        title: '¡PDF Generado!',
                        text: 'El PDF se ha descargado correctamente con contenido visible.',
                        timer: 3000,
                        showConfirmButton: false
                    });
                }
            })
            .catch((error) => {
                console.error('❌ Error al generar PDF desde contenedor visible:', error);
                // Limpiar elementos temporales
                document.body.removeChild(tempContainer);
                document.body.removeChild(overlay);
                hidePDFLoadingExample();
                
                if (typeof Swal !== 'undefined') {
                    Swal.fire({
                        icon: 'error',
                        title: 'Error en Generación',
                        text: 'Error al generar el PDF: ' + error.message,
                        footer: 'Revisa la consola para más detalles'
                    });
                } else {
                    alert('Error al generar el PDF: ' + error.message);
                }
            });
    }, 1000); // ⏰ Esperar 1 segundo para renderización completa
}

// Funciones para el indicador de carga del ejemplo
function showPDFLoadingExample() {
    if ($('#pdf-loading-example').length === 0) {
        $('body').append(`
            <div id="pdf-loading-example" style="
                position: fixed; top: 0; left: 0; right: 0; bottom: 0; 
                background: rgba(0,0,0,0.8); z-index: 9999; 
                display: flex; align-items: center; justify-content: center;
            ">
                <div style="
                    background: white; padding: 40px; border-radius: 15px; 
                    text-align: center; max-width: 350px; box-shadow: 0 10px 30px rgba(0,0,0,0.3);
                ">
                    <div class="spinner-border text-primary mb-3" role="status" style="width: 3rem; height: 3rem;"></div>
                    <h4 style="color: #0066cc; margin-bottom: 10px;">Generando PDF de Ejemplo</h4>
                    <p class="text-muted mb-0">Creando documento con datos de muestra...</p>
                    <div style="margin-top: 15px;">
                        <small class="text-success">
                            <i class="fas fa-check-circle mr-1"></i>Usando plantilla optimizada
                        </small>
                    </div>
                </div>
            </div>
        `);
    }
}

function hidePDFLoadingExample() {
    $('#pdf-loading-example').remove();
}

// Ocultar la vista previa inicialmente
document.addEventListener('DOMContentLoaded', function() {
    document.getElementById('pdf-preview-container').style.display = 'none';
});
</script>