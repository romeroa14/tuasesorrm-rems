<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-line text-primary"></i> Presupuestos</h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nuevo Presupuesto</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <table id="dataTable" class="table table-striped table-bordered w-100">
                <thead class="thead-light"><tr><th>ID</th><th>Usuario</th><th>Categoría</th><th>Monto</th><th>Período</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr></thead>
            </table>
        </div>
    </div>
</div>
<div class="modal fade" id="financeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="modalTitle">Nuevo Presupuesto</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form id="financeForm"><div class="modal-body"><input type="hidden" id="record_id" name="id">
        <div class="row"><div class="col-md-6"><div class="form-group"><label>Categoría <span class="text-danger">*</span></label><select class="form-control" id="category_id" name="category_id" required><option value="">Cargando...</option></select></div></div>
        <div class="col-md-6"><div class="form-group"><label>Monto $ <span class="text-danger">*</span></label><input type="number" step="0.01" class="form-control" id="amount" name="amount" required min="0"></div></div></div>
        <div class="row"><div class="col-md-4"><div class="form-group"><label>Período</label><select class="form-control" id="period_type" name="period_type"><option value="monthly">Mensual</option><option value="quarterly">Trimestral</option><option value="yearly">Anual</option></select></div></div>
        <div class="col-md-4"><div class="form-group"><label>Inicio <span class="text-danger">*</span></label><input type="date" class="form-control" id="start_date" name="start_date" required></div></div>
        <div class="col-md-4"><div class="form-group"><label>Fin <span class="text-danger">*</span></label><input type="date" class="form-control" id="end_date" name="end_date" required></div></div></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar</button></div></form>
</div></div></div>
<script>
var dt, apiBase='<?= base_url('/app/finance/api/budgets') ?>';
function loadTable(){$.post(apiBase,function(r){var rows=[];if(r.status==='success')r.data.forEach(function(d){rows.push([d.id,d.user_id,d.category_id,'$'+parseFloat(d.amount||0).toFixed(2),d.period_type,d.start_date,d.end_date,'<button class="btn btn-info btn-sm mr-1" onclick="edit('+d.id+')"><i class="fas fa-edit"></i></button><button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>']);});if(dt)dt.clear().rows.add(rows).draw();else dt=$('#dataTable').DataTable({data:rows,pageLength:25,language:{url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}});});}
function showModal(mode,id){$('#record_id').val('');$('#financeForm')[0].reset();$('#modalTitle').text(mode==='create'?'Nuevo Presupuesto':'Editar Presupuesto');$.post('<?= base_url('/app/finance/api/categories') ?>',function(r){var sel=$('#category_id').empty().append('<option value="">Seleccionar...</option>');if(r.status==='success')r.data.forEach(function(d){sel.append('<option value="'+d.id+'">'+d.name+'</option>');});if(mode==='edit'&&id)$.post(apiBase+'/'+id,function(r2){if(r2.status==='success'){var d=r2.data;Object.keys(d).forEach(function(k){var el=$('[name="'+k+'"]');if(el.length)el.val(d[k]);});$('#record_id').val(d.id);}});});$('#financeModal').modal('show');}
function edit(id){showModal('edit',id);}
function remove(id){if(!confirm('¿Eliminar?'))return;$.post(apiBase+'/'+id+'/delete',function(r){if(r.status==='success')loadTable();});}
$('#financeForm').submit(function(e){e.preventDefault();var id=$('#record_id').val();$.ajax({url:apiBase+(id?'/'+id:'/create'),method:'POST',data:$(this).serialize(),dataType:'json',success:function(r){if(r.status==='success'){$('#financeModal').modal('hide');loadTable();}}});});
$(document).ready(function(){loadTable();});
</script>