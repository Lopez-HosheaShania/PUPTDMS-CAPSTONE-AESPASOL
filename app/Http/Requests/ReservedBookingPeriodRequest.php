<?php

namespace App\Http\Requests;

use App\Models\ReservedBookingPeriod;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ReservedBookingPeriodRequest extends FormRequest
{
    protected $errorBag = 'reservedPeriod';

    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $timeslots = collect($this->input('timeslots', []))
            ->map(fn ($slot) => [
                'time' => isset($slot['time']) ? trim((string) $slot['time']) : null,
            ])
            ->values()
            ->all();

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'is_active' => $this->has('is_active')
                ? $this->input('is_active')
                : '0',
            'program_code' => filled($this->input('program_code'))
                ? strtoupper(trim((string) $this->input('program_code')))
                : null,
            'section' => filled($this->input('section'))
                ? strtoupper(trim((string) $this->input('section')))
                : null,
            'notes' => filled($this->input('notes'))
                ? trim((string) $this->input('notes'))
                : null,
            'timeslots' => $timeslots,
        ]);
    }

    public function rules(): array
    {
        $isStudent = $this->input('target_patient_type') === 'student';
        $usesTimeslots = $this->input('booking_mode') === 'timeslot';
        $isActive = $this->boolean('is_active');
        $reservedBookingPeriod = $this->route('reservedBookingPeriod');

        $reservedDateRules = [
            'required',
            'date',
            'after_or_equal:today',
        ];

        if ($isActive) {
            $reservedDateRules[] = Rule::unique('reserved_booking_periods', 'active_reserved_date')
                ->ignore($reservedBookingPeriod?->id);
        }

        return [
            'title' => ['required', 'string', 'max:120'],
            'is_active' => ['required', 'boolean'],
            'reserved_date' => $reservedDateRules,
            'start_time' => ['required', 'date_format:H:i'],
            'end_time' => ['required', 'date_format:H:i', 'after:start_time'],
            'booking_mode' => [
                'required',
                Rule::in(ReservedBookingPeriod::BOOKING_MODES),
            ],
            'target_patient_type' => [
                'required',
                Rule::in(ReservedBookingPeriod::PATIENT_TYPES),
            ],
            'program_code' => [
                Rule::requiredIf($isStudent),
                Rule::prohibitedIf(! $isStudent),
                'nullable',
                'string',
                'max:50',
            ],
            'year_level' => [
                Rule::requiredIf($isStudent),
                Rule::prohibitedIf(! $isStudent),
                'nullable',
                'integer',
                'min:1',
                'max:8',
            ],
            'section' => [
                Rule::requiredIf($isStudent),
                Rule::prohibitedIf(! $isStudent),
                'nullable',
                'string',
                'max:50',
            ],
            'max_capacity' => [
                Rule::requiredIf(! $usesTimeslots),
                Rule::excludeIf($usesTimeslots),
                'nullable',
                'integer',
                'min:1',
                'max:'.ReservedBookingPeriod::MAX_CAPACITY,
            ],
            'timeslot_duration_minutes' => [
                Rule::requiredIf($usesTimeslots),
                Rule::excludeIf(! $usesTimeslots),
                'nullable',
                'integer',
                'min:5',
                'max:240',
                'multiple_of:5',
            ],
            'timeslots' => [
                Rule::requiredIf($usesTimeslots),
                Rule::excludeIf(! $usesTimeslots),
                'array',
                'min:1',
                'max:'.ReservedBookingPeriod::MAX_CAPACITY,
            ],
            'timeslots.*.time' => ['required', 'date_format:H:i'],
            'notes' => ['nullable', 'string', 'max:500'],
        ];
    }

    public function messages(): array
    {
        return [
            'program_code.required' => 'Select a program for the student group.',
            'year_level.required' => 'Select a year level for the student group.',
            'section.required' => 'Enter a section for the student group.',
            'end_time.after' => 'The reserved end time must be later than its start time.',
            'reserved_date.unique' => 'This date already has an active reserved booking period. Set that period to Inactive first or choose another date.',
            'max_capacity.max' => 'Maximum capacity cannot exceed '.ReservedBookingPeriod::MAX_CAPACITY.' patients.',
            'timeslots.required' => 'Add at least one selectable timeslot for patients.',
            'timeslots.min' => 'Add at least one selectable timeslot for patients.',
            'timeslots.max' => 'A reserved period cannot have more than '.ReservedBookingPeriod::MAX_CAPACITY.' timeslots.',
            'timeslots.*.time.required' => 'Each timeslot must have a time.',
            'timeslot_duration_minutes.required' => 'Set how long each timeslot should last.',
            'timeslot_duration_minutes.multiple_of' => 'Timeslot duration must use 5-minute increments.',
        ];
    }
}
