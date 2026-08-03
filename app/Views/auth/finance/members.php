<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-users-cog text-primary"></i> Miembros financieros</h1>
    </div>
    <div class="card shadow mb-4">
        <div class="card-header py-3"><h6 class="m-0 font-weight-bold text-primary">Matriz de permisos</h6></div>
        <div class="card-body">
            <p class="text-muted small mb-3">
                <strong>loader</strong> (Julia): carga ingresos/egresos ·
                <strong>approver</strong> (Rosana): aprueba ·
                <strong>viewer</strong> (Liliana): solo reportes ·
                <strong>full</strong>: acceso total
            </p>
            <table class="table table-bordered table-sm">
                <thead class="thead-light"><tr><th>Usuario</th><th>Rol CRM</th><th>Perfil finanzas</th><th>Activo</th></tr></thead>
                <tbody>
                <?php
                $userMap = [];
                foreach ($users as $u) { $userMap[$u['id']] = trim(($u['name'] ?? '') . ' ' . ($u['lastname'] ?? '')); }
                foreach ($members as $m):
                ?>
                <tr>
                    <td><?= esc($userMap[$m['user_id']] ?? ('#' . $m['user_id'])) ?></td>
                    <td><?= esc($m['member_role']) ?></td>
                    <td><span class="badge badge-info"><?= esc($m['finance_profile'] ?? 'full') ?></span></td>
                    <td><?= ! empty($m['is_active']) ? 'Sí' : 'No' ?></td>
                </tr>
                <?php endforeach; ?>
                </tbody>
            </table>
            <hr>
            <h6>Agregar / actualizar miembro</h6>
            <form id="memberForm" class="form-row">
                <div class="form-group col-md-2"><label>Usuario ID</label><input name="user_id" class="form-control" required></div>
                <div class="form-group col-md-2"><label>Rol</label>
                    <select name="member_role" class="form-control"><option value="assistant">assistant</option><option value="admin">admin</option><option value="owner">owner</option></select>
                </div>
                <div class="form-group col-md-3"><label>Perfil</label>
                    <select name="finance_profile" class="form-control">
                        <option value="loader">loader (carga)</option>
                        <option value="approver">approver (aprueba)</option>
                        <option value="viewer">viewer (reportes)</option>
                        <option value="full">full</option>
                    </select>
                </div>
                <div class="form-group col-md-2 align-self-end"><button type="submit" class="btn btn-primary btn-block">Guardar</button></div>
            </form>
        </div>
    </div>
</div>
<script>
$('#memberForm').on('submit', function(e) {
    e.preventDefault();
    $.post('<?= base_url('/app/finance/members/save') ?>', $(this).serialize(), function(r) {
        alert(r.status === 'success' ? 'Guardado' : 'Error');
        if (r.status === 'success') location.reload();
    }, 'json');
});
</script>
