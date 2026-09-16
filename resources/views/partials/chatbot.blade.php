<div class="assistive-fab-group" id="assistiveFabGroup">

    <button type="button" class="assistive-main-fab" id="assistiveMainFab" onclick="toggleAssistiveMenu()"
        aria-label="Open assistance tools" aria-expanded="false">
        <i class="fa-solid fa-wand-magic-sparkles"></i>
    </button>

    <div class="assistive-mini-menu" id="assistiveMiniMenu" aria-hidden="true">

        <button type="button" class="assistive-mini-fab chatbot-fab" onclick="closeAssistiveMenu(); toggleChat();"
            aria-label="Open dental chatbot" data-tooltip="Chatbot" data-tooltip-tone="view">
            <i class="fas fa-comments"></i>
        </button>

        <button type="button" class="assistive-mini-fab accessibility-fab" onclick="toggleAccessibilityFromMain()"
            aria-label="Open accessibility tools" data-tooltip="Accessibility" data-tooltip-tone="view">
            <i class="fa-solid fa-universal-access"></i>
        </button>

    </div>
</div>

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

    <div id="chat-warning" class="chat-warning" role="alert" aria-live="polite">
        <div class="chat-warning-icon">
            <i class="fa-solid fa-triangle-exclamation"></i>
        </div>

        <div class="chat-warning-content">
            <strong id="chat-warning-title">Warning</strong>
            <p id="chat-warning-message"></p>
        </div>

        <button type="button" class="chat-warning-close" onclick="hideChatWarning()" aria-label="Close warning">
            <i class="fa-solid fa-xmark"></i>
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

            <h4 id="chat-empty-title">How can I help you today?</h4>
            <p id="chat-empty-desc">Ask me about appointments, dental records, odontogram, or document requests.</p>
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
            <input type="text" id="user-input" placeholder="Type your question..." autocomplete="off" maxlength="300"
                aria-describedby="chat-input-counter">

            <span id="chat-input-counter" class="chat-input-counter">0/300</span>
            <button type="button" id="send-btn" class="chatbot-send" onclick="sendMessage()">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>

<script>
    window.authUserName = "{{ optional(auth()->user())->name ?? '' }}";
    window.authUserId = "{{ auth()->id() ?? 'guest' }}";
    window.authUserRole =
        "{{ session('impersonated_role') ?? (optional(optional(auth()->user())->role)->slug ?? 'guest') }}";
    window.chatbotSessionId = "{{ session()->getId() }}";
    window.chatbotBotName = 'PUP SmileGuide AI';

    window.chatbotDocumentRequestStoreUrl =
        @json(route('patient.document.requests.store'));
</script>

