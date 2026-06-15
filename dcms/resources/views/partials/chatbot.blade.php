<button type="button" class="chatbot-fab" onclick="toggleChat()" aria-label="Open dental chatbot">
    <i class="fas fa-comments"></i>
</button>

<div id="chat-window" class="chatbot-panel">
    <div class="chatbot-header">
        <div class="chatbot-title">
            <div class="chatbot-avatar">
                <i class="fas fa-tooth"></i>
            </div>
            <div>
                <div>PUP SmileGuide AI</div>
                <div class="chatbot-status">
                    <span class="chatbot-status-dot"></span>
                    <span>AI Online</span>
                </div>
            </div>
        </div>

        <button type="button" class="chatbot-close" onclick="toggleChat()" aria-label="Close chatbot">
            <i class="fas fa-times"></i>
        </button>
    </div>

    <div class="chatbot-messages" id="chat-messages">
        <div class="chat-empty-state">
            <div class="chat-empty-orbit">
                <i class="fas fa-tooth"></i>
                <span></span>
                <span></span>
                <span></span>
            </div>

            <h4>How can I help you today?</h4>
            <p>Ask me about appointments, dental records, odontogram, or document requests.</p>
        </div>
    </div>

    <div class="chatbot-quick-chips">
        <button type="button" class="chatbot-chip"
            onclick="sendQuickMessage('Where can I view my odontogram in the Dental Records page?')">
            <i class="fas fa-tooth"></i>
            <span>Odontogram</span>
        </button>

        <button type="button" class="chatbot-chip"
            onclick="sendQuickMessage('How do I book an appointment from the patient dashboard?')">
            <i class="fas fa-calendar-check"></i>
            <span>Book</span>
        </button>

        <button type="button" class="chatbot-chip"
            onclick="sendQuickMessage('Where can I request a dental clearance document?')">
            <i class="fas fa-file-medical"></i>
            <span>Documents</span>
        </button>
    </div>

    <div class="chatbot-footer">
        <div class="chatbot-input-wrap">
            <input type="text" id="user-input" placeholder="Type your question..." autocomplete="off">
            <button type="button" id="send-btn" class="chatbot-send" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script>
    window.authUserName = "{{ optional(auth()->user())->name ?? '' }}";
    window.chatbotBotName = 'PUP SmileGuide AI';
</script>

