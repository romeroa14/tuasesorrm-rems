<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users-cog text-primary"></i> Usuarios, roles y finanzas</h1>
        <button type="button" class="btn btn-primary btn-sm" id="btnNewUser"><i class="fas fa-plus"></i> Nuevo usuario</button>
    </div>

    <div class="alert alert-info small">
        Solo <strong>superadmin@gmail.com</strong> y <strong>rossanamigliore@tuasesorrm.com.ve</strong> tienen acceso total a la plataforma
        (configurable con <code>PLATFORM_ADMIN_EMAILS</code> en <code>.env</code>).
        El resto de permisos CRM se asignan por <strong>rol</strong>; finanzas por <strong>perfil</strong> en la matriz.
    </div>

    <div class="card shadow mb-4">
        <div class="card-body">
            <div class="row mb-3">
                <div class="col-md-6 col-lg-5">
                    <div class="input-group input-group-sm">
                        <div class="input-group-prepend">
                            <span class="input-group-text"><i class="fas fa-search"></i></span>
                        </div>
                        <input type="search" id="userSearch" class="form-control" placeholder="Buscar por nombre, correo, rol, estado, finanzas o ID…" autocomplete="off">
                        <div class="input-group-append">
                            <button type="button" class="btn btn-outline-secondary" id="btnClearSearch" title="Limpiar">×</button>
                        </div>
                    </div>
                </div>
                <div class="col-md-6 col-lg-7 d-flex align-items-center justify-content-md-end mt-2 mt-md-0">
                    <span class="text-muted small" id="searchSummary"></span>
                </div>
            </div>
            <div id="bulkBar" class="d-none alert alert-secondary py-2 px-3 mb-3 d-flex align-items-center justify-content-between flex-wrap">
                <span><strong id="bulkCount">0</strong> usuario(s) seleccionado(s)</span>
                <button type="button" class="btn btn-sm btn-danger" id="btnBulkDelete">
                    <i class="fas fa-trash-alt"></i> Eliminar seleccionados
                </button>
            </div>
            <table class="table table-bordered table-sm" id="adminUsersTable" width="100%" data-admin-users-table="1">
                <thead class="thead-light">
                <tr>
                    <th style="width:36px" class="text-center">
                        <input type="checkbox" id="selectAllUsers" title="Seleccionar todos">
                    </th>
                    <th>Nombre</th>
                    <th>Correo</th>
                    <th>Rol CRM</th>
                    <th>Estado</th>
                    <th>Finanzas</th>
                    <th>Plataforma</th>
                    <th></th>
                </tr>
                </thead>
                <tbody></tbody>
            </table>
        </div>
    </div>
</div>

<div class="modal fade" id="userModal" tabindex="-1">
    <div class="modal-dialog modal-lg">
        <div class="modal-content">
            <form id="userForm">
                <div class="modal-header">
                    <h5 class="modal-title">Usuario</h5>
                    <button type="button" class="close" data-dismiss="modal"><span>&times;</span></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" name="id" id="userId">
                    <div class="form-row">
                        <div class="form-group col-md-6">
                            <label>Nombre completo</label>
                            <input name="full_name" id="full_name" class="form-control" required>
                        </div>
                        <div class="form-group col-md-6">
                            <label>Correo</label>
                            <input type="email" name="email" id="email" class="form-control" required>
                        </div>
                    </div>
                    <div class="form-row">
                        <div class="form-group col-md-4">
                            <label>Rol CRM</label>
                            <select name="id_fk_rol" id="id_fk_rol" class="form-control" required>
                                <?php foreach ($roles as $rol): ?>
                                    <option value="<?= (int) $rol['id'] ?>"><?= esc($rol['description'] ?? $rol['name'] ?? ('Rol ' . $rol['id'])) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Estado</label>
                            <select name="status" id="status" class="form-control">
                                <option value="activo">activo</option>
                                <option value="inactivo">inactivo</option>
                                <option value="suspendido">suspendido</option>
                            </select>
                        </div>
                        <div class="form-group col-md-4">
                            <label>Contraseña <span class="text-muted small" id="pwdHint">(obligatoria al crear)</span></label>
                            <input type="password" name="password" id="password" class="form-control" autocomplete="new-password">
                        </div>
                    </div>
                    <hr>
                    <h6 class="text-primary">Acceso a finanzas</h6>
                    <div class="form-row align-items-center">
                        <div class="form-group col-md-3">
                            <div class="custom-control custom-checkbox mt-2">
                                <input type="checkbox" class="custom-control-input" id="finance_enabled" name="finance_enabled" value="1">
                                <label class="custom-control-label" for="finance_enabled">Habilitar finanzas</label>
                            </div>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Perfil</label>
                            <select name="finance_profile" id="finance_profile" class="form-control">
                                <?php foreach ($finance_profiles as $p): ?>
                                    <option value="<?= esc($p) ?>"><?= esc($p) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <label>Rol finanzas</label>
                            <select name="member_role" id="member_role" class="form-control">
                                <?php foreach ($member_roles as $mr): ?>
                                    <option value="<?= esc($mr) ?>"><?= esc($mr) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="form-group col-md-3">
                            <div class="custom-control custom-checkbox mt-4">
                                <input type="checkbox" class="custom-control-input" id="can_manage_members" name="can_manage_members" value="1">
                                <label class="custom-control-label" for="can_manage_members">Gestiona miembros</label>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary">Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
