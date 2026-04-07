<div class="row">
<div class="col-md-12 pt-3 pb-3">
    <div class="shadow-sm mb-3 bg-white rounded pt-3 pb-2 pr-3 pl-3">
        <?= view('shared/search/properties'); ?>
    </div>
</div>
<div class="col-md-12">
    <div class="page-header flex-wrap mb-0">
        <p>
            Página <?= $pager->getCurrentPage() ?> de <?= $pager->getPageCount() ?>
        </p>
        <p>
            <?= $pager->getTotal() ?> Propiedades
        </p>
    </div>
</div>
<?php if(!empty($data_catalogue)): ?> 
<?php 
helper('url');
helper('filesystem');
foreach($data_catalogue AS $row): 
$files = directory_map(FCPATH.'properties/RM00'.$row['id_properties'].'/graphic/');
?>
    <div class="col-md-12 pt-0 pb-3" data-aos="zoom-out">
        <div class="shadow-sm mb-3 bg-white rounded">
            <div class="row ">
                <div class="col-md-4">
                    <?php $items = []; ?>

                    <?php foreach ($images as $key => $file): ?>
                        <?php if ($file['property_id'] == $row['id_properties']): ?>
                            <?php array_push($items, $file['image']); ?>
                        <?php endif; ?>
                    <?php endforeach; ?>

                    
                    <img class="w-100 image-card-style" src="<?= empty($items[0]) ? '/assets/images/recurso-no-encontrado.jpg' : '/properties/RM00'.$row['id_properties'].'/graphic/'.$items[0] ?>">
                </div>
                <div class="col-md-8 px-3">
                    <div class="card-block px-3 pt-3">
                    <div class="page-header flex-wrap mb-0">
                        <div class="d-flex flex-wrap text-catalogue-lineal">
                            <div class="pt-2">
                                <a class="card-title mb-0" href="<?= !empty(session()->get('id')) ? '/properties/view_property/'.$row['id_properties'] : '/visitor/properties/view_property/'.$row['id_properties'] ?>" target="_blank">
                                    <?= mb_strtoupper($row['housingtype_name'], 'UTF-8') ?> | <?= mb_strtoupper($row['address'], 'UTF-8') ?>
                                </a>
                                <p class="mt-0 mb-3 text-muted">
                                Municipio <?= ucwords($row['municipality_name']) ?> del <?= $row['state_name'] === 'Distrito Capital' ? '' : 'Estado' ?> <?= ucwords($row['state_name']) ?>.
                                </p>
                            </div>
                        </div>
                        <div class="d-flex flex-wrap">
                            <span class="bg-dark p-1 font-12 rounded text-white mr-1">
                                Mercado <?= ucwords($row['markettype_name']) ?>
                            </span>
                            <span class="bg-dark p-1 font-12 rounded text-white ml-1">
                                RM00<?= $row['id_properties'] ?>
                            </span>
                        </div>
                    </div>
                    <hr class="mb-1">
                    <p class="card-text mb-0"><i class="text-secondary fa fa-arrows-alt" aria-hidden="true"></i> <?= $row['meters_construction'] ?> m² <i class="text-secondary fa fa-bed pl-3" aria-hidden="true"></i> <?= $row['bedrooms'] ?> hab <i class="text-secondary fa fa-bath pl-3" aria-hidden="true"></i> <?= $row['bathrooms'] ?> Baños <i class="text-secondary fa fa-car pl-3" aria-hidden="true"></i> <?= $row['garages'] ?> Estac.</p>
                    <hr class="mt-1">
                    <p class="card-text">
                        En <?= $row['businessmodel_name'] ?> | $<?= $row['price'] > 0 ? number_format($row['price']) : number_format($row['price_additional']) ?>
                    </p>
                    <p class="card-text">
                        Asesor inmobiliario: <?= ucwords($row['name_agent']) ?>
                    </p>
                    <div class="row mt-8 mb-2">
                        <div class="col-md-8 px-2 pt-2">
                            <a href="<?= !empty(session()->get('id')) ? '/properties/view_property/'.$row['id_properties'] : '/visitor/properties/view_property/'.$row['id_properties'] ?>" class="btn btn-dark btn-sm w-100" target="_blank">
                                <i class="fa fa-external-link-square pr-2" aria-hidden="true"></i> Ver propiedad
                            </a>
                        </div>
                        <div class="col-md-4 px-2 pt-2">
                            <!-- Button trigger modal -->
                            <button type="button" class="btn btn-success btn-sm w-100" data-toggle="modal" data-target="#shared_<?= $row['id_properties'] ?>">
                                <i class="fa fa-share-square pr-2" aria-hidden="true"></i> Compartir
                            </button>
                        </div>
                    </div>
                    </div>
                </div>

                </div>
            </div>
        </div>   

        <!-- Modal -->
        <div class="modal fade" id="shared_<?= $row['id_properties'] ?>" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
        <div class="modal-dialog" role="document">
            <div class="modal-content">
            <div class="modal-body">
                <div class="row">
                    <div class="col-md-12">
                        <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                            <span aria-hidden="true">&times;</span>
                        </button>
                        <h5 class="modal-title text-center" id="exampleModalLabel">Compartir RM00<?= $row['id_properties'] ?></h5>
                        <p class="text-center">
                            Comparte las propiedades de una mejor manera, elige la formas que mejor te parezca. 
                        </p>
                        <hr>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-center">
                            <a href="?shared-whatsapp=<?= $row['id_properties'] ?>" class="btn btn-success btn-rounded btn-icon" target="_blank">
                                <i class="fa fa-whatsapp pt-2" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="d-flex justify-content-center">
                            <span>
                                WA corto
                            </span>
                        </div>
                    </div>
                    <div class="col-md-4">
                        <div class="d-flex justify-content-center">
                            <a href="?shared-whatsapp-complete=<?= $row['id_properties'] ?>" class="btn btn-success btn-rounded btn-icon" target="_blank">
                                <i class="fa fa-whatsapp pt-2" aria-hidden="true"></i>
                            </a>
                        </div>
                        <div class="d-flex justify-content-center">
                            <span>
                                WA completo
                            </span>
                        </div>
                    </div>



                    <?php if(!empty($data_wasi)): ?>
                    <div class="col-md-12">
                        <hr>
                    </div>
                    <div class="col-md-12">
                        <div class="d-flex align-items-center">
                            <div>
                                <img src="/assets/images/icon_web/wasi-logo.png" width="50px" alt="wasi-logo">
                            </div>
                            <div class="pl-3">
                                <?php 
                                    // Suponiendo que $array es el array con más de 100 registros
                                    $found = false;

                                    foreach ($data_wasi as $wasi) {
                                        if ($wasi['property_id'] == $row['id_properties']) {
                                            $found = true;

                                            echo '<a href="'.str_replace('asesoresrm.inmo.co', 'info.wasi.co', $wasi['link_wasi']).'" target="_blank">'.str_replace('asesoresrm.inmo.co', 'info.wasi.co', $wasi['link_wasi']).'</a>';

                                            break; // Salir del bucle una vez que se encuentra el registro
                                        }
                                    }

                                    if (!$found) {
                                        echo "No posee link.";
                                    }
                                ?>
                            </div>
                        </div>
                    </div>
                    <?php endif; ?>

                    
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-dark w-100" data-dismiss="modal">Cancelar</button>
            </div>
            </div>
        </div>
        </div>
    <?php endforeach; ?>    
    <?php else: ?>   
        <div class="col-md-12">
            <h1>No hay datos para mostrar.</h1>
        </div>
    <?php endif; ?>            
</div>
<?= $pager->links() ?>