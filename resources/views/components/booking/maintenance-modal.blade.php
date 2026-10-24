<div id="bookingMaintenanceModal" class="ui-modal" role="dialog" aria-modal="true"
    aria-hidden="true" aria-labelledby="bookingMaintenanceTitle" aria-describedby="bookingMaintenanceMessage">
    <div class="ui-modal-card modal-md">
        <header class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon" aria-hidden="true">
                    <i class="fa-solid fa-calendar-xmark"></i>
                </div>
                <div class="modal-copy">
                    <h2 id="bookingMaintenanceTitle" class="modal-title" tabindex="-1" data-modal-initial-focus>
                        Clinic Temporarily Unavailable
                    </h2>
                    <p class="modal-subtitle">Appointment booking is temporarily paused</p>
                </div>
            </div>
        </header>

        <div class="modal-bd modal-form-grid">
            <div class="global-confirm-alert">
                <p id="bookingMaintenanceMessage">The clinic system is temporarily unavailable right now. Appointment booking is temporarily disabled while we perform system maintenance. Please try again later.</p>
            </div>

            <section class="modal-form-section" aria-labelledby="bookingMaintenanceActions">
                <div class="modal-section-heading">
                    <h3 id="bookingMaintenanceActions" class="text-xs font-bold">What you can do</h3>
                </div>

                <ul class="m-0 list-none space-y-3 p-0">
                    <li class="flex items-start gap-3">
                        <div class="modal-section-icon" aria-hidden="true">
                            <i class="fa-solid fa-house"></i>
                        </div>
                        <p class="m-0 pt-2 text-xs font-medium leading-relaxed">
                            Continue using your dashboard and viewing your existing appointments.
                        </p>
                    </li>
                    <li class="flex items-start gap-3">
                        <div class="modal-section-icon" aria-hidden="true">
                            <i class="fa-regular fa-calendar-check"></i>
                        </div>
                        <p class="m-0 pt-2 text-xs font-medium leading-relaxed">
                            Try booking again later, once system maintenance is complete.
                        </p>
                    </li>
                </ul>
            </section>

            <p class="modal-helper-text">Closing this message will keep you on your current page.</p>
        </div>

        <footer class="modal-ft">
            <button type="button" class="modal-btn-primary" data-modal-close="bookingMaintenanceModal">
                <i class="fa-solid fa-check" aria-hidden="true"></i>
                <span>Okay, Got It</span>
            </button>
        </footer>
    </div>
</div>
