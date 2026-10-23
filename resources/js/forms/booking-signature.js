function escapeSignatureHtml(value = '') {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function createBookingSignature(root) {
    let editingExistingSignature = false;

    if (
        !root ||
        root.dataset.bookingSignatureInitialized ===
        'true'
    ) {
        return root?.__bookingSignature || null;
    }

    const mode =
        root.dataset.signatureMode ||
        'patient';

    const validationUrl =
        root.dataset.signatureValidationUrl ||
        '';

    const drawnFilePrefix =
        root.dataset.signatureDrawnPrefix ||
        'drawn-signature';

    const sigInput =
        root.querySelector(
            '#patient_signature, [data-signature-input]'
        );

    const signatureSourceInput =
        root.querySelector(
            '#signature_source, [data-signature-source]'
        );

    const sigName =
        root.querySelector(
            '#signature_filename, [data-signature-filename]'
        );

    const sigError =
        root.querySelector(
            '#signature_error, [data-signature-message]'
        );

    const sigResultBox =
        root.querySelector(
            '#signature_result_box, [data-signature-result]'
        );

    const signatureUploadTitle =
        root.querySelector(
            '[data-signature-upload-title]'
        );

    const defaultSignatureUploadTitle =
        signatureUploadTitle?.dataset.defaultTitle ||
        'Select your file or drag and drop';

    const signatureCanvas =
        root.querySelector(
            '#signatureCanvas, [data-signature-canvas]'
        );

    const signatureCtx =
        signatureCanvas?.getContext('2d');

    const signatureUndoBtn =
        root.querySelector(
            '#signatureUndoBtn, [data-signature-undo]'
        );

    const signatureRedoBtn =
        root.querySelector(
            '#signatureRedoBtn, [data-signature-redo]'
        );

    const signatureClearBtn =
        root.querySelector(
            '#signatureClearBtn, [data-signature-clear]'
        );

    const signatureUseDrawnBtn =
        root.querySelector(
            '#signatureUseDrawnBtn, [data-signature-use-drawn]'
        );

    const signatureDrawCard =
        root.querySelector(
            '[data-signature-draw-card]'
        );

    const signatureExpandBtn =
        root.querySelector(
            '[data-signature-expand]'
        );

    const signatureModal =
        root.querySelector(
            '[data-signature-modal]'
        );

    const signatureModalBody =
        root.querySelector(
            '[data-signature-modal-body]'
        );

    const signatureModalCloseBtn =
        root.querySelector(
            '[data-signature-modal-close]'
        );

    const signatureModalError =
        root.querySelector(
            '[data-signature-modal-error]'
        );

    const signatureModalErrorText =
        root.querySelector(
            '[data-signature-modal-error-text]'
        );

    const uploadTabBtn =
        root.querySelector(
            '#uploadTabBtn, [data-signature-tab="upload"]'
        );

    const drawTabBtn =
        root.querySelector(
            '#drawTabBtn, [data-signature-tab="draw"]'
        );

    const uploadPanel =
        root.querySelector(
            '#uploadPanel, [data-signature-panel="upload"]'
        );

    const drawPanel =
        root.querySelector(
            '#drawPanel, [data-signature-panel="draw"]'
        );

    let signatureAiValid =
        mode === 'draw-only';

    let signatureAiChecking =
        false;

    let drawnSignatureStrokes =
        [];

    let drawnSignatureRedoStrokes =
        [];

    let drawnSignatureCurrentStroke =
        [];

    let drawnSignatureIsDrawing =
        false;

    let drawnSignatureWasUsed =
        false;

    let signatureExpanded =
        false;

    let signatureOriginalParent =
        null;

    let signatureOriginalNextSibling =
        null;

    const acceptedMessage =
        root.dataset.signatureAcceptedMessage ||
        'Signature verified and accepted';

    const declinedMessage =
        root.dataset.signatureDeclinedMessage ||
        'Signature could not be processed. Please try again.';

    function updateSignatureUploadTitle(
        fileName = ''
    ) {
        if (!signatureUploadTitle) {
            return;
        }

        signatureUploadTitle.textContent =
            fileName || defaultSignatureUploadTitle;

        signatureUploadTitle.title =
            fileName || '';
    }

    function clearSignatureDisplay() {
        updateSignatureUploadTitle();

        sigResultBox?.classList.add(
            'hidden'
        );

        if (sigName) {
            sigName.textContent = '';

            sigName.classList.remove(
                'hidden',
                'text-emerald-700',
                'text-red-600'
            );
        }

        if (sigError) {
            sigError.textContent = '';

            sigError.classList.add(
                'hidden'
            );

            sigError.classList.remove(
                'signature-status-success',
                'signature-status-error',
                'signature-status-neutral',
                'text-emerald-700',
                'text-red-600'
            );

            sigError.style.removeProperty(
                'color'
            );

            sigError.style.removeProperty(
                'font-weight'
            );
        }

        window.clearGlobalGroupError?.(
            root,
            'patient_signature'
        );

        clearExpandedSignatureError();
    }

    function resetDrawnSignatureState() {
        drawnSignatureStrokes =
            [];

        drawnSignatureRedoStrokes =
            [];

        drawnSignatureCurrentStroke =
            [];

        drawnSignatureIsDrawing =
            false;

        drawnSignatureWasUsed =
            false;

        redrawSignatureCanvas();
        syncHistoryButtons();
    }

    function clearUploadedSignatureState(
        options = {}
    ) {
        const {
            preserveStatus = false,
            resetUploadTitle = true,
        } = options;

        signatureAiValid = false;
        signatureAiChecking = false;

        if (sigInput) {
            sigInput.value = '';
        }

        if (
            signatureSourceInput
        ) {
            signatureSourceInput.value =
                '';
        }

        if (resetUploadTitle) {
            updateSignatureUploadTitle();
        }

        if (!preserveStatus) {
            clearSignatureDisplay();
        }
    }

    function showSignatureStatus(
        fileName = '',
        message = '',
        type = 'neutral'
    ) {
        if (
            type === 'success' &&
            fileName
        ) {
            updateSignatureUploadTitle(
                fileName
            );
        }

        sigResultBox?.classList.remove(
            'hidden'
        );

        if (sigName) {
            sigName.textContent = '';
            sigName.classList.add('hidden');
        }

        if (sigError) {
            sigError.textContent =
                message || '';

            sigError.classList.remove(
                'hidden'
            );

            sigError.classList.remove(
                'signature-status-success',
                'signature-status-error',
                'signature-status-neutral',
                'text-emerald-700',
                'text-red-600'
            );

            sigError.style.removeProperty(
                'color'
            );

            sigError.style.removeProperty(
                'font-weight'
            );

            if (type === 'success') {
                sigError.classList.add(
                    'signature-status-success'
                );

                sigError.innerHTML = `
        <span class="signature-success-icon">
            <i class="fa-solid fa-check"></i>
        </span>

        <span>
            ${escapeSignatureHtml(message || '')}
        </span>
    `;

                sigError.style.setProperty(
                    'color',
                    '#15803d',
                    'important'
                );

                sigError.style.setProperty(
                    'font-weight',
                    '700',
                    'important'
                );
            }

            else if (type === 'error') {
                sigError.classList.add(
                    'signature-status-error'
                );

                sigError.style.setProperty(
                    'color',
                    '#b91c1c',
                    'important'
                );

                sigError.style.setProperty(
                    'font-weight',
                    '700',
                    'important'
                );
            }
            else {
                sigError.classList.add(
                    'signature-status-neutral'
                );

                sigError.style.setProperty(
                    'color',
                    '#6b7280',
                    'important'
                );

                sigError.style.setProperty(
                    'font-weight',
                    '600',
                    'important'
                );
            }
        }
    }
    function showSignatureError(
        fileName = '',
        result = {}
    ) {
        signatureAiValid = false;
        signatureAiChecking = false;

        const reason =
            result.reason ||
            declinedMessage;

        updateSignatureUploadTitle();
        sigResultBox?.classList.add(
            'hidden'
        );

        if (sigName) {
            sigName.textContent = '';
            sigName.classList.add(
                'hidden'
            );
        }

        if (sigError) {
            sigError.textContent =
                reason;

            sigError.classList.remove(
                'hidden'
            );

            sigError.classList.remove(
                'signature-status-success',
                'signature-status-error',
                'signature-status-neutral',
                'text-emerald-700',
                'text-red-600'
            );

            sigError.classList.add(
                'signature-status-error'
            );

            sigError.style.setProperty(
                'color',
                '#b91c1c',
                'important'
            );

            sigError.style.setProperty(
                'font-weight',
                '700',
                'important'
            );
        }

        window.showGlobalGroupError?.(
            root,
            'patient_signature',
            reason
        );

        showExpandedSignatureError(
            reason
        );

        if (sigInput) {
            sigInput.value = '';
        }

        if (signatureSourceInput) {
            signatureSourceInput.value = '';
        }

    }

    function isDrawnSignatureFile(
        file
    ) {
        if (!file?.name) {
            return false;
        }

        return (
            file.name.startsWith(
                'drawn-signature-'
            ) ||
            file.name.startsWith(
                'walk-in-signature-'
            ) ||
            file.name.startsWith(
                `${drawnFilePrefix}-`
            )
        );
    }

    async function validateSignatureWithAi(
        file
    ) {
        if (!validationUrl) {
            return {
                valid: true,
                accepted: true,
            };
        }

        const formData =
            new FormData();

        formData.append(
            'patient_signature',
            file
        );

        const csrfToken =
            document
                .querySelector(
                    'meta[name="csrf-token"]'
                )
                ?.getAttribute(
                    'content'
                ) || '';

        const response =
            await fetch(
                validationUrl,
                {
                    method: 'POST',

                    headers: {
                        'X-CSRF-TOKEN':
                            csrfToken,

                        Accept:
                            'application/json',
                    },

                    body:
                        formData,
                }
            );

        const data =
            await response
                .json()
                .catch(() => ({}));

        if (
            !response.ok ||
            data.valid === false ||
            data.accepted === false
        ) {
            const error =
                new Error(
                    data.message ||
                    declinedMessage
                );

            error.data = data;

            throw error;
        }

        return data;
    }

    async function handleSignatureFile() {
        const file =
            sigInput?.files?.[0];

        signatureAiValid = false;
        signatureAiChecking = false;

        if (!file) {
            if (
                signatureSourceInput
            ) {
                signatureSourceInput.value =
                    '';
            }

            clearSignatureDisplay();
            return;
        }

        const isDrawn =
            isDrawnSignatureFile(
                file
            );

        if (
            signatureSourceInput
        ) {
            signatureSourceInput.value =
                isDrawn
                    ? 'drawn'
                    : 'upload';
        }

        if (isDrawn) {
            drawnSignatureWasUsed =
                true;
        } else {
            resetDrawnSignatureState();
        }

        const allowedTypes = [
            'image/jpeg',
            'image/png',
        ];

        const maxSize =
            25 * 1024 * 1024;

        if (
            !allowedTypes.includes(
                file.type
            )
        ) {
            showSignatureError(
                file.name,
                {
                    reason:
                        'Signature must be a JPG or PNG file.',
                    detected_type:
                        'invalid_file_type',
                    confidence: 0,
                }
            );

            return;
        }

        if (
            file.size >
            maxSize
        ) {
            showSignatureError(
                file.name,
                {
                    reason:
                        'Signature file must not exceed 25 MB.',
                    detected_type:
                        'file_too_large',
                    confidence: 0,
                }
            );

            return;
        }

        if (isDrawn) {
            signatureAiValid =
                true;

            signatureAiChecking =
                false;

            showSignatureStatus(
                '',
                'Drawn signature accepted.',
                'success'
            );

            window.clearGlobalGroupError?.(
                root,
                'patient_signature'
            );

            root.dispatchEvent(
                new CustomEvent(
                    'booking-signature:ready',
                    {
                        bubbles: true,
                        detail: {
                            file,
                            source:
                                'drawn',
                        },
                    }
                )
            );

            return;
        }

        if (mode === 'draw-only') {
            return;
        }

        const image =
            new Image();

        const objectUrl =
            URL.createObjectURL(
                file
            );

        image.onload =
            async function () {
                const {
                    width,
                    height,
                } = image;

                URL.revokeObjectURL(
                    objectUrl
                );

                if (
                    width < 120 ||
                    height < 60
                ) {
                    showSignatureError(
                        file.name,
                        {
                            reason:
                                'Signature image is too small. Please upload a clearer signature.',
                            detected_type:
                                'image_too_small',
                            confidence:
                                0,
                        }
                    );

                    return;
                }

                if (
                    width > 5000 ||
                    height > 5000
                ) {
                    showSignatureError(
                        file.name,
                        {
                            reason:
                                'Signature image is too large. Please upload a smaller image.',
                            detected_type:
                                'image_too_large',
                            confidence:
                                0,
                        }
                    );

                    return;
                }

                try {
                    signatureAiChecking =
                        true;

                    showSignatureStatus(
                        file.name,
                        'Checking signature image...',
                        'neutral'
                    );

                    await validateSignatureWithAi(
                        file
                    );

                    signatureAiValid =
                        true;

                    signatureAiChecking =
                        false;

                    showSignatureStatus(
                        file.name,
                        acceptedMessage,
                        'success'
                    );

                    window.clearGlobalGroupError?.(
                        root,
                        'patient_signature'
                    );

                    root.dispatchEvent(
                        new CustomEvent(
                            'booking-signature:ready',
                            {
                                bubbles:
                                    true,

                                detail: {
                                    file,
                                    source:
                                        'upload',
                                },
                            }
                        )
                    );
                } catch (
                error
                ) {
                    showSignatureError(
                        file.name,
                        error.data || {
                            reason:
                                error.message ||
                                declinedMessage,
                            detected_type:
                                'unknown',
                            confidence:
                                0,
                        }
                    );
                }
            };

        image.onerror =
            function () {
                URL.revokeObjectURL(
                    objectUrl
                );

                showSignatureError(
                    file.name,
                    {
                        reason:
                            'Invalid signature image file.',
                        detected_type:
                            'invalid_image',
                        confidence:
                            0,
                    }
                );
            };

        image.src =
            objectUrl;
    }

    function paintSignatureCanvasBackground() {
        if (
            !signatureCanvas ||
            !signatureCtx
        ) {
            return;
        }

        signatureCtx.save();

        signatureCtx.setTransform(
            1,
            0,
            0,
            1,
            0,
            0
        );

        signatureCtx.clearRect(
            0,
            0,
            signatureCanvas.width,
            signatureCanvas.height
        );

        signatureCtx.fillStyle =
            '#ffffff';

        signatureCtx.fillRect(
            0,
            0,
            signatureCanvas.width,
            signatureCanvas.height
        );

        signatureCtx.restore();
    }

    function drawSignatureStroke(
        stroke
    ) {
        if (
            !signatureCtx ||
            !stroke?.length
        ) {
            return;
        }

        signatureCtx.lineWidth =
            3.2;

        signatureCtx.lineCap =
            'round';

        signatureCtx.lineJoin =
            'round';

        signatureCtx.strokeStyle =
            '#111827';

        if (
            stroke.length === 1
        ) {
            signatureCtx.beginPath();

            signatureCtx.arc(
                stroke[0].x,
                stroke[0].y,
                1.8,
                0,
                Math.PI * 2
            );

            signatureCtx.fillStyle =
                '#111827';

            signatureCtx.fill();
            return;
        }

        signatureCtx.beginPath();

        signatureCtx.moveTo(
            stroke[0].x,
            stroke[0].y
        );

        for (
            let index = 1;
            index <
            stroke.length - 1;
            index++
        ) {
            const midX =
                (
                    stroke[index].x +
                    stroke[index + 1].x
                ) / 2;

            const midY =
                (
                    stroke[index].y +
                    stroke[index + 1].y
                ) / 2;

            signatureCtx
                .quadraticCurveTo(
                    stroke[index].x,
                    stroke[index].y,
                    midX,
                    midY
                );
        }

        const last =
            stroke[
            stroke.length - 1
            ];

        signatureCtx.lineTo(
            last.x,
            last.y
        );

        signatureCtx.stroke();
    }

    function redrawSignatureCanvas() {
        if (
            !signatureCanvas ||
            !signatureCtx
        ) {
            return;
        }

        paintSignatureCanvasBackground();

        drawnSignatureStrokes
            .forEach(
                drawSignatureStroke
            );

        if (
            drawnSignatureCurrentStroke
                .length
        ) {
            drawSignatureStroke(
                drawnSignatureCurrentStroke
            );
        }
    }

    function resize() {
        if (
            !signatureCanvas ||
            !signatureCtx
        ) {
            return;
        }

        const rect =
            signatureCanvas
                .getBoundingClientRect();

        if (!rect.width) {
            return;
        }

        const cssWidth =
            rect.width;

        const cssHeight =
            signatureCanvas.offsetHeight ||
            rect.height ||
            220;

        const previousWidth =
            Number(
                signatureCanvas.dataset
                    .signatureCssWidth || 0
            );

        const previousHeight =
            Number(
                signatureCanvas.dataset
                    .signatureCssHeight || 0
            );

        if (
            previousWidth > 0 &&
            previousHeight > 0 &&
            (
                Math.abs(previousWidth - cssWidth) > 0.5 ||
                Math.abs(previousHeight - cssHeight) > 0.5
            )
        ) {
            const scaleX =
                cssWidth /
                previousWidth;

            const scaleY =
                cssHeight /
                previousHeight;

            const scaleStroke =
                stroke => {
                    stroke.forEach(
                        point => {
                            point.x *= scaleX;
                            point.y *= scaleY;
                        }
                    );
                };

            drawnSignatureStrokes
                .forEach(scaleStroke);

            drawnSignatureRedoStrokes
                .forEach(scaleStroke);

            if (
                drawnSignatureCurrentStroke
                    .length
            ) {
                scaleStroke(
                    drawnSignatureCurrentStroke
                );
            }
        }

        signatureCanvas.dataset
            .signatureCssWidth =
            String(cssWidth);

        signatureCanvas.dataset
            .signatureCssHeight =
            String(cssHeight);

        const dpr =
            Math.max(
                window.devicePixelRatio ||
                1,
                1
            );

        signatureCanvas.width =
            Math.round(
                cssWidth * dpr
            );

        signatureCanvas.height =
            Math.round(
                cssHeight * dpr
            );

        signatureCtx.setTransform(
            dpr,
            0,
            0,
            dpr,
            0,
            0
        );

        redrawSignatureCanvas();
    }

    function getSignaturePoint(
        event
    ) {
        const rect =
            signatureCanvas
                .getBoundingClientRect();

        return {
            x:
                event.clientX -
                rect.left,

            y:
                event.clientY -
                rect.top,
        };
    }

    function isBlank() {
        return (
            drawnSignatureStrokes
                .length === 0 &&
            drawnSignatureCurrentStroke
                .length === 0
        );
    }

    function syncHistoryButtons() {
        if (signatureUndoBtn) {
            signatureUndoBtn.disabled =
                drawnSignatureStrokes
                    .length === 0;
        }

        if (signatureRedoBtn) {
            signatureRedoBtn.disabled =
                drawnSignatureRedoStrokes
                    .length === 0;
        }
    }

    function showExpandedSignatureError(
        message
    ) {
        if (
            !signatureExpanded ||
            !signatureModalError
        ) {
            return;
        }

        if (signatureModalErrorText) {
            signatureModalErrorText.textContent =
                message || '';
        }

        signatureModalError.classList.remove(
            'hidden'
        );

        signatureModalError.classList.add(
            'show'
        );
    }

    function clearExpandedSignatureError() {
        if (!signatureModalError) {
            return;
        }

        if (signatureModalErrorText) {
            signatureModalErrorText.textContent =
                '';
        }

        signatureModalError.classList.add(
            'hidden'
        );

        signatureModalError.classList.remove(
            'show'
        );
    }

    function openExpandedSignature() {
        if (
            !signatureModal ||
            !signatureModalBody ||
            !signatureDrawCard ||
            signatureExpanded
        ) {
            return;
        }

        signatureExpanded =
            true;

        signatureOriginalParent =
            signatureDrawCard.parentNode;

        signatureOriginalNextSibling =
            signatureDrawCard.nextSibling;


        /*
         * Keep global modal outside booking containers
         * so it cannot be clipped by the workflow card.
         */
        if (
            signatureModal.parentNode !==
            document.body
        ) {
            document.body.appendChild(
                signatureModal
            );
        }


        /*
         * Keep the local error underneath the drawing card.
         */
        if (
            signatureModalError &&
            signatureModalError.parentNode ===
            signatureModalBody
        ) {
            signatureModalBody.insertBefore(
                signatureDrawCard,
                signatureModalError
            );
        } else {
            signatureModalBody.appendChild(
                signatureDrawCard
            );
        }


        signatureModal.classList.remove(
            'closing'
        );

        signatureModal.classList.add(
            'open'
        );

        signatureModal.setAttribute(
            'aria-hidden',
            'false'
        );


        window.requestAnimationFrame(
            () => {
                window.requestAnimationFrame(
                    () => {
                        resize();

                        signatureCanvas
                            ?.focus?.();
                    }
                );
            }
        );
    }

    function closeExpandedSignature() {
        if (!signatureExpanded) {
            return;
        }

        signatureExpanded =
            false;


        if (
            signatureOriginalParent &&
            signatureDrawCard
        ) {
            if (
                signatureOriginalNextSibling &&
                signatureOriginalNextSibling
                    .parentNode ===
                signatureOriginalParent
            ) {
                signatureOriginalParent
                    .insertBefore(
                        signatureDrawCard,
                        signatureOriginalNextSibling
                    );
            } else {
                signatureOriginalParent
                    .appendChild(
                        signatureDrawCard
                    );
            }
        }


        signatureModal?.classList.remove(
            'open'
        );

        signatureModal?.classList.remove(
            'closing'
        );

        signatureModal?.setAttribute(
            'aria-hidden',
            'true'
        );


        clearExpandedSignatureError();


        signatureOriginalParent =
            null;

        signatureOriginalNextSibling =
            null;


        window.requestAnimationFrame(
            resize
        );
    }

    function invalidateAfterEdit() {
        if (
            !drawnSignatureWasUsed
        ) {
            return;
        }

        drawnSignatureWasUsed =
            false;

        signatureAiValid =
            false;

        if (sigInput) {
            sigInput.value = '';
        }

        if (
            signatureSourceInput
        ) {
            signatureSourceInput.value =
                '';
        }

        showSignatureStatus(
            '',
            'Signature changed. Click Use Drawn Signature again before proceeding.',
            'neutral'
        );
    }

    function startDrawing(
        event
    ) {
        if (!signatureCanvas) {
            return;
        }

        if (
            event.pointerType ===
            'mouse' &&
            event.button !== 0
        ) {
            return;
        }

        if (
            signatureSourceInput
                ?.value ===
            'upload' &&
            sigInput?.files?.length
        ) {
            clearUploadedSignatureState({
                preserveStatus: true,
            });
        }

        resize();

        event.preventDefault();

        clearSignatureDisplay();

        drawnSignatureRedoStrokes =
            [];

        syncHistoryButtons();

        drawnSignatureIsDrawing =
            true;

        drawnSignatureCurrentStroke =
            [
                getSignaturePoint(
                    event
                ),
            ];

        signatureCanvas
            .setPointerCapture?.(
                event.pointerId
            );

        redrawSignatureCanvas();
    }

    function moveDrawing(
        event
    ) {
        if (
            !drawnSignatureIsDrawing
        ) {
            return;
        }

        event.preventDefault();

        drawnSignatureCurrentStroke.push(
            getSignaturePoint(
                event
            )
        );

        redrawSignatureCanvas();
    }

    function endDrawing(
        event
    ) {
        if (
            !drawnSignatureIsDrawing
        ) {
            return;
        }

        event.preventDefault();

        if (
            drawnSignatureCurrentStroke
                .length
        ) {
            drawnSignatureStrokes.push(
                drawnSignatureCurrentStroke
            );
        }

        drawnSignatureCurrentStroke =
            [];

        drawnSignatureIsDrawing =
            false;

        invalidateAfterEdit();

        redrawSignatureCanvas();

        syncHistoryButtons();

        root.dispatchEvent(
            new CustomEvent(
                'booking-signature:changed',
                {
                    bubbles: true,
                }
            )
        );
    }

    function clear() {
        resetDrawnSignatureState();

        clearUploadedSignatureState({
            preserveStatus: true,
        });

        clearSignatureDisplay();

        syncHistoryButtons();

        root.dispatchEvent(
            new CustomEvent(
                'booking-signature:changed',
                {
                    bubbles: true,
                }
            )
        );
    }


    function undo() {
        if (
            !drawnSignatureStrokes
                .length
        ) {
            return;
        }

        const removedStroke =
            drawnSignatureStrokes.pop();

        if (removedStroke) {
            drawnSignatureRedoStrokes.push(
                removedStroke
            );
        }

        redrawSignatureCanvas();
        invalidateAfterEdit();
        syncHistoryButtons();

        if (isBlank()) {
            drawnSignatureWasUsed =
                false;

            signatureAiValid =
                false;

            if (sigInput) {
                sigInput.value =
                    '';
            }

            if (
                signatureSourceInput
            ) {
                signatureSourceInput.value =
                    '';
            }

            clearSignatureDisplay();
        }

        root.dispatchEvent(
            new CustomEvent(
                'booking-signature:changed',
                {
                    bubbles: true,
                }
            )
        );
    }

    function redo() {
        if (
            !drawnSignatureRedoStrokes
                .length
        ) {
            return;
        }

        const restoredStroke =
            drawnSignatureRedoStrokes.pop();

        if (restoredStroke) {
            drawnSignatureStrokes.push(
                restoredStroke
            );
        }

        redrawSignatureCanvas();
        invalidateAfterEdit();
        syncHistoryButtons();

        root.dispatchEvent(
            new CustomEvent(
                'booking-signature:changed',
                {
                    bubbles: true,
                }
            )
        );
    }

    function attachDrawnSignatureToFileInput(
        file
    ) {
        if (!sigInput) {
            return false;
        }

        if (
            typeof DataTransfer ===
            'undefined'
        ) {
            showSignatureError(
                '',
                {
                    reason:
                        'Your browser cannot attach the drawn signature.',
                    detected_type:
                        'browser_unsupported',
                    confidence:
                        0,
                }
            );

            return false;
        }

        const dataTransfer =
            new DataTransfer();

        dataTransfer.items.add(
            file
        );

        sigInput.files =
            dataTransfer.files;

        return true;
    }

    function cropCanvasToSignature() {

        if (!signatureCanvas) {
            return null;
        }

        const ctx =
            signatureCanvas.getContext('2d');

        const width =
            signatureCanvas.width;

        const height =
            signatureCanvas.height;

        const imageData =
            ctx.getImageData(
                0,
                0,
                width,
                height
            );

        const data =
            imageData.data;


        let minX = width;
        let minY = height;
        let maxX = 0;
        let maxY = 0;


        for (
            let y = 0;
            y < height;
            y++
        ) {

            for (
                let x = 0;
                x < width;
                x++
            ) {

                const index =
                    (y * width + x) * 4;


                const alpha =
                    data[index + 3];


                const r =
                    data[index];

                const g =
                    data[index + 1];

                const b =
                    data[index + 2];


                // ignore white background
                if (
                    alpha > 0 &&
                    (
                        r < 240 ||
                        g < 240 ||
                        b < 240
                    )
                ) {

                    minX =
                        Math.min(
                            minX,
                            x
                        );

                    minY =
                        Math.min(
                            minY,
                            y
                        );

                    maxX =
                        Math.max(
                            maxX,
                            x
                        );

                    maxY =
                        Math.max(
                            maxY,
                            y
                        );
                }
            }
        }


        if (
            minX >= maxX ||
            minY >= maxY
        ) {
            return null;
        }


        const padding = 20;


        const crop =
            document.createElement(
                'canvas'
            );


        crop.width =
            maxX - minX + padding * 2;

        crop.height =
            maxY - minY + padding * 2;


        const cropCtx =
            crop.getContext('2d');


        cropCtx.fillStyle =
            '#ffffff';

        cropCtx.fillRect(
            0,
            0,
            crop.width,
            crop.height
        );


        cropCtx.drawImage(
            signatureCanvas,
            minX,
            minY,
            maxX - minX,
            maxY - minY,
            padding,
            padding,
            maxX - minX,
            maxY - minY
        );


        return crop;
    }

    function useDrawn() {
        if (!signatureCanvas) {
            return;
        }

        if (isBlank()) {
            const message =
                'Please draw your signature first.';

            window.showGlobalGroupError?.(
                root,
                'patient_signature',
                message
            );

            showExpandedSignatureError(
                message
            );

            return;
        }

        redrawSignatureCanvas();

        const croppedCanvas = cropCanvasToSignature();

        if (!croppedCanvas) {
            const message =
                'Please draw your signature first.';

            window.showGlobalGroupError?.(
                root,
                'patient_signature',
                message
            );

            showExpandedSignatureError(
                message
            );

            return;
        }

        croppedCanvas.toBlob(
            blob => {
                if (!blob) {
                    showSignatureError(
                        '',
                        {
                            reason:
                                'Unable to process drawn signature. Please try again.',
                            detected_type:
                                'canvas_error',
                            confidence:
                                0,
                        }
                    );

                    return;
                }

                const file =
                    new File(
                        [blob],
                        `${drawnFilePrefix}-${Date.now()}.png`,
                        {
                            type:
                                'image/png',

                            lastModified:
                                Date.now(),
                        }
                    );

                if (
                    !attachDrawnSignatureToFileInput(
                        file
                    )
                ) {
                    return;
                }

                drawnSignatureWasUsed =
                    true;

                signatureAiValid =
                    true;

                if (
                    signatureSourceInput
                ) {
                    signatureSourceInput.value =
                        'drawn';
                }

                sigInput.dispatchEvent(
                    new Event(
                        'change',
                        {
                            bubbles: true,
                        }
                    )
                );

                clearExpandedSignatureError();

                if (signatureExpanded) {
                    closeExpandedSignature();
                }
                
            },
            'image/png',
            0.95
        );
    }

    function setMode(
        nextMode
    ) {
        const isUpload =
            nextMode === 'upload';

        uploadPanel?.classList.toggle(
            'hidden',
            !isUpload
        );

        drawPanel?.classList.toggle(
            'hidden',
            isUpload
        );

        uploadTabBtn?.classList.toggle(
            'is-active',
            isUpload
        );

        drawTabBtn?.classList.toggle(
            'is-active',
            !isUpload
        );

        uploadTabBtn?.setAttribute(
            'aria-pressed',
            isUpload
                ? 'true'
                : 'false'
        );

        drawTabBtn?.setAttribute(
            'aria-pressed',
            isUpload
                ? 'false'
                : 'true'
        );

        if (!isUpload) {
            window.setTimeout(
                resize,
                60
            );
        }
    }

    function isReady() {

        if (
            root.dataset.hasExistingSignature === 'true'
            &&
            !editingExistingSignature
            &&
            !sigInput?.files?.length
        ) {
            return true;
        }


        if (signatureAiChecking) {
            return false;
        }


        if (!sigInput?.files?.length) {
            return false;
        }


        if (
            signatureSourceInput?.value === 'drawn'
            &&
            !drawnSignatureWasUsed
        ) {
            return false;
        }


        return signatureAiValid;
    }

    function validate() {
        if (isReady()) {
            window.clearGlobalGroupError?.(
                root,
                'patient_signature'
            );

            return true;
        }

        let message =
            'Please provide a valid signature.';

        if (
            signatureAiChecking
        ) {
            message =
                'Please wait while the signature is being checked.';
        } else if (
            !sigInput?.files?.length
        ) {
            message =
                'Please provide your signature.';
        } else if (
            !drawnSignatureWasUsed &&
            signatureSourceInput
                ?.value ===
            'drawn'
        ) {
            message =
                'Please click Use Drawn Signature before proceeding.';
        }

        window.showGlobalGroupError?.(
            root,
            'patient_signature',
            message
        );

        return false;
    }

    sigInput?.addEventListener(
        'change',
        handleSignatureFile
    );

    signatureCanvas?.addEventListener(
        'pointerdown',
        startDrawing
    );

    signatureCanvas?.addEventListener(
        'pointermove',
        moveDrawing
    );

    signatureCanvas?.addEventListener(
        'pointerup',
        endDrawing
    );

    signatureCanvas?.addEventListener(
        'pointercancel',
        endDrawing
    );

    signatureCanvas?.addEventListener(
        'pointerleave',
        endDrawing
    );

    signatureUndoBtn?.addEventListener(
        'click',
        undo
    );

    signatureRedoBtn?.addEventListener(
        'click',
        redo
    );

    signatureClearBtn?.addEventListener(
        'click',
        clear
    );

    signatureUseDrawnBtn?.addEventListener(
        'click',
        useDrawn
    );

    signatureExpandBtn?.addEventListener(
        'click',
        openExpandedSignature
    );

    signatureModalCloseBtn?.addEventListener(
        'click',
        closeExpandedSignature
    );

    signatureModal?.addEventListener(
        'click',
        event => {
            if (
                event.target ===
                signatureModal
            ) {
                closeExpandedSignature();
            }
        }
    );

    document.addEventListener(
        'keydown',
        event => {
            if (
                event.key === 'Escape' &&
                signatureExpanded
            ) {
                closeExpandedSignature();
            }
        }
    );

    uploadTabBtn?.addEventListener(
        'click',
        () =>
            setMode(
                'upload'
            )
    );

    drawTabBtn?.addEventListener(
        'click',
        () =>
            setMode(
                'draw'
            )
    );

    const editExistingSignatureBtn =
        root.querySelector(
            '#editExistingSignatureBtn'
        );

    const signatureEditorWrapper =
        root.querySelector(
            '#signatureEditorWrapper'
        );

    const reuseExistingInput =
        root.querySelector(
            '#reuse_existing_signature'
        );

    const existingSignatureCard =
        root.querySelector(
            '[data-existing-signature-card]'
        );

    const existingSignaturePreview =
        root.querySelector(
            '[data-existing-signature-preview]'
        );

    const existingSignatureImage =
        root.querySelector(
            '[data-existing-signature-image]'
        );

    editExistingSignatureBtn?.addEventListener(
        'click',
        editExisting
    );

    function setExistingSignature({
        reusable = false,
        url = '',
    } = {}) {
        editingExistingSignature =
            false;

        root.dataset.hasExistingSignature =
            reusable ? 'true' : 'false';

        if (reuseExistingInput) {
            reuseExistingInput.value =
                reusable ? '1' : '0';
        }

        existingSignatureCard
            ?.classList.toggle(
                'hidden',
                !reusable
            );

        signatureEditorWrapper
            ?.classList.toggle(
                'hidden',
                reusable
            );

        if (existingSignatureImage) {
            existingSignatureImage.src =
                url || '';
        }

        existingSignaturePreview
            ?.classList.toggle(
                'hidden',
                !url
            );

        if (reusable) {
            clearUploadedSignatureState();

            window.clearGlobalGroupError?.(
                root,
                'patient_signature'
            );
        }

        window.setTimeout(
            resize,
            80
        );
    }

    function editExisting() {
        if (
            root.dataset
                .hasExistingSignature !==
            'true'
        ) {
            return;
        }

        editingExistingSignature = true;

        root.dataset.hasExistingSignature =
            'false';

        if (reuseExistingInput) {
            reuseExistingInput.value = '0';
        }

        existingSignatureCard
            ?.classList.add(
                'hidden'
            );

        signatureEditorWrapper
            ?.classList.remove(
                'hidden'
            );

        sigInput?.removeAttribute(
            'disabled'
        );

        setMode('draw');

        window.setTimeout(
            resize,
            100
        );

        root.dispatchEvent(
            new CustomEvent(
                'booking-signature:editing',
                {
                    bubbles: true,
                }
            )
        );
    }

    const controller = {
        root,
        resize,
        clear,
        undo,
        redo,
        useDrawn,
        openExpandedSignature,
        closeExpandedSignature,
        isBlank,
        isReady,
        validate,
        setExistingSignature,
        editExisting,

        isChecking() {
            return signatureAiChecking;
        },

        getFile() {
            return (
                sigInput
                    ?.files?.[0] ||
                null
            );
        },
    };

    root.dataset.bookingSignatureInitialized =
        'true';

    root.__bookingSignature =
        controller;

    if (uploadPanel) {
        setMode('upload');
    }

    window.setTimeout(
        resize,
        250
    );

    syncHistoryButtons();
    return controller;
}

function initBookingSignatures(
    root = document
) {
    const scope =
        root &&
            typeof root.querySelectorAll ===
            'function'
            ? root
            : document;

    const components = [];

    if (
        scope.matches?.(
            '[data-booking-signature]'
        )
    ) {
        components.push(
            scope
        );
    }

    scope
        .querySelectorAll?.(
            '[data-booking-signature]'
        )
        .forEach(
            element => {
                components.push(
                    element
                );
            }
        );

    components.forEach(
        createBookingSignature
    );
}

function getBookingSignature(
    source = document
) {
    const root =
        source?.matches?.(
            '[data-booking-signature]'
        )
            ? source
            : source?.querySelector?.(
                '[data-booking-signature]'
            ) ||
            document.querySelector(
                '[data-booking-signature]'
            );

    return (
        root?.__bookingSignature ||
        null
    );
}

function bootBookingSignatures() {
    initBookingSignatures();
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        bootBookingSignatures,
        {
            once: true,
        }
    );
} else {
    bootBookingSignatures();
}

window.addEventListener(
    'resize',
    () => {
        document
            .querySelectorAll(
                '[data-booking-signature]'
            )
            .forEach(
                root => {
                    root
                        .__bookingSignature
                        ?.resize();
                }
            );
    }
);

window.BookingSignature = {
    init:
        initBookingSignatures,

    get:
        getBookingSignature,
};
