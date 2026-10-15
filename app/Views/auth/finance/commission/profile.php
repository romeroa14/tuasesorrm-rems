<div class="container-fluid">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success"><?= esc(session()->getFlashdata('success')) ?></div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger"><?= esc(session()->getFlashdata('error')) ?></div>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-percentage text-primary"></i> Comisiones — Perfil de reparto
        </h1>
    </div>

    <?php $profile = $profile ?? []; $roles = $profile['roles'] ?? []; ?>

    <form method="post" action="<?= base_url('/app/finance/commission/save-profile') ?>">
        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">De la comisión de la venta</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Sobre lo que cobra la inmobiliaria (precio × %), se reparte así. Debe sumar 100%.</p>
                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Empresa (%)</label>
                            <input type="number" step="0.01" class="form-control" name="company_pct" value="<?= esc($profile['company_pct'] ?? 50) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Captó la venta (%)</label>
                            <input type="number" step="0.01" class="form-control" name="captador_pct" value="<?= esc($profile['captador_pct'] ?? 25) ?>" required>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Closer (%)</label>
                            <input type="number" step="0.01" class="form-control" name="closer_pct" value="<?= esc($profile['closer_pct'] ?? 25) ?>" required>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="card shadow mb-4">
            <div class="card-header py-3">
                <h6 class="m-0 font-weight-bold text-primary">De la porción de la empresa</h6>
            </div>
            <div class="card-body">
                <p class="text-muted">Porcentajes sobre la porción de la empresa. En el ingreso se elige uno de estos perfiles y la persona. Luego se pueden ajustar aquí.</p>
                <?php foreach ($roles as $i => $role): ?>
                <div class="row align-items-end">
                    <input type="hidden" name="roles[<?= $i ?>][code]" value="<?= esc($role['code']) ?>">
                    <input type="hidden" name="roles[<?= $i ?>][sort_order]" value="<?= esc($role['sort_order'] ?? $i) ?>">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Perfil</label>
                            <input type="text" class="form-control" name="roles[<?= $i ?>][label]" value="<?= esc($role['label']) ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>% de la comisión empresa</label>
                            <input type="number" step="0.01" class="form-control" name="roles[<?= $i ?>][pct_of_company]" value="<?= esc($role['pct_of_company'] ?? 0) ?>">
                        </div>
                    </div>
                    <div class="col-md-2">
                        <div class="form-group">
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="hidden" name="roles[<?= $i ?>][active]" value="0">
                                <input type="checkbox" class="custom-control-input" id="role_active_<?= $i ?>" name="roles[<?= $i ?>][active]" value="1" <?= ! empty($role['active']) ? 'checked' : '' ?>>
                                <label class="custom-control-label" for="role_active_<?= $i ?>">Activo</label>
                            </div>
                        </div>
                    </div>
                </div>
                <?php endforeach; ?>
                <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Guardar perfil</button>
            </div>
        </div>
    </form>
</div>
