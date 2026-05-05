<style>
    .chatbot-fab {
        position: fixed;
        right: var(--float-right-final, 22px);
        bottom: var(--chatbot-bottom-final);
        width: var(--fab-final-size, 52px);
        height: var(--fab-final-size, 52px);
        z-index: 99996;
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8B0000, #c1121f);
        color: white;
        box-shadow: 0 18px 35px rgba(139, 0, 0, .35);
        display: grid;
        place-items: center;
        transition: .25s ease;
    }

    .chatbot-fab:hover {
        transform: translateY(-3px) scale(1.04);
    }

    .chatbot-panel {
        position: fixed;
        right: 22px;
        bottom: calc(var(--chatbot-bottom-final, 92px) + 60px);
        width: 380px;
        height: 520px;
        max-height: calc(100vh - 140px);
        background: rgba(255, 255, 255, .96);
        backdrop-filter: blur(18px);
        border: 1px solid rgba(139, 0, 0, .16);
        border-radius: 18px;
        box-shadow: 0 25px 70px rgba(15, 23, 42, .24);
        z-index: 99994;
        overflow: hidden;
        display: flex;
        opacity: 0;
        transform: translateY(18px) scale(.96);
        pointer-events: none;
        will-change: transform, opacity;
        transition:
            opacity .28s ease,
            transform .28s cubic-bezier(.22, 1, .36, 1);
        flex-direction: column;
    }

    .chatbot-panel.show {
        opacity: 1;
        transform: translateY(0) scale(1);
        pointer-events: auto;
    }

    .chatbot-panel.closing {
        opacity: 0;
        transform: translateY(20px) scale(.96);
    }

    @keyframes chatPop {
        from {
            opacity: 0;
            transform: translateY(18px) scale(.96);
        }

        to {
            opacity: 1;
            transform: translateY(0) scale(1);
        }
    }

    .chatbot-header {
        padding: 16px;
        background: linear-gradient(135deg, #7f0000, #b91c1c);
        color: white;
        display: flex;
        align-items: center;
        justify-content: space-between;
    }

    .chatbot-title {
        display: flex;
        align-items: center;
        gap: 11px;
        font-weight: 800;
        font-size: 15px;
    }

    .chatbot-avatar {
        width: 42px;
        height: 42px;
        border-radius: 16px;
        background: rgba(255, 255, 255, .18);
        display: grid;
        place-items: center;
    }

    .chatbot-status {
        margin-top: 4px;
        display: inline-flex;
        align-items: center;
        gap: 6px;
        font-size: 12px;
        font-weight: 700;
        color: rgba(255, 255, 255, .92);
    }

    .chatbot-status-dot {
        width: 8px;
        height: 8px;
        border-radius: 999px;
        background: #22c55e;
        box-shadow: 0 0 0 4px rgba(34, 197, 94, .18);
    }

    .chatbot-close {
        border: 0;
        background: rgba(255, 255, 255, .18);
        color: white;
        width: 38px;
        height: 38px;
        border-radius: 999px;
        font-size: 16px;
    }

    .chatbot-messages {
        flex: 1;
        overflow-y: auto;
        padding: 16px;
        background:
            radial-gradient(circle at top left, rgba(139, 0, 0, .08), transparent 35%),
            linear-gradient(180deg, #fffafa 0%, #fff 100%);
    }

    .chat-row {
        display: flex;
        align-items: flex-end;
        gap: 8px;
        margin-bottom: 12px;
        animation: msgIn .2s ease;
    }

    .chat-row.user {
        justify-content: flex-end;
        flex-direction: row-reverse;
    }

    @keyframes msgIn {
        from {
            opacity: 0;
            transform: translateY(8px);
        }

        to {
            opacity: 1;
            transform: translateY(0);
        }
    }

    .chat-bubble {
        max-width: 72%;
        padding: 9px 12px;
        border-radius: 16px;
        font-size: 13px;
        line-height: 1.35;
    }

    .chat-row.ai .chat-bubble {
        background: white;
        color: #1f2937;
        border: 1px solid #f1d6d6;
        border-top-left-radius: 7px;
        box-shadow: 0 8px 20px rgba(15, 23, 42, .06);
    }

    .chat-row.user .chat-bubble {
        background: linear-gradient(135deg, #8B0000, #c1121f);
        margin-left: auto;
        color: white;
        border-top-right-radius: 7px;
        box-shadow: 0 8px 20px rgba(139, 0, 0, .20);
    }

    .typing-bubble {
        display: inline-flex;
        align-items: center;
        gap: 8px;
    }

    .typing-text {
        font-size: 13px;
        font-weight: 700;
        color: #7f1d1d;
    }

    .typing-dots {
        display: inline-flex;
        gap: 4px;
        align-items: center;
    }

    .typing-dots span {
        width: 7px;
        height: 7px;
        background: #991b1b;
        border-radius: 50%;
        animation: typingBounce 1s infinite ease-in-out;
    }

    .typing-dots span:nth-child(2) {
        animation-delay: .15s;
    }

    .typing-dots span:nth-child(3) {
        animation-delay: .3s;
    }

    #typing-indicator .chat-bubble {
        width: auto;
        min-width: 118px;
        max-width: 150px;
        padding: 10px 12px;
        border-radius: 16px;
    }

    #typing-indicator .typing-text {
        font-size: 12px;
    }

    @keyframes typingBounce {

        0%,
        80%,
        100% {
            transform: translateY(0);
            opacity: .45;
        }

        40% {
            transform: translateY(-5px);
            opacity: 1;
        }
    }

    .chatbot-quick-chips {
        display: grid;
        grid-template-columns: repeat(3, 1fr);
        gap: 8px;
        padding: 8px 12px 10px;
        margin-top: 0;
        background: white;
    }

    .chatbot-chip {
        min-width: 0;
        border: 1px solid #f0b8b8;
        background: #fff7f7;
        color: #8B0000;
        border-radius: 16px;
        padding: 8px 6px;
        font-size: 11px;
        font-weight: 900;
        display: grid;
        place-items: center;
        gap: 4px;
    }

    .chatbot-chip i {
        font-size: 13px;
    }

    .chatbot-chip span {
        white-space: normal;
        text-align: center;
        line-height: 1.2;
    }

    .chatbot-chip:hover {
        background: #8B0000;
        color: white;
    }

    .chatbot-footer {
        padding: 12px;
        background: white;
        border-top: 1px solid #f1d6d6;
    }

    .chatbot-input-wrap {
        display: flex;
        align-items: center;
        gap: 8px;
        background: #fff5f5;
        border: 1px solid #f1c9c9;
        border-radius: 20px;
        padding: 8px;
    }

    .chatbot-input-wrap input {
        flex: 1;
        border: 0;
        outline: 0;
        background: transparent;
        font-size: 14px;
        padding: 8px 9px;
    }

    .chatbot-send {
        width: 42px;
        height: 42px;
        border: 0;
        border-radius: 15px;
        background: #8B0000;
        color: white;
        display: grid;
        place-items: center;
        transition: .2s ease;
    }

    .chatbot-send:disabled {
        opacity: .55;
        cursor: not-allowed;
        transform: scale(.96);
    }

    .chat-message-avatar {
        width: 30px;
        height: 30px;
        border-radius: 999px;
        flex: 0 0 30px;
        display: grid;
        place-items: center;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 6px 14px rgba(15, 23, 42, .12);
    }

    .chat-row.ai .chat-message-avatar {
        background: #fff1f1;
        color: #8B0000;
        border: 1px solid #f1c9c9;
    }

    .chat-row.user .chat-message-avatar {
        background: linear-gradient(135deg, #8B0000, #c1121f);
        color: #fff;
    }

    @media (min-width: 768px) and (max-width: 1024px) {
        .chatbot-panel {
            height: 65vh;
            max-width: 480px;
        }
    }

    @media (min-width: 641px) and (max-width: 1024px) {
        .chatbot-panel {
            right: 22px;
            left: auto;
            transform: none;

            width: 360px;
            height: 520px;
            bottom: calc(var(--chatbot-bottom-final, 92px) + 60px);
        }
    }

    @media (max-width: 640px) {

        .chatbot-messages {
            padding: 12px;
        }

        .chat-bubble {
            max-width: 70%;
            padding: 9px 11px;
            font-size: 13px;
        }

        .chat-message-avatar {
            width: 26px;
            height: 26px;
            flex-basis: 26px;
            font-size: 11px;
        }

        body.chatbot-open-mobile .back-to-top,
        body.chatbot-open-mobile .chatbot-fab,
        body.chatbot-open-mobile .asw-container,
        body.chatbot-open-mobile .asw-widget,
        body.chatbot-open-mobile .asw-menu-btn {
            opacity: 0 !important;
            pointer-events: none !important;
            transform: scale(.85) !important;
        }

        .chatbot-header {
            padding-top: 20px;
        }

        .chatbot-quick-chips {
            padding: 8px 10px 10px;
            gap: 7px;
        }

        .chatbot-chip {
            font-size: 10.5px;
            padding: 8px 5px;
        }

        .chatbot-footer {
            padding: 10px;
        }

        .chatbot-panel::before {
            content: "";
            width: 42px;
            height: 5px;
            border-radius: 999px;
            background: rgba(255, 255, 255, .55);
            position: absolute;
            top: 8px;
            left: 50%;
            transform: translateX(-50%);
            z-index: 2;
        }

        .chatbot-fab {
            right: var(--float-right-final, 18px);
            bottom: var(--chatbot-bottom-final, 92px);
        }

        .chatbot-panel {
            left: 0;
            right: 0;
            bottom: 0;
            width: 100%;
            height: 75vh;
            border-radius: 24px 24px 0 0;
            transform: translateY(100%);
            opacity: 0;
            transition:
                opacity .24s ease,
                transform .36s cubic-bezier(.22, 1, .36, 1);
        }

        .chatbot-panel.show {
            opacity: 1;
            transform: translateY(0);
        }

        .chatbot-panel.closing {
            opacity: 1;
            transform: translateY(100%);
        }
    }

    .chat-action-btn {
        border: 0;
        border-radius: 999px;
        background: linear-gradient(135deg, #8B0000, #c1121f);
        color: white;
        padding: 9px 13px;
        font-size: 12px;
        font-weight: 900;
        box-shadow: 0 8px 18px rgba(139, 0, 0, .20);
    }

    .chat-action-bubble {
        background: transparent !important;
        border: 0 !important;
        box-shadow: none !important;
        padding: 0 !important;
    }

    .chat-row.grouped .chat-message-avatar {
        opacity: 0;
    }

    .chat-status-text {
        display: block;
        margin-top: 4px;
        font-size: 10px;
        font-weight: 700;
        opacity: .75;
        text-align: right;
    }

    .chat-empty-state {
        text-align: center;
        color: #7f1d1d;
        padding: 22px 14px;
        font-size: 12px;
        font-weight: 700;
    }

    .chat-empty-state i {
        width: 42px;
        height: 42px;
        display: grid;
        place-items: center;
        margin: 0 auto 8px;
        border-radius: 16px;
        background: #fff1f1;
        color: #8B0000;
    }

    .chat-empty-state h4 {
        margin: 10px 0 4px;
        font-size: 14px;
        font-weight: 900;
    }

    .chat-empty-state p {
        max-width: 260px;
        margin: 0 auto;
        font-size: 12px;
        color: #6b7280;
        line-height: 1.4;
    }

    .chat-empty-orbit {
        position: relative;
        width: 64px;
        height: 64px;
        margin: 0 auto;
        display: grid;
        place-items: center;
        border-radius: 22px;
        background: linear-gradient(135deg, #fff1f1, #ffffff);
        box-shadow: 0 14px 28px rgba(139, 0, 0, .12);
    }

    .chat-empty-orbit i {
        color: #8B0000;
        font-size: 24px;
        animation: toothFloat 1.9s ease-in-out infinite;
    }

    .chat-empty-orbit span {
        position: absolute;
        width: 7px;
        height: 7px;
        border-radius: 999px;
        background: #c1121f;
        opacity: .75;
        animation: orbitDot 2.2s linear infinite;
    }

    .chat-empty-orbit span:nth-child(2) {
        top: 6px;
        left: 50%;
    }

    .chat-empty-orbit span:nth-child(3) {
        right: 8px;
        bottom: 16px;
        animation-delay: .3s;
    }

    .chat-empty-orbit span:nth-child(4) {
        left: 10px;
        bottom: 14px;
        animation-delay: .6s;
    }

    @keyframes toothFloat {
        50% {
            transform: translateY(-4px);
        }
    }

    @keyframes orbitDot {
        50% {
            transform: scale(1.35);
            opacity: .35;
        }
    }


    .chat-action-btn,
    .chatbot-chip,
    .chatbot-send {
        position: relative;
        overflow: hidden;
    }

    .ripple {
        position: absolute;
        border-radius: 50%;
        transform: scale(0);
        animation: ripple .45s linear;
        background: rgba(255, 255, 255, .45);
        pointer-events: none;
    }

    @keyframes ripple {
        to {
            transform: scale(4);
            opacity: 0;
        }
    }

    .chat-highlight-target {
        outline: 4px solid rgba(193, 18, 31, .25);
        border-radius: 18px;
        animation: highlightPulse 1.3s ease 2;
    }

    @keyframes highlightPulse {
        50% {
            box-shadow: 0 0 0 8px rgba(193, 18, 31, .15);
        }
    }

    [data-theme="dark"] .chatbot-panel {
        background: linear-gradient(180deg, #1a0f0f 0%, #140909 100%);
        border-color: rgba(193, 18, 31, .25);
        box-shadow: 0 28px 80px rgba(0, 0, 0, .6);
    }

    [data-theme="dark"] .chatbot-messages {
        background:
            radial-gradient(circle at top left, rgba(193, 18, 31, .22), transparent 40%),
            linear-gradient(180deg, #140909 0%, #0f0606 100%);
    }

    [data-theme="dark"] .chat-empty-state h4 {
        color: #fecaca;
    }

    [data-theme="dark"] .chat-empty-state p {
        color: #e5e7eb;
    }

    [data-theme="dark"] .chat-empty-orbit {
        background: linear-gradient(135deg, rgba(127, 29, 29, .45), rgba(15, 23, 42, .95));
        box-shadow: 0 14px 30px rgba(0, 0, 0, .35);
    }

    [data-theme="dark"] .chatbot-quick-chips,
    [data-theme="dark"] .chatbot-footer {
        background: #140909;
        border-top: 1px solid rgba(193, 18, 31, .25);
    }

    [data-theme="dark"] .chatbot-chip {
        background: rgba(193, 18, 31, .12);
        border-color: rgba(248, 113, 113, .35);
        color: #fecaca;
    }

    [data-theme="dark"] .chatbot-chip:hover {
        background: #8B0000;
        color: #fff;
    }

    [data-theme="dark"] .chatbot-input-wrap {
        background: rgba(255, 255, 255, .04);
        border: 1px solid rgba(248, 113, 113, .28);
    }

    [data-theme="dark"] .chatbot-input-wrap input {
        color: #f9fafb;
    }

    [data-theme="dark"] .chatbot-input-wrap input::placeholder {
        color: #9ca3af;
    }

    [data-theme="dark"] .chatbot-panel {
        box-shadow:
            0 28px 80px rgba(0, 0, 0, .6),
            0 0 40px rgba(193, 18, 31, .15);
    }

    .chatbot-input-wrap {
        border-radius: 999px;
        padding: 8px 9px 8px 14px;
    }

    .chatbot-send {
        width: 44px;
        height: 44px;
        min-width: 44px;
        border-radius: 999px;
        font-size: 15px;
    }
</style>

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
