<!-- Commission Settlement Report — read-only from vw_commission_settlement_report -->
<div class="container-fluid">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <h1 class="h3 mb-0 text-gray-800">
            <i class="fas fa-file-alt text-info"></i> <?= esc($title ?? 'Comisiones — Reporte') ?>
        </h1>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-filter"></i> Filtros</h6>
        </div>
        <div class="card-body">
            <div class="row">
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Período Desde</label>
                        <input type="date" class="form-control form-control-sm" id="filter_period_start">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Período Hasta</label>
                        <input type="date" class="form-control form-control-sm" id="filter_period_end">
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="form-group">
                        <label>Estado</label>
                        <select class="form-control form-control-sm" id="filter_status">
                            <option value="">Todos</option>
                            <option value="draft">Borrador</option>
                            <option value="finalized">Finalizado</option>
                            <option value="paid">Pagado</option>
                        </select>
                    </div>
                </div>
                <div class="col-md-3 d-flex align-items-end">
                    <button class="btn btn-primary btn-sm" onclick="applyFilters()">
                        <i class="fas fa-search"></i> Filtrar
                    </button>
                </div>
            </div>
        </div>
    </div>

    <div class="card shadow mb-4">
        <div class="card-header py-3 d-flex flex-row align-items-center justify-content-between">
            <h6 class="m-0 font-weight-bold text-primary"><i class="fas fa-list"></i> Reporte de Liquidaciones</h6>
        </div>
        <div class="card-body">
            <div class="table-responsive">
                <table id="reportTable" class="table table-striped table-bordered" style="width:100%">
                    <thead class="thead-light">
                        <tr>
                            <th>ID</th>
                            <th>Asesor</th>
                            <th>Período Inicio</th>
                            <th>Período Fin</th>
                            <th>Estado</th>
                            <th>Total Comisiones</th>
                            <th>Total Adelantos</th>
                            <th>Saldo Neto</th>
                            <th>Alerta</th>
                        </tr>
                    </thead>
                    <tbody></tbody>
                    <tfoot>
                        <tr class="bg-light font-weight-bold">
                            <th colspan="5" class="text-right">Totales:</th>
                            <th id="footer_commission">—</th>
                            <th id="footer_advances">—</th>
                            <th id="footer_net">—</th>
                            <th></th>
                        </tr>
                    </tfoot>
                </table>
            </div>
        </div>
    </div>
</div>

<script>
var dt;
var allData = <?= json_encode($report_data ?? []) ?>;

function formatMoney(value) {
    if (!isFinite(value)) return '$ 0.00';
    return '$ ' + parseFloat(value).toLocaleString('es-VE', { minimumFractionDigits: 2, maximumFractionDigits: 2 });
}

function applyFilters() {
    var start = $('#filter_period_start').val();
    var end = $('#filter_period_end').val();
    var status = $('#filter_status').val();

    var filtered = allData.filter(function(row) {
        if (start && row.period_start < start) return false;
        if (end && row.period_end > end) return false;
        if (status && row.status !== status) return false;
        return true;
    });

    var rows = [];
    var totalComm = 0, totalAdv = 0, totalNet = 0;
    var statusLbl = {'draft': 'Borrador', 'finalized': 'Finalizado', 'paid': 'Pagado'};
    var statusCls = {'draft': 'secondary', 'finalized': 'primary', 'paid': 'success'};

    filtered.forEach(function(row) {
        totalComm += parseFloat(row.total_comisiones || 0);
        totalAdv += parseFloat(row.total_adelantos || 0);
        totalNet += parseFloat(row.saldo_neto || 0);

        rows.push([
            row.id,
            row.asesor || '—',
            row.period_start || '—',
            row.period_end || '—',
            '<span class="badge badge-' + (statusCls[row.status] || 'secondary') + '">' + (statusLbl[row.status] || row.status) + '</span>',
            formatMoney(row.total_comisiones),
            formatMoney(row.total_adelantos),
            '<span class="' + (parseFloat(row.saldo_neto) < 0 ? 'text-danger' : 'text-success') + '">' + formatMoney(row.saldo_neto) + '</span>',
            parseInt(row.alerta) ? '<i class="fas fa-exclamation-triangle text-danger" title="Saldo negativo"></i>' : '<i class="fas fa-check-circle text-success"></i>'
        ]);
    });

    if (dt) dt.clear().rows.add(rows).draw();
    else {
        dt = $('#reportTable').DataTable({
            data: rows,
            columns: [
                {title: 'ID'},
                {title: 'Asesor'},
                {title: 'Período Inicio'},
                {title: 'Período Fin'},
                {title: 'Estado'},
                {title: 'Total Comisiones'},
                {title: 'Total Adelantos'},
                {title: 'Saldo Neto'},
                {title: 'Alerta', orderable: false}
            ],
            pageLength: 50,
            language: {url: '//cdn.datatables.net/plug-ins/1.13.7/i18n/es-ES.json'},
            dom: 'Bfrtip',
            buttons: ['excel', 'pdf']
        });
    }

    $('#footer_commission').text(formatMoney(totalComm));
    $('#footer_advances').text(formatMoney(totalAdv));
    $('#footer_net').text(formatMoney(totalNet));
}

$(document).ready(function() { applyFilters(); });
</script>
