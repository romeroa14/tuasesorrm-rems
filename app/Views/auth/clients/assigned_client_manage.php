<div class="row">
    <div class="col-md-6">
        <div class="card">
            <div class="card-body">
                <h4>Información del cliente</h4>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Cliente:</strong>
                        <?= $client_data['name'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Teléfono:</strong>    
                        <?= $client_data['phone'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>¿Quién te lo asigno?:</strong>
                        <?= $client_data['delegate_name'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>¿De donde proviene?:</strong>
                        <?= $client_data['funnels_name'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>¿Cuál es su interés?:</strong>
                        <?= $client_data['businessmodel_name'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Observación de ATC:</strong>
                        <?= $client_data['observation'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Tú observación:</strong>
                        <?= $client_data['assignedclients_observation'] ?? '---' ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Seguimiento:</strong>
                        <?= $client_data['trackingstatus_name'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Estatus general:</strong>
                        <?= $client_data['general_status'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Fecha cuando fue registrado:</strong>
                        <?= $client_data['created_at'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Fecha cuando te fue asignado:</strong>
                        <?= $client_data['assignment_at'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Fecha cuando lo contactaste:</strong>
                        <?= $client_data['first_contact_at'] ?>
                    </p>
                </div>
                <hr>
                <div class="d-flex align-items-center pt-3">
                    <p class="mb-0">
                        <strong>Días de existencia:</strong>
                        <?= $client_data['days_life'] ?>
                    </p>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card h-100">
            <div class="card-body">
                <h4>Gestión de seguimiento</h4>
                <form class="pt-3" action="<?= base_url('/app/assigned_clients/manage/'.$id_client) ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <?= form_label('Estatus de seguimiento', 'trackingstatus'); ?>
                        <?= form_dropdown('trackingstatus', array_column($trackingstatus, 'name', 'id'), $client_data['trackingstatus_id'] ?? '', 'class="select2-single form-control w-100" id="select2Single_trackingstatus" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Observación', 'observation'); ?>
                        <?= form_textarea('observation', $client_data['assignedclients_observation'] ?? '', ['rows' => '20', 'cols' => '50', 'class' => 'form-control']); ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                        <i class="fa fa-wrench"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
</div>