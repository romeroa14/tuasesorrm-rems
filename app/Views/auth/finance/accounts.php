<?php
/**
 * Finance Accounts — CRUD with DataTable + Modal
 * Reusable modal template with dynamic dropdown loading
 */
?>
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
<?= view('auth/finance/_modal', [
    'title' => 'Cuenta',
    'fields' => [
        ['id' => 'name', 'label' => 'Nombre', 'type' => 'text', 'col' => 6, 'required' => true],
        ['id' => 'type', 'label' => 'Tipo', 'type' => 'select', 'col' => 6, 'required' => true, 'options' => [
            '' => 'Seleccionar...', 'bank' => 'Banco', 'cash' => 'Efectivo', 'credit_card' => 'Tarjeta de Crédito', 'digital_wallet' => 'Billetera Digital'
        ]],
        ['id' => 'account_number', 'label' => 'Nro. Cuenta', 'type' => 'text', 'col' => 6],
        ['id' => 'initial_balance', 'label' => 'Saldo Inicial', 'type' => 'number', 'col' => 6, 'attrs' => 'step="0.01" min="0"'],
        ['id' => 'currency_id', 'label' => '', 'type' => 'hidden', 'col' => 0, 'value' => '1'],
    ],
    'entity' => 'accounts'
]) ?>
<script>
var dt, apiBase = '<?= base_url('/app/finance/api/accounts') ?>';
function loadTable() {
    $.post(apiBase, function(r) {
        var rows = [];
        if (r.status==='success' && Array.isArray(r.data)) r.data.forEach(function(d) {
            rows.push([d.id, d.name, '<span class="badge badge-info">'+d.type+'</span>',
                d.account_number||'—', parseFloat(d.initial_balance||0).toFixed(2), parseFloat(d.current_balance||0).toFixed(2),
                d.active ? '<span class="badge badge-success">Activa</span>' : '<span class="badge badge-secondary">Inactiva</span>',
                '<button class="btn btn-info btn-sm mr-1" onclick="edit('+d.id+')"><i class="fas fa-edit"></i></button>'+
                '<button class="btn btn-danger btn-sm" onclick="remove('+d.id+')"><i class="fas fa-trash"></i></button>']);
        });
        if (dt) dt.clear().rows.add(rows).draw();
        else dt = $('#dataTable').DataTable({data:rows, pageLength:25,
            language: {url:'//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
            dom:'Bfrtip', buttons:['excel','pdf']});
    });
}
function showModal(mode,id){ window._editId=id||null; $('#modalTitle').text(mode==='create'?'Nueva Cuenta':'Editar Cuenta'); $('#financeForm')[0].reset(); $('#record_id').val(''); if(mode==='edit'&&id)$.post(apiBase+'/'+id,function(r){if(r.status==='success'){var d=r.data; Object.keys(d).forEach(function(k){var el=$('[name="'+k+'"]'); if(el.length) el.val(d[k]); }); $('#record_id').val(d.id); }}); $('#financeModal').modal('show'); }
function edit(id){ showModal('edit',id); }
window.showModalForm = showModal;
$(document).ready(function(){ loadTable(); });
</script>