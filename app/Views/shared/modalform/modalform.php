
<div class="header-right ">
    <button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#<?= $modalform['prefix'] ?>">
        <i class="fa fa-plus" aria-hidden="true"></i> <?= $modalform['title'] ?>
    </button>
</div>
<!-- Modal -->
<div class="modal fade" id="<?= $modalform['prefix'] ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
        <div class="modal-header">
            <h5 class="modal-title" id="exampleModalLabel"> <?= $modalform['title'] ?></h5>
            <button type="button" class="close" data-dismiss="modal" aria-label="Close">
            <span aria-hidden="true">&times;</span>
            </button>
        </div>
        <div class="modal-body">
        <form action="<?= base_url($modalform['urlPost']) ?>" method="post" enctype="multipart/form-data">
            <?= csrf_field() ?>
            <?php foreach ($modalform['data'] as $item): ?>
                <?php if ($item['type'] === 'select'): ?>
                <div class="form-group">
                    <label for="<?= $item['name'] ?>"><?= $item['label'] ?></label>
                    <select class="form-control" name="<?= $item['name'] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>>
                        <option value="">
                            Seleccionar...
                        </option>
                        <?php foreach ($item['options_model'] as $option): ?>
                            <option value="<?= $option['id'] ?>">
                                <?= ucfirst($option['name']) ?>
                            </option>
                        <?php endforeach ?>
                    </select>
                </div>
                <?php elseif($item['type'] === 'file_multiple'): ?>
                    <div class="form-group">
                        <label for="<?= $item['name'] ?>"><?= $item['label'] ?></label>
                        <input multiple <?= $item['type'] == 'number' ? 'step="0.01"' : '' ?>  type="file" class="form-control form-control-sm rounded" name="<?= $item['name'] ?>" placeholder="<?= $item['placeholder'] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>>
                    </div>
                <?php elseif($item['type'] === 'textarea'): ?>
                    <div class="form-group">
                    <label for="<?= $item['name'] ?>"><?= $item['label'] ?></label>
                        <textarea class="form-control" rows="5" name="<?= $item['name'] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>></textarea>
                    </div>
                <?php else: ?>
                    <div class="form-group">
                        <label for="<?= $item['name'] ?>"><?= $item['label'] ?></label>
                        <input <?= $item['type'] == 'number' ? 'step="0.01"' : '' ?>  type="<?= $item['type'] ?>" class="form-control form-control-sm rounded" name="<?= $item['name'] ?>" placeholder="<?= $item['placeholder'] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>>
                    </div>
                <?php endif; ?>
            <?php endforeach ?>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark" data-dismiss="modal">Cancelar</button>
                <button type="submit" class="btn btn-outline-success">Aceptar</button>
            </div>
        </form>
        </div>
        </div>
    </div>
</div>