<div class="row">
    <div class="col-sm-12 pb-4">
        <div class="card">
            <div class="card-body">
                <div class="row">
                    <div class="col-md-3">
                        <span>Código: RM00<?= $property_data['id_properties'] ?></span>
                    </div>
                    <div class="col-md-3">
                        <span>Agente: <?= $property_data['name_agent'] ?></span>
                    </div>
                    <div class="col-md-3">
                        <span>Estatus: <?= $property_data['status_name'] ?></span>
                    </div>
                    <div class="col-md-3">
                        <span>Porcentaje: <?= $property_data['percentage'] ?? 'Sin porcentaje' ?></span>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <div class="col-sm-8">
        <div class="card">
            <div class="card-body">
                <h4 class="pt-4">
                    Imágenes
                </h4>
                <form method="POST" enctype="multipart/form-data" action="<?= base_url('/app/my_properties/upload_images/'.$id_property) ?>">
                    <?= csrf_field() ?>
                    <div class="d-flex">
                        <div class="form-group w-100">
                            <input type="file" class="border w-100 rounded-0" name="graphic[]" id="input_graphic" accept="image/*" multiple>
                        </div>
                        <button type="submit" class="btn btn-success btn-sm h-25 w-25 rounded-0">
                            <i class="fa fa-upload"></i> Cargar
                        </button>
                    </div>
                </form>
                <form action="<?= base_url('/app/my_properties/update_position_images/'.$id_property) ?>" method="POST">
                <?= csrf_field() ?>
                    <div class="row pt-3">
                        <?php foreach ($graphics as $key => $graphic): ?>
                            <div class="col-md-4 pb-3">
                                <img id="graphic" src="<?= '/properties/RM00'.$id_property.'/graphic/'.$graphic['image'] ?>" class="img-property-grid" alt="Asesores RM">
                                <div class="input-group input-group-sm mt-0">
                                    <div class="input-group-prepend">
                                        <span class="input-group-text rounded-0" id="inputGroup-sizing-sm">
                                            Número de puesto
                                        </span>
                                    </div>
                                    <input value="<?= $key + 1 ?>" name="position_<?= $key + 1 ?>" type="number" class="form-control rounded-0" aria-label="Small" aria-describedby="inputGroup-sizing-sm">
                                    <input value="<?= $graphic['image'] ?>" name="image_name_<?= $key + 1 ?>" type="text" hidden="">
                                </div>
                                <div class="row mb-3">
                                    <div class="col-md-6">
                                        <a 
                                        href="<?= '/app/my_properties/delete_image/'.$id_property.'/'.base64_encode('/properties/RM00'.$id_property.'/graphic/'.$graphic['image'].'/'.$graphic['id']) ?>" 
                                        class="btn btn-danger btn-sm w-100 rounded-0" >
                                            <i class="fa fa-trash" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                    <div class="col-md-6">
                                        <a 
                                        href="<?= '/properties/RM00'.$id_property.'/graphic/'.$graphic['image']?>" 
                                        class="btn btn-success btn-sm w-100 rounded-0" 
                                        download="<?= $graphic['image'] ?>">
                                            <i class="fa fa-download" aria-hidden="true"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                        <i class="fa fa-wrench"></i> Guardar puestos
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm-4">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="pt-4">
                    Documentación
                </h4>
                <form method="POST" enctype="multipart/form-data" action="<?= base_url('/app/my_properties/upload_documentary/'.$id_property) ?>">
                    <?= csrf_field() ?>
                    <div class="d-flex">
                        <div class="form-group w-100">
                            <input type="file" class="border w-100 rounded-0" name="documentary" id="input_documentary">
                        </div>
                        <button type="submit" class="btn btn-success btn-sm h-25 w-25 rounded-0">
                            <i class="fa fa-upload"></i> Cargar
                        </button>
                    </div>
                </form>
                <div class="row">
                <?php foreach ($documentarys as $key => $documentary): ?>
                    <div class="col-md-12 p-3">
                        <div class="row">
                            <div class="col-md-12">
                                <div class="d-flex justify-content-center bg-dark text-white p-5 rounded">
                                    <i class="fa fa-file-text pr-2" aria-hidden="true"></i>
                                    Documento
                                </div>
                            </div>
                            <div class="col-md-12">
                                <p>
                                    Nombre: <?= $documentary ?>
                                </p>
                            </div>
                            <div class="col-md-6 p-0">
                                <a 
                                href="<?= '/properties/RM00'.$id_property.'/documentary/'.$documentary?>" 
                                class="btn btn-success btn-sm w-100 rounded-0" 
                                download="<?= $documentary ?>">
                                    <i class="fa fa-download" aria-hidden="true"></i>
                                </a>
                            </div>
                            <div class="col-md-6 p-0">
                                <?= view('shared/destroy_material/destructor_file', [
                                    'url_post_destroy' => '/app/my_properties/delete_documentary/'.$id_property,
                                    'directory_file_destroy' => '/properties/RM00'.$id_property.'/documentary/'.$documentary
                                ]); ?>
                            </div>
                        </div>
                    </div>
                    <hr class="w-100">
                <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>
