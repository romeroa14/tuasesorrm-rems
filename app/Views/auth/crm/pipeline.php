<?php
$crmPipelineApi  = site_url('app/crm/api/pipeline');
$crmPipelineMove = site_url('app/crm/api/pipeline/move');
$crmPipelineSyncIg = site_url('app/crm/api/pipeline/sync-instagram-messages');
$crmInboxBase    = site_url('app/crm/inbox');
?>
<link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&family=Plus+Jakarta+Sans:wght@600;700&display=swap" rel="stylesheet"/>
<div class="crm-pipeline-shell">
    <div class="pl-top">
        <div>
            <div class="pl-kicker">Instagram DM · tablero aparte del dashboard</div>
            <h1>Pipeline CRM</h1>
            <p class="pl-sub">Leads con conversación. <span id="pl-total">—</span> en el tablero · <span id="pl-hot">—</span> calientes o listos.</p>
        </div>
        <div class="pl-actions">
            <a href="<?= esc(site_url('app/crm/inbox')) ?>" class="pl-btn">
                <i class="fas fa-inbox"></i> Inbox
            </a>
            <a href="<?= esc(site_url('app/crm/export/meta')) ?>?score_min=50" class="pl-btn">
                <i class="fas fa-file-export"></i> Exportar a Meta
            </a>
            <button type="button" id="btn-sync-instagram-graph" class="pl-btn pl-btn-dark" title="Últimos 5 hilos DM desde Meta Graph">
                <i class="fab fa-instagram"></i> Sync DM
            </button>
        </div>
    </div>

    <div class="pl-toolbar">
        <label class="pl-field">
            <i class="fab fa-instagram"></i>
            <select id="filter-recipient">
                <option value="">Todas las cuentas</option>
            </select>
        </label>
        <div class="pl-temps" id="filter-temp">
            <button type="button" class="is-on" data-temp="">Todos</button>
            <button type="button" data-temp="caliente">Caliente</button>
            <button type="button" data-temp="tibio">Tibio</button>
            <button type="button" data-temp="frio">Frío</button>
            <button type="button" data-temp="listo">Listo</button>
        </div>
        <span class="pl-hint"><i class="fas fa-arrows-alt"></i> Arrastra la tarjeta para cambiar de etapa</span>
    </div>

    <div class="d-flex pipeline-board-inner" id="pipeline-board">
        <div class="pl-loading"><i class="fas fa-spinner fa-spin"></i> Cargando pipeline...</div>
    </div>
</div>

<style>
body.page-pipeline-crm { overflow-x: hidden; }
body.page-pipeline-crm #wrapper {
    height: 100vh; height: 100dvh; max-height: 100vh; max-height: 100dvh;
    overflow: hidden; align-items: stretch;
}
body.page-pipeline-crm #wrapper > ul.sidebar,
body.page-pipeline-crm #wrapper > #accordionSidebar.sidebar {
    flex-shrink: 0; align-self: stretch; min-height: 0;
    max-height: 100vh; max-height: 100dvh; overflow-x: hidden; overflow-y: auto;
}
body.page-pipeline-crm #wrapper #content-wrapper {
    flex: 1 1 auto; min-width: 0; min-height: 0;
    max-height: 100vh; max-height: 100dvh; overflow: hidden;
    background: #f8f9ff;
}
body.page-pipeline-crm #wrapper #content-wrapper #content {
    flex: 1 1 auto !important; display: flex !important; flex-direction: column !important; min-height: 0 !important;
}
body.page-pipeline-crm #content > nav.navbar { flex-shrink: 0; }
body.page-pipeline-crm #container-wrapper {
    flex: 1 1 auto; display: flex; flex-direction: column; min-height: 0;
    padding: 0 0.5rem 0.25rem !important; background: #f8f9ff;
}
body.page-pipeline-crm #content-wrapper > footer.sticky-footer { flex-shrink: 0; margin-top: 0 !important; }

