// ============================================================
// G&G Support Portal — ai_chat.js
// Chat widget logic: open/close, send, render, history.
// Loaded in footer.php only for logged-in users.
// ============================================================
'use strict';

(function () {

    // ── DOM refs ────────────────────────────────────────────────
    const fab      = document.getElementById('ai-chat-fab');
    const widget   = document.getElementById('ai-chat-widget');
    const closeBtn = document.getElementById('ai-chat-close');
    const clearBtn = document.getElementById('ai-chat-clear');
    const msgBox   = document.getElementById('ai-chat-messages');
    const input    = document.getElementById('ai-chat-input');
    const sendBtn  = document.getElementById('ai-chat-send');
    const typing   = document.getElementById('ai-typing');
    const csrfToken = document.getElementById('ai-csrf-token')?.value ?? '';

    if (!fab || !widget) return;   // safety — not rendered if user not logged in

    // ── Conversation history (kept in memory for multi-turn RAG) ─
    let history = [];              // [{role:'user'|'assistant', content:'...'}]
    let busy    = false;

    // ── Open / close ─────────────────────────────────────────────
    fab.addEventListener('click', function () {
        const isOpen = !widget.classList.contains('ai-chat-hidden');
        if (isOpen) {
            widget.classList.add('ai-chat-hidden');
        } else {
            widget.classList.remove('ai-chat-hidden');
            input.focus();
            scrollToBottom();
        }
    });

    closeBtn.addEventListener('click', function () {
        widget.classList.add('ai-chat-hidden');
    });

    // ── Clear conversation ────────────────────────────────────────
    clearBtn.addEventListener('click', function () {
        history = [];
        // Remove all messages except the first welcome bubble
        const bubbles = msgBox.querySelectorAll('.ai-msg');
        Array.from(bubbles).slice(1).forEach(el => el.remove());
    });

    // ── Auto-grow textarea ────────────────────────────────────────
    input.addEventListener('input', function () {
        this.style.height = 'auto';
        this.style.height = Math.min(this.scrollHeight, 110) + 'px';
    });

    // ── Send on Enter (Shift+Enter = new line) ────────────────────
    input.addEventListener('keydown', function (e) {
        if (e.key === 'Enter' && !e.shiftKey) {
            e.preventDefault();
            sendMessage();
        }
    });
    sendBtn.addEventListener('click', sendMessage);

    // ── Core send function ────────────────────────────────────────
    function sendMessage() {
        if (busy) return;
        const question = input.value.trim();
        if (!question) return;

        appendMessage('user', question);
        history.push({ role: 'user', content: question });

        input.value = '';
        input.style.height = 'auto';
        setTyping(true);
        busy = true;

        const body = new URLSearchParams({
            action:     'chat',
            question:   question,
            history:    JSON.stringify(history.slice(-6)),
            csrf_token: csrfToken,
        });

        fetch(BASE_URL + '/includes/ai_chat.php', {
            method:      'POST',
            credentials: 'same-origin',
            headers:     { 'Content-Type': 'application/x-www-form-urlencoded' },
            body:        body.toString(),
        })
        .then(function (resp) {
            if (!resp.ok) throw new Error('HTTP ' + resp.status);
            return resp.json();
        })
        .then(function (data) {
            setTyping(false);
            busy = false;

            if (data.success) {
                appendMessage('bot', data.answer, data.sources ?? []);
                history.push({ role: 'assistant', content: data.answer });
            } else {
                appendError(data.message ?? 'Something went wrong. Please try again.');
            }
        })
        .catch(function (err) {
            setTyping(false);
            busy = false;
            appendError('Could not reach the AI service. Please try again shortly.');
            console.error('AI chat error:', err);
        });
    }

    // ── Render helpers ────────────────────────────────────────────

    function appendMessage(role, text, sources) {
        const wrap = document.createElement('div');
        wrap.className = 'ai-msg ai-msg-' + (role === 'user' ? 'user' : 'bot');

        const bubble = document.createElement('div');
        bubble.className = 'ai-msg-bubble';
        bubble.innerHTML = formatText(text);
        wrap.appendChild(bubble);

        // Source pills — show which KB solutions were used
        if (sources && sources.length > 0) {
            const src = document.createElement('div');
            src.className = 'ai-sources';
            src.innerHTML = '📎 Sources: ' + sources.map(function (s) {
                return '<span title="Solution #' + esc(String(s.id)) + '">' + esc(s.title || 'Solution ' + s.id) + '</span>';
            }).join('');
            wrap.appendChild(src);
        }

        msgBox.appendChild(wrap);
        scrollToBottom();
    }

    function appendError(msg) {
        const wrap = document.createElement('div');
        wrap.className = 'ai-msg ai-msg-bot ai-msg-error';
        const bubble = document.createElement('div');
        bubble.className = 'ai-msg-bubble';
        bubble.textContent = '⚠️ ' + msg;
        wrap.appendChild(bubble);
        msgBox.appendChild(wrap);
        scrollToBottom();
    }

    function setTyping(show) {
        typing.classList.toggle('ai-chat-hidden', !show);
        sendBtn.disabled = show;
        scrollToBottom();
    }

    function scrollToBottom() {
        msgBox.scrollTop = msgBox.scrollHeight;
    }

    // ── Basic markdown-lite formatter ────────────────────────────
    // Converts **bold**, `code`, and newlines to HTML.
    function formatText(raw) {
        let t = esc(raw);
        t = t.replace(/\*\*(.*?)\*\*/g, '<strong>$1</strong>');
        t = t.replace(/`([^`]+)`/g,     '<code style="background:var(--border);padding:1px 4px;border-radius:3px;font-family:var(--font-mono);font-size:.82em">$1</code>');
        t = t.replace(/\n/g,            '<br>');
        return t;
    }

    function esc(str) {
        return String(str)
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;');
    }

})();
