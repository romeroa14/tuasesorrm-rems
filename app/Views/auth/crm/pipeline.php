<?php
// URLs para AJAX (subcarpeta /index.php si aplica)
$crmPipelineApi  = site_url('app/crm/api/pipeline');
$crmPipelineMove = site_url('app/crm/api/pipeline/move');
$crmPipelineSyncIg = site_url('app/crm/api/pipeline/sync-instagram-messages');
$crmInboxBase    = site_url('app/crm/inbox');
?>
<div class="crm-pipeline-shell">
    <div class="d-sm-flex align-items-center justify-content-between mb-4">
        <div>
            <h1 class="h3 mb-1 text-gray-800">Pipeline CRM</h1>
            <p class="text-muted small mb-0">Solo leads con conversación Instagram DM (como bandeja principal).</p>
        </div>
        <div>
            <span class="text-muted small mr-2">Cuenta:</span>
            <select class="form-control form-control-sm d-inline-block" id="filter-recipient" style="width: auto; min-width: 150px;">
                <option value="">Todas</option>
            </select>
        </div>
        <div>
            <a href="<?= esc(site_url('app/crm/inbox')) ?>" class="btn btn-sm btn-outline-primary">
                <i class="fas fa-inbox"></i> Inbox
            </a>
            <a href="<?= esc(site_url('app/crm/export/meta')) ?>?score_min=50" class="btn btn-sm btn-outline-success ml-2">
                <i class="fas fa-file-export"></i> Exportar a Meta
            </a>
            <button type="button" id="btn-sync-instagram-graph" class="btn btn-sm btn-outline-info ml-2" title="Últimos 5 hilos DM desde Meta Graph → BD (solo conversaciones ya conocidas en el CRM)">
                <i class="fab fa-instagram"></i> Sync últimos DM (Meta)
            </button>
        </div>
    </div>

    <div class="d-flex pipeline-board-inner" id="pipeline-board">
        <div class="text-center py-5 w-100"><i class="fas fa-spinner fa-spin fa-2x"></i> Cargando pipeline...</div>
    </div>
</div>

<style>
/*
 * Pipeline: alto acotado al viewport (alinea con el sidebar / “Version 3.0”), sin scroll infinito en body.
 * El tablero usa solo el espacio entre navbar y footer; columnas con scroll interno.
 */
body.page-pipeline-crm {
    overflow-x: hidden;
}
body.page-pipeline-crm #wrapper {
    height: 100vh;
    height: 100dvh;
    max-height: 100vh;
    max-height: 100dvh;
    overflow: hidden;
    align-items: stretch;
}
/* Sidebar mismo alto que viewport; si hay muchos ítems, scroll interno (no empuja la página) */
body.page-pipeline-crm #wrapper > ul.sidebar,
body.page-pipeline-crm #wrapper > #accordionSidebar.sidebar {
    flex-shrink: 0;
    align-self: stretch;
    min-height: 0;
    max-height: 100vh;
    max-height: 100dvh;
    overflow-x: hidden;
    overflow-y: auto;
}
body.page-pipeline-crm #wrapper #content-wrapper {
    flex: 1 1 auto;
    min-width: 0;
    min-height: 0;
    max-height: 100vh;
    max-height: 100dvh;
    overflow: hidden;
}
body.page-pipeline-crm #wrapper #content-wrapper #content {
    flex: 1 1 auto !important;
    display: flex !important;
    flex-direction: column !important;
    min-height: 0 !important;
}
body.page-pipeline-crm #content > nav.navbar {
    flex-shrink: 0;
}
body.page-pipeline-crm #container-wrapper {
    flex: 1 1 auto;
    display: flex;
    flex-direction: column;
    min-height: 0;
    /* Quita el hueco típico de .container-fluid (1.5rem); el Kanban arranca junto al sidebar */
    padding-left: 0 !important;
    padding-right: 0 !important;
}
body.page-pipeline-crm #container-wrapper > .alert {
    margin-left: 1rem;
    margin-right: 1rem;
}
body.page-pipeline-crm .crm-pipeline-shell > .d-sm-flex.mb-4 {
    flex-shrink: 0;
    padding-left: 1rem;
    padding-right: 1rem;
}
body.page-pipeline-crm #content-wrapper > footer.sticky-footer {
    flex-shrink: 0;
    margin-top: 1rem !important;
}
.crm-pipeline-shell {
    display: flex;
    flex-direction: column;
    flex: 1 1 auto;
    min-height: 0;
}
#pipeline-board.pipeline-board-inner {
    flex: 1 1 auto;
    min-height: 0;
    overflow-x: auto;
    overflow-y: hidden;
    gap: 12px;
    padding-bottom: 8px;
    padding-left: 0.75rem;
    padding-right: 0.75rem;
    align-items: stretch;
    scrollbar-width: thin;
}

