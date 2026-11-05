<?php

namespace App\Support;

final class DefaultRolePermissions
{
    public const PERMISSIONS = [
        ['name' => 'Access Super Admin Dashboard', 'slug' => 'access_super_admin_dashboard', 'module' => 'General Access'],
        ['name' => 'Access Dentist Dashboard', 'slug' => 'access_dentist_dashboard', 'module' => 'General Access'],
        ['name' => 'Access Patient Dashboard', 'slug' => 'access_patient_dashboard', 'module' => 'General Access'],
        ['name' => 'Receive Notifications', 'slug' => 'receive_notifications', 'module' => 'General Access'],

        ['name' => 'Manage System Settings', 'slug' => 'manage_system_settings', 'module' => 'System Settings'],
        ['name' => 'Manage Audit Trail', 'slug' => 'manage_audit_trail', 'module' => 'System Settings'],
        ['name' => 'Set Academic Year', 'slug' => 'set_academic_year', 'module' => 'System Settings'],
        ['name' => 'Set Archive Records', 'slug' => 'set_archive_records', 'module' => 'System Settings'],
        ['name' => 'Set Report Periods', 'slug' => 'set_report_periods', 'module' => 'System Settings'],
        ['name' => 'Set Required Fields', 'slug' => 'set_required_fields', 'module' => 'System Settings'],
        ['name' => 'Set Appointment Limit', 'slug' => 'set_appointment_limit', 'module' => 'System Settings'],
        ['name' => 'Set Notification Rules', 'slug' => 'set_notification_rules', 'module' => 'System Settings'],
        ['name' => 'Set Export File Type', 'slug' => 'set_export_file_type', 'module' => 'System Settings'],

        ['name' => 'View System Logs', 'slug' => 'view_system_logs', 'module' => 'System Logs'],
        ['name' => 'Export System Logs', 'slug' => 'export_system_logs', 'module' => 'System Logs'],
        ['name' => 'Archive System Logs', 'slug' => 'archive_system_logs', 'module' => 'System Logs'],

        ['name' => 'Manage User Accounts', 'slug' => 'manage_user_accounts', 'module' => 'User Management'],
        ['name' => 'Manage User Roles', 'slug' => 'manage_user_roles', 'module' => 'User Management'],
        ['name' => 'Manage Dentist Accounts', 'slug' => 'manage_dentist_accounts', 'module' => 'User Management'],
        ['name' => 'Create/Disable Users', 'slug' => 'create_disable_users', 'module' => 'User Management'],
        ['name' => 'Update Role, Password', 'slug' => 'update_role_password', 'module' => 'User Management'],
        ['name' => 'View Account Details', 'slug' => 'view_account_details', 'module' => 'User Management'],
        ['name' => 'Create Users', 'slug' => 'create_users', 'module' => 'User Management'],
        ['name' => 'Disable Users', 'slug' => 'disable_users', 'module' => 'User Management'],
        ['name' => 'Update User Role', 'slug' => 'update_user_role', 'module' => 'User Management'],
        ['name' => 'Update User Password', 'slug' => 'update_user_password', 'module' => 'User Management'],

        ['name' => 'View Roles & Permissions', 'slug' => 'view_roles_permissions', 'module' => 'Role Permissions'],
        ['name' => 'Create Custom Roles', 'slug' => 'create_custom_roles', 'module' => 'Role Permissions'],
        ['name' => 'Update Role Permissions', 'slug' => 'update_role_permissions', 'module' => 'Role Permissions'],
        ['name' => 'Delete Custom Roles', 'slug' => 'delete_custom_roles', 'module' => 'Role Permissions'],

        ['name' => 'View CMS Integration', 'slug' => 'view_cms_integration', 'module' => 'CMS Integration'],
        ['name' => 'Create CMS Integration', 'slug' => 'create_cms_integration', 'module' => 'CMS Integration'],
        ['name' => 'Update CMS Integration', 'slug' => 'update_cms_integration', 'module' => 'CMS Integration'],

        ['name' => 'View Faculty Integration', 'slug' => 'view_faculty_integration', 'module' => 'Faculty Integration'],
        ['name' => 'Create Faculty Integration', 'slug' => 'create_faculty_integration', 'module' => 'Faculty Integration'],
        ['name' => 'Update Faculty Integration', 'slug' => 'update_faculty_integration', 'module' => 'Faculty Integration'],

        ['name' => 'View Dentist Transitions', 'slug' => 'view_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Create Dentist Transitions', 'slug' => 'create_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Update Dentist Transitions', 'slug' => 'update_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Assign Dentist Successors', 'slug' => 'assign_dentist_successors', 'module' => 'Dentist Continuity'],
        ['name' => 'Finalize Dentist Transitions', 'slug' => 'finalize_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Cancel Dentist Transitions', 'slug' => 'cancel_dentist_transitions', 'module' => 'Dentist Continuity'],
        ['name' => 'Extend Dentist Access', 'slug' => 'extend_dentist_access', 'module' => 'Dentist Continuity'],
        ['name' => 'View Dentist Transition Audit Logs', 'slug' => 'view_dentist_transition_audit_logs', 'module' => 'Dentist Continuity'],

        ['name' => 'Manage Document Templates', 'slug' => 'manage_document_templates', 'module' => 'Document Templates'],
        ['name' => 'Manage Reports', 'slug' => 'manage_reports', 'module' => 'Reports'],
        ['name' => 'View Reports', 'slug' => 'view_reports', 'module' => 'Reports'],
        ['name' => 'Create Report Files', 'slug' => 'create_report_files', 'module' => 'Reports'],
        ['name' => 'View AI Reports', 'slug' => 'view_ai_reports', 'module' => 'Reports'],
        ['name' => 'Create AI Generative Reports', 'slug' => 'create_ai_generative_reports', 'module' => 'Reports'],

        ['name' => 'Manage Inventory', 'slug' => 'manage_inventory', 'module' => 'Inventory'],
        ['name' => 'View Inventory', 'slug' => 'view_inventory', 'module' => 'Inventory'],
        ['name' => 'Add Inventory', 'slug' => 'add_inventory', 'module' => 'Inventory'],
        ['name' => 'Update Inventory', 'slug' => 'update_inventory', 'module' => 'Inventory'],
        ['name' => 'Delete Inventory', 'slug' => 'delete_inventory', 'module' => 'Inventory'],

        ['name' => 'Manage Dental Records', 'slug' => 'manage_dental_records', 'module' => 'Dental Records'],
        ['name' => 'View Dental Records', 'slug' => 'view_dental_records', 'module' => 'Dental Records'],

        ['name' => 'Manage Appointments', 'slug' => 'manage_appointments', 'module' => 'Appointments'],
        ['name' => 'View Appointments', 'slug' => 'view_appointments', 'module' => 'Appointments'],
        ['name' => 'Reschedule Appointments', 'slug' => 'reschedule_appointments', 'module' => 'Appointments'],
        ['name' => 'Cancel Appointments', 'slug' => 'cancel_appointments', 'module' => 'Appointments'],
        ['name' => 'Create Follow-up Appointments', 'slug' => 'create_follow_up_appointments', 'module' => 'Appointments'],
        ['name' => 'Manage Walk-in Patients', 'slug' => 'manage_walk_in_patients', 'module' => 'Appointments'],
        ['name' => 'Add Existing Record', 'slug' => 'manage_existing_records', 'module' => 'Appointments'],
        ['name' => 'Book Appointments', 'slug' => 'book_appointments', 'module' => 'Appointments'],
        ['name' => 'View Own Appointments', 'slug' => 'view_own_appointments', 'module' => 'Appointments'],

        ['name' => 'View Schedule and Dates', 'slug' => 'view_clinic_schedule', 'module' => 'Clinic Schedule'],
        ['name' => 'Update Clinic Hours', 'slug' => 'update_clinic_schedule', 'module' => 'Clinic Schedule'],
        ['name' => 'Create Clinic Hours', 'slug' => 'create_clinic_schedule', 'module' => 'Clinic Schedule'],
        ['name' => 'Delete Clinic Hours', 'slug' => 'delete_clinic_schedule', 'module' => 'Clinic Schedule'],

        ['name' => 'Create Procedure Records', 'slug' => 'create_procedure_records', 'module' => 'Clinical Records'],
        ['name' => 'Create Dental Records', 'slug' => 'create_dental_records', 'module' => 'Clinical Records'],
        ['name' => 'Create Medical Records', 'slug' => 'create_medical_records', 'module' => 'Clinical Records'],
        ['name' => 'Create Odontograms', 'slug' => 'create_odontograms', 'module' => 'Clinical Records'],
        ['name' => 'Update Odontograms', 'slug' => 'update_odontograms', 'module' => 'Clinical Records'],

        ['name' => 'View Academic Periods/PUP Calendar/Time', 'slug' => 'view_academic_periods', 'module' => 'Academic Period'],
        ['name' => 'Create/Delete Academic Period', 'slug' => 'create_delete_academic_period', 'module' => 'Academic Period'],
        ['name' => 'Update Academic Period', 'slug' => 'update_academic_period', 'module' => 'Academic Period'],
        ['name' => 'Create Academic Period', 'slug' => 'create_academic_period', 'module' => 'Academic Period'],
        ['name' => 'Delete Academic Period', 'slug' => 'delete_academic_period', 'module' => 'Academic Period'],

        ['name' => 'View Service Type', 'slug' => 'view_service_type', 'module' => 'Service Types'],
        ['name' => 'Create Service Type', 'slug' => 'create_service_type', 'module' => 'Service Types'],
        ['name' => 'Delete Service Type', 'slug' => 'delete_service_type', 'module' => 'Service Types'],
        ['name' => 'Update Default Service Type', 'slug' => 'update_default_service_type', 'module' => 'Service Types'],

        ['name' => 'Manage Patient Profiles', 'slug' => 'manage_patient_profiles', 'module' => 'Patients'],
        ['name' => 'View Patient Profiles', 'slug' => 'view_patient_profiles', 'module' => 'Patients'],
        ['name' => 'View Own Profile', 'slug' => 'view_own_profile', 'module' => 'Patients'],

        ['name' => 'Manage Document Requests', 'slug' => 'manage_document_requests', 'module' => 'Document Requests'],
        ['name' => 'View Document Requests', 'slug' => 'view_document_requests', 'module' => 'Document Requests'],
        ['name' => 'Approve Document Requests', 'slug' => 'approve_document_requests', 'module' => 'Document Requests'],
        ['name' => 'Reject Document Requests', 'slug' => 'reject_document_requests', 'module' => 'Document Requests'],
        ['name' => 'Request Documents', 'slug' => 'request_documents', 'module' => 'Document Requests'],

        ['name' => 'View Own Records', 'slug' => 'view_own_records', 'module' => 'Dental Records'],
    ];

