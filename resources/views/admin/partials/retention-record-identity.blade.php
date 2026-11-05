@php
    $person = match ($category) {
        'users', 'patients', 'clinical_records' => $record,
        'appointments', 'document_requests' => $record->patient,
        default => null,
    };
    $personName = trim((string) $person?->name) ?: ($category === 'users' ? 'Unnamed account' : 'Patient unavailable');
    $title = $personName;
    $subtitle = trim((string) $person?->email) ?: $definition['label'];
    $icon = null;

    switch ($category) {
        case 'appointments':
            $subtitle = implode(' · ', array_filter([$record->service_type, $record->appointment_date])) ?: 'Appointment';
            break;
        case 'document_requests':
            $title = trim((string) $record->reference_number) ?: 'Document request';
            $subtitle = implode(' · ', array_filter([$record->document_type, $personName]));
            $icon = 'fa-file-lines';
            break;
        case 'audit_logs':
            $title = \Illuminate\Support\Str::headline($record->action ?? '') ?: 'Audit event';
            $subtitle = \Illuminate\Support\Str::headline($record->module ?? '') ?: 'System activity';
            $icon = 'fa-clock-rotate-left';
            break;
        case 'ai_service_logs':
            $title = \Illuminate\Support\Str::headline($record->feature ?? '') ?: 'AI service log';
            $subtitle = trim((string) $record->provider) ?: 'Provider not recorded';
            $icon = 'fa-microchip';
            break;
    }
@endphp
<div class="flex items-center gap-3 min-w-0">
    <span class="table-tag table-tag-neutral" aria-label="{{ $definition['record_label'] }} ID {{ $record->id }}">#{{ $record->id }}</span>
    <div class="table-primary flex-1 min-w-0">
        @if ($icon)
            <span class="patient-avatar patient-avatar-sm" aria-hidden="true"><i class="fa-solid {{ $icon }}"></i></span>
        @else
            <span class="patient-avatar patient-avatar-sm" data-patient-avatar data-patient-name="{{ $personName }}" aria-hidden="true"></span>
        @endif
        <div class="global-record-identity">
            <p class="global-record-name" title="{{ $title }}">{{ $title }}</p>
            <span class="global-info-subvalue block">{{ $subtitle }}</span>
        </div>
    </div>
</div>
