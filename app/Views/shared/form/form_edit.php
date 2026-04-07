<!-- content-wrapper start -->
<div class="container-fluid">
<div class="card mb-3 bg-white rounded mt-3 px-3 pt-2 pb-1">
<h4 class="pt-4 pb-4">
    <?= $generate_form['title'] ?>
</h4>
<form action="<?= base_url($generate_form['urlPost']) ?>" method="post">
    <?= csrf_field() ?>
    <input type="text" hidden="" name="form_edit" value="form_edit">
    <?php foreach ($generate_form['data'] as $item): ?>
        <?php if ($item['type'] === 'select'): ?>
        <div class="form-group pb-2 pt-2">
            <label for="<?= $item['name'] ?>"><?= $item['label'] ?></label>
            <select class="form-control" name="<?= $item['name'] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>> 
                <option value="">
                    Seleccionar...
                </option>
                <?php foreach ($item['options_model'] as $option): ?>
                    <option 
                    value="<?= $option['id'] ?>"
                    <?php if($option['name'] == $generate_form['model_form'][$item['selected']]): ?>
                        selected=""
                    <?php endif; ?>
                    >
                        <?= ucfirst($option['name']) ?>
                    </option>
                <?php endforeach ?>
            </select>
        </div>
        <?php elseif($item['type'] === 'textarea'): ?>
        <div class="form-group pb-2 pt-2">
        <label for="<?= $item['name'] ?>"><?= $item['label'] ?></label>
            <textarea class="form-control" rows="5" name="<?= $item['name'] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>><?= $generate_form['model_form'][$item['name']] ?></textarea>
        </div>
        <?php else: ?>
            <div class="form-group pb-2 pt-2">
                <label for="<?= $item['name'] ?>"><?= $item['label'] ?></label>
                <input <?= $item['type'] == 'number' ? 'step="0.01"' : '' ?> type="<?= $item['type'] ?>" class="form-control form-control-sm rounded" name="<?= $item['name'] ?>" placeholder="<?= $item['placeholder'] ?>" value="<?= $generate_form['model_form'][$item['name']] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>>
            </div>
        <?php endif; ?>
    <?php endforeach ?>
    <div class="modal-footer pt-4 pb-3">
        <button type="submit" class="btn btn-success w-100">Guardar</button>
    </div>
</form>
</div>
</div>
<!-- content-wrapper ends -->