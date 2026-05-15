<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tags text-primary"></i> Categorías</h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nueva Categoría</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Listado de Categorías</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light"><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Descripción</th><th>Padre</th><th>Acciones</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="financeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-gradient-primary text-white"><h5 class="modal-title" id="modalTitle"><i class="fas fa-tags"></i> Categoría</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form id="financeForm"><div class="modal-body">
        <input type="hidden" id="record_id" name="id">
        <div class="card bg-light mb-3">
            <div class="card-header py-2"><i class="fas fa-info-circle text-primary"></i> <strong>Información de la Categoría</strong></div>
            <div class="card-body">
                <div class="form-group"><label><i class="fas fa-heading text-muted"></i> Nombre <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required></div>
                <div class="row">
                    <div class="col-md-6"><div class="form-group"><label><i class="fas fa-arrows-alt-h text-muted"></i> Tipo <span class="text-danger">*</span></label><select class="form-control" id="type" name="type" required><option value="">Seleccionar...</option><option value="income">💰 Ingreso</option><option value="expense">💸 Gasto</option></select></div></div>
                    <div class="col-md-6"><div class="form-group"><label><i class="fas fa-sitemap text-muted"></i> Categoría Padre</label><select class="form-control" id="parent_id" name="parent_id"><option value="">Ninguna</option></select></div></div>
                </div>
                <div class="form-group"><label><i class="fas fa-comment text-muted"></i> Descripción</label><textarea class="form-control" id="description" name="description" rows="2"></textarea></div>
            </div>
        </div>
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button></div></form>
</div></div></div>
<script>
var dt, apiBase='<?= base_url('/app/finance/api/categories') ?>';
function loadTable(){$.post(apiBase,function(r){var rows=[];if(r.status==='success')r.data.forEach(function(d){rows.push([d.id,d.name,'<span class="badge badge-'+ (d.type==='income'?'success':'danger')+'">'+(d.type==='income'?'💰 Ingreso':'💸 Gasto')+'</span>',d.description||'—',d.parent_id||'—','<button class="btn btn-info btn-sm mr-1" onclick="edit('+d.id+')"><i class="fas fa-edit"></i></button><button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>']);});if(dt)dt.clear().rows.add(rows).draw();else dt=$('#dataTable').DataTable({data:rows,pageLength:25,language:{url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}});});}
function showModal(mode,id){$('#record_id').val('');$('#financeForm')[0].reset();$('#modalTitle').text(mode==='create'?'Nueva Categoría':'Editar Categoría');$.post(apiBase,function(r){var sel=$('#parent_id').empty().append('<option value="">Ninguna</option>');if(r.status==='success')r.data.forEach(function(d){sel.append('<option value="'+d.id+'">'+d.name+'</option>');});if(mode==='edit'&&id)$.get(apiBase+'/'+id,function(r2){if(r2.status==='success'){var d=r2.data;Object.keys(d).forEach(function(k){var el=$('[name="'+k+'"]');if(el.length)el.val(d[k]);});$('#record_id').val(d.id);}});});$('#financeModal').modal('show');}
function edit(id){showModal('edit',id);}
function remove(id){if(!confirm('¿Eliminar?'))return;$.post(apiBase+'/'+id+'/delete',function(r){if(r.status==='success')loadTable();});}
$('#financeForm').submit(function(e){e.preventDefault();var id=$('#record_id').val();$.ajax({url:apiBase+(id?'/'+id:'/create'),method:'POST',data:$(this).serialize(),dataType:'json',success:function(r){if(r.status==='success'){$('#financeModal').modal('hide');loadTable();}},error:function(){alert('Error');}});});
$(document).ready(function(){loadTable();});
</script>
