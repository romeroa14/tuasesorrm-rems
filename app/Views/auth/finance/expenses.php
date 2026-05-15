<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-receipt text-primary"></i> Gastos</h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nuevo Gasto</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Listado de Gastos</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light"><tr><th>ID</th><th>Título</th><th>Tipo</th><th>Monto USD</th><th>Total USD</th><th>Empresa</th><th>Estado</th><th>Fecha</th><th>Acciones</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="financeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="modalTitle">Nuevo Gasto</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <input type="hidden" name="user_id" value="<?= session()->get('id') ?>">
                    <input type="hidden" name="created_by" value="<?= session()->get('id') ?>">
                    <input type="hidden" name="currency_id" value="1">
                    <input type="hidden" name="amount" id="amount" value="0">
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Título <span class="text-danger">*</span></label><input type="text" class="form-control" id="title" name="title" required></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Tipo de Gasto <span class="text-danger">*</span></label><select class="form-control" id="expense_type_id" name="expense_type_id" required><option value="">Cargando...</option></select></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Monto USD <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="amount_usd" name="amount_usd" required min="0" onchange="document.getElementById('amount').value=this.value"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>IVA</label><input type="number" step="0.01" class="form-control" id="tax_amount_usd" name="tax_amount_usd" value="0" min="0"></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Moneda Origen</label><input type="text" class="form-control" id="original_currency" name="original_currency" placeholder="USD/VES"></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Empresa</label><select class="form-control" id="company_id" name="company_id"><option value="">Seleccionar...</option></select></div></div>
                        <div class="col-md-6"><div class="form-group"><label>Departamento</label><select class="form-control" id="department_id" name="department_id"><option value="">Seleccionar...</option></select></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Proyecto</label><select class="form-control" id="project_id" name="project_id"><option value="">Seleccionar...</option></select></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Categoría <span class="text-danger">*</span></label><select class="form-control" id="category_id" name="category_id" required><option value="">Cargando...</option></select></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Forma de Pago <span class="text-danger">*</span></label><select class="form-control" id="payment_type_id" name="payment_type_id" required><option value="">Cargando...</option></select></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-4"><div class="form-group"><label>Fecha Gasto <span class="text-danger">*</span></label><input type="date" class="form-control" id="expense_date" name="expense_date" required></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Prioridad</label><select class="form-control" id="priority" name="priority"><option value="medium">Media</option><option value="low">Baja</option><option value="high">Alta</option></select></div></div>
                        <div class="col-md-4"><div class="form-group"><label>Estado</label><select class="form-control" id="status" name="status"><option value="pending">Pendiente</option><option value="approved">Aprobado</option><option value="paid">Pagado</option><option value="rejected">Rechazado</option></select></div></div>
                    </div>
                    <div class="row">
                        <div class="col-md-6"><div class="form-group"><label>Nro. Factura</label><input type="text" class="form-control" id="invoice_number" name="invoice_number"></div></div>
                    </div>
                    <div class="form-group"><label>Descripción</label><textarea class="form-control" id="description" name="description" rows="2"></textarea></div>
                </div>
                <div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar</button></div>
            </form>
        </div>
    </div>
</div>
<script>
var dt, apiBase = '<?= base_url('/app/finance/api/expenses') ?>', editId = null;
function loadTable() {
    $.post(apiBase, function(r) {
        var rows = [];
        if (r.status==='success' && Array.isArray(r.data)) r.data.forEach(function(d) {
            rows.push([d.id, d.title||'—', d.category||d.expense_type_id||'—',
                '$'+parseFloat(d.amount_usd||0).toFixed(2), '$'+parseFloat(d.total_amount_usd||d.amount_usd||0).toFixed(2),
                d.company_id||'—',
                `<span class="badge badge-${d.status==='paid'?'success':d.status==='approved'?'primary':d.status==='rejected'?'danger':'secondary'}">${d.status}</span>`,
                d.expense_date||'—',
                '<button class="btn btn-info btn-sm mr-1" onclick="edit('+d.id+')"><i class="fas fa-edit"></i></button>'+
                '<button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>']);
        });
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#dataTable').DataTable({data:rows, pageLength:25, language: {url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}});
    });
}
function loadDropdowns() {
    var fieldMap = {'expense_types':'expense_type_id','companies':'company_id','departments':'department_id','projects':'project_id','payment_types':'payment_type_id','categories':'category_id'};
    ['expense_types','companies','departments','projects','payment_types','categories'].forEach(function(e) {
        $.post('<?= base_url('/app/finance/api/') ?>'+e, function(r) {
            var sel = $('#'+fieldMap[e]).empty().append('<option value="">Seleccionar...</option>');
            if (r.status==='success') r.data.forEach(function(d) { sel.append('<option value="'+d.id+'">'+(d.name||d.title||'')+'</option>'); });
        });
    });
}
function showModal(mode,id) {
    editId=mode==='edit'?id:null; $('#record_id').val('');
    $('#financeForm')[0].reset(); $('#modalTitle').text(mode==='create'?'Nuevo Gasto':'Editar Gasto');
    loadDropdowns();
    if(mode==='edit'&&id)$.get(apiBase+'/'+id,function(r){if(r.status==='success'){var d=r.data; Object.keys(d).forEach(function(k){var el=$('[name="'+k+'"]'); if(el.length) el.val(d[k]); }); $('#record_id').val(d.id);}});
    $('#financeModal').modal('show');
}
function edit(id){showModal('edit',id);}
function remove(id){if(!confirm('¿Eliminar?')) return; $.post(apiBase+'/'+id+'/delete',function(r){if(r.status==='success')loadTable();else alert('Error: '+(r.message||''));});}
$('#financeForm').submit(function(e){e.preventDefault();var id=$('#record_id').val(); var url=apiBase+(id?'/'+id:'/create');
    $.ajax({url:url, method:'POST', data:$(this).serialize(), dataType:'json',success:function(r){if(r.status==='success'){$('#financeModal').modal('hide');loadTable();}else alert(r.message||'Error');},error:function(){alert('Error de conexión');}});});
$(document).ready(function(){loadTable();});
</script>