<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid" id="container-wrapper">

            <div class="d-sm-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="h3 mb-0 text-gray-800"><i class="fas fa-comments text-primary"></i> Control de Atención de Conversaciones</h1>
                    <small class="text-muted">Auditoría de atención por asesor y chats sin responder</small>
                </div>
            </div>

            <!-- Filters -->
            <div class="card shadow-sm mb-4">
                <div class="card-body py-3">
                    <form id="report-filters" class="form-inline">
                        <label class="mr-2 font-weight-bold" for="rep-from">Desde</label>
                        <input type="date" id="rep-from" class="form-control form-control-sm mr-3">
                        <label class="mr-2 font-weight-bold" for="rep-to">Hasta</label>
                        <input type="date" id="rep-to" class="form-control form-control-sm mr-3">
                        <button type="submit" class="btn btn-sm btn-primary mr-2"><i class="fas fa-filter"></i> Aplicar</button>
                        <div class="btn-group btn-group-sm mr-3" role="group" aria-label="Rangos rápidos">
                            <button type="button" class="btn btn-outline-secondary" data-range="today">Hoy</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="7">7 días</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="30">30 días</button>
                            <button type="button" class="btn btn-outline-secondary" data-range="month">Este mes</button>
                        </div>
                        <a id="export-phones" href="#" class="btn btn-sm btn-success ml-auto">
                            <i class="fas fa-file-excel"></i> Exportar números (Excel)
                        </a>
                    </form>
                    <div class="mt-2 d-flex flex-wrap align-items-center">
                        <span class="font-weight-bold mr-3">Cuentas</span>
                        <div id="account-boxes" class="d-flex flex-wrap"><span class="text-muted small">Cargando…</span></div>
                        <button type="button" id="accounts-all" class="btn btn-link btn-sm">Todas</button>
                        <button type="button" id="accounts-none" class="btn btn-link btn-sm">Ninguna</button>
                    </div>
                </div>
            </div>

            <div class="btn-group mb-3" role="group" aria-label="Tipo de análisis">
                <button type="button" class="btn btn-primary" data-mode="conversation">
                    <i class="fas fa-comments"></i> Análisis por conversación
                </button>
                <button type="button" class="btn btn-outline-primary" data-mode="crm" disabled
                        title="Se habilita cuando el equipo ATC responda desde el CRM">
                    <i class="fas fa-database"></i> Análisis por datos del CRM
                </button>
            </div>
            <p class="small text-muted mb-3" id="mode-help"></p>

            <div id="report-error" class="alert alert-danger d-none"></div>

            <!-- KPI cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card text-white h-100" style="background:#4e5bf2">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase small font-weight-bold">Total conversaciones</div>
                                <div class="h2 mb-0 font-weight-bold" id="kpi-total">-</div>
                            </div>
                            <i class="fas fa-inbox fa-2x" style="opacity:.6"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card text-white h-100" style="background:#0f9d58">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase small font-weight-bold">Atendidas</div>
                                <div class="h2 mb-0 font-weight-bold" id="kpi-attended">-</div>
                            </div>
                            <i class="fas fa-check-circle fa-2x" style="opacity:.6"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card text-white h-100" style="background:#e8174f">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase small font-weight-bold">Sin atender (alerta)</div>
                                <div class="h2 mb-0 font-weight-bold" id="kpi-unattended">-</div>
                            </div>
                            <i class="fas fa-exclamation-triangle fa-2x" style="opacity:.6"></i>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card text-white h-100" style="background:#7b11b8">
                        <div class="card-body d-flex justify-content-between align-items-center">
                            <div>
                                <div class="text-uppercase small font-weight-bold">Compartidas (multi-asesor)</div>
                                <div class="h2 mb-0 font-weight-bold" id="kpi-shared">-</div>
                            </div>
                            <i class="fas fa-users fa-2x" style="opacity:.6"></i>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Per advisor -->
            <div class="card shadow-sm mb-4">
                <div class="card-header text-white" style="background:#212529">
                    <i class="fas fa-user-check"></i> <strong>Rendimiento por Asesor</strong>
                </div>
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead>
                            <tr class="text-center">
                                <th class="text-left">Asesor</th>
                                <th>Total atendidas</th>
                                <th>Veces primero <i class="fas fa-trophy text-warning"></i></th>
                                <th>Exclusivas (sólo él/ella)</th>
                                <th>Compartidas</th>
                                <th class="text-left" style="min-width:180px">% Cuota del total</th>
                            </tr>
                        </thead>
                        <tbody id="advisor-rows">
                            <tr><td colspan="6" class="text-center text-muted py-4">Cargando…</td></tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <p class="small text-muted" id="report-note"></p>
            <div class="small text-muted">
                <strong>Total atendidas</strong>: conversaciones en las que el asesor respondió.
                <strong>Veces primero</strong>: en cuántas fue el primero en responder.
                <strong>Exclusivas</strong>: atendidas sólo por ese asesor.
                <strong>Compartidas</strong>: respondieron dos o más asesores (cuenta para cada uno).
                <strong>% Cuota del total</strong>: atendidas del asesor ÷ total de atendidas; con compartidas la suma puede pasar del 100&nbsp;%.
                <strong>Sin atender</strong>: conversaciones sin asesor identificado, incluidas las respondidas sin firma.
            </div>

        </div>
    </div>
</div>

