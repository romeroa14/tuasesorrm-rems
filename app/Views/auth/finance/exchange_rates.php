<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-dollar-sign text-primary"></i> Tasas de Cambio</h1>
        <div>
            <button class="btn btn-success btn-sm" onclick="fetchRates()"><i class="fas fa-sync"></i> Obtener Tasas</button>
            <button class="btn btn-primary btn-sm" onclick="showModal('create')"><i class="fas fa-plus"></i> Manual</button>
        </div>
    </div>
    <div class="card shadow mb-4"><div class="card-body">
        <table id="dataTable" class="table table-striped table-bordered w-100">
            <thead class="thead-light"><tr><th>ID</th><th>Moneda</th><th>Fuente</th><th>Tasa</th><th>Fecha</th><th>Auto</th><th>Actualizado</th><th>Acciones</th></tr></thead>
        </table>
    </div></div>
</div>
<div class="modal fade" id="financeModal" tabindex="-1"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header bg-primary text-white"><h5 class="modal-title" id="modalTitle">Nueva Tasa Manual</h5><button type="button" class="close text-white" data-dismiss="modal">&times;</button></div>
    <form id="financeForm"><div class="modal-body"><input type="hidden" id="record_id" name="id">
        <div class="row"><div class="col-md-6"><div class="form-group"><label>Moneda <span class="text-danger">*</span></label><select class="form-control" id="currency_id" name="currency_id" required><option value="">Cargando...</option></select></div></div>
        <div class="col-md-6"><div class="form-group"><label>Fuente <span class="text-danger">*</span></label><select class="form-control" id="source" name="source" required><option value="">Seleccionar...</option><option value="oficial">Oficial</option><option value="paralelo">Paralelo</option><option value="promedio_usdt">Promedio USDT</option></select></div></div></div>
        <div class="row"><div class="col-md-6"><div class="form-group"><label>Tasa (Bs.) <span class="text-danger">*</span></label><input type="number" step="0.0001" class="form-control" id="rate" name="rate" required min="0"></div></div>
        <div class="col-md-6"><div class="form-group"><label>Fecha <span class="text-danger">*</span></label><input type="date" class="form-control" id="rate_date" name="rate_date" required></div></div></div>
        <input type="hidden" name="is_auto" value="0">
    </div><div class="modal-footer"><button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button><button type="submit" class="btn btn-success">Guardar</button></div></form>
</div></div></div>
<script>
var dt, apiBase='<?= base_url('/app/finance/api/exchange_rates') ?>';
function loadTable(){$.post(apiBase,function(r){var rows=[];if(r.status==='success')r.data.forEach(function(d){rows.push([d.id,d.currency_code||d.currency_id,d.source,parseFloat(d.rate).toFixed(2)+' Bs.',d.rate_date,d.is_auto?'<span class="badge badge-info">API</span>':'<span class="badge badge-secondary">Manual</span>',d.fetched_at||'—','<button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>']);});if(dt)dt.clear().rows.add(rows).draw();else dt=$('#dataTable').DataTable({data:rows,pageLength:25,language:{url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'}});});}
function fetchRates(){$.post('<?= base_url('/app/finance/exchange_rates/fetch') ?>',function(r){alert(r.message||'Tasas actualizadas');if(r.status==='success')loadTable();},'json').fail(function(){alert('Error al obtener tasas');});}
function showModal(mode,id){$('#record_id').val('');$('#financeForm')[0].reset();$('#modalTitle').text('Nueva Tasa Manual');$.post('<?= base_url('/app/finance/api/currencies') ?>',function(r){var sel=$('#currency_id').empty().append('<option value="">Seleccionar...</option>');if(r.status==='success')r.data.forEach(function(d){sel.append('<option value="'+d.id+'">'+d.name+'</option>');});});$('#financeModal').modal('show');}
function remove(id){if(!confirm('¿Eliminar?'))return;$.post(apiBase+'/'+id+'/delete',function(r){if(r.status==='success')loadTable();});}
$('#financeForm').submit(function(e){e.preventDefault();var id=$('#record_id').val();$.ajax({url:apiBase+(id?'/'+id:'/create'),method:'POST',data:$(this).serialize(),dataType:'json',success:function(r){if(r.status==='success'){$('#financeModal').modal('hide');loadTable();}}});});
$(document).ready(function(){loadTable();});
</script>