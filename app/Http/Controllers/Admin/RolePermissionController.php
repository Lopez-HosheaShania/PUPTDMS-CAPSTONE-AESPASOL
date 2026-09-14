<?php

namespace App\Http\Controllers\Admin;

use App\Helpers\AuditLogger;
use App\Http\Controllers\Controller;
use App\Models\Permission;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class RolePermissionController extends Controller
{
    private const CORE_ROLE_SLUGS = ['admin', 'dentist', 'patient'];

    private const ADMIN_ONLY_HIDDEN_PERMISSION_SLUGS = [
        'view_roles_permissions',
        'create_custom_roles',
        'update_role_permissions',
        'delete_custom_roles',
    ];

    private const LEGACY_DENTIST_DEFAULT_PERMISSION_SLUGS = [
        'access_dentist_dashboard',
        'view_patient_profiles',
        'view_appointments',
        'reschedule_appointments',
        'cancel_appointments',
        'view_clinic_schedule',
        'update_clinic_schedule',
        'create_clinic_schedule',
        'delete_clinic_schedule',
        'view_inventory',
        'add_inventory',
        'update_inventory',
        'delete_inventory',
        'view_reports',
        'create_report_files',
    ];

    private const LEGACY_DENTIST_DOCUMENT_REQUEST_BACKFILL_SLUGS = [
        'access_dentist_dashboard',
        'view_patient_profiles',
        'view_appointments',
        'reschedule_appointments',
        'cancel_appointments',
        'manage_walk_in_patients',
        'manage_existing_records',
        'view_document_requests',
        'view_clinic_schedule',
        'update_clinic_schedule',
        'create_clinic_schedule',
        'delete_clinic_schedule',
        'view_inventory',
        'add_inventory',
        'update_inventory',
        'delete_inventory',
        'view_reports',
        'create_report_files',
    ];

    private const LEGACY_PERMISSION_MIGRATIONS = [
        'create_delete_clinic_schedule' => [
            'create_clinic_schedule',
            'delete_clinic_schedule',
        ],
        'view_service_types' => [
            'view_service_type',
        ],
        'create_delete_custom_service_types' => [
            'create_service_type',
            'delete_service_type',
        ],
        'update_service_types' => [
            'update_default_service_type',
        ],
        'create_delete_academic_period' => [
            'create_academic_period',
            'delete_academic_period',
        ],
        'manage_inventory' => [
            'view_inventory',
            'add_inventory',
            'update_inventory',
            'delete_inventory',
        ],
        'manage_inventory_items' => [
            'add_inventory',
            'update_inventory',
            'delete_inventory',
        ],
        'create_disable_users' => [
            'create_users',
            'disable_users',
        ],
        'update_role_password' => [
            'update_user_role',
            'update_user_password',
        ],
    ];

    private const REMOVED_PERMISSION_SLUGS = [
        'manage_super_admin_accounts',
        'manage_document_requests',
        'manage_reports',
        'manage_clinic_schedule',
        'manage_user_accounts',
        'manage_user_roles',
        'manage_dentist_accounts',
        'set_academic_year',
        'manage_cms_users',
        'set_report_periods',
        'set_required_fields',
        'set_export_file_type',
        'create_delete_clinic_schedule',
        'view_service_types',
        'create_delete_custom_service_types',
        'update_service_types',
        'create_delete_academic_period',
        'manage_inventory',
        'manage_inventory_items',
        'create_disable_users',
        'update_role_password',
        'manage_appointments',
        'view_appointment_details',
        'manage_audit_trail',
        'set_archive_records',
        'update_cms_integration',
        'update_faculty_integration',
    ];

    private const REQUIRED_PERMISSIONS = [
        ['name' => 'Access Super Admin Dashboard', 'slug' => 'access_super_admin_dashboard', 'module' => 'General Access'],
        ['name' => 'Access Dentist Dashboard', 'slug' => 'access_dentist_dashboard', 'module' => 'General Access'],
        ['name' => 'Access Patient Dashboard', 'slug' => 'access_patient_dashboard', 'module' => 'General Access'],
        ['name' => 'Receive Notifications', 'slug' => 'receive_notifications', 'module' => 'General Access'],

        ['name' => 'Manage System Settings', 'slug' => 'manage_system_settings', 'module' => 'System Settings'],
        ['name' => 'Set Appointment Limit', 'slug' => 'set_appointment_limit', 'module' => 'System Settings'],
        ['name' => 'Set Notification Rules', 'slug' => 'set_notification_rules', 'module' => 'System Settings'],

        ['name' => 'View System Logs', 'slug' => 'view_system_logs', 'module' => 'System Logs'],
        ['name' => 'Export System Logs', 'slug' => 'export_system_logs', 'module' => 'System Logs'],
        ['name' => 'Archive System Logs', 'slug' => 'archive_system_logs', 'module' => 'System Logs'],

        ['name' => 'View Account Details', 'slug' => 'view_account_details', 'module' => 'User Management'],
        ['name' => 'Create Users', 'slug' => 'create_users', 'module' => 'User Management'],
        ['name' => 'Disable Users', 'slug' => 'disable_users', 'module' => 'User Management'],
        ['name' => 'Update User Role', 'slug' => 'update_user_role', 'module' => 'User Management'],
        ['name' => 'Update User Password', 'slug' => 'update_user_password', 'module' => 'User Management'],

        ['name' => 'View Roles & Permissions', 'slug' => 'view_roles_permissions', 'module' => 'Role Permissions'],
        ['name' => 'Create Custom Roles', 'slug' => 'create_custom_roles', 'module' => 'Role Permissions'],
        ['name' => 'Update Role Permissions', 'slug' => 'update_role_permissions', 'module' => 'Role Permissions'],
        ['name' => 'Delete Custom Roles', 'slug' => 'delete_custom_roles', 'module' => 'Role Permissions'],

        ['name' => 'View Faculty Integration', 'slug' => 'view_faculty_integration', 'module' => 'Faculty Integration'],
        ['name' => 'Create Faculty Integration', 'slug' => 'create_faculty_integration', 'module' => 'Faculty Integration'],

        ['name' => 'View CMS Integration', 'slug' => 'view_cms_integration', 'module' => 'CMS Integration'],
        ['name' => 'Create CMS Integration', 'slug' => 'create_cms_integration', 'module' => 'CMS Integration'],

        ['name' => 'View Dentist Transitions', 'slug' => 'view_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Create Dentist Transitions', 'slug' => 'create_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Update Dentist Transitions', 'slug' => 'update_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Assign Dentist Successors', 'slug' => 'assign_dentist_successors', 'module' => 'Dentist Continuity'],
        ['name' => 'Finalize Dentist Transitions', 'slug' => 'finalize_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Cancel Dentist Transitions', 'slug' => 'cancel_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Extend Dentist Access', 'slug' => 'extend_dentist_access', 'module' => 'Dentist Continuity'],
        ['name' => 'View Dentist Transition Audit Logs', 'slug' => 'view_dentist_transition_audit_logs', 'module' => 'Dentist Continuity'],

        ['name' => 'Manage Document Templates', 'slug' => 'manage_document_templates', 'module' => 'Document Templates'],

        ['name' => 'View Service Type', 'slug' => 'view_service_type', 'module' => 'Service Types'],
        ['name' => 'Create Service Type', 'slug' => 'create_service_type', 'module' => 'Service Types'],
        ['name' => 'Delete Service Type', 'slug' => 'delete_service_type', 'module' => 'Service Types'],
        ['name' => 'Update Default Service Type', 'slug' => 'update_default_service_type', 'module' => 'Service Types'],

        ['name' => 'View Reports', 'slug' => 'view_reports', 'module' => 'Reports'],
        ['name' => 'Create Report Files', 'slug' => 'create_report_files', 'module' => 'Reports'],
        ['name' => 'View AI Reports', 'slug' => 'view_ai_reports', 'module' => 'Reports'],
        ['name' => 'Create AI Generative Reports', 'slug' => 'create_ai_generative_reports', 'module' => 'Reports'],

        ['name' => 'View Inventory', 'slug' => 'view_inventory', 'module' => 'Inventory'],
        ['name' => 'Add Inventory', 'slug' => 'add_inventory', 'module' => 'Inventory'],
        ['name' => 'Update Inventory', 'slug' => 'update_inventory', 'module' => 'Inventory'],
        ['name' => 'Delete Inventory', 'slug' => 'delete_inventory', 'module' => 'Inventory'],

        ['name' => 'View Academic Periods/PUP Calendar/Time', 'slug' => 'view_academic_periods', 'module' => 'Academic Period'],
        ['name' => 'Update Academic Period', 'slug' => 'update_academic_period', 'module' => 'Academic Period'],
        ['name' => 'Create Academic Period', 'slug' => 'create_academic_period', 'module' => 'Academic Period'],
        ['name' => 'Delete Academic Period', 'slug' => 'delete_academic_period', 'module' => 'Academic Period'],

        ['name' => 'View Dental Records', 'slug' => 'view_dental_records', 'module' => 'Dental Records'],
        ['name' => 'Manage Dental Records', 'slug' => 'manage_dental_records', 'module' => 'Dental Records'],
        ['name' => 'Create Procedure Records', 'slug' => 'create_procedure_records', 'module' => 'Dental Records'],
        ['name' => 'Create Dental Records', 'slug' => 'create_dental_records', 'module' => 'Dental Records'],
        ['name' => 'Create Medical Records', 'slug' => 'create_medical_records', 'module' => 'Dental Records'],
        ['name' => 'Create Odontograms', 'slug' => 'create_odontograms', 'module' => 'Dental Records'],
        ['name' => 'Update Odontograms', 'slug' => 'update_odontograms', 'module' => 'Dental Records'],

        ['name' => 'View Appointments', 'slug' => 'view_appointments', 'module' => 'Appointments'],
        ['name' => 'Reschedule Appointments', 'slug' => 'reschedule_appointments', 'module' => 'Appointments'],
        ['name' => 'Cancel Appointments', 'slug' => 'cancel_appointments', 'module' => 'Appointments'],
        ['name' => 'Create Follow-Up Appointments', 'slug' => 'create_follow_up_appointments', 'module' => 'Appointments'],
        ['name' => 'Manage Walk-in Patients', 'slug' => 'manage_walk_in_patients', 'module' => 'Appointments'],
        ['name' => 'Add Existing Record', 'slug' => 'manage_existing_records', 'module' => 'Appointments'],
        ['name' => 'Book Appointments', 'slug' => 'book_appointments', 'module' => 'Appointments'],
        ['name' => 'View Own Appointments', 'slug' => 'view_own_appointments', 'module' => 'Appointments'],

        ['name' => 'View Schedule and Dates', 'slug' => 'view_clinic_schedule', 'module' => 'Clinic Schedule'],
        ['name' => 'Update Clinic Hours', 'slug' => 'update_clinic_schedule', 'module' => 'Clinic Schedule'],
        ['name' => 'Create Clinic Hours', 'slug' => 'create_clinic_schedule', 'module' => 'Clinic Schedule'],
        ['name' => 'Delete Clinic Hours', 'slug' => 'delete_clinic_schedule', 'module' => 'Clinic Schedule'],

        ['name' => 'View Patient Profiles', 'slug' => 'view_patient_profiles', 'module' => 'Patients'],
        ['name' => 'Manage Patient Profiles', 'slug' => 'manage_patient_profiles', 'module' => 'Patients'],
        ['name' => 'View Own Profile', 'slug' => 'view_own_profile', 'module' => 'Patients'],

        ['name' => 'View Document Requests', 'slug' => 'view_document_requests', 'module' => 'Document Requests'],
        ['name' => 'Approve Document Requests', 'slug' => 'approve_document_requests', 'module' => 'Document Requests'],
        ['name' => 'Reject Document Requests', 'slug' => 'reject_document_requests', 'module' => 'Document Requests'],
        ['name' => 'Request Documents', 'slug' => 'request_documents', 'module' => 'Document Requests'],

        ['name' => 'View Own Records', 'slug' => 'view_own_records', 'module' => 'Dental Records'],
    ];

    private const DEFAULT_ROLE_PERMISSIONS = [
        'admin' => [
            'access_super_admin_dashboard',
            'receive_notifications',
            'manage_system_settings',
            'set_notification_rules',
            'view_system_logs',
            'export_system_logs',
            'archive_system_logs',
            'view_account_details',
            'create_users',
            'disable_users',
            'update_user_role',
            'update_user_password',
            'view_roles_permissions',
            'create_custom_roles',
            'update_role_permissions',
            'delete_custom_roles',
            'view_dentist_transitions',
            'create_dentist_transitions',
            'update_dentist_transitions',
            'assign_dentist_successors',
            'finalize_dentist_transitions',
            'cancel_dentist_transitions',
            'extend_dentist_access',
            'manage_document_templates',
            'view_service_type',
            'create_service_type',
            'delete_service_type',
            'update_default_service_type',
            'view_ai_reports',
            'create_ai_generative_reports',
            'view_inventory',
            'add_inventory',
            'update_inventory',
            'delete_inventory',
            'view_patient_profiles',
            'view_dental_records',
            'view_appointments',
            'reschedule_appointments',
            'cancel_appointments',
            'view_clinic_schedule',
            'update_clinic_schedule',
            'create_clinic_schedule',
            'delete_clinic_schedule',
            'view_document_requests',
            'approve_document_requests',
            'reject_document_requests',
            'view_academic_periods',
            'update_academic_period',
            'create_academic_period',
            'delete_academic_period',
            'view_faculty_integration',
            'create_faculty_integration',
            'view_cms_integration',
            'create_cms_integration',
        ],
        'dentist' => [
            'access_dentist_dashboard',
            'receive_notifications',
            'view_patient_profiles',
            'view_appointments',
            'reschedule_appointments',
            'cancel_appointments',
            'create_follow_up_appointments',
            'manage_walk_in_patients',
            'manage_existing_records',
            'create_procedure_records',
            'create_dental_records',
            'create_medical_records',
            'create_odontograms',
            'update_odontograms',
            'view_clinic_schedule',
            'update_clinic_schedule',
            'create_clinic_schedule',
            'delete_clinic_schedule',
            'view_document_requests',
            'approve_document_requests',
            'reject_document_requests',
            'request_documents',
            'view_inventory',
            'add_inventory',
            'update_inventory',
            'delete_inventory',
            'view_service_type',
            'create_service_type',
            'delete_service_type',
            'update_default_service_type',
            'view_reports',
            'create_report_files',
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

    public function index(Request $request)
    {
        $this->ensureRequiredPermissionsExist();
        $this->seedDefaultsIfEmpty();
        $this->synchronizeAdminOnlyPermissions();

        $roles = Role::with('permissions')->get();

        $permissions = Permission::where('slug', '!=', 'manage_backup')
            ->whereNotIn('slug', self::REMOVED_PERMISSION_SLUGS)
            ->whereNotIn('slug', self::ADMIN_ONLY_HIDDEN_PERMISSION_SLUGS)
            ->orderBy('module')
            ->orderBy('name')
            ->get();

        $groupedPermissions = $permissions->groupBy('module');

        $highlightRoleId = session('new_role_id') ?? $request->query('highlight_role');

        $authUser = $request->user();

        $canViewAsRole = $authUser?->hasPermission('access_super_admin_dashboard') ?? false;
        $canCreateRoles = $authUser?->hasPermission('create_custom_roles') ?? false;
        $canUpdateRolePermissions = $authUser?->hasPermission('update_role_permissions') ?? false;
        $canDeleteCustomRoles = $authUser?->hasPermission('delete_custom_roles') ?? false;

        AuditLogger::log(
            'view',
            'roles_permissions',
            'Admin viewed roles and permissions'
        );

        return view('admin.role-permissions', compact(
            'roles',
            'groupedPermissions',
            'highlightRoleId',
            'canViewAsRole',
            'canCreateRoles',
            'canUpdateRolePermissions',
            'canDeleteCustomRoles'
        ));
    }

    private function seedDefaultsIfEmpty(): void
    {
        foreach (self::CORE_ROLE_SLUGS as $slug) {
            $role = Role::where('slug', $slug)->first();

            if ($role && $role->permissions()->count() === 0) {
                $this->applyDefaults($role, $slug);
            }
        }
    }

    private function ensureRequiredPermissionsExist(): void
    {
        $recreatedDefaultPermissionIds = [];

        foreach (self::REQUIRED_PERMISSIONS as $permissionData) {
            $permission = Permission::updateOrCreate(
                ['slug' => $permissionData['slug']],
                $permissionData
            );

            if (! $permission->wasRecentlyCreated) {
                continue;
            }

            foreach (self::DEFAULT_ROLE_PERMISSIONS as $roleSlug => $defaultPermissionSlugs) {
                if (! in_array($permission->slug, $defaultPermissionSlugs, true)) {
                    continue;
                }

                $recreatedDefaultPermissionIds[$roleSlug][] = (int) $permission->id;
            }
        }

        $this->migrateLegacyPermissionAssignments();

        Permission::whereIn('slug', self::REMOVED_PERMISSION_SLUGS)->delete();

        $this->restoreRecreatedDefaultPermissionAssignments($recreatedDefaultPermissionIds);
        $this->backfillLegacyDentistDefaults();
    }

    private function restoreRecreatedDefaultPermissionAssignments(array $permissionIdsByRole): void
    {
        foreach ($permissionIdsByRole as $roleSlug => $permissionIds) {
            $permissionIds = array_values(array_unique(array_map('intval', $permissionIds)));

            if ($permissionIds === []) {
                continue;
            }

            $role = Role::where('slug', $roleSlug)->first();

            if (! $role) {
                continue;
            }

            $role->permissions()->syncWithoutDetaching($permissionIds);
        }
    }

    private function migrateLegacyPermissionAssignments(): void
    {
        foreach (self::LEGACY_PERMISSION_MIGRATIONS as $legacySlug => $replacementSlugs) {
            $legacyPermission = Permission::where('slug', $legacySlug)->first();

            if (! $legacyPermission) {
                continue;
            }

            $replacementIds = Permission::whereIn('slug', $replacementSlugs)->pluck('id');

            if ($replacementIds->isEmpty()) {
                continue;
            }

            foreach ($legacyPermission->roles as $role) {
                $mergedPermissionIds = $role->permissions()
                    ->where('permissions.id', '!=', $legacyPermission->id)
                    ->pluck('permissions.id')
                    ->merge($replacementIds)
                    ->unique()
                    ->values();

                $role->permissions()->sync($mergedPermissionIds);
            }
        }
    }

    private function applyDefaults(Role $role, string $slug): void
    {
        if (! isset(self::DEFAULT_ROLE_PERMISSIONS[$slug])) {
            return;
        }

        $ids = Permission::whereIn('slug', self::DEFAULT_ROLE_PERMISSIONS[$slug])->pluck('id');

        $role->permissions()->sync($ids);
    }

    private function backfillLegacyDentistDefaults(): void
    {
        $role = Role::with('permissions')
            ->where('slug', 'dentist')
            ->first();

        if (! $role) {
            return;
        }

        $currentSlugs = $role->permissions
            ->pluck('slug')
            ->sort()
            ->values()
            ->all();

        $legacySlugs = collect(self::LEGACY_DENTIST_DEFAULT_PERMISSION_SLUGS)
            ->sort()
            ->values()
            ->all();

        $legacyDocumentRequestBackfillSlugs = collect(self::LEGACY_DENTIST_DOCUMENT_REQUEST_BACKFILL_SLUGS)
            ->sort()
            ->values()
            ->all();

        if ($currentSlugs !== $legacySlugs && $currentSlugs !== $legacyDocumentRequestBackfillSlugs) {
            return;
        }

        $updatedSlugs = array_values(array_unique(array_merge(
            $currentSlugs,
            self::DEFAULT_ROLE_PERMISSIONS['dentist']
        )));

        $permissionIds = Permission::whereIn('slug', $updatedSlugs)->pluck('id');

        $role->permissions()->sync($permissionIds);
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

        $role->permissions()->sync(
            $this->normalizePermissionIdsForRole($role, $permissionIds)
        );

        AuditLogger::log(
            'update',
            'roles_permissions',
            "Admin updated permissions for role ID {$role->id} ({$role->name})"
        );

        if ($request->expectsJson()) {
            $savedPermissions = Permission::whereIn('id', $permissionIds)
                ->get(['id', 'name', 'slug', 'module'])
                ->map(fn ($permission) => [
                    'id' => $permission->id,
                    'name' => $permission->name,
                    'slug' => $permission->slug,
                    'module' => $permission->module,
                ])
                ->values()
                ->toArray();

            return response()->json([
                'success' => true,
                'message' => "Permissions for \"{$role->name}\" updated successfully.",
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $savedPermissions,
            ]);
        }

        $savedPermissions = Permission::whereIn('id', $permissionIds)
            ->get(['id', 'name', 'slug', 'module'])
            ->map(fn ($permission) => [
                'id' => $permission->id,
                'name' => $permission->name,
                'slug' => $permission->slug,
                'module' => $permission->module,
            ])
            ->values()
            ->toArray();

        return redirect()
            ->route($this->rolePermissionsRouteName())
            ->with('success', 'Role permissions updated successfully.')
            ->with('saved_view_as', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'permissions' => $savedPermissions,
            ]);
    }

    public function reset()
    {
        $this->ensureRequiredPermissionsExist();

        foreach (self::DEFAULT_ROLE_PERMISSIONS as $slug => $permissionSlugs) {
            $role = Role::where('slug', $slug)->firstOrFail();
            $permissionIds = Permission::whereIn('slug', $permissionSlugs)->pluck('id');

            $role->permissions()->sync($permissionIds);
        }

        Role::query()
            ->whereNotIn('slug', self::CORE_ROLE_SLUGS)
            ->each(function (Role $role): void {
                $role->permissions()->sync([]);
            });

        $this->synchronizeAdminOnlyPermissions();

        AuditLogger::log(
            'update',
            'roles_permissions',
            'Admin reset all permissions to defaults'
        );

        if (request()->expectsJson()) {
            return response()->json([
                'success' => true,
                'message' => 'Default permissions restored.',
            ]);
        }

        return redirect()
            ->route($this->rolePermissionsRouteName())
            ->with('success', 'Default permissions restored.');
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
                'regex:/^[a-z0-9]+(?:[-_][a-z0-9]+)*$/',
            ],
        ], [
            'name.unique' => 'A role with this name already exists.',
            'slug.unique' => 'A role with this slug already exists.',
            'slug.regex' => 'Slug may only contain lowercase letters, numbers, hyphens, and underscores.',
        ]);

        $role = Role::create([
            'name' => $request->name,
            'slug' => $request->slug,
        ]);

        $role->permissions()->sync([]);

        $message = "Role \"{$role->name}\" created successfully. You can now assign permissions and use it in User Management.";

        AuditLogger::log(
            'create',
            'roles_permissions',
            "Admin created role ID {$role->id} ({$role->name})"
        );

        if ($request->ajax() || $request->wantsJson()) {
            return response()->json([
                'success' => true,
                'message' => $message,
                'role' => [
                    'id' => $role->id,
                    'name' => $role->name,
                    'slug' => $role->slug,
                ],
                'user_management_url' => route('admin.user_management'),
            ], 201);
        }

        return redirect()
            ->route($this->rolePermissionsRouteName(), ['highlight_role' => $role->id])
            ->with('success', $message)
            ->with('new_role_id', $role->id);
    }

    public function destroyRole($id)
    {
        try {
            $role = Role::findOrFail($id);

            if (
                in_array(strtolower($role->slug), ['super_admin', 'super-admin', 'superadmin'], true) ||
                str_contains(strtolower($role->name), 'super')
            ) {
                $message = 'Cannot delete the Super Admin role.';

                if (request()->ajax() || request()->wantsJson()) {
                    return response()->json([
                        'success' => false,
                        'message' => $message,
                    ], 422);
                }

                return redirect()
                    ->route($this->rolePermissionsRouteName())
                    ->with('error', $message);
            }

            $fallbackRole = $this->resolveFallbackRole($role);
            $affectedUsers = 0;

            DB::transaction(function () use ($role, $fallbackRole, &$affectedUsers) {
                $affectedUsers = User::where('role_id', $role->id)->count();

                if ($affectedUsers > 0) {
                    User::where('role_id', $role->id)->update([
                        'role_id' => $fallbackRole->id,
                    ]);
                }

                $role->permissions()->detach();
                $role->delete();
            });

            AuditLogger::log(
                'delete',
                'roles_permissions',
                "Deleted role ID {$role->id} ({$role->name}) and reassigned {$affectedUsers} user(s) to {$fallbackRole->display_name}"
            );

            $message = $affectedUsers > 0
                ? "Role '{$role->name}' has been deleted. {$affectedUsers} user(s) were reassigned to {$fallbackRole->display_name}."
                : "Role '{$role->name}' has been deleted.";

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => true,
                    'message' => $message,
                    'fallback_role' => [
                        'id' => $fallbackRole->id,
                        'name' => $fallbackRole->display_name,
                        'slug' => $fallbackRole->slug,
                    ],
                    'affected_users' => $affectedUsers,
                ]);
            }

            return redirect()
                ->route($this->rolePermissionsRouteName())
                ->with('success', $message);
        } catch (\Throwable $e) {
            $message = app()->hasDebugModeEnabled() && config('app.debug')
                ? $e->getMessage()
                : 'Could not delete role.';

            if (request()->ajax() || request()->wantsJson()) {
                return response()->json([
                    'success' => false,
                    'message' => $message,
                ], 500);
            }

            return redirect()
                ->route($this->rolePermissionsRouteName())
                ->with('error', $message);
        }
    }

    private function resolveFallbackRole(Role $deletedRole): Role
    {
        $name = strtolower((string) $deletedRole->name);
        $slug = strtolower((string) $deletedRole->slug);

        if (str_contains($slug, 'dentist') || str_contains($name, 'dentist')) {
            return Role::where('slug', 'dentist')->firstOrFail();
        }

        if (
            str_contains($slug, 'admin') ||
            str_contains($slug, 'staff') ||
            str_contains($name, 'staff') ||
            str_contains($slug, 'clinic') ||
            str_contains($name, 'clinic')
        ) {
            return Role::where('slug', 'admin')->firstOrFail();
        }

        return Role::where('slug', 'patient')->firstOrFail();
    }

    private function rolePermissionsRouteName(): string
    {
        return request()->routeIs('dentist.*')
            ? 'dentist.role_permissions'
            : 'admin.role_permissions';
    }

    private function normalizePermissionIdsForRole(Role $role, array $permissionIds): array
    {
        $adminOnlyPermissionIds = Permission::whereIn('slug', self::ADMIN_ONLY_HIDDEN_PERMISSION_SLUGS)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        $normalizedPermissionIds = array_values(
            array_unique(array_map('intval', $permissionIds))
        );

        if ($role->slug === 'admin') {
            return array_values(
                array_unique(array_merge($normalizedPermissionIds, $adminOnlyPermissionIds))
            );
        }

        return array_values(
            array_diff($normalizedPermissionIds, $adminOnlyPermissionIds)
        );
    }

    private function synchronizeAdminOnlyPermissions(): void
    {
        $adminOnlyPermissionIds = Permission::whereIn('slug', self::ADMIN_ONLY_HIDDEN_PERMISSION_SLUGS)
            ->pluck('id')
            ->map(fn ($id) => (int) $id)
            ->all();

        if ($adminOnlyPermissionIds === []) {
            return;
        }

        Role::with('permissions')
            ->get()
            ->each(function (Role $role) use ($adminOnlyPermissionIds): void {
                $currentPermissionIds = $role->permissions
                    ->pluck('id')
                    ->map(fn ($id) => (int) $id)
                    ->all();

                $normalizedPermissionIds = $role->slug === 'admin'
                    ? array_values(array_unique(array_merge($currentPermissionIds, $adminOnlyPermissionIds)))
                    : array_values(array_diff($currentPermissionIds, $adminOnlyPermissionIds));

                sort($currentPermissionIds);

                $sortedNormalizedPermissionIds = $normalizedPermissionIds;
                sort($sortedNormalizedPermissionIds);

                if ($currentPermissionIds !== $sortedNormalizedPermissionIds) {
                    $role->permissions()->sync($normalizedPermissionIds);
                }
            });
    }
}
