document.addEventListener('DOMContentLoaded', () => {
    const chatContainer = document.getElementById('chatContainer');
    const chatForm = document.getElementById('chatForm');
    const messageInput = document.getElementById('messageInput');
    const sendBtn = document.getElementById('sendBtn');
    const sendIcon = document.getElementById('sendIcon');
    const statusText = document.getElementById('statusText');

    let isSending = false;
    let typingRow = null;

    // Configure Markdown parser for AI assistant responses (safe external links)
    if (window.marked) {
        const renderer = new marked.Renderer();
        renderer.link = function(href, title, text) {
            const safeTitle = title ? ` title="${title}"` : '';
            return `<a href="${href}"${safeTitle} target="_blank" rel="noopener noreferrer">${text}</a>`;
        };
        marked.setOptions({ renderer, breaks: true, gfm: true });
    }

    // Initialize the chat session and retrieve the opening Socratic question
    initChat();

    // Auto-resize textarea height dynamically based on line count
    messageInput.addEventListener('input', function() {
        this.style.height = 'auto';
        this.style.height = (this.scrollHeight < 120 ? this.scrollHeight : 120) + 'px';
        updateSendIcon();
    });

    // Send on Enter key (without Shift); isComposing prevents premature submission during IME composition
    messageInput.addEventListener('keydown', function(e) {
        if (e.key === 'Enter' && !e.shiftKey && !e.isComposing) {
            e.preventDefault();
            sendMessage();
        }
    });

    // Handle form submit event
    chatForm.addEventListener('submit', (e) => {
        e.preventDefault();
        sendMessage();
    });

    // Main send message logic triggered by both Enter key and send button
    async function sendMessage() {
        if (isSending) return;

        const text = messageInput.value.trim();
        if (!text) return;

        isSending = true;

        appendMessage('user', text);
        messageInput.value = '';
        messageInput.style.height = 'auto';
        updateSendIcon();

        setLoading(true);

        try {
            const response = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'send', message: text })
            });
            const data = await response.json();

            if (data.success) {
                appendMessage('assistant', data.reply);
            } else {
                appendMessage('assistant', '⚠️ خطا: ' + (data.error || 'پاسخی دریافت نشد.'));
            }
        } catch (err) {
            appendMessage('assistant', '⚠️ خطای شبکه: ارتباط با سرور برقرار نشد.');
        } finally {
            setLoading(false);
            isSending = false;
        }
    }

    // Fetch initial mentor greeting or restore previous conversation history
    async function initChat() {
        setLoading(true);
        try {
            const res = await fetch('api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'init' })
            });
            const data = await res.json();
            if (data.success) {
                if (data.reply) {
                    appendMessage('assistant', data.reply);
                } else if (data.messages) {
                    data.messages.forEach(msg => appendMessage(msg.role, msg.content));
                }
            } else {
                appendMessage('assistant', '⚠️ خطا: ' + (data.error || 'خطا در بارگذاری اولیه.'));
            }
        } catch (err) {
            appendMessage('assistant', '⚠️ خطا در بارگذاری اولیه کارگاه (ارتباط با سرور برقرار نشد).');
        } finally {
            setLoading(false);
        }
    }

    // Append a new message bubble (user or assistant) to the chat container
    function appendMessage(role, text) {
        const isUser = role === 'user';

        const row = document.createElement('div');
        row.className = `message-row ${isUser ? 'from-user' : 'from-assistant'}`;

        const bubble = document.createElement('div');
        bubble.className = `message-bubble ${isUser ? 'user-bubble' : 'bot-bubble'}`;

        const textSpan = document.createElement('span');
        textSpan.className = 'message-text';
        textSpan.innerHTML = renderContent(text, isUser);

        const metaSpan = document.createElement('span');
        metaSpan.className = 'message-meta';
        const time = new Date().toLocaleTimeString('fa-IR', { hour: '2-digit', minute: '2-digit' });
        metaSpan.innerHTML = isUser
            ? `${time} <i class="bi bi-check2-all check-icon"></i>`
            : time;

        bubble.appendChild(textSpan);
        bubble.appendChild(metaSpan);
        row.appendChild(bubble);
        chatContainer.appendChild(row);
        scrollToBottom();
    }

    // Display WhatsApp-style animated three-dot typing indicator
    function showTypingBubble() {
        if (typingRow) return;
        typingRow = document.createElement('div');
        typingRow.className = 'typing-row';
        typingRow.innerHTML = `
            <div class="typing-bubble">
                <span class="dot"></span><span class="dot"></span><span class="dot"></span>
            </div>`;
        chatContainer.appendChild(typingRow);
        scrollToBottom();
    }

    // Remove typing indicator bubble from chat
    function hideTypingBubble() {
        if (typingRow) {
            typingRow.remove();
            typingRow = null;
        }
    }

    // Update UI loading state (status text, button disability, and typing bubble)
    function setLoading(isLoading) {
        sendBtn.disabled = isLoading;
        if (isLoading) {
            statusText.textContent = 'در حال تایپ...';
            statusText.classList.add('typing');
            showTypingBubble();
        } else {
            statusText.textContent = 'آنلاین';
            statusText.classList.remove('typing');
            hideTypingBubble();
        }
        scrollToBottom();
    }

    // Toggle mic / send icon based on whether the input field contains text (WhatsApp UI behavior)
    function updateSendIcon() {
        const hasText = messageInput.value.trim().length > 0;
        sendIcon.className = hasText ? 'bi bi-send-fill' : 'bi bi-mic-fill';
    }

    // Parse Markdown into sanitized HTML for assistant replies; preserve plain text for user messages
    function renderContent(text, isUser) {
        if (isUser || !window.marked || !window.DOMPurify) {
            return escapeHtml(text).replace(/\n/g, '<br>');
        }
        const rawHtml = marked.parse(text);
        return DOMPurify.sanitize(rawHtml, { ADD_ATTR: ['target', 'rel'] });
    }

    // Escape special HTML characters to prevent XSS injection attacks
    function escapeHtml(str) {
        return str
            .replace(/&/g, '&amp;')
            .replace(/</g, '&lt;')
            .replace(/>/g, '&gt;')
            .replace(/"/g, '&quot;')
            .replace(/'/g, '&#39;');
    }

    // Scroll chat container smoothly to the newest message
    function scrollToBottom() {
        chatContainer.scrollTop = chatContainer.scrollHeight;
    }

    // Reset conversation history and restart the workshop
    window.resetChat = async function() {
        if (!confirm('آیا مایلید کارگاه را از ابتدا شروع کنید؟')) return;
        chatContainer.innerHTML = '';
        setLoading(true);
        await fetch('api.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ action: 'reset' })
        });
        initChat();
    };
});