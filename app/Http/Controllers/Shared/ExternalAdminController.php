<?php

namespace App\Http\Controllers\Shared;

use App\Http\Controllers\Controller;
use App\Models\ExternalAdminAccess;
use App\Models\ExternalAdminProfile;
use Illuminate\Support\Facades\DB;
use Illuminate\Http\Client\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\View\View;

class ExternalAdminController extends Controller
{
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
            DB::transaction(function () use ($validated) {
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
                    $profileValues[$field] = $validated[$field] ?? null;
                }
                $profile = ExternalAdminProfile::updateOrCreate(
                    ['external_admin_id' => $validated['external_admin_id']],
                    $profileValues
                );
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
                'error' => $e->getMessage(),
                'data' => $validated,
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
            /** @var Response $response */
            $response = $this->makeApiRequest('/external/admins', array_filter([
                'search' => $validated['search'] ?? null,
                'admin_id' => $validated['admin_id'] ?? null,
                'email' => $validated['email'] ?? null,
                'email_address' => $validated['email_address'] ?? null,
                'access_level' => $validated['access_level'] ?? null,
                'role' => $validated['role'] ?? null,
            ], static fn($value): bool => !is_null($value) && $value !== ''));

            if ($response->failed()) {
                Log::error('OCMS external admin search failed', [
                    'status' => $response->status(),
                    'body' => $response->body(),
                    'query' => $validated,
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch admin records from OCMS.',
                ], $response->status());
            }

            /** @var array<string, mixed> $payload */
            $payload = $response->json();

            /** @var Collection<int, array<string, mixed>> $records */
            $records = collect(
                is_array($payload['data'] ?? null) ? $payload['data'] : []
            );

            $searchTerm = strtolower(trim((string) ($validated['search'] ?? '')));

            $mapped = $records
                ->map(fn(array $item): array => $this->mapAdminRecord($item))
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
                'error' => $e->getMessage(),
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
     * @param string|int $adminId
     */
    public function show($adminId): JsonResponse
    {
        try {
            /** @var Response $response */
            $response = $this->makeApiRequest('/external/admins/' . urlencode((string) $adminId));

            if ($response->failed()) {
                Log::error('OCMS external admin fetch failed', [
                    'admin_id' => $adminId,
                    'status' => $response->status(),
                    'body' => $response->body(),
                ]);

                return response()->json([
                    'success' => false,
                    'message' => 'Failed to fetch selected admin record.',
                ], $response->status());
            }

            /** @var array<string, mixed> $payload */
            $payload = $response->json();

            /** @var array<string, mixed> $item */
            $item = is_array($payload['data'] ?? null) ? $payload['data'] : [];

            return response()->json([
                'success' => true,
                'data' => $this->mapAdminRecord($item),
            ]);
        } catch (\Throwable $e) {
            Log::error('OCMS external admin fetch exception', [
                'admin_id' => $adminId,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Unable to connect to OCMS external admin system.',
            ], 500);
        }
    }

    /**
     * Create HTTP request to external OCMS admin API.
     */
    private function makeApiRequest(string $path, array $query = []): Response
    {
        $baseUrl = rtrim((string) env('OCMS_EXTERNAL_API_URL'), '/');
        $apiKey = (string) env('OCMS_EXTERNAL_API_KEY');
        $url = $baseUrl . $path;

        /** @var Response $response */
        $response = Http::timeout(15)
            ->acceptJson()
            ->withHeaders([
                'X-External-Api-Key' => $apiKey,
            ])
            ->get($url, $query);

        return $response;
    }

    /**
     * Normalize external admin payload.
     *
     * @param array<string, mixed> $item
     * @return array<string, mixed>
     */
    private function mapAdminRecord(array $item): array
    {
        $fname = trim((string) ($item['first_name'] ?? ''));
        $lname = trim((string) ($item['last_name'] ?? ''));
        $fullName = trim((string) ($item['name'] ?? trim($fname . ' ' . $lname)));
        $externalAdminId = collect([
            $item['admin_id'] ?? null,
            $item['external_admin_id'] ?? null,
            $item['user_id'] ?? null,
            $item['id'] ?? null,
            $item['email'] ?? null,
        ])
            ->map(fn($value) => is_scalar($value) ? trim((string) $value) : '')
            ->first(fn($value) => $value !== '');

        return [
            'admin_id' => $externalAdminId,
            'fname' => $fname,
            'lname' => $lname,
            'full_name' => $fullName,
            'email' => $item['email'] ?? '',
            'office' => $item['office'] ?? '',
            'address' => $item['address'] ?? '',
            'age' => $item['age'] ?? null,
            'gender' => $item['gender'] ?? '',
            'birthday' => $item['birthday'] ?? null,
            'civil_status' => $item['civil_status'] ?? '',
            'access_level' => $item['access_level'] ?? '',
            'contact_number' => $item['emergency_contact_no'] ?? '',
            'senior_pwd' => $item['senior_pwd'] ?? '',
            'emergency_contact_person' => $item['emergency_contact_person'] ?? '',
            'last_updated' => $item['last_updated'] ?? null,
        ];
    }
}
