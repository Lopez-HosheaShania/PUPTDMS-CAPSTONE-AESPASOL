<script>
(() => {
    if (window.__GLOBAL_VOICE_INPUTS_READY__) {
        document.dispatchEvent(new CustomEvent('voice:refresh', { detail: { root: document } }));
        return;
    }

    window.__GLOBAL_VOICE_INPUTS_READY__ = true;

    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;
    let activeController = null;

    function getTargetInput(button) {
        const targetSelector = button.dataset.voiceTarget;

        if (targetSelector) {
            return document.querySelector(targetSelector);
        }

        const field = button.closest('[data-voice-field], .voice-search-row, .st-voice-row, .voice-input-wrap');
        return field?.querySelector('input:not([type="hidden"]), textarea') || null;
    }

    function getStatusLabel(button) {
        const statusSelector = button.dataset.voiceStatus;

        if (statusSelector) {
            return document.querySelector(statusSelector);
        }

        const field = button.closest('[data-voice-field], .voice-search-row, .st-voice-row, .voice-input-wrap');
        return field?.querySelector('[data-voice-status]') || null;
    }

    function setVoiceStatus(statusLabel, message = '', state = 'default') {
        if (!statusLabel) return;

        statusLabel.textContent = message;
        statusLabel.classList.remove('hidden', 'is-listening', 'is-success', 'is-error', 'is-default');

        if (!message) {
            statusLabel.classList.add('hidden');
            return;
        }

        statusLabel.classList.add(`is-${state}`);
    }

    function setMicPressed(button, value) {
        button.setAttribute('aria-pressed', value ? 'true' : 'false');
    }

    function resetMic(button) {
        if (!button) return;

        button.classList.remove('is-listening', 'mic-active', 'text-[#8B0000]');
        button.innerHTML = '<i class="fa-solid fa-microphone"></i>';
        setMicPressed(button, false);
    }

    function stopActiveController(options = {}) {
        if (!activeController) return;

        const current = activeController;
        activeController = null;

        try {
            current.recognition.stop();
        } catch (e) {}

        resetMic(current.button);

        if (!options.keepStatus) {
            setVoiceStatus(current.statusLabel, '');
        }
    }

    function normalizeSpaces(value) {
        return String(value || '').replace(/\s+/g, ' ').trim();
    }

    function joinText(baseText, spokenText) {
        const base = normalizeSpaces(baseText);
        const spoken = normalizeSpaces(spokenText);

        if (!base) return spoken;
        if (!spoken) return base;

        return `${base} ${spoken}`.trim();
    }

    function applyMaxLength(input, value) {
        const max = Number(input.getAttribute('maxlength') || input.dataset.wordLimit || 0);

        if (max > 0 && value.length > max) {
            return value.slice(0, max);
        }

        return value;
    }

    function writeTranscript(input, baseValue, spokenText) {
        const tag = input.tagName.toLowerCase();
        const shouldAppend = tag === 'textarea' || input.dataset.voiceAppend === 'true';

        let nextValue = shouldAppend
            ? joinText(baseValue, spokenText)
            : normalizeSpaces(spokenText);

        nextValue = applyMaxLength(input, nextValue);
        input.value = nextValue;

        input.dispatchEvent(new Event('input', { bubbles: true }));
        input.dispatchEvent(new Event('change', { bubbles: true }));
    }

    function initializeVoiceInputs(root = document) {
        const scope = root && typeof root.querySelectorAll === 'function' ? root : document;

        const buttons = scope.querySelectorAll(
            '.voice-search-mic.external[data-voice-trigger], .voice-search-mic.external[data-global-voice-trigger], [data-global-voice-trigger]'
        );

        buttons.forEach((button) => {
            if (button.dataset.voiceReady === 'true' || button.dataset.voiceInitialized === 'true') return;

            button.dataset.voiceReady = 'true';
            button.dataset.voiceInitialized = 'true';

            button.addEventListener('click', function (event) {
                event.preventDefault();
                event.stopPropagation();

                const input = getTargetInput(button);
                const statusLabel = getStatusLabel(button);

                if (!input) {
                    setVoiceStatus(statusLabel, 'No input found', 'error');
                    return;
                }

                if (!SpeechRecognition) {
                    setVoiceStatus(statusLabel, 'Voice not supported', 'error');
                    return;
                }

                if (activeController && activeController.button === button) {
                    stopActiveController();
                    return;
                }

                stopActiveController();

                const recognition = new SpeechRecognition();
                recognition.lang = input.dataset.voiceLang || button.dataset.voiceLang || 'en-US';
                recognition.continuous = false;
                recognition.interimResults = true;
                recognition.maxAlternatives = 1;

                const baseValue = input.value || '';
                const finalParts = [];
                const finalIndexes = new Set();

                recognition.onstart = () => {
                    button.classList.add('is-listening', 'mic-active', 'text-[#8B0000]');
                    button.innerHTML = '<i class="fa-solid fa-stop"></i>';
                    setMicPressed(button, true);
                    setVoiceStatus(statusLabel, 'Listening...', 'listening');
                };

                recognition.onresult = (event) => {
                    const interimParts = [];

                    for (let i = event.resultIndex; i < event.results.length; i++) {
                        const transcript = event.results[i][0]?.transcript?.trim() || '';

                        if (!transcript) continue;

                        if (event.results[i].isFinal) {
                            if (!finalIndexes.has(i)) {
                                finalIndexes.add(i);
                                finalParts.push(transcript);
                            }
                        } else {
                            interimParts.push(transcript);
                        }
                    }

                    const spokenText = normalizeSpaces([...finalParts, ...interimParts].join(' '));

                    if (!spokenText) return;

                    /*
                     * Important:
                     * Do not append to the input's current value on every interim result.
                     * Always rebuild the visible text from the original value captured at mic start.
                     * This prevents "double" or repeated dictation in modal textareas.
                     */
                    writeTranscript(input, baseValue, spokenText);
                };

                recognition.onerror = (event) => {
                    resetMic(button);

                    const error = event?.error || 'unknown';
                    const message = error === 'not-allowed'
                        ? 'Microphone blocked'
                        : 'Voice input failed';

                    setVoiceStatus(statusLabel, message, 'error');

                    setTimeout(() => {
                        setVoiceStatus(statusLabel, '');
                    }, 1800);

                    activeController = null;
                };

                recognition.onend = () => {
                    resetMic(button);

                    if (activeController?.recognition === recognition) {
                        activeController = null;
                    }

                    if (input.value.trim()) {
                        setVoiceStatus(statusLabel, 'Captured', 'success');

                        setTimeout(() => {
                            setVoiceStatus(statusLabel, '');
                        }, 1200);
                    } else {
                        setVoiceStatus(statusLabel, '');
                    }
                };

                activeController = {
                    recognition,
                    button,
                    input,
                    statusLabel,
                };

                try {
                    recognition.start();
                } catch (e) {
                    resetMic(button);
                    setVoiceStatus(statusLabel, 'Voice input failed', 'error');
                    activeController = null;
                }
            });
        });
    }

    window.initializeVoiceInputs = initializeVoiceInputs;
    window.initGlobalVoiceInputs = initializeVoiceInputs;
    window.stopGlobalVoiceInput = stopActiveController;

    document.addEventListener('DOMContentLoaded', () => {
        initializeVoiceInputs(document);
    });

    document.addEventListener('voice:refresh', (event) => {
        initializeVoiceInputs(event?.detail?.root || document);
    });

    document.addEventListener('modal:before-close', () => {
        stopActiveController();
    });

    document.addEventListener('visibilitychange', () => {
        if (document.hidden) stopActiveController();
    });
})();
</script>
