<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\ExternalAdminAccess;
use App\Models\ExternalAdminProfile;
use App\Services\CmsApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ExternalAdminController extends Controller
{
    public function __construct(private readonly CmsApiService $cmsApi) {}

    public function index(): View
    {
        return view('shared.assign-cms-access');
    }

    public function store(Request $request): RedirectResponse
    {
        abort_unless(
            Auth::user()?->hasPermission('create_cms_integration'), 403
        );

        $validated = $request->validate([
            'external_admin_id' => 'required|string|max:255',
            'fname' => 'nullable|string|max:255',
            'lname' => 'nullable|string|max:255',
            'email' => 'nullable|email|max:255',
            'office' => 'nullable|string|max:255',
            'address' => 'nullable|string',
            'age' => 'nullable|integer',
            'senior_pwd' => 'nullable|string|max:255',
            'gender' => 'nullable|string|max:50',
            'contact_number' => 'nullable|string|max:50',
            'cms_role' => 'required|in:admin,patient,dentist',
            'cms_status' => 'required|in:active,inactive',
        ]);

        try {
            $admin = $this->cmsApi->getAdmin($validated['external_admin_id']);
            DB::transaction(function () use ($validated, $admin) {
                $profileValues = [];
                foreach (
                    [
                        'fname',
                        'lname',
                        'email',
                        'office',
                        'address',
                        'age',
                        'gender',
                        'contact_number',
                        'senior_pwd',
                    ] as $field
                ) {
                    $profileValues[$field] = $admin[$field] ?? null;
                }
                $profiles = ExternalAdminProfile::where(function ($query) use ($admin) {
                    $query->where('external_admin_id', $admin['admin_id']);
                    if (filled($admin['email'])) {
                        $query->orWhereRaw('LOWER(TRIM(email)) = ?', [strtolower(trim($admin['email']))]);
                    }
                })->lockForUpdate()->get();
                if ($profiles->count() > 1) {
                    throw new \RuntimeException('Multiple local CMS profiles match this identity.');
                }
                $profile = $profiles->first() ?? new ExternalAdminProfile;
                $profile->fill(['external_admin_id' => $admin['admin_id']] + array_filter(
                    $profileValues, static fn ($value) => $value !== null && $value !== ''
                ))->save();
                ExternalAdminAccess::updateOrCreate(
                    ['external_admin_profile_id' => $profile->id],
                    ['has_cms_access' => true, 'cms_role' => $validated['cms_role'], 'cms_status' => $validated['cms_status']]
                );
            });

            return redirect()
                ->route(request()->routeIs('dentist.*') ? 'dentist.assign-cms-access' : 'admin.assign-cms-access')
                ->with('success', 'CMS access saved successfully.');
        } catch (\Throwable $e) {
            Log::error('Failed to save CMS access', [
                'exception' => get_class($e),
            ]);

            return redirect()
                ->back()
                ->withInput()
                ->with('error', 'Failed to save access. Please try again.');
        }
    }

    /**
     * Get all admins / search admins from OCMS API.
     *
     * Supported query params:
     * - search
     * - admin_id
     * - email
     * - email_address
     * - access_level
     * - role
     */
    public function search(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'nullable|string|max:255',
            'admin_id' => 'nullable|string|max:255',
            'email' => 'nullable|string|max:255',
            'email_address' => 'nullable|string|max:255',
            'access_level' => 'nullable|string|max:255',
            'role' => 'nullable|string|max:255',
        ]);

        try {
            $admins = $this->cmsApi->getAdmins(array_filter([
                'search' => $validated['search'] ?? null,
                'admin_id' => $validated['admin_id'] ?? null,
                'email' => $validated['email'] ?? null,
                'email_address' => $validated['email_address'] ?? null,
                'access_level' => $validated['access_level'] ?? null,
                'role' => $validated['role'] ?? null,
            ], static fn ($value): bool => ! is_null($value) && $value !== ''));

            /** @var Collection<int, array<string, mixed>> $records */
            $records = collect($admins);

            $searchTerm = strtolower(trim((string) ($validated['search'] ?? '')));

            $mapped = $records
                ->filter(function (array $user) use ($searchTerm): bool {
                    if ($searchTerm === '') {
                        return true;
                    }

                    return str_contains(strtolower((string) $user['full_name']), $searchTerm)
                        || str_contains(strtolower((string) $user['fname']), $searchTerm)
                        || str_contains(strtolower((string) $user['lname']), $searchTerm)
                        || str_contains(strtolower((string) $user['email']), $searchTerm)
                        || str_contains(strtolower((string) $user['office']), $searchTerm);
                })
                ->values();

            return response()->json([
                'success' => true,
                'data' => $mapped,
            ]);
        } catch (\Throwable $e) {
            Log::error('OCMS external admin search exception', [
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to OCMS external admin system.',
            ], 500);
        }
    }

    /**
     * Get single admin by admin_id from OCMS API.
     *
     * @param  string|int  $adminId
     */
    public function show($adminId): JsonResponse
    {
        try {
            $item = $this->cmsApi->getAdmin((string) $adminId);

            return response()->json([
                'success' => true,
                'data' => $item,
            ]);
        } catch (\Throwable $e) {
            Log::error('OCMS external admin fetch exception', [
                'admin_id' => $adminId,
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to OCMS external admin system.',
            ], 500);
        }
    }
}
