<?php

namespace App\Http\Controllers\Admin;

use Illuminate\Support\Facades\DB;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Role;
use App\Models\Patient;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;
use App\Helpers\AuditLogger;

class UserManagementController extends Controller
{
    public function index(Request $request)
    {
        $roles = Role::withCount('users')->orderBy('name')->get();
        $roleCounts = $roles->mapWithKeys(fn ($role) => [$role->slug => $role->users_count]);

        $search = trim((string) $request->get('search', ''));
        $roleFilter = trim((string) $request->get('role', ''));
        $statusFilter = trim((string) $request->get('status', ''));
        $perPage = (int) $request->get('per_page', 10);
        $perPage = in_array($perPage, [10, 25, 50, 100]) ? $perPage : 10;

        $query = User::with(['role', 'patient']);

        if ($search !== '') {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%");
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

        $allUsersCount = User::count();
        $adminCount = User::whereHas('role', function ($q) {
            $q->where('slug', 'super_admin')->orWhere('name', 'super_admin');
        })->count();

        $dentistCount = User::whereHas('role', function ($q) {
            $q->where('slug', 'dentist')->orWhere('name', 'dentist');
        })->count();

        $patientCount = User::whereHas('role', function ($q) {
            $q->where('slug', 'patient')->orWhere('name', 'patient');
        })->count();

        $activeCount = User::where('status', 'active')->count();
        $inactiveCount = User::where('status', 'inactive')->count();

        // AuditLogger::log(
        //     'view',
        //     'user_management',
        //     'Admin viewed user management'
        // );

        if ($request->ajax()) {
            return response()->json([
                'users' => $users->getCollection()->map(function ($user) {
                    return [
                        'id' => $user->id,
                        'name' => $user->name,
                        'email' => $user->email,
                        'status' => $user->status,
                        'role_id' => $user->role_id,
                        'role_name' => optional($user->role)->name ?? '—',
                        'role_slug' => optional($user->role)->slug ?? '',
                        'created_at_day' => optional($user->created_at)?->format('M d, Y'),
                        'created_at_time' => optional($user->created_at)?->format('h:i A'),
                    ];
                })->values(),
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
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:users,email|unique:patients,email',
            'password' => 'required|min:8|confirmed',
            'role_id' => 'nullable|exists:roles,id',
            'status' => 'required|in:active,inactive',
            'phone' => 'nullable|string|max:20',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female',
        ]);

        $user = DB::transaction(function () use ($request) {
            $role = $request->role_id ? Role::find($request->role_id) : null;

            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'password' => Hash::make($request->password),
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

        return redirect()->route('admin.user_management')
            ->with('success', 'User created successfully.');
    }
    public function update(Request $request, User $user)
    {
        $request->validate([
            'name' => 'required|string|max:255',
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
            'phone' => 'nullable|string|max:20',
            'birthdate' => 'nullable|date',
            'gender' => 'nullable|in:Male,Female',
        ]);

        DB::transaction(function () use ($request, $user) {
            $role = $request->role_id ? Role::find($request->role_id) : null;

            $user->update([
                'name' => $request->name,
                'email' => $request->email,
                'role_id' => $request->role_id,
                'status' => $request->status,
            ]);

            if ($role && $role->slug === 'patient') {
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

    public function resetPassword(Request $request, User $user)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        DB::transaction(function () use ($request, $user) {
            $hashedPassword = Hash::make($request->password);
            $user->update(['password' => $hashedPassword]);

            $role = $user->role;
            if ($role && $role->slug === 'patient') {
                Patient::where('user_id', $user->id)->update(['password' => $hashedPassword]);
            }
        });

        AuditLogger::log('reset_password', 'user', "Reset password for user #{$user->id}");

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Password reset successfully.',
            ]);
        }

        return redirect()->route('admin.user_management')
            ->with('success', 'Password reset successfully.');
    }

    public function toggleStatus(User $user)
    {
        $user->status = $user->status === 'active' ? 'inactive' : 'active';
        $user->save();

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
        $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'required|email|unique:patients,email,' . $patient->id,
        ]);

        $patient->update([
            'name' => $request->name,
            'email' => $request->email,
        ]);

        return redirect()->route('admin.user_management')
            ->with('success', 'Patient updated successfully.');
    }

    public function resetPatientPassword(Request $request, Patient $patient)
    {
        $request->validate([
            'password' => 'required|min:8|confirmed',
        ]);

        $hashedPassword = Hash::make($request->password);

        $patient->update([
            'password' => $hashedPassword,
        ]);

        // If the patient is linked to a user, also update the user's password (consistency)
        if ($patient->user) {
            $patient->user->update([
                'password' => $hashedPassword,
            ]);

            $user = $patient->user;

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

        return redirect()->route('admin.user_management')
            ->with('success', 'Patient password reset successfully.');
    }

    public function destroy(User $user)
    {
        $email = $user->email;

        DB::transaction(function () use ($user) {
            Patient::where('user_id', $user->id)->delete();
            $user->delete(); // or ->forceDelete() if you don't use soft deletes
        });

        AuditLogger::log(
            'delete',
            'user',
            "Deleted user #{$user->id} ({$email})",
        );

        return redirect()->route('admin.user_management')
            ->with('success', 'User deleted successfully.');
    }
}
