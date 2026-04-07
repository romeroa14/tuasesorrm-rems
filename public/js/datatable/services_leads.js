 /* Formatting function for row details - modify as you need */
function format(d) {
    // `d` is the original data object for the row

    var name_users_delegation = d.name_users_delegation ? d.name_users_delegation : '<span class="text-danger">Sin delegado</span>';

    var name_assignedclients_assigned_client = d.name_assignedclients_assigned_client ? d.name_assignedclients_assigned_client : '<span class="text-danger">Sin agente</span>';

    var name_tracking_assigned_client = d.name_tracking_assigned_client ? d.name_tracking_assigned_client : '<span class="text-danger">Sin estatus</span>';

    var observatio_assigned_client = d.observatio_assigned_client ? d.observatio_assigned_client : '<span class="text-danger">Sin observación</span>';

    var step_step = d.name_users_delegation ? d.name_assignedclients_assigned_client ? d.name_tracking_assigned_client ? 'Cliente atendido' : '<span class="text-dark">Asignado pero el agente no ha interactuado con el cliente</span>' :'<span class="text-dark">Delegado pero sin asignar agente</span>' : '<span class="text-dark">No se encuentra delegado el lead</span>';
    
    return (
        '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +

            '<tr>' +
                '<td><strong>Teléfono lead:</strong></td>' +
                '<td>' +
                d.phone_lead +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Cédula lead:</strong></td>' +
                '<td>' +
                d.writ_identity_lead +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Correo electrónico lead:</strong></td>' +
                '<td>' +
                d.email_lead +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Rango de precio lead:</strong></td>' +
                '<td>' +
                d.price_range_lead +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Usuario ATC:</strong></td>' +
                '<td>' +
                d.name_user_lead +
                '</td>' +
            '</tr>' +



            '<tr>' +
                '<td><strong>Estatus ATC:</strong></td>' +
                '<td>' +
                d.name_trackingstatus_lead +
                '</td>' +
            '</tr>' +


            '<tr>' +
                '<td><strong>Observación ATC:</strong></td>' +
                '<td>' +
                d.observation_lead +
                '</td>' +
            '</tr>' +


            '<tr>' +
                '<td><strong>Delegado:</strong></td>' +
                '<td>' +
                name_users_delegation +
                '</td>' +
            '</tr>' +


            '<tr>' +
                '<td><strong>Agente:</strong></td>' +
                '<td>' +
                name_assignedclients_assigned_client +
                '</td>' +
            '</tr>' +

            
            '<tr>' +
                '<td><strong>Estatus agente:</strong></td>' +
                '<td>' +
                name_tracking_assigned_client +
                '</td>' +
            '</tr>' +


            '<tr>' +
                '<td><strong>Observación agente:</strong></td>' +
                '<td>' +
                observatio_assigned_client +
                '</td>' +
            '</tr>' +


            '<tr>' +
                '<td><strong>Resumen de seguimiento:</strong></td>' +
                '<td>' +
                step_step +
                '</td>' +
            '</tr>' +


            '<tr>' +
                '<td><strong>Acción:</strong></td>' +
                '<td>' +
                    '<a class="btn btn-primary btn-sm" href="/leads/edit_lead/'+
                    d.id_lead +
                    '" role="button">Editar'+
                    '</a>'+
                '</td>' +
            '</tr>' +
        '</table>'
    );
}

$(document).ready(function () {
    var table = $('#tracing_leads_table').DataTable({
        pageLength : 100,
        scrollX: true,
        scroller: {
            rowHeight: 60
        },
        language: {
            "decimal": "",
            "emptyTable": "No hay información",
            "info": "Registros _END_ de _TOTAL_",
            "infoEmpty": "Mostrando 0 to 0 of 0 Entradas",
            "infoFiltered": "(Filtrado de _MAX_ total entradas)",
            "infoPostFix": "",
            "thousands": ",",
            "lengthMenu": "Mostrar _MENU_ Registros",
            "loadingRecords": "Cargando...",
            "processing": "Procesando...",
            "search": "Buscar:",
            "zeroRecords": "Sin resultados encontrados",
            "paginate": {
                "first": "Primero",
                "last": "Ultimo",
                "next": "Siguiente",
                "previous": "Anterior"
            } 
        },
        ajax: '/get_leads',
        columns: [
            {
                className: 'dt-control',
                orderable: false,
                data: null,
                defaultContent: '',
            },
            { data: 'id_lead' },
            { data: 'full_name_lead' },
            { data: 'name_funnel_lead' },
            { data: 'name_housingtype_lead' },
            { data: 'observation_lead' },
            { data: 'name_businessmodel_lead' },
            { data: 'created_at_lead' },
            { data: 'time_elapsed_lead' },
        ],
        order: [[1, 'asc']],

        createdRow: function(row, data, dataIndex) {
            
            function day_difference(fecha) {
                // Crear objetos Date a partir de las fechas y horas
                var fechaFin = new Date();

                // Obtener la diferencia en milisegundos
                var diferencia_actual = fechaFin.getTime() - new Date(fecha);

                // Convertir los milisegundos a días, horas, minutos y segundos
                var segundos = Math.floor(diferencia_actual / 1000);
                var minutos = Math.floor(segundos / 60);
                var horas = Math.floor(minutos / 60);
                var dias = Math.floor(horas / 24);

                return dias
            }
            
            if (day_difference(data['created_at_lead']) >= 2) {
                if (data['name_tracking_assigned_client']) {
                    $(row).find('td:eq(8)').css('background-color', '#28a745').css('color', '#fff');
                }else{
                    $(row).find('td:eq(8)').css('background-color', '#dc3545').css('color', '#fff');
                }
            }
            else {
                $(row).find('td:eq(8)').css('background-color', '#28a745').css('color', '#fff');
            }
        }
        
    });

    // Add event listener for opening and closing details
    $('#tracing_leads_table tbody').on('click', 'td.dt-control', function () {
        var tr = $(this).closest('tr');
        var row = table.row(tr);

        if (row.child.isShown()) {
            // This row is already open - close it
            row.child.hide();
            tr.removeClass('shown');
        } else {
            // Open this row
            row.child(format(row.data())).show();
            tr.addClass('shown');
        }
    });
});