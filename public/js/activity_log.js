$(document).ready(function() {
    // Configuración base de URLs - usar base_url dinámico o ruta relativa
    const BASE_URL = ''; // Vacío usa ruta relativa del dominio actual
    const API_ENDPOINTS = {
        all: `${BASE_URL}/app/activity_log/api/all`,
        stats: `${BASE_URL}/app/activity_log/api/stats`,
        user: `${BASE_URL}/app/activity_log/api/user`
    };

    let activityTable;

    // Inicializar DataTable
    function initializeDataTable() {
        activityTable = $('#activity-table').DataTable({
            processing: true,
            serverSide: false,
            responsive: true,
            pageLength: 25,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, "Todos"]],
            order: [[4, 'desc']], // Ordenar por fecha descendente
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: '<i class="fas fa-file-excel"></i> Excel',
                    className: 'btn btn-success btn-sm'
                },
                {
                    extend: 'csv',
                    text: '<i class="fas fa-file-csv"></i> CSV',
                    className: 'btn btn-info btn-sm'
                }
            ],
            language: {
                url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'
            },
            columnDefs: [
                {
                    targets: [0], // ID column
                    visible: false
                },
                {
                    targets: [2], // Página visitada
                    render: function(data, type, row) {
                        if (type === 'display' && data.length > 50) {
                            return '<span title="' + data + '">' + data.substr(0, 50) + '...</span>';
                        }
                        return data;
                    }
                },
                {
                    targets: [3], // Acción
                    render: function(data, type, row) {
                        const badges = {
                            'view': '<span class="badge badge-primary">Visualización</span>',
                            'login': '<span class="badge badge-success">Inicio Sesión</span>',
                            'logout': '<span class="badge badge-warning">Cierre Sesión</span>',
                            'update': '<span class="badge badge-info">Actualización</span>',
                            'create': '<span class="badge badge-success">Creación</span>',
                            'delete': '<span class="badge badge-danger">Eliminación</span>'
                        };
                        return badges[data] || `<span class="badge badge-secondary">${data}</span>`;
                    }
                },
                {
                    targets: [4], // Fecha
                    render: function(data, type, row) {
                        if (type === 'display') {
                            const date = new Date(data);
                            return date.toLocaleString('es-ES');
                        }
                        return data;
                    }
                },
                {
                    targets: [8], // Detalles
                    render: function(data, type, row) {
                        return `<button class="btn btn-sm btn-outline-primary view-details" data-id="${row[0]}">
                                    <i class="fas fa-eye"></i> Ver
                                </button>`;
                    },
                    orderable: false
                }
            ]
        });
    }

    // Cargar estadísticas
    function loadStats() {
        $.ajax({
            url: API_ENDPOINTS.stats,
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const stats = response.data.activity_stats;
                    let viewCount = 0, loginCount = 0, updateCount = 0, totalCount = 0;

                    stats.forEach(stat => {
                        totalCount += parseInt(stat.count);
                        switch(stat.action_type) {
                            case 'view':
                                viewCount = stat.count;
                                break;
                            case 'login':
                                loginCount = stat.count;
                                break;
                            case 'update':
                                updateCount = stat.count;
                                break;
                        }
                    });

                    $('#view-count').text(viewCount.toLocaleString());
                    $('#login-count').text(loginCount.toLocaleString());
                    $('#update-count').text(updateCount.toLocaleString());
                    $('#total-count').text(totalCount.toLocaleString());

                    // Cargar usuarios más activos en el filtro
                    const activeUsers = response.data.most_active_users;
                    const userSelect = $('#user-filter');
                    activeUsers.forEach(user => {
                        userSelect.append(`<option value="${user.user_id}">${user.user_name}</option>`);
                    });
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading stats:', error);
                showAlert('Error al cargar las estadísticas', 'danger');
            }
        });
    }

    // Cargar datos de actividades
    function loadActivities(filters = {}) {
        const params = new URLSearchParams();
        
        if (filters.search) params.append('search', filters.search);
        if (filters.start_date) params.append('start_date', filters.start_date);
        if (filters.end_date) params.append('end_date', filters.end_date);
        if (filters.action_type) params.append('action_type', filters.action_type);
        if (filters.user_id) params.append('user_id', filters.user_id);
        
        params.append('limit', 1000); // Cargar más registros
        params.append('sort_by', 'timestamp');
        params.append('sort_order', 'DESC');

        const url = filters.user_id ? 
            `${API_ENDPOINTS.user}/${filters.user_id}?${params.toString()}` : 
            `${API_ENDPOINTS.all}?${params.toString()}`;

        $.ajax({
            url: url,
            method: 'GET',
            success: function(response) {
                if (response.status === 'success') {
                    const data = response.data.map(activity => [
                        activity.id,
                        activity.user_name,
                        activity.page_visited,
                        activity.action_type,
                        activity.timestamp,
                        activity.ip_address,
                        activity.device_type,
                        activity.browser_info,
                        '' // Columna de detalles (botón)
                    ]);

                    activityTable.clear().rows.add(data).draw();
                } else {
                    showAlert('Error al cargar las actividades: ' + response.message, 'danger');
                }
            },
            error: function(xhr, status, error) {
                console.error('Error loading activities:', error);
                showAlert('Error al cargar las actividades', 'danger');
            }
        });
    }

    // Mostrar detalles de actividad
