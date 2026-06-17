<dialog id="termsModal" data-show-terms="{{ session('show_terms_modal') ? 'true' : 'false' }}">
    <div class="terms-header">
        <div class="terms-header-icon"><i class="fa-solid fa-file-shield"></i></div>
        <div>
            <h2>Terms and Conditions</h2>
            <p>Please read and accept before continuing</p>
        </div>
    </div>
    <div class="terms-body">
        <p>
            By clicking <strong>"I Agree"</strong>, you consent to the collection, use, and
            processing of your personal data for legitimate purposes related to this service.
        </p>
        <p style="margin-bottom:0;">
            Your information will be handled in accordance with our <strong>Privacy Policy</strong>
            and in compliance with the <strong>Data Privacy Act of 2012</strong>.
        </p>
        <div class="terms-divider"></div>

        <label class="terms-checkbox-row">
            <input type="checkbox" id="termsCheckbox" data-terms-checkbox>
            <span>
                I Agree and acknowledge the
                <a href="https://www.pup.edu.ph/terms/" target="_blank" onclick="event.stopPropagation()"
                    style="color: #8B0000; text-decoration: underline; font-weight: 700;">
                    Terms and Conditions
                </a>
            </span>
        </label>
        <div class="terms-actions">
            <form method="POST" action="{{ route('logout') }}" style="margin:0;">
                @csrf
                <button type="submit" class="terms-cancel-btn">Cancel</button>
            </form>
            <button type="button" id="termsContinueBtn" class="terms-continue-btn" disabled data-terms-continue>
                <i class="fa-solid fa-check" style="font-size:.75rem; margin-right:5px;"></i> Continue
            </button>
        </div>
    </div>
</dialog>
