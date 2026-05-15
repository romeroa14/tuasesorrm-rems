<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-tasks text-primary"></i> Proyectos</h1>
        <button class="btn btn-sm btn-primary" onclick="openForm()"><i class="fas fa-plus"></i> Nuevo Proyecto</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <table id="projectsTable" class="table table-bordered table-hover w-100">
                <thead><tr><th>ID</th><th>Nombre</th><th>Código</th><th>Departamento</th><th>Presupuesto</th><th>Estado</th><th>Inicio</th><th>Fin</th><th>Acciones</th></tr></thead>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    const t = $('#projectsTable').DataTable({
        processing: true, serverSide: false, ajax: {url: '/app/finance/api/projects', type: 'POST'},
        columns: [
            {data: 'id'}, {data: 'name'}, {data: 'code'}, {data: 'department_id'},
            {data: 'budget', render: b => '$' + parseFloat(b||0).toLocaleString()},
            {data: 'status', render: s => {
                const m = {planning:'badge-secondary',active:'badge-success',completed:'badge-primary',cancelled:'badge-danger'};
                return `<span class="badge ${m[s]||'badge-secondary'}">${s}</span>`;
            }},
            {data: 'start_date'}, {data: 'end_date'},
            {data: 'id', render: id => `<button class="btn btn-sm btn-outline-primary" onclick="edit(${id})"><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="remove(${id})"><i class="fas fa-trash"></i></button>`}
        ]
    });
    window.openForm = function() { alert('Form modal - implementar'); };
    window.edit = function(id) { alert('Edit ' + id); };
    window.remove = function(id) { if(confirm('¿Eliminar?')) $.post('/app/finance/api/projects/'+id+'/delete', r => {if(r.status=='success') t.ajax.reload();}); };
});
</script>