<script>
    const chatWindow = document.getElementById('chat-window');
    const input = document.getElementById('user-input');
    const msgDiv = document.getElementById('chat-messages');
    const sendBtn = document.getElementById('send-btn');
    const chatbotContext = window.chatbotContext || {};
    const isLoginPage = chatbotContext.page === 'login' || window.location.pathname === '/login';
    const botName = window.chatbotBotName || 'PUP SmileGuide AI';

    const currentUserId = window.authUserId || document.querySelector('meta[name="auth-user-id"]')?.getAttribute(
        'content') || 'guest';

    function normalizeChatbotRole(role) {
        const normalizedRole = String(role || 'guest')
            .trim()
            .toLowerCase();

        const roleAliases = {
            admin: 'admin',
            super_admin: 'admin',
            'super-admin': 'admin',
            superadmin: 'admin',

            dentist: 'dentist',
            dentist_role: 'dentist',
            'dentist-role': 'dentist',

            patient: 'patient',
            patient_role: 'patient',
            'patient-role': 'patient',

            guest: 'guest'
        };

        return roleAliases[normalizedRole] || 'guest';
    }

    const currentUserRole = normalizeChatbotRole(
        window.authUserRole
    );

    const currentChatSessionId = window.chatbotSessionId || 'session';

    const chatStorageKey = `puptdms_chatbot_messages_${currentUserRole}_${currentUserId}_${currentChatSessionId}`;
    const chatOpenStorageKey = `puptdms_chatbot_open_${currentUserRole}_${currentUserId}_${currentChatSessionId}`;
    const assistiveOpenStorageKey =
        `puptdms_assistive_menu_open_${currentUserRole}_${currentUserId}_${currentChatSessionId}`;

    let introShown = false;
    let lastMessageType = null;

    let lastChatSentAt = 0;
    let chatBurstCount = 0;
    let chatBurstWindowStartedAt = Date.now();

    const CHAT_COOLDOWN_MS = 2500;
    const CHAT_BURST_LIMIT = 6;
    const CHAT_BURST_WINDOW_MS = 60000;
    const CHAT_MAX_CHARS = 300;
    const CHAT_MAX_WORDS = 60;
    const inputCounter = document.getElementById('chat-input-counter');
    const chatWarning = document.getElementById('chat-warning');
    const chatWarningTitle = document.getElementById('chat-warning-title');
    const chatWarningMessage = document.getElementById('chat-warning-message');

    let chatWarningTimer = null;

    function showChatWarning(message, title = 'Warning') {
        if (!chatWarning) return;

        chatWarningTitle.textContent = title;
        chatWarningMessage.textContent = message;

        chatWarning.classList.add('show');

        clearTimeout(chatWarningTimer);

        chatWarningTimer = setTimeout(() => {
            hideChatWarning();
        }, 5000);
    }

    function hideChatWarning() {
        if (!chatWarning) return;

        chatWarning.classList.remove('show');
        clearTimeout(chatWarningTimer);
    }

    function countWords(value) {
        return value.trim().split(/\s+/).filter(Boolean).length;
    }

    function trimToWordLimit(value) {
        const words = value.trim().split(/\s+/).filter(Boolean);

        if (words.length <= CHAT_MAX_WORDS) {
            return value;
        }

        return words.slice(0, CHAT_MAX_WORDS).join(' ');
    }

    function updateChatInputCounter() {
        if (!input || !inputCounter) return;

        const words = countWords(input.value);
        const chars = input.value.length;

        inputCounter.textContent = `${chars}/${CHAT_MAX_CHARS}`;

        inputCounter.classList.toggle(
            'is-warning',
            chars >= CHAT_MAX_CHARS - 30 || words >= CHAT_MAX_WORDS - 10
        );
    }

    input.addEventListener('input', function() {
        if (input.value.length > CHAT_MAX_CHARS) {
            input.value = input.value.slice(0, CHAT_MAX_CHARS);
        }

        if (countWords(input.value) > CHAT_MAX_WORDS) {
            input.value = trimToWordLimit(input.value);
        }

        updateChatInputCounter();
    });

    updateChatInputCounter();

    function canSendChatMessage() {
        const now = Date.now();

        if (now - lastChatSentAt < CHAT_COOLDOWN_MS) {
            showChatWarning(
                'Please wait a few seconds before sending another message.',
                'Please slow down'
            );

            return false;
        }

        if (now - chatBurstWindowStartedAt > CHAT_BURST_WINDOW_MS) {
            chatBurstWindowStartedAt = now;
            chatBurstCount = 0;
        }

        if (chatBurstCount >= CHAT_BURST_LIMIT) {
            showChatWarning(
                'You have sent too many messages. Please wait a minute before trying again.',
                'Too many messages'
            );

            return false;
        }

        lastChatSentAt = now;
        chatBurstCount++;

        return true;
    }

    const roleConfig = {
        admin: {
            intro: `Hi! I’m <strong>${botName}</strong>. I can help you with <strong>dashboard analytics</strong>, <strong>patient directory</strong>, <strong>appointments management</strong>, <strong>document requests</strong>, <strong>reports</strong>, and <strong>system settings</strong>.`,
            emptyDesc: 'Ask me about admin dashboard, patients, appointments, reports, inventory, document requests, and system settings.',
            defaultChips: [
                ['Patients', 'How can I manage patients in the admin dashboard?'],
                ['Appointments', 'How can I view and manage clinic appointments as admin?'],
                ['Reports', 'Where can I view reports and analytics?']
            ]
        },
        dentist: {
            intro: `Hi! I’m <strong>${botName}</strong>. I can help you with <strong>today’s appointments</strong>, <strong>patient profiles</strong>, <strong>odontogram</strong>, <strong>walk-ins</strong>, <strong>clinic schedule</strong>, and <strong>reports</strong>.`,
            emptyDesc: 'Ask me about dentist appointments, patient profiles, odontogram, walk-ins, reports, and clinic schedule.',
            defaultChips: [
                ['Today', 'How can I check today’s appointments?'],
                ['Patients', 'How can I open a patient profile?'],
                ['Odontogram', 'How can I start or view a patient odontogram?']
            ]
        },
        patient: {
            intro: `Hi! I’m <strong>${botName}</strong>. I’m ready to help with <strong>appointments</strong>, <strong>dental records</strong>, <strong>schedules</strong>, and <strong>document requests</strong>.`,
            emptyDesc: 'Ask me about appointments, dental records, odontogram, or document requests.',
            defaultChips: [
                ['Book', 'How do I book an appointment from the patient dashboard?'],
                ['Records', 'How can I open my dental records from the dashboard?'],
                ['Documents', 'Where can I request a dental clearance document?']
            ]
        },
        guest: {
            intro: `Hi! I’m <strong>${botName}</strong>. This is the <strong>login page</strong>, so I can help you with <strong>signing in</strong>, <strong>SSO access</strong>, and what you can do after you log in.`,
            emptyDesc: 'Ask me about signing in, SSO access, or login help.',
            defaultChips: [
                ['Log in', 'How do I log in to the clinic system?'],
                ['SSO', 'How do I use the SSO login option?'],
                ['Help', 'What can I do on this login page?']
            ]
        }
    };

    const activeRoleConfig = roleConfig[currentUserRole] || roleConfig.guest;

    function syncChatEmptyState() {
        document.getElementById('chat-empty-desc')?.replaceChildren(document.createTextNode(activeRoleConfig
            .emptyDesc));
    }

    syncChatEmptyState();

    function toggleAssistiveMenu(forceClose = false) {
        const group = document.getElementById('assistiveFabGroup');
        const menu = document.getElementById('assistiveMiniMenu');
        const mainBtn = document.getElementById('assistiveMainFab');

        if (!group || !menu || !mainBtn) return;

        const isOpen = group.classList.contains('open');
        const shouldOpen = forceClose ? false : !isOpen;

        group.classList.toggle('open', shouldOpen);
        menu.setAttribute('aria-hidden', shouldOpen ? 'false' : 'true');
        mainBtn.setAttribute('aria-expanded', shouldOpen ? 'true' : 'false');

        localStorage.setItem(assistiveOpenStorageKey, shouldOpen ? '1' : '0');
    }

    function openAssistiveMenu() {
        toggleAssistiveMenu(false);
    }

    function closeAssistiveMenu() {
        toggleAssistiveMenu(true);
    }

    function toggleAccessibilityFromMain() {
        closeChatOnly();
        closeAssistiveMenu();

        const clickAccessibilityButton = (attempt = 0) => {
            const aswButton = document.querySelector('.asw-menu-btn');

            if (aswButton) {
                aswButton.dispatchEvent(new MouseEvent('click', {
                    bubbles: true,
                    cancelable: true,
                    view: window
                }));
                return;
            }

            if (attempt < 10) {
                setTimeout(() => clickAccessibilityButton(attempt + 1), 150);
            } else {
                console.warn('Sienna accessibility button was not found.');
            }
        };

        clickAccessibilityButton();
    }

    function closeChatOnly() {
        if (!chatWindow.classList.contains('show')) return;

        chatWindow.classList.remove('show');
        chatWindow.classList.add('closing');

        setTimeout(() => {
            chatWindow.classList.remove('closing');
        }, 380);

        document.body.classList.remove('chatbot-open-mobile');
        localStorage.setItem(chatOpenStorageKey, '0');
    }

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
        localStorage.setItem(chatOpenStorageKey, isNowOpen ? '1' : '0');
        const isMobile = window.matchMedia('(max-width: 640px)').matches;

        document.body.classList.toggle('chatbot-open-mobile', isNowOpen && isMobile);

        if (isNowOpen) {
            if (!introShown) {
                showIntroMessage();
                introShown = true;
            }

            input.value = '';
            updateChatInputCounter();

            setTimeout(() => input.focus(), 100);
        }
    }

    function showIntroMessage() {
        const introText = isLoginPage ? roleConfig.guest.intro : activeRoleConfig.intro;

        addMessage('ai', introText, {
            allowHtml: true
        });
    }

    function escapeHTML(text) {
        const div = document.createElement('div');
        div.textContent = text;
        return div.innerHTML;
    }

    function getStoredMessages() {
        try {
            return JSON.parse(localStorage.getItem(chatStorageKey) || '[]');
        } catch (e) {
            return [];
        }
    }

    function saveStoredMessages(messages) {
        localStorage.setItem(chatStorageKey, JSON.stringify(messages.slice(-60)));
    }

    function saveChatMessage(type, text, options = {}) {
        const messages = getStoredMessages();

        messages.push({
            type,
            text,
            allowHtml: Boolean(options.allowHtml),
            status: options.status || '',
            createdAt: Date.now()
        });

        saveStoredMessages(messages);
    }

    function restoreChatMessages() {
        const messages = getStoredMessages();

        if (!messages.length) return false;

        const empty = msgDiv.querySelector('.chat-empty-state');
        if (empty) empty.remove();

        messages.forEach(message => {
            addMessage(message.type, message.text, {
                allowHtml: message.allowHtml,
                status: message.status,
                skipSave: true
            });
        });

        introShown = true;
        scrollChat();

        return true;
    }

    function cleanChatbotResponse(text) {
        if (typeof text !== 'string') {
            return text;
        }

        return text
            .replace(/\*\*(.*?)\*\*/g, '$1')
            .replace(/\*\*/g, '')
            .trim();
    }

    function addMessage(type, text, options = {}) {
        if (type === 'ai') {
            text = cleanChatbotResponse(text);
        }

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

        if (!options.skipSave && type !== 'typing') {
            saveChatMessage(type, text, options);
        }

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

    function lockPageScrollInsideChatbot() {
        if (!chatWindow || !msgDiv) return;

        msgDiv.addEventListener('wheel', function(event) {
            const atTop = msgDiv.scrollTop <= 0;
            const atBottom = Math.ceil(msgDiv.scrollTop + msgDiv.clientHeight) >= msgDiv.scrollHeight;

            const scrollingUp = event.deltaY < 0;
            const scrollingDown = event.deltaY > 0;

            if ((scrollingUp && atTop) || (scrollingDown && atBottom)) {
                event.preventDefault();
            }

            event.stopPropagation();
        }, {
            passive: false
        });

        let startY = 0;

        msgDiv.addEventListener('touchstart', function(event) {
            startY = event.touches[0].clientY;
        }, {
            passive: true
        });

        msgDiv.addEventListener('touchmove', function(event) {
            const currentY = event.touches[0].clientY;
            const deltaY = startY - currentY;

            const atTop = msgDiv.scrollTop <= 0;
            const atBottom = Math.ceil(msgDiv.scrollTop + msgDiv.clientHeight) >= msgDiv.scrollHeight;

            const scrollingUp = deltaY < 0;
            const scrollingDown = deltaY > 0;

            if ((scrollingUp && atTop) || (scrollingDown && atBottom)) {
                event.preventDefault();
            }

            event.stopPropagation();
        }, {
            passive: false
        });
    }

    lockPageScrollInsideChatbot();

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

        sendMessage(message);
    }

    function cleanErrorMessage(data) {
        const message = data?.error || data?.body || '';

        if (data?.status === 429 || message.toLowerCase().includes('too many')) {
            return 'You are sending messages too quickly. Please wait before trying again.';
        }

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


    async function submitChatbotDocumentRequest(action) {
        const documentType = String(
            action?.document_type || ''
        ).trim();

        const purpose = String(
            action?.purpose || ''
        ).trim();

        if (!documentType || !purpose) {
            addMessage(
                'ai',
                'I need both the document type and purpose before I can submit the request.'
            );

            return;
        }

        const csrfMeta =
            document.querySelector(
                'meta[name="csrf-token"]'
            );

        const csrfToken =
            csrfMeta ?
            csrfMeta.getAttribute('content') :
            '';

        try {
            const response = await fetch(
                window.chatbotDocumentRequestStoreUrl ||
                '/document-requests', {
                    method: 'POST',

                    headers: {
                        'Content-Type': 'application/json',

                        'Accept': 'application/json',

                        'X-Requested-With': 'XMLHttpRequest',

                        'X-CSRF-TOKEN': csrfToken
                    },

                    body: JSON.stringify({
                        document_type: documentType,

                        purpose: purpose
                    })
                }
            );

            let data = {};

            const responseText =
                await response.text();

            if (responseText) {
                try {
                    data =
                        JSON.parse(
                            responseText
                        );
                } catch (parseError) {
                    console.warn(
                        'Document request returned a non-JSON response.',
                        parseError
                    );
                }
            }

            if (
                !response.ok ||
                !data.success
            ) {
                let message =
                    data?.message || '';

                if (!message) {
                    if (
                        response.status === 401
                    ) {
                        message =
                            'Your patient session could not be verified. Please sign in again.';
                    } else if (
                        response.status === 403
                    ) {
                        message =
                            'Your current role is not allowed to submit a patient document request.';
                    } else if (
                        response.status === 419
                    ) {
                        message =
                            'Your session has expired. Please refresh the page and try again.';
                    } else if (
                        response.status === 422
                    ) {
                        message =
                            'The document request could not be submitted. Please check the request details.';
                    } else {
                        message =
                            'The document request could not be submitted right now. Please try again.';
                    }
                }

                addMessage(
                    'ai',
                    message
                );

                return;
            }


            addMessage(
                'ai',
                `Your ${documentType} request for ${purpose} was submitted successfully and is now pending review by the dental clinic.`
            );

            addActionButton(
                'View Document Requests',
                '/document-requests'
            );

        } catch (error) {
            console.error(
                'Chatbot document request failed.',
                error
            );

            addMessage(
                'ai',
                'I could not connect to the document request service. Please try again.'
            );
        }
    }

    function runSystemCommand(message) {
        const command = message.trim().toLowerCase();

        const roleRoutes = {
            admin: {
                '/patients': '/admin/patient-directory',
                '/appointments': '/admin/appointments',
                '/documents': '/admin/document-requests',
                '/reports': '/admin/reports',
                '/inventory': '/admin/inventory',
                '/settings': '/admin/system-settings'
            },
            dentist: {
                '/patients': '/dentist/patients',
                '/appointments': '/dentist/appointments',
                '/documents': '/dentist/document-requests',
                '/reports': '/dentist/report',
                '/inventory': '/dentist/inventory',
                '/schedule': '/dentist/clinic-schedule',
                '/walkin': '/dentist/walk-in'
            },
            patient: {
                '/book': '/book-appointment',
                '/records': '/record',
                '/appointments': '/patient/appointments',
                '/documents': '/document-requests'
            }
        };

        const routes = roleRoutes[currentUserRole] || {};

        if (routes[command]) {
            window.location.href = routes[command];
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

    async function sendMessage(messageOverride = null) {
        const message = String(
            messageOverride !== null ?
            messageOverride :
            input.value
        ).trim();

        if (!message || sendBtn.disabled) return;

        if (message.length > CHAT_MAX_CHARS || countWords(message) > CHAT_MAX_WORDS) {
            showChatWarning(
                `Please keep your message within ${CHAT_MAX_CHARS} characters or ${CHAT_MAX_WORDS} words.`,
                'Message too long'
            );

            return;
        }

        if (!canSendChatMessage()) {
            return;
        }

        addMessage('user', message, {
            status: 'Sent ✓'
        });

        input.value = '';
        updateChatInputCounter();
        setLoading(true);
        runSystemCommand(message);
        await smartDelay();
        showTyping();

        try {
            const csrfMeta = document.querySelector('meta[name="csrf-token"]');
            const csrfToken = csrfMeta ? csrfMeta.getAttribute('content') : (document.querySelector(
                'input[name="_token"]') ? document.querySelector('input[name="_token"]').value : '');

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
                if (response.status === 429) {
                    showChatWarning(
                        data?.error ||
                        'You have sent too many messages. Please wait a minute before trying again.',
                        'Too many messages'
                    );

                    return;
                }

                throw new Error(cleanErrorMessage(data));
            }

            if (
                data?.action?.type ===
                'submit_document_request'
            ) {
                await submitChatbotDocumentRequest(
                    data.action
                );

                return;
            }

            let reply =
                data.reply ||
                'No response from AI.';

            addMessage(
                'ai',
                reply
            );

            handleSmartActions(
                data.reply || '',
                message
            );

        } catch (error) {
            removeTyping();
            console.error(error);

            showChatWarning(
                error.message || 'AI assistant is unavailable. Please try again.',
                'Unable to send message'
            );
        } finally {
            setLoading(false);
            input.focus();
        }
    }

    function handleSmartActions(reply, originalMessage = '') {
        if (isLoginPage) return;

        const replyText = (reply || '').toLowerCase();
        const userText = (originalMessage || '').toLowerCase();

        const unclearWords = [
            'unclear',
            'please specify',
            'specify what you need',
            'request is unclear',
            'i am not sure',
            "i'm not sure"
        ];

        if (unclearWords.some(word => replyText.includes(word))) {
            return;
        }

        const actionMap = {
            admin: [
                [
                    ['dashboard', 'admin dashboard'], 'Go to Dashboard', '/admin/dashboard'
                ],
                [
                    ['patient directory', 'patients page', 'manage patients', 'search patients'],
                    'Go to Patients', '/admin/patient-directory'
                ],
                [
                    ['appointment', 'appointments', 'reschedule', 'cancel appointment'], 'Go to Appointments',
                    '/admin/appointments'
                ],
                [
                    ['document request', 'document requests', 'approve request', 'reject request'],
                    'Go to Document Requests', '/admin/document-requests'
                ],
                [
                    ['report', 'reports', 'analytics'], 'Go to Reports', '/admin/reports'
                ],
                [
                    ['inventory', 'stock', 'supplies', 'medicine'], 'Go to Inventory', '/admin/inventory'
                ],
                [
                    ['setting', 'settings', 'system settings'], 'Go to System Settings',
                    '/admin/system-settings'
                ]
            ],
            dentist: [
                [
                    ['patient profile', 'patient profiles', 'manage patients'], 'Go to Patients',
                    '/dentist/patients'
                ],
                [
                    ['appointment', 'appointments', 'follow-up', 'consultation'], 'Go to Appointments',
                    '/dentist/appointments'
                ],
                [
                    ['odontogram'], 'Go to Patients', '/dentist/patients'
                ],
                [
                    ['walk-in', 'walk in'], 'Go to Walk-in', '/dentist/walk-in'
                ],
                [
                    ['document request', 'document requests'], 'Go to Document Requests',
                    '/dentist/document-requests'
                ],
                [
                    ['report', 'reports'], 'Go to Reports', '/dentist/report'
                ],
                [
                    ['inventory', 'stock', 'supplies', 'medicine'], 'Go to Inventory', '/dentist/inventory'
                ],
                [
                    ['schedule', 'clinic schedule'], 'Go to Clinic Schedule', '/dentist/clinic-schedule'
                ]
            ],
            patient: [
                [
                    ['appointment', 'appointments'], 'Go to Appointments', '/patient/appointments'
                ],
                [
                    ['book', 'booking', 'available date'], 'Book Appointment', '/book-appointment'
                ],
                [
                    ['record', 'records', 'dental record', 'odontogram'], 'Go to Dental Records', '/record'
                ],
                [
                    ['document', 'clearance', 'document request'], 'Go to Document Requests',
                    '/document-requests'
                ],
                [
                    ['schedule', 'available'], 'Check Available Dates', '/book-appointment'
                ]
            ]
        };

        const actions = actionMap[currentUserRole] || [];

        const matched = actions.find(([keywords]) => {
            return keywords.some(keyword => userText.includes(keyword));
        });

        if (matched) {
            addActionButton(matched[1], matched[2]);
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

    const rolePageChips = {
        admin: {
            default: [
                ['Patients', 'How can I manage patients in the admin dashboard?'],
                ['Appointments', 'How can I view and manage clinic appointments as admin?'],
                ['Reports', 'Where can I view reports and analytics?']
            ],

            '/admin/dashboard': [
                ['Dashboard', 'What can I see on the admin dashboard?'],
                ['Inventory', 'How can I check the inventory overview as admin?'],
                ['Reports', 'Where can I view clinic reports?']
            ],

            '/admin/patient-directory': [
                ['Patients', 'How can I search and manage patients?'],
                ['Records', 'How can I view a patient dental record?'],
                ['Profile', 'How can I open a patient profile?']
            ],

            '/admin/patient/': [
                ['Profile', 'What information can I view in this patient profile?'],
                ['Records', 'How can I review this patient dental records?'],
                ['Appointments', 'How can I check this patient appointments?']
            ],

            '/admin/dental-records': [
                ['Records', 'How can I review dental records as admin?'],
                ['Patient', 'How can I find a patient dental record?'],
                ['Details', 'What information is available in a dental record?']
            ],

            '/dentist/walk-in': [
                ['Walk-in', 'How does the walk-in patient workflow work?'],
                ['Patient', 'How can I search an existing patient for a walk-in?'],
                ['Start', 'How can I start a walk-in consultation?']
            ],

            '/admin/add-existing-record': [
                ['Existing Record', 'How can I add an existing dental record?'],
                ['Patient', 'How can I search for a patient before adding an existing record?'],
                ['Record', 'What information do I need when adding an existing record?']
            ],

            '/admin/dentist-transitions': [
                ['Continuity', 'How does Dentist Continuity work?'],
                ['Successor', 'How can I assign a successor dentist?'],
                ['Finalize', 'How can I finalize a dentist transition?']
            ],

            '/faculty-integration': [
                ['Faculty', 'What can I do on the Faculty Integration page?'],
                ['Search', 'How can I find a faculty member for integration?'],
                ['Access', 'How does faculty integration work in the clinic system?']
            ],

            '/admin/appointments': [
                ['Appointments', 'How can I manage appointments as admin?'],
                ['Reschedule', 'How can I reschedule an appointment?'],
                ['Cancel', 'How can I cancel an appointment?']
            ],

            '/admin/clinic-schedule': [
                ['Schedule', 'How can I manage the clinic schedule?'],
                ['Block Date', 'How can I block an unavailable clinic date?'],
                ['Availability', 'How can I manage available appointment schedules?']
            ],

            '/admin/academic-periods': [
                ['Periods', 'How do Academic Periods work?'],
                ['Active', 'How can I check or change the active academic period?'],
                ['Sync', 'How does Academic Period synchronization work?']
            ],

            '/admin/document-requests': [
                ['Requests', 'How can I review document requests?'],
                ['Approve', 'How can I approve a document request?'],
                ['Reject', 'How can I reject a document request?']
            ],

            '/admin/service-types': [
                ['Services', 'How can I manage dental service types?'],
                ['Add', 'How can I add a new service type?'],
                ['Status', 'How can I manage the status of a service type?']
            ],

            '/admin/document-template': [
                ['Templates', 'How can I manage document templates?'],
                ['Default', 'How can I set a default document template?'],
                ['Archive', 'How can I archive or activate a document template?']
            ],

            '/admin/inventory': [
                ['Inventory', 'How can I manage clinic inventory?'],
                ['Add Item', 'How can I add an inventory item?'],
                ['Stock', 'How can I check medicine and supply stock?']
            ],

            '/admin/report-files': [
                ['Reports', 'How can I generate clinic reports?'],
                ['Files', 'Where can I find generated report files?'],
                ['Download', 'How can I download a clinic report?']
            ],

            '/admin/reports': [
                ['AI Reports', 'What are AI-assisted reports?'],
                ['Generate', 'How can I generate an AI-assisted report?'],
                ['Download', 'How can I download an AI-generated report?']
            ],

            '/admin/user-management': [
                ['Users', 'What can I do in User Management?'],
                ['Create', 'How can I create a new user account?'],
                ['Role', 'How can I update a user role?']
            ],

            '/admin/assign-cms-access': [
                ['CMS Access', 'What is Assign CMS Access used for?'],
                ['Assign', 'How can I assign CMS access to a user?'],
                ['Status', 'How can I check a user CMS access status?']
            ],

            '/admin/role-permissions': [
                ['Roles', 'How can I manage roles?'],
                ['Permissions', 'How can I update role permissions?'],
                ['Custom Role', 'How can I create a custom role?']
            ],

            '/admin/system-logs': [
                ['Logs', 'What information can I see in System Logs?'],
                ['Search', 'How can I find a specific system log?'],
                ['Export', 'How can I export system logs?']
            ],

            '/admin/session-management': [
                ['Sessions', 'What can I see in the Session Dashboard?'],
                ['Devices', 'How can I review active user sessions and devices?'],
                ['Revoke', 'How can I terminate an active user session?']
            ],

            '/admin/system-settings': [
                ['Settings', 'What can I configure in System Settings?'],
                ['Notifications', 'How can I configure notification settings?'],
                ['Update', 'How can I update system settings?']
            ]
        },

        dentist: {
            default: [
                ['Today', 'How can I check today’s appointments?'],
                ['Patients', 'How can I open a patient profile?'],
                ['Odontogram', 'How can I start or view a patient odontogram?']
            ],

            '/dentist/dashboard': [
                ['Today', 'How can I check today’s appointments?'],
                ['Calendar', 'How can I view scheduled appointments?'],
                ['Reports', 'Where can I view dentist reports?']
            ],

            '/dentist/patients': [
                ['Patients', 'How can I view patient profiles?'],
                ['Records', 'How can I review patient dental records?'],
                ['Odontogram', 'How can I open a patient odontogram?']
            ],

            '/dentist/dental-records': [
                ['Records', 'How can I review dental records?'],
                ['Patient', 'How can I find a patient dental record?'],
                ['Treatment', 'Where can I review treatment information?']
            ],

            '/dentist/walk-in': [
                ['Walk-in', 'How can I add a walk-in patient?'],
                ['Search', 'How can I search an existing patient for walk-in?'],
                ['Start', 'How can I start a walk-in consultation?']
            ],

            '/dentist/add-existing-record': [
                ['Existing Record', 'How can I add an existing dental record?'],
                ['Search', 'How can I search a patient before adding an existing record?'],
                ['Save', 'How can I save an existing dental record?']
            ],

            '/dentist/transitions': [
                ['Continuity', 'What can I do in Dentist Continuity?'],
                ['Successor', 'How does dentist successor assignment work?'],
                ['Status', 'How can I check a dentist transition status?']
            ],

            '/dentist/faculty-integration': [
                ['Faculty', 'What can I do on the Faculty Integration page?'],
                ['Search', 'How can I find a faculty member for integration?'],
                ['Access', 'How does faculty integration work?']
            ],

            '/dentist/appointments': [
                ['Appointments', 'How can I manage appointments as dentist?'],
                ['Start', 'How can I start an appointment?'],
                ['Follow-up', 'How can I set a follow-up appointment?']
            ],

            '/dentist/clinic-schedule': [
                ['Schedule', 'How can I manage the clinic schedule?'],
                ['Availability', 'How can I manage available clinic schedules?'],
                ['Block Date', 'How can I block an unavailable date?']
            ],

            '/dentist/academic-periods': [
                ['Periods', 'How do Academic Periods work?'],
                ['Active', 'How can I check the active academic period?'],
                ['Sync', 'How does Academic Period synchronization work?']
            ],

            '/dentist/document-requests': [
                ['Requests', 'How can I review document requests?'],
                ['Approve', 'How can I approve a document request?'],
                ['Reject', 'How can I reject a document request?']
            ],

            '/dentist/service-types': [
                ['Services', 'How can I manage dental service types?'],
                ['Add', 'How can I add a service type?'],
                ['Status', 'How can I manage a service type status?']
            ],

            '/dentist/document-template': [
                ['Templates', 'How can I manage document templates?'],
                ['Default', 'How can I set a default document template?'],
                ['Archive', 'How can I archive or activate a document template?']
            ],

            '/dentist/inventory': [
                ['Inventory', 'How can I manage clinic inventory?'],
                ['Add Item', 'How can I add an inventory item?'],
                ['Stock', 'How can I check medicine and supply stock?']
            ],

            '/dentist/report': [
                ['Reports', 'What reports are available to the dentist?'],
                ['DTR', 'How can I generate a Daily Treatment Record report?'],
                ['Download', 'How can I download a clinic report?']
            ],

            '/dentist/reports': [
                ['AI Reports', 'What are AI-assisted reports?'],
                ['Generate', 'How can I generate an AI-assisted report?'],
                ['Download', 'How can I download an AI-generated report?']
            ],

            '/dentist/user-management': [
                ['Users', 'What can I do in User Management?'],
                ['Create', 'How can I create a user account?'],
                ['Role', 'How can I update a user role?']
            ],

            '/dentist/assign-cms-access': [
                ['CMS Access', 'What is Assign CMS Access used for?'],
                ['Assign', 'How can I assign CMS access?'],
                ['Status', 'How can I check CMS access status?']
            ],

            '/dentist/role-permissions': [
                ['Roles', 'How can I manage roles?'],
                ['Permissions', 'How can I update role permissions?'],
                ['Custom Role', 'How can I create a custom role?']
            ],

            '/dentist/system-logs': [
                ['Logs', 'What information can I see in System Logs?'],
                ['Search', 'How can I find a specific system log?'],
                ['Export', 'How can I export system logs?']
            ],

            '/dentist/system-settings': [
                ['Settings', 'What can I configure in System Settings?'],
                ['Notifications', 'How can I configure notification settings?'],
                ['Update', 'How can I update system settings?']
            ]
        },

        patient: {
            default: [
                ['Book', 'How do I book an appointment from the patient dashboard?'],
                ['Records', 'How can I open my dental records from the dashboard?'],
                ['Documents', 'Where can I request a dental clearance document?']
            ],

            '/homepage': [
                ['Book', 'How do I book an appointment from the patient dashboard?'],
                ['Schedule', 'Where can I check available appointment dates and clinic schedule?'],
                ['Records', 'How can I open my dental records from the dashboard?']
            ],

            '/patient/dashboard': [
                ['Book', 'How do I book an appointment from the patient dashboard?'],
                ['Schedule', 'Where can I check available appointment dates?'],
                ['Records', 'How can I open my dental records?']
            ],

            '/patient/appointment': [
                ['Available', 'How can I check available dates for booking an appointment?'],
                ['Reschedule', 'How can I reschedule my existing appointment?'],
                ['Cancel', 'How can I cancel my appointment in the system?']
            ],

            '/patient/appointments': [
                ['Available', 'How can I check available dates for booking an appointment?'],
                ['Reschedule', 'How can I reschedule my existing appointment?'],
                ['Cancel', 'How can I cancel my appointment in the system?']
            ],

            '/book-appointment': [
                ['Date', 'How can I choose an available appointment date?'],
                ['Time', 'How can I select an available appointment time?'],
                ['Booking', 'What do I need to complete my appointment booking?']
            ],

            '/patient/book-appointment': [
                ['Date', 'How can I choose an available appointment date?'],
                ['Time', 'How can I select an available appointment time?'],
                ['Booking', 'What do I need to complete my appointment booking?']
            ],

            '/signature-review': [
                ['Signature', 'What should I check on the signature review page?'],
                ['Booking', 'How do I complete my appointment booking?'],
                ['Edit', 'What should I do if my signature information is incorrect?']
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
            ],

            '/about-us': [
                ['Clinic', 'What information can I find on the About Us page?'],
                ['Services', 'What dental clinic services are available?'],
                ['Help', 'Where can I find information about the dental clinic?']
            ]
        },

        guest: {
            default: [
                ['Log in', 'How do I log in to the clinic system?'],
                ['SSO', 'How do I use the SSO login option?'],
                ['Help', 'What can I do on this login page?']
            ],

            '/login': [
                ['Log in', 'How do I log in to the clinic system?'],
                ['SSO', 'How do I use the SSO login option?'],
                ['Help', 'What can I do on this login page?']
            ],

            '/backup-login': [
                ['Log in', 'How do I use the backup login option?'],
                ['Account', 'What account can I use for backup login?'],
                ['Help', 'What should I do if I cannot sign in?']
            ]
        }
    };

    function getCurrentPageChips(roleChips) {
        const currentPath =
            window.location.pathname.replace(/\/+$/, '') || '/';

        if (roleChips[currentPath]) {
            return roleChips[currentPath];
        }

        const matchingPath = Object.keys(roleChips)
            .filter(path => {
                if (path === 'default') {
                    return false;
                }

                const normalizedPath =
                    path.replace(/\/+$/, '');

                return (
                    currentPath === normalizedPath ||
                    currentPath.startsWith(
                        normalizedPath + '/'
                    )
                );
            })
            .sort(
                (a, b) =>
                b.length - a.length
            )[0];

        return matchingPath ?
            roleChips[matchingPath] :
            roleChips.default;
    }

    function renderDynamicChips() {
        const chipWrap =
            document.querySelector(
                '.chatbot-quick-chips'
            );

        if (!chipWrap) {
            return;
        }

        const roleChips =
            rolePageChips[currentUserRole] ||
            rolePageChips.guest;

        const chips =
            getCurrentPageChips(
                roleChips
            );

        chipWrap.innerHTML =
            chips.map(
                ([label, message]) => `
                <button
                    type="button"
                    class="chatbot-chip"
                    onclick="sendQuickMessage('${message.replace(/'/g, "\\'")}')"
                >
                    ${label}
                </button>
            `
            ).join('');
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
        const widget = document.querySelector('.asw-menu');

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

    document.addEventListener('DOMContentLoaded', () => {
        restoreChatMessages();

        if (localStorage.getItem(assistiveOpenStorageKey) === '1') {
            const group = document.getElementById('assistiveFabGroup');
            const menu = document.getElementById('assistiveMiniMenu');
            const mainBtn = document.getElementById('assistiveMainFab');

            group?.classList.add('open');
            menu?.setAttribute('aria-hidden', 'false');
            mainBtn?.setAttribute('aria-expanded', 'true');
        }

        if (localStorage.getItem(chatOpenStorageKey) === '1') {
            chatWindow.classList.add('show');

            const isMobile = window.matchMedia('(max-width: 640px)').matches;
            document.body.classList.toggle('chatbot-open-mobile', isMobile);
        }

        document.querySelectorAll('form[action*="logout"], a[href*="logout"], button[data-logout]').forEach(
            item => {
                item.addEventListener('click', clearAssistiveStorageOnLogout);
                item.addEventListener('submit', clearAssistiveStorageOnLogout);
            });
    });

    function clearAssistiveStorageOnLogout() {
        Object.keys(localStorage).forEach(key => {
            if (
                key.startsWith('puptdms_chatbot_') ||
                key.startsWith('puptdms_assistive_') ||
                key.toLowerCase().includes('sienna') ||
                key.toLowerCase().includes('asw') ||
                key.toLowerCase().includes('accessibility')
            ) {
                localStorage.removeItem(key);
            }
        });
    }

    function clearOldChatbotStorageKeys() {
        Object.keys(localStorage).forEach(key => {
            const isOldChatKey =
                key === `puptdms_chatbot_messages_${currentUserId}` ||
                key === `puptdms_chatbot_open_${currentUserId}` ||
                key === `puptdms_assistive_menu_open_${currentUserId}`;

            if (isOldChatKey) {
                localStorage.removeItem(key);
            }
        });
    }

    clearOldChatbotStorageKeys();
</script>