<script>
    const chatWindow = document.getElementById('chat-window');
    const input = document.getElementById('user-input');
    const msgDiv = document.getElementById('chat-messages');
    const sendBtn = document.getElementById('send-btn');
    const chatbotContext = window.chatbotContext || {};
    const isLoginPage = chatbotContext.page === 'login' || window.location.pathname === '/login';
    const botName = window.chatbotBotName || 'PUP SmileGuide AI';
    let introShown = false;

    function toggleChat(forceClose = false) {

        if (!forceClose) {
            closeAccessibilityWidget();
        }

        const isOpen = chatWindow.classList.contains('show');

        if (forceClose || isOpen) {
            chatWindow.classList.remove('show');
            chatWindow.classList.add('closing');

            setTimeout(() => {
                chatWindow.classList.remove('closing');
            }, 380);
        } else {
            chatWindow.classList.add('show');
        }

        const isNowOpen = chatWindow.classList.contains('show');
        const isMobile = window.matchMedia('(max-width: 640px)').matches;

        document.body.classList.toggle('chatbot-open-mobile', isNowOpen && isMobile);

        if (isNowOpen) {
            if (!introShown) {
                showIntroMessage();
                introShown = true;
            }

            setTimeout(() => input.focus(), 100);
        }
    }

    function showIntroMessage() {
        const introText = isLoginPage
            ? `Hi! I’m <strong>${botName}</strong>. This is the <strong>login page</strong>, so I can help you with <strong>signing in</strong>, <strong>SSO access</strong>, and what you can do after you log in.`
            : `Hi! I’m <strong>${botName}</strong>. I’m ready to help with <strong>appointments</strong>, <strong>dental records</strong>, <strong>schedules</strong>, and <strong>document requests</strong>.`;

        addMessage('ai', introText, { allowHtml: true });
    }

    function escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    let lastMessageType = null;

    function addMessage(type, text, options = {}) {
        const row = document.createElement('div');
        row.className = `chat-row ${type}`;

        if (lastMessageType === type) {
            row.classList.add('grouped');
        }

        const avatar = document.createElement('div');
        avatar.className = 'chat-message-avatar';
        avatar.innerHTML = type === 'user' ?
            '<i class="fas fa-user"></i>' :
            '<i class="fas fa-tooth"></i>';

        const bubble = document.createElement('div');
        bubble.className = 'chat-bubble';
        bubble.innerHTML = options.allowHtml ? text : escapeHTML(text);

        if (type === 'user' && options.status) {
            bubble.innerHTML += `<span class="chat-status-text">${options.status}</span>`;
        }

        row.appendChild(avatar);
        row.appendChild(bubble);
        const empty = msgDiv.querySelector('.chat-empty-state');
        if (empty) empty.remove();

        msgDiv.appendChild(row);

        lastMessageType = type;
        scrollChat();

        return bubble;
    }

    function showTyping() {
        const row = document.createElement('div');
        row.className = 'chat-row ai';
        row.id = 'typing-indicator';

        row.innerHTML = `
            <div class="chat-bubble">
                <span class="typing-bubble">
                    <span class="typing-text">Typing</span>
                    <span class="typing-dots">
                        <span></span><span></span><span></span>
                    </span>
                </span>
            </div>
        `;

        msgDiv.appendChild(row);
        scrollChat();
    }

    function removeTyping() {
        const typing = document.getElementById('typing-indicator');
        if (typing) typing.remove();
    }

    function scrollChat() {
        msgDiv.scrollTop = msgDiv.scrollHeight;
    }

    function setLoading(isLoading) {
        input.disabled = isLoading;
        sendBtn.disabled = isLoading;
        sendBtn.innerHTML = isLoading ?
            '<i class="fas fa-spinner fa-spin"></i>' :
            '<i class="fas fa-paper-plane"></i>';
    }

    async function typeText(element, text) {
        element.innerHTML = '';

        for (let i = 0; i < text.length; i++) {
            element.innerHTML += escapeHTML(text.charAt(i));
            scrollChat();
            await new Promise(resolve => setTimeout(resolve, 10));
        }
    }

    function sendQuickMessage(message) {
        if (sendBtn.disabled) return;

        input.value = message;
        sendMessage();
    }

    function cleanErrorMessage(data) {
        const message = data?.error || data?.body || '';

        if (
            data?.status === 503 ||
            message.toLowerCase().includes('high demand') ||
            message.toLowerCase().includes('unavailable')
        ) {
            return 'AI is busy. Please try again.';
        }

        if (message.toLowerCase().includes('api key')) {
            return 'There is an issue with the AI setup. Please check the API key.';
        }

        return 'AI assistant temporarily unavailable.';
    }

    function smartDelay() {
        return new Promise(resolve => {
            setTimeout(resolve, 500 + Math.random() * 700);
        });
    }

    function runSystemCommand(message) {
        const command = message.trim().toLowerCase();

        if (command === '/book') {
            window.location.href = '/book-appointment';
            return true;
        }

        if (command === '/records') {
            window.location.href = '/record';
            return true;
        }

        if (command === '/appointments') {
            window.location.href = '/patient/appointments';
            return true;
        }

        if (command === '/documents') {
            window.location.href = '/document-requests';
            return true;
        }

        return false;
    }

    function scrollToFeature(selector) {
        const target = document.querySelector(selector);

        if (!target) return;

        target.scrollIntoView({
            behavior: 'smooth',
            block: 'center'
        });
        target.classList.add('chat-highlight-target');

        setTimeout(() => {
            target.classList.remove('chat-highlight-target');
        }, 2600);
    }

    function triggerHaptic() {
        if (navigator.vibrate) {
            navigator.vibrate(18);
        }
    }

    async function sendMessage() {
        const message = input.value.trim();

        if (!message || sendBtn.disabled) return;

        addMessage('user', message, {
            status: 'Sent ✓'
        });

        input.value = '';
        setLoading(true);
        runSystemCommand(message);
        await smartDelay();
        showTyping();

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : (document.querySelector('input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '');

            const response = await fetch('/chat/send', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json',
                    'Accept': 'application/json',
                    'X-CSRF-TOKEN': csrfToken
                },
                body: JSON.stringify({
                    message,
                    context: window.location.pathname,
                    patient_id: window.authUserId
                })
            });

            let data = null;

            try {
                data = await response.json();
            } catch (e) {
                data = {
                    error: 'AI assistant temporarily unavailable.'
                };
            }

            removeTyping();

            if (!response.ok) {
                throw new Error(cleanErrorMessage(data));
            }

            let reply = data.reply || 'No response from AI.';

            if (window.authUserName && reply.toLowerCase().startsWith('hello')) {
                reply = reply.replace(/^hello(?:\s+there)?[!,.\s]*/i, `Hello ${window.authUserName}! `).replace(/\s+/g, ' ').trim();
            }

            addMessage('ai', reply);
            handleSmartActions(data.reply || '');

        } catch (error) {
            removeTyping();
            console.error(error);
            addMessage('ai', error.message || 'AI assistant is unavailable. Try again.');
        } finally {
            setLoading(false);
            input.focus();
        }
    }

    function handleSmartActions(reply) {
        if (isLoginPage) {
            return;
        }

        const text = reply.toLowerCase();

        if (text.includes('appointment') || text.includes('book')) {
            addActionButton('Go to Appointments', '/patient/appointments');
            scrollToFeature('#appointments, .appointments, [data-section="appointments"]');
        }

        if (text.includes('records') || text.includes('dental record')) {
            addActionButton('Go to Dental Records', '/record');
            scrollToFeature('#records, .records, [data-section="records"]');
        }

        if (text.includes('schedule') || text.includes('available')) {
            addActionButton('Check Available Dates', '/book-appointment');
            scrollToFeature('#calendar, .calendar, [data-section="calendar"]');
        }

        if (text.includes('document')) {
            addActionButton('Go to Document Requests', '/document-requests');
            scrollToFeature('#documents, .documents, [data-section="documents"]');
        }
    }

    let chatStartY = 0;
    let chatCurrentY = 0;
    let isDraggingChat = false;

    chatWindow.addEventListener('touchstart', function(e) {
        if (!window.matchMedia('(max-width: 640px)').matches) return;

        chatStartY = e.touches[0].clientY;
        chatCurrentY = chatStartY;
        isDraggingChat = true;
        chatWindow.style.transition = 'none';
    }, {
        passive: true
    });

    chatWindow.addEventListener('touchmove', function(e) {
        if (!isDraggingChat) return;

        chatCurrentY = e.touches[0].clientY;
        const diff = Math.max(0, chatCurrentY - chatStartY);

        chatWindow.style.transform = `translateY(${diff}px)`;
    }, {
        passive: true
    });

    chatWindow.addEventListener('touchend', function() {
        if (!isDraggingChat) return;

        const diff = Math.max(0, chatCurrentY - chatStartY);

        chatWindow.style.transition = '';
        chatWindow.style.transform = '';

        if (diff > 90) {
            toggleChat(true);
        }

        isDraggingChat = false;
    });

    input.addEventListener('keydown', function(e) {
        if (e.key === 'Enter') {
            e.preventDefault();
            sendMessage();
        }
    });

    const pageChips = {
        '/login': [
            ['Log in', 'How do I log in to the clinic system?'],
            ['SSO', 'How do I use the SSO login option?'],
            ['Help', 'What can I do on this login page?']
        ],
        '/homepage': [
            ['Book', 'How do I book an appointment from the patient dashboard?'],
            ['Schedule', 'Where can I check available appointment dates and clinic schedule?'],
            ['Records', 'How can I open my dental records from the dashboard?']
        ],
        '/patient/appointments': [
            ['Available', 'How can I check available dates for booking an appointment?'],
            ['Reschedule', 'How can I reschedule my existing appointment?'],
            ['Cancel', 'How can I cancel my appointment in the system?']
        ],
        '/record': [
            ['Records', 'What information can I see on the Dental Records page?'],
            ['Odontogram', 'Where can I view my odontogram in the Dental Records page?'],
            ['Treatment', 'Where can I see my treatment history and diagnosis?']
        ],
        '/document-requests': [
            ['Clearance', 'How can I request a dental clearance document?'],
            ['Health Record', 'How can I request my dental health record?'],
            ['Status', 'Where can I check the status of my document request?']
        ]
    };

    function renderDynamicChips() {
        const chipWrap = document.querySelector('.chatbot-quick-chips');
        if (!chipWrap) return;

        const chips = pageChips[window.location.pathname] || pageChips[isLoginPage ? '/login' : '/homepage'];

        chipWrap.innerHTML = chips.map(([label, message]) => `
        <button type="button" class="chatbot-chip" onclick="sendQuickMessage('${message}')">
            ${label}
        </button>
    `).join('');
    }

    renderDynamicChips();

    function addActionButton(label, url) {
        const row = document.createElement('div');
        row.className = 'chat-row ai action-row';

        const avatar = document.createElement('div');
        avatar.className = 'chat-message-avatar';
        avatar.innerHTML = '<i class="fas fa-tooth"></i>';

        const bubble = document.createElement('div');
        bubble.className = 'chat-action-bubble';

        bubble.innerHTML = `
        <button type="button" class="chat-action-btn" onclick="window.location.href='${url}'">
            ${label}
        </button>
    `;

        row.appendChild(avatar);
        row.appendChild(bubble);
        msgDiv.appendChild(row);
        scrollChat();
    }

    document.addEventListener('click', function(e) {
        const target = e.target.closest('.chatbot-chip, .chatbot-send, .chat-action-btn');
        if (!target) return;

        triggerHaptic();

        const ripple = document.createElement('span');
        ripple.className = 'ripple';

        const rect = target.getBoundingClientRect();
        ripple.style.width = ripple.style.height = Math.max(rect.width, rect.height) + 'px';
        ripple.style.left = (e.clientX - rect.left - rect.width / 2) + 'px';
        ripple.style.top = (e.clientY - rect.top - rect.height / 2) + 'px';

        target.appendChild(ripple);

        setTimeout(() => ripple.remove(), 500);
    });

    function closeAccessibilityWidget() {
        const widget = document.querySelector('.asw-menu'); // actual panel

        if (widget && widget.classList.contains('active')) {
            const btn = document.querySelector('.asw-menu-btn');
            if (btn) btn.click();
        }
    }

    document.addEventListener('click', function(e) {
        const isAccessibilityBtn =
            e.target.closest('.asw-menu-btn') ||
            e.target.closest('[aria-label="Accessibility"]');

        if (isAccessibilityBtn) {
            chatWindow.classList.remove('show');
        }
    });
</script>
