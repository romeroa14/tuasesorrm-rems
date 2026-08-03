<div class="modal fade" id="createClientModal" tabindex="-1">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header bg-primary text-white">
                <h5 class="modal-title"><i class="fas fa-user-plus"></i> Nuevo cliente</h5>
                <button type="button" class="close text-white" data-dismiss="modal">&times;</button>
            </div>
            <form id="createClientForm">
                <div class="modal-body">
                    <p class="text-muted small mb-3">
                        El cliente se registrará en el módulo de leads del CRM (embudo: Finanzas - Registro manual).
                    </p>
                    <div class="form-group">
                        <label>Nombre <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="name" id="create_client_name" required placeholder="Ej: Edsu Pérez">
                    </div>
                    <div class="form-group">
                        <label>Teléfono <span class="text-danger">*</span></label>
                        <input type="text" class="form-control" name="phone" id="create_client_phone" required placeholder="Ej: +584120000000">
                    </div>
                    <div class="form-group">
                        <label>Correo</label>
                        <input type="email" class="form-control" name="email" id="create_client_email" placeholder="Opcional">
                    </div>
                    <div class="form-group mb-0">
                        <label>Observación</label>
                        <textarea class="form-control" name="observation" id="create_client_observation" rows="2" placeholder="Opcional"></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-dismiss="modal">Cancelar</button>
                    <button type="submit" class="btn btn-primary"><i class="fas fa-save"></i> Crear y seleccionar</button>
                </div>
            </form>
        </div>
    </div>
</div>