/* Scroll horizontal más discreto (muchas columnas siguen necesitando scroll) */
#pipeline-board::-webkit-scrollbar {
    height: 6px;
}
#pipeline-board::-webkit-scrollbar-track {
    background: transparent;
}
#pipeline-board::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.18);
    border-radius: 4px;
}
#pipeline-board::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.28);
}

.pipeline-column {
    min-width: 260px;
    max-width: 280px;
    flex-shrink: 0;
    background: #ebecf0; /* Trello-like background */
    border-radius: 8px;
    display: flex;
    flex-direction: column;
    height: 100%;
}
.pipeline-column-header {
    padding: 10px 15px;
    font-weight: 700;
    font-size: 13px;
    border-bottom: 3px solid;
    border-radius: 8px 8px 0 0;
    display: flex;
    justify-content: space-between;
    align-items: center;
}
.pipeline-column-body {
    padding: 8px;
    flex-grow: 1;
    overflow-y: auto;
    transition: background-color 0.15s ease;
}
/* Custom Scrollbar for Column Body */
.pipeline-column-body::-webkit-scrollbar {
    width: 8px;
}
.pipeline-column-body::-webkit-scrollbar-track {
    background: transparent;
}
.pipeline-column-body::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.15);
    border-radius: 4px;
}
.pipeline-column-body::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.25);
}
.pipeline-column-body.pipeline-drop-target {
    background-color: rgba(78, 115, 223, 0.12);
    outline: 2px dashed #4e73df;
    outline-offset: -2px;
}
.pipeline-card {
    background: white;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    cursor: grab;
    transition: box-shadow 0.2s;
    border-left: 3px solid;
}
.pipeline-card:active {
    cursor: grabbing;
}
.pipeline-card:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
}
.pipeline-card.dragging {
    opacity: 0.55;
}
.pipeline-card .card-name {
    font-weight: 600;
    font-size: 13px;
    margin-bottom: 4px;
}
.pipeline-card .card-info {
    font-size: 11px;
    color: #858796;
}
.pipeline-card .card-score {
    font-size: 11px;
    font-weight: 700;
}
.pipeline-card .card-ig-account {
    font-size: 11px;
    font-weight: 600;
    color: #c13584;
}
</style>

<script>
/** Evita que el click navegue al Inbox justo después de soltar el drag (comportamiento típico del navegador). */
var pipelineSuppressCardClick = false;

var CRM_PIPELINE_URL = <?= json_encode($crmPipelineApi) ?>;
var CRM_PIPELINE_MOVE_URL = <?= json_encode($crmPipelineMove) ?>;
var CRM_PIPELINE_SYNC_IG_URL = <?= json_encode($crmPipelineSyncIg) ?>;
var CRM_INBOX_URL = <?= json_encode($crmInboxBase) ?>;

$(document).ready(function() {
    loadPipeline();
    // Load ATC users for assignment
    $.get('/app/options/get_atc_users', function(r) {
        window.atcUsers = r.data || [];
    });
    // Assign lead to ATC on select change
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
                    loadPipeline();
                } else {
                    var msg = (res.message || 'Error');
                    if (res.data && res.data.graph_error) {
                        msg += '<br><small>' + escapeHtml(JSON.stringify(res.data.graph_error)) + '</small>';
                    }
                    Swal.fire({
                        icon: 'error',
                        title: 'Graph API',
                        html: msg,
                        confirmButtonColor: '#e74a3b'
                    });
                }
            })
            .fail(function () {
                Swal.fire({
                    icon: 'error',
                    title: 'Red',
                    text: 'No se pudo llamar al servidor.',
                    confirmButtonColor: '#e74a3b'
                });
            })
            .always(function () {
                $btn.prop('disabled', false);
            });
    });
});

