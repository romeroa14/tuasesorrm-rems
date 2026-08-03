<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-piggy-bank text-info"></i> Carteras</h1>
    </div>
    <div class="alert alert-info">
        <i class="fas fa-info-circle"></i>
        Módulo de carteras internas (incluye placeholder OECD). Las reglas de OECD se definirán con la directiva.
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary">Saldos</h6>
            <?php if (! empty($can_manage_wallets)): ?>
            <button class="btn btn-primary btn-sm" data-toggle="modal" data-target="#transferModal"><i class="fas fa-exchange-alt"></i> Transferir</button>
            <?php endif; ?>
        </div>
        <div class="card-body">
            <table class="table table-bordered" id="walletsTable"><thead class="thead-light">
                <tr><th>Código</th><th>Nombre</th><th>Tipo</th><th>Saldo</th><th>Notas</th></tr>
            </thead><tbody></tbody></table>
        </div>
    </div>
</div>
<?php if (! empty($can_manage_wallets)): ?>
<div class="modal fade" id="transferModal"><div class="modal-dialog"><div class="modal-content">
    <div class="modal-header"><h5 class="modal-title">Transferencia entre carteras</h5><button type="button" class="close" data-dismiss="modal">&times;</button></div>
    <div class="modal-body">
        <div class="form-group"><label>Desde</label><select id="from_wallet_id" class="form-control"></select></div>
        <div class="form-group"><label>Hacia</label><select id="to_wallet_id" class="form-control"></select></div>
        <div class="form-group"><label>Monto</label><input type="number" step="0.01" id="transfer_amount" class="form-control"></div>
        <div class="form-group"><label>Fecha</label><input type="date" id="transfer_date" class="form-control" value="<?= date('Y-m-d') ?>"></div>
        <div class="form-group"><label>Descripción</label><input type="text" id="transfer_description" class="form-control"></div>
    </div>
    <div class="modal-footer"><button class="btn btn-primary" onclick="submitTransfer()">Registrar</button></div>
</div></div></div>
<?php endif; ?>
<script>
var wallets = [];
function loadWallets() {
    $.post('<?= base_url('/app/finance/wallets/api/list') ?>', function(r) {
        if (r.status !== 'success') return;
        wallets = r.data || [];
        var rows = wallets.map(function(w) {
            return [w.code, w.name, w.wallet_type, parseFloat(w.balance||0).toFixed(2), w.notes||''];
        });
        if ($.fn.DataTable.isDataTable('#walletsTable')) $('#walletsTable').DataTable().clear().rows.add(rows).draw();
        else $('#walletsTable').DataTable({ data: rows, pageLength: 25 });
        var opts = wallets.map(function(w){ return '<option value="'+w.id+'">'+w.name+'</option>'; }).join('');
        $('#from_wallet_id, #to_wallet_id').html('<option value="">—</option>'+opts);
    });
}
function submitTransfer() {
    $.post('<?= base_url('/app/finance/wallets/api/transfer') ?>', {
        from_wallet_id: $('#from_wallet_id').val(),
        to_wallet_id: $('#to_wallet_id').val(),
        amount: $('#transfer_amount').val(),
        transfer_date: $('#transfer_date').val(),
        description: $('#transfer_description').val()
    }, function(r) {
        alert(r.message || 'OK');
        if (r.status === 'success') { $('#transferModal').modal('hide'); loadWallets(); }
    }, 'json');
}
loadWallets();
</script>
