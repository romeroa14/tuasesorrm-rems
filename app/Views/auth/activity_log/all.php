<style>
    #detailsModal .table td {
        padding: 0.25rem 0.5rem;
    }

    #detailsModal pre {
        font-size: 0.8rem;
        max-height: 150px;
        overflow-y: auto;
    }

    #detailsModal code {
        font-size: 0.85rem;
    }

    #detailsModal .modal-body {
        max-height: 70vh;
        overflow-y: auto;
    }
</style>
<div class="container-fluid">
    <!-- Estadísticas del Activity Log -->
    <div class="row mt-0">
        <div class="col-md-12">
            <h4>
                <i class="fas fa-chart-line"></i> Estadísticas de Actividad
            </h4>
            <div class="dropdown-divider pt-2 pb-2"></div>
        </div>
        <div class="col-md-3 pt-3">
            <div class="card h-100">
                <div class="card-body">
                    <div class="row no-gutters align-items-center">
                        <div class="col mr-2">
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Visualizaciones</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="view-count">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-eye fa-2x text-primary"></i>
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
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Inicios de Sesión</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="login-count">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-sign-in-alt fa-2x text-success"></i>
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
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Actualizaciones</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="update-count">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-edit fa-2x text-warning"></i>
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
                            <div class="text-xs font-weight-bold text-uppercase mb-1">Total Actividades</div>
                            <div class="h5 mb-0 font-weight-bold text-gray-800" id="total-count">-</div>
                        </div>
                        <div class="col-auto">
                            <i class="fas fa-chart-bar fa-2x text-dark"></i>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="row mt-5">
        <div class="col-md-12">
            <h4>
                <i class="fas fa-history"></i> Historial de Actividades
            </h4>
            <div class="dropdown-divider pt-2 pb-2"></div>
        </div>
        <div class="col-md-12">
            <div class="card">
                <div class="card-header">
                    <div class="row">
                        <div class="col-md-3">
                            <label for="date-from">Fecha Desde:</label>
                            <input type="date" id="date-from" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label for="date-to">Fecha Hasta:</label>
                            <input type="date" id="date-to" class="form-control form-control-sm">
                        </div>
                        <div class="col-md-3">
                            <label for="action-filter">Tipo de Acción:</label>
                            <select id="action-filter" class="form-control form-control-sm">
                                <option value="">Todas las acciones</option>
                                <option value="view">Visualización</option>
                                <option value="login">Inicio de Sesión</option>
                                <option value="logout">Cierre de Sesión</option>
                                <option value="update">Actualización</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="user-filter">Usuario:</label>
                            <select id="user-filter" class="form-control form-control-sm">
                                <option value="">Todos los usuarios</option>
                            </select>
                        </div>
                    </div>
                    <div class="row mt-2">
                        <div class="col-md-12">
                            <button id="apply-filters" class="btn btn-primary btn-sm">
                                <i class="fas fa-filter"></i> Aplicar Filtros
                            </button>
                            <button id="clear-filters" class="btn btn-secondary btn-sm">
                                <i class="fas fa-times"></i> Limpiar
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table id="activity-table" class="table table-striped table-bordered" style="width:100%">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>Usuario</th>
                                    <th>Página Visitada</th>
                                    <th>Acción</th>
                                    <th>Fecha/Hora</th>
                                    <th>IP</th>
                                    <th>Dispositivo</th>
                                    <th>Navegador</th>
                                    <th>Detalles</th>
                                </tr>
                            </thead>
                            <tbody>
                                <!-- Los datos se cargarán dinámicamente -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Modal para mostrar detalles -->
<div class="modal fade" id="detailsModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document"> <!-- Cambiado a modal-xl para más espacio -->
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title">Detalles de la Actividad</h5>
                <button type="button" class="close text-white" data-dismiss="modal">
                    <span>&times;</span>
                </button>
            </div>
            <div class="modal-body" id="modal-body-content">
                <!-- Contenido dinámico -->
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-dismiss="modal">
                    <i class="fas fa-times"></i> Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
