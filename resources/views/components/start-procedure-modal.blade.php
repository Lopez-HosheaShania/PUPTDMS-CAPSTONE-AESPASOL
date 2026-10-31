@props([
    'id' => 'startProcedureModal',
    'title' => 'Start Procedure',
    'subtitle' => 'Open the odontogram to begin this appointment.',
    'patient' => '—',
    'schedule' => '—',
    'service' => '—',
    'startUrl' => '',
])

<x-modal :id="$id" :title="$title" :title-id="$id . 'Title'" :subtitle="$subtitle" icon="fa-solid fa-play" size="md"
    theme="success" close-label="Close start procedure modal" data-start-procedure-modal
    data-start-url="{{ $startUrl }}">
    <div class="global-confirm-alert">
        <i class="fa-solid fa-circle-play"></i>

        <div>
            <strong data-start-modal-heading>
                Ready to start this appointment?
            </strong>

            <span data-start-modal-message>
                You will be redirected to the odontogram page
                for the selected patient.
            </span>
        </div>
    </div>

    <div class="confirmed-modal-details mt-4">

        <div class="confirmed-modal-details-header">
            <div class="confirmed-modal-details-icon">
                <i class="fa-solid fa-user"></i>
            </div>

            <div>
                <span class="confirmed-modal-details-eyebrow">
                    Appointment
                </span>

                <strong class="confirmed-modal-details-title">
                    Selected appointment details
                </strong>
            </div>
        </div>

        <div class="confirmed-modal-schedule-grid">

            <div class="confirmed-modal-schedule-item">
                <span class="confirmed-modal-schedule-icon">
                    <i class="fa-solid fa-user"></i>
                </span>

                <div>
                    <span class="confirmed-modal-schedule-label">
                        Patient
                    </span>

                    <strong class="confirmed-modal-schedule-value" data-start-modal-patient>
                        {{ $patient }}
                    </strong>
                </div>
            </div>

            <div class="confirmed-modal-schedule-item">
                <span class="confirmed-modal-schedule-icon">
                    <i class="fa-regular fa-calendar"></i>
                </span>

                <div>
                    <span class="confirmed-modal-schedule-label">
                        Schedule
                    </span>

                    <strong class="confirmed-modal-schedule-value" data-start-modal-schedule>
                        {{ $schedule }}
                    </strong>
                </div>
            </div>

            <div class="confirmed-modal-schedule-item">
                <span class="confirmed-modal-schedule-icon">
                    <i class="fa-solid fa-tooth"></i>
                </span>

                <div>
                    <span class="confirmed-modal-schedule-label">
                        Service Type
                    </span>

                    <strong class="confirmed-modal-schedule-value" data-start-modal-service>
                        {{ $service }}
                    </strong>
                </div>
            </div>

        </div>
    </div>

    <x-slot:footer>

        <x-button type="button" variant="secondary" data-modal-close="{{ $id }}">
            Cancel
        </x-button>

        <x-button type="button" variant="success" icon="fa-solid fa-play" data-start-modal-confirm>
            Proceed to Odontogram
        </x-button>

    </x-slot:footer>
</x-modal>
