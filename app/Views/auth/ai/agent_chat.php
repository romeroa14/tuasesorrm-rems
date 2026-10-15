<div id="content-wrapper" class="d-flex flex-column">
    <div id="content">
        <div class="container-fluid" id="container-wrapper">
            <div class="d-sm-flex align-items-center justify-content-between mb-3">
                <div>
                    <h1 class="h3 mb-1 text-gray-800">Agente IA</h1>
                    <p class="mb-0 text-muted small">
                        Catálogo, captadores, visitas, búsquedas, CRM, marketing y Wasi. Finanzas solo si tu usuario tiene acceso.
                    </p>
                </div>
                <a href="<?= esc(site_url('/app/crm/inbox')) ?>" class="btn btn-sm btn-outline-primary">
                    <i class="fas fa-inbox"></i> CRM Inbox
                </a>
            </div>

            <div class="ai-shell card shadow">
                <aside class="ai-threads">
                    <div class="ai-threads-head">
                        <span>Conversaciones</span>
                        <button type="button" class="btn btn-sm btn-primary" id="ai-chat-new">Nueva</button>
                    </div>
                    <div id="ai-thread-list" class="ai-thread-list"></div>
                </aside>
                <section class="ai-conversation">
                    <div class="ai-conversation-head">
                        <div>
                            <div class="ai-conversation-title" id="ai-chat-title">Nueva conversación</div>
                            <div class="text-muted small">Los hilos se guardan en tu usuario.</div>
                        </div>
                        <label class="mb-0 small text-muted">
                            <input type="checkbox" id="ai-chat-debug"> Depuración
                        </label>
                    </div>
                    <div id="ai-chat-log" class="ai-chat-log"></div>
                    <div class="ai-composer">
                        <div id="ai-chat-alert" class="alert alert-danger d-none small mb-2" role="alert"></div>
                        <div id="ai-chat-debug-panel" class="alert alert-secondary d-none small mb-2" style="white-space: pre-wrap; max-height: 140px; overflow-y: auto;"></div>
                        <div class="ai-composer-row">
                            <textarea id="ai-chat-input" rows="1" placeholder="Escribe un mensaje"></textarea>
                            <button class="ai-send" type="button" id="ai-chat-send" title="Enviar">
                                <i class="fas fa-paper-plane"></i>
                            </button>
                        </div>
                        <small class="text-muted">Enter envía · Shift+Enter nueva línea</small>
                    </div>
                </section>
            </div>
        </div>
    </div>
</div>

