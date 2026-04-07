<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex flex-column border-bottom mb-4 pb-2">
            <h4 class="font-weight-bold">
                Estatus de mis propiedades
            </h4>
            <p>
                Sigue de cerca los estatus de tus propiedades
            </p>
        </div>
    </div>
    
    <!-- Propiedades aprobadas -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Propiedades aprobadas</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $approved_properties; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-home fa-2x text-success"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Propiedades sin aprobar -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Propiedades sin aprobar</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $unapproved_properties; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-home fa-2x text-warning"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Propiedades rechazadas -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Propiedades rechazadas</div>
                        <div class="h5 mb-0 mr-3 font-weight-bold text-gray-800"><?= $rejected_properties; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-home fa-2x text-danger"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Total de propiedades -->
    <div class="col-xl-3 col-md-6 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row no-gutters align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total de propiedades</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $full_properties; ?></div>
                    </div>
                    <div class="col-auto">
                        <i class="fa fa-plus-circle fa-2x text-dark"></i>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>