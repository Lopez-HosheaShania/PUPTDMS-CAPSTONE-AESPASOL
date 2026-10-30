<?php

namespace App\Http\Requests;

use App\Models\DentistTransitionItem;
use App\Models\User;
use App\Services\AssignedDentistService;
use Illuminate\Foundation\Http\FormRequest;

class AssignDentistSuccessorsRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->hasAnyRole(['admin', 'super_admin']) ?? false;
    }

    public function rules(): array
    {
        return [
            'default_successor_dentist_id' => ['nullable', 'integer', 'exists:users,id'],
            'items' => ['nullable', 'array'],
            'items.*.selected_for_transfer' => ['nullable', 'boolean'],
            'items.*.successor_dentist_id' => ['nullable', 'integer', 'exists:users,id'],
            'items.*.transfer_status' => ['nullable', 'in:ready,pending,excluded,manually_resolved'],
            'items.*.resolution_type' => ['nullable', 'string', 'max:40'],
            'items.*.remarks' => ['nullable', 'string'],
        ];
    }

    public function withValidator($validator): void
    {
        $validator->after(function ($validator) {
            /** @var \App\Models\DentistTransition|null $transition */
            $transition = $this->route('transition');

            if (!$transition) {
                return;
            }

            $defaultId = $this->input('default_successor_dentist_id');
            if ($defaultId) {
                $successor = User::with('role')->find($defaultId);
                if (! $successor || $defaultId == $transition->dentist_id || ! AssignedDentistService::isDentist($successor) || $successor->status !== 'active') {
                    $validator->errors()->add('default_successor_dentist_id', 'Choose an active replacement dentist other than the affected dentist.');
                }
            }
            $items = $this->input('items', []);

            foreach ($items as $itemId => $itemData) {
                $transitionItem = DentistTransitionItem::find($itemId);

                if (!$transitionItem || $transitionItem->dentist_transition_id !== $transition->id) {
                    $validator->errors()->add("items.{$itemId}", 'Invalid transition item selected.');
                    continue;
                }

                $successorId = (int) ($itemData['successor_dentist_id'] ?? 0);

                if (!$successorId) {
                    continue;
                }

                if ($successorId === (int) $transition->dentist_id) {
                    $validator->errors()->add("items.{$itemId}.successor_dentist_id", 'The departing dentist cannot be their own successor.');
                    continue;
                }

                $successor = User::with('role')->find($successorId);

                if (!$successor || ! AssignedDentistService::isDentist($successor) || $successor->status !== 'active') {
                    $validator->errors()->add("items.{$itemId}.successor_dentist_id", 'Each successor must be an active dentist.');
                }
            }
        });
    }
}
