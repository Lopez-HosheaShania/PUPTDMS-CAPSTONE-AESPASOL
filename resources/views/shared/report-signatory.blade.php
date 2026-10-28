@if (\App\Services\ReportSignatory::canManage(auth()->user()))
@php
    $signatory = app(\App\Services\ReportSignatory::class)->ownProfile();
    $isPreparerSignatory = \App\Services\ReportSignatory::isPreparer(auth()->user());
    $preparerLabel = \App\Services\ReportSignatory::preparerLabel(auth()->user());
@endphp
<div id="reportSignatoryModal" class="ui-modal modal-theme-primary" role="dialog" aria-modal="true"
    aria-hidden="true" aria-labelledby="reportSignatoryTitle" aria-describedby="reportSignatorySubtitle">
    <div class="ui-modal-card modal-md">
        <form action="{{ route('reports.signatory.update') }}" method="POST" enctype="multipart/form-data"
            class="modal-card-form" data-discard-form data-discard-title="Discard signatory changes?"
            data-discard-subtitle="Your report signatory has not been saved."
            data-discard-message="Your changes to the signatory details will be removed. Do you want to discard these changes?">
            @csrf
            @method('PUT')
            <div class="modal-hd">
                <div class="modal-heading">
                    <span class="modal-icon" aria-hidden="true"><i class="fa-solid fa-signature"></i></span>
                    <div class="modal-copy">
                        <h2 id="reportSignatoryTitle" class="modal-title">{{ $isPreparerSignatory ? $preparerLabel . ' signatory' : 'Report signatory' }}</h2>
                        <p id="reportSignatorySubtitle" class="modal-subtitle">{{ $isPreparerSignatory ? 'Set your name and signature for the Prepared by section of reports.' : 'Set the dentist details printed on your report PDFs.' }}</p>
                    </div>
                </div>
            </div>
            <div class="modal-bd modal-scroll-body">
                <div class="modal-form-grid">
                    @if ($errors->reportSignatory->any())
                    <div role="alert" class="modal-error-banner">
                        <i class="fa-solid fa-triangle-exclamation" aria-hidden="true"></i>
                        <ul>
                            @foreach ($errors->reportSignatory->all() as $error)<li>{{ $error }}</li>@endforeach
                        </ul>
                    </div>
                    @endif
                    <div class="{{ $isPreparerSignatory ? 'modal-form-grid' : 'modal-form-grid-2' }}">
                        <div class="modal-field">
                            <label for="signatoryName" class="global-form-label">{{ $isPreparerSignatory ? $preparerLabel . ' name' : 'Dentist name' }} <span class="required-mark">*</span></label>
                            <input id="signatoryName" name="signatory_name" type="text" maxlength="120" required class="form-input-custom" data-modal-initial-focus value="{{ old('signatory_name', $signatory?->name ?? auth()->user()->name) }}">
                        </div>
                        @if (!$isPreparerSignatory)
                        <div class="modal-field">
                            <label for="signatoryLicense" class="global-form-label">License number <span class="required-mark">*</span></label>
                            <input id="signatoryLicense" name="license_number" type="text" maxlength="60" required class="form-input-custom" value="{{ old('license_number', $signatory?->license_number) }}">
                        </div>
                        @endif
                    </div>
                    <div class="modal-field">
                        <label for="signatorySignature" class="global-form-label">Signature image @if (!$signatory)<span class="required-mark">*</span>@endif</label>
                        <div class="report-signature-upload">
                            <input id="signatorySignature" name="signature" type="file" accept="image/png,image/jpeg" @required(!$signatory) aria-describedby="signatureFileStatus signatureUploadError">
                            <span class="report-signature-upload-icon" aria-hidden="true"><i class="fa-solid fa-arrow-up-from-bracket"></i></span>
                            <div class="report-signature-upload-copy">
                                <strong>Upload your signature</strong>
                                <span>PNG or JPG <span aria-hidden="true">&middot;</span> Maximum 2 MB</span>
                            </div>
                            <span class="report-signature-upload-action" aria-hidden="true">Browse files</span>
                        </div>
                        <p id="signatureFileStatus" class="report-signature-file-status" role="status" hidden></p>
                        <p id="signatureUploadError" class="report-signature-error" role="alert" hidden></p>
                    </div>
                    <div id="signaturePreview" class="report-signature-preview" @if (!$signatory?->signaturePath()) hidden @endif>
                        <div class="report-signature-preview-heading">
                            <span id="signaturePreviewLabel">Saved signature</span>
                            <button type="button" id="clearSignatureSelection" hidden>Clear selection</button>
                        </div>
                        <div class="report-signature-preview-image">
                            <img id="signaturePreviewImage" @if ($signatory?->signaturePath()) src="{{ $signatory->signatureDataUri() }}" @endif alt="Report signature preview">
                        </div>
                    </div>
                </div>
            </div>
            <div class="modal-ft">
                <button type="button" class="btn-close-modal" data-discard-close="reportSignatoryModal">Cancel</button>
                <button type="submit" class="modal-btn-confirm primary"><i class="fa-solid fa-check" aria-hidden="true"></i> Save</button>
            </div>
        </form>
    </div>
</div>
@push('scripts')
<script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
    document.addEventListener('DOMContentLoaded', () => {
        const input = document.getElementById('signatorySignature');
        if (!input) return;
        const preview = document.getElementById('signaturePreview');
        const image = document.getElementById('signaturePreviewImage');
        const label = document.getElementById('signaturePreviewLabel');
        const status = document.getElementById('signatureFileStatus');
        const error = document.getElementById('signatureUploadError');
        const clear = document.getElementById('clearSignatureSelection');
        const savedSource = image.getAttribute('src');
        let objectUrl;

        const showSelection = () => {
            if (objectUrl) URL.revokeObjectURL(objectUrl);
            objectUrl = null;
            input.setCustomValidity('');
            input.removeAttribute('aria-invalid');
            error.hidden = true;
            image.onerror = null;
            if (savedSource) image.src = savedSource;
            else image.removeAttribute('src');
            preview.hidden = !savedSource;
            label.textContent = 'Saved signature';
            clear.hidden = true;
            const file = input.files[0];
            status.textContent = file ? file.name : '';
            status.hidden = !file;
            if (!file) return;

            const reject = (message) => {
                input.setCustomValidity(message);
                input.setAttribute('aria-invalid', 'true');
                error.textContent = message;
                error.hidden = false;
            };
            if (!['image/png', 'image/jpeg'].includes(file.type)) {
                reject('Please choose a PNG or JPG image.');
                return;
            }
            if (file.size > 2 * 1024 * 1024) {
                reject('The signature image must be 2 MB or smaller.');
                return;
            }
            image.onerror = () => {
                preview.hidden = true;
                reject('This image could not be opened. Please choose another file.');
            };
            objectUrl = URL.createObjectURL(file);
            image.src = objectUrl;
            preview.hidden = false;
            label.textContent = 'Selected signature';
            clear.hidden = false;
        };
        input.addEventListener('change', showSelection);
        clear.addEventListener('click', () => {
            input.value = '';
            input.dispatchEvent(new Event('change', { bubbles: true }));
            input.focus();
        });
        input.form.addEventListener('reset', () => requestAnimationFrame(showSelection));
        @if ($errors->reportSignatory->any())
        window.openModal?.('reportSignatoryModal');
        @endif
        @if (session('report_signatory_saved'))
        window.showToast?.('success', @json(session('report_signatory_saved')));
        @endif
    });
</script>
@endpush
@endif
