@props([
    'id' => 'confirmModal',
    'eyebrow' => 'Appointment',
    'title' => 'Appointment Confirmed',
    'subtitle' => 'The appointment was saved successfully.',
    'headerIcon' => 'fa-check',
    'sectionIcon' => 'fa-calendar-check',
    'sectionEyebrow' => 'Confirmation Status',
    'sectionTitle' => 'Appointment saved',
    'sectionMessage' => 'The appointment information has been recorded successfully.',
    'detailLabel' => 'Status',
    'resultTitle' => 'Confirmed',

    'messageTitle' => 'Confirmation details',
    'messageId' => 'confirmMessage',

    'size' => 'modal-lg',

    'isStatic' => false,
])

@php
    $resolvedSize = match ($size) {
        'modal-sm' => 'sm',
        'modal-md' => 'md',
        'modal-lg' => 'lg',
        'modal-xl' => 'xl',
        default => $size,
    };

    $titleId = $id . 'Title';
@endphp

<x-modal :id="$id" :title="$title" :title-id="$titleId" :size="$resolvedSize" :static="$isStatic" :show-close="false"
    header-class="appointment-modal-header">
    <x-slot:header>
        <div class="modal-heading">

            <div class="appointment-modal-header-icon">
                <i class="fa-solid {{ $headerIcon }}"></i>
            </div>

            <div class="appointment-modal-header-copy">

                <span class="appointment-modal-eyebrow">
                    {{ $eyebrow }}
                </span>

                <h2 id="{{ $titleId }}" class="appointment-modal-title">
                    {{ $title }}
                </h2>

                <p class="appointment-modal-subtitle">
                    {{ $subtitle }}
                </p>

            </div>

        </div>
    </x-slot:header>

    <div class="modal-profile-card modal-profile-card-single">

        <div class="modal-profile-main">

            <div class="modal-profile-avatar">
                <i class="fa-solid {{ $sectionIcon }}"></i>
            </div>

            <div class="modal-profile-main-copy">

                <span class="modal-profile-eyebrow">
                    {{ $sectionEyebrow }}
                </span>

                <strong class="modal-profile-name">
                    {{ $sectionTitle }}
                </strong>

                <p class="modal-subtitle">
                    {{ $sectionMessage }}
                </p>

            </div>

        </div>

        <div class="modal-profile-details modal-profile-details-single">

            <div class="modal-profile-detail">

                <div class="modal-profile-detail-icon">
                    <i class="fa-solid fa-circle-check"></i>
                </div>

                <div>

                    <span class="modal-profile-label">
                        {{ $detailLabel }}
                    </span>

                    <strong class="modal-profile-value">
                        {{ $resultTitle }}
                    </strong>

                </div>

            </div>

        </div>

    </div>

    <div class="confirmed-modal-details">

        <div class="confirmed-modal-details-header">

            <div class="confirmed-modal-details-icon">
                <i class="fa-regular fa-calendar-check"></i>
            </div>

            <div>
                <span class="confirmed-modal-details-eyebrow">
                    Appointment Details
                </span>

                <strong class="confirmed-modal-details-title">
                    {{ $messageTitle }}
                </strong>
            </div>

        </div>

        <div id="{{ $messageId }}" class="confirmed-modal-details-content">
            {{ $slot }}
        </div>

    </div>

    <x-slot:footer>
        {{ $footer }}
    </x-slot:footer>

</x-modal>