function showActivityDetails(activityId) {
    // Buscar la actividad en los datos actuales de la tabla
    const allData = activityTable.rows().data().toArray();
    const rowData = allData.find(row => row[0] == activityId);
    
    if (!rowData) {
        showAlert('No se encontraron detalles para esta actividad', 'warning');
        return;
    }

    // Hacer consulta para obtener el registro completo con todos los campos
    $.ajax({
        url: `${API_ENDPOINTS.all}?limit=1000`,
        method: 'GET',
        success: function(response) {
            if (response.status === 'success') {
                const activity = response.data.find(a => a.id == activityId);
                
                if (activity) {
                    // Procesar información adicional
                    let additionalInfo = {};
                    try {
                        additionalInfo = JSON.parse(activity.additional_info || '{}');
                    } catch (e) {
                        additionalInfo = {};
                    }

                    // Procesar datos previos y nuevos
                    let previousData = '';
                    let newData = '';
                    
                    try {
                        if (activity.previous_data) {
                            const prevData = JSON.parse(activity.previous_data);
                            previousData = `<pre class="bg-light p-2 rounded"><code>${JSON.stringify(prevData, null, 2)}</code></pre>`;
                        } else {
                            previousData = '<span class="text-muted">Sin datos previos</span>';
                        }
                    } catch (e) {
                        previousData = '<span class="text-muted">Datos previos no válidos</span>';
                    }

                    try {
                        if (activity.new_data) {
                            const newDataObj = JSON.parse(activity.new_data);
                            newData = `<pre class="bg-light p-2 rounded"><code>${JSON.stringify(newDataObj, null, 2)}</code></pre>`;
                        } else {
                            newData = '<span class="text-muted">Sin Datos Enviados</span>';
                        }
                    } catch (e) {
                        newData = '<span class="text-muted">Datos Enviados no válidos</span>';
                    }

                    // Formatear fecha
                    const formattedDate = new Date(activity.timestamp).toLocaleString('es-ES', {
                        year: 'numeric',
                        month: '2-digit',
                        day: '2-digit',
                        hour: '2-digit',
                        minute: '2-digit',
                        second: '2-digit'
                    });

                    // Crear contenido del modal
                    const modalContent = `
                        <div class="container-fluid">
                            <!-- Información Principal -->
                            <div class="row mb-3">
                                <div class="col-md-6">
                                    <h6 class="text-primary"><i class="fas fa-info-circle"></i> Información General</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>ID:</strong></td>
                                            <td>${activity.id}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Usuario:</strong></td>
                                            <td>${activity.user_name} (ID: ${activity.user_id})</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Acción:</strong></td>
                                            <td>
                                                ${getActionBadge(activity.action_type)}
                                            </td>
                                        </tr>
                                        <tr>
                                            <td><strong>Página visitada:</strong></td>
                                            <td><code>${activity.page_visited}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Timestamp:</strong></td>
                                            <td>${formattedDate}</td>
                                        </tr>
                                    </table>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success"><i class="fas fa-desktop"></i> Información Técnica</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td><strong>IP:</strong></td>
                                            <td><code>${activity.ip_address}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Dispositivo:</strong></td>
                                            <td>${activity.device_type}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Navegador:</strong></td>
                                            <td>${activity.browser_info}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Sesión ID:</strong></td>
                                            <td><code>${activity.session_id}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Tabla afectada:</strong></td>
                                            <td>${activity.affected_table || '<span class="text-muted">N/A</span>'}</td>
                                        </tr>
                                        <tr>
                                            <td><strong>Registro afectado:</strong></td>
                                            <td>${activity.affected_record_id || '<span class="text-muted">N/A</span>'}</td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Información Adicional del Request -->
                            <div class="row mb-3">
                                <div class="col-md-12">
                                    <h6 class="text-info"><i class="fas fa-globe"></i> Detalles de la Petición</h6>
                                    <table class="table table-sm table-borderless">
                                        <tr>
                                            <td style="width: 150px;"><strong>User Agent:</strong></td>
                                            <td><small>${additionalInfo.user_agent || 'N/A'}</small></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Referer:</strong></td>
                                            <td><code>${additionalInfo.referer || 'N/A'}</code></td>
                                        </tr>
                                        <tr>
                                            <td><strong>Método HTTP:</strong></td>
                                            <td><span class="badge badge-secondary">${additionalInfo.method || 'N/A'}</span></td>
                                        </tr>
                                        <tr>
                                            <td><strong>URL Completa:</strong></td>
                                            <td><code>${additionalInfo.url || 'N/A'}</code></td>
                                        </tr>
                                    </table>
                                </div>
                            </div>

                            <!-- Datos Previos y Nuevos -->
                            ${(activity.previous_data || activity.new_data) ? `
                            <div class="row">
                                <div class="col-md-6">
                                    <h6 class="text-warning"><i class="fas fa-history"></i> Datos Previos</h6>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        ${previousData}
                                    </div>
                                </div>
                                <div class="col-md-6">
                                    <h6 class="text-success"><i class="fas fa-plus-circle"></i> Datos Enviados</h6>
                                    <div style="max-height: 200px; overflow-y: auto;">
                                        ${newData}
                                    </div>
                                </div>
                            </div>
                            ` : ''}
                        </div>
                    `;

                    // Actualizar título del modal
                    $('#detailsModal .modal-title').html(`
                        <i class="fas fa-search"></i> Detalles de Auditoría - ID: ${activity.id}
                    `);

                    // Mostrar contenido en el modal
                    $('#modal-body-content').html(modalContent);
                    $('#detailsModal').modal('show');
                } else {
                    showAlert('No se encontró la actividad solicitada', 'warning');
                }
            } else {
                showAlert('Error al cargar los detalles: ' + response.message, 'danger');
            }
        },
        error: function(xhr, status, error) {
            console.error('Error loading activity details:', error);
            showAlert('Error al cargar los detalles de la actividad', 'danger');
        }
    });
}

