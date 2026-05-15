<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-building text-primary"></i> Empresas</h1>
        <button class="btn btn-sm btn-primary" onclick="openForm()"><i class="fas fa-plus"></i> Nueva Empresa</button>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <table id="companiesTable" class="table table-bordered table-hover w-100">
                <thead><tr><th>ID</th><th>Nombre</th><th>RIF</th><th>Teléfono</th><th>Email</th><th>Contacto</th><th>Acciones</th></tr></thead>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    const t = $('#companiesTable').DataTable({
        processing: true, serverSide: false, ajax: {url: '/app/finance/api/companies', type: 'POST'},
        columns: [
            {data: 'id'}, {data: 'name'}, {data: 'tax_id'}, {data: 'phone'},
            {data: 'email'}, {data: 'contact_person'},
            {data: 'id', render: id => `<button class="btn btn-sm btn-outline-primary" onclick="edit(${id})"><i class="fas fa-edit"></i></button> <button class="btn btn-sm btn-outline-danger" onclick="remove(${id})"><i class="fas fa-trash"></i></button>`}
        ]
    });
    window.openForm = function() { alert('Form modal - implementar'); };
    window.edit = function(id) { alert('Edit ' + id); };
    window.remove = function(id) { if(confirm('¿Eliminar?')) $.post('/app/finance/api/companies/'+id+'/delete', r => {if(r.status=='success') t.ajax.reload();}); };
});
</script>