.crm-pipeline-shell {
    --pl-ink: #0b1c30;
    --pl-muted: #45464d;
    --pl-line: #d3e4fe;
    --pl-blue: #0051d5;
    --pl-card: #ffffff;
    --pl-soft: #eff4ff;
    display: flex; flex-direction: column; flex: 1 1 auto; min-height: 0;
    font-family: Inter, system-ui, sans-serif; color: var(--pl-ink);
    padding: 0.85rem 0.75rem 0;
}
.pl-top { display: flex; flex-wrap: wrap; align-items: flex-end; justify-content: space-between; gap: 0.75rem; margin-bottom: 0.75rem; }
.pl-kicker { font-size: 11px; font-weight: 600; letter-spacing: 0.04em; text-transform: uppercase; color: var(--pl-blue); margin-bottom: 0.2rem; }
.crm-pipeline-shell h1 { font-family: "Plus Jakarta Sans", Inter, sans-serif; font-size: 24px; line-height: 1.2; font-weight: 700; margin: 0; color: var(--pl-ink); }
.pl-sub { margin: 0.25rem 0 0; font-size: 12px; color: #76777d; }
.pl-actions { display: flex; flex-wrap: wrap; gap: 0.45rem; }
.pl-btn {
    display: inline-flex; align-items: center; gap: 0.4rem; height: 36px; padding: 0 0.85rem;
    border-radius: 12px; border: 0; background: #fff; color: var(--pl-ink);
    box-shadow: 0 1px 4px rgba(11, 28, 48, 0.06); font-size: 12px; font-weight: 600; text-decoration: none;
}
.pl-btn:hover { background: #eff4ff; color: var(--pl-ink); text-decoration: none; }
.pl-btn-dark { background: #131b2e; color: #fff; }
.pl-btn-dark:hover { background: #0b1c30; color: #fff; }
.pl-toolbar {
    display: flex; flex-direction: column; align-items: flex-start; gap: 0.55rem;
    background: #fff; border-radius: 16px; padding: 0.55rem 0.7rem; margin-bottom: 0.75rem;
    box-shadow: 0 1px 8px rgba(11, 28, 48, 0.04);
}
.pl-field {
    display: inline-flex; align-items: center; gap: 0.4rem; background: #eff4ff;
    border-radius: 12px; padding: 0.35rem 0.7rem; margin: 0; color: #76777d; font-size: 12px;
}
.pl-field select { border: 0; background: transparent; color: var(--pl-ink); font-weight: 600; font-size: 12px; outline: none; }
.pl-temps { display: inline-flex; gap: 0.2rem; background: #eff4ff; border-radius: 12px; padding: 0.2rem; }
.pl-temps button {
    border: 0; background: transparent; color: #45464d; font-size: 12px; font-weight: 600;
    border-radius: 9px; padding: 0.3rem 0.65rem; cursor: pointer;
}
.pl-temps button.is-on { background: #fff; color: var(--pl-blue); box-shadow: 0 1px 3px rgba(11, 28, 48, 0.08); }
.pl-hint { margin-left: 0; font-size: 11px; color: #76777d; }
.pl-origin { display: block; margin-top: 2px; font-size: 12px; font-weight: 700; color: #0b1c30; }
.pl-loading { width: 100%; text-align: center; color: #76777d; padding: 2.5rem 0; }

#pipeline-board.pipeline-board-inner {
    flex: 1 1 auto; min-height: 0; overflow-x: auto; overflow-y: hidden;
    gap: 14px; padding-bottom: 8px; align-items: stretch; scrollbar-width: thin;
}
.pipeline-column { min-width: 300px; max-width: 320px; flex-shrink: 0; display: flex; flex-direction: column; height: 100%; background: transparent; }
.pipeline-column-header {
    display: flex; align-items: center; gap: 0.45rem; background: #fff; border-radius: 16px;
    padding: 0.75rem 0.85rem; margin-bottom: 0.55rem; box-shadow: 0 1px 6px rgba(11, 28, 48, 0.05); border: 0;
}
.pl-dot { width: 9px; height: 9px; border-radius: 50%; flex-shrink: 0; }
.pl-stage-name { font-size: 12px; font-weight: 700; color: var(--pl-ink); }
.pl-count {
    margin-left: auto; background: #dce9ff; color: var(--pl-blue); border-radius: 999px;
    min-width: 1.4rem; text-align: center; font-size: 11px; font-weight: 700; padding: 0.05rem 0.4rem;
}
.pipeline-column-body { flex: 1 1 auto; min-height: 0; overflow-y: auto; padding: 2px 2px 8px; border-radius: 16px; }
.pipeline-column-body.pipeline-drop-target { background: rgba(0, 81, 213, 0.08); outline: 2px dashed #0051d5; outline-offset: -2px; }
.pipeline-empty-hint { color: #76777d; font-size: 12px; text-align: center; padding: 1rem 0.4rem; }

.pipeline-card {
    background: #fff; border-radius: 16px; padding: 0.85rem; margin-bottom: 0.6rem;
    box-shadow: 0 1px 6px rgba(11, 28, 48, 0.06); cursor: grab; border: 0; border-left: 0;
}
.pipeline-card:hover { box-shadow: 0 6px 16px rgba(11, 28, 48, 0.1); }
.pipeline-card:active, .pipeline-card.dragging { cursor: grabbing; }
.pipeline-card.dragging { opacity: 0.55; }
.pl-card-top { display: flex; align-items: flex-start; justify-content: space-between; gap: 0.5rem; }
.pl-who { display: flex; align-items: center; gap: 0.45rem; min-width: 0; }
.pl-who-text { min-width: 0; }
.pl-name { display: block; font-size: 13px; font-weight: 700; color: var(--pl-ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; max-width: 150px; }
.pl-account { display: block; font-size: 11px; color: #76777d; }
.pl-avatar, .pl-avatar-letter {
    width: 32px; height: 32px; border-radius: 50%; object-fit: cover; flex-shrink: 0;
}
.pl-avatar-letter {
    display: inline-flex; align-items: center; justify-content: center;
    background: #131b2e; color: #fff; font-size: 12px; font-weight: 700;
}
.pl-temp {
    flex-shrink: 0; font-size: 11px; font-weight: 700; border-radius: 999px; padding: 0.15rem 0.45rem; background: #eff4ff; color: #45464d;
}
.pl-temp-caliente { background: #ffe8d6; color: #c2410c; }
.pl-temp-tibio { background: #fff4d6; color: #a16207; }
.pl-temp-frio { background: #e7eeff; color: #0051d5; }
.pl-temp-listo { background: #d9fbe8; color: #047857; }
.pl-strip {
    margin-top: 0.65rem; background: #eff4ff; border-radius: 12px; padding: 0.5rem 0.65rem;
    display: flex; justify-content: space-between; gap: 0.5rem; font-size: 11px;
}
.pl-strip span { display: block; color: #76777d; font-size: 10px; text-transform: uppercase; letter-spacing: 0.03em; }
.pl-strip strong { color: var(--pl-ink); font-weight: 700; font-size: 12px; }
.pl-card-foot { display: flex; align-items: center; justify-content: space-between; gap: 0.4rem; margin-top: 0.65rem; font-size: 11px; color: #76777d; }
.pl-open {
    display: inline-flex; align-items: center; height: 28px; padding: 0 0.6rem; border-radius: 10px;
    background: #0051d5; color: #fff !important; font-size: 11px; font-weight: 700; text-decoration: none;
}
.pl-open:hover { background: #316bf3; color: #fff; text-decoration: none; }
.pl-assign { margin-top: 0.5rem; }
.pl-assign select {
    width: 100%; border: 0; background: #eff4ff; border-radius: 10px; font-size: 12px; padding: 0.35rem 0.5rem; color: var(--pl-ink);
}
</style>

<script>
var pipelineSuppressCardClick = false;
var CRM_PIPELINE_URL = <?= json_encode($crmPipelineApi) ?>;
var CRM_PIPELINE_MOVE_URL = <?= json_encode($crmPipelineMove) ?>;
var CRM_PIPELINE_SYNC_IG_URL = <?= json_encode($crmPipelineSyncIg) ?>;
var CRM_INBOX_URL = <?= json_encode($crmInboxBase) ?>;

$(document).ready(function() {
    loadPipeline();
    $.get('/app/options/get_atc_users', function(r) {
        window.atcUsers = r.data || [];
    });
    $(document).on('change', '.assign-atc', function() {
        var userId = $(this).val();
        var leadId = $(this).data('lead-id');
        var convId = $(this).data('conv-id');
        if (!userId) return;
        $.post('/app/crm/api/pipeline/assign', {lead_id: leadId, assigned_id: userId, conv_id: convId}, function(r) {
            if (r.status === 'success') loadPipeline();
            else alert('Error: ' + (r.message || 'No se pudo asignar'));
        });
    });
});

$(document).on('click', '#btn-sync-instagram-graph', function() {
    var $btn = $(this).prop('disabled', true);
    $.post(CRM_PIPELINE_SYNC_IG_URL, function(res) {
        if (res.status === 'success') {
            loadPipeline();
            Swal.fire({icon: 'success', title: 'Sincronizado', text: 'Mensajes actualizados.', confirmButtonColor: '#0051d5', timer: 2000});
        } else {
            var msg = (res.message || 'Error');
            if (res.data && res.data.graph_error) {
                msg += '<br><small>' + escapeHtml(JSON.stringify(res.data.graph_error)) + '</small>';
            }
            Swal.fire({icon: 'error', title: 'Graph API', html: msg, confirmButtonColor: '#ba1a1a'});
        }
    }).fail(function() {
        Swal.fire({icon: 'error', title: 'Red', text: 'No se pudo llamar al servidor.', confirmButtonColor: '#ba1a1a'});
    }).always(function() {
        $btn.prop('disabled', false);
    });
});

function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, function(c) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
}

function safeLeadPhoto(pic) {
    return typeof pic === 'string' && /^\/uploads\/crm-avatars\/\d+\.(jpg|png|webp|gif)$/.test(pic) ? pic : '';
}

function leadAvatarHtml(pic, name) {
    var photo = safeLeadPhoto(pic);
    var letter = escapeHtml(((name || '?') + '').charAt(0).toUpperCase());
    if (photo) {
        return '<img class="pl-avatar" src="' + photo + '" alt="" width="32" height="32">';
    }
    return '<span class="pl-avatar-letter">' + letter + '</span>';
}

function tempClass(label) {
    var key = String(label || 'frio').toLowerCase();
    if (key === 'caliente' || key === 'tibio' || key === 'frio' || key === 'listo') return 'pl-temp pl-temp-' + key;
    return 'pl-temp';
}

function tempText(label) {
    var map = { frio: 'Frío', tibio: 'Tibio', caliente: 'Caliente', listo: 'Listo' };
    return map[String(label || '').toLowerCase()] || 'Frío';
}

function caracasParts(value) {
    if (!value) return null;
    var raw = String(value).trim().replace(' ', 'T');
    if (raw.length === 16) raw += ':00';
    if (!/[zZ]|[+-]\d{2}:\d{2}$/.test(raw)) raw += 'Z';
    var d = new Date(raw);
    if (isNaN(d.getTime())) return null;
    var map = {};
    new Intl.DateTimeFormat('en-CA', {
        timeZone: 'America/Caracas',
        year: 'numeric', month: '2-digit', day: '2-digit',
        hour: '2-digit', minute: '2-digit', hourCycle: 'h23'
    }).formatToParts(d).forEach(function (part) {
        if (part.type !== 'literal') map[part.type] = part.value;
    });
    return map;
}

function originLine(lead) {
    var title = String(lead.ad_title || '').trim();
    var source = String(lead.referral_source || '').toLowerCase();
    var adId = String(lead.ad_id || '').trim();
    if (title) return 'Anuncio · ' + title;
    if (source === 'ads' || (adId && adId !== '0')) return 'Vino de un anuncio';
    if (source === 'direct') return 'Mensaje directo';
    return '';
}

function formatWhen(value) {
    var parts = caracasParts(value);
    if (!parts) return '—';
    return parts.day + '/' + parts.month + ' ' + parts.hour + ':' + parts.minute;
}

function loadPipeline() {
    $.get(CRM_PIPELINE_URL, function(response) {
        if (response.status === 'success') {
            renderPipeline(response.data);
            populateRecipientFilter(response.data);
            applyPipelineFilters();
        }
    });
}

function populateRecipientFilter(stages) {
    var current = $('#filter-recipient').val() || '';
    var usernames = {};
    stages.forEach(function(stage) {
        (stage.leads || []).forEach(function(lead) {
            var uname = String(lead.recipient_ig_username || '').replace(/^@/, '').trim();
            if (uname) usernames[uname] = true;
        });
    });
    var sel = $('#filter-recipient');
    sel.find('option:gt(0)').remove();
    Object.keys(usernames).sort().forEach(function(u) {
        sel.append('<option value="' + escapeHtml(u) + '">@' + escapeHtml(u) + '</option>');
    });
    if (current) sel.val(current);
}

function applyPipelineFilters() {
    var account = $('#filter-recipient').val() || '';
    var temp = $('#filter-temp button.is-on').data('temp') || '';
    $('.pipeline-card').each(function() {
        var cardAccount = String($(this).data('recipient') || '').replace(/^@/, '');
        var cardTemp = String($(this).data('temp') || '');
        var okA = !account || cardAccount === account;
        var okT = !temp || cardTemp === temp;
        $(this).toggle(okA && okT);
    });
    $('.pipeline-column').each(function() {
        $(this).find('.pl-count').text($(this).find('.pipeline-card:visible').length);
    });
}

$(document).on('change', '#filter-recipient', applyPipelineFilters);
$(document).on('click', '#filter-temp button', function() {
    $('#filter-temp button').removeClass('is-on');
    $(this).addClass('is-on');
    applyPipelineFilters();
});

function renderPipeline(stages) {
    const board = $('#pipeline-board');
    let html = '';
    let total = 0;
    let hot = 0;
    const colors = ['#0051d5', '#316bf3', '#0b1c30', '#76777d', '#069669', '#c2410c', '#5b4b8a'];

    (stages || []).forEach((stage, idx) => {
        const color = colors[idx % colors.length];
        const leads = stage.leads || [];
        total += leads.length;
        html += `
            <div class="pipeline-column" data-tracking-status-id="${escapeHtml(stage.id)}">
                <div class="pipeline-column-header">
                    <span class="pl-dot" style="background:${color}"></span>
                    <span class="pl-stage-name">${escapeHtml(stage.name || '')}</span>
                    <span class="pl-count">${leads.length}</span>
                </div>
                <div class="pipeline-column-body" data-tracking-status-id="${escapeHtml(stage.id)}">
        `;

        if (leads.length) {
            leads.forEach(lead => {
                const label = String(lead.intention_label || 'frio').toLowerCase();
                if (label === 'caliente' || label === 'listo') hot += 1;
                const convId = lead.conversation_id || 0;
                const account = String(lead.recipient_ig_username || '').replace(/^@/, '');
                const interest = lead.zone_interest || lead.interest_type || '';
                const budget = lead.budget_detected ? ('$' + Number(lead.budget_detected).toLocaleString('es-VE')) : '—';
                const inboxUrl = convId ? CRM_INBOX_URL + (CRM_INBOX_URL.indexOf('?') >= 0 ? '&' : '?') + 'open=' + convId : '';
                const assign = stage.id === 'unassigned'
                    ? '<div class="pl-assign"><select class="assign-atc" data-lead-id="' + escapeHtml(lead.lead_id) + '" data-conv-id="' + escapeHtml(convId) + '"><option value="">Asignar a ATC...</option>' + (window.atcUsers || []).map(u => '<option value="' + escapeHtml(u.id) + '">' + escapeHtml(u.full_name) + '</option>').join('') + '</select></div>'
                    : '';
                html += `
                    <div class="pipeline-card" draggable="true" data-lead-id="${escapeHtml(lead.lead_id)}" data-conv-id="${escapeHtml(convId)}" data-tracking-status-id="${escapeHtml(stage.id)}" data-recipient="${escapeHtml(account)}" data-temp="${escapeHtml(label)}">
                        <div class="pl-card-top">
                            <div class="pl-who">
                                ${leadAvatarHtml(lead.profile_pic, lead.lead_name)}
                                <div class="pl-who-text">
                                    <span class="pl-name">${escapeHtml(lead.lead_name || 'Sin nombre')}</span>
                                    <span class="pl-account">@${escapeHtml(account || 'sin cuenta')}</span>
                                    ${originLine(lead) ? '<span class="pl-origin">' + escapeHtml(originLine(lead)) + '</span>' : ''}
                                </div>
                            </div>
                            <span class="${tempClass(label)}">${escapeHtml(tempText(label))} ${escapeHtml(lead.intention_score || 0)}%</span>
                        </div>
                        <div class="pl-strip">
                            <div><span>Interés</span><strong>${escapeHtml(interest || 'Sin zona')}</strong></div>
                            <div style="text-align:right"><span>Presupuesto</span><strong>${escapeHtml(budget)}</strong></div>
                        </div>
                        <div class="pl-card-foot">
                            <span>${escapeHtml(lead.agent_name || 'Sin asignar')} · ${escapeHtml(formatWhen(lead.created_at))}</span>
                            ${inboxUrl ? '<a class="pl-open" href="' + inboxUrl + '">Inbox</a>' : ''}
                        </div>
                        ${assign}
                    </div>
                `;
            });
        } else {
            html += '<div class="pipeline-empty-hint">Arrastra un lead aquí</div>';
        }
        html += '</div></div>';
    });

    $('#pl-total').text(total);
    $('#pl-hot').text(hot);
    if (!html) {
        html = '<div class="pl-loading">No hay datos en el pipeline</div>';
    }
    board.html(html);
    bindPipelineDnD(board);
}

function bindPipelineDnD(board) {
    board.off('.pipelineDnd');
    board.on('click.pipelineDnd', '.pipeline-card', function(e) {
        if (pipelineSuppressCardClick) {
            e.preventDefault();
            e.stopImmediatePropagation();
            return false;
        }
        if ($(e.target).closest('a, button, select, option, .assign-atc, .pl-open').length) return;
        const convId = $(this).data('conv-id');
        if (convId) {
            window.location.href = CRM_INBOX_URL + (CRM_INBOX_URL.indexOf('?') >= 0 ? '&' : '?') + 'open=' + convId;
        } else {
            Swal.fire({ icon: 'warning', title: 'Sin conversación', text: 'Este lead no tiene una conversación activa en el CRM.', confirmButtonColor: '#0051d5' });
        }
    });
    board.on('dragstart.pipelineDnd', '.pipeline-card', function(e) {
        const ev = e.originalEvent;
        if (ev.dataTransfer) {
            ev.dataTransfer.setData('text/plain', String($(this).data('lead-id')));
            ev.dataTransfer.effectAllowed = 'move';
        }
        $(this).addClass('dragging');
    });
    board.on('dragend.pipelineDnd', '.pipeline-card', function() {
        $(this).removeClass('dragging');
        board.find('.pipeline-column-body').removeClass('pipeline-drop-target');
        pipelineSuppressCardClick = true;
        window.setTimeout(function () { pipelineSuppressCardClick = false; }, 400);
    });
    board.on('dragover.pipelineDnd', '.pipeline-column-body', function(e) {
        e.preventDefault();
        const ev = e.originalEvent;
        if (ev.dataTransfer) ev.dataTransfer.dropEffect = 'move';
        $(this).addClass('pipeline-drop-target');
    });
    board.on('dragleave.pipelineDnd', '.pipeline-column-body', function(e) {
        var rel = e.relatedTarget;
        if (rel && this.contains(rel)) return;
        $(this).removeClass('pipeline-drop-target');
    });
    board.on('drop.pipelineDnd', '.pipeline-column-body', function(e) {
        e.preventDefault();
        const body = $(this);
        body.removeClass('pipeline-drop-target');
        const ev = e.originalEvent;
        var leadId = ev.dataTransfer ? ev.dataTransfer.getData('text/plain') : '';
        leadId = String(leadId || '').trim();
        const newStatusId = body.data('tracking-status-id');
        if (!leadId || newStatusId === undefined || newStatusId === '') return;
        const card = board.find('.pipeline-card[data-lead-id="' + leadId + '"]');
        const fromStatus = card.length ? card.data('tracking-status-id') : null;
        if (fromStatus !== null && String(fromStatus) === String(newStatusId)) return;
        $.post(CRM_PIPELINE_MOVE_URL, { lead_id: leadId, trackingstatus_id: newStatusId }, function(res) {
            if (res.status === 'success') loadPipeline();
            else Swal.fire({ icon: 'error', title: 'Error', text: res.message || 'No se pudo mover el lead', confirmButtonColor: '#ba1a1a' });
        }).fail(function() {
            Swal.fire({ icon: 'error', title: 'Error de red', text: 'Error de red al mover el lead', confirmButtonColor: '#ba1a1a' });
        });
    });
}
</script>
