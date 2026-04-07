<button type="button" class="btn btn-primary mb-3" data-toggle="modal" data-target="#add_property">
    <i class="fa fa-plus" aria-hidden="true"></i> Declarar captación
</button>
<?= view('shared/datatable/datatable'); ?>

<!-- Modal -->
<div class="modal fade" id="add_property" tabindex="-1" role="dialog" aria-labelledby="exampleModalLabel" aria-hidden="true">
    <div class="modal-dialog" role="document">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="exampleModalLabel">Declarar captación</h5>
                <button type="button" class="close" data-dismiss="modal" aria-label="Close">
                <span aria-hidden="true">&times;</span>
                </button>
            </div>
            <div class="modal-body">
                <form action="<?= base_url('/app/my_properties/create') ?>" method="post">
                    <?= csrf_field() ?>
                    <div class="form-group">
                        <label for="area_type">Tipo de área</label>
                        <select class="form-control" name="area_type" required="">
                            <option value=""> Seleccionar... </option>
                            <?php foreach($area_type as $area_type_row): ?>
                                <option value="<?= $area_type_row['id'] ?>"><?= $area_type_row['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="housing_type">Tipo de vivienda</label>
                        <select class="form-control" name="housing_type" required="">
                            <option value=""> Seleccionar... </option>
                            <?php foreach($housing_type as $housing_type_row): ?>
                                <option value="<?= $housing_type_row['id'] ?>"><?= $housing_type_row['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="business_model">Modelo de negocio</label>
                        <select class="form-control" name="business_model" required="">
                            <option value=""> Seleccionar... </option>
                            <?php foreach($business_model as $business_model_row): ?>
                                <option value="<?= $business_model_row['id'] ?>"><?= $business_model_row['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="market_type">Tipo de mercado</label>
                        <select class="form-control" name="market_type" required="">
                            <option value=""> Seleccionar... </option>
                            <?php foreach($market_type as $market_type_row): ?>
                                <option value="<?= $market_type_row['id'] ?>"><?= $market_type_row['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="bedrooms">Habitaciones</label>
                        <input step="0.01" type="number" class="form-control rounded" name="bedrooms" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="bathrooms">Baños</label>
                        <input step="0.01" type="number" class="form-control rounded" name="bathrooms" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="garages">Puestos de estacionamiento</label>
                        <input step="0.01" type="number" class="form-control rounded" name="garages" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="meters_construction">Construcción m²</label>
                        <input step="0.01" type="number" class="form-control rounded" name="meters_construction" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="meters_land">Terreno m²</label>
                        <input step="0.01" type="number" class="form-control rounded" name="meters_land" placeholder="0">
                    </div>
                    <div class="form-group">
                        <label for="state">Estado</label>
                        <select class="form-control" name="state" id="state" required="">
                            <option value=""> Seleccionar... </option>
                            <?php foreach($state as $state_row): ?>
                                <option value="<?= $state_row['id'] ?>"><?= $state_row['name'] ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="municipality">Municipio</label>
                        <select class="form-control" name="municipality" id="municipality" required="">
                            <option value="">Selecciona un estado</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="city">Ciudad/Zona</label>
                        <select class="form-control" name="city" id="city" disabled="" required="">
                            <option value="">Selecciona un municipio</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label for="address">Dirección (30 Caracteres)</label>
                        <input type="text" class="form-control rounded" name="address" placeholder="Ej: Av. San Martin" required="" maxlength="30">
                    </div>
                    <div class="form-group">
                        <label for="price">Precio para venta</label>
                        <input step="0.01" type="number" class="form-control rounded" name="price" placeholder="000000">
                    </div>
                    <div class="form-group">
                        <label for="price_additional">Precio de alquiler</label>
                        <input step="0.01" type="number" class="form-control rounded" name="price_additional" placeholder="000000">
                    </div>
                    <div class="form-group">
                        <label for="owner">Propietario</label>
                        <input type="text" class="form-control rounded" name="owner" placeholder="Nombre &amp; apellido" required="">
                    </div>
                    <div class="form-group">
                        <label for="owner_mail">Correo del propietario</label>
                        <input type="text" class="form-control rounded" name="owner_mail" placeholder="propietario@uncorreo.com" required="">
                    </div>
                    <div class="form-group">
                        <label for="owner_phone">Teléfono del propietario</label>
                        <input type="text" class="form-control rounded" name="owner_phone" placeholder="0000000000" required="">
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-dark" data-dismiss="modal">Cancelar</button>
                        <button type="submit" class="btn btn-outline-success">Aceptar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>