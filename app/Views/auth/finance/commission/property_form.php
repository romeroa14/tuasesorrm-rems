<!-- Commission Property Form — create/edit with inline participants -->
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
            <i class="fas fa-building text-primary"></i> <?= esc($title ?? 'Comisiones — Propiedad') ?>
        </h1>
        <a href="<?= base_url('/app/finance/commission/properties') ?>" class="btn btn-secondary btn-sm">
            <i class="fas fa-arrow-left"></i> Volver a Propiedades
        </a>
    </div>

    <?php $property = $property ?? []; $isEdit = !empty($property['id']); ?>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-info-circle"></i> Datos de la Propiedad</h6>
        </div>
        <div class="card-body">
            <form id="propertyForm" method="post" action="<?= base_url('/app/finance/commission/save-property') ?>">
                <?php if ($isEdit): ?>
                    <input type="hidden" name="id" value="<?= esc($property['id']) ?>">
                <?php endif; ?>

                <div class="row">
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Referencia <span class="text-danger">*</span></label>
                            <input type="text" class="form-control" name="reference" required
                                   value="<?= esc($property['reference'] ?? '') ?>"
                                   placeholder="Ej: APTO-001 / INM-REF-123">
                        </div>
                    </div>
                    <div class="col-md-6">
                        <div class="form-group">
                            <label>Fecha de Venta <span class="text-danger">*</span></label>
                            <input type="date" class="form-control" name="sale_date" required
                                   value="<?= esc($property['sale_date'] ?? date('Y-m-d')) ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Precio de Venta ($) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="sale_price" id="sale_price"
                                   required min="0.01" placeholder="0.00"
                                   value="<?= esc($property['sale_price'] ?? '') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Comisión (%) <span class="text-danger">*</span></label>
                            <input type="number" step="0.01" class="form-control" name="commission_pct" id="commission_pct"
                                   required min="0.01" placeholder="3.00"
                                   value="<?= esc($property['commission_pct'] ?? '3.00') ?>">
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Gasto de Registro ($)</label>
                            <input type="number" step="0.01" class="form-control" name="registration_fee" id="registration_fee"
                                   min="0" placeholder="0.00"
                                   value="<?= esc($property['registration_fee'] ?? '0.00') ?>">
                        </div>
                    </div>
                </div>

                <div class="row">
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Base de Comisión (calculado)</label>
                            <input type="text" class="form-control" id="calc_commission_base" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Ingreso Neto (calculado)</label>
                            <input type="text" class="form-control" id="calc_net_income" readonly>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="form-group">
                            <label>Estado</label>
                            <select class="form-control" name="status">
                                <option value="pending" <?= ($property['status'] ?? 'pending') === 'pending' ? 'selected' : '' ?>>Pendiente</option>
                                <option value="settled" <?= ($property['status'] ?? '') === 'settled' ? 'selected' : '' ?>>Liquidado</option>
                                <option value="cancelled" <?= ($property['status'] ?? '') === 'cancelled' ? 'selected' : '' ?>>Cancelado</option>
                            </select>
                        </div>
                    </div>
                </div>

                <div class="form-group">
                    <label>Notas</label>
                    <textarea class="form-control" name="notes" rows="2" placeholder="Observaciones..."><?= esc($property['notes'] ?? '') ?></textarea>
                </div>

                <div class="form-group text-right">
                    <button type="submit" class="btn btn-success">
                        <i class="fas fa-save"></i> <?= $isEdit ? 'Actualizar' : 'Guardar' ?> Propiedad
                    </button>
                </div>
            </form>
        </div>
    </div>

    <?php if ($isEdit): ?>
    <!-- Participants Section -->
    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-users"></i> Participantes</h6>
            <button type="button" class="btn btn-success btn-sm" id="addParticipantBtn">
                <i class="fas fa-plus"></i> Agregar Participante
            </button>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table class="table table-sm" id="participantsTable">
                    <thead class="thead-light">
                        <tr>
                            <th>Agente</th>
                            <th>Rol</th>
                            <th>Tipo</th>
                            <th>Valor</th>
                            <th>Monto Calc.</th>
                            <th>Acciones</th>
                        </tr>
                    </thead>
                    <tbody id="participantsBody">
                        <!-- loaded via JS -->
                    </tbody>
                </table>
            </div>
            <div id="noParticipants" class="text-center text-muted py-3">
                <i class="fas fa-info-circle"></i> No hay participantes asignados. Agregue al menos uno.
            </div>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Hidden template row for JS cloning -->
