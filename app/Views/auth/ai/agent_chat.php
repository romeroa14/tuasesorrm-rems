<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid" id="container-wrapper">
            <div class="d-sm-flex align-items-center justify-content-between mb-4">
                <div>
                    <h1 class="h3 mb-1 text-gray-800">Agente IA</h1>
                    <p class="mb-0 text-muted small">
                        Asistente con acceso al catálogo <strong>Aprobado</strong> (misma lógica que el microservicio Python).
                    </p>
                </div>
                <div class="mt-2 mt-sm-0">
                    <button type="button" class="btn btn-sm btn-outline-secondary" id="ai-chat-reset">
                        <i class="fas fa-redo-alt"></i> Nueva conversación
                    </button>
                    <a href="<?= esc(site_url('/app/crm/inbox')) ?>" class="btn btn-sm btn-outline-primary ml-2">
                        <i class="fas fa-inbox"></i> CRM Inbox
                    </a>
                </div>
            </div>

            <div class="row justify-content-center">
                <div class="col-lg-10 col-xl-8">
                    <div class="card shadow mb-4">
                        <div class="card-header py-3 d-flex justify-content-between align-items-center flex-wrap">
                            <span class="font-weight-bold text-primary">Chat</span>
                            <label class="mb-0 small text-muted">
                                <input type="checkbox" id="ai-chat-debug"> Depuración (tools / trazas)
                            </label>
                        </div>
                        <div class="card-body p-0">
                            <div id="ai-chat-log" class="border-bottom bg-light px-3 py-3" style="min-height: 320px; max-height: min(55vh, 520px); overflow-y: auto;">
                                <div class="text-muted small text-center py-5" id="ai-chat-placeholder">
                                    Escribe un mensaje para consultar propiedades o resolver dudas del inventario.
                                </div>
                            </div>
                            <div class="p-3">
                                <div id="ai-chat-alert" class="alert alert-danger d-none small mb-2" role="alert"></div>
                                <div id="ai-chat-debug-panel" class="alert alert-secondary d-none small mb-2 font-monospace" style="white-space: pre-wrap; max-height: 180px; overflow-y: auto;"></div>
                                <div class="input-group">
                                    <textarea id="ai-chat-input" class="form-control" rows="2" placeholder="Ej.: Apartamentos aprobados en renta hasta 800…"></textarea>
                                    <div class="input-group-append">
                                        <button class="btn btn-primary" type="button" id="ai-chat-send" title="Enviar">
                                            <i class="fas fa-paper-plane"></i>
                                        </button>
                                    </div>
                                </div>
                                <small class="text-muted">Enter para enviar · Shift+Enter nueva línea</small>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
(function () {
    var AI_CHAT_URL = <?= json_encode(site_url('app/ai/chat')) ?>;

    var logEl = document.getElementById('ai-chat-log');
    var inputEl = document.getElementById('ai-chat-input');
    var sendBtn = document.getElementById('ai-chat-send');
    var resetBtn = document.getElementById('ai-chat-reset');
    var alertEl = document.getElementById('ai-chat-alert');
    var debugCheck = document.getElementById('ai-chat-debug');
    var debugPanel = document.getElementById('ai-chat-debug-panel');
    var placeholder = document.getElementById('ai-chat-placeholder');

    /** @type {{role: string, content: string}[]} */
    var history = [];

    function hideAlert() {
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
    }

    function showAlert(msg) {
        alertEl.textContent = msg;
        alertEl.classList.remove('d-none');
    }

    function hideDebugPanel() {
        debugPanel.classList.add('d-none');
        debugPanel.textContent = '';
    }

    function appendBubble(role, text) {
        if (placeholder && placeholder.parentNode) {
            placeholder.parentNode.removeChild(placeholder);
            placeholder = null;
        }
        var wrap = document.createElement('div');
        wrap.className = 'mb-3 clearfix';
        var bubble = document.createElement('div');
        bubble.className = role === 'user'
            ? 'float-right bg-primary text-white rounded px-3 py-2 shadow-sm'
            : 'float-left bg-white border rounded px-3 py-2 shadow-sm';
        bubble.style.maxWidth = '85%';
        bubble.style.whiteSpace = 'pre-wrap';
        bubble.style.wordBreak = 'break-word';
        bubble.textContent = text;
        wrap.appendChild(bubble);
        logEl.appendChild(wrap);
        logEl.scrollTop = logEl.scrollHeight;
    }

    function setLoading(on) {
        sendBtn.disabled = on;
        inputEl.disabled = on;
    }

    function sendMessage() {
        var msg = (inputEl.value || '').trim();
        if (!msg) return;

        hideAlert();
        hideDebugPanel();

        var priorHistory = history.map(function (h) {
            return { role: h.role, content: h.content };
        });

        appendBubble('user', msg);
        inputEl.value = '';
        setLoading(true);

        $.ajax({
            url: AI_CHAT_URL,
            method: 'POST',
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            data: JSON.stringify({
                message: msg,
                history: priorHistory,
                debug: debugCheck.checked
            })
        }).done(function (res) {
            var reply = res && typeof res.reply === 'string' ? res.reply : '';
            if (!reply && res && res.detail && typeof res.detail.message === 'string') {
                showAlert(res.detail.message);
                setLoading(false);
                return;
            }
            if (!reply && res && typeof res.message === 'string') {
                showAlert(res.message);
                setLoading(false);
                return;
            }
            history.push({ role: 'user', content: msg });
            history.push({ role: 'assistant', content: reply });
            appendBubble('assistant', reply || '(Sin texto)');

            if (debugCheck.checked && res.debug && res.debug.length) {
                debugPanel.textContent = JSON.stringify(res.debug, null, 2);
                debugPanel.classList.remove('d-none');
            }
        }).fail(function (xhr) {
            var err = 'Error al contactar el agente.';
            try {
                var j = xhr.responseJSON;
                if (j && j.message) err = j.message;
                else if (j && j.detail && j.detail.message) err = j.detail.message;
                else if (typeof xhr.responseText === 'string' && xhr.responseText.length < 400) err = xhr.responseText;
            } catch (e) {}
            showAlert(err);
        }).always(function () {
            setLoading(false);
        });
    }

    sendBtn.addEventListener('click', sendMessage);

    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });

    resetBtn.addEventListener('click', function () {
        history = [];
        hideAlert();
        hideDebugPanel();
        logEl.innerHTML = '';
        var ph = document.createElement('div');
        ph.className = 'text-muted small text-center py-5';
        ph.id = 'ai-chat-placeholder';
        ph.textContent = 'Escribe un mensaje para consultar propiedades o resolver dudas del inventario.';
        logEl.appendChild(ph);
        placeholder = ph;
        inputEl.focus();
    });
})();
</script>
