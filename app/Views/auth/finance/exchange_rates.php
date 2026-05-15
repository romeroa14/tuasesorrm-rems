<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-dollar-sign text-primary"></i> Tasas de Cambio</h1>
        <div>
            <button class="btn btn-sm btn-success" onclick="fetchRates()"><i class="fas fa-sync"></i> Obtener Tasas</button>
            <button class="btn btn-sm btn-primary" onclick="openForm()"><i class="fas fa-plus"></i> Manual</button>
        </div>
    </div>
    <div class="card shadow mb-4">
        <div class="card-body">
            <table id="ratesTable" class="table table-bordered table-hover w-100">
                <thead><tr><th>ID</th><th>Moneda</th><th>Fuente</th><th>Tasa</th><th>Fecha</th><th>Auto</th><th>Actualizado</th><th>Acciones</th></tr></thead>
            </table>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    const t = $('#ratesTable').DataTable({
        processing: true, serverSide: false, ajax: {url: '/app/finance/api/exchange_rates', type: 'POST'},
        columns: [
            {data: 'id'}, {data: 'currency_code'}, {data: 'source'},
            {data: 'rate', render: r => parseFloat(r).toFixed(2) + ' Bs.'}, {data: 'rate_date'},
            {data: 'is_auto', render: a => a ? '<span class="badge badge-info">API</span>' : '<span class="badge badge-secondary">Manual</span>'},
            {data: 'fetched_at'},
            {data: 'id', render: id => `<button class="btn btn-sm btn-outline-danger" onclick="remove(${id})"><i class="fas fa-trash"></i></button>`}
        ]
    });
    window.fetchRates = function() {
        $.post('/app/finance/exchange_rates/fetch', function(r) {
            alert(r.message || 'Tasas actualizadas');
            if(r.status=='success') t.ajax.reload();
        });
    };
    window.remove = function(id) { if(confirm('¿Eliminar?')) $.post('/app/finance/api/exchange_rates/'+id+'/delete', r => {if(r.status=='success') t.ajax.reload();}); };
});
</script>