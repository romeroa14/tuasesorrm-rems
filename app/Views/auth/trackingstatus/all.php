<?= view('shared/modalform/modalform', ['modalform' => $modalform]); ?>

<style>
.ts-board { display: grid; gap: 12px; }
.ts-card {
    display: grid;
    grid-template-columns: 52px 1fr auto;
    gap: 14px;
    align-items: center;
    background: #fff;
    border: 1px solid #e7e5e4;
    border-radius: 16px;
    padding: 14px 16px;
}
.ts-card.is-off { opacity: 0.62; }
.ts-emoji {
    width: 52px;
    height: 52px;
    border-radius: 14px;
    background: #faf7f2;
    display: flex;
    align-items: center;
    justify-content: center;
    font-size: 26px;
}
.ts-name { font-weight: 650; color: #1c1917; font-size: 15px; }
.ts-hint { color: #78716c; font-size: 13px; margin-top: 2px; }
.ts-meta { display: flex; gap: 8px; align-items: center; margin-top: 6px; }
.ts-pill {
    font-size: 11px;
    letter-spacing: .04em;
    text-transform: uppercase;
    border-radius: 999px;
    padding: 3px 8px;
    font-weight: 650;
}
.ts-pill.on { background: #ecfdf3; color: #067647; }
.ts-pill.off { background: #f5f5f4; color: #57534e; }
.ts-actions { display: flex; flex-wrap: wrap; gap: 6px; justify-content: flex-end; }
.ts-actions .btn { border-radius: 999px; }
@media (max-width: 720px) {
    .ts-card { grid-template-columns: 52px 1fr; }
    .ts-actions { grid-column: 1 / -1; justify-content: flex-start; }
}
</style>

<div class="card mb-4" style="border:0; background:transparent; box-shadow:none;">
    <div class="mb-3">
        <h6 class="m-0 font-weight-bold text-primary">Estatus del pipeline</h6>
        <p class="text-muted mb-0 mt-1">Los estatus activos son las columnas del tablero y las opciones que Jev puede elegir. Desactivar uno lo saca de la clasificación sin borrar los leads que ya estaban ahí.</p>
    </div>
    <div class="ts-board">
        <?php foreach ($statuses as $status): ?>
            <?php $active = (int) ($status['is_active'] ?? 1) === 1; ?>
            <article class="ts-card <?= $active ? '' : 'is-off' ?>">
                <div class="ts-emoji" aria-hidden="true"><?= esc($status['emoji'] ?: '🏷️') ?></div>
                <div>
                    <div class="ts-name"><?= esc($status['name']) ?></div>
                    <div class="ts-hint"><?= esc($status['classifier_hint'] ?: 'Sin frase para el clasificador') ?></div>
                    <div class="ts-meta">
                        <span class="ts-pill <?= $active ? 'on' : 'off' ?>"><?= $active ? 'Activo' : 'Inactivo' ?></span>
                        <span class="text-muted" style="font-size:12px;">#<?= (int) $status['id'] ?></span>
                    </div>
                </div>
                <div class="ts-actions">
                    <a class="btn btn-sm <?= $active ? 'btn-outline-secondary' : 'btn-outline-success' ?>"
                       href="<?= base_url('/app/trackingstatus/toggle/'.$status['id']) ?>"
                       onclick="return confirm('<?= $active ? '¿Desactivar este estatus?' : '¿Activar este estatus?' ?>')">
                        <?= $active ? '⏸️ Desactivar' : '▶️ Activar' ?>
                    </a>
                    <a class="btn btn-sm btn-outline-primary" href="<?= base_url('/app/trackingstatus/edit/'.$status['id']) ?>">✏️ Editar</a>
                    <?php if ((int) $status['id'] !== 1): ?>
                        <a class="btn btn-sm btn-outline-danger"
                           href="<?= base_url('/app/trackingstatus/delete/'.$status['id']) ?>"
                           onclick="return confirm('¿Eliminar este estatus?')">🗑️ Eliminar</a>
                    <?php endif; ?>
                </div>
            </article>
        <?php endforeach; ?>
    </div>
</div>
