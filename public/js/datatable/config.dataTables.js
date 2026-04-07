function initDataTable(data){
    // Obtener la cadena de consulta de la URL
    var queryString = window.location.search;
    var urlParams = new URLSearchParams(queryString);
    var dt_filter = urlParams.get('dt_filter');
    var name_table = '#' + data;
    
    $(document).ready(function () {
        $(name_table).DataTable({
            initComplete: function () {
                this.api()
                    .columns()
                    .every(function () {
                        let column = this;
                        let title = column.header().textContent;
                        let is_filter = column.header().getAttribute("is_filtrable");
                        
                        if (is_filter) {
                            // Create input element
                            let input = document.createElement('input');
                            input.placeholder = title.trim();
                            input.className = 'datatable_filtrable_input';

                            for (const [key, value] of urlParams) {
                                if (title === key) {
                                    input.value = value;
                                    column.search(input.value).draw();
                                }
                            }

                            column.header().replaceChildren(input);
            
                            // Event listener for user input
                            input.addEventListener('keyup', () => {
                                if (column.search() !== this.value) {
                                    column.search(input.value).draw();
                                }
                            });
                        }
                    });
            },
            pageLength: 100,
            dom: 'Bfrtip',
            buttons: [
                {
                    extend: 'excel',
                    text: 'Excel',
                    exportOptions: {
                        columns: ':visible:not(:last-child)', // Excluye la última columna (acciones)
                        format: {
                            body: function(data, row, column, node) {
                                // Limpia el HTML y devuelve solo texto
                                return $(data).text() || data;
                            }
                        }
                    }
                },
                {
                    extend: 'pdf',
                    text: 'PDF',
                    exportOptions: {
                        columns: ':visible:not(:last-child)', // Excluye la última columna (acciones)
                        format: {
                            body: function(data, row, column, node) {
                                // Limpia el HTML y devuelve solo texto
                                return $(data).text() || data;
                            }
                        }
                    },
                    customize: function(doc) {
                        // Personalización del PDF
                        doc.defaultStyle.fontSize = 8;
                        doc.styles.tableHeader.fontSize = 9;
                        doc.styles.tableHeader.fillColor = '#2c3e50';
                        doc.styles.tableHeader.color = 'white';
                        
                        // Ajustar ancho de columnas automáticamente
                        if (doc.content[1].table && doc.content[1].table.body) {
                            var colCount = doc.content[1].table.body[0].length;
                            doc.content[1].table.widths = Array(colCount).fill('*');
                        }
                        
                        // Configurar orientación según número de columnas
                        if (colCount > 6) {
                            doc.pageOrientation = 'landscape';
                        }
                    }
                }
            ],
            scrollX: true,
            scroller: {
                rowHeight: 60
            },
            search: {"search": (dt_filter) ? dt_filter : ''},
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
            }
        });

        // Personalizar estilos de los botones después de que se creen
        setTimeout(function() {
            // Botón Excel
            var excelButton = document.querySelector('.buttons-excel');
            if (excelButton) {
                excelButton.classList.add('btn', 'btn-success', 'btn-sm', 'p');
                excelButton.style.width = '6%';
                excelButton.style.borderRadius = '25px';
                excelButton.style.marginRight = '10px';
                var excelSpan = excelButton.querySelector('span');
                if (excelSpan) {
                    excelSpan.classList.add('p');
                    excelSpan.innerHTML = 'Excel';
                }
            }

            // Botón PDF
            var pdfButton = document.querySelector('.buttons-pdf');
            if (pdfButton) {
                pdfButton.classList.add('btn', 'btn-danger', 'btn-sm', 'p');
                pdfButton.style.width = '6%';
                pdfButton.style.borderRadius = '25px';
                var pdfSpan = pdfButton.querySelector('span');
                if (pdfSpan) {
                    pdfSpan.classList.add('p');
                    pdfSpan.innerHTML = 'PDF';
                }
            }
        }, 100);
    });
}
