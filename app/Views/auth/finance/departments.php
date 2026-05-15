<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-sitemap text-primary"></i> Departamentos</h1>
        <button class="btn btn-sm btn-primary" onclick="openForm()"><i class="fas fa-plus"></i> Nuevo Departamento</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <table id="departmentsTable" class="table table-bordered table-hover w-100">
                <thead><tr><th>ID</th><th>Nombre</th><th>Encargado</th><th>Presupuesto</th><th>Estado</th><th>Acciones</th></tr></thead>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    const t = $('#departmentsTable').DataTable({
        processing: true, serverSide: false, ajax: {url: '/app/finance/api/departments', type: 'POST'},
        columns: [
            {data: 'id'}, {data: 'name'}, {data: 'manager'}, {data: 'budget'},
            {data: 'status', render: s => s == 'active' ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-secondary">Inactivo</span>'},
            {data: 'id', render: id => `<button class="btn btn-sm btn-outline-primary" onclick="edit(${id})"><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="remove(${id})"><i class="fas fa-trash"></i></button>`}
        ]
    });
    window.openForm = function() { alert('Form modal - implementar'); };
    window.edit = function(id) { alert('Edit ' + id); };
    window.remove = function(id) { if(confirm('¿Eliminar?')) $.post('/app/finance/api/departments/'+id+'/delete', r => {if(r.status=='success') t.ajax.reload();}); };
});
</script>