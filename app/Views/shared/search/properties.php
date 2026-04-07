<form class="form-sample" action="?" method="get">                    
    <div class="row w-100">
        <div class="col-md-12 py-1">
        <div class="accordion w-100" id="accordion1">
            <div id="heading1">
            <div class="row">
                <div class="col-md-12 col-lg-10 mt-0">
                <h3>
                    Buscador de propiedades
                </h3>
                </div>
            </div>
            </div>
        </div>
        </div>
    </div>

    <div class="form-row">
                <div class="form-group col-md-12 col-lg-6 pr-2 pl-2">
                <label for="simple-select2">Estado
                </label>
                <select class="form-control rounded-pill form-control-sm select2 w-100 select2-hidden-accessible" name="e">
                    <option value="">Elegir...
                    </option>
                    <optgroup label="Estado">
                    <?php foreach ($state_data as $option): ?>
                        <option 
                        value="<?= $option['id'] ?>"
                        <?php if(!empty($_GET['e'])): ?>
                            <?php if($option['id'] == $_GET['e']): ?>
                                selected=""
                            <?php endif; ?>
                        <?php endif; ?>
                        >
                            <?= ucfirst($option['name']) ?>
                        </option>
                    <?php endforeach ?>
                    </optgroup>
                </select>
                </div> 
                <!-- form-group -->
                <div class="form-group col-md-12 col-lg-6 pr-2 pl-2">
                <label for="simple-select2">Municipio
                </label>
                <select class="form-control rounded-pill form-control-sm select2 w-100 select2-hidden-accessible" name="m">
                    <option value="">Elegir...
                    </option>
                    <optgroup label="Municipio">
                    <?php foreach ($municipality_data as $option): ?>
                        <option 
                        value="<?= $option['id'] ?>"
                        <?php if(!empty($_GET['m'])): ?>
                            <?php if($option['id'] == $_GET['m']): ?>
                                selected=""
                            <?php endif; ?>
                        <?php endif; ?>
                        >
                            <?= ucfirst($option['name']) ?>
                        </option>
                    <?php endforeach ?>
                    </optgroup>
                </select>
                </div> 
                <!-- form-group -->
                <div class="form-group col-md-12 col-lg-4 pr-2 pl-2">
                <label for="simple-select2">Tipo de negocio
                </label>
                <select class="form-control rounded-pill form-control-sm select2 w-100 select2-hidden-accessible" name="tn" id="tipo_negocio">
                    <option value="">Elegir...
                    </option>
                    <optgroup label="Tipo de negocio">
                    <?php foreach ($business_model_data as $option): ?>
                        <option 
                        value="<?= $option['id'] ?>"
                        <?php if(!empty($_GET['tn'])): ?>
                            <?php if($option['id'] == $_GET['tn']): ?>
                                selected=""
                            <?php endif; ?>
                        <?php endif; ?>
                        >
                            <?= ucfirst($option['name']) ?>
                        </option>
                    <?php endforeach ?>
                    </optgroup>
                </select>
                </div> 
                <!-- form-group -->
                <div class="form-group col-md-12 col-lg-4 pr-2 pl-2">
                <label for="simple-select2">Tipo de propiedad
                </label>
                <select class="form-control rounded-pill form-control-sm select2 w-100 select2-hidden-accessible" name="tp">
                    <option value="">Elegir...
                    </option>
                    <optgroup label="Tipo de propiedad">
                    <?php foreach ($housingtype_data as $option): ?>
                        <option 
                        value="<?= $option['id'] ?>"
                        <?php if(!empty($_GET['tp'])): ?>
                            <?php if($option['id'] == $_GET['tp']): ?>
                                selected=""
                            <?php endif; ?>
                        <?php endif; ?>
                        >
                            <?= ucfirst($option['name']) ?>
                        </option>
                    <?php endforeach ?>
                    </optgroup>
                </select>
                </div> 
                <!-- form-group -->
        <div class="form-group col-md-12 col-lg-4">
        <label for="simple-select2">Código RM
        </label>
        <input 
        type="text" 
        class="form-control rounded-pill form-control-sm" 
        name="rm" 
        id="rm" 
        <?php if(!empty($_GET['rm'])): ?>
            value="<?= $_GET['rm'] ?>" 
        <?php endif; ?>
        placeholder="RM00____" 
        aria-label="RM00____" 
        aria-describedby="basic-addon1">
        </div>
        <!-- form-group -->
        <div class="form-group col-md-12 col-lg-3">
            <label for="simple-select2">Precio mínimo 
            </label>
            <input 
            type="number" 
            class="form-control rounded-pill form-control-sm" 
            name="min_p" 
            id="min_p" 
            <?php if(!empty($_GET['min_p'])): ?>
                value="<?= $_GET['min_p'] ?>" 
            <?php endif; ?>
            placeholder="000000" 
            aria-label="000000" 
            aria-describedby="basic-addon1">
        </div>
        <div class="form-group col-md-12 col-lg-3">
            <label for="simple-select2">Precio máximo
            </label>
            <input 
            type="number" 
            class="form-control rounded-pill form-control-sm" 
            name="max_p" 
            id="max_p" 
            <?php if(!empty($_GET['max_p'])): ?>
                value="<?= $_GET['max_p'] ?>" 
            <?php endif; ?>
            placeholder="000000" 
            aria-label="000000" 
            aria-describedby="basic-addon1">
        </div>
        <div class="form-group col-md-12 col-lg-3">
            <label for="simple-select2">Construcción m² mínimo 
            </label>
            <input 
            type="number" 
            class="form-control rounded-pill form-control-sm" 
            name="min_c_m2" 
            id="min_c_m2" 
            <?php if(!empty($_GET['min_c_m2'])): ?>
                value="<?= $_GET['min_c_m2'] ?>" 
            <?php endif; ?>
            placeholder="000000" 
            aria-label="000000" 
            aria-describedby="basic-addon1">
        </div>
        <div class="form-group col-md-12 col-lg-3">
            <label for="simple-select2">Construcción m² máximo
            </label>
            <input 
            type="number" 
            class="form-control rounded-pill form-control-sm" 
            name="max_c_m2" 
            id="max_c_m2" 
            <?php if(!empty($_GET['max_c_m2'])): ?>
                value="<?= $_GET['max_c_m2'] ?>" 
            <?php endif; ?>
            placeholder="000000" 
            aria-label="000000" 
            aria-describedby="basic-addon1">
        </div>
        <!-- form-group -->
        <div class="form-group col-md-12 col-lg-9">
            <label for="simple-select2">Dirección
            </label>
            <input 
            type="text" 
            class="form-control rounded-pill form-control-sm" 
            name="ubi" 
            id="ubi" 
            <?php if(!empty($_GET['ubi'])): ?>
                value="<?= $_GET['ubi'] ?>" 
            <?php endif; ?>
            placeholder="Ej. Urbanización prados del este" 
            aria-label="Ej. Urbanización prados del este" 
            aria-describedby="basic-addon1">
        </div> 
        <!-- form-group -->
        <div class="form-group col-md-12 col-lg-3 pt-4 pr-4">
        <button type="submit" class="btn btn-success pt-1 pb-2 btn-sm rounded-pill w-100">
            <i class="fe fe-search fe-16">
            </i>
            <i class="fa fa-search" aria-hidden="true"></i> Buscar
        </button>
        </div>
    </div> 
    <!-- form-row -->
</form>
