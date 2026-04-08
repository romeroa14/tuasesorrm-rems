<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid" id="container-wrapper">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <h1 class="h3 mb-0 text-gray-800">CRM Inbox</h1>
                <div>
                    <span class="badge badge-danger" id="unread-badge">0 sin leer</span>
                    <a href="/app/crm/pipeline" class="btn btn-sm btn-outline-primary ml-2">
                        <i class="fas fa-columns"></i> Pipeline
                    </a>
                    <a href="/app/crm/dashboard" class="btn btn-sm btn-outline-info ml-2">
                        <i class="fas fa-chart-bar"></i> Estadísticas
                    </a>
                </div>
            </div>

            <div class="row" style="height: calc(100vh - 200px); min-height: 500px;">
                <!-- Conversation List -->
                <div class="col-md-4 col-lg-3 pr-0">
                    <div class="card h-100 mb-0" style="border-radius: 0;">
                        <!-- Filters -->
                        <div class="card-header py-2">
                            <div class="input-group input-group-sm mb-2">
                                <input type="text" class="form-control" id="search-conversations" placeholder="Buscar...">
                                <div class="input-group-append">
                                    <button class="btn btn-outline-secondary" type="button"><i class="fas fa-search"></i></button>
                                </div>
                            </div>
                            <div class="d-flex flex-wrap gap-1">
                                <select class="form-control form-control-sm mb-1" id="filter-channel" style="font-size: 11px;">
                                    <option value="">Todos los canales</option>
                                    <option value="instagram">📷 Instagram</option>
                                    <option value="whatsapp">💬 WhatsApp</option>
                                    <option value="web">🌐 Web</option>
                                </select>
                                <select class="form-control form-control-sm mb-1" id="filter-label" style="font-size: 11px;">
                                    <option value="">Todas las intenciones</option>
                                    <option value="frio">🔵 Frío</option>
                                    <option value="tibio">🟡 Tibio</option>
                                    <option value="caliente">🟠 Caliente</option>
                                    <option value="listo">🔴 Listo</option>
                                </select>
                                <select class="form-control form-control-sm" id="filter-status" style="font-size: 11px;">
                                    <option value="">Todos</option>
                                    <option value="open" selected>Abiertos</option>
                                    <option value="assigned">Asignados</option>
                                    <option value="resolved">Resueltos</option>
                                </select>
                            </div>
                        </div>
                        <!-- Conversation list -->
                        <div class="card-body p-0" id="conversation-list" style="overflow-y: auto;">
                            <div class="text-center py-5 text-muted" id="empty-conversations">
                                <i class="fas fa-inbox fa-3x mb-3"></i>
                                <p>No hay conversaciones</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Chat Area -->
                <div class="col-md-5 col-lg-6 px-0">
                    <div class="card h-100 mb-0" style="border-radius: 0;">
                        <!-- Chat Header -->
                        <div class="card-header py-2 d-flex align-items-center" id="chat-header" style="display: none !important;">
                            <div class="d-flex align-items-center flex-grow-1">
                                <div class="rounded-circle bg-primary text-white d-flex align-items-center justify-content-center mr-2" style="width: 40px; height: 40px; font-size: 16px;" id="chat-avatar">?</div>
                                <div>
                                    <h6 class="mb-0" id="chat-lead-name">Selecciona una conversación</h6>
                                    <small class="text-muted" id="chat-channel-info"></small>
                                </div>
                            </div>
                            <div>
                                <button class="btn btn-sm btn-outline-warning" onclick="rescoreConversation()" title="Recalcular score">
                                    <i class="fas fa-brain"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-success" onclick="assignToMe()" title="Asignar a mí">
                                    <i class="fas fa-user-check"></i>
                                </button>
                                <button class="btn btn-sm btn-outline-secondary" onclick="resolveConversation()" title="Resolver">
                                    <i class="fas fa-check"></i>
                                </button>
                            </div>
                        </div>

                        <!-- Messages Area -->
                        <div class="card-body p-3" id="messages-area" style="overflow-y: auto; background-color: #e5ddd5;">
                            <div class="text-center py-5" id="no-chat-selected">
                                <i class="fas fa-comments fa-4x text-muted mb-3"></i>
                                <h5 class="text-muted">Selecciona una conversación</h5>
                                <p class="text-muted">Los mensajes aparecerán aquí</p>
                            </div>
                        </div>

                        <!-- Message Input -->
                        <div class="card-footer py-2" id="message-input-area" style="display: none;">
                            <div class="input-group">
                                <input type="text" class="form-control" id="message-input" placeholder="Escribe un mensaje..." 
                                    onkeypress="if(event.key==='Enter') sendMessage()">
                                <div class="input-group-append">
                                    <button class="btn btn-primary" onclick="sendMessage()">
                                        <i class="fas fa-paper-plane"></i>
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Lead Detail Panel -->
                <div class="col-md-3 col-lg-3 pl-0">
                    <div class="card h-100 mb-0" style="border-radius: 0;">
                        <div class="card-header py-2">
                            <h6 class="mb-0"><i class="fas fa-user"></i> Detalle del Lead</h6>
                        </div>
                        <div class="card-body" id="lead-detail-panel" style="overflow-y: auto;">
                            <div class="text-center py-5 text-muted" id="no-lead-selected">
                                <i class="fas fa-user fa-3x mb-3"></i>
                                <p>Selecciona una conversación</p>
                            </div>

                            <div id="lead-detail-content" style="display: none;">
                                <!-- Score Bar -->
                                <div class="mb-3">
                                    <label class="font-weight-bold">Score de Intención</label>
                                    <div class="progress" style="height: 25px;">
                                        <div class="progress-bar" id="score-bar" role="progressbar" style="width: 0%">
                                            <span id="score-text">0%</span>
                                        </div>
                                    </div>
                                    <span class="badge mt-1" id="score-label-badge">-</span>
                                </div>

                                <!-- Lead Info -->
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Nombre</label>
                                    <span id="detail-name">-</span>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Canal</label>
                                    <span id="detail-channel">-</span>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Instagram</label>
                                    <span id="detail-instagram">-</span>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Teléfono</label>
                                    <span id="detail-phone">-</span>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Email</label>
                                    <span id="detail-email">-</span>
                                </div>
                                <hr>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Interés Detectado</label>
                                    <span id="detail-interest" class="badge badge-info">-</span>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Presupuesto</label>
                                    <span id="detail-budget">-</span>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Zona de Interés</label>
                                    <span id="detail-zone">-</span>
                                </div>
                                <div class="mb-3">
                                    <label class="font-weight-bold d-block">Agente Asignado</label>
                                    <span id="detail-agent">Sin asignar</span>
                                </div>
                                <hr>
                                <!-- Actions -->
                                <div class="mb-2">
                                    <a href="#" id="detail-lead-link" class="btn btn-sm btn-block btn-outline-primary">
                                        <i class="fas fa-external-link-alt"></i> Ver Lead Completo
                                    </a>
                                </div>
                                <div class="mb-2">
                                    <button class="btn btn-sm btn-block btn-outline-success" onclick="exportSingleLead()">
                                        <i class="fas fa-file-export"></i> Exportar para Meta
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.conversation-item {
    padding: 12px 15px;
    border-bottom: 1px solid #e3e6f0;
    cursor: pointer;
    transition: background-color 0.2s;
}
.conversation-item:hover {
    background-color: #f8f9fc;
}
.conversation-item.active {
    background-color: #e2e6ea;
    border-left: 3px solid #4e73df;
}
.conversation-item .conv-name {
    font-weight: 600;
    font-size: 14px;
    margin-bottom: 2px;
}
.conversation-item .conv-preview {
    font-size: 12px;
    color: #858796;
    white-space: nowrap;
    overflow: hidden;
    text-overflow: ellipsis;
    max-width: 200px;
}
.conversation-item .conv-time {
    font-size: 11px;
    color: #b7b9cc;
}
.conversation-item .conv-unread {
    background: #e74a3b;
    color: white;
    border-radius: 50%;
    width: 20px;
    height: 20px;
    font-size: 11px;
    display: flex;
    align-items: center;
    justify-content: center;
}

