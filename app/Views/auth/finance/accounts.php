<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-university text-primary"></i> Cuentas</h1>
        <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Nueva Cuenta</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Listado de Cuentas</h6></div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="dataTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light"><tr><th>ID</th><th>Nombre</th><th>Tipo</th><th>Nro. Cuenta</th><th>Saldo Inicial</th><th>Saldo Actual</th><th>Estado</th><th>Acciones</th></tr></thead>
                </table>
            </div>
        </div>
    </div>
</div>
<div class="modal fade" id="financeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header bg-gradient-primary text-white"><h5 class="modal-title" id="modalTitle"><i class="fas fa-university"></i> Cuenta</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
            <form id="financeForm"><div class="modal-body">
                <input type="hidden" id="record_id" name="id">
                <input type="hidden" name="currency_id" value="1">
                <div class="card bg-light mb-3">
                    <div class="card-header py-2"><i class="fas fa-info-circle text-primary"></i> <strong>Información de la Cuenta</strong></div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label><i class="fas fa-heading text-muted"></i> Nombre <span class="text-danger">*</span></label><input type="text" class="form-control" id="name" name="name" required placeholder="Ej: Banesco"></div></div>
                            <div class="col-md-6"><div class="form-group"><label><i class="fas fa-tag text-muted"></i> Tipo <span class="text-danger">*</span></label><select class="form-control" id="type" name="type" required><option value="">Seleccionar...</option><option value="bank">Banco</option><option value="cash">Efectivo</option><option value="credit_card">Tarjeta Crédito</option><option value="digital_wallet">Billetera Digital</option></select></div></div>
                        </div>
                        <div class="row">
                            <div class="col-md-6"><div class="form-group"><label><i class="fas fa-hashtag text-muted"></i> Nro. Cuenta</label><input type="text" class="form-control" id="account_number" name="account_number" placeholder="000-000000-0"></div></div>
                            <div class="col-md-6"><div class="form-group"><label><i class="fas fa-coins text-muted"></i> Saldo Inicial</label><input type="number" step="0.01" class="form-control" id="initial_balance" name="initial_balance" value="0" min="0"></div></div>
                        </div>
                    </div>
                </div>
            </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal"><i class="fas fa-times"></i> Cancelar</button><button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button></div></form>
        </div>
    </div>
</div>
<script>
var dt, apiBase='<?= base_url('/app/finance/api/accounts') ?>';
function loadTable(){$.post(apiBase,function(r){var rows=[];if(r.status==='success'&&Array.isArray(r.data))r.data.forEach(function(d){rows.push([d.id,d.name,'<span class="badge badge-info">'+d.type+'</span>',d.account_number||'—',parseFloat(d.initial_balance||0).toFixed(2),parseFloat(d.current_balance||0).toFixed(2),d.active?'<span class="badge badge-success">Activa</span>':'<span class="badge badge-secondary">Inactiva</span>','<button class="btn btn-info btn-sm mr-1" onclick="edit('+d.id+')"><i class="fas fa-edit"></i></button><button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>']);});if(dt)dt.clear().rows.add(rows).draw();else dt=$('#dataTable').DataTable({data:rows,pageLength:25,language:{url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}});});}
function showModal(mode,id){$('#record_id').val('');$('#financeForm')[0].reset();$('#modalTitle').text(mode==='create'?'Nueva Cuenta':'Editar Cuenta');if(mode==='edit'&&id)$.get(apiBase+'/'+id,function(r){if(r.status==='success'){var d=r.data;Object.keys(d).forEach(function(k){var el=$('[name="'+k+'"]');if(el.length)el.val(d[k]);});$('#record_id').val(d.id);}});$('#financeModal').modal('show');}
function edit(id){showModal('edit',id);}
function remove(id){if(!confirm('¿Eliminar?'))return;$.post(apiBase+'/'+id+'/delete',function(r){if(r.status==='success')loadTable();});}
$('#financeForm').submit(function(e){e.preventDefault();var id=$('#record_id').val();$.ajax({url:apiBase+(id?'/'+id:'/create'),method:'POST',data:$(this).serialize(),dataType:'json',success:function(r){if(r.status==='success'){$('#financeModal').modal('hide');loadTable();}}});});
$(document).ready(function(){loadTable();});
</script>
