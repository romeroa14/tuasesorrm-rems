<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid" id="container-wrapper">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">CRM Dashboard</h1>
                <div>
                    <a href="/app/crm/inbox" class="btn btn-sm btn-outline-primary"><i class="fas fa-inbox"></i> Inbox</a>
                    <a href="/app/crm/pipeline" class="btn btn-sm btn-outline-info ml-2"><i class="fas fa-columns"></i> Pipeline</a>
                </div>
            </div>

            <!-- Stats Cards -->
            <div class="row mb-4">
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-primary h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-primary text-uppercase mb-1">Total Leads</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-total-leads">-</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-users fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-success h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-success text-uppercase mb-1">Conversaciones Abiertas</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-open">-</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-comments fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-warning h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-warning text-uppercase mb-1">Sin Asignar</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-unassigned">-</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-user-times fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="col-xl-3 col-md-6 mb-4">
                    <div class="card border-left-info h-100">
                        <div class="card-body">
                            <div class="row align-items-center">
                                <div class="col mr-2">
                                    <div class="text-xs font-weight-bold text-info text-uppercase mb-1">Total Conversaciones</div>
                                    <div class="h5 mb-0 font-weight-bold text-gray-800" id="stat-total-conv">-</div>
                                </div>
                                <div class="col-auto"><i class="fas fa-envelope fa-2x text-gray-300"></i></div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row">
                <!-- By Label Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Leads por Intención</h6>
                        </div>
                        <div class="card-body">
                            <div style="position: relative; height: 300px;">
                                <canvas id="labelChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
                <!-- By Channel Chart -->
                <div class="col-lg-6 mb-4">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Conversaciones por Canal</h6>
                        </div>
                        <div class="card-body">
                            <div style="position: relative; height: 300px;">
                                <canvas id="channelChart"></canvas>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Intention Labels Summary -->
            <div class="row mb-4">
                <div class="col-lg-12">
                    <div class="card shadow">
                        <div class="card-header py-3 d-flex align-items-center justify-content-between">
                            <h6 class="m-0 font-weight-bold text-primary">Distribución de Intención</h6>
                        </div>
                        <div class="card-body">
                            <div class="row" id="label-summary-row">
                                <!-- Populated by JS -->
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Recent Scoring Activity -->
            <div class="row">
                <div class="col-12">
                    <div class="card shadow">
                        <div class="card-header py-3">
                            <h6 class="m-0 font-weight-bold text-primary">Actividad de Scoring Reciente</h6>
                        </div>
                        <div class="card-body">
                            <div class="table-responsive">
                                <table class="table table-sm table-hover" id="scoring-table">
                                    <thead>
                                        <tr>
                                            <th>Lead</th>
                                            <th>Score Anterior</th>
                                            <th>Score Nuevo</th>
                                            <th>Etiqueta</th>
                                            <th>Razón</th>
                                            <th>Fecha</th>
                                        </tr>
                                    </thead>
                                    <tbody id="scoring-tbody">
                                        <tr><td colspan="6" class="text-center">Cargando...</td></tr>
                                    </tbody>
                                </table>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
$(document).ready(function() {
    loadCrmStats();
});

function loadCrmStats() {
    $.get('/app/crm/api/stats', function(response) {
        if (response.status !== 'success') return;
        const data = response.data;

        $('#stat-total-leads').text(data.total_leads);
        $('#stat-open').text(data.open_conversations);
        $('#stat-unassigned').text(data.unassigned);
        $('#stat-total-conv').text(data.total_conversations);

        // Label chart
        if (typeof Chart !== 'undefined') {
            const labels = data.by_label.map(l => getLabelText(l.intention_label));
            const values = data.by_label.map(l => parseInt(l.total));
            const colors = data.by_label.map(l => getLabelColor(l.intention_label));

            new Chart(document.getElementById('labelChart'), {
                type: 'doughnut',
                data: {
                    labels: labels,
                    datasets: [{
                        data: values,
                        backgroundColor: colors,
                        borderWidth: 2,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    legend: { position: 'bottom' }
                }
            });

            // Channel chart
            const chLabels = data.by_channel.map(c => c.channel);
            const chValues = data.by_channel.map(c => parseInt(c.total));
            const chColors = data.by_channel.map(c => {
                switch(c.channel) {
                    case 'instagram': return '#E1306C';
                    case 'whatsapp': return '#25D366';
                    case 'web': return '#4e73df';
                    default: return '#858796';
                }
            });

            new Chart(document.getElementById('channelChart'), {
                type: 'bar',
                data: {
                    labels: chLabels,
                    datasets: [{
                        label: 'Conversaciones',
                        data: chValues,
                        backgroundColor: chColors,
                        borderWidth: 1,
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        yAxes: [{ ticks: { beginAtZero: true } }]
                    }
                }
            });
        }

        // Label summary row
        const labelOrder = ['frio', 'tibio', 'caliente', 'listo'];
        const labelMap = {};
        data.by_label.forEach(l => { labelMap[l.intention_label] = l.total; });
        
        let summaryHtml = '';
        labelOrder.forEach(label => {
            const count = labelMap[label] || 0;
            const color = getLabelColor(label);
            const text = getLabelText(label);
            summaryHtml += `
                <div class="col-xl-3 col-md-6 mb-3">
                    <div class="card" style="border-left: 4px solid ${color};">
                        <div class="card-body py-3">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <div class="text-xs font-weight-bold text-uppercase mb-1" style="color: ${color};">${text}</div>
                                    <div class="h4 mb-0 font-weight-bold">${count}</div>
                                </div>
                                <div class="rounded-circle d-flex align-items-center justify-content-center" style="width: 45px; height: 45px; background: ${color}20;">
                                    <span style="font-size: 20px;">${getLabelEmoji(label)}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            `;
        });
        $('#label-summary-row').html(summaryHtml);

        // Scoring table
        let tbody = '';
        if (data.recent_scores && data.recent_scores.length > 0) {
            data.recent_scores.forEach(s => {
                const labelColor = getLabelColor(s.new_label);
                const trend = s.new_score > s.previous_score ? '↑' : (s.new_score < s.previous_score ? '↓' : '→');
                const trendColor = s.new_score > s.previous_score ? 'text-success' : (s.new_score < s.previous_score ? 'text-danger' : 'text-muted');
                tbody += `
                    <tr>
                        <td><strong>${s.lead_name}</strong></td>
                        <td>${s.previous_score}%</td>
                        <td><strong style="color:${labelColor}">${s.new_score}% <span class="${trendColor}">${trend}</span></strong></td>
                        <td><span class="badge" style="background:${labelColor};color:white">${s.new_label}</span></td>
                        <td style="max-width:300px; font-size:12px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;" title="${s.ai_reasoning || ''}">${s.ai_reasoning || '-'}</td>
                        <td style="font-size:12px;">${s.created_at}</td>
                    </tr>
                `;
            });
        } else {
            tbody = '<tr><td colspan="6" class="text-center text-muted">Sin actividad de scoring aún</td></tr>';
        }
        $('#scoring-tbody').html(tbody);
    });
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

function getLabelText(label) {
    switch(label) {
        case 'frio': return 'Frío';
        case 'tibio': return 'Tibio';
        case 'caliente': return 'Caliente';
        case 'listo': return 'Listo';
        default: return label || 'Sin clasificar';
    }
}

function getLabelEmoji(label) {
    switch(label) {
        case 'frio': return '❄️';
        case 'tibio': return '🌤️';
        case 'caliente': return '🔥';
        case 'listo': return '🎯';
        default: return '❓';
    }
}
</script>