<style>
    .ai-shell { display: flex; flex-direction: row; min-height: calc(100vh - 11.5rem); overflow: hidden; border: 0; border-radius: 16px; }
    .ai-threads { width: 280px; flex: 0 0 280px; background: #fff; border-right: 1px solid #e7ebf3; display: flex; flex-direction: column; }
    .ai-threads-head { display: flex; align-items: center; justify-content: space-between; padding: 14px 14px 10px; font-weight: 700; color: #1f2a44; }
    .ai-thread-list { overflow-y: auto; flex: 1; }
    .ai-thread { width: 100%; text-align: left; border: 0; background: transparent; padding: 12px 14px; border-bottom: 1px solid #f2f4f8; cursor: pointer; }
    .ai-thread.active { background: #eef3ff; }
    .ai-thread-title { display: block; font-weight: 600; color: #24324a; white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
    .ai-thread-meta { display: flex; justify-content: space-between; gap: 8px; color: #8a94a6; font-size: 12px; margin-top: 3px; }
    .ai-thread-delete { border: 0; background: transparent; color: #b0b8c6; padding: 0 0 0 8px; }
    .ai-thread.active .ai-thread-delete, .ai-thread:hover .ai-thread-delete { color: #6c757d; }
    .ai-conversation { flex: 1; min-width: 0; display: flex; flex-direction: column; background: #f6f8fc; }
    .ai-conversation-head { display: flex; justify-content: space-between; gap: 12px; align-items: center; padding: 14px 18px; background: #fff; border-bottom: 1px solid #e7ebf3; }
    .ai-conversation-title { font-weight: 700; color: #1f2a44; }
    .ai-chat-log { flex: 1; overflow-y: auto; padding: 18px; }
    .ai-empty { color: #8a94a6; text-align: center; margin-top: 18%; }
    .ai-row { display: flex; margin-bottom: 14px; }
    .ai-row.user { justify-content: flex-end; }
    .ai-bubble { max-width: min(78%, 640px); padding: 10px 14px; word-break: break-word; }
    .ai-row.user .ai-bubble { background: #2e59d9; color: #fff; border-radius: 16px 16px 4px 16px; white-space: pre-wrap; }
    .ai-row.assistant .ai-bubble { background: #fff; color: #24324a; border-radius: 16px 16px 16px 4px; box-shadow: 0 1px 2px rgba(31, 42, 68, .06); }
    .ai-chat-time { font-size: 11px; margin-top: 6px; opacity: .7; }
    .ai-chat-assistant p { margin: 0 0 .35rem; }
    .ai-chat-gap { height: .45rem; }
    .ai-chat-photo { display: block; width: 100%; max-width: 320px; height: 180px; object-fit: cover; border-radius: 12px; margin: .35rem 0 .65rem; background: #eef1f6; }
    .ai-chat-gallery { display: flex; gap: .5rem; overflow-x: auto; margin: .35rem 0 .75rem; }
    .ai-chat-gallery .ai-chat-photo { width: 180px; max-width: none; flex: 0 0 auto; margin: 0; }
    .ai-composer { background: #fff; border-top: 1px solid #e7ebf3; padding: 12px 16px 10px; }
    .ai-composer-row { display: flex; gap: 10px; align-items: flex-end; }
    #ai-chat-input { flex: 1; resize: none; border: 1px solid #e1e6ef; border-radius: 14px; padding: 10px 14px; max-height: 140px; outline: none; }
    #ai-chat-input:focus { border-color: #2e59d9; }
    .ai-send { width: 42px; height: 42px; border: 0; border-radius: 50%; background: #2e59d9; color: #fff; }
    .ai-send:disabled { opacity: .6; }
    @media (max-width: 767px) {
        .ai-shell { flex-direction: column; min-height: calc(100vh - 9rem); }
        .ai-threads { width: 100%; flex-basis: auto; max-height: 190px; border-right: 0; border-bottom: 1px solid #e7ebf3; }
    }
</style>

<script>
(function () {
    var AI_CHAT_URL = <?= json_encode(site_url('app/ai/chat')) ?>;
    var AI_THREADS_URL = <?= json_encode(site_url('app/ai/threads')) ?>;
    var logEl = document.getElementById('ai-chat-log');
    var listEl = document.getElementById('ai-thread-list');
    var inputEl = document.getElementById('ai-chat-input');
    var sendBtn = document.getElementById('ai-chat-send');
    var newBtn = document.getElementById('ai-chat-new');
    var alertEl = document.getElementById('ai-chat-alert');
    var debugCheck = document.getElementById('ai-chat-debug');
    var debugPanel = document.getElementById('ai-chat-debug-panel');
    var titleEl = document.getElementById('ai-chat-title');
    var threadId = null;
    var requestSeq = 0;

    function escapeHtml(value) {
        return String(value)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }
    
    function safePropertyPhoto(url, pageHost) {
        try {
            var parsed = new URL(url, 'https://' + (pageHost || 'rems.asesoresrm.com.ve'));
            var allowed = {
                'rems.asesoresrm.com.ve': true,
                'rems.tuasesorrm.com.ve': true
            };
            if (pageHost) allowed[pageHost] = true;
            if (parsed.protocol !== 'https:') return '';
            if (!allowed[parsed.hostname]) return '';
            if (parsed.pathname.indexOf('/properties/') !== 0) return '';
            if (!/\.(jpe?g|png|webp|gif)$/i.test(parsed.pathname)) return '';
            return parsed.href;
        } catch (e) {
            return '';
        }
    }
    
    function photoHtml(url, alt) {
        var safe = escapeHtml(url);
        var label = escapeHtml(alt || 'Foto del inmueble');
        return '<a class="ai-chat-photo-link" href="' + safe + '" target="_blank" rel="noopener noreferrer">'
            + '<img class="ai-chat-photo" src="' + safe + '" alt="' + label + '" loading="lazy">'
            + '</a>';
    }
    
    function galleryHtml(items) {
        if (!items.length) return '';
        if (items.length === 1) return photoHtml(items[0].url, items[0].alt);
        return '<div class="ai-chat-gallery">' + items.map(function (item) {
            return photoHtml(item.url, item.alt);
        }).join('') + '</div>';
    }
    
    function renderAssistant(text, pageHost) {
        var images = [];
        var src = String(text || '').replace(/```[a-z]*\n?/gi, '').replace(/```/g, '');
        src = src.replace(/!\[([^\]]*)\]\(([^)\s]+)(?:\s+"[^"]*")?\)/g, function (_, alt, url) {
            var safe = safePropertyPhoto(url, pageHost);
            if (!safe) return '';
            images.push({ alt: alt, url: safe });
            return '\u0000IMG' + (images.length - 1) + '\u0000';
        });
        src = src.replace(/https?:\/\/[^\s)<]+?\.(?:jpe?g|png|webp|gif)/gi, function (url) {
            var safe = safePropertyPhoto(url, pageHost);
            if (!safe) return url;
            images.push({ alt: 'Foto del inmueble', url: safe });
            return '\u0000IMG' + (images.length - 1) + '\u0000';
        });
    
        var html = escapeHtml(src);
        html = html.replace(/\*\*([^*]+)\*\*/g, '<strong>$1</strong>');
        html = html.replace(/(^|[\s(])\*([^*\n]+)\*(?=[\s).,!]|$)/g, '$1<em>$2</em>');
    
        var lines = html.split('\n');
        var parts = [];
        var pending = [];
    
        function flushPhotos() {
            if (!pending.length) return;
            parts.push(galleryHtml(pending));
            pending = [];
        }
    
        lines.forEach(function (line) {
            var trimmed = line.trim();
            if (!trimmed) {
                flushPhotos();
                parts.push('<div class="ai-chat-gap"></div>');
                return;
            }
            var only = trimmed.match(/^\u0000IMG(\d+)\u0000$/);
            if (only) {
                pending.push(images[Number(only[1])]);
                return;
            }
            flushPhotos();
            trimmed = trimmed.replace(/\u0000IMG(\d+)\u0000/g, function (_, index) {
                pending.push(images[Number(index)]);
                return '';
            });
            if (pending.length && !trimmed) {
                flushPhotos();
                return;
            }
            var photos = '';
            if (pending.length) {
                photos = galleryHtml(pending);
                pending = [];
            }
            parts.push('<p class="mb-1">' + trimmed + '</p>' + photos);
        });
        flushPhotos();
        return parts.join('');
    }

    function hideAlert() {
        alertEl.classList.add('d-none');
        alertEl.textContent = '';
    }
    function showAlert(msg) {
        alertEl.textContent = msg;
        alertEl.classList.remove('d-none');
    }
    function hideDebug() {
        debugPanel.classList.add('d-none');
        debugPanel.textContent = '';
    }
    function clock(value) {
        if (!value) return '';
        var date = new Date(String(value).replace(' ', 'T'));
        if (isNaN(date.getTime())) return '';
        return date.toLocaleString('es-VE', { day: '2-digit', month: 'short', hour: '2-digit', minute: '2-digit' });
    }
    function showEmpty() {
        logEl.innerHTML = '';
        var empty = document.createElement('div');
        empty.className = 'ai-empty';
        empty.id = 'ai-chat-placeholder';
        empty.textContent = 'Escribe para empezar. Cada conversación queda guardada en la lista.';
        logEl.appendChild(empty);
    }
    function appendBubble(role, text, when) {
        var placeholder = document.getElementById('ai-chat-placeholder');
        if (placeholder) placeholder.remove();
        var row = document.createElement('div');
        row.className = 'ai-row ' + (role === 'user' ? 'user' : 'assistant');
        var bubble = document.createElement('div');
        bubble.className = 'ai-bubble' + (role === 'user' ? '' : ' ai-chat-assistant');
        if (role === 'user') bubble.textContent = text;
        else bubble.innerHTML = renderAssistant(text, window.location.hostname);
        var time = document.createElement('div');
        time.className = 'ai-chat-time';
        time.textContent = clock(when) || '';
        if (time.textContent) bubble.appendChild(time);
        row.appendChild(bubble);
        logEl.appendChild(row);
        logEl.scrollTop = logEl.scrollHeight;
    }
    function renderThreads(threads) {
        listEl.innerHTML = '';
        if (!threads.length) {
            var empty = document.createElement('div');
            empty.className = 'text-muted small px-3 py-3';
            empty.textContent = 'Todavía no hay conversaciones.';
            listEl.appendChild(empty);
            return;
        }
        threads.forEach(function (thread) {
            var item = document.createElement('div');
            item.className = 'ai-thread' + (Number(thread.id) === Number(threadId) ? ' active' : '');
            var title = document.createElement('span');
            title.className = 'ai-thread-title';
            title.textContent = thread.title || 'Conversación';
            var meta = document.createElement('div');
            meta.className = 'ai-thread-meta';
            var when = document.createElement('span');
            when.textContent = clock(thread.updated_at);
            var remove = document.createElement('button');
            remove.type = 'button';
            remove.className = 'ai-thread-delete';
            remove.textContent = 'Borrar';
            remove.addEventListener('click', function (event) {
                event.stopPropagation();
                removeThread(thread.id);
            });
            meta.appendChild(when);
            meta.appendChild(remove);
            item.appendChild(title);
            item.appendChild(meta);
            item.addEventListener('click', function () { openThread(thread.id); });
            listEl.appendChild(item);
        });
    }
    function loadThreads(selectId) {
        return $.getJSON(AI_THREADS_URL).done(function (res) {
            var threads = res && res.threads ? res.threads : [];
            if (selectId) threadId = selectId;
            renderThreads(threads);
        });
    }
    function openThread(id) {
        requestSeq += 1;
        threadId = id;
        hideAlert();
        hideDebug();
        logEl.innerHTML = '';
        $.getJSON(AI_THREADS_URL + '/' + id).done(function (res) {
            if (Number(threadId) !== Number(id)) return;
            titleEl.textContent = (res.thread && res.thread.title) || 'Conversación';
            var messages = res.messages || [];
            if (!messages.length) showEmpty();
            messages.forEach(function (message) {
                appendBubble(message.role, message.content, message.created_at);
            });
            loadThreads();
        }).fail(function () {
            showAlert('No se pudo abrir esa conversación.');
        });
    }
    function removeThread(id) {
        if (!window.confirm('¿Borrar esta conversación?')) return;
        $.ajax({ url: AI_THREADS_URL + '/' + id, method: 'DELETE' }).done(function () {
            if (Number(threadId) === Number(id)) startNew();
            loadThreads();
        }).fail(function () {
            showAlert('No se pudo borrar la conversación.');
        });
    }
    function startNew() {
        requestSeq += 1;
        threadId = null;
        titleEl.textContent = 'Nueva conversación';
        hideAlert();
        hideDebug();
        showEmpty();
        inputEl.focus();
        loadThreads();
    }
    function setLoading(on) {
        sendBtn.disabled = on;
        inputEl.disabled = on;
    }
    function sendMessage() {
        var msg = (inputEl.value || '').trim();
        if (!msg) return;
        hideAlert();
        hideDebug();
        var seq = ++requestSeq;
        var sendingThread = threadId;
        appendBubble('user', msg, new Date().toISOString());
        inputEl.value = '';
        setLoading(true);
        $.ajax({
            url: AI_CHAT_URL,
            method: 'POST',
            contentType: 'application/json; charset=UTF-8',
            dataType: 'json',
            data: JSON.stringify({
                message: msg,
                thread_id: sendingThread || 0,
                debug: debugCheck.checked
            })
        }).done(function (res) {
            if (seq !== requestSeq) return;
            var reply = res && typeof res.reply === 'string' ? res.reply : '';
            if (!reply) {
                showAlert((res && res.message) || 'El agente no respondió.');
                return;
            }
            threadId = res.thread_id || sendingThread;
            if (res.title) titleEl.textContent = res.title;
            appendBubble('assistant', reply, new Date().toISOString());
            if (debugCheck.checked && res.debug && res.debug.length) {
                debugPanel.textContent = JSON.stringify(res.debug, null, 2);
                debugPanel.classList.remove('d-none');
            }
            loadThreads();
        }).fail(function (xhr) {
            if (seq !== requestSeq) return;
            var err = 'Error al contactar el agente.';
            try {
                var body = xhr.responseJSON;
                if (body && body.message) err = body.message;
            } catch (e) {}
            showAlert(err);
        }).always(function () {
            if (seq === requestSeq) setLoading(false);
        });
    }

    sendBtn.addEventListener('click', sendMessage);
    newBtn.addEventListener('click', startNew);
    inputEl.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    inputEl.addEventListener('input', function () {
        inputEl.style.height = 'auto';
        inputEl.style.height = Math.min(inputEl.scrollHeight, 140) + 'px';
    });
    showEmpty();
    loadThreads();
})();
</script>
