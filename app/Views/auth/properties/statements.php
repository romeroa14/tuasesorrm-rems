<?php
$listPath = ! empty($statements_list_path)
    ? (str_starts_with((string) $statements_list_path, 'http')
        ? $statements_list_path
        : base_url($statements_list_path))
    : base_url('/app/statements/all');
$startDate = $start_date ?? date('Y-m-01');
$endDate = $end_date ?? date('Y-m-d');
$stats = $statement_stats ?? ['total' => 0, 'approved_with_docs' => 0, 'approved_without_docs' => 0];
$showCaptacionExtras = ! empty($statements_active);
?>

<div class="card border-0 shadow-sm mb-4">
    <div class="card-body py-3">
        <form method="get" action="<?= esc($listPath) ?>">
            <div class="row align-items-center">
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label for="start_date" class="text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Desde la fecha</label>
                        <input type="date" class="form-control form-control-sm rounded-0" name="start_date" id="start_date" value="<?= esc($startDate) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0">
                        <label for="end_date" class="text-muted text-uppercase font-weight-bold" style="font-size: 0.75rem;">Hasta la fecha</label>
                        <input type="date" class="form-control form-control-sm rounded-0" name="end_date" id="end_date" value="<?= esc($endDate) ?>">
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="form-group mb-0 mt-4 d-flex">
                        <button type="submit" class="btn btn-primary btn-sm w-50 rounded-0 mr-1">
                            <i class="fa fa-filter"></i> Filtrar
                        </button>
                        <?php if ($showCaptacionExtras): ?>
                        <button type="button" class="btn btn-info btn-sm w-50 rounded-0 ml-1" data-toggle="modal" data-target="#topAgentsModal">
                            <i class="fa fa-trophy"></i> Top Agentes
                        </button>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </form>
    </div>
</div>

<div class="row mb-4">
    <div class="col-md-4">
        <div class="card border-0 shadow-sm">
            <div class="card-body text-center">
                <h6 class="text-muted text-uppercase font-weight-bold mb-2">Total Inmuebles</h6>
                <h2 class="text-primary mb-0 font-weight-bold"><?= (int) ($stats['total'] ?? 0) ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm<?= $showCaptacionExtras ? '' : '' ?>" <?= $showCaptacionExtras ? 'style="cursor: pointer;" data-toggle="modal" data-target="#withDocsModal"' : '' ?>>
            <div class="card-body text-center">
                <h6 class="text-muted text-uppercase font-weight-bold mb-2">
                    Aprobados CON Documentos
                    <?php if ($showCaptacionExtras): ?><i class="fa fa-external-link ml-1"></i><?php endif; ?>
                </h6>
                <h2 class="text-success mb-0 font-weight-bold"><?= (int) ($stats['approved_with_docs'] ?? 0) ?></h2>
            </div>
        </div>
    </div>

    <div class="col-md-4">
        <div class="card border-0 shadow-sm" <?= $showCaptacionExtras ? 'style="cursor: pointer;" data-toggle="modal" data-target="#withoutDocsModal"' : '' ?>>
            <div class="card-body text-center">
                <h6 class="text-muted text-uppercase font-weight-bold mb-2">
                    Aprobados SIN Documentos
                    <?php if ($showCaptacionExtras): ?><i class="fa fa-external-link ml-1"></i><?php endif; ?>
                </h6>
                <h2 class="text-danger mb-0 font-weight-bold"><?= (int) ($stats['approved_without_docs'] ?? 0) ?></h2>
            </div>
        </div>
    </div>
</div>

<?php if ($showCaptacionExtras): ?>
<div class="modal fade" id="topAgentsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-info text-white rounded-0">
                <h5 class="modal-title"><i class="fa fa-trophy"></i> Top Captadores (Aprobados)</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($top_agents)): ?>
                        <li class="list-group-item text-center text-muted py-4">Sin captaciones aprobadas en el periodo.</li>
                    <?php else: ?>
                        <?php foreach ($top_agents as $i => $row): ?>
                            <?php
                            $rank = $i + 1;
                            $badgeClass = $rank <= 3 ? 'badge-warning text-dark' : 'badge-secondary';
                            ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <div>
                                    <span class="badge <?= $badgeClass ?> mr-2" style="width: 25px;"><?= $rank ?></span>
                                    <span class="font-weight-bold text-secondary"><?= esc($row['agent']) ?></span>
                                </div>
                                <span class="badge badge-info badge-pill" style="font-size: 0.85rem;"><?= (int) $row['count'] ?> prop.</span>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-0" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="withDocsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-success text-white rounded-0">
                <h5 class="modal-title"><i class="fa fa-file-text"></i> Propiedades CON Documentos</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($properties_with_docs)): ?>
                        <li class="list-group-item text-center text-muted py-4">Ninguna propiedad aprobada con documentos en el periodo.</li>
                    <?php else: ?>
                        <?php foreach ($properties_with_docs as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-success"><?= esc($item['label']) ?></span>
                                <a href="<?= esc($item['view_url']) ?>" class="btn btn-primary btn-sm rounded-0">Gestionar</a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-0" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>

<div class="modal fade" id="withoutDocsModal" tabindex="-1" role="dialog" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-dialog-scrollable" role="document">
        <div class="modal-content border-0 shadow">
            <div class="modal-header bg-danger text-white rounded-0">
                <h5 class="modal-title"><i class="fa fa-exclamation-triangle"></i> Propiedades SIN Documentos</h5>
                <button type="button" class="close text-white" data-dismiss="modal" aria-label="Close">
                    <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body p-0">
                <ul class="list-group list-group-flush">
                    <?php if (empty($properties_without_docs)): ?>
                        <li class="list-group-item text-center text-muted py-4">Ninguna propiedad aprobada sin documentos en el periodo.</li>
                    <?php else: ?>
                        <?php foreach ($properties_without_docs as $item): ?>
                            <li class="list-group-item d-flex justify-content-between align-items-center">
                                <span class="font-weight-bold text-danger"><?= esc($item['label']) ?></span>
                                <a href="<?= esc($item['view_url']) ?>" class="btn btn-primary btn-sm rounded-0">Gestionar</a>
                            </li>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </ul>
            </div>
            <div class="modal-footer bg-light border-0">
                <button type="button" class="btn btn-secondary btn-sm rounded-0" data-dismiss="modal">Cerrar</button>
            </div>
        </div>
    </div>
</div>
<?php endif; ?>

<?php if (! empty($datatable)): ?>
    <?= view('shared/datatable/datatable', ['datatable' => $datatable]) ?>
<?php endif; ?>
