<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-sitemap text-primary"></i> Departamentos</h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nuevo Departamento</button>
    </div>
    <div class="card shadow mb-4"><div class="card-body">
        <table id="dataTable" class="table table-striped table-bordered w-100">
            <thead class="thead-light"><tr><th>ID</th><th>Nombre</th><th>Encargado</th><th>Presupuesto</th><th>Estado</th><th>Acciones</th></tr></thead>
        </table>
    </div></div>
</div>
<div class="modal fade" id="financeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="modalTitle">Nuevo Departamento</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form id="financeForm"><div class="modal-body"><input type="hidden" id="record_id" name="id">
        <div class="form-group"><label>Nombre <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required></div>
        <div class="row"><div class="col-md-6"><div class="form-group"><label>Encargado</label><input type="text" class="form-control" id="manager" name="manager"></div></div>
        <div class="col-md-6"><div class="form-group"><label>Presupuesto $</label><input type="number" step="0.01" class="form-control" id="budget" name="budget" min="0"></div></div></div>
        <div class="form-group"><label>Descripción</label><textarea class="form-control" id="description" name="description" rows="2"></textarea></div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar</button></div></form>
</div></div></div>
<script>
var dt, apiBase='<?= base_url('/app/finance/api/departments') ?>';
function loadTable(){$.post(apiBase,function(r){var rows=[];if(r.status==='success')r.data.forEach(function(d){rows.push([d.id,d.name,d.manager||'—','$'+parseFloat(d.budget||0).toFixed(2),d.active||d.status||'active','<button class="btn btn-info btn-sm mr-1" onclick="edit('+d.id+')"><i class="fas fa-edit"></i></button><button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>']);});if(dt)dt.clear().rows.add(rows).draw();else dt=$('#dataTable').DataTable({data:rows,pageLength:25,language:{url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}});});}
function showModal(mode,id){$('#record_id').val('');$('#financeForm')[0].reset();$('#modalTitle').text(mode==='create'?'Nuevo Departamento':'Editar Departamento');if(mode==='edit'&&id)$.get(apiBase+'/'+id,function(r){if(r.status==='success'){var d=r.data;Object.keys(d).forEach(function(k){var el=$('[name="'+k+'"]');if(el.length)el.val(d[k]);});$('#record_id').val(d.id);}});$('#financeModal').modal('show');}
function edit(id){showModal('edit',id);}
function remove(id){if(!confirm('¿Eliminar?'))return;$.post(apiBase+'/'+id+'/delete',function(r){if(r.status==='success')loadTable();});}
$('#financeForm').submit(function(e){e.preventDefault();var id=$('#record_id').val();$.ajax({url:apiBase+(id?'/'+id:'/create'),method:'POST',data:$(this).serialize(),dataType:'json',success:function(r){if(r.status==='success'){$('#financeModal').modal('hide');loadTable();}}});});
$(document).ready(function(){loadTable();});
</script>