function escapeHtml(s) {
    if (s === null || s === undefined) return '';
    return String(s).replace(/[&<>"']/g, function(c) {
        return ({ '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' })[c];
    });
}

function formatIgBusinessLineHtml(lead) {
    if (lead.channel !== 'instagram') return '';
    var uname = (lead.recipient_ig_username || '').replace(/^@/, '').trim();
    if (uname) {
        return '<div class="card-ig-account mt-1">ig: @' + escapeHtml(uname) + '</div>';
    }
    var rid = lead.recipient_ig_id;
    if (rid) {
        var ridStr = String(rid);
        return '<div class="card-ig-account mt-1 text-muted small" title="' + escapeHtml(ridStr) + '">ig: · '
            + escapeHtml(ridStr.slice(0, 14)) + (ridStr.length > 14 ? '…' : '') + '</div>';
    }
    return '';
}

function loadPipeline() {
    $.get(CRM_PIPELINE_URL, function(response) {
        if (response.status === 'success') {
            renderPipeline(response.data);
            populateRecipientFilter(response.data);
        }
    });
}

function populateRecipientFilter(stages) {
    var usernames = {};
    stages.forEach(function(stage) {
        if (stage.leads) {
            stage.leads.forEach(function(lead) {
                var uname = (lead.recipient_ig_username || '').replace(/^@/, '').trim();
                if (uname) usernames[uname] = true;
            });
        }
    });
    var sel = $('#filter-recipient');
    sel.find('option:gt(0)').remove();
    Object.keys(usernames).sort().forEach(function(u) {
        sel.append('<option value="' + escapeHtml(u) + '">@' + escapeHtml(u) + '</option>');
    });
}

$(document).on('change', '#filter-recipient', function() {
    var val = $(this).val();
    $('.pipeline-card').each(function() {
        var cardRecipient = $(this).data('recipient') || '';
        if (!val || cardRecipient === val) {
            $(this).show();
        } else {
            $(this).hide();
        }
    });
    // Update column counts
    $('.pipeline-column').each(function() {
        var visible = $(this).find('.pipeline-card:visible').length;
        $(this).find('.badge').text(visible);
    });
});

