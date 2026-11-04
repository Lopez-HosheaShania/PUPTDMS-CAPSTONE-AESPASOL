@php
    if (!session()->has('chatbot_storage_id')) {
        session()->put('chatbot_storage_id', (string) \Illuminate\Support\Str::uuid());
    }
@endphp

<div class="assistive-fab-group" id="assistiveFabGroup">

    <button type="button" class="assistive-main-fab" id="assistiveMainFab" aria-label="Open assistance tools"
        aria-controls="assistiveMiniMenu" aria-expanded="false">
        <i class="fa-solid fa-wand-magic-sparkles" aria-hidden="true"></i>
    </button>

    <div class="assistive-mini-menu" id="assistiveMiniMenu" aria-hidden="true">

        <button type="button" class="assistive-mini-fab chatbot-fab" data-ui-click="action-15220cbe0457"
            aria-label="Open dental chatbot" data-tooltip="Chatbot" data-tooltip-tone="view">
            <i class="fas fa-comments"></i>
        </button>

        <button type="button" class="assistive-mini-fab accessibility-fab" data-ui-click="action-257d14926763"
            aria-label="Open accessibility tools" data-tooltip="Accessibility" data-tooltip-tone="view">
            <i class="fa-solid fa-universal-access"></i>
        </button>

    </div>
</div>

<div id="chat-window" class="chatbot-panel" data-chatbot-user-name="{{ optional(auth()->user())->name ?? '' }}"
    data-chatbot-user-id="{{ (string) (auth()->id() ?? 'guest') }}"
    data-chatbot-user-role="{{ session('impersonated_role') ?? (optional(optional(auth()->user())->role)->slug ?? 'guest') }}"
    data-chatbot-session-id="{{ session('chatbot_storage_id') }}"
    data-chatbot-document-request-url="{{ route('patient.document.requests.store') }}">
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

        <button type="button" class="chatbot-close" data-ui-click="action-a1629b68d2fa" aria-label="Close chatbot">
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

        <button type="button" class="chat-warning-close" data-ui-click="action-0d26e7107f0a"
            aria-label="Close warning">
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
        <button type="button" class="chatbot-chip" data-ui-click="action-cfe2cb05f09d">
            <i class="fas fa-tooth"></i>
            <span>Odontogram</span>
        </button>

        <button type="button" class="chatbot-chip" data-ui-click="action-9aa4327f110d">
            <i class="fas fa-calendar-check"></i>
            <span>Book</span>
        </button>

        <button type="button" class="chatbot-chip" data-ui-click="action-4f7aad6a7b70">
            <i class="fas fa-file-medical"></i>
            <span>Documents</span>
        </button>
    </div>

    <div class="chatbot-footer">
        <div class="chatbot-input-wrap">
            <input type="text" id="user-input" placeholder="Type your question..." autocomplete="off" maxlength="300"
                aria-describedby="chat-input-counter">

            <span id="chat-input-counter" class="chat-input-counter">0/300</span>
            <button type="button" id="send-btn" class="chatbot-send" data-ui-click="action-75963eb3acd5">
                <i class="fas fa-paper-plane"></i>
            </button>
        </div>
    </div>
</div>