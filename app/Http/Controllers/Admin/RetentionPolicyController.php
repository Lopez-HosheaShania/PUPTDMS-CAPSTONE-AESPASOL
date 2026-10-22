<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\RetentionPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class RetentionPolicyController extends Controller
{
    private function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()?->hasAnyRole(['admin', 'super_admin'])
            && ! $request->session()->has('impersonated_role'), 403);
    }

    public function index(Request $request)
    {
        $this->authorizeAdministrator($request);

        return redirect()->route('admin.system_settings', ['tab' => 'retention']);
    }

    public function update(Request $request, RetentionPolicy $policy)
    {
        $this->authorizeAdministrator($request);
        $data = $request->validate([
            'action' => ['required', Rule::in(['update', 'approve', 'activate', 'deactivate'])],
            'version' => ['required', 'integer', 'min:1'],
            'confirmed' => ['accepted'],
            'return_to' => ['nullable', Rule::in(['system_settings'])],
            'duration' => ['required_if:action,update', 'integer', 'min:1', 'max:1200'],
            'unit' => ['required_if:action,update', Rule::in(['days', 'weeks', 'months', 'years'])],
            'start_event' => ['required_if:action,update', Rule::in(['reviewed_lifecycle_date', 'last_updated'])],
            'approval_reference' => ['required_if:action,approve', 'nullable', 'string', 'min:10', 'max:2000'],
        ]);
        if (in_array($data['action'], ['approve', 'activate'], true)) {
            abort_unless($request->user()->hasRole('super_admin'), 403);
        }

        DB::transaction(function () use ($request, $policy, $data) {
            $locked = RetentionPolicy::lockForUpdate()->findOrFail($policy->id);
            abort_unless($locked->version === (int) $data['version'], 409,
                'This policy changed. Reload the page before trying again.');
            $before = $locked->getAttributes();
            $action = $data['action'];
            if ($action === 'update') {
                $locked->fill(collect($data)->only(['duration', 'unit', 'start_event'])->all());
                $locked->fill(['status' => 'provisional', 'approved_by' => null, 'approved_at' => null,
                    'approval_reference' => null, 'activated_by' => null, 'activated_at' => null]);
            } elseif ($action === 'approve') {
                abort_unless($locked->status === 'provisional', 422, 'Only provisional policies can be approved.');
                $locked->fill(['status' => 'approved', 'approved_by' => $request->user()->id,
                    'approved_at' => now(), 'approval_reference' => $data['approval_reference']]);
            } elseif ($action === 'activate') {
                abort_unless($locked->status === 'approved' && $locked->approved_by
                    && $locked->approved_at && $locked->approval_reference, 422, 'Formal approval is required.');
                $locked->fill(['status' => 'active', 'activated_by' => $request->user()->id, 'activated_at' => now()]);
            } else {
                abort_unless($locked->status === 'active', 422, 'Only active policies can be deactivated.');
                $locked->fill(['status' => 'approved', 'activated_by' => null, 'activated_at' => null]);
            }
            // Enabling automation is deliberately unavailable in this initial implementation.
            $locked->fill(['archive_enabled' => false, 'disposal_enabled' => false,
                'updated_by' => $request->user()->id, 'version' => $locked->version + 1])->save();
            $auditAction = match ($action) {
                'approve' => 'retention_policy_approved', 'activate' => 'retention_policy_activated',
                'deactivate' => 'retention_policy_deactivated', default => 'retention_policy_updated',
            };
            $description = json_encode(['policy_id' => $locked->id, 'category' => $locked->category,
                'previous' => $before, 'updated' => $locked->getAttributes(),
                'outcome' => 'success'], JSON_THROW_ON_ERROR);
            AuditLogger::log($auditAction, 'data_retention', $description);
            if ($before['duration'] !== $locked->duration || $before['unit'] !== $locked->unit) {
                AuditLogger::log('retention_period_modified', 'data_retention', $description);
            }
        });

        return redirect()->route('admin.system_settings', ['tab' => 'retention'])->with('success',
            'Retention policy saved. Automatic archiving and disposal remain disabled.');
    }
}
