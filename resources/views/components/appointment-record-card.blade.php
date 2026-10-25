@props([
'appointment',
'variant' => 'past',
'showDetails' => false,
'showCountdown' => null,
'showTimeRange' => true,
'animationDelay' => null,
'compact' => false,
'recordEditUrl' => null,
'showReserved' => false,
'previousOdontogram' => [],
])

@php
use Carbon\Carbon;
use App\Support\OdontogramTreatmentDisplay;
use App\Services\AppointmentOdontogramSnapshotService;
use Illuminate\Support\Str;

$get = function ($key, $fallback = null) use ($appointment) {
return data_get($appointment, $key, $fallback);
};

$dateRaw = $get('appointment_date') ?? $get('date');

$timeRaw = $get('appointment_time') ?? $get('time');

$apptDate = null;
$apptTime = null;

try {
$apptDate = $dateRaw ? Carbon::parse($dateRaw) : null;
} catch (\Throwable $e) {
$apptDate = null;
}

try {
$apptTime = $timeRaw ? Carbon::parse($timeRaw) : null;
} catch (\Throwable $e) {
$apptTime = null;
}

$service = $get('service_type') ?? ($get('service') ?? 'Dental Appointment');

$otherService = $get('other_services');

$serviceLabel = filled($otherService) ? $service . ' (' . $otherService . ')' : $service;

$rawStatus = strtolower(trim((string) ($get('status') ?? 'completed')));

$normalizedStatus = match ($rawStatus) {
'scheduled', 'confirmed', 'pending', 'upcoming' => 'upcoming',

'reschedule', 'rescheduled' => 'rescheduled',

'cancelled', 'canceled' => 'cancelled',

'completed' => 'completed',

default => 'default',
};

$statusClass = 'status-' . $normalizedStatus;

$statusLabel =
$normalizedStatus === 'default' ? Str::headline($rawStatus ?: 'Status') : Str::headline($normalizedStatus);

$dentistName =
$get('dentist.name') ??
($get('originalDentist.name') ??
($get('dentist_name') ?? ((is_string($get('dentist')) ? $get('dentist') : null) ?? 'Not assigned')));

$isFollowUp = (bool) ($get('is_follow_up') ?? false) || str_contains(strtolower($serviceLabel), 'follow-up');

$isReserved = (bool) $showReserved && filled($get('reserved_booking_period_id'));

$reservedTitle = $isReserved
    ? ($get('reservedBookingPeriod.title') ?? $get('reserved_title'))
    : null;

$isPast = $variant === 'past' || in_array($normalizedStatus, ['completed', 'cancelled'], true);

if ($showCountdown === null) {
$showCountdown = !$isPast && $apptDate !== null;
}

$countdown = null;

if ($showCountdown && $apptDate) {
$today = Carbon::today();

$difference = $today->diffInDays($apptDate->copy()->startOfDay(), false);

if ($difference === 0) {
$countdown = 'Today';
} elseif ($difference === 1) {
$countdown = 'Tomorrow';
} elseif ($difference > 1) {
$countdown = 'In ' . $difference . ' days';
}
}

if ($showDetails) {
$procedure = $get('procedure');

$durationSeconds =
data_get($procedure, 'procedure_duration_seconds') ??
($get('duration_seconds') ?? $get('procedure_duration_seconds'));

$duration = $get('duration');

$remarks =
    $get('remarks') ?? ($get('treatment') ?? $get('treatment_notes'));

$oral = data_get($procedure, 'oral_examination') ?? ($get('oral') ?? $get('oral_examination'));

$diagnosis = data_get($procedure, 'diagnosis') ?? $get('diagnosis');

$prescription = data_get($procedure, 'prescriptions') ?? ($get('prescription') ?? $get('prescriptions'));

$currentOdontogram =
    data_get(
        $procedure,
        'odontogram_data'
    )
    ?? (
        $get('odontogram')
        ?? (
            $get('odontogram_data')
            ?? []
        )
    );

$odontogramSnapshotService =
    app(
        AppointmentOdontogramSnapshotService::class
    );

$odontogram =
    $odontogramSnapshotService
        ->appointmentSnapshot(
            $currentOdontogram,
            $previousOdontogram
        );

$treatmentItems =
    OdontogramTreatmentDisplay::items(
        $odontogram
    );

$followUp = $get('follow_up') ?? $get('followUp');

if (!$followUp) {
$followUps = collect($get('followUpAppointments', []))->sortBy(function ($item) {
return sprintf('%s %s', data_get($item, 'appointment_date', ''), data_get($item, 'appointment_time', ''));
});

$followUpAppointment = $followUps->first();

if ($followUpAppointment) {
$followUp = [
'date' => data_get($followUpAppointment, 'appointment_date')
? Carbon::parse(data_get($followUpAppointment, 'appointment_date'))->format('F d, Y')
: null,

'time' => data_get($followUpAppointment, 'appointment_time')
? Carbon::parse(data_get($followUpAppointment, 'appointment_time'))->format('g:i A')
: null,

'service' => data_get($followUpAppointment, 'service_type') ?? 'Follow-up',

'status' => data_get($followUpAppointment, 'status') ?? 'upcoming',

'reason' => data_get($followUpAppointment, 'follow_up_reason'),
];
}
}

   $revisionItems = collect(
    $get(
        'odontogramRevisions',
        []
    )
)
    ->sortByDesc('version_number')
    ->map(function ($revision) {
        $createdAt =
            data_get(
                $revision,
                'created_at'
            );

        return [
            'id' =>
                data_get(
                    $revision,
                    'id'
                ),

            'version_number' =>
                (int) data_get(
                    $revision,
                    'version_number',
                    0
                ),

            'odontogram_data' =>
                data_get(
                    $revision,
                    'odontogram_data',
                    []
                ),

            'changes' =>
                data_get(
                    $revision,
                    'changes',
                    []
                ),

           'reason_type_code' =>
                data_get(
                    $revision,
                    'reasonType.code'
                ),

            'reason_type_label' =>
                data_get(
                    $revision,
                    'reasonType.label'
                ),

            'reason_detail' =>
                data_get(
                    $revision,
                    'reason_detail'
                ),

            'reason_notes' =>
                data_get(
                    $revision,
                    'reason_notes'
                ),

            'changed_by_name' =>
                data_get(
                    $revision,
                    'changedBy.name',
                    'Unknown user'
                ),

            'changed_by_role' =>
                data_get(
                    $revision,
                    'changed_by_role'
                ),

            'created_at' =>
                $createdAt
                    ? Carbon::parse(
                        $createdAt
                    )->toIso8601String()
                    : null,
        ];
    })
    ->values()
    ->all();

$currentRevisionVersion =
    collect($revisionItems)
        ->max('version_number');

$odontogramEditLocked = false;
$odontogramEditDeadline = null;

if ($appointment instanceof \App\Models\Appointment) {
    $odontogramEditLocked =
        $normalizedStatus === 'completed'
        &&
        $appointment->isOdontogramEditLocked();

    $odontogramEditDeadline =
        $appointment->odontogramEditDeadline();
}

$recordPayload = [
'id' => $get('id'),

'service' => $serviceLabel,

'date' => $apptDate ? $apptDate->format('F d, Y') : $dateRaw,

'time' => $apptTime ? $apptTime->format('g:i A') : $timeRaw,

'status' => $rawStatus,

'duration_seconds' => $durationSeconds,

'duration' => $duration,

'remarks' => $remarks,

'oral' => $oral,

'diagnosis' => $diagnosis,

'prescription' => $prescription,

'odontogram_data' => $odontogram,

'treatment_items' => $treatmentItems,

'follow_up' => $followUp,

'revisions' => $revisionItems,

'current_version' => $currentRevisionVersion,

'edit_url' => $recordEditUrl,

'edit_locked' => $odontogramEditLocked,

'edit_lock_reason' =>
    $odontogramEditLocked
        ? 'The 7-day editing period for this completed odontogram has expired.'
        : null,

'edit_deadline' =>
    $odontogramEditDeadline
        ?->toIso8601String(),
];
}

