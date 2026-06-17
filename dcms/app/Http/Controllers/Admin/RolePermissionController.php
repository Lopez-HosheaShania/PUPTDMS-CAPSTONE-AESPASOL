<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use App\Helpers\AuditLogger;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class RolePermissionController extends Controller
{
    public function index(Request $request)
    {
        $this->seedDefaultsIfEmpty();

        $roles = Role::with('permissions')->get();
        $permissions = Permission::orderBy('module')->orderBy('name')->get();
        $groupedPermissions = $permissions->groupBy('module');

        $highlightRoleId = session('new_role_id') ?? $request->query('highlight_role');

        AuditLogger::log(
            'view',
            'roles_permissions',
            'Admin viewed roles and permissions'
        );

        return view('admin.role-permissions', compact('roles', 'groupedPermissions', 'highlightRoleId'));
    }

    private function seedDefaultsIfEmpty(): void
    {
        $coreRoles = ['admin', 'dentist', 'patient'];

        foreach ($coreRoles as $slug) {
            $role = Role::where('slug', $slug)->first();
            if ($role && $role->permissions()->count() === 0) {
                $this->applyDefaults($role, $slug);
            }
        }
    }

    private function applyDefaults(Role $role, string $slug): void
    {
        $map = [
            'admin' => [
                'access_super_admin_dashboard',
                'access_patient_dashboard',
                'receive_notifications',
                'manage_user_accounts',
                'manage_user_roles',
                'manage_dentist_accounts',
                'manage_super_admin_accounts',
                'manage_system_settings',
                'manage_audit_trail',
                'manage_backup',
                'manage_document_templates',
                'manage_reports',
                'manage_patient_profiles',
                'manage_appointments',
                'manage_dental_records',
                'set_academic_year',
                'set_archive_records',
                'set_report_periods',
                'set_required_fields',
                'set_appointment_limit',
                'set_notification_rules',
                'set_export_file_type',
            ],
            'dentist' => [
                'access_dentist_dashboard',
                'receive_notifications',
                'manage_dental_records',
                'manage_appointments',
                'manage_patient_profiles',
                'manage_inventory',
                'manage_reports',
                'manage_document_requests',
            ],
            'patient' => [
                'access_patient_dashboard',
                'receive_notifications',
                'book_appointments',
                'view_own_appointments',
                'view_own_profile',
                'view_own_records',
                'request_documents',
            ],
        ];

        if (!isset($map[$slug])) return;

        $ids = Permission::whereIn('slug', $map[$slug])->pluck('id');
        $role->permissions()->sync($ids);
    }

    public function update(Request $request)
    {
        $request->validate([
            'role_id' => ['required', 'exists:roles,id'],
        ]);

        $role = Role::findOrFail($request->role_id);

        if ($request->expectsJson()) {
            $permissionIds = array_map('intval', $request->input('permissions', []));
        } else {
            $permissionIds = array_map('intval', $request->input("permissions.{$role->id}", []));
        }

        $role->permissions()->sync($permissionIds);

        AuditLogger::log(
            'update',
            'roles_permissions',
            "Admin updated permissions for role ID {$role->id} ({$role->name})"
        );

        if ($request->expectsJson()) {
            $savedPermissions = Permission::whereIn('id', $permissionIds)
                ->get(['id', 'name', 'slug', 'module'])
                ->map(fn($p) => [
                    'id'     => $p->id,
                    'name'   => $p->name,
                    'slug'   => $p->slug,
                    'module' => $p->module,
                ])
                ->values()
                ->toArray();

            return response()->json([
                'success'     => true,
                'message'     => "Permissions for \"{$role->name}\" updated successfully.",
                'role_id'     => $role->id,
                'role_name'   => $role->name,
                'permissions' => $savedPermissions,
            ]);
        }

        $savedPermissions = Permission::whereIn('id', $permissionIds)
            ->get(['id', 'name', 'slug', 'module'])
            ->map(fn($p) => ['id' => $p->id, 'name' => $p->name, 'slug' => $p->slug, 'module' => $p->module])
            ->values()
            ->toArray();

        return redirect()
            ->route('admin.role_permissions')
            ->with('success', 'Role permissions updated successfully.')
            ->with('saved_view_as', [
                'role_id'     => $role->id,
                'role_name'   => $role->name,
                'permissions' => $savedPermissions,
            ]);
    }

    public function reset()
    {
        $admin = Role::where('slug', 'admin')->firstOrFail();
        $dentist    = Role::where('slug', 'dentist')->firstOrFail();
        $patient    = Role::where('slug', 'patient')->firstOrFail();

        $superAdminPermissions = Permission::whereIn('slug', [
            'access_super_admin_dashboard',
            'access_dentist_dashboard',
            'access_patient_dashboard',
            'receive_notifications',
            'manage_system_settings',
            'manage_audit_trail',
            'manage_user_accounts',
            'manage_user_roles',
            'manage_dentist_accounts',
            'manage_super_admin_accounts',
            'manage_document_templates',
            'manage_reports',
            'manage_patient_profiles',
            'manage_appointments',
            'manage_inventory',
            'manage_backup',
            'set_academic_year',
            'set_archive_records',
            'set_report_periods',
            'set_required_fields',
            'set_appointment_limit',
            'set_notification_rules',
            'set_export_file_type',
        ])->pluck('id');

        $dentistPermissions = Permission::whereIn('slug', [
            'access_dentist_dashboard',
            'receive_notifications',
            'manage_dental_records',
            'manage_appointments',
            'manage_patient_profiles',
            'manage_inventory',
            'manage_reports',
            'manage_document_requests',
        ])->pluck('id');

        $patientPermissions = Permission::whereIn('slug', [
            'access_patient_dashboard',
            'receive_notifications',
            'book_appointments',
            'view_own_appointments',
            'view_own_profile',
            'view_own_records',
            'request_documents',
        ])->pluck('id');

        $admin->permissions()->sync($superAdminPermissions);
        $dentist->permissions()->sync($dentistPermissions);
        $patient->permissions()->sync($patientPermissions);

        AuditLogger::log
            ('update', 
            'roles_permissions', 
            'Admin reset all permissions to defaults');

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Default permissions restored.',
            ]);
        }

        return back()->with('success', 'Default permissions restored.');
    }

    public function storeRole(Request $request)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255', 'unique:roles,name'],
            'slug' => [
                'required',
                'string',
                'max:255',
                'unique:roles,slug',
                'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/'
            ],
            'user_name' => ['nullable', 'required_with:user_email', 'string', 'max:255'],
            'user_email' => ['nullable', 'required_with:user_name', 'email', 'max:255', 'unique:users,email', 'unique:patients,email'],
        ], [
            'name.unique' => 'A role with this name already exists.',
            'slug.unique' => 'A role with this slug already exists.',
            'slug.regex'  => 'Slug may only contain lowercase letters, numbers, hyphens, and underscores.',
            'user_name.required_with' => 'Please enter the user name when adding a user email.',
            'user_email.required_with' => 'Please enter the user email when adding a user name.',
            'user_email.unique' => 'A user with this email already exists.',
        ]);

        [$role, $user] = DB::transaction(function () use ($request) {
            $role = Role::create([
                'name' => $request->name,
                'slug' => $request->slug,
            ]);

            $user = null;

            if ($request->filled('user_name') && $request->filled('user_email')) {
                $user = User::create([
                    'name' => $request->user_name,
                    'email' => $request->user_email,
                    'password' => Hash::make(Str::random(16)),
                    'role_id' => $role->id,
                    'status' => 'active',
                ]);
            }

            return [$role, $user];
        });

        $message = $user
            ? "Role \"{$role->name}\" and user \"{$user->name}\" created successfully. The user is now visible in User Management."
            : "Role \"{$role->name}\" created successfully. You can now assign permissions and use it in User Management.";

        AuditLogger::log(
            'create',
            'roles_permissions',
            "Admin created role ID {$role->id} ({$role->name})"
        );

        if ($user) {
            AuditLogger::log(
                'create',
                'user',
                "Created user #{$user->id} ({$user->email}) for role ID {$role->id}"
            );
        }

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                ],
                'user' => $user ? [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role_id' => $user->role_id,
                ] : null,
                'user_management_url' => route('admin.user_management'),
            ], 201);
        }

        return redirect()
            ->route('admin.role_permissions', ['highlight_role' => $role->id])
            ->with('success', $message)
            ->with('new_role_id', $role->id);
    }

    public function destroyRole($id)
    {
        $role = Role::findOrFail($id);

        if (
            in_array(strtolower($role->slug), ['super_admin', 'super-admin', 'superadmin']) ||
            str_contains(strtolower($role->name), 'super')
        ) {
            return redirect()->route('admin.role_permissions')
                ->with('error', 'Cannot delete the Super Admin role.');
        }

        $role->permissions()->detach();
        $role->delete();

        return redirect()->route('admin.role_permissions')
            ->with('success', "Role '{$role->name}' has been deleted.");
    }
}
