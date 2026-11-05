<div id="activeAppointmentModal" class="ui-modal modal-theme-warning" role="dialog" aria-modal="true" aria-hidden="true"
    aria-labelledby="activeAppointmentModalTitle" aria-describedby="activeAppointmentModalDescription">
    <div class="ui-modal-card modal-sm">
        <div class="modal-hd">
            <div class="modal-heading">
                <div class="modal-icon">
                    <i class="fa-solid fa-calendar-xmark"></i>
                </div>

                <div class="modal-copy">
                    <h2 id="activeAppointmentModalTitle" class="modal-title">
                        Active Appointment
                    </h2>
                </div>
            </div>

            <button type="button" class="modal-x" data-modal-close="activeAppointmentModal">
                <i class="fa-solid fa-xmark"></i>
            </button>
        </div>

        <div class="modal-bd">
            <div class="global-confirm-alert">
                <div>
                    <p>
                        You currently have an active appointment.
                    </p>
                    <span>
                        Please complete it before booking another dental appointment.
                    </span>
                </div>
            </div>
        </div>

        <div class="modal-ft">

            <button type="button" class="ui-btn ui-btn-secondary" data-modal-close="activeAppointmentModal">
                Close
            </button>

            <a href="{{ route('patient.appointment.index') }}" class="ui-btn ui-btn-primary">
                View My Appointments
            </a>
        </div>
    </div>
</div>