<script>
(function () {
    var DATA_URL = '<?= base_url('/app/crm/reports/data') ?>';
    var PHONES_URL = '<?= base_url('/app/crm/reports/phones') ?>';
    var $from = $('#rep-from'), $to = $('#rep-to');
    var mode = 'conversation';
    var selected = null; // null = every account
    var HELP = {
        conversation: 'Clasificación por mensajes: el asesor se identifica por cómo firma o se presenta en el chat (“Valery Mendoza | Asesores RM”, “Odra Cedeño del Equipo de Asesores RM”, “le habla Luis Rojas”).',
        crm: 'Clasificación por datos del CRM: cuenta a los usuarios que respondieron desde el CRM con su sesión. Es la fuente oficial cuando el equipo ATC trabaje en el CRM.'
    };
    function setMode(m) {
        mode = m;
        $('[data-mode]').each(function () {
            var on = $(this).data('mode') === m;
            $(this).toggleClass('btn-primary', on).toggleClass('btn-outline-primary', !on);
        });
        $('#mode-help').text(HELP[m]);
    }

    function iso(d) {
        var m = ('0' + (d.getMonth() + 1)).slice(-2), day = ('0' + d.getDate()).slice(-2);
        return d.getFullYear() + '-' + m + '-' + day;
    }
    function setRange(kind) {
        var now = new Date(), start = new Date();
        if (kind === '7') start.setDate(now.getDate() - 6);
        else if (kind === '30') start.setDate(now.getDate() - 29);
        else if (kind === 'month') start = new Date(now.getFullYear(), now.getMonth(), 1);
        $from.val(iso(start));
        $to.val(iso(now));
    }
    function renderAccounts(list) {
        var html = list.map(function (a) {
            var on = selected === null || selected.indexOf(a.account) !== -1;
            return '<label class="mr-3 mb-0"><input type="checkbox" class="account-box mr-1" value="' + esc(a.account) + '"' + (on ? ' checked' : '') + '>' +
                esc(a.account) + ' <span class="text-muted small">(' + a.conversations + ')</span></label>';
        });
        $('#account-boxes').html(html.join('') || '<span class="text-muted small">Sin conversaciones en el rango</span>');
    }
    function readAccounts() {
        var all = $('.account-box'), on = $('.account-box:checked');
        selected = on.length === all.length ? null : on.map(function () { return this.value; }).get();
    }
    function esc(s) { return $('<div>').text(s == null ? '' : s).html(); }

    function render(d) {
        $('#kpi-total').text(d.total);
        $('#kpi-attended').text(d.attended);
        $('#kpi-unattended').text(d.unattended);
        $('#kpi-shared').text(d.shared);
        var rows = d.advisors.map(function (a) {
            return '<tr class="text-center">' +
                '<td class="text-left font-weight-bold">' + esc(a.advisor) + '</td>' +
                '<td class="text-primary font-weight-bold">' + a.total + '</td>' +
                '<td><span class="badge badge-warning">' + a.first + '</span></td>' +
                '<td class="text-success font-weight-bold">' + a.exclusive + '</td>' +
                '<td class="font-weight-bold" style="color:#7b11b8">' + a.shared + '</td>' +
                '<td class="text-left"><div class="d-flex align-items-center">' +
                    '<span class="mr-2" style="width:48px">' + a.share_pct + '%</span>' +
                    '<div class="progress flex-grow-1" style="height:6px"><div class="progress-bar" style="width:' + Math.min(100, a.share_pct) + '%"></div></div>' +
                '</div></td></tr>';
        });
        $('#advisor-rows').html(rows.join('') || '<tr><td colspan="6" class="text-center text-muted py-4">Sin datos</td></tr>');
        $('#report-note').text(d.unidentified_human > 0
            ? 'Sin firma: ' + d.unidentified_human + ' conversaciones tienen respuesta humana pero sin nombre de asesor; cuentan como “sin atender”.'
            : '');
    }

    function load() {
        var q = { from: $from.val(), to: $to.val(), mode: mode };
        if (selected !== null) { q.accounts = selected.length ? selected : ['__none__']; }
        $('#report-error').addClass('d-none');
        $('#export-phones').attr('href', PHONES_URL + '?' + $.param(q));
        $.getJSON(DATA_URL, q).done(function (res) {
            $from.val(res.from); $to.val(res.to);
            $('[data-mode="crm"]').prop('disabled', !res.crm_available);
            setMode(res.mode);
            renderAccounts(res.accounts_available || []);
            var exportQ = { from: res.from, to: res.to, mode: res.mode };
            if (selected !== null) { exportQ.accounts = selected.length ? selected : ['__none__']; }
            $('#export-phones').attr('href', PHONES_URL + '?' + $.param(exportQ));
            render(res.data);
        }).fail(function () {
            $('#report-error').text('No se pudo cargar el reporte.').removeClass('d-none');
        });
    }

    $('#report-filters').on('submit', function (e) { e.preventDefault(); load(); });
    $('[data-range]').on('click', function () { setRange($(this).data('range')); load(); });
    $('#account-boxes').on('change', '.account-box', function () { readAccounts(); load(); });
    $('#accounts-all').on('click', function () { selected = null; load(); });
    $('#accounts-none').on('click', function () { selected = []; load(); });
    $('[data-mode]').on('click', function () { if (!this.disabled) { setMode($(this).data('mode')); load(); } });
    setMode('conversation');
    setRange('month');
    load();
})();
</script>