$cardClasses = [
'global-record-card',
'appt-visit-card',

$isPast ? 'appt-visit-card-past' : 'appt-visit-card-upcoming',

$statusClass,

$compact ? 'appt-visit-card-compact' : '',
];

$timeLabel = '—';

if ($apptTime) {
$timeLabel = $apptTime->format('g:i A');

if ($showTimeRange) {
$timeLabel .= ' – ' . $apptTime->copy()->addHour()->format('g:i A');
}
}
@endphp

<div {{ $attributes->class($cardClasses) }}
    @if ($animationDelay !== null) style="animation-delay: {{ $animationDelay }}s" @endif>
    <div class="appt-visit-date
            {{ $isPast ? 'appt-visit-date-muted' : '' }}">
        <span class="appt-visit-day">
            {{ $apptDate?->format('d') ?? '—' }}
        </span>

        <span class="appt-visit-month">
            {{ $apptDate?->format('M') ?? '' }}
        </span>

        <span class="appt-visit-year">
            {{ $apptDate?->format('Y') ?? '' }}
        </span>
    </div>

    <div class="appt-visit-main">
        <div class="appt-visit-head">
            <div class="appt-visit-title-group">

                <div class="appt-visit-title-row">
                    <h3 class="appt-visit-title">
                        {{ $serviceLabel }}
                    </h3>

                    @if ($isFollowUp)
                    <span class="appt-type-icon" data-tooltip="Follow-up appointment" data-tooltip-tone="neutral"
                        aria-label="Follow-up appointment" tabindex="0">
                        <i class="fa-solid fa-calendar-plus"></i>
                    </span>
                    @endif

                    @if ($isReserved)
                    <span class="status-pill status-today"
                        data-tooltip="Reserved{{ filled($reservedTitle) ? ': ' . $reservedTitle : ' appointment' }}"
                        data-tooltip-tone="neutral" aria-label="Reserved appointment" tabindex="0">
                        <i class="fa-solid fa-calendar-check"></i>
                        <span>Reserved</span>
                    </span>
                    @endif
                </div>

                <span class="status-pill {{ $statusClass }}">
                    <span class="status-dot"></span>

                    <span>
                        {{ $statusLabel }}
                    </span>
                </span>
            </div>

            @if ($countdown)
            <span class="urgency-chip urgency-upcoming">
                {{ $countdown }}
            </span>
            @endif
        </div>

        <div class="appt-visit-meta">
            <span class="global-info-pill">
                <i class="fa-regular fa-clock"></i>

                <span>
                    {{ $timeLabel }}
                </span>
            </span>

            <span class="global-info-pill">
                <i class="fa-solid fa-user-doctor"></i>

                <span>
                    {{ $dentistName }}
                </span>
            </span>
        </div>
    </div>

    @if ($showDetails)

    <div class="appt-visit-actions">
        <button type="button" class="ui-action-btn ui-action-view" data-appointment-id="{{ $get('id') }}" data-record='@json(
        $recordPayload,
        JSON_HEX_TAG |
        JSON_HEX_APOS |
        JSON_HEX_AMP |
        JSON_HEX_QUOT
    )' onclick="openRecordModal(
        JSON.parse(this.dataset.record)
    )" aria-label="View details" data-tooltip="View details" data-tooltip-tone="view">
            <i class="fa-regular fa-eye"></i>
        </button>
    </div>
    
    @endif
</div>
