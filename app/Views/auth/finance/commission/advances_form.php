<!-- Commission Advance Form — create/edit -->
<div class="container-fluid">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-hand-holding-usd text-warning"></i> <?= esc($title ?? 'Comisiones — Adelanto') ?>
        </h1>
        <a href="<?= base_url('/app/finance/commission/advances') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver a Adelantos
        </a>
    </div>

    <?php $advance = $advance ?? []; $isEdit = !empty($advance['id']); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> Datos del Adelanto</h6>
        </div>
        <div class="card-body">
            <form id="advanceForm" method="post" action="<?= base_url('/app/finance/commission/save-advance') ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= esc($advance['id']) ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Agente <span class="text-danger">*</span></label>
                            <select class="form-control select2-advance" name="user_id" required>
                                <option value="">Seleccionar agente...</option>
                                <?php foreach ($users ?? [] as $user): ?>
                                    <option value="<?= esc($user['id']) ?>" <?= ($advance['user_id'] ?? '') == $user['id'] ? 'selected' : '' ?>>
                                        <?= esc($user['full_name'] ?? $user['name'] ?? ('Usuario #' . $user['id'])) ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Monto ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="amount" required min="0.01"
                                   placeholder="0.00" value="<?= esc($advance['amount'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="form-group">
                            <label>Fecha <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="advance_date" required
                                   value="<?= esc($advance['advance_date'] ?? date('Y-m-d')) ?>">
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Motivo</label>
                    <textarea class="form-control" name="reason" rows="2" placeholder="Motivo del adelanto..."><?= esc($advance['reason'] ?? '') ?></textarea>
                </div>

                <?php if ($isEdit && !empty($advance['settled'])): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-info-circle"></i> Este adelanto ya fue liquidado y no se puede modificar.
                    </div>
                <?php else: ?>
                    <div class="form-group text-right">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> <?= $isEdit ? 'Actualizar' : 'Guardar' ?> Adelanto
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    $('.select2-advance').select2({width: '100%', placeholder: 'Seleccionar agente...'});
});
</script>
