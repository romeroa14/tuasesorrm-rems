<!-- Header con información de la ficha -->
<div class="d-flex justify-content-between align-items-center mb-4">
    <div>
        <h4 class="text-primary mb-1">
            <i class="fas fa-tasks mr-2"></i>Aplicar Tabla de Actividades - Ficha #<?= $commission_id ?>
        </h4>
        <p class="text-muted mb-0">Evalúa el cumplimiento de actividades para los agentes internos</p>
    </div>
    <a href="<?= base_url('/app/commission_sheets/all') ?>" class="btn btn-secondary">
        <i class="fas fa-arrow-left mr-1"></i>Volver al Listado
    </a>
</div>

<!-- Información de la ficha -->
<div class="card mb-4">
    <div class="card-header bg-info text-white">
        <h5 class="mb-0">
            <i class="fas fa-info-circle mr-2"></i>Información de la Ficha
        </h5>
    </div>
    <div class="card-body">
        <div class="row">
            <div class="col-md-6">
                <p><strong>Propiedad:</strong> <?= $commission['property_name'] ?? 'N/A' ?></p>
                <p><strong>Tipo de Negocio:</strong> <?= ucfirst($commission['business_type'] ?? 'N/A') ?></p>
            </div>
            <div class="col-md-6">
                <p><strong>Estado:</strong> <span class="badge badge-info"><?= ucfirst($commission['status'] ?? 'N/A') ?></span></p>
                <p><strong>Comisión Total:</strong> $<?= number_format($commission['total_commission_amount'] ?? 0, 2) ?></p>
            </div>
        </div>
    </div>
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
    <!-- Formulario de tabla de actividades -->

<form id="activity-form" method="post" action="<?= base_url('/app/commission_sheets/process_activity_table/' . $commission_id) ?>">
    <?= csrf_field() ?>
    
    <div class="row">
        <?php if (isset($agents['acquisition'])): ?>
        <!-- AGENTE CAPTADOR -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-search mr-2"></i>Agente Captador
                    </h5>
                </div>
                <div class="card-body">
                    <div class="agent-info mb-3">
                        <p><strong>Nombre:</strong> <?= $agents['acquisition'] ?></p>
                        <p><strong>Comisión Original:</strong> $<?= number_format($commission['acquisition_agent_commission_original'] ?? $commission['acquisition_agent_commission'], 2) ?></p>
                        <p><strong>Comisión Calculada:</strong> $<span id="acquisition_calculated_commission" class="font-weight-bold text-success"><?= number_format($commission['acquisition_agent_commission_original'] ?? $commission['acquisition_agent_commission'], 2) ?></span></p>
                        <p><strong>Porcentaje de Cumplimiento:</strong> <span id="acquisition_percentage" class="font-weight-bold text-primary">0%</span></p>
                    </div>
                    
                    <h6 class="text-secondary mb-3">
                        <i class="fas fa-check-square mr-1"></i>Actividades a Evaluar
                    </h6>
                    
                    <div class="activities-list">
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $activity): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input acquisition-activity" 
                                       type="checkbox" 
                                       name="acquisition_activities[]" 
                                       value="<?= $activity['id'] ?>" 
                                       data-percentage="<?= $activity['percentage'] ?>"
                                       id="acquisition_activity_<?= $activity['id'] ?>">
                                <label class="form-check-label" for="acquisition_activity_<?= $activity['id'] ?>">
                                    <?= $activity['name'] ?> 
                                    <span class="badge badge-info"><?= $activity['percentage'] ?>%</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No hay actividades configuradas.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Actividades seleccionadas: <span id="acquisition_count" class="font-weight-bold">0</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <?php if (isset($agents['closing'])): ?>
        <!-- AGENTE CERRADOR -->
        <div class="col-md-6">
            <div class="card shadow">
                <div class="card-header bg-warning text-dark">
                    <h5 class="mb-0">
                        <i class="fas fa-handshake mr-2"></i>Agente Cerrador
                    </h5>
                </div>
                <div class="card-body">
                    <div class="agent-info mb-3">
                        <p><strong>Nombre:</strong> <?= $agents['closing'] ?></p>
                        <p><strong>Comisión Original:</strong> $<?= number_format($commission['closing_agent_commission_original'] ?? $commission['closing_agent_commission'], 2) ?></p>
                        <p><strong>Comisión Calculada:</strong> $<span id="closing_calculated_commission" class="font-weight-bold text-success"><?= number_format($commission['closing_agent_commission_original'] ?? $commission['closing_agent_commission'], 2) ?></span></p>
                        <p><strong>Porcentaje de Cumplimiento:</strong> <span id="closing_percentage" class="font-weight-bold text-primary">0%</span></p>
                    </div>
                    
                    <h6 class="text-secondary mb-3">
                        <i class="fas fa-check-square mr-1"></i>Actividades a Evaluar
                    </h6>
                    
                    <div class="activities-list">
                        <?php if (!empty($activities)): ?>
                            <?php foreach ($activities as $activity): ?>
                            <div class="form-check mb-2">
                                <input class="form-check-input closing-activity" 
                                       type="checkbox" 
                                       name="closing_activities[]" 
                                       value="<?= $activity['id'] ?>" 
                                       data-percentage="<?= $activity['percentage'] ?>"
                                       id="closing_activity_<?= $activity['id'] ?>">
                                <label class="form-check-label" for="closing_activity_<?= $activity['id'] ?>">
                                    <?= $activity['name'] ?> 
                                    <span class="badge badge-info"><?= $activity['percentage'] ?>%</span>
                                </label>
                            </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p class="text-muted">No hay actividades configuradas.</p>
                        <?php endif; ?>
                    </div>
                    
                    <div class="mt-3 text-center">
                        <small class="text-muted">
                            <i class="fas fa-info-circle mr-1"></i>
                            Actividades seleccionadas: <span id="closing_count" class="font-weight-bold">0</span>
                        </small>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>
    </div>

    <!-- Resumen de Evaluación -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="card shadow">
                <div class="card-header bg-primary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-clipboard-list mr-2"></i>Resumen de Evaluación
                    </h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <div class="alert alert-info">
                                <h6 class="font-weight-bold mb-2">
                                    <i class="fas fa-info-circle mr-1"></i>Cómo Funciona
                                </h6>
                                <ul class="mb-0">
                                    <li>Marca las actividades que cada agente interno cumplió</li>
                                    <li>El sistema calculará el porcentaje de cumplimiento automáticamente</li>
                                    <li>La comisión se ajustará según el porcentaje obtenido</li>
                                    <li><strong>Ejemplo:</strong> Si cumple 70% de actividades, recibirá 70% de su comisión</li>
                                </ul>
                            </div>
                        </div>
                        <div class="col-md-6">
                            <div id="summary-stats" class="alert alert-secondary">
                                <h6 class="font-weight-bold mb-2">
                                    <i class="fas fa-chart-pie mr-1"></i>Estadísticas
                                </h6>
                                <p class="mb-1">Comisión Total Original: $<span id="total_original"><?= number_format(($commission['acquisition_agent_commission_original'] ?? $commission['acquisition_agent_commission']) + ($commission['closing_agent_commission_original'] ?? $commission['closing_agent_commission']), 2) ?></span></p>
                                <p class="mb-1">Comisión Total Calculada: $<span id="total_calculated" class="font-weight-bold text-success"><?= number_format(($commission['acquisition_agent_commission_original'] ?? $commission['acquisition_agent_commission']) + ($commission['closing_agent_commission_original'] ?? $commission['closing_agent_commission']), 2) ?></span></p>
                                <p class="mb-0">Diferencia: $<span id="total_difference" class="font-weight-bold">0.00</span></p>
                            </div>
                        </div>
                    </div>
                    
                    <div class="text-center mt-3">
                        <button type="button" id="reset-btn" class="btn btn-warning mr-2">
                            <i class="fas fa-undo mr-1"></i>Limpiar Todo
                        </button>
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save mr-1"></i>Aplicar Tabla de Actividades
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>

