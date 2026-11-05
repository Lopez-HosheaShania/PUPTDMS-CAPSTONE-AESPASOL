@props(['message', 'checkboxId' => 'finalConfirm', 'heading' => 'Final check', 'icon' => 'fa-shield-halved'])

<div class="booking-final-confirmation">

    <div class="booking-confirmation-note">

        <span class="booking-confirmation-icon" aria-hidden="true">
            <i class="fa-solid {{ $icon }}"></i>
        </span>

        <div class="booking-confirmation-note-copy">

            <strong class="booking-confirmation-note-title">
                {{ $heading }}
            </strong>

            <p class="booking-confirmation-note-text">
                {{ $message }}
            </p>

        </div>

    </div>


    <div class="booking-confirmation-field" data-global-field>
        <label for="{{ $checkboxId }}" class="global-checkbox-row booking-confirmation-check">

            <input id="{{ $checkboxId }}" name="final_confirmation" value="1" type="checkbox" class="global-checkbox-input"
                data-field-label="Final confirmation" data-required-message="Please confirm this before continuing."
                required>

            <span class="global-checkbox-label booking-confirmation-check-text">
                {{ $slot }}
            </span>

        </label>
    </div>

</div>
