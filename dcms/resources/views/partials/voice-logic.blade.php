<script>
(() => {
    const SpeechRecognition = window.SpeechRecognition || window.webkitSpeechRecognition;

    function getJSVoice() {
        return window.JSVoice;
    }

    function normalizeVoiceError(error) {
        if (!error) return 'unknown';
        if (typeof error === 'string') return error;
        if (typeof error.error === 'string') return error.error;
        if (typeof error.code === 'string') return error.code;
        return 'unknown';
    }

    function createRecognizer(input, handlers) {
        const lang = input.dataset.voiceLang || 'en-US';
        const JSVoice = getJSVoice();

        if (JSVoice && (typeof JSVoice.isApiSupported === 'undefined' || JSVoice.isApiSupported)) {
            const voice = new JSVoice({
                lang,
                continuous: false,
                interimResults: false,
                autoRestart: false,
                commands: {},
                patternCommands: [],
                onCommandRecognized: (phrase, raw) => {
                    const transcript = (raw || phrase || '').trim();
                    if (transcript) handlers.onResult(transcript);
                },
                onCommandNotRecognized: (raw) => {
                    const transcript = (raw || '').trim();
                    if (transcript) handlers.onResult(transcript);
                },
                onMicrophonePermissionDenied: () => handlers.onError('not-allowed'),
                onError: (error) => handlers.onError(normalizeVoiceError(error)),
                onSpeechEnd: () => handlers.onEnd()
            });

            return {
                start: () => Promise.resolve(voice.start()),
                stop: () => {
                    voice.stop();
                }
            };
        }

        if (!SpeechRecognition) return null;

        const recognition = new SpeechRecognition();
        recognition.lang = lang;
        recognition.continuous = false;
        recognition.interimResults = true;
        recognition.maxAlternatives = 1;

        let processedFinalIndex = -1;

        recognition.onresult = (event) => {
            let transcript = '';
            let hasNewFinal = false;

            for (let i = event.resultIndex; i < event.results.length; i++) {
                const result = event.results[i];
                const chunk = result?.[0]?.transcript?.trim() || '';
                if (!chunk) continue;

                if (result.isFinal) {
                    if (i > processedFinalIndex) {
                        transcript += ` ${chunk}`;
                        processedFinalIndex = i;
                        hasNewFinal = true;
                    }
                } else if (!transcript) {
                    transcript = chunk;
                }
            }

            transcript = transcript.trim();

            if (hasNewFinal && transcript) {
                handlers.onResult(transcript);
            }
        };

        recognition.onerror = (event) => {
            handlers.onError(event?.error || 'unknown');
        };

        recognition.onend = () => {
            handlers.onEnd();
        };

        return {
            start: () => {
                recognition.start();
                return Promise.resolve();
            },
            stop: () => {
                recognition.stop();
            }
        };
    }

    let activeController = null;

    const selector = [
        'input:not([type]):not([readonly]):not([type="hidden"])',
        'input[type="text"]:not([readonly])',
        'input[type="email"]:not([readonly])',
        'input[type="tel"]:not([readonly])',
        'textarea:not([readonly])'
    ].join(', ');

    function initializeVoiceInputs(root = document) {
    const scope = root && typeof root.querySelectorAll === 'function' ? root : document;
    if (!SpeechRecognition && !getJSVoice()) {
        console.warn('Your browser does not support Voice Recognition.');
        return;
    }

    const inputs = scope.querySelectorAll(selector);

    inputs.forEach((input) => {
        if (input.classList.contains('no-voice')) return;
        if (input.id === 'relation_other' || input.id === 'emergency_relation') return;
        if (input.dataset.voiceReady === 'true') return;

        const isTextarea = input.tagName.toLowerCase() === 'textarea';
        const parent = input.parentNode;
        const isInsideSearchWrap = parent && parent.classList && parent.classList.contains('search-wrap');
        const isPatientDirectorySearch = input.id === 'patientSearch';

        let wrapper = input.closest('[data-voice-field]');
        let micBtn = wrapper?.querySelector('[data-voice-trigger]') || null;
        let statusLabel = wrapper?.querySelector('[data-voice-status]') || null;

        if (isInsideSearchWrap) {
            parent.classList.add('voice-search-wrap');
            if (!input.classList.contains('voice-search-input')) {
                input.classList.add('voice-search-input');
            }

            if (!statusLabel) {
                statusLabel = document.createElement('span');
                statusLabel.className = 'voice-status hidden';
                statusLabel.setAttribute('data-voice-status', '');
                parent.appendChild(statusLabel);
            }

            if (!micBtn) {
                micBtn = document.createElement('button');
                micBtn.type = 'button';
                micBtn.setAttribute('aria-label', 'Toggle voice input');
                micBtn.setAttribute('data-voice-trigger', '');
                micBtn.className = 'voice-mic-btn voice-search-mic';
                micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
                parent.appendChild(micBtn);
            }

            if (isPatientDirectorySearch) {
                parent.style.overflow = 'visible';
                Object.assign(micBtn.style, {
                    position: 'absolute',
                    right: '12px',
                    top: '50%',
                    transform: 'translateY(-50%)',
                    width: '18px',
                    height: '18px',
                    border: 'none',
                    background: 'transparent',
                    padding: '0',
                    margin: '0',
                    lineHeight: '1',
                    display: 'inline-flex',
                    alignItems: 'center',
                    justifyContent: 'center',
                    cursor: 'pointer',
                    zIndex: '4'
                });

                Object.assign(statusLabel.style, {
                    position: 'absolute',
                    right: '0',
                    top: 'calc(100% + 6px)',
                    display: 'inline-flex',
                    alignItems: 'center',
                    whiteSpace: 'nowrap',
                    fontSize: '.74rem',
                    fontWeight: '700',
                    lineHeight: '1',
                    padding: '.18rem .48rem',
                    borderRadius: '999px',
                    pointerEvents: 'none',
                    zIndex: '6'
                });
            }
        } else if (!wrapper) {
            wrapper = document.createElement('div');
            wrapper.className = 'voice-input-wrap';
            wrapper.setAttribute('data-voice-field', '');

            if (input.classList.contains('voice-full')) {
                wrapper.classList.add('is-full');
            } else if (input.classList.contains('voice-small')) {
                wrapper.classList.add('is-small');
            } else {
                wrapper.classList.add('is-medium');
            }

            const computedDisplay = window.getComputedStyle(input).display;
            if (computedDisplay === 'inline' || computedDisplay === 'inline-block') {
                wrapper.style.display = 'inline-block';
            } else {
                wrapper.style.display = 'block';
                wrapper.style.width = '100%';
                wrapper.style.maxWidth = '100%';
            }

            parent.insertBefore(wrapper, input);
            wrapper.appendChild(input);

            micBtn = document.createElement('button');
            micBtn.type = 'button';
            micBtn.setAttribute('aria-label', 'Toggle voice input');
            micBtn.setAttribute('data-voice-trigger', '');
            micBtn.className = 'voice-mic-btn';
            micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
            wrapper.appendChild(micBtn);

            statusLabel = document.createElement('span');
            statusLabel.className = 'voice-status hidden';
            statusLabel.setAttribute('data-voice-status', '');
            wrapper.appendChild(statusLabel);
        }

        input.style.width = '100%';
        input.style.maxWidth = '100%';
        input.style.boxSizing = 'border-box';

        if (input.tagName.toLowerCase() !== 'select') {
            input.classList.add('has-voice-padding');
        }

        if (isTextarea) {
            input.classList.add('is-voice-textarea');
        }

        input.dataset.voiceReady = 'true';

        let hasRecognizedSpeech = false;

        const recognizer = createRecognizer(input, {
            onResult: (transcript) => {
                hasRecognizedSpeech = true;
                if (isTextarea && input.value.trim()) {
                    input.value = `${input.value.trim()} ${transcript}`.trim();
                } else {
                    input.value = transcript;
                }

                input.dispatchEvent(new Event('input', { bubbles: true }));
                input.dispatchEvent(new Event('change', { bubbles: true }));

                showStatus('Voice captured.', 'success');
                hideStatus(1500);
            },
            onError: (errorCode) => {
                if (errorCode === 'no-speech') {
                    showStatus("Didn't catch that. Try again.", 'error');
                } else if (errorCode === 'not-allowed') {
                    showStatus('Microphone permission denied.', 'error');
                } else if (errorCode === 'aborted') {
                    statusLabel.classList.add('hidden');
                } else {
                    showStatus('Voice input stopped.', 'error');
                }
                hideStatus(2200);
            },
            onEnd: () => {
                resetVisualState();

                if (activeController === controller) {
                    activeController = null;
                }

                if (statusLabel.textContent === 'Listening...') {
                    if (!hasRecognizedSpeech) {
                        showStatus("Didn't catch that. Try again.", 'error');
                        hideStatus(1800);
                    } else {
                        statusLabel.classList.add('hidden');
                    }
                }
            }
        });

        if (!recognizer) {
            micBtn.disabled = true;
            micBtn.title = 'Voice input is not supported in this browser';
            input.dataset.voiceReady = 'unsupported';
            return;
        }

        const controller = {
            input,
            micBtn,
            statusLabel,
            recognizer
        };

        function enforcePatientSearchLayout() {
            if (!isPatientDirectorySearch || !parent || !statusLabel || !micBtn) return;

            parent.style.setProperty('position', 'relative', 'important');
            parent.style.setProperty('overflow', 'visible', 'important');

            input.style.setProperty('padding-right', '1.8rem', 'important');

            micBtn.style.setProperty('position', 'absolute', 'important');
            micBtn.style.setProperty('right', '12px', 'important');
            micBtn.style.setProperty('top', '50%', 'important');
            micBtn.style.setProperty('transform', 'translateY(-50%)', 'important');
            micBtn.style.setProperty('width', '18px', 'important');
            micBtn.style.setProperty('height', '18px', 'important');
            micBtn.style.setProperty('display', 'inline-flex', 'important');
            micBtn.style.setProperty('align-items', 'center', 'important');
            micBtn.style.setProperty('justify-content', 'center', 'important');
            micBtn.style.setProperty('z-index', '4', 'important');

            statusLabel.style.setProperty('position', 'absolute', 'important');
            statusLabel.style.setProperty('right', '0', 'important');
            statusLabel.style.setProperty('top', 'calc(100% + 6px)', 'important');
            statusLabel.style.setProperty('display', 'inline-flex', 'important');
            statusLabel.style.setProperty('align-items', 'center', 'important');
            statusLabel.style.setProperty('white-space', 'nowrap', 'important');
            statusLabel.style.setProperty('z-index', '6', 'important');
        }

        enforcePatientSearchLayout();

        function resetVisualState() {
            micBtn.classList.remove('is-listening', 'text-[#8B0000]');
            micBtn.innerHTML = '<i class="fas fa-microphone"></i>';
        }

        function showStatus(message, state = 'default') {
            enforcePatientSearchLayout();
            statusLabel.textContent = message;
            statusLabel.className = 'voice-status';

            if (state === 'listening') {
                statusLabel.classList.add('is-listening');
            } else if (state === 'error') {
                statusLabel.classList.add('is-error');
            } else if (state === 'success') {
                statusLabel.classList.add('is-success');
            } else {
                statusLabel.classList.add('is-default');
            }
        }

        function hideStatus(delay = 0) {
            setTimeout(() => statusLabel.classList.add('hidden'), delay);
        }

        micBtn.addEventListener('click', (e) => {
            e.preventDefault();
            e.stopPropagation();

            const isSameActive = activeController === controller;

            if (isSameActive) {
                recognizer.stop();
                return;
            }

            if (activeController) {
                try {
                    activeController.recognizer.stop();
                } catch (err) {}
            }

            activeController = controller;

            hasRecognizedSpeech = false;
            Promise.resolve(recognizer.start()).then(() => {
                micBtn.classList.add('is-listening', 'text-[#8B0000]');
                micBtn.innerHTML = '<span aria-hidden="true" style="display:block;width:10px;height:10px;border-radius:2px;background:currentColor;"></span>';
                showStatus('Listening...', 'listening');
            }).catch(() => {
                showStatus('Unable to start voice input.', 'error');
                hideStatus(2500);
                resetVisualState();
                activeController = null;
            });
        });
    });
    }

    window.initializeVoiceInputs = initializeVoiceInputs;

    document.addEventListener('DOMContentLoaded', () => {
        initializeVoiceInputs(document);
    });

    document.addEventListener('voice:refresh', (event) => {
        initializeVoiceInputs(event?.detail?.root || document);
    });
})();
</script>