<!-- Información de aplicaciones previas -->
<?php if (!empty($previous_activities)): ?>
    <div class="alert alert-info">
        <h6><i class="fas fa-history mr-2"></i>Evaluación Previa Detectada</h6>
        <p class="mb-1"><strong>Fecha de última aplicación:</strong> <?= $previous_activities['applied_date'] ?? 'N/A' ?></p>
        <div class="row">
            <div class="col-md-6">
                <small><strong>Captador:</strong> <?= $previous_activities['acquisition_percentage'] ?? 0 ?>% - <?= count($previous_activities['acquisition_activities'] ?? []) ?> actividades</small>
            </div>
            <div class="col-md-6">
                <small><strong>Cerrador:</strong> <?= $previous_activities['closing_percentage'] ?? 0 ?>% - <?= count($previous_activities['closing_activities'] ?? []) ?> actividades</small>
            </div>
        </div>
    </div>
<?php endif; ?>



<!-- Pasar datos PHP a JavaScript -->
<script>
    // Datos de actividades previamente aplicadas (si existen)
    <?php if (!empty($previous_activities)): ?>
        window.previousActivities = <?= json_encode($previous_activities) ?>;
        console.log('📋 Ficha #<?= $commission_id ?>: Cargando datos previos');
    <?php else: ?>
        window.previousActivities = null;
        console.log('📋 Ficha #<?= $commission_id ?>: Sin datos previos (ficha nueva/limpia)');
    <?php endif; ?>
    
    // ID de ficha actual
    window.currentCommissionId = <?= $commission_id ?>;
</script>

<!-- JavaScript cargado desde archivo externo: public/js/activity_table.js --> 