<div class="row">
    <div class="col-md-12 pt-4">
        <div class="card">
            <div class="card-body">
                <h4 class="pt-4">
                    Mapa de google maps
                </h4>
                <form method="POST" action="<?= base_url('/app/my_properties/map_update/'.$id_property.'/map_update') ?>">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="map_coordinates">longitud y latitud</label>
                        <input type="text" class="form-control form-control-sm rounded" name="map_coordinates" placeholder="longitud y latitud" value="<?= $property_data['map_coordinates'] ?>" <?php if(isset($item['required'])): ?><?= $item['required'] ? 'required': '' ?><?php endif; ?>>
                    </div>
                    <?php if(isset($property_data['map_coordinates'])): ?>
                        <iframe class="w-100" src="https://www.google.com/maps?q=<?= $latitud ?>,<?= $longitud ?>&output=embed" width="600" height="450" frameborder="0" style="border:0" allowfullscreen></iframe>
                    <?php endif; ?>
                    <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                        <i class="fa fa-wrench"></i> Guardar
                    </button>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm pt-4">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="pt-4"> A.C.E.A. </h4>
                <p> El presente gestor actua como un motor para la generación de memorias descriptivas, constituye en gran parte la información visible. </p>
                <form action="<?= base_url('/app/my_properties/acea_update/'.$id_property.'/acea') ?>" method="post"> <?= csrf_field() ?>
                
                <div class="pt-3">
                    <h3>Ambientes</h3>
                    <div class="row">
                    <?php foreach($aceas as $value): ?>
                            <?php if($value['acea'] == 1): ?>
                            <div class="col-md-4">
                                <div class="form-check form-check-primary">
                                    <label class="form-check-label">
                                        <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        value="<?= $value['id_acea'] ?>" 
                                        <?php if(in_array($value['id_acea'], explode(",", $property_data['environments']))): ?>
                                        checked=""
                                        <?php endif; ?>
                                        name="environments[]"> 
                                        <?= $value['name'] ?> <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                </div>
                <hr>
                <div class="pt-3">
                    <h3>Comodidades</h3>
                    <div class="row">
                    <?php foreach($aceas as $value): ?>
                            <?php if($value['acea'] == 4): ?>
                            <div class="col-md-4">
                                <div class="form-check form-check-primary">
                                    <label class="form-check-label">
                                        <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        value="<?= $value['id_acea'] ?>"
                                        <?php if(in_array($value['id_acea'], explode(",", $property_data['amenities']))): ?>
                                        checked=""
                                        <?php endif; ?>
                                        name="amenities[]"> 
                                        <?= $value['name'] ?>  <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                </div>
                <hr>
                <div class="pt-3">
                    <h3>Exteriores</h3>
                    <div class="row">
                    <?php foreach($aceas as $value): ?>
                            <?php if($value['acea'] == 5): ?>
                            <div class="col-md-4">
                                <div class="form-check form-check-primary">
                                    <label class="form-check-label">
                                        <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        value="<?= $value['id_acea'] ?>" 
                                        <?php if(in_array($value['id_acea'], explode(",", $property_data['exterior']))): ?>
                                        checked=""
                                        <?php endif; ?>
                                        name="exterior[]"> 
                                        <?= $value['name'] ?>  <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                </div>
                <hr>
                <div class="pt-3">
                    <h3>Adyacencias</h3>
                    <div class="row">
                    <?php foreach($aceas as $value): ?>
                            <?php if($value['acea'] == 7): ?>
                            <div class="col-md-4">
                                <div class="form-check form-check-primary">
                                    <label class="form-check-label">
                                        <input 
                                        type="checkbox" 
                                        class="form-check-input" 
                                        value="<?= $value['id_acea'] ?>" 
                                        <?php if(in_array($value['id_acea'], explode(",", $property_data['adjacencies']))): ?>
                                        checked=""
                                        <?php endif; ?>
                                        name="adjacencies[]"> 
                                        <?= $value['name'] ?>  <i class="input-helper"></i>
                                    </label>
                                </div>
                            </div>
                            <?php endif; ?>
                    <?php endforeach; ?>
                    </div>
                </div>
                <hr>
                <div class="pt-3 pb-2">
                    <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                        <i class="fa fa-wrench"></i> Guardar
                    </button>
                </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-sm pt-4">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="pt-4">
                    Información
                </h4>
                <form action="<?= base_url('/app/my_properties/property_update/'.$id_property.'/property') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <?= form_label('Tipo de área', 'area_type'); ?>
                        <?= form_dropdown('area_type', array_column($area_type, 'name', 'id'), $property_data['area_type'] ?? '', 'class="select2-single form-control" id="select2Single_area_type" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Tipo de vivienda', 'housing_type'); ?>
                        <?= form_dropdown('housing_type', array_column($housing_type, 'name', 'id'), $property_data['housing_type'] ?? '', 'class="select2-single form-control" id="select2Single_housing_type" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Modelo de negocio', 'business_model'); ?>
                        <?= form_dropdown('business_model', array_column($business_model, 'name', 'id'), $property_data['business_model'] ?? '', 'class="select2-single form-control" id="select2Single_business_model" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Tipo de mercado', 'market_type'); ?>
                        <?= form_dropdown('market_type', array_column($market_type, 'name', 'id'), $property_data['market_type'] ?? '', 'class="select2-single form-control" id="select2Single_market_type" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Habitaciones', 'bedrooms'); ?>
                        <?= form_input('bedrooms', $property_data['bedrooms'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Baños', 'bathrooms'); ?>
                        <?= form_input('bathrooms', $property_data['bathrooms'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Puestos de estacionamiento', 'garages'); ?>
                        <?= form_input('garages', $property_data['garages'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Construcción m²', 'meters_construction'); ?>
                        <?= form_input('meters_construction', $property_data['meters_construction'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Terreno m²', 'meters_land'); ?>
                        <?= form_input('meters_land', $property_data['meters_land'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Estado', 'state'); ?>
                        <?= form_dropdown('state', array_column($state, 'name', 'id'), $property_data['state'] ?? '', 'class="select2-single form-control" id="select2Single_state" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Municipio', 'municipality'); ?>
                        <?= form_dropdown('municipality', array_column($municipality, 'name', 'id'), $property_data['municipality'] ?? '', 'class="select2-single form-control" id="select2Single_municipality" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Ciudad/Zona:', 'city'); ?>
                        <?= form_dropdown('city', array_column($city, 'name', 'id'), $property_data['city'] ?? '', 'class="select2-single form-control" id="select2Single_city" required') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Dirección (30 Caracteres)', 'address'); ?>
                        <?= form_input('address', $property_data['address'] ?? '', 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Precio para venta', 'price'); ?>
                        <?= form_input('price', $property_data['price'] ?? '', 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Precio de alquiler', 'price_additional'); ?>
                        <?= form_input('price_additional', $property_data['price_additional'] ?? '', 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Propietario', 'owner'); ?>
                        <?= form_input('owner', $property_data['owner'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Correo del propietario', 'owner_mail'); ?>
                        <?= form_input('owner_mail', $property_data['owner_mail'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="form-group">
                        <?= form_label('Teléfono del propietario', 'owner_phone'); ?>
                        <?= form_input('owner_phone', $property_data['owner_phone'], 'class="form-control rounded"') ?>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                            <i class="fa fa-wrench"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="col-md-12 pt-4">
        <div class="card h-100">
            <div class="card-body">
                <h4 class="pt-4">
                    Captador/a
                </h4>
                <form action="<?= base_url('/app/my_properties/property_update/'.$id_property.'/property') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <?=  form_label('Agente', 'agent'); ?>
                        <?= form_dropdown('agent', array_column($agents, 'full_name', 'id'), $property_data['id_agent'] ?? '', 'class="select2-single form-control" id="select2Single_agents" required') ?>
                    </div>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                            <i class="fa fa-wrench"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    <div class="row pt-4">
        <div class="col-sm-12">
            <div class="card">
                <div class="card-body">
                    <div class="d-flex justify-content-between">
                        <h4 class="pt-4">
                            Condiciones de negocio
                        </h4>
                    </div>
                    <hr>
                    <form action="<?= base_url('/app/my_properties/business_conditions_update/'.$id_property.'/business_conditions') ?>" method="post">
                        <?= csrf_field() ?>
                        <div class="pt-3">
                            <div class="row">
                                <?php foreach($business_conditions as $value): ?>
                                    <div class="col-md-4">
                                        <div class="form-check form-check-primary">
                                            <label class="form-check-label">
                                                <input 
                                                type="checkbox" 
                                                class="form-check-input" 
                                                value="<?= $value['id'] ?>" 
                                                <?php if(in_array($value['id'], explode(",", $property_data['business_conditions']))): ?>
                                                checked=""
                                                <?php endif; ?>
                                                name="business_conditions[]"> 
                                                <?= $value['name'] ?> <i class="input-helper"></i>
                                            </label>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                            <hr>
                            <div class="pt-3 pb-2">
                                <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                                    <i class="fa fa-wrench"></i> Guardar
                                </button>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    <div class="col-md-12 pt-4">
        <div class="card w-100">
            <div class="card-body">
                <h4 class="pt-4 pb-4">
                    Calificación
                </h4>
                <form action="<?= base_url('/app/statements/rate_property/'.$id_property) ?>" method="post">
                    <?= csrf_field() ?>
                    <?php foreach($status as $value): ?>
                        <?php if($value['id'] != 2): ?>
                            <div class="col-md-4">
                            <div class="form-check form-check-primary">
                                <label class="form-check-label">
                                    <input 
                                    type="radio" 
                                    class="form-check-input" 
                                    value="<?= $value['id'] ?>" 
                                    <?php if($value['name'] === $property_data['status_name']): ?>
                                    checked=""
                                    <?php endif; ?>
                                    name="status"> 
                                    <?= ucfirst($value['name']) ?> <i class="input-helper"></i>
                                </label>
                            </div>
                        </div>
                        <?php endif; ?>
                    <?php endforeach; ?>
                    <div class="modal-footer">
                        <button type="submit" class="btn btn-primary btn-sm h-25 w-100 rounded-0">
                            <i class="fa fa-wrench"></i> Guardar
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>