@props([
    'mode' => 'patient',
    'label' => "Patient's Signature",
    'drawTitle' => null,
    'drawHelp' => null,
    'hasExistingSignature' => false,
    'existingSignatureUrl' => null,
])

@php
    $isDrawOnly = $mode === 'draw-only';
    $isReadOnly = $mode === 'read-only';

    $resolvedDrawTitle =
        $drawTitle ?? ($isDrawOnly ? "Draw the patient's signature here" : 'Or draw your signature here');

    $resolvedDrawHelp = 'Draw the signature using a mouse, finger, or stylus.';
@endphp


<div class="booking-section-card signature-booking-section-card" data-global-field data-booking-signature
    data-signature-mode="{{ $mode }}" data-has-existing-signature="{{ $hasExistingSignature ? 'true' : 'false' }}"
    data-signature-validation-url="{{ $mode === 'patient' ? route('book.appointment.validate-signature') : '' }}"
    data-signature-drawn-prefix="{{ $isDrawOnly ? 'walk-in-signature' : 'drawn-signature' }}">

    <div class="signature-full-row">

        <div class="signature-existing-card {{ $hasExistingSignature ? '' : 'hidden' }}" data-existing-signature-card>

            <div class="signature-existing-header">
                <i class="fa-solid fa-circle-check"></i>

                <div>
                    <p class="signature-existing-title">
                        Existing signature on file
                    </p>
                </div>
            </div>

            <div class="signature-existing-preview {{ $existingSignatureUrl ? '' : 'hidden' }}"
                data-existing-signature-preview>
                <img src="{{ $existingSignatureUrl ?? '' }}" alt="Existing signature" data-existing-signature-image>
            </div>

            <p class="signature-existing-help">
                @if ($isReadOnly)
                    The patient's previously saved signature is shown for reference and cannot be edited here.
                @else
                    Your previously verified signature will be reused.
                @endif
            </p>

            @if ($isReadOnly)
                <span class="booking-summary-view-only">
                    <i class="fa-regular fa-eye"></i>
                    <span>View Only</span>
                </span>
            @else
                <button type="button" class="ui-btn ui-btn-secondary ui-btn-sm" id="editExistingSignatureBtn">
                    <i class="fa-solid fa-pen"></i>
                    Edit Signature
                </button>
            @endif

        </div>

        @if (!$isReadOnly)
            <input type="hidden" name="reuse_existing_signature" id="reuse_existing_signature"
                value="{{ $hasExistingSignature ? '1' : '0' }}">
        @endif

        @if (!$isReadOnly)

            <div id="signatureEditorWrapper" class="{{ $hasExistingSignature ? 'hidden' : '' }}">

                <label class="global-form-label" for="patient_signature">
                    {{ $label }}
                    <span class="required-mark">
                        *
                    </span>
                </label>

                @if ($isDrawOnly)
                    <input type="file" name="patient_signature" id="patient_signature" class="hidden"
                        accept=".png,image/png">

                    <input type="hidden" name="signature_source" id="signature_source" value="drawn">

                    <div class="signature-methods-grid signature-drawing-only">

                        <div class="signature-draw-card" data-signature-draw-card>

                            <div class="signature-draw-header">
                                <p class="signature-draw-title">
                                    {{ $resolvedDrawTitle }}
                                </p>
                            </div>

                            <div class="signature-pad-wrap">

                                <button type="button" class="ui-icon-btn neutral signature-expand-btn"
                                    data-signature-expand data-tooltip="Expand signature pad"
                                    data-tooltip-tone="neutral" aria-label="Expand signature pad">

                                    <i class="fa-solid fa-expand" aria-hidden="true"></i>

                                </button>

                                <canvas id="signatureCanvas" class="signature-pad-canvas">
                                </canvas>

                            </div>

                            <div class="signature-pad-footer">

                                <span class="signature-pad-help">
                                    {{ $resolvedDrawHelp }}
                                    Once finished, click <strong>Use Signature</strong> to continue.
                                </span>

                                <div class="signature-pad-actions">

                                    <button type="button" id="signatureUndoBtn"
                                        class="ui-btn ui-btn-secondary ui-btn-sm" data-signature-undo
                                        data-tooltip="Undo" data-tooltip-tone="neutral"
                                        aria-label="Undo signature stroke">

                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                        <span class="signature-action-label">Undo</span>

                                    </button>

                                    <button type="button" id="signatureRedoBtn"
                                        class="ui-btn ui-btn-secondary ui-btn-sm" data-signature-redo
                                        data-tooltip="Redo" data-tooltip-tone="neutral"
                                        aria-label="Redo signature stroke">

                                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                        <span class="signature-action-label">Redo</span>

                                    </button>

                                    <button type="button" id="signatureClearBtn"
                                        class="ui-btn ui-btn-secondary ui-btn-sm" data-signature-clear
                                        data-tooltip="Clear" data-tooltip-tone="neutral" aria-label="Clear signature">

                                        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                                        <span class="signature-action-label">Clear</span>

                                    </button>

                                    <button type="button" id="signatureUseDrawnBtn"
                                        class="ui-btn ui-btn-primary ui-btn-sm" data-signature-use-drawn>
                                        <span>Use Signature</span>
                                    </button>

                                </div>

                            </div>
                        </div>
                    </div>
                @else
                    <div class="signature-methods-grid">

                        <div class="file-upload-zone signature-upload-card">

                            <p class="signature-method-title">
                                Upload your signature
                            </p>

                            <div class="signature-upload-content">

                                <span class="signature-upload-icon">
                                    <i class="fa-regular fa-image"></i>
                                </span>

                                <p class="signature-upload-title" data-signature-upload-title
                                    data-default-title="Select your file or drag and drop">
                                    Select your file or drag and drop
                                </p>

                                <small class="signature-upload-help">
                                    JPG, PNG, up to 25 MB
                                </small>

                                <label class="ui-btn ui-btn-primary ui-btn-sm cursor-pointer">
                                    <i class="fa-solid fa-upload"></i>
                                    Browse
                                    <input type="file" name="patient_signature" id="patient_signature" class="hidden"
                                        accept=".jpg,.jpeg,.png">
                                    <input type="hidden" name="signature_source" id="signature_source"
                                        value="">
                                </label>

                            </div>
                        </div>

                        <div class="signature-draw-card" data-signature-draw-card>

                            <div class="signature-draw-header">
                                <p class="signature-draw-title">
                                    {{ $resolvedDrawTitle }}
                                </p>
                            </div>

                            <div class="signature-pad-wrap">

                                <button type="button" class="ui-icon-btn neutral signature-expand-btn"
                                    data-signature-expand data-tooltip="Expand signature pad"
                                    data-tooltip-tone="neutral" aria-label="Expand signature pad">

                                    <i class="fa-solid fa-expand" aria-hidden="true"></i>

                                </button>

                                <canvas id="signatureCanvas" class="signature-pad-canvas">
                                </canvas>

                            </div>

                            <div class="signature-pad-footer">

                                <span class="signature-pad-help">
                                    {{ $resolvedDrawHelp }}
                                    Once finished, click <strong>Use Signature</strong> to continue.
                                </span>

                                <div class="signature-pad-actions">

                                    <button type="button" id="signatureUndoBtn"
                                        class="ui-btn ui-btn-secondary ui-btn-sm" data-signature-undo
                                        data-tooltip="Undo" data-tooltip-tone="neutral"
                                        aria-label="Undo signature stroke">

                                        <i class="fa-solid fa-rotate-left" aria-hidden="true"></i>
                                        <span class="signature-action-label">Undo</span>

                                    </button>

                                    <button type="button" id="signatureRedoBtn"
                                        class="ui-btn ui-btn-secondary ui-btn-sm" data-signature-redo
                                        data-tooltip="Redo" data-tooltip-tone="neutral"
                                        aria-label="Redo signature stroke">

                                        <i class="fa-solid fa-rotate-right" aria-hidden="true"></i>
                                        <span class="signature-action-label">Redo</span>

                                    </button>

                                    <button type="button" id="signatureClearBtn"
                                        class="ui-btn ui-btn-secondary ui-btn-sm" data-signature-clear
                                        data-tooltip="Clear" data-tooltip-tone="neutral"
                                        aria-label="Clear signature">

                                        <i class="fa-solid fa-eraser" aria-hidden="true"></i>
                                        <span class="signature-action-label">Clear</span>

                                    </button>

                                    <button type="button" id="signatureUseDrawnBtn"
                                        class="ui-btn ui-btn-primary ui-btn-sm" data-signature-use-drawn>
                                        <span>Use Signature</span>
                                    </button>

                                </div>

                            </div>
                        </div>
                    </div>
                @endif

                <div class="ui-modal" data-signature-modal role="dialog" aria-modal="true"
                    aria-labelledby="signatureExpandedTitle" aria-hidden="true">

                    <div class="ui-modal-card modal-preview-card" data-signature-modal-dialog>

                        <div class="modal-hd">

                            <div class="modal-heading">

                                <span class="modal-icon">
                                    <i class="fa-solid fa-signature"></i>
                                </span>

                                <div class="modal-copy">

                                    <h3 id="signatureExpandedTitle" class="modal-title">
                                        Draw Your Signature
                                    </h3>

                                    <p class="modal-subtitle">
                                        {{ $resolvedDrawHelp }}
                                        Once finished, click <strong>Use Signature</strong> to continue.
                                    </p>

                                </div>

                            </div>

                            <button type="button" class="modal-x" data-signature-modal-close
                                aria-label="Close expanded signature pad" title="Close">

                                <i class="fa-solid fa-xmark"></i>

                            </button>

                        </div>


                        <div class="modal-bd modal-preview-body" data-signature-modal-body>

                            {{-- Modal-local validation message --}}
                            <p class="global-field-error hidden" data-signature-modal-error>

                                <i class="fa-solid fa-circle-exclamation"></i>

                                <span data-signature-modal-error-text></span>

                            </p>

                        </div>

                    </div>
                </div>

                <div id="signature_result_box" class="signature-result-box hidden">
                    <p id="signature_filename"></p>
                    <div id="signature_error" class="signature-result-message hidden">
                    </div>
                </div>

            </div>
        @endif
        @error('patient_signature')
            <p class="global-field-error show">
                <i class="fa-solid fa-circle-exclamation"></i>
                {{ $message }}
            </p>
        @enderror

    </div>
</div>