// Función auxiliar para obtener el badge de acción
function getActionBadge(actionType) {
    const badges = {
        'view': '<span class="badge badge-primary"><i class="fas fa-eye"></i> Visualización</span>',
        'login': '<span class="badge badge-success"><i class="fas fa-sign-in-alt"></i> Inicio Sesión</span>',
        'logout': '<span class="badge badge-warning"><i class="fas fa-sign-out-alt"></i> Cierre Sesión</span>',
        'update': '<span class="badge badge-info"><i class="fas fa-edit"></i> Actualización</span>',
        'create': '<span class="badge badge-success"><i class="fas fa-plus"></i> Creación</span>',
        'delete': '<span class="badge badge-danger"><i class="fas fa-trash"></i> Eliminación</span>'
    };
    return badges[actionType] || `<span class="badge badge-secondary">${actionType}</span>`;
}


    // Función para mostrar alertas
    function showAlert(message, type = 'info') {
        const alertHtml = `
            <div class="alert alert-${type} alert-dismissible fade show" role="alert">
                ${message}
                <button type="button" class="close" data-dismiss="alert">
                    <span>&times;</span>
                </button>
            </div>
        `;
        
        // Insertar la alerta al principio del contenedor
        $('.container-fluid').prepend(alertHtml);
        
        // Auto-remover después de 5 segundos
        setTimeout(() => {
            $('.alert').fadeOut();
        }, 5000);
    }

    // Event Listeners
    $('#apply-filters').click(function() {
        const filters = {
            start_date: $('#date-from').val(),
            end_date: $('#date-to').val(),
            action_type: $('#action-filter').val(),
            user_id: $('#user-filter').val()
        };
        
        loadActivities(filters);
    });

    $('#clear-filters').click(function() {
        $('#date-from, #date-to').val('');
        $('#action-filter, #user-filter').val('');
        loadActivities();
    });

    // Event listener para botones de detalles
    $(document).on('click', '.view-details', function() {
        const activityId = $(this).data('id');
        showActivityDetails(activityId);
    });

    // Inicialización
    initializeDataTable();
    loadStats();
    loadActivities();

    // Establecer fechas por defecto (último mes)
    const today = new Date();
    const lastMonth = new Date(today.getFullYear(), today.getMonth() - 1, today.getDate());
    
    $('#date-from').val(lastMonth.toISOString().split('T')[0]);
    $('#date-to').val(today.toISOString().split('T')[0]);
})