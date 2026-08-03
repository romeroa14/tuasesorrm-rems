<!-- Participant row template — reusable HTML for JS cloning -->
<tr>
    <td>
        <select class="form-control form-control-sm participant-user" name="participants[][user_id]" style="width:100%">
            <option value="">Seleccionar...</option>
        </select>
    </td>
    <td>
        <select class="form-control form-control-sm participant-role" name="participants[][role]">
            <option value="cerrador">Cerrador</option>
            <option value="cap">CAP</option>
            <option value="coordinator">Coordinador</option>
            <option value="gs">GS</option>
            <option value="fe">FE</option>
            <option value="sales_manager">Gerente Ventas</option>
            <option value="registro">Registro</option>
            <option value="external_advisor">Asesor Externo</option>
            <option value="ne">NE</option>
        </select>
    </td>
    <td>
        <select class="form-control form-control-sm participant-type" name="participants[][commission_type]">
            <option value="percentage">Porcentaje (%)</option>
            <option value="fixed">Fijo ($)</option>
            <option value="formula">Fórmula</option>
        </select>
    </td>
    <td>
        <input type="number" step="0.01" class="form-control form-control-sm participant-value" name="participants[][commission_value]" min="0.01" placeholder="0.00" required>
    </td>
    <td class="text-muted">—</td>
    <td>
        <button type="button" class="btn btn-success btn-sm mr-1 save-participant-row"><i class="fas fa-save"></i></button>
        <button type="button" class="btn btn-danger btn-sm remove-participant-row"><i class="fas fa-times"></i></button>
    </td>
</tr>
