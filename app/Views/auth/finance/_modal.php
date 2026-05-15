<!-- Shared Finance Modal -->
<div class="modal fade" id="financeModal" tabindex="-1" role="dialog">
    <div class="modal-dialog modal-lg" role="document">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title" id="modalTitle"><?= $title ?? 'Formulario' ?></h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="financeForm">
                <div class="modal-body">
                    <input type="hidden" id="record_id" name="id">
                    <input type="hidden" name="entity" value="<?= $entity ?? '' ?>">
                    <div class="row">
                        <?php foreach ($fields as $f): ?>
                        <div class="col-md-<?= $f['col'] ?? 12 ?>">
                            <div class="form-group">
                                <label for="<?= $f['id'] ?>"><?= $f['label'] ?><?= !empty($f['required']) ? ' <span class="text-danger">*</span>' : '' ?></label>
                                <?php if (($f['type'] ?? 'text') === 'select'): ?>
                                    <select class="form-control" id="<?= $f['id'] ?>" name="<?= $f['id'] ?>" <?= !empty($f['required']) ? 'required' : '' ?>>
                                        <?php if (!empty($f['options'])): ?>
                                            <?php foreach ($f['options'] as $val => $txt): ?>
                                                <option value="<?= $val ?>"><?= $txt ?></option>
                                            <?php endforeach; ?>
                                        <?php elseif (!empty($f['source'])): ?>
                                            <option value="">Cargando...</option>
                                        <?php endif; ?>
                                    </select>
                                <?php elseif (($f['type'] ?? 'text') === 'textarea'): ?>
                                    <textarea class="form-control" id="<?= $f['id'] ?>" name="<?= $f['id'] ?>" rows="3"></textarea>
                                <?php else: ?>
                                    <input type="<?= $f['type'] ?? 'text' ?>" class="form-control" id="<?= $f['id'] ?>" name="<?= $f['id'] ?>" <?= !empty($f['required']) ? 'required' : '' ?> <?= $f['attrs'] ?? '' ?>>
                                <?php endif; ?>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-success"><i class="fas fa-save"></i> Guardar</button>
                </div>
            </form>
        </div>
    </div>
</div>
<script>
$(document).ready(function() {
    // Dynamic dropdown loading from API source
    <?php foreach ($fields as $f): if (!empty($f['source'])): ?>
    $.post('<?= base_url('/app/finance/api/'.$f['source']) ?>', function(r) {
        var sel = $('#<?= $f['id'] ?>').empty().append('<option value="">Seleccionar...</option>');
        if (r.status==='success' && Array.isArray(r.data)) r.data.forEach(function(d) {
            sel.append('<option value="'+d.id+'">'+(d.name||d.code||'')+'</option>');
        });
    });
    <?php endif; endforeach; ?>

    // Submit handler
    $('#financeForm').submit(function(e) {
        e.preventDefault();
        var id = $('#record_id').val();
        var entity = $('[name="entity"]').val();
        var url = '/app/finance/api/'+entity+(id ? '/'+id : '/create');
        $.ajax({
            url: '<?= base_url('/app/finance/api/') ?>'+entity+(id ? '/'+id : '/create'), method:'POST', data: $(this).serialize(), dataType:'json',
            success: function(r) {
                if (r.status==='success') { $('#financeModal').modal('hide'); if (typeof loadTable==='function') loadTable(); }
                else alert('Error: '+(r.message||'Operación fallida'));
            },
            error: function() { alert('Error de conexión'); }
        });
    });
});
</script>