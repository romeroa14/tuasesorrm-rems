<!-- Commission Settlement Form — create/edit period -->
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
            <i class="fas fa-calculator text-success"></i> <?= esc($title ?? 'Comisiones — Liquidación') ?>
        </h1>
        <a href="<?= base_url('/app/finance/commission/settlements') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver a Liquidaciones
        </a>
    </div>

    <?php $settlement = $settlement ?? []; $isEdit = !empty($settlement['id']); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-calendar-alt"></i> Período de Liquidación</h6>
        </div>
        <div class="card-body">
            <form method="post" action="<?= base_url('/app/finance/commission/save-settlement') ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= esc($settlement['id']) ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fecha Inicio del Período <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="period_start" required
                                   value="<?= esc($settlement['period_start'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fecha Fin del Período <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="period_end" required
                                   value="<?= esc($settlement['period_end'] ?? '') ?>">
                        </div>
                    </div>
                </div>

                <?php if ($isEdit && ($settlement['status'] ?? '') === 'finalized'): ?>
                    <div class="alert alert-info">
                        <i class="fas fa-lock"></i> Esta liquidación ya fue finalizada y no se puede modificar.
                    </div>
                <?php else: ?>
                    <div class="form-group text-right">
                        <button type="submit" class="btn btn-success">
                            <i class="fas fa-save"></i> <?= $isEdit ? 'Actualizar' : 'Guardar' ?> Período
                        </button>
                    </div>
                <?php endif; ?>
            </form>
        </div>
    </div>
</div>
