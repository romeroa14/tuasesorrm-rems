<!-- Modal Logout -->
                        <div class="modal fade" id="logoutModal" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabelLogout"
                        aria-hidden="true">
                            <div class="modal-dialog" role="document">
                                <div class="modal-content">
                                    <div class="modal-header">
                                        <h5 class="modal-title" id="exampleModalLabelLogout">¿Realmente deseas cerrar sesión?</h5>
                                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                                            <span aria-hidden="true">&times;</span>
                                        </button>
                                    </div>
                                    <div class="modal-body">
                                        <p>Estás a punto de cerrar sesión en este dispositivo, al hacerlo tus datos iguales se mantendrán seguros, espero vuelvas.</p>
                                    </div>
                                    <div class="modal-footer">
                                        <button type="button" class="btn btn-outline-dark" data-dismiss="modal">Permanecer en la cuenta</button>
                                        <a href="<?= base_url('/logout') ?>" class="btn btn-danger">Cerrar sesión</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                    <!---Container Fluid-->
                </div>
                <!-- Footer -->
                <footer class="sticky-footer bg-white mt-5">
                    <div class="container my-auto">
                        <div class="copyright text-center my-auto">
                            <span>copyright &copy; <script> document.write(new Date().getFullYear()); </script> - developed by
                                <b><a href="https://asesoresrm.com.ve/login" target="_blank">Asesores RM</a></b>
                            </span>
                        </div>
                    </div>
                </footer>
                <!-- Footer -->
            </div>
        </div>

        <!-- Scroll to top -->
        <a class="scroll-to-top rounded" href="#page-top">
            <i class="fas fa-angle-up"></i>
        </a>

        <script src="<?= base_url('vendor/bootstrap/js/bootstrap.bundle.min.js') ?>"></script>
        <script src="<?= base_url('vendor/jquery-easing/jquery.easing.min.js') ?>"></script>
        <!-- SweetAlert2 -->
        <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
        <!-- html2pdf.js -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/html2pdf.js/0.10.1/html2pdf.bundle.min.js"></script>
        <script src="<?= base_url('js/asynchronous/urls.js') ?>"></script>
        <script src="<?= base_url('js/ruang-admin.js') ?>"></script>
        <script src="<?= base_url('js/axios.min.js') ?>"></script>
        <script src="<?= base_url('js/imagepreview.js') ?>"></script>
        <script src="<?= base_url('js/endpoints.js') ?>"></script>

        <script src="<?= base_url('js/get_municipality_city.js') ?>"></script>
        <?php if(strpos($title, 'Catálogo inmobiliario') !== false): ?>
            <script src="<?= base_url('js/get_properties.js') ?>"></script>
        <?php endif; ?>
        <?php if(strpos($title, 'RM00') !== false): ?>
            <script src="<?= base_url('js/get_property_view.js') ?>"></script>
        <?php endif; ?>

        <!-- Select2 -->
        <script src="<?= base_url('vendor/select2/dist/js/select2.min.js') ?>"></script>

        <!-- DataTable -->
        <script src="<?= base_url('js/datatable/jquery.dataTables.min.js') ?>"></script>
        <script src="<?= base_url('js/datatable/config.dataTables.js') ?>"></script>
        <script src="<?= base_url('vendor/chart.js/Chart.min.js') ?>"></script>
        <?php if(strpos($title, 'Historial de acciones') !== false): ?>
            <script src="<?= base_url('js/activity_log.js') ?>"></script>
        <?php endif; ?>
        <?php if(strpos($title, 'Panel') !== false): ?>
            <script src="<?= base_url('js/demo/chart-area-demo.js') ?>"></script>
        <?php endif; ?>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/dataTables.buttons.min.js"></script>
        <script src="<?= base_url('js/get_wasi.js') ?>"></script>
        <?php if(isset($table_dinamic)): ?>
            <script src="js/datatable/<?= isset($table_dinamic) ? $table_dinamic : '' ?>.js"></script>
        <?php endif; ?>
        <?php if(strpos($title, 'Fichas de Comisiones') !== false || strpos($title, 'Gestionar Ficha de Comisión') !== false): ?>
            <script src="<?= base_url('js/commission_form.js') ?>"></script>
        <?php endif; ?>
        <?php if(strpos($title, 'Fichas de Comisiones') !== false): ?>
            <script src="<?= base_url('js/commission_pdf.js') ?>"></script>
        <?php endif; ?>
        <?php if(strpos($title, 'Aplicar Tabla de Actividades') !== false): ?>
            <script src="<?= base_url('js/activity_table.js') ?>"></script>
        <?php endif; ?>
        
        <!-- Librerías para exportación de DataTables -->
        <script src="https://cdnjs.cloudflare.com/ajax/libs/jszip/3.10.1/jszip.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/pdfmake.min.js"></script>
        <script src="https://cdnjs.cloudflare.com/ajax/libs/pdfmake/0.1.53/vfs_fonts.js"></script>
        <script src="https://cdn.datatables.net/buttons/2.4.1/js/buttons.html5.min.js"></script>
        <script>
            $(document).ready(function () {
                $('.select2-single').select2();
            });
        </script>
        <script>
            function getTextWidth(text, font) {
                var canvas = getTextWidth.canvas || (getTextWidth.canvas = document.createElement("canvas"));
                var context = canvas.getContext("2d");
                context.font = font;
                return context.measureText(text).width;
            }

            $(document).ready(function () {
                var funnelsOptions = [];
                var businessmodelOptions = [];
                var housingOptions = [];
                var dt;

                function loadTableData() {
                    $.ajax({
                        url: get_leads_atc,
                        method: "GET",
                        dataType: "json",
                        success: function (response) {
                            var tableData = [];
                            $.each(response.data, function (i, lead) {
                                tableData.push([
                                    lead.id,
                                    { funnels_name: lead.funnels_name, funnels_id: lead.funnels_id },
                                    { businessmodel_name: lead.businessmodel_name, businessmodel_id: lead.businessmodel_id },
                                    { housingtype_name: lead.housingtype_name, housingtype_id: lead.housingtype_id },
                                    lead.name,
                                    lead.phone,
                                    lead.observation,
                                    lead.created_at
                                ]);
                            });

                            if (dt) {
                                dt.clear().rows.add(tableData).draw();
                            } else {
                                dt = $('#leadsAtcTable').DataTable({
                                    data: tableData,
                                    columns: [
                                        { title: "ID" },
                                        {
                                            title: "Proviene",
                                            render: function (data) {
                                                var select = '<select data-field="funnels_id" data-default="' + data.funnels_id + '" style="font-size:10px; width:200px;" class="form-control form-control-sm">';
                                                for (var i = 0; i < funnelsOptions.length; i++) {
                                                    var selected = (funnelsOptions[i].id === data.funnels_id) ? 'selected' : '';
                                                    select += '<option value="' + funnelsOptions[i].id + '" ' + selected + '>' + funnelsOptions[i].name + '</option>';
                                                }
                                                select += '</select>';
                                                return select;
                                            }
                                        },
                                        {
                                            title: "Negocio",
                                            render: function (data) {
                                                var select = '<select data-field="businessmodel_id" data-default="' + data.businessmodel_id + '" style="font-size:10px; width:200px;" class="form-control form-control-sm">';
                                                for (var i = 0; i < businessmodelOptions.length; i++) {
                                                    var selected = (businessmodelOptions[i].id === data.businessmodel_id) ? 'selected' : '';
                                                    select += '<option value="' + businessmodelOptions[i].id + '" ' + selected + '>' + businessmodelOptions[i].name + '</option>';
                                                }
                                                select += '</select>';
                                                return select;
                                            }
                                        },
                                        {
                                            title: "Interés",
                                            render: function (data) {
                                                var select = '<select data-field="housingtype_id" data-default="' + data.housingtype_id + '" style="font-size:10px; width:200px;" class="form-control form-control-sm">';
                                                for (var i = 0; i < housingOptions.length; i++) {
                                                    var selected = (housingOptions[i].id === data.housingtype_id) ? 'selected' : '';
                                                    select += '<option value="' + housingOptions[i].id + '" ' + selected + '>' + housingOptions[i].name + '</option>';
                                                }
                                                select += '</select>';
                                                return select;
                                            }
                                        },
                                        {
                                            title: "Participante",
                                            render: function (data) {
                                                var font = "16px sans-serif";
                                                var width = Math.ceil(getTextWidth(data, font));
                                                return '<div class="input-group input-group-sm"><input type="text" data-field="name" data-default="' + data + '" class="form-control" value="' + data + '" style="font-size:10px; width:' + (width * 1.1) + 'px;"></div>';
                                            }
                                        },
                                        {
                                            title: "Teléfono",
                                            render: function (data) {
                                                var font = "16px sans-serif";
                                                var width = Math.ceil(getTextWidth(data, font));
                                                return '<div class="input-group input-group-sm"><input type="text" data-field="phone" data-default="' + data + '" class="form-control" value="' + data + '" style="font-size:10px; width:' + (width * 1.1) + 'px;"></div>';
                                            }
                                        },
                                        {
                                            title: "Observación",
                                            render: function (data) {
                                                return '<div class="form-group"><textarea data-field="observation" data-default="' + data + '" class="form-control" rows="1" style="font-size:10px; width:250px; height:26.5px;">' + data + '</textarea></div>';
                                            }
                                        },
                                        { title: "Fecha de registro" }
                                    ]
                                });
                            }

                            $('#leadsAtcTable').off('change').on('change', 'input, select, textarea', function () {
                                var $row = $(this).closest('tr');
                                var rowData = dt.row($row).data();
                                var updateData = {};
                                updateData.id_lead_atc = rowData[0];

                                $row.find('input[data-field], select[data-field], textarea[data-field]').each(function () {
                                    var field = $(this).data("field");
                                    var value = $(this).val();
                                    if (!value) {
                                        value = $(this).data("default");
                                    }
                                    updateData[field] = value;
                                });

                                $.ajax({
                                    url: update_lead_atc,
                                    method: "POST",
                                    data: updateData,
                                    dataType: "json",
                                    success: function (response) {
                                        var alertClass = (response.status === "success") ? "bg-success" : "bg-danger";
                                        var alertHtml = '<div class="alert ' + alertClass + ' alert-dismissible fade show" role="alert">' +
                                            response.message +
                                            '<button type="button" class="close" data-dismiss="alert" aria-label="Close"><span aria-hidden="true">×</span></button>' +
                                            '</div>';
                                        $("#contentAlertsScript").html(alertHtml);
                                        loadTableData();
                                    },
                                    error: function () {
                                        loadTableData();
                                    }
                                });
                            });
                        }
                    });
                }
                
                $.ajax({
                    url: get_funnels,
                    method: "GET",
                    dataType: "json",
                    success: function (funnelsResponse) {
                        funnelsOptions = funnelsResponse.data;
                        $.ajax({
                            url: get_businessmodel,
                            method: "GET",
                            dataType: "json",
                            success: function (businessmodelResponse) {
                                businessmodelOptions = businessmodelResponse.data;
                                $.ajax({
                                    url: get_housingtype,
                                    method: "GET",
                                    dataType: "json",
                                    success: function (housingResponse) {
                                        housingOptions = housingResponse.data;
                                        loadTableData();
                                    }
                                });
                            }
                        });
                    }
                });
            });
        </script>
    </body>
</html>