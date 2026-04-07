<div>
    <div>
        <h5> 
            📌 La propiedad tiene <?= $data['meters_construction'] ?> m² de construcción 
            <?php if($data['meters_land'] > 1): ?>
                sobre un terreno de <?= $data['meters_land'] ?> m²
            <?php endif; ?>
            distribuidos en:
        </h5>
        <ul> 
        <li>
            <?php if($data['bedrooms'] != 0): ?>
                <?= $data['bedrooms'] ?> <?= $data['bedrooms'] > 1 ? 'Habitaciones' : 'Habitacion'?>.
            <?php endif; ?>
        </li>
        <li>
            <?php if($data['bathrooms'] != 0): ?>
                <?= $data['bathrooms'] ?> <?= $data['bathrooms'] > 1 ? 'Baños' : 'Baño'?>.
            <?php endif; ?>
        </li>
        <?php foreach($aceas as $value): ?>
            <?php if($value['acea'] == 1): ?>
                <?php if(in_array($value['id_acea'], explode(",", $property_data['environments']))): ?>
                <li>
                    <?= ucfirst($value['name']) ?>.
                </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        </ul>
    </div>
    <hr>
    <div>
        <h5> 📌 Comodidades del inmueble: </h5>
        <ul>
        <?php foreach($aceas as $value): ?>
            <?php if($value['acea'] == 4): ?>
                <?php if(in_array($value['id_acea'], explode(",", $property_data['amenities']))): ?>
                <li>
                    <?= ucfirst($value['name']) ?>.
                </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        </ul>
    </div>
    <hr>
    <div>
        <h5> 📌 Exteriores del inmueble: </h5>
        <ul>
        <?php foreach($aceas as $value): ?>
            <?php if($value['acea'] == 5): ?>
                <?php if(in_array($value['id_acea'], explode(",", $property_data['exterior']))): ?>
                <li>
                    <?= ucfirst($value['name']) ?>.
                </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        </ul>
    </div>
    <hr>
    <div>
        <h5> 📌 Adyacencias del inmueble: </h5>
        <ul>
        <?php foreach($aceas as $value): ?>
            <?php if($value['acea'] == 7): ?>
                <?php if(in_array($value['id_acea'], explode(",", $property_data['adjacencies']))): ?>
                <li>
                    <?= ucfirst($value['name']) ?>.
                </li>
                <?php endif; ?>
            <?php endif; ?>
        <?php endforeach; ?>
        </ul>
    </div>
    <hr>
    <div>
        <p>
        <?php if(!$data['garages'] == 0): ?>
            <span style="font-weight: bold;">
                🚗 Estacionamiento:
            </span> 
            <?= $data['garages'] ?> <?= $data['garages'] > 1 ? 'puestos' : 'puesto'?>.
        <?php endif; ?>
        </p>
    </div>
    <hr>
    <div>
        <h5> 📌 Las condiciones de negocio son: </h5>
        <ul>
        <?php if(!empty($property_data['business_conditions'])): ?>
            <?php foreach($business_conditions as $value): ?>
                <?php if(in_array($value['id'], explode(",", $property_data['business_conditions']))): ?>
                    <li>
                        <?= ucfirst($value['name']) ?>.
                    </li>
                <?php endif; ?>
            <?php endforeach; ?>
        <?php endif; ?>
        </ul>
    </div>
    <div>
        <p>
            <span style="font-weight: bold;">
                💰 Precio de <?= $data['businessmodel_name'] ?>:
            </span> 
            $<?= number_format($data['price']) ?>
        </p>
    </div>
    <?php if($data['price_additional'] > 0): ?>
        <div>
            <p>
                <span style="font-weight: bold;">
                    💰 Precio de alquiler:
                </span> 
                $<?= number_format($data['price_additional']) ?>
            </p>
        </div>
    <?php endif; ?>
</div>