    public const DEFAULT_ROLE_PERMISSION_SLUGS = [
        'admin' => [
            'access_super_admin_dashboard',
            'receive_notifications',
            'manage_system_settings',
            'manage_audit_trail',
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
            'view_dentist_transition_audit_logs',
            'manage_document_templates',
            'view_service_type',
            'create_service_type',
            'delete_service_type',
            'update_default_service_type',
            'view_reports',
            'create_report_files',
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
            'create_follow_up_appointments',
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
            'view_academic_periods',
            'update_academic_period',
            'create_academic_period',
            'delete_academic_period',
            'view_faculty_integration',
            'create_faculty_integration',
            'update_faculty_integration',
            'view_cms_integration',
            'create_cms_integration',
            'update_cms_integration',
            'set_appointment_limit',
            'set_notification_rules',
        ],
        'dentist' => [
            'access_dentist_dashboard',
            'view_patient_profiles',
            'view_dental_records',
            'view_appointments',
            'reschedule_appointments',
            'cancel_appointments',
            'manage_walk_in_patients',
            'manage_existing_records',
            'view_document_requests',
            'approve_document_requests',
            'reject_document_requests',
            'request_documents',
            'create_follow_up_appointments',
            'create_procedure_records',
            'create_dental_records',
            'create_medical_records',
            'create_odontograms',
            'update_odontograms',
            'view_clinic_schedule',
            'update_clinic_schedule',
            'create_clinic_schedule',
            'delete_clinic_schedule',
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

    public static function all(): array
    {
        return self::PERMISSIONS;
    }

    public static function defaultsForRole(string $roleSlug): array
    {
        return self::DEFAULT_ROLE_PERMISSION_SLUGS[$roleSlug] ?? [];
    }

    public static function allDefaultRoleMappings(): array
    {
        return self::DEFAULT_ROLE_PERMISSION_SLUGS;
    }

    public static function permissionDefinitionsForSlugs(array $slugs): array
    {
        $requestedSlugs = array_values(array_unique($slugs));

        return array_values(array_filter(
            self::PERMISSIONS,
            static fn (array $permission): bool => in_array($permission['slug'], $requestedSlugs, true)
        ));
    }

    public static function requiredAdminPermissionDefinitions(): array
    {
        return self::permissionDefinitionsForSlugs(self::defaultsForRole('admin'));
    }
}
