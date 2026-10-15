<!-- Commission Properties — DataTable list -->
<div class="container-fluid">
    <?php if (session()->getFlashdata('success')): ?>
        <div class="alert alert-success alert-dismissible fade show" role="alert">
            <i class="fas fa-check-circle"></i> <?= session()->getFlashdata('success') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>
    <?php if (session()->getFlashdata('error')): ?>
        <div class="alert alert-danger alert-dismissible fade show" role="alert">
            <i class="fas fa-exclamation-triangle"></i> <?= session()->getFlashdata('error') ?>
            <button type="button" class="close" data-dismiss="alert">&times;</button>
        </div>
    <?php endif; ?>

    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-building text-primary"></i> <?= esc($title ?? 'Comisiones — Propiedades') ?>
        </h1>
        <a href="<?= base_url('/app/finance/commission/property-form') ?>" class="btn btn-primary btn-sm">
            <i class="fas fa-plus"></i> Agregar Propiedad
        </a>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Listado de Propiedades</h6>
        </div>
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-4">
                    <label class="mb-1">Perfil</label>
                    <select class="form-control form-control-sm" id="filterProfile">
                        <option value="">Todos</option>
                        <option value="coordinator">Coordinadora</option>
                        <option value="atc">ATC</option>
                        <option value="marketing">Mercadeo</option>
                    </select>
                </div>
                <div class="col-md-4">
                    <label class="mb-1">Persona</label>
                    <select class="form-control form-control-sm" id="filterPerson">
                        <option value="">Todas</option>
                        <?php foreach (($filter_users ?? []) as $user): ?>
                        <option value="<?= (int) ($user['id'] ?? 0) ?>"><?= esc($user['full_name'] ?? ('Usuario #' . ($user['id'] ?? ''))) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
            </div>
            <div class="table-responsive">
                <table id="propertiesTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Referencia</th>
                            <th>Pago total</th>
                            <th>Comisión</th>
                            <th>Gasto Registro</th>
                            <th>Ingreso Neto</th>
                            <th>Fecha Venta</th>
                            <th>Estado</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var dt;
var propertyMeta = {};
var baseUrl = '<?= base_url('/app/finance/commission') ?>';

function formatMoney(value) {
    if (!isFinite(value)) return '';
    return '$ ' + parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function loadTable() {
    var rows = [];
    <?php foreach ($properties ?? [] as $row): ?>
    propertyMeta[<?= (int) ($row['id'] ?? 0) ?>] = <?= json_encode([
        'roles' => array_values(array_unique(array_column($row['participants'] ?? [], 'role'))),
        'users' => array_values(array_unique(array_map('intval', array_column($row['participants'] ?? [], 'user_id')))),
    ], JSON_UNESCAPED_UNICODE) ?>;
    var statusLbl = {'pending': 'Pendiente', 'settled': 'Liquidado', 'cancelled': 'Cancelado'};
    var statusCls = {'pending': 'warning', 'settled': 'success', 'cancelled': 'secondary'};
    rows.push([
        <?= $row['id'] ?? 0 ?>,
        '<?= esc($row['reference'] ?? '—', 'js') ?>',
        formatMoney(<?= $row['sale_price'] ?? 0 ?>),
        formatMoney(<?= $row['commission_base'] ?? 0 ?>),
        formatMoney(<?= $row['registration_fee'] ?? 0 ?>),
        formatMoney(<?= $row['net_income'] ?? 0 ?>),
        '<?= esc($row['sale_date'] ?? '—', 'js') ?>',
        '<span class="badge badge-' + (statusCls['<?= esc($row['status'] ?? 'pending', 'js') ?>'] || 'secondary') + '">' + (statusLbl['<?= esc($row['status'] ?? '', 'js') ?>'] || '<?= esc($row['status'] ?? '—', 'js') ?>') + '</span>',
        '<a href="' + baseUrl + '/property-form/<?= $row['id'] ?? 0 ?>" class="btn btn-info btn-sm mr-1"><i class="fas fa-edit"></i></a>' +
        '<button class="btn btn-danger btn-sm" onclick="deleteProperty(<?= $row['id'] ?? 0 ?>)"><i class="fas fa-trash"></i></button>'
    ]);
    <?php endforeach; ?>

    if (dt) dt.clear().rows.add(rows).draw();
    else {
        dt = $('#propertiesTable').DataTable({
            data: rows,
            columns: [
                {title: 'ID'},
                {title: 'Referencia'},
                {title: 'Precio Venta'},
                {title: 'Base Comisión'},
                {title: 'Gasto Registro'},
                {title: 'Ingreso Neto'},
                {title: 'Fecha Venta'},
                {title: 'Estado'},
                {title: 'Acciones', orderable: false}
            ],
            pageLength: 25,
            language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
            dom: 'Bfrtip',
            buttons: ['excel', 'pdf']
        });
    }
}

function deleteProperty(id) {
    if (!confirm('¿Eliminar esta propiedad? Se eliminarán también sus participantes.')) return;
    $.post(baseUrl + '/delete-property/' + id, function() {
        location.reload();
    });
}

$.fn.dataTable.ext.search.push(function(settings, data) {
    if (settings.nTable.id !== 'propertiesTable') return true;
    var id = parseInt(data[0], 10);
    var meta = propertyMeta[id] || {roles: [], users: []};
    var role = $('#filterProfile').val();
    var person = $('#filterPerson').val();
    if (role && (meta.roles || []).indexOf(role) === -1) return false;
    if (person && (meta.users || []).map(String).indexOf(String(person)) === -1) return false;
    return true;
});
$('#filterProfile, #filterPerson').on('change', function() {
    if (dt) dt.draw();
});
$(document).ready(function() { loadTable(); });

</script>