/* Chat bubbles */
.message-bubble {
    max-width: 70%;
    padding: 8px 14px;
    border-radius: 12px;
    margin-bottom: 8px;
    position: relative;
    word-wrap: break-word;
}
.message-bubble.inbound {
    background: white;
    margin-right: auto;
    border-bottom-left-radius: 4px;
}
.message-bubble.outbound {
    background: #dcf8c6;
    margin-left: auto;
    border-bottom-right-radius: 4px;
}
.message-bubble .msg-time {
    font-size: 10px;
    color: #999;
    text-align: right;
    margin-top: 4px;
}
.message-bubble .msg-sender {
    font-size: 11px;
    font-weight: 600;
    color: #4e73df;
    margin-bottom: 2px;
}

/* Score colors */
.score-frio { background-color: #4e73df; }
.score-tibio { background-color: #f6c23e; }
.score-caliente { background-color: #fd7e14; }
.score-listo { background-color: #e74a3b; }

/* Channel icons */
.channel-instagram { color: #E1306C; }
.channel-whatsapp { color: #25D366; }
.channel-web { color: #4e73df; }
</style>

<script>
let currentConversationId = null;
let conversations = [];
let refreshInterval = null;

$(document).ready(function() {
    loadConversations();
    // Auto-refresh every 10 seconds
    refreshInterval = setInterval(loadConversations, 10000);

    // Filter change handlers
    $('#filter-channel, #filter-label, #filter-status').on('change', loadConversations);
    
    // Search
    $('#search-conversations').on('input', function() {
        const search = $(this).val().toLowerCase();
        $('.conversation-item').each(function() {
            const name = $(this).find('.conv-name').text().toLowerCase();
            $(this).toggle(name.includes(search));
        });
    });

    // Check URL param for auto-open
    const urlParams = new URLSearchParams(window.location.search);
    const openId = urlParams.get('open');
    if (openId) {
        setTimeout(() => openConversation(parseInt(openId)), 500);
    }
});

function loadConversations() {
    const params = new URLSearchParams({
        channel: $('#filter-channel').val() || '',
        intention_label: $('#filter-label').val() || '',
        status: $('#filter-status').val() || '',
    });

    $.get('/app/crm/api/conversations?' + params, function(response) {
        if (response.status === 'success') {
            conversations = response.data;
            renderConversationList(conversations);
            
            // Update unread badge
            const totalUnread = conversations.reduce((sum, c) => sum + (parseInt(c.unread_count) || 0), 0);
            $('#unread-badge').text(totalUnread + ' sin leer');
        }
    });
}

function renderConversationList(convs) {
    const list = $('#conversation-list');
    
    if (convs.length === 0) {
        list.html(`
            <div class="text-center py-5 text-muted">
                <i class="fas fa-inbox fa-3x mb-3"></i>
                <p>No hay conversaciones</p>
            </div>
        `);
        return;
    }

    let html = '';
    convs.forEach(conv => {
        const isActive = conv.id == currentConversationId ? 'active' : '';
        const channelIcon = getChannelIcon(conv.channel);
        const labelColor = getLabelColor(conv.intention_label);
        const unread = parseInt(conv.unread_count) > 0 ? `<div class="conv-unread">${conv.unread_count}</div>` : '';
        const timeAgo = formatTimeAgo(conv.last_message_at);
        const preview = conv.last_message ? conv.last_message.substring(0, 40) + (conv.last_message.length > 40 ? '...' : '') : 'Sin mensajes';

        html += `
            <div class="conversation-item ${isActive}" onclick="openConversation(${conv.id})" data-id="${conv.id}">
                <div class="d-flex justify-content-between align-items-start">
                    <div class="flex-grow-1">
                        <div class="d-flex align-items-center">
                            <span class="mr-1">${channelIcon}</span>
                            <span class="conv-name">${conv.lead_name || 'Sin nombre'}</span>
                            <span class="badge badge-sm ml-1" style="background:${labelColor}; color:white; font-size:10px;">${conv.intention_score || 0}%</span>
                        </div>
                        <div class="conv-preview">${preview}</div>
                    </div>
                    <div class="text-right ml-2">
                        <div class="conv-time">${timeAgo}</div>
                        ${unread}
                    </div>
                </div>
            </div>
        `;
    });

    list.html(html);
}

function openConversation(id) {
    currentConversationId = id;
    
    // Highlight active
    $('.conversation-item').removeClass('active');
    $(`.conversation-item[data-id="${id}"]`).addClass('active');

    // Show loading
    $('#no-chat-selected').hide();
    $('#messages-area').html('<div class="text-center py-5"><i class="fas fa-spinner fa-spin fa-2x"></i></div>');
    
    $.get(`/app/crm/api/messages/${id}`, function(response) {
        if (response.status === 'success') {
            renderMessages(response.data.messages);
            renderLeadDetail(response.data.conversation);
            
            // Show input area
            $('#message-input-area').show();
            $('#chat-header').css('display', '').removeClass('d-none').show();
            
            // Update header
            const conv = response.data.conversation;
            $('#chat-lead-name').text(conv.lead_name || 'Sin nombre');
            $('#chat-channel-info').html(getChannelIcon(conv.channel) + ' ' + (conv.external_username || conv.channel));
            $('#chat-avatar').text((conv.lead_name || '?')[0].toUpperCase());

            // Scroll to bottom
            scrollToBottom();
        }
    });
}

function renderMessages(messages) {
    if (messages.length === 0) {
        $('#messages-area').html('<div class="text-center py-5 text-muted"><p>No hay mensajes aún</p></div>');
        return;
    }

    let html = '';
    let lastDate = '';

    messages.forEach(msg => {
        const msgDate = msg.created_at ? msg.created_at.split(' ')[0] : '';
        if (msgDate !== lastDate) {
            html += `<div class="text-center my-3"><span class="badge badge-light px-3 py-1">${formatDate(msgDate)}</span></div>`;
            lastDate = msgDate;
        }

        const direction = msg.direction;
        const senderName = direction === 'outbound' ? (msg.sender_name || 'Agente') : '';
        const time = msg.created_at ? msg.created_at.split(' ')[1].substring(0, 5) : '';

        html += `
            <div class="d-flex ${direction === 'outbound' ? 'justify-content-end' : 'justify-content-start'}">
                <div class="message-bubble ${direction}">
                    ${direction === 'outbound' ? `<div class="msg-sender">${senderName}</div>` : ''}
                    <div>${escapeHtml(msg.content)}</div>
                    <div class="msg-time">${time} ${direction === 'outbound' ? '✓✓' : ''}</div>
                </div>
            </div>
        `;
    });

    $('#messages-area').html(html);
}

function renderLeadDetail(conv) {
    if (!conv) return;

    $('#no-lead-selected').hide();
    $('#lead-detail-content').show();

    const score = parseInt(conv.intention_score) || 0;
    const label = conv.intention_label || 'frio';
    const labelColor = getLabelColor(label);
    const labelText = getLabelText(label);

    // Score bar
    $('#score-bar').css('width', score + '%').css('background-color', labelColor).attr('aria-valuenow', score);
    $('#score-text').text(score + '%');
    $('#score-label-badge').text(labelText).css('background-color', labelColor).css('color', 'white');

    // Lead info
    $('#detail-name').text(conv.lead_name || '-');
    $('#detail-channel').html(getChannelIcon(conv.channel) + ' ' + (conv.channel || '-'));
    $('#detail-instagram').text(conv.instagram_username || '-');
    $('#detail-phone').text(conv.lead_phone || '-');
    $('#detail-email').text(conv.lead_email || '-');
    $('#detail-interest').text(conv.interest_type || 'No detectado');
    $('#detail-budget').text(conv.budget_detected ? '$' + parseFloat(conv.budget_detected).toLocaleString() : 'No detectado');
    $('#detail-zone').text(conv.zone_interest || 'No detectada');
    $('#detail-agent').text(conv.agent_name || 'Sin asignar');
    $('#detail-lead-link').attr('href', '/app/leads/edit/' + conv.lead_id);
}

function sendMessage() {
    const content = $('#message-input').val().trim();
    if (!content || !currentConversationId) return;

    $.post('/app/crm/api/send', {
        conversation_id: currentConversationId,
        content: content
    }, function(response) {
        if (response.status === 'success') {
            // Add message to chat
            const now = new Date();
            const time = now.getHours().toString().padStart(2,'0') + ':' + now.getMinutes().toString().padStart(2,'0');
            const html = `
                <div class="d-flex justify-content-end">
                    <div class="message-bubble outbound">
                        <div class="msg-sender"><?= session()->get('full_name') ?></div>
                        <div>${escapeHtml(content)}</div>
                        <div class="msg-time">${time} ✓✓</div>
                    </div>
                </div>
            `;
            $('#messages-area').append(html);
            $('#message-input').val('');
            scrollToBottom();
        }
    });
}

function assignToMe() {
    if (!currentConversationId) return;
    $.post('/app/crm/api/assign', {
        conversation_id: currentConversationId,
        agent_id: <?= session()->get('id') ?>
    }, function() {
        loadConversations();
        openConversation(currentConversationId);
    });
}

function resolveConversation() {
    if (!currentConversationId) return;
    $.post('/app/crm/api/status', {
        conversation_id: currentConversationId,
        status: 'resolved'
    }, function() {
        loadConversations();
    });
}

function rescoreConversation() {
    if (!currentConversationId) return;
    $.get('/app/crm/api/rescore/' + currentConversationId, function(response) {
        if (response.status === 'success') {
            alert('Score actualizado: ' + response.data.score + '% (' + response.data.label + ')\n' + response.data.reasoning);
            openConversation(currentConversationId);
            loadConversations();
        }
    });
}

function exportSingleLead() {
    // TODO: Implement single lead export
    alert('Funcionalidad de exportación disponible pronto');
}

// Utility functions
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

function getLabelText(label) {
    switch(label) {
        case 'frio': return '❄️ Frío';
        case 'tibio': return '🌤️ Tibio';
        case 'caliente': return '🔥 Caliente';
        case 'listo': return '🎯 Listo';
        default: return label;
    }
}

function formatTimeAgo(dateStr) {
    if (!dateStr) return '';
    const date = new Date(dateStr);
    const now = new Date();
    const diff = Math.floor((now - date) / 1000);
    
    if (diff < 60) return 'ahora';
    if (diff < 3600) return Math.floor(diff / 60) + ' min';
    if (diff < 86400) return Math.floor(diff / 3600) + ' h';
    if (diff < 604800) return Math.floor(diff / 86400) + ' d';
    return dateStr.split(' ')[0];
}

function formatDate(dateStr) {
    if (!dateStr) return '';
    const today = new Date().toISOString().split('T')[0];
    if (dateStr === today) return 'Hoy';
    const yesterday = new Date(Date.now() - 86400000).toISOString().split('T')[0];
    if (dateStr === yesterday) return 'Ayer';
    return dateStr;
}

function scrollToBottom() {
    const area = document.getElementById('messages-area');
    area.scrollTop = area.scrollHeight;
}

function escapeHtml(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
}
</script>
