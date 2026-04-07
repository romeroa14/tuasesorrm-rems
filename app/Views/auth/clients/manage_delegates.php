<div class="card">
    <div class="card-body">
        <div class="d-flex align-items-center pt-3">
            <p class="mb-0">
                <strong>Observación de ATC:</strong>
                <?= $lead['observation'] ?? 'Sin observación' ?>
            </p>
        </div>
        <hr>
        <div class="d-flex align-items-center pt-3">
            <p class="mb-0">
                <strong>Observación del asesor:</strong>
                <?= $assigned['observation'] ?? 'Sin observación' ?>
            </p>
        </div>
        <hr>
        <form action="<?= base_url('/app/delegates/manage/'.$lead['id']) ?>" method="post">
            <?= csrf_field() ?>
            <div class="form-group">
                <?= form_label('Asesores', 'users'); ?>
                <?= form_dropdown('users', array_column($users, 'full_name', 'id'), $assigned['assigned_id'] ?? '', 'class="select2-single form-control w-100" id="select2Single_users" required') ?>
            </div>
            <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                <i class="fa fa-wrench"></i> Guardar
            </button>
        </form>
    </div>
</div>