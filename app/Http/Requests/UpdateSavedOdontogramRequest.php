<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use App\Models\OdontogramRevisionReasonType;

class UpdateSavedOdontogramRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'reason_type_id' => filled($this->input('reason_type_id'))
                ? (int) $this->input('reason_type_id')
                : null,

            'reason_detail' => filled($this->input('reason_detail'))
                ? trim((string) $this->input('reason_detail')) : null,

            'reason_notes' => trim((string) $this->input('reason_notes', '')),
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

            'reason_type_id' => [
                'required',
                'integer',
                Rule::exists(
                    'odontogram_revision_reason_types',
                    'id'
                )->where(
                    fn($query) =>
                    $query->where(
                        'is_active',
                        true
                    )
                ),
            ],

            'reason_detail' => [
                'nullable',
                'string',
                'max:255',
            ],

            'reason_notes' => [
                'nullable',
                'string',
                'max:2000',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'reason_type_id.required' =>
            'Please select a reason for the correction.',

            'reason_type_id.exists' =>
            'The selected correction reason is invalid.',

            'reason_detail.max' =>
            'The specified correction reason may not exceed 255 characters.',
        ];
    }

    public function withValidator(
        $validator
    ): void {
        $validator->after(
            function ($validator) {
                if (
                    $validator
                    ->errors()
                    ->has(
                        'reason_type_id'
                    )
                ) {
                    return;
                }

                $reasonType =
                    OdontogramRevisionReasonType
                    ::query()
                    ->find(
                        $this->input(
                            'reason_type_id'
                        )
                    );

                if (
                    $reasonType
                    ?->requires_detail &&
                    blank(
                        $this->input(
                            'reason_detail'
                        )
                    )
                ) {
                    $validator
                        ->errors()
                        ->add(
                            'reason_detail',
                            'Please specify the correction reason.'
                        );
                }
            }
        );
    }
}
