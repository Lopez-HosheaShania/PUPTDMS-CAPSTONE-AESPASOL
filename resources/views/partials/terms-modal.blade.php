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
        <p data-ui-presentation="presentation-768bda7dd991">
            Your information will be handled in accordance with our <strong>Privacy Policy</strong>
            and in compliance with the <strong>Data Privacy Act of 2012</strong>.
        </p>
        <div class="terms-divider"></div>

        <label class="terms-checkbox-row">
            <input type="checkbox" id="termsCheckbox" data-terms-checkbox>
            <span>
                I Agree and acknowledge the
                <a href="https://www.pup.edu.ph/terms/" target="_blank" data-ui-click="action-22499e15f3ee"
                    data-ui-presentation="presentation-fd315d817385">
                    Terms and Conditions
                </a>
            </span>
        </label>
        <div class="terms-actions">
            <form method="POST" action="{{ route('logout') }}" data-ui-presentation="presentation-38965f9b18ca">
                @csrf
                <button type="submit" class="terms-cancel-btn">Cancel</button>
            </form>
            <button type="button" id="termsContinueBtn" class="terms-continue-btn" disabled data-terms-continue>
                <i class="fa-solid fa-check" data-ui-presentation="presentation-0d3f386975b5"></i> Continue
            </button>
        </div>
    </div>
</dialog>
