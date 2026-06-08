<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-receipt text-primary"></i> Gastos</h1>
        <?php if (empty($can_write_legacy)): ?>
        <span class="badge badge-warning text-wrap p-2">Modo solo lectura mientras se reemplaza el libro legacy.</span>
        <?php else: ?>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nuevo Gasto</button>
        <?php endif; ?>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Listado de Gastos</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light"><tr><th>ID</th><th>Título</th><th>Tipo Gasto</th><th>Monto USD</th><th>Destinatario</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="financeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-xl" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white">
                <h5 class="modal-title" id="modalTitle"><i class="fas fa-receipt"></i> Gasto</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <input type="hidden" name="user_id" value="<?= session()->get('id') ?>">
                    <input type="hidden" name="created_by" value="<?= session()->get('id') ?>">
                    <input type="hidden" name="currency_id" value="1">
                    <input type="hidden" name="amount" id="amount" value="0">

                    <!-- Section: Info General -->
                    <div class="card bg-light mb-3">
                        <div class="card-header py-2"><i class="fas fa-info-circle text-primary"></i> <strong>Información General</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-heading text-muted"></i> Título <span class="text-danger">*</span></label><input type="text" class="form-control" id="title" name="title" required placeholder="Ej: Compra de materiales"></div></div>
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-tag text-muted"></i> Tipo de Gasto <span class="text-danger">*</span></label><select class="form-control select2-finance" id="expense_type_id" name="expense_type_id" required><option value="">Cargando...</option></select></div></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-building text-muted"></i> Empresa</label><select class="form-control select2-finance" id="company_id" name="company_id"><option value="">Seleccionar...</option></select></div></div>
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-sitemap text-muted"></i> Departamento</label><select class="form-control select2-finance" id="department_id" name="department_id"><option value="">Seleccionar...</option></select></div></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-tasks text-muted"></i> Proyecto</label><select class="form-control select2-finance" id="project_id" name="project_id"><option value="">Seleccionar...</option></select></div></div>
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-tags text-muted"></i> Categoría <span class="text-danger">*</span></label><select class="form-control select2-finance" id="category_id" name="category_id" required><option value="">Cargando...</option></select></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Financiero -->
                    <div class="card bg-light mb-3">
                        <div class="card-header py-2"><i class="fas fa-dollar-sign text-primary"></i> <strong>Información Financiera</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-4"><div class="form-group"><label><i class="fas fa-money-bill-wave text-muted"></i> Monto USD <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="amount_usd" name="amount_usd" required min="0" onchange="document.getElementById('amount').value=this.value" placeholder="0.00"></div></div>
                                <div class="col-md-4"><div class="form-group"><label><i class="fas fa-percentage text-muted"></i> IVA</label><input type="number" step="0.01" class="form-control" id="tax_amount_usd" name="tax_amount_usd" value="0" min="0" placeholder="0.00"></div></div>
                                <div class="col-md-4"><div class="form-group"><label><i class="fas fa-exchange-alt text-muted"></i> Moneda Origen</label><input type="text" class="form-control" id="original_currency" name="original_currency" placeholder="USD/VES"></div></div>
                            </div>
                            <div class="row">
                                <div class="col-md-4"><div class="form-group"><label><i class="fas fa-credit-card text-muted"></i> Forma de Pago <span class="text-danger">*</span></label><select class="form-control select2-finance" id="payment_type_id" name="payment_type_id" required><option value="">Cargando...</option></select></div></div>
                                <div class="col-md-4"><div class="form-group"><label><i class="fas fa-calendar text-muted"></i> Fecha <span class="text-danger">*</span></label><input type="date" class="form-control" id="expense_date" name="expense_date" required></div></div>
                                <div class="col-md-4"><div class="form-group"><label><i class="fas fa-flag text-muted"></i> Prioridad</label><select class="form-control" id="priority" name="priority"><option value="medium">Media</option><option value="low">Baja</option><option value="high">Alta</option></select></div></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-check-circle text-muted"></i> Estado</label><select class="form-control" id="status" name="status"><option value="pending">Pendiente</option><option value="approved">Aprobado</option><option value="rejected">Rechazado</option></select></div></div>
                            </div>
                        </div>
                    </div>

                    <!-- Section: Proveedor -->
                    <div class="card bg-light mb-3">
                        <div class="card-header py-2"><i class="fas fa-truck text-primary"></i> <strong>Proveedor / Destinatario</strong></div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6"><div class="form-group">
                                    <label><i class="fas fa-user text-muted"></i> Destinatario del Gasto</label>
                                    <input type="text" class="form-control" id="recipient" name="recipient" placeholder="Supermercado XYZ, Juan Pérez..." list="recipient-list">
                                    <datalist id="recipient-list"></datalist>
                                    <small class="text-muted">👤 Escribe para buscar o crear nuevo</small>
                                </div></div>
                                <div class="col-md-6"><div class="form-group">
                                    <label><i class="fas fa-building text-muted"></i> Proveedor</label>
                                    <input type="text" class="form-control" id="provider" name="provider" placeholder="Nombre del proveedor" list="provider-list">
                                    <datalist id="provider-list"></datalist>
                                </div></div>
                            </div>
                            <div class="row">
                                <div class="col-md-6"><div class="form-group"><label><i class="fas fa-file-invoice text-muted"></i> Nro. Factura</label><input type="text" class="form-control" id="invoice_number" name="invoice_number" placeholder="FAC-001"></div></div>
                            </div>
                            <div class="form-group"><label><i class="fas fa-comment text-muted"></i> Descripción</label><textarea class="form-control" id="description" name="description" rows="2" placeholder="Detalle del gasto..."></textarea></div>
                        </div>
                    </div>

                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
