<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Patient;
use App\Services\ConcurrentSessionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Helpers\AuditLogger;
use Illuminate\Validation\Rules\Password;

class UserManagementController extends Controller
{
    private const PASSWORD_LENGTH = 12;
    private const USER_MANAGEMENT_PERMISSIONS = [
        'view_account_details',
        'create_users',
        'disable_users',
        'update_user_role',
        'update_user_password',
    ];

    public function __construct(
        private readonly ConcurrentSessionService $concurrentSessionService
    ) {}

    public function index(Request $request)
    {
        $this->authorizeAnyUserManagementAccess();

        $roles = Role::withCount('users')
            ->orderBy('name')
            ->get()
            ->filter(fn(Role $role) => $this->canManageRoleAccounts($role))
            ->values();
        $roleCounts = $roles->mapWithKeys(fn($role) => [$role->slug => $role->users_count]);
        $visibleRoleIds = $roles->pluck('id')->all();

        $search = trim((string) $request->get('search', ''));
        $roleFilter = trim((string) $request->get('role', ''));
        $statusFilter = trim((string) $request->get('status', ''));
        $perPageInput = (int) $request->input('per_page', 10);

        $perPage = in_array(
            $perPageInput,
            [10, 20, 50, 100],
            true
        ) ? $perPageInput : 10;

        $query = User::with(['role', 'patient']);

        if ($visibleRoleIds === []) {
            $query->whereRaw('1 = 0');
        } else {
            $query->whereIn('role_id', $visibleRoleIds);
        }

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where(
                    'name',
                    'like',
                    "%{$search}%"
                )
                    ->orWhere(
                        'email',
                        'like',
                        "%{$search}%"
                    )
                    ->orWhereHas(
                        'patient',
                        function ($patientQuery) use ($search) {
                            $patientQuery->where(
                                'name',
                                'like',
                                "%{$search}%"
                            );
                        }
                    );
            });
        }

        if ($roleFilter !== '') {
            $query->whereHas('role', function ($q) use ($roleFilter) {
                $q->where('slug', $roleFilter)
                    ->orWhere('name', $roleFilter);
            });
        }

        if ($statusFilter !== '') {
            $query->where('status', $statusFilter);
        }

        $users = $query->latest()->paginate($perPage)->withQueryString();

        $notifications = collect([]);

        $countQuery = User::query();

        if ($visibleRoleIds === []) {
            $countQuery->whereRaw('1 = 0');
        } else {
            $countQuery->whereIn('role_id', $visibleRoleIds);
        }

        $allUsersCount = (clone $countQuery)->count();
        $adminCount = (clone $countQuery)->whereHas('role', function ($q) {
            $q->where('slug', 'super_admin')->orWhere('name', 'super_admin');
        })->count();

        $dentistCount = (clone $countQuery)->whereHas('role', function ($q) {
            $q->where('slug', 'dentist')->orWhere('name', 'dentist');
        })->count();

        $patientCount = (clone $countQuery)->whereHas('role', function ($q) {
            $q->where('slug', 'patient')->orWhere('name', 'patient');
        })->count();

        $activeCount = (clone $countQuery)->where('status', 'active')->count();
        $inactiveCount = (clone $countQuery)->where('status', 'inactive')->count();

        // AuditLogger::log(
        //     'view',
        //     'user_management',
        //     'Admin viewed user management'
        // );

        if ($request->ajax()) {
            return response()->json([
                'users' => $users->getCollection()
                    ->map(fn($user) => $this->formatUserForResponse($user))
                    ->values(),
                'pagination' => [
                    'total' => $users->total(),
                    'from' => $users->firstItem() ?? 0,
                    'to' => $users->lastItem() ?? 0,
                    'current_page' => $users->currentPage(),
                    'last_page' => $users->lastPage(),
                    'per_page' => $users->perPage(),
                ],
                'counts' => [
                    'all' => $allUsersCount,
                    'admin' => $adminCount,
                    'dentist' => $dentistCount,
                    'patient' => $patientCount,
                    'active' => $activeCount,
                    'inactive' => $inactiveCount,
                    'roles' => $roleCounts,
                ],
            ]);
        }

        return view('admin.user-management', compact(
            'users',
            'roles',
            'notifications',
            'allUsersCount',
            'adminCount',
            'dentistCount',
            'patientCount',
            'activeCount',
            'inactiveCount',
            'roleCounts',
            'perPage',
            'search',
            'roleFilter',
            'statusFilter'
        ) + [
            'layoutRole' => request()->routeIs('dentist.user_management*') ? 'dentist' : 'admin',
            'routeNames' => [
                'index' => $this->routeName('index'),
                'store' => $this->routeName('store'),
                'update' => $this->routeName('update'),
                'reset_password' => $this->routeName('reset_password'),
                'toggle_status' => $this->routeName('toggle_status'),
            ],
        ]);
    }

    public function store(Request $request)
    {
        $this->authorizeUserManagementAccess('create_users');

        $request->merge([
            'phone' => $this->normalizePhoneNumber($request->input('phone')),
        ]);

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-zÑñ\s.\'-]+$/u',
            ],
            'email' => 'required|email|unique:users,email|unique:patients,email',
            'role_id' => 'nullable|exists:roles,id',
            'password' => [
                'required',
                'string',
                'confirmed',

                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
            'status' => 'required|in:active,inactive',
            'phone' => ['nullable', 'regex:/^09\d{9}$/'],
            'birthdate' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'gender' => 'nullable|in:Male,Female',
        ], [
            'name.regex' => 'Full name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'birthdate.before_or_equal' => 'Birthdate cannot be in the future.',
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
        ]);

        $roleId = $this->resolveUserRoleId(
            $request->input('role_id')
        );

        $role = Role::findOrFail($roleId);
        $this->authorizeRoleAccountAccess($role);

        $request->merge([
            'role_id' => $roleId,
        ]);

        $user = DB::transaction(function () use ($request) {
            $role = Role::findOrFail($request->role_id);

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'birthdate' => $request->birthdate,
                'gender' => $request->gender,
                'password' => Hash::make((string) $request->password),
                'role_id' => $request->role_id,
                'status' => $request->status,
            ]);

            if ($role && $role->slug === 'patient') {
                Patient::create([
                    'user_id' => $user->id,
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone ?? '',
                    'birthdate' => $request->birthdate ?? now()->toDateString(),
                    'gender' => $request->gender ?? 'Male',
                    'password' => $user->password,
                ]);
            }

            return $user;
        });

        AuditLogger::log(
            'create',
            'user',
            "Created user #{$user->id} ({$user->email})"
        );

        return redirect()->route($this->routeName('index'))
            ->with('success', 'User created successfully.');
    }
    public function update(Request $request, User $user)
    {
        $this->authorizeUserManagementAccess('update_user_role');
        $this->authorizeManagedUserAccount($user);

        $request->merge([
            'phone' => $this->normalizePhoneNumber($request->input('phone')),
        ]);

        $validated = $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-zÑñ\s.\'-]+$/u',
            ],
            'email' => [
                'required',
                'email',
                Rule::unique('users')->ignore($user->id),
                Rule::unique('patients', 'email')->ignore(
                    optional(Patient::where('user_id', $user->id)->first())->id
                ),
            ],
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'required|in:active,inactive',
            'phone' => ['nullable', 'regex:/^09\d{9}$/'],
            'birthdate' => [
                'nullable',
                'date',
                'before_or_equal:today',
            ],
            'gender' => 'nullable|in:Male,Female',
        ], [
            'phone.regex' => 'Phone number must start with 09 and contain exactly 11 digits.',
            'name.regex' => 'Full name may only contain letters, spaces, apostrophes, periods, and hyphens.',
            'birthdate.before_or_equal' => 'Birthdate cannot be in the future.',
        ]);

        $originalRole = $user->role;
        $newRoleId = $this->resolveUserRoleId(
            $validated['role_id'] ?? null
        );

        $newRole = Role::findOrFail($newRoleId);
        $this->authorizeRoleAccountAccess($newRole);
        $roleChanged = (string) ($user->role_id ?? '') !== (string) ($newRoleId ?? '');

        if ($roleChanged) {
            $this->authorizeRoleChange($request, $user);
        }

        DB::transaction(function () use (
            $request,
            $user,
            $newRole,
            $newRoleId
        ) {
            $user->forceFill([
                'name' => $request->name,
                'email' => $request->email,
                'phone' => $request->phone,
                'birthdate' => $request->birthdate,
                'gender' => $request->gender,
                'role_id' => $newRoleId,
                'status' => $request->status,
            ])->save();

            $user->refresh();

            if ($newRole && $newRole->slug === 'patient') {
                $patient = Patient::firstOrNew(['user_id' => $user->id]);

                $patient->fill([
                    'name' => $request->name,
                    'email' => $request->email,
                    'phone' => $request->phone ?? ($patient->phone ?? ''),
                    'birthdate' => $request->birthdate ?? ($patient->birthdate ?? now()->toDateString()),
                    'gender' => $request->gender ?? ($patient->gender ?? 'Male'),
                    'password' => $user->password ?? $patient->password ?? Hash::make(\Str::random(16)),

                ]);

                $patient->user_id = $user->id;
                $patient->save();
            } else {
                Patient::where('user_id', $user->id)->delete();

                AuditLogger::log(
                    'update',
                    'user',
                    "Removed linked patient record for user #{$user->id}"
                );
            }
        });

        if ($roleChanged) {
            $this->concurrentSessionService->revokeAllSessions($user, null, 'role_changed');

            AuditLogger::log(
                'security',
                'user',
                sprintf(
                    'Role changed for user #%d from %s to %s after admin password confirmation',
                    $user->id,
                    optional($originalRole)->name ?? 'No Role',
                    optional($newRole)->name ?? 'No Role'
                )
            );
        }

        AuditLogger::log(
            'update',
            'user',
            "Updated user #{$user->id} ({$user->email})",
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'User updated successfully.',
            ]);
        }

        return back()->with('success', 'User updated successfully.');
    }

    private function authorizeRoleChange(Request $request, User $user): void
    {
        $this->authorizeUserManagementAccess('update_user_role');

        $actor = Auth::user();

        if (!$actor) {
            abort(403, 'Authentication is required before changing a user role.');
        }

        if ((int) $actor->id === (int) $user->id) {
            $request->validate([
                'role_id' => [
                    function ($attribute, $value, $fail) {
                        $fail('You cannot change your own role.');
                    },
                ],
            ]);
        }

        $request->validate([
            'admin_current_password' => ['required', 'string'],
        ], [
            'admin_current_password.required' => 'Enter your current admin password before changing a user role.',
        ]);

        if (!$actor->password || !Hash::check((string) $request->input('admin_current_password'), $actor->password)) {
            $request->validate([
                'admin_current_password' => [
                    function ($attribute, $value, $fail) {
                        $fail('The admin password confirmation is incorrect.');
                    },
                ],
            ]);
        }
    }

    public function resetPassword(Request $request, User $user)
    {
        $this->authorizeUserManagementAccess('update_user_password');
        $this->authorizeManagedUserAccount($user);

        $request->validate([
            'password' => [
                'required',
                'confirmed',

                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        DB::transaction(function () use ($request, $user) {
            $hashedPassword = Hash::make($request->password);
            $user->update(['password' => $hashedPassword]);

            $role = $user->role;
            if ($role && $role->slug === 'patient') {
                Patient::where('user_id', $user->id)->update(['password' => $hashedPassword]);
            }
        });

        $this->concurrentSessionService->revokeAllSessions($user, null, 'password_reset');

        AuditLogger::log('reset_password', 'user', "Reset password for user #{$user->id}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
            ]);
        }

        return redirect()->route($this->routeName('index'))
            ->with('success', 'Password reset successfully.');
    }

    public function toggleStatus(User $user)
    {
        $this->authorizeUserManagementAccess('disable_users');
        $this->authorizeManagedUserAccount($user);

        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

        if ($user->status === 'inactive') {
            $this->concurrentSessionService->revokeAllSessions($user, null, 'account_deactivated');
        }

        $label = $user->status === 'active' ? 'activated' : 'deactivated';

        AuditLogger::log('update', 'user', "Status changed: user #{$user->id} is now {$user->status}");

        if (request()->ajax() || request()->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => "{$user->name} has been {$label} successfully.",
                'status'  => $user->status,
            ]);
        }

        return back()->with('success', "{$user->name} has been {$label} successfully.");
    }

    public function updatePatient(Request $request, Patient $patient)
    {
        $this->authorizeUserManagementAccess('view_account_details');

        $request->validate([
            'name' => [
                'required',
                'string',
                'max:255',
                'regex:/^[A-Za-zÑñ\s.\'-]+$/u',
            ],
            'email' => 'required|email|unique:patients,email,' . $patient->id,
        ], [
            'name.regex' =>
            'Full name may only contain letters, spaces, apostrophes, periods, and hyphens.',
        ]);

        $patient->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route($this->routeName('index'))
            ->with('success', 'Patient updated successfully.');
    }

    public function resetPatientPassword(Request $request, Patient $patient)
    {
        $this->authorizeUserManagementAccess('update_user_password');

        $request->validate([
            'password' => [
                'required',
                'confirmed',

                Password::min(8)
                    ->mixedCase()
                    ->numbers()
                    ->symbols(),
            ],
        ]);

        $hashedPassword = Hash::make($request->password);

        $patient->update([
            'password' => $hashedPassword,
        ]);

        if ($patient->user) {
            $patient->user->update([
                'password' => $hashedPassword,
            ]);

            $user = $patient->user;
            $this->concurrentSessionService->revokeAllSessions($user, null, 'password_reset');

            AuditLogger::log(
                'reset_password',
                'user',
                "Reset password for user #{$user->id} via patient record"
            );
        } else {
            // Log anyway — even if no linked user (rare case)
            AuditLogger::log(
                'reset_password',
                'patient',
                "Reset password for standalone patient #{$patient->id}",
            );
        }

        return redirect()->route($this->routeName('index'))
            ->with('success', 'Patient password reset successfully.');
    }

    public function destroy(User $user)
    {
        $this->authorizeManagedUserAccount($user);

        $email = $user->email;

        $this->concurrentSessionService->revokeAllSessions($user, null, 'account_deleted');

        DB::transaction(function () use ($user) {
            Patient::where('user_id', $user->id)->delete();
            $user->delete(); // or ->forceDelete() if you don't use soft deletes
        });

        AuditLogger::log(
            'delete',
            'user',
            "Deleted user #{$user->id} ({$email})",
        );

        return redirect()->route($this->routeName('index'))
            ->with('success', 'User deleted successfully.');
    }

    private function authorizeAnyUserManagementAccess(): void
    {
        $user = Auth::user();

        abort_unless($user, 403);

        foreach (self::USER_MANAGEMENT_PERMISSIONS as $permission) {
            if ($user->hasPermission($permission)) {
                return;
            }
        }

        abort(403, 'Unauthorized.');
    }

    private function authorizeUserManagementAccess(string $permission): void
    {
        $user = Auth::user();

        abort_unless($user, 403);
        abort_unless($user->hasPermission($permission), 403, 'Unauthorized.');
    }

    private function authorizeManagedUserAccount(User $user): void
    {
        $role = $user->role;

        abort_unless($role, 403, 'Unauthorized.');

        $this->authorizeRoleAccountAccess($role);
    }

    private function authorizeRoleAccountAccess(Role $role): void
    {
        abort_unless($this->canManageRoleAccounts($role), 403, 'Unauthorized.');
    }

    private function canManageRoleAccounts(Role $role): bool
    {
        $user = Auth::user();

        if (!$user) {
            return false;
        }

        $roleKey = strtolower((string) ($role->slug ?: $role->name));

        return match ($roleKey) {
            'patient' => $user->hasAnyPermission(['view_account_details', 'create_users', 'disable_users', 'update_user_role', 'update_user_password']),
            'dentist' => $user->hasAnyPermission(['view_account_details', 'create_users', 'disable_users', 'update_user_role', 'update_user_password']),
            default => $user->hasAnyPermission(['update_user_role', 'update_user_password']),
        };
    }

    private function routeName(string $action): string
    {
        if (request()->routeIs('dentist.user_management*')) {
            return match ($action) {
                'index' => 'dentist.user_management',
                'store' => 'dentist.user_management.store',
                'update' => 'dentist.user_management.update',
                'reset_password' => 'dentist.user_management.reset_password',
                'toggle_status' => 'dentist.user_management.toggle_status',
            };
        }

        return match ($action) {
            'index' => 'admin.user_management',
            'store' => 'admin.user_management.store',
            'update' => 'admin.user_management.update',
            'reset_password' => 'admin.user_management.reset_password',
            'toggle_status' => 'admin.user_management.toggle_status',
        };
    }

    private function formatUserForResponse(User $user): array
    {
        $user->loadMissing(['role', 'patient']);

        $patient = $user->patient;
        $role = $user->role;
        $displayName =
            $patient?->name
            ?? $user->name;
        $displayRole = $role?->display_name ?? $role?->name ?? 'No Role';
        $phone = $patient?->phone ?: $user->phone;
        $birthdate = $patient?->birthdate ?: $user->birthdate;
        $gender = $patient?->gender ?: $user->gender;

        return [
            'id' => $user->id,
            'name' => $displayName,
            'email' => $user->email,
            'status' => $user->status,
            'role_id' => $user->role_id,
            'role_name' => $displayRole,
            'role_slug' => $role?->slug ?? '',
            'created_at_day' => optional($user->created_at)?->format('M d, Y'),
            'created_at_time' => optional($user->created_at)?->format('h:i A'),
            'details' => [
                'id' => $user->id,
                'name' => $displayName,
                'email' => $user->email,
                'role' => $displayRole,
                'status' => ucfirst((string) $user->status),
                'source' => 'Users',
                'created_at' => optional($user->created_at)?->format('M d, Y h:i A') ?? 'N/A',
                'updated_at' => optional($user->updated_at)?->format('M d, Y h:i A') ?? 'N/A',
                'phone' => $phone ?: 'N/A',
                'phone_raw' => $phone ?: '',
                'birthdate' => $birthdate?->format('M d, Y') ?? 'N/A',
                'birthdate_raw' => $birthdate?->format('Y-m-d') ?? '',
                'gender' => $gender ?: 'N/A',
                'gender_raw' => $gender ?: '',
                'patient_profile' => $patient ? 'Linked' : 'Not linked',
                'last_login_at' => optional($user->last_login_at)?->format('M d, Y h:i A') ?? 'Never',
            ],
        ];
    }

    private function resolveUserRoleId(mixed $roleId): int
    {
        if (!empty($roleId)) {
            return (int) $roleId;
        }

        $patientRoleId = Role::query()
            ->where('slug', 'patient')
            ->orWhereRaw('LOWER(name) = ?', ['patient'])
            ->value('id');

        abort_unless(
            $patientRoleId,
            422,
            'The default Patient role is not configured.'
        );

        return (int) $patientRoleId;
    }

    private function generateRandomPassword(): string
    {
        $lower = 'abcdefghijkmnopqrstuvwxyz';
        $upper = 'ABCDEFGHJKLMNPQRSTUVWXYZ';
        $numbers = '23456789';
        $symbols = '@#$%*!?';

        $password = [
            $lower[random_int(0, strlen($lower) - 1)],
            $upper[random_int(0, strlen($upper) - 1)],
            $numbers[random_int(0, strlen($numbers) - 1)],
            $symbols[random_int(0, strlen($symbols) - 1)],
        ];

        $all = $lower . $upper . $numbers . $symbols;

        while (count($password) < self::PASSWORD_LENGTH) {
            $password[] = $all[random_int(0, strlen($all) - 1)];
        }

        shuffle($password);

        return implode('', $password);
    }

    private function normalizePhoneNumber(mixed $value): ?string
    {
        $digits = preg_replace('/\D+/', '', (string) $value);

        return $digits !== '' ? $digits : null;
    }
}
