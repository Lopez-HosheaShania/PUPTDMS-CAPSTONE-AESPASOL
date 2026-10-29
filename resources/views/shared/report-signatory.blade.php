@if (\App\Services\ReportSignatory::canManage(auth()->user()))
    @php
        $signatory = app(\App\Services\ReportSignatory::class)->ownProfile();
        $isDentistSignatory = \App\Services\AssignedDentistService::isDentist(auth()->user());
        $isPreparerSignatory = \App\Services\ReportSignatory::isPreparer(auth()->user());
        $preparerLabel = \App\Services\ReportSignatory::preparerLabel(auth()->user());
    @endphp
    <div id="reportSignatoryModal" class="ui-modal modal-theme-primary" role="dialog" aria-modal="true" aria-hidden="true"
        aria-labelledby="reportSignatoryTitle" aria-describedby="reportSignatorySubtitle">
        <div class="ui-modal-card modal-md">
            <form action="{{ route('reports.signatory.update') }}" method="POST" enctype="multipart/form-data"
                class="modal-card-form" data-global-validation data-discard-form
                data-signatory-validation-url="{{ route('reports.signatory.validate-signature') }}"
                data-signatory-has-errors="{{ $errors->reportSignatory->any() ? 'true' : 'false' }}"
                data-signatory-saved="{{ session('report_signatory_saved') }}"
                data-discard-title="Discard signatory changes?"
                data-discard-subtitle="Your report signatory has not been saved."
                data-discard-message="Your changes to the signatory details will be removed. Do you want to discard these changes?">
                @csrf
                @method('PUT')
                <input type="hidden" name="signature_source" id="reportSignatureSource" value="upload">
                <div class="modal-hd">
                    <div class="modal-heading">
                        <span class="modal-icon" aria-hidden="true"><i class="fa-solid fa-signature"></i></span>
                        <div class="modal-copy">
                            <h2 id="reportSignatoryTitle" class="modal-title">
                                {{ $isPreparerSignatory ? $preparerLabel . ' signatory' : 'Report signatory' }}</h2>
                            <p id="reportSignatorySubtitle" class="modal-subtitle">
                                {{ $isPreparerSignatory ? 'Set your name and signature for the Prepared by section of reports.' : 'Set the dentist details printed on your report PDFs.' }}
                            </p>
                        </div>
                    </div>
                </div>
                <div class="modal-bd modal-scroll-body">
                    <div class="modal-form-grid">
                        @if ($errors->reportSignatory->any())
                            <div role="alert" class="modal-error-banner">
                                <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                                <ul>
                                    @foreach ($errors->reportSignatory->all() as $error)
                                        <li>{{ $error }}</li>
                                    @endforeach
                                </ul>
                            </div>
                        @endif
                        <div class="{{ $isDentistSignatory ? 'modal-form-grid-2' : 'modal-form-grid' }}">
                            <div class="modal-field" data-global-field>
                                <label for="signatoryName"
                                    class="global-form-label">{{ $isDentistSignatory ? 'Dentist name' : $preparerLabel . ' name' }}
                                    <span class="required-mark">*</span></label>
                                <input id="signatoryName" name="signatory_name" type="text" maxlength="120"
                                    pattern="[\p{L}\p{M}]+(?: +[\p{L}\p{M}]+)*"
                                    data-pattern-message="Name must contain letters and spaces only." required
                                    class="form-input-custom" data-modal-initial-focus
                                    value="{{ old('signatory_name', $signatory?->name ?? auth()->user()->name) }}">
                            </div>
                            @if ($isDentistSignatory)
                                <div class="modal-field" data-global-field>
                                    <label for="signatoryLicense" class="global-form-label">License number <span
                                            class="required-mark">*</span></label>
                                    <input id="signatoryLicense" name="license_number" type="text" minlength="6"
                                        maxlength="7" inputmode="numeric" pattern="[0-9]{6,7}"
                                        data-minlength-message="License number is incomplete. Please enter at least 6 digits."
                                        data-maxlength-message="License number must not exceed 7 digits."
                                        data-pattern-message="License number must contain digits (0-9) only, with no special characters."
                                        required class="form-input-custom"
                                        value="{{ old('license_number', $signatory?->license_number) }}">
                                </div>
                            @endif
                        </div>
                        <div class="modal-field modal-form-grid" data-global-field>
                            <label for="signatorySignature" class="global-form-label">Signature image @if (!$signatory)
                                    <span class="required-mark">*</span>
                                @endif
                            </label>
                            <div class="report-signature-upload">
                                <input id="signatorySignature" name="signature" type="file"
                                    accept="image/png,image/jpeg" @required(!$signatory)
                                    aria-describedby="signatureFileStatus signatureUploadError">
                                <span class="report-signature-upload-icon" aria-hidden="true"><i
                                        class="fa-solid fa-arrow-up-from-bracket"></i></span>
                                <div class="report-signature-upload-copy">
                                    <strong>Upload your signature</strong>
                                    <span>PNG or JPG <span aria-hidden="true">&middot;</span> Maximum 2 MB</span>
                                </div>
                                <span class="report-signature-upload-action" aria-hidden="true">Browse files</span>
                            </div>
                            <details data-report-signature-drawing>
                                <summary class="ui-btn ui-btn-secondary ui-btn-sm">
                                    <i class="fa-solid fa-pen" aria-hidden="true"></i> Draw signature
                                </summary>
                                <x-booking.signature mode="draw-only" label="Draw your signature"
                                    drawn-file-prefix="drawn-signature" draw-title="Draw your signature here"
                                    :input-name="false" input-id="reportDrawnSignature" :required="false"
                                    :show-result="false" />
                            </details>
                            <div id="signaturePreview" class="report-signature-preview"
                                @if (!$signatory?->signaturePath()) hidden @endif>
                                <div class="report-signature-preview-heading">
                                    <span id="signaturePreviewLabel">Saved signature</span>
                                    <button type="button" id="clearSignatureSelection" hidden>Clear selection</button>
                                </div>
                                <div class="report-signature-preview-image">
                                    <img id="signaturePreviewImage"
                                        @if ($signatory?->signaturePath()) src="{{ $signatory->signatureDataUri() }}" @endif
                                        alt="Report signature preview">
                                </div>
                            </div>
                            <p id="signatureFileStatus" class="report-signature-file-status" role="status" hidden>
                            </p>
                            <p id="signatureUploadError" class="global-field-error"
                                data-error-for="signatorySignature" role="alert" aria-hidden="true"></p>
                        </div>
                    </div>
                </div>
                <div class="modal-ft">
                    <button type="button" class="ui-btn ui-btn-secondary" data-discard-form
                        data-discard-close="reportSignatoryModal">Cancel</button>
                    <button type="submit" class="ui-btn ui-btn-primary">
                        <span data-signatory-save-label>Save</span>
                    </button>
                </div>
            </form>
        </div>
    </div>
@endif