$(function () {
    var users = [];

    function selectedIds() {
        var ids = [];
        $('.user-select:checked').each(function () {
            ids.push(parseInt($(this).val(), 10));
        });
        return ids;
    }

    function normalizeSearch(s) {
        return (s || '').toString().toLowerCase().normalize('NFD').replace(/[\u0300-\u036f]/g, '');
    }

    function buildSearchHaystack(u) {
        var fin = u.finance && u.finance.is_active == 1
            ? (u.finance.finance_profile || '')
            : '';
        return normalizeSearch([
            u.id,
            u.full_name,
            u.email,
            u.rol_name,
            u.status,
            fin,
            u.is_platform_admin ? 'admin plataforma' : ''
        ].join(' '));
    }

    function userMatchesQuery(u, q) {
        if (!q) {
            return true;
        }
        var haystack = u._searchHaystack || buildSearchHaystack(u);
        return haystack.indexOf(q) !== -1;
    }

    function updateBulkBar() {
        var ids = selectedIds();
        var n = ids.length;
        $('#bulkCount').text(n);
        if (n > 0) {
            $('#bulkBar').removeClass('d-none');
        } else {
            $('#bulkBar').addClass('d-none');
        }
        var selectable = $('#adminUsersTable tbody tr.user-row:visible .user-select').length;
        var checked = $('#adminUsersTable tbody tr.user-row:visible .user-select:checked').length;
        $('#selectAllUsers').prop('checked', selectable > 0 && checked === selectable);
        $('#selectAllUsers').prop('indeterminate', checked > 0 && checked < selectable);
    }

    function renderUsersTable() {
        var $tb = $('#adminUsersTable tbody').empty();
        users.forEach(function (u) {
            u._searchHaystack = buildSearchHaystack(u);
            var fin = u.finance && u.finance.is_active == 1
                ? (u.finance.finance_profile || '—')
                : '—';
            var plat = u.is_platform_admin ? '<span class="badge badge-dark">Admin plataforma</span>' : '';
            var canSelect = u.can_select === true || u.can_select === 1;
            var chk = canSelect
                ? '<input type="checkbox" class="user-select" value="' + u.id + '">'
                : '<span class="text-muted" title="No seleccionable">—</span>';
            $tb.append(
                '<tr class="user-row" data-user-id="' + u.id + '" data-search="' + $('<div>').text(u._searchHaystack).html() + '">' +
                '<td class="text-center align-middle">' + chk + '</td>' +
                '<td>' + $('<div>').text(u.full_name || '').html() + '</td>' +
                '<td>' + $('<div>').text(u.email || '').html() + '</td>' +
                '<td>' + $('<div>').text(u.rol_name || u.id_fk_rol || '').html() + '</td>' +
                '<td>' + $('<div>').text(u.status || '').html() + '</td>' +
                '<td>' + $('<div>').text(fin).html() + '</td>' +
                '<td>' + plat + '</td>' +
                '<td class="text-nowrap">' +
                '<button type="button" class="btn btn-sm btn-outline-primary btn-edit mr-1" data-id="' + u.id + '">Editar</button>' +
                (u.is_platform_admin ? '' : '<button type="button" class="btn btn-sm btn-outline-danger btn-delete" data-id="' + u.id + '">Eliminar</button>') +
                '</td>' +
                '</tr>'
            );
        });
        applySearchFilter();
    }

    function applySearchFilter() {
        var q = normalizeSearch($('#userSearch').val());
        var shown = 0;
        $('#adminUsersTable tbody tr.user-row').each(function () {
            var haystack = normalizeSearch($(this).attr('data-search') || '');
            var match = q === '' || haystack.indexOf(q) !== -1;
            $(this).toggle(match);
            if (match) {
                shown++;
            }
        });

        $('#adminUsersTable tbody tr.no-results-row').remove();
        if (users.length === 0) {
            $('#searchSummary').text('');
        } else if (q) {
            $('#searchSummary').text('Mostrando ' + shown + ' de ' + users.length + ' usuarios');
        } else {
            $('#searchSummary').text(users.length + ' usuarios en total');
        }

        if (q && shown === 0 && users.length > 0) {
            $('#adminUsersTable tbody').append(
                '<tr class="no-results-row"><td colspan="8" class="text-center text-muted py-4">Ningún usuario coincide con la búsqueda.</td></tr>'
            );
        }

        updateBulkBar();
    }

    function loadUsers() {
        $('#selectAllUsers').prop('checked', false).prop('indeterminate', false);
        $.getJSON('<?= base_url('/app/admin/users/api/list') ?>', function (r) {
            if (r.status !== 'success') return;
            users = r.data || [];
            renderUsersTable();
        });
    }

    function openModal(user) {
        $('#userId').val(user ? user.id : '');
        $('#full_name').val(user ? user.full_name : '');
        $('#email').val(user ? user.email : '');
        $('#id_fk_rol').val(user ? user.id_fk_rol : $('#id_fk_rol option:first').val());
        $('#status').val(user ? user.status : 'activo');
        $('#password').val('');
        $('#pwdHint').text(user ? '(dejar vacío para no cambiar)' : '(obligatoria al crear)');

        var fin = user && user.finance;
        var finOn = fin && fin.is_active == 1;
        $('#finance_enabled').prop('checked', finOn);
        if (finOn) {
            $('#finance_profile').val(fin.finance_profile || 'viewer');
            $('#member_role').val(fin.member_role || 'assistant');
            $('#can_manage_members').prop('checked', fin.can_manage_members == 1);
        } else {
            $('#finance_profile').val('viewer');
            $('#member_role').val('assistant');
            $('#can_manage_members').prop('checked', false);
        }

        if (user && user.is_platform_admin) {
            $('#finance_enabled').prop('checked', true).prop('disabled', true);
            $('#finance_profile, #member_role, #can_manage_members').prop('disabled', true);
        } else {
            $('#finance_enabled').prop('disabled', false);
            $('#finance_profile, #member_role, #can_manage_members').prop('disabled', false);
        }

        $('#userModal').modal('show');
    }

    $('#btnNewUser').on('click', function () { openModal(null); });

    $(document).on('click', '.btn-edit', function () {
        var id = parseInt($(this).data('id'), 10);
        var user = users.find(function (u) { return parseInt(u.id, 10) === id; });
        openModal(user || null);
    });

    $(document).on('change', '.user-select', updateBulkBar);

    $('#selectAllUsers').on('change', function () {
        var checked = $(this).is(':checked');
        $('#adminUsersTable tbody tr.user-row:visible .user-select').prop('checked', checked);
        updateBulkBar();
    });

    $('#userSearch').on('input keyup paste search', function () {
        applySearchFilter();
    });

    $('#btnClearSearch').on('click', function () {
        $('#userSearch').val('');
        applySearchFilter();
        $('#userSearch').focus();
    });

    $('#btnBulkDelete').on('click', function () {
        var ids = selectedIds();
        if (ids.length === 0) {
            return;
        }
        if (!confirm('¿Eliminar ' + ids.length + ' usuario(s) seleccionado(s)? Los que tengan datos vinculados quedarán suspendidos. Los administradores de plataforma se omitirán.')) {
            return;
        }
        $.post('<?= base_url('/app/admin/users/api/bulk-delete') ?>', { ids: ids }, function (r) {
            alert(r.message || (r.status === 'success' ? 'Listo' : 'Error'));
            if (r.status === 'success') {
                loadUsers();
            }
        }, 'json').fail(function (xhr) {
            var r = xhr.responseJSON || {};
            alert(r.message || 'Error en eliminación masiva');
        });
    });

    $(document).on('click', '.btn-delete', function () {
        var id = parseInt($(this).data('id'), 10);
        var rowUser = users.find(function (u) { return parseInt(u.id, 10) === id; });
        var name = (rowUser && rowUser.full_name) ? rowUser.full_name : ('#' + id);
        if (!confirm('¿Eliminar al usuario "' + name + '"? Si tiene registros vinculados quedará suspendido.')) {
            return;
        }
        $.post('<?= base_url('/app/admin/users/api/delete') ?>', { id: id }, function (r) {
            alert(r.message || (r.status === 'success' ? 'Listo' : 'Error'));
            if (r.status === 'success') {
                loadUsers();
            }
        }, 'json').fail(function (xhr) {
            var r = xhr.responseJSON || {};
            alert(r.message || 'Error al eliminar');
        });
    });

    $('#userForm').on('submit', function (e) {
        e.preventDefault();
        var $form = $(this);
        $.post('<?= base_url('/app/admin/users/api/save') ?>', $form.serialize(), function (r) {
            alert(r.message || (r.status === 'success' ? 'Guardado' : 'Error'));
            if (r.status === 'success') {
                $('#userModal').modal('hide');
                loadUsers();
            }
        }, 'json').fail(function (xhr) {
            var r = xhr.responseJSON || {};
            alert(r.message || 'Error al guardar');
        });
    });

    loadUsers();
});
</script>
