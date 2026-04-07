<div class="card mb-4">
    <div class="card-header py-3">
        <h6 class="m-0 font-weight-bold text-primary"><?= $datatable['title'] ?></h6>
        <p>
            <?= $datatable['description'] ?>
        </p>
    </div>
    <div class="table-responsive p-3">
        <table class="table align-items-center table-flush table-hover w-100 font-size-datatable" id="<?= $datatable['prefix'] ?>table">
        <thead class="thead-light">
            <tr>
                <?php foreach ($datatable['header'] as $item): ?>
                    <th is_filtrable="<?= $item['filtrable'] ?>">
                        <?= $item['name'] ?>
                    </th>
                <?php endforeach ?>
            </tr>
        </thead>
        <tfoot>
            <tr>
                <?php foreach ($datatable['header'] as $item): ?>
                    <th>
                        <?= $item['name'] ?>
                    </th>
                <?php endforeach ?>
            </tr>
        </tfoot>
        <tbody>
        <?php foreach ($datatable['data'] as $key => $data):?>
            <tr>
                <?php  foreach ($data as $item): ?>
                    <td>
                        <?php if (is_array($item)): ?>
                            <?php foreach (is_array($item) ? $item : array() as $dat): ?>
                                <?php if (isset($dat['onclick'])): ?>
                                    <button class="btn <?= isset($dat['class_style']) ? $dat['class_style'] : '' ?> btn-sm" onclick="<?= $dat['onclick'] ?>(<?= $datatable['data'][$key][$dat['pk']] ?>)" type="button">
                                        <?= ucfirst($dat['button_name']); ?>
                                    </button>
                                <?php else: ?>
                                    <a class="btn <?= isset($dat['class_style']) ? $dat['class_style'] : '' ?> btn-sm" href="<?= $dat['url'].$datatable['data'][$key][$dat['pk']] ?>" role="button">
                                        <?= ucfirst($dat['button_name']); ?>
                                    </a>
                                <?php endif; ?>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <?php if(is_array(json_decode($item,true))): ?>
                                <div class="bg-dark rounded m-2 pt-3 pb-1 pl-3 pr-2">
                                    <?php foreach(json_decode($item,true) as $key => $array_item): ?>
                                        <ul>
                                            <li class="text-secondary">
                                                <?= ucfirst($key) ?>: <?= print_r($array_item) ?>
                                            </li>
                                        </ul>
                                    <?php endforeach; ?>
                                </div>
                            <?php else: ?>
                                <div class="d-flex align-items-center"><?=  $item ? ucfirst($item) : '---' ?></div>
                            <?php endif; ?>
                        <?php endif; ?>
                    </td>
                <?php endforeach ?>
            </tr>
            <?php endforeach ?>
        </tbody>
        </table>
    </div>
</div>