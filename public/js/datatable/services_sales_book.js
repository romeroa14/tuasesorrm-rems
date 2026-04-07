 /* Formatting function for row details - modify as you need */
function format(d) {
    // `d` is the original data object for the row
    
    var name_lead = d.name_lead ? d.name_lead : '<span class="text-danger">No</span>';
    var external_customer = d.external_customer ? d.external_customer : '<span class="text-danger">No</span>';
    
    return (
        '<table cellpadding="5" cellspacing="0" border="0" style="padding-left:50px;">' +

            '<tr>' +
                '<td><strong>Cliente interno:</strong></td>' +
                '<td>' +
                name_lead +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Cliente externo:</strong></td>' +
                '<td>' +
                external_customer +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Teléfono del cliente:</strong></td>' +
                '<td>' +
                d.number_customer +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Monto final del negocio:</strong></td>' +
                '<td>$' +
                d.final_operation_amount +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Porcentaje de comisión:</strong></td>' +
                '<td>' +
                d.commission_percentage +
                '%</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Ganancia bruta:</strong></td>' +
                '<td>$' +
                d.commission_earned +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Historia:</strong></td>' +
                '<td>' +
                d.history +
                '</td>' +
            '</tr>' +

            '<tr>' +
                '<td><strong>Acción:</strong></td>' +
                '<td>' 
                    + '<a class="btn btn-info btn-sm m-1" href="/sale_consolidation/'+ d.id +
                    '" role="button">Consolidación'+
                    '</a>'+
                    '<a class="btn btn-primary btn-sm m-1" href="/edit_sale/'+ d.id +
                    '" role="button">Editar venta'+
                    '</a>'+
                '</td>' +
            '</tr>' +

        '</table>'

    );
}

$(document).ready(function () {
    var table = $('#macro_leads_table').DataTable({
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
        ajax: '/get_sales_book',
        columns: [
            {
                className: 'dt-control',
                orderable: false,
                data: null,
                defaultContent: '',
            },
            { data: 'id' },
            { 
                data: 'id_properties' ,
                render: function(data, type, row, meta) {
                    return (data) ? data : 'No';
                }
            },
            { 
                data: 'external_property' ,
                render: function(data, type, row, meta) {
                    return (data) ? data : 'No';
                }
            },
            { 
                data: 'u_captor' ,
                render: function(data, type, row, meta) {
                    return (data) ? data : 'No';
                }
            },
            { 
                data: 'u_closer' ,
                render: function(data, type, row, meta) {
                    return (data) ? data : 'No';
                }
            },
            { data: 'name_presalesstatus' },
            { 
                data: 'status_consolidated_sale' ,
                render: function(data, type, row, meta) {
                    return (data) ? data : 'Sin consolidar';
                }
            },
            { data: 'created_at' },
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
            
            data['u_closer'] ? '' : $(row).find('td:eq(5)').css('color', '#ff0854');
            
            data['u_captor'] ? '' : $(row).find('td:eq(4)').css('color', '#ff0854');
            
            data['id_properties'] ? '' : $(row).find('td:eq(2)').css('color', '#ff0854');
            
            data['external_property'] ? '' : $(row).find('td:eq(3)').css('color', '#ff0854');

            data['status_consolidated_sale'] == 'Consolidado' ? '' : $(row).find('td:eq(7)').css('color', '#ff0854');

        }
        
    });

    // Add event listener for opening and closing details
    $('#macro_leads_table tbody').on('click', 'td.dt-control', function () {
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