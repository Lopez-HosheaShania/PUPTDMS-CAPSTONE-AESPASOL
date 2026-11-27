@props([
    'id' => 'confirmModal',
    'title' => 'Appointment Confirmed',
    'sectionEyebrow' => 'Confirmation Status',
    'sectionTitle' => 'Appointment saved',
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

            <div class="appointment-modal-header-copy">

                <h2 id="{{ $titleId }}" class="appointment-modal-title">
                    {{ $title }}
                </h2>
            </div>

        </div>
    </x-slot:header>

    <div class="modal-profile-card modal-profile-card-single">
        <div class="modal-profile-main-copy">

            <span class="modal-profile-eyebrow">
                {{ $sectionEyebrow }}
            </span>

            <strong class="modal-profile-name">
                {{ $sectionTitle }}
            </strong>
        </div>
    </div>

    <div class="confirmed-modal-details">

        <div class="confirmed-modal-details-header">

            <div>
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
