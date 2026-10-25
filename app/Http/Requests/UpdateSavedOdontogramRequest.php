<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateSavedOdontogramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason_category' => filled($this->input('reason_category'))
                ? trim((string) $this->input('reason_category'))
                : null,

            'reason_notes' => trim(
                (string) $this->input(
                    'reason_notes',
                    ''
                )
            ),
        ]);
    }

    public function rules(): array
    {
        return [
            'odontogram_data' => ['present', 'array'],
            'odontogram_data.*.tooth' => ['nullable', 'integer', 'min:1'],

            'odontogram_data.*.status.code' => ['nullable', 'string', 'max:20'],
            'odontogram_data.*.status.label' => ['nullable', 'string', 'max:255'],
            'odontogram_data.*.status.colorHex' => ['nullable', 'string', 'max:30'],

            'odontogram_data.*.threeD.code' => ['nullable', 'string', 'max:20'],
            'odontogram_data.*.threeD.label' => ['nullable', 'string', 'max:255'],
            'odontogram_data.*.threeD.colorHex' => ['nullable', 'string', 'max:30'],

            'odontogram_data.*.surfaces' => ['nullable', 'array'],
            'odontogram_data.*.surfaces.*.code' => ['nullable', 'string', 'max:20'],
            'odontogram_data.*.surfaces.*.label' => ['nullable', 'string', 'max:255'],
            'odontogram_data.*.surfaces.*.colorHex' => ['nullable', 'string', 'max:30'],

            'oral_examination' => ['nullable', 'string'],
            'diagnosis' => ['nullable', 'string'],
            'prescriptions' => ['nullable', 'string'],

            'inventory_notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:10000',
            ],

            'completion_action' => ['nullable', Rule::in(['finished'])],
            'has_applied_treatment' => ['required', 'boolean'],

            'procedure_duration_hms' => [
                'required',
                'regex:/^\d{2}:[0-5]\d:[0-5]\d$/',
            ],
            'procedure_duration_seconds' => [
                'nullable',
                'integer',
                'min:0',
            ],

            'reason_category' => [
                'required',
                'string',
                Rule::in([
                    'wrong_tooth',
                    'wrong_surface',
                    'wrong_condition',
                    'treatment_changed',
                    'data_entry_error',
                    'other',
                ]),
            ],

            'reason_notes' => [
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_category.required' =>
            'Please select a reason for the correction.',

            'reason_category.in' =>
            'The selected correction reason is invalid.',
        ];
    }
}
