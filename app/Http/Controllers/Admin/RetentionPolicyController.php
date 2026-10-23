<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\RetentionPolicy;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class RetentionPolicyController extends Controller
{
    private function authorizeAdministrator(Request $request): void
    {
        abort_unless($request->user()?->status === 'active'
            && $request->user()->hasAnyRole(['admin', 'super_admin'])
            && $request->user()->hasPermission('manage_system_settings')
            && ! $request->session()->has('impersonated_role'), 403);
    }

    private function verifyPassword(Request $request, RetentionPolicy $policy): void
    {
        $key = 'retention-password:'.$request->user()->getAuthIdentifier();
        if (RateLimiter::tooManyAttempts($key, 5)) {
            throw ValidationException::withMessages([
                'password' => 'Too many incorrect password attempts. Try again in '.RateLimiter::availableIn($key).' seconds.',
            ]);
        }

        $validator = Validator::make($request->only('password'), [
            'password' => ['bail', 'required', 'string', 'max:1024', 'current_password:web'],
        ], [
            'password.required' => 'Enter your current admin password to save retention changes.',
            'password.current_password' => 'The admin password is incorrect.',
        ]);
        if ($validator->fails()) {
            RateLimiter::hit($key, 60);
            AuditLogger::log('retention_password_verification_failed', 'data_retention', json_encode([
                'policy_id' => $policy->id, 'outcome' => 'denied',
            ], JSON_THROW_ON_ERROR));
            throw new ValidationException($validator);
        }

        RateLimiter::clear($key);
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
            'approval_reference' => ['required_if:action,approve', 'nullable', 'string', 'min:10', 'max:2000'],
        ]);
        if (in_array($data['action'], ['approve', 'activate'], true)) {
            abort_unless($request->user()->hasRole('super_admin'), 403);
        }
        $this->verifyPassword($request, $policy);

        DB::transaction(function () use ($request, $policy, $data) {
            $locked = RetentionPolicy::lockForUpdate()->findOrFail($policy->id);
            abort_unless($locked->version === (int) $data['version'], 409,
                'This policy changed. Reload the page before trying again.');
            $before = $locked->attributesToArray();
            $action = $data['action'];
            if ($action === 'update') {
                $locked->fill(collect($data)->only(['duration', 'unit'])->all());
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
            $locked->fill(['updated_by' => $request->user()->id, 'version' => $locked->version + 1])->save();
            if ($action === 'update' && $locked->approval) {
                $locked->approval()->delete();
                $locked->setRelation('approval', null);
            }
            $auditAction = match ($action) {
                'approve' => 'retention_policy_approved', 'activate' => 'retention_policy_activated',
                'deactivate' => 'retention_policy_deactivated', default => 'retention_policy_updated',
            };
            $description = json_encode(['policy_id' => $locked->id, 'category' => $locked->category,
                'previous' => $before, 'updated' => $locked->attributesToArray(),
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
