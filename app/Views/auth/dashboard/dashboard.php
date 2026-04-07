<?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 6 || session()->get('id_fk_rol') == 8): ?>
    <div class="row mt-0">
        <div class="col-md-12">
            <h4>
                Resumen de declaraciones
            </h4>
            <div class="dropdown-divider pt-2 pb-2"></div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Aprobadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $approved_properties_declaraciones; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Sin aprobar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $unapproved_properties_declaraciones; ?></div>

                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Rechazadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $rejected_properties_declaraciones; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $full_properties_declaraciones; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 6 || session()->get('id_fk_rol') == 1 || session()->get('id_fk_rol') == 8): ?>
    <div class="row mt-5">
        <div class="col-md-12">
            <h4>
                Resumen de mis propiedades
            </h4>
            <div class="dropdown-divider pt-2 pb-2"></div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Aprobadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $my_approved_properties; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-success"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Sin aprobar</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $my_unapproved_properties; ?></div>

                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-warning"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Rechazadas</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $my_rejected_properties; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-danger"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $my_full_properties; ?></div>
                        </div>
                        <div class="col-auto">
                            <i class="fa fa-home fa-2x text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<?php if(session()->get('id_fk_rol') == 2 || session()->get('id_fk_rol') == 5 || session()->get('id_fk_rol') == 6 || session()->get('id_fk_rol') == 1 || session()->get('id_fk_rol') == 8): ?>
    <div class="row mt-5">
        <div class="col-md-12">
            <h4>
                Accesos directos
            </h4>
            <div class="dropdown-divider pt-2 pb-2"></div>
        </div>
        <div class="col-md-3">
            <a class="text-white" href="<?= base_url('/app/my_visits/all') ?>">
                <div class="card bg-primary">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                        <i class="fa fa-link" aria-hidden="true"></i>
                            Mis visitas
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a class="text-white" href="<?= base_url('/app/my_real_estate_searches/all') ?>">
                <div class="card bg-info">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                        <i class="fa fa-link" aria-hidden="true"></i>
                            Mis búsquedas
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a class="text-white" href="<?= base_url('/app/my_properties/all') ?>">
                <div class="card bg-warning">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                        <i class="fa fa-link" aria-hidden="true"></i>
                            Mis propiedades
                        </div>
                    </div>
                </div>
            </a>
        </div>
        <div class="col-md-3">
            <a class="text-white" href="<?= base_url('/app/assigned_clients/all') ?>">
                <div class="card bg-success">
                    <div class="card-body">
                        <div class="d-flex justify-content-between">
                        <i class="fa fa-link" aria-hidden="true"></i>
                            Mis clientes
                        </div>
                    </div>
                </div>
            </a>
        </div>
    </div>
    <div class="row mt-5">
        <!-- Area Charts -->
        <div class="col-lg-12">
            <div class="card mb-4">
            <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
                <h6 class="m-0 font-weight-bold text-primary">Observa el comportamiento en declaraciones de todos los asesores mes a mes</h6>
            </div>
            <div class="card-body">
                <div class="chart-area">
                <canvas id="myAreaChart"></canvas>
                </div>
                <hr>
                Todas las propiedades acá reflejadas tienen el estatus "Aprobado".
            </div>
            </div>
        </div>
    </div>
<?php endif; ?>