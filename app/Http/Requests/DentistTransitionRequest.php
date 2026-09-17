<?php

namespace App\Http\Requests;

use App\Models\DentistTransition;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;

class DentistTransitionRequest extends FormRequest
{
    private string $selectedTransitionType = '';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->selectedTransitionType = trim((string) $this->input('transition_type', ''));
        $customTransitionType = trim((string) $this->input('transition_type_other', ''));

        $this->merge([
            'transition_type' => $this->selectedTransitionType === 'other' && $customTransitionType !== ''
                ? $customTransitionType
                : $this->selectedTransitionType,
            'transition_type_other' => $customTransitionType,
        ]);
    }

    public function rules(): array
    {
        $rules = [
            'transition_type' => ['required', 'string', 'max:40'],
            'transition_type_other' => ['nullable', 'string', 'max:40'],
            'default_successor_dentist_id' => ['required', 'integer', 'exists:users,id'],
            'last_working_date' => ['required', 'date'],
            'handover_notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($this->isCreateRequest()) {
            $rules['dentist_id'] = ['required', 'integer', 'exists:users,id'];
        }

        return $rules;
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var DentistTransition|null $transition */
            $transition = $this->route('transition');
            $transitionType = trim((string) $this->input('transition_type', ''));
            $customTransitionType = trim((string) $this->input('transition_type_other', ''));

            $dentistId = $transition
                ? (int) $transition->dentist_id
                : (int) $this->input('dentist_id');

            $successorId = (int) $this->input('default_successor_dentist_id');

            $dentist = User::with('role')->find($dentistId);
            $successor = $successorId ? User::with('role')->find($successorId) : null;

            if ($this->selectedTransitionType === 'other' && $customTransitionType === '') {
                $validator->errors()->add('transition_type_other', 'Please enter the other transition reason.');
            }

            if (
                $this->selectedTransitionType !== 'other' &&
                !in_array($transitionType, DentistTransition::TYPES, true)
            ) {
                $validator->errors()->add('transition_type', 'Please select a valid transition reason.');
            }

            if (!$dentist || optional($dentist->role)->slug !== 'dentist') {
                $validator->errors()->add('dentist_id', 'The selected departing user must be a dentist.');
            }

            if ($successorId && $dentistId === $successorId) {
                $validator->errors()->add('default_successor_dentist_id', 'The departing dentist cannot be their own successor.');
            }

            if ($successor && (optional($successor->role)->slug !== 'dentist' || $successor->status !== 'active')) {
                $validator->errors()->add('default_successor_dentist_id', 'The successor dentist must be an active dentist.');
            }

            if ($this->isCreateRequest()) {
                $duplicateExists = DentistTransition::query()
                    ->where('dentist_id', $dentistId)
                    ->whereIn('status', ['draft', 'pending_review', 'handover_in_progress', 'scheduled'])
                    ->exists();

                if ($duplicateExists) {
                    $validator->errors()->add('dentist_id', 'This dentist already has an active transition.');
                }
            }
        });
    }

    private function isCreateRequest(): bool
    {
        return $this->routeIs(
            'admin.dentist-transitions.store',
            'dentist.dentist.transitions.store'
        );
    }
}