<template id="participantRowTemplate">
    <?= view('auth/finance/commission/participant_row', ['users' => $users ?? []]) ?>
</template>

<script>
var propertyId = <?= $property['id'] ?? 0 ?>;
var baseUrl = '<?= base_url('/app/finance/commission') ?>';
var users = <?= json_encode($users ?? []) ?>;

function formatMoney(value) {
    if (!isFinite(value)) return '$ 0.00';
    return '$ ' + parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function recalcPreview() {
    var salePrice = parseFloat($('#sale_price').val() || 0);
    var pct = parseFloat($('#commission_pct').val() || 0);
    var fee = parseFloat($('#registration_fee').val() || 0);

    if (salePrice > 0 && pct > 0) {
        var base = salePrice * pct / 100;
        var net = base - fee;
        $('#calc_commission_base').val(formatMoney(base));
        $('#calc_net_income').val(formatMoney(net));
    } else {
        $('#calc_commission_base').val('');
        $('#calc_net_income').val('');
    }
}

function loadParticipants() {
    if (!propertyId) return;
    $.get(baseUrl + '/get-participants/' + propertyId, function(data) {
        var tbody = $('#participantsBody').empty();
        if (!Array.isArray(data) || data.length === 0) {
            $('#noParticipants').show();
            $('#participantsTable').hide();
            return;
        }
        $('#noParticipants').hide();
        $('#participantsTable').show();

        var roleLabels = {
            'cerrador': 'Cerrador', 'cap': 'CAP', 'coordinator': 'Coordinador',
            'gs': 'GS', 'fe': 'FE', 'sales_manager': 'Gerente Ventas',
            'registro': 'Registro', 'external_advisor': 'Asesor Externo', 'ne': 'NE'
        };
        var typeLabels = {'percentage': 'Porcentaje', 'fixed': 'Fijo', 'formula': 'Fórmula'};

        data.forEach(function(p) {
            var userName = p.user_name || ('Usuario #' + p.user_id);
            var row = '<tr>';
            row += '<td>' + userName + '</td>';
            row += '<td>' + (roleLabels[p.role] || p.role) + '</td>';
            row += '<td>' + (typeLabels[p.commission_type] || p.commission_type) + '</td>';
            row += '<td>' + p.commission_value + (p.commission_type === 'percentage' ? '%' : '') + '</td>';
            row += '<td>' + formatMoney(p.calculated_amount) + '</td>';
            row += '<td><button class="btn btn-danger btn-sm" onclick="deleteParticipant(' + p.id + ')"><i class="fas fa-trash"></i></button></td>';
            row += '</tr>';
            tbody.append(row);
        });
    });
}

function deleteParticipant(id) {
    if (!confirm('¿Eliminar este participante?')) return;
    $.post(baseUrl + '/delete-participant/' + id, function(r) {
        if (r.success) loadParticipants();
        else alert(r.error || 'Error al eliminar participante.');
    });
}

$('#addParticipantBtn').click(function() {
    var template = document.getElementById('participantRowTemplate');
    var clone = template.content.cloneNode(true);

    // Build user options
    var userSel = clone.querySelector('.participant-user');
    if (userSel) {
        users.forEach(function(u) {
            var opt = document.createElement('option');
            opt.value = u.id;
            opt.textContent = u.full_name || u.name || ('Usuario #' + u.id);
            userSel.appendChild(opt);
        });
    }

    $('#participantsBody').append(clone);
    $('#noParticipants').hide();
    $('#participantsTable').show();

    // Init Select2 on new rows
    $('.participant-user').select2({width: '100%', placeholder: 'Seleccionar agente...'});
});

$(document).on('click', '.remove-participant-row', function() {
    $(this).closest('tr').remove();
    if ($('#participantsBody tr').length === 0) {
        $('#noParticipants').show();
        $('#participantsTable').hide();
    }
});

$(document).on('click', '.save-participant-row', function() {
    var row = $(this).closest('tr');
    var formData = {
        property_id: propertyId,
        user_id: row.find('.participant-user').val(),
        role: row.find('.participant-role').val(),
        commission_type: row.find('.participant-type').val(),
        commission_value: row.find('.participant-value').val()
    };

    $.post(baseUrl + '/save-participant', formData, function(r) {
        if (r.success) loadParticipants();
        else alert(r.error || 'Error al guardar participante.');
    });
});

$('#sale_price, #commission_pct, #registration_fee').on('change keyup', recalcPreview);

$(document).ready(function() {
    recalcPreview();
    loadParticipants();
});
</script>
