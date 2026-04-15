<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid" id="container-wrapper">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">Pipeline CRM</h1>
                <div>
                    <a href="/app/crm/inbox" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-inbox"></i> Inbox
                    </a>
                    <a href="/app/crm/export/meta?score_min=50" class="btn btn-sm btn-outline-success ml-2">
                        <i class="fas fa-file-export"></i> Exportar a Meta
                    </a>
                </div>
            </div>

            <div id="pipeline-counts-panel" class="card mb-3 shadow-sm" style="display:none;">
                <div class="card-body py-2">
                    <div class="d-flex flex-wrap align-items-center justify-content-between">
                        <div class="small text-muted mb-1 mb-md-0">
                            <strong>BD:</strong> <code>trackingstatus</code> → <code>assignedclients.trackingstatus_id</code> · un lead → máx.1 fila en <code>assignedclients</code> (<code>lead_id</code> UNIQUE)
                        </div>
                        <div id="pipeline-counts-badges" class="d-flex flex-wrap gap-1"></div>
                    </div>
                    <div id="pipeline-orphan-hint" class="small text-warning mt-2" style="display:none;"></div>
                </div>
            </div>

            <div class="d-flex" id="pipeline-board" style="overflow-x: auto; gap: 12px; height: calc(100vh - 180px); padding-bottom: 12px;">
                <div class="text-center py-5 w-100"><i class="fas fa-spinner fa-spin fa-2x"></i> Cargando pipeline...</div>
            </div>
        </div>
    </div>
</div>

<style>
/* Custom Scrollbar for Pipeline Board */
#pipeline-board::-webkit-scrollbar {
    height: 12px;
}
#pipeline-board::-webkit-scrollbar-track {
    background: rgba(0, 0, 0, 0.05);
    border-radius: 6px;
}
#pipeline-board::-webkit-scrollbar-thumb {
    background: rgba(0, 0, 0, 0.2);
    border-radius: 6px;
}
#pipeline-board::-webkit-scrollbar-thumb:hover {
    background: rgba(0, 0, 0, 0.3);
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
</style>

<script>
$(document).ready(function() {
    loadPipelineCounts();
    loadPipeline();
});

function loadPipelineCounts() {
    $.get('/app/crm/api/pipeline/counts', function(response) {
        if (response.status !== 'success' || !response.data) return;
        const d = response.data;
        const panel = $('#pipeline-counts-panel');
        const badges = $('#pipeline-counts-badges');
        badges.empty();

        (d.leads_by_tracking_status || []).forEach(function(row) {
            badges.append(
                $('<span class="badge badge-light border text-dark mr-1 mb-1"></span>')
                    .text(row.name + ': ' + row.lead_count)
            );
        });

        const orphan = d.crm_leads_with_conversation_but_no_assignedclients_row;
        const hint = $('#pipeline-orphan-hint');
        if (orphan > 0) {
            hint.text('Leads con conversación CRM pero sin fila en assignedclients: ' + orphan + ' (al moverlos desde el Kanban se crea la asignación).').show();
        } else {
            hint.hide();
        }

        panel.show();
    });
}

function loadPipeline() {
    $.get('/app/crm/api/pipeline', function(response) {
        if (response.status === 'success') {
            renderPipeline(response.data);
        }
    });
}

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
                const inboxUrl = convId ? '/app/crm/inbox?open=' + convId : '#';

                html += `
                    <div class="pipeline-card" draggable="true" data-lead-id="${lead.lead_id}" data-conv-id="${convId}" data-tracking-status-id="${stage.id}">
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
        if ($(e.target).closest('.btn').length) return;
        const convId = $(this).data('conv-id');
        if (convId) {
            window.location.href = '/app/crm/inbox?open=' + convId;
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
            ev.dataTransfer.setData('text/plain', $(this).data('lead-id'));
            ev.dataTransfer.effectAllowed = 'move';
        }
        $(this).addClass('dragging');
    });

    board.on('dragend.pipelineDnd', '.pipeline-card', function() {
        $(this).removeClass('dragging');
        board.find('.pipeline-column-body').removeClass('pipeline-drop-target');
    });

    board.on('dragover.pipelineDnd', '.pipeline-column-body', function(e) {
        e.preventDefault();
        const ev = e.originalEvent;
        if (ev.dataTransfer) ev.dataTransfer.dropEffect = 'move';
        $(this).addClass('pipeline-drop-target');
    });

    board.on('dragleave.pipelineDnd', '.pipeline-column-body', function() {
        $(this).removeClass('pipeline-drop-target');
    });

    board.on('drop.pipelineDnd', '.pipeline-column-body', function(e) {
        e.preventDefault();
        const body = $(this);
        body.removeClass('pipeline-drop-target');

        const ev = e.originalEvent;
        const leadId = ev.dataTransfer ? ev.dataTransfer.getData('text/plain') : null;
        const newStatusId = body.data('tracking-status-id');
        if (!leadId || !newStatusId) return;

        const card = board.find('.pipeline-card[data-lead-id="' + leadId + '"]');
        const fromStatus = card.length ? card.data('tracking-status-id') : null;
        if (fromStatus && String(fromStatus) === String(newStatusId)) return;

        $.post('/app/crm/api/pipeline/move', {
            lead_id: leadId,
            trackingstatus_id: newStatusId
        }, function(res) {
            if (res.status === 'success') {
                loadPipelineCounts();
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
