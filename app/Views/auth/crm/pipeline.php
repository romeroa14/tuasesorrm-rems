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

            <div class="d-flex" id="pipeline-board" style="overflow-x: auto; gap: 10px; min-height: 500px; padding-bottom: 20px;">
                <div class="text-center py-5 w-100"><i class="fas fa-spinner fa-spin fa-2x"></i> Cargando pipeline...</div>
            </div>
        </div>
    </div>
</div>

<style>
.pipeline-column {
    min-width: 250px;
    max-width: 280px;
    flex-shrink: 0;
    background: #f8f9fc;
    border-radius: 8px;
    display: flex;
    flex-direction: column;
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
    max-height: calc(100vh - 280px);
}
.pipeline-card {
    background: white;
    border-radius: 6px;
    padding: 10px 12px;
    margin-bottom: 8px;
    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
    cursor: pointer;
    transition: box-shadow 0.2s;
    border-left: 3px solid;
}
.pipeline-card:hover {
    box-shadow: 0 3px 8px rgba(0,0,0,0.15);
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
    loadPipeline();
});

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
            <div class="pipeline-column">
                <div class="pipeline-column-header" style="border-color: ${color}; background: ${color}10;">
                    <span>${stage.name}</span>
                    <span class="badge badge-secondary">${leadCount}</span>
                </div>
                <div class="pipeline-column-body" data-stage="${stage.id}">
        `;

        if (stage.leads && stage.leads.length > 0) {
            stage.leads.forEach(lead => {
                const labelColor = getLabelColor(lead.intention_label);
                const channelIcon = lead.channel ? getChannelIcon(lead.channel) : '📩';

                html += `
                    <div class="pipeline-card" style="border-color: ${labelColor};" onclick="openLeadFromPipeline(${lead.conversation_id || 0}, ${lead.lead_id})">
                        <div class="d-flex justify-content-between align-items-start">
                            <div class="card-name">${channelIcon} ${lead.lead_name || 'Sin nombre'}</div>
                            <div class="card-score" style="color: ${labelColor};">${lead.intention_score || 0}%</div>
                        </div>
                        <div class="card-info">
                            ${lead.interest_type ? '<span class="badge badge-info badge-sm mr-1">' + lead.interest_type + '</span>' : ''}
                            ${lead.budget_detected ? '<span class="badge badge-success badge-sm mr-1">$' + parseFloat(lead.budget_detected).toLocaleString() + '</span>' : ''}
                            ${lead.zone_interest ? '<span class="badge badge-secondary badge-sm">' + lead.zone_interest + '</span>' : ''}
                        </div>
                        <div class="card-info mt-1">
                            ${lead.agent_name ? '<i class="fas fa-user-check text-success"></i> ' + lead.agent_name : '<i class="fas fa-user-times text-muted"></i> Sin asignar'}
                        </div>
                    </div>
                `;
            });
        } else {
            html += '<div class="text-center text-muted py-3" style="font-size: 12px;">Sin leads</div>';
        }

        html += '</div></div>';
    });

    if (!html) {
        html = '<div class="text-center text-muted py-5 w-100"><i class="fas fa-inbox fa-3x mb-3"></i><p>No hay datos en el pipeline</p></div>';
    }

    board.html(html);
}

function openLeadFromPipeline(conversationId, leadId) {
    if (conversationId) {
        window.location.href = '/app/crm/inbox?open=' + conversationId;
    }
}

function getChannelIcon(channel) {
    switch(channel) {
        case 'instagram': return '📷';
        case 'whatsapp': return '💬';
        case 'web': return '🌐';
        default: return '📩';
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
