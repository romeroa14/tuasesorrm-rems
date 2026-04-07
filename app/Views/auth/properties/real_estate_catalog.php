<div class="card">
    <div class="card-body">
        <div class="container-fluid mt-4">
            <div class="row w-100">
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="rm">Código RM</label>
                        <input type="text" class="form-control h-50 border-radius-search" id="rm" aria-describedby="rm" placeholder="RM00__">
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="agent">Agente</label>
                        <select class="form-control h-50 border-radius-search" id="agent">
                            <option value="0">Todos</option>
                            <?php foreach ($agent_model_data as $option): ?>
                                <option 
                                value="<?= $option['id'] ?>"
                                <?php if(!empty($_GET['tn'])): ?>
                                    <?php if($option['id'] == $_GET['tn']): ?>
                                        selected=""
                                    <?php endif; ?>
                                <?php endif; ?>
                                >
                                    <?= ucfirst($option['full_name']) ?>
                                </option>
                            <?php endforeach ?>
                        </select>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="form-group">
                        <label for="business_model">Negocio</label>
                        <select class="form-control h-50 border-radius-search" id="business_model">
                            <option value="0">Todos</option>
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
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="min_p">Monto mínimo <strong>($USD)</strong></label>
                        <input type="number" class="form-control h-50 border-radius-search" id="min_p" aria-describedby="min_p" placeholder="000">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="max_p">Monto máximo <strong>($USD)</strong></label>
                        <input type="number" class="form-control h-50 border-radius-search" id="max_p" aria-describedby="max_p" placeholder="000">
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="form-group">
                        <label for="tp">Tipo de propiedad</label>
                        <select class="form-control h-50 border-radius-search" id="tp">
                            <option value="0">Todos</option>
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
                        </select>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="min_c_m2">Const. mínimo <strong>(m²)</strong></label>
                        <input type="number" class="form-control h-50 border-radius-search" id="min_c_m2" aria-describedby="min_c_m2" placeholder="000">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label for="max_c_m2">Const. máximo <strong>(m²)</strong></label>
                        <input type="number" class="form-control h-50 border-radius-search" id="max_c_m2" aria-describedby="max_c_m2" placeholder="000">
                    </div>
                </div>
                <div class="col-md-10">
                    <div class="form-group">
                        <label for="address">Dirección <strong>(Palabra clave)</strong></label>
                        <input type="text" class="form-control h-50 border-radius-search" id="address" placeholder="Dirección">
                    </div>
                </div>
                <div class="col-md-2">
                    <button type="submit" onclick="searchFilter();" class="btn btn-primary border-radius-search w-100" style="margin-top:30px;">Buscar</button>
                </div>
            </div>
            <hr>
            <div class="d-flex mt-3">
                <p>Página</p>
                <div id="pagination-container"></div>
            </div>
        </div>
    </div>
</div>
<div class="content-properties row" id="cards-container"></div>

<div id="message-result-search"></div>

<div id="preloader" class="d-flex justify-content-center w-100 pt-3 pb-5" style="display: none;">
    <div class="spinner-border text-primary" role="status">
        <span class="sr-only">Loading...</span>
    </div>
    <h3 class="pl-3" id="preloader-message">Cargando...</h3>
</div>