function renderPipeline(stages) {
    const board = $('#pipeline-board');
    let html = '';

    const colors = ['#6c757d', '#17a2b8', '#ffc107', '#fd7e14', '#28a745', '#e74a3b', '#6f42c1', '#20c997', '#e83e8c', '#007bff', '#343a40', '#795548', '#ff9800', '#9c27b0', '#00bcd4'];

    stages.forEach((stage, idx) => {
        const color = colors[idx % colors.length];
        const leadCount = stage.leads ? stage.leads.length : 0;

        html += `
            <div class="pipeline-column" data-tracking-status-id="${stage.id}">
                <div class="pipeline-column-header" style="border-color: ${color}; background: ${color}10;">
                    <span>${stage.name}</span>
                    <span class="badge badge-secondary">${leadCount}</span>
                </div>
                <div class="pipeline-column-body" data-tracking-status-id="${stage.id}">
        `;

        if (stage.leads && stage.leads.length > 0) {
            stage.leads.forEach(lead => {
                const labelColor = getLabelColor(lead.intention_label);
                const channelIcon = lead.channel ? getChannelIcon(lead.channel) : '[--]';
                const convId = lead.conversation_id || 0;
                const inboxUrl = convId ? CRM_INBOX_URL + (CRM_INBOX_URL.indexOf('?') >= 0 ? '&' : '?') + 'open=' + convId : '#';

                html += `
                    <div class="pipeline-card" draggable="true" data-lead-id="${lead.lead_id}" data-conv-id="${convId}" data-tracking-status-id="${stage.id}" data-recipient="${lead.recipient_ig_username || ''}">
                        ${formatIgBusinessLineHtml(lead)}
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="card-name">${channelIcon} ${lead.lead_name || 'Sin nombre'}</div>
                            <div class="card-score" style="color: ${labelColor};">${lead.intention_score || 0}%</div>
                        </div>
                        <div class="card-info">
                            ${lead.interest_type ? '<span class="badge badge-info badge-sm mr-1">' + lead.interest_type + '</span>' : ''}
                            ${lead.budget_detected ? '<span class="badge badge-success badge-sm mr-1">$' + parseFloat(lead.budget_detected).toLocaleString() + '</span>' : ''}
                            ${lead.zone_interest ? '<span class="badge badge-secondary badge-sm">' + lead.zone_interest + '</span>' : ''}
                        </div>
                        <div class="card-info mt-1 d-flex justify-content-between align-items-center">
                            <span>${lead.agent_name ? '<i class="fas fa-user-check text-success"></i> ' + lead.agent_name : '<i class="fas fa-user-times text-muted"></i> Sin asignar'}</span>
                            ${convId ? '<a href="' + inboxUrl + '" class="btn btn-xs btn-outline-primary btn-sm py-0 px-1">Inbox</a>' : ''}
                        </div>
                        ${stage.id === 'unassigned' ? `
                        <div class="mt-1">
                            <select class="form-control form-control-sm assign-atc" data-lead-id="${lead.lead_id}" data-conv-id="${convId}" style="font-size:11px;">
                                <option value="">Asignar a ATC...</option>
                                ${(window.atcUsers || []).map(u => '<option value="'+u.id+'">'+u.full_name+'</option>').join('')}
                            </select>
                        </div>` : ''}
                    </div>
                `;
            });
        } else {
            html += '<div class="text-center text-muted py-3 pipeline-empty-hint" style="font-size: 12px;">Arrastra un lead aquí</div>';
        }

        html += '</div></div>';
    });

    if (!html) {
        html = '<div class="text-center text-muted py-5 w-100"><i class="fas fa-inbox fa-3x mb-3"></i><p>No hay datos en el pipeline</p></div>';
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
        if ($(e.target).closest('.btn').length) return;
        const convId = $(this).data('conv-id');
        if (convId) {
            window.location.href = CRM_INBOX_URL + (CRM_INBOX_URL.indexOf('?') >= 0 ? '&' : '?') + 'open=' + convId;
        } else {
            Swal.fire({
                icon: 'warning',
                title: 'Sin conversación',
                text: 'Este lead no tiene una conversación activa en el CRM.',
                confirmButtonColor: '#4e73df'
            });
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
        window.setTimeout(function () {
            pipelineSuppressCardClick = false;
        }, 400);
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
        if (!leadId || !newStatusId) return;

        const card = board.find('.pipeline-card[data-lead-id="' + leadId + '"]');
        const fromStatus = card.length ? card.data('tracking-status-id') : null;
        if (fromStatus && String(fromStatus) === String(newStatusId)) return;

        $.post(CRM_PIPELINE_MOVE_URL, {
            lead_id: leadId,
            trackingstatus_id: newStatusId
        }, function(res) {
            if (res.status === 'success') {
                loadPipeline();
            } else {
                Swal.fire({
                    icon: 'error',
                    title: 'Error',
                    text: res.message || 'No se pudo mover el lead',
                    confirmButtonColor: '#e74a3b'
                });
            }
        }).fail(function() {
            Swal.fire({
                icon: 'error',
                title: 'Error de red',
                text: 'Error de red al mover el lead',
                confirmButtonColor: '#e74a3b'
            });
        });
    });
}

function getChannelIcon(channel) {
    switch (channel) {
        case 'instagram': return '[IG]';
        case 'whatsapp': return '[WA]';
        case 'web': return '[Web]';
        default: return '[--]';
    }
}

function getLabelColor(label) {
    switch(label) {
        case 'frio': return '#4e73df';
        case 'tibio': return '#f6c23e';
        case 'caliente': return '#fd7e14';
        case 'listo': return '#e74a3b';
        default: return '#858796';
    }
}
</script>
