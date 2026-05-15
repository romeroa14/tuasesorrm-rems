<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-chart-line text-primary"></i> Presupuestos</h1>
        <button class="btn btn-sm btn-primary" onclick="openForm()"><i class="fas fa-plus"></i> Nuevo Presupuesto</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <table id="budgetsTable" class="table table-bordered table-hover w-100">
                <thead><tr><th>ID</th><th>Usuario</th><th>Categoría</th><th>Monto</th><th>Período</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr></thead>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    const t = $('#budgetsTable').DataTable({
        processing: true, serverSide: false, ajax: {url: '/app/finance/api/budgets', type: 'POST'},
        columns: [
            {data: 'id'}, {data: 'user_id'}, {data: 'category_id'},
            {data: 'amount'}, {data: 'period_type'}, {data: 'start_date'},
            {data: 'end_date'}, {data: 'id', render: id => `<button class="btn btn-sm btn-outline-primary" onclick="edit(${id})"><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="remove(${id})"><i class="fas fa-trash"></i></button>`}
        ]
    });
    window.openForm = function() { alert('Form modal - implementar'); };
    window.edit = function(id) { alert('Edit ' + id); };
    window.remove = function(id) { if(confirm('¿Eliminar?')) $.post('/app/finance/api/budgets/'+id+'/delete', r => {if(r.status=='success') t.ajax.reload();}); };
});
</script>