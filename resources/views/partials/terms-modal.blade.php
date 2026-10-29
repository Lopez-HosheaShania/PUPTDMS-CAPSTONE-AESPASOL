<div id="termsModal" class="ui-modal" role="dialog" aria-modal="true" aria-hidden="true" aria-labelledby="termsModalTitle"
    aria-describedby="termsModalSubtitle" data-modal-static data-modal-no-initial-focus
    data-show-terms="{{ session('show_terms_modal') ? 'true' : 'false' }}">

    <div class="ui-modal-card modal-md">

        <header class="modal-hd">
            <div class="modal-heading">

                <div class="modal-icon" aria-hidden="true">
                    <i class="fa-solid fa-file-shield"></i>
                </div>

                <div class="modal-copy">
                    <h2 id="termsModalTitle" class="modal-title">
                        Terms and Conditions
                    </h2>

                    <p id="termsModalSubtitle" class="modal-subtitle">
                        Please read and accept before continuing
                    </p>
                </div>

            </div>
        </header>

        <div class="modal-bd modal-form-grid">

            <div class="modal-form-section">
                <p class="confirmed-modal-message">
                    By clicking <strong>"I Agree"</strong>, you consent to the
                    collection, use, and processing of your personal data for
                    legitimate purposes related to this service.
                </p>

                <br>

                <p class="confirmed-modal-message" data-ui-presentation="presentation-768bda7dd991">
                    Your information will be handled in accordance with our

                    <a href="https://www.pup.edu.ph/privacy/" target="_blank" rel="noopener noreferrer"
                        class="modal-link">
                        Privacy Policy
                    </a>

                    and in compliance with the
                    <strong>Data Privacy Act of 2012</strong>.
                </p>
            </div>

            <label class="global-checkbox-row">
                <input type="checkbox" id="termsCheckbox" class="global-checkbox-input" data-terms-checkbox>

                <span class="confirmed-modal-message">
                    I Agree and acknowledge the
                    <a href="https://www.pup.edu.ph/terms/" target="_blank" data-ui-click="action-22499e15f3ee"
                        data-ui-presentation="presentation-fd315d817385" rel="noopener noreferrer" class="modal-link">
                        Terms and Conditions
                    </a>
                </span>
            </label>

        </div>

        <footer class="modal-ft">

            <form method="POST" action="{{ route('logout') }}">
                @csrf

                <button type="submit" class="ui-btn ui-btn-secondary">
                    Cancel
                </button>
            </form>

            <button type="button" id="termsContinueBtn" class="ui-btn ui-btn-primary" disabled data-terms-continue>
                <span>Continue</span>

            </button>

        </footer>

    </div>

</div>
