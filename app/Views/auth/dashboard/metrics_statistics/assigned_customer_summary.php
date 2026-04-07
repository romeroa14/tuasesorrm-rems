<div class="row mt-5 mb-5">
    <div class="col-md-12">
        <div class="d-flex flex-column border-bottom mb-4 pb-2">
            <h4 class="font-weight-bold">
                Resumen clientes asignados
            </h4>
            <p>
                Sigue de cerca tus clientes asignados y mantente pendiente de no dejar ninguno sin atender
            </p>
        </div>
    </div>
    <!-- Total atendidos -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total atendidos</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $count_total_attended; ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="bg-success text-white pr-2 pl-2 pt-1 pb-1 w-100 rounded-circle">
                            <i class="fa fa-phone" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Total sin atender -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total sin atender</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $count_total_unattended; ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="bg-danger text-white pr-2 pl-2 pt-1 pb-1 w-100 rounded-circle">
                            <i class="fa fa-phone" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Total sin atender -->
    <div class="col-md-4 mb-4">
        <div class="card h-100">
            <div class="card-body">
                <div class="row align-items-center">
                    <div class="col mr-2">
                        <div class="text-xs font-weight-bold text-uppercase mb-1">Total asignados</div>
                        <div class="h5 mb-0 font-weight-bold text-gray-800"><?= $count_total_assigned; ?></div>
                    </div>
                    <div class="col-auto">
                        <div class="bg-dark text-white pr-2 pl-2 pt-1 pb-1 w-100 rounded-circle">
                            <i class="fa fa-phone" aria-hidden="true"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>