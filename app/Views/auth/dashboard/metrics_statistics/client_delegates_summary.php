<div class="row mb-3">
    <div class="col-md-12">
        <div class="d-flex flex-column border-bottom mb-4 pb-2">
            <h4 class="font-weight-bold">
                Resumen clientes delegados
            </h4>
            <p>
                Sigue de cerca tus clientes delegados y mantente pendiente de no dejar ninguno sin atender
            </p>
        </div>
    </div>
    <div class="col-md-6">
        <div class="shadow-sm bg-white w-100 mb-3 rounded">
            <div class="d-flex align-items-center">
                <div class="mr-auto p-2">
                    <div class="bg-danger text-white pr-2 pl-2 pt-1 pt-1 pb-1 w-100 rounded-circle">
                        <i class="fa fa-frown-o" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="p-2 pt-3 pb-2 px-3">
                    <h6 class="text-muted">Total sin asignar</h6>
                    <h4 class="font-weight-bold text-danger mb-0">
                        <?= $total_sin_asignar; ?>
                    </h4>
                </div>
            </div>
            <a href="/delegated_clients?dt_filter=Sin asignar" class="badge badge-dark w-100 rounded-0">
                Ver todos sin asignar
            </a>
        </div>
    </div>


    <div class="col-md-6">
        <div class="shadow-sm bg-white w-100 mb-3 rounded">
            <div class="d-flex align-items-center">
                <div class="mr-auto p-2">
                    <div class="bg-danger text-white pr-2 pl-2 pt-1 pb-1 w-100 rounded-circle">
                        <i class="fa fa-frown-o" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="p-2 pt-3 pb-2 px-3">
                    <h6 class="text-muted">Total asignados sin estatus</h6>
                    <h4 class="font-weight-bold text-danger mb-0">
                        <?= $total_asignados_sin_estatus; ?>
                    </h4>
                </div>
            </div>
            <a href="/delegated_clients?dt_filter=Sin estatus" class="badge badge-dark w-100 rounded-0">
                Ver todos sin estatus
            </a>
        </div>
    </div>

    <div class="col-md-6">
        <div class="shadow-sm bg-white w-100 mb-3 rounded">
            <div class="d-flex align-items-center">
                <div class="mr-auto p-2">
                    <div class="bg-success text-white pr-2 pl-2 pt-1 pb-1 w-100 rounded-circle">
                        <i class="fa fa-smile-o" aria-hidden="true"></i>
                    </div>
                </div>
                <div class="p-2 pt-3 pb-2 px-3">
                    <h6 class="text-muted">Total asignados con estatus</h6>
                    <h4 class="font-weight-bold text-success mb-0">
                        <?= $total_asignados_con_estatus; ?>
                    </h4>
                </div>
            </div>
        </div>
    </div>
    
    <div class="col-md-6">
        <div class="shadow-sm bg-white w-100 mb-3 rounded">
        <div class="d-flex align-items-center">
            <div class="mr-auto p-2">
                <div class="bg-dark text-white pr-2 pl-2 pt-1 pb-1 w-100 rounded-circle">
                    <i class="fa fa-plus" aria-hidden="true"></i>
                </div>
            </div>
            <div class="p-2 pt-3 pb-2 px-3">
                <h6 class="text-muted">Total delegados</h6>
                <h4 class="font-weight-bold text-dark mb-0">
                    <?= $total_delegados; ?>
                </h4>
            </div>
        </div>
        </div>
    </div>
</div>