var dt, apiBase = '<?= base_url('/app/finance/api/expenses') ?>', editId = null;
var canWriteLegacy = <?= !empty($can_write_legacy) ? 'true' : 'false' ?>;
function loadTable() {
    $.post(apiBase, function(r) {
        var rows = [];
        if (r.status==='success' && Array.isArray(r.data)) r.data.forEach(function(d) {
            var statLbl = {pending:'Pendiente',approved:'Aprobado',rejected:'Rechazado'};
            var statCls = {pending:'secondary',approved:'primary',rejected:'danger'};
            var priLbl = {low:'Baja',medium:'Media',high:'Alta'};
            var actions = canWriteLegacy
                ? '<button class="btn btn-info btn-sm mr-1" onclick="edit('+d.id+')"><i class="fas fa-edit"></i></button>'+
                  '<button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>'
                : '—';
            rows.push([d.id, d.title||'—', d.expense_type_id||'—',
                '$'+parseFloat(d.amount_usd||0).toFixed(2),
                d.recipient||'—',
                '<span class="badge badge-'+statCls[d.status]+'">'+(statLbl[d.status]||d.status)+'</span>',
                d.expense_date||'—',
                actions]);
        });
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#dataTable').DataTable({
            data:rows,
            columns: [
                {title:'ID'},{title:'Título'},{title:'Tipo Gasto'},{title:'Monto USD'},
                {title:'Destinatario'},{title:'Estado'},{title:'Fecha'},{title:'Acciones',orderable:false}
            ],
            pageLength:25,
            language: {url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}
        });
    });
}
function loadDropdowns() {
    var fieldMap = {'expense_types':'expense_type_id','companies':'company_id','departments':'department_id','projects':'project_id','payment_types':'payment_type_id','categories':'category_id'};
    ['expense_types','companies','departments','projects','payment_types','categories'].forEach(function(e) {
        $.post('<?= base_url('/app/finance/api/') ?>'+e, function(r) {
            var sel = $('#'+fieldMap[e]).empty().append('<option value="">Seleccionar...</option>');
            if (r.status==='success') r.data.forEach(function(d) { sel.append('<option value="'+d.id+'">'+(d.name||d.title||'')+'</option>'); });
            // Reinitialize Select2 after loading options
            $('#'+fieldMap[e]).select2({dropdownParent: $('#financeModal'), width:'100%', placeholder: 'Seleccionar...', allowClear: true});
        });
    });
}
function showModal(mode,id) {
    if (!canWriteLegacy) return;
    editId=mode==='edit'?id:null; $('#record_id').val('');
    $('#financeForm')[0].reset(); $('#modalTitle').text(mode==='create'?'Nuevo Gasto':'Editar Gasto');
    loadDropdowns();
    if(mode==='edit'&&id)$.get(apiBase+'/'+id,function(r){if(r.status==='success'){var d=r.data; Object.keys(d).forEach(function(k){var el=$('[name="'+k+'"]'); if(el.length) el.val(d[k]).trigger('change'); }); $('#record_id').val(d.id);}});
    // Load existing recipients and providers for autocomplete
    $.post(apiBase, function(r) {
        if (r.status==='success') {
            var recipients = {}, providers = {};
            r.data.forEach(function(d) {
                if (d.recipient) recipients[d.recipient] = true;
                if (d.provider) providers[d.provider] = true;
            });
            var rList = $('#recipient-list').empty();
            Object.keys(recipients).sort().forEach(function(v) { rList.append('<option value="'+v+'">'); });
            var pList = $('#provider-list').empty();
            Object.keys(providers).sort().forEach(function(v) { pList.append('<option value="'+v+'">'); });
        }
    });
    $('#financeModal').modal('show');
}
function edit(id){showModal('edit',id);}
function remove(id){if(!canWriteLegacy||!confirm('¿Eliminar?')) return; $.post(apiBase+'/'+id+'/delete',function(r){if(r.status==='success')loadTable();else alert('Error: '+(r.message||''));});}
$('#financeForm').submit(function(e){e.preventDefault();var id=$('#record_id').val(); var url=apiBase+(id?'/'+id:'/create');
    if(!canWriteLegacy)return;
    $.ajax({url:url, method:'POST', data:$(this).serialize(), dataType:'json',success:function(r){if(r.status==='success'){$('#financeModal').modal('hide');loadTable();}else alert(r.message||'Error');},error:function(){alert('Error de conexión');}});});
$(document).ready(function(){loadTable();});
</script>