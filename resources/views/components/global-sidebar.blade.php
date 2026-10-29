@php
use App\Models\Role;
use Illuminate\Support\Facades\Route;
$sidebarRole = $role ?? (request()->is('admin*') ? 'admin' : (request()->is('dentist*') ? 'dentist' : 'patient'));

$authUser = auth()->user();

$drawerDisplayName = $authUser?->name ?? ucwords(str_replace('_', ' ', $sidebarRole));

$drawerDisplayRole = session()->has('impersonated_role')
? Role::displayNameFor(session('impersonated_role'))
: $authUser?->display_role_name ?? Role::displayNameFor($sidebarRole);

$drawerAvatarUrl = !empty($authUser?->profile_image)
? asset('storage/' . $authUser->profile_image)
: 'https://ui-avatars.com/api/?name=' .
urlencode($drawerDisplayName) .
'&background=8B0000&color=ffffff&bold=true';

$sidebarGroups = [
'admin' => [
[
'section' => 'Clinic Management',
'label' => 'Clinic Management',
'sublabel' => 'Core clinical modules',
'icon' => 'fa-hospital',
'items' => [
[
'route' => 'admin.admin.dashboard',
'permission' => 'access_super_admin_dashboard',
'active' => ['admin.admin.dashboard'],
'icon' => 'fa-chart-line',
'label' => 'Dashboard',
],
[
'route' => 'admin.patient_directory',
'permission' => 'view_patient_profiles',
'active' => ['admin.patient_directory'],
'icon' => 'fa-users',
'label' => 'Patients',
],
[
'route' => 'admin.dental-records.index',
'permission' => 'view_dental_records',
'active' => ['admin.dental-records*'],
'icon' => 'fa-tooth',
'label' => 'Dental Records',
],
[
'route' => 'admin.admin.appointments',
'permission' => 'view_appointments',
'active' => ['admin.admin.appointments'],
'icon' => 'fa-calendar-check',
'label' => 'Appointments',
],
[
'route' => 'dentist.walk-in.index',
'permission' => 'manage_walk_in_patients',
'active' => ['dentist.walk-in.*'],
'icon' => 'fa-person-walking',
'label' => 'Walk-in',
],
[
'route' => 'admin.existing-record.index',
'permission' => 'manage_existing_records',
'active' => ['admin.existing-record.*'],
'icon' => 'fa-folder-open',
'label' => 'Add Existing Record',
],
[
'route' => 'admin.document-requests.index',
'permissions_any' => [
'view_document_requests',
'approve_document_requests',
'reject_document_requests',
],
'active' => ['admin.document-requests*'],
'icon' => 'fa-file-circle-check',
'label' => 'Document Requests',
],
[
'route' => 'admin.report-files',
'permission' => 'create_report_files',
'active' => ['admin.report-files'],
'icon' => 'fa-file',
'label' => 'Reports',
],
[
'route' => 'admin.reports',
'permission' => 'view_ai_reports',
'active' => ['admin.reports', 'admin.reports.ai-generated'],
'icon' => 'fa-wand-magic-sparkles',
'label' => 'AI Reports',
],
],
],
[
'section' => 'Maintenance',
'label' => 'Configuration',
'sublabel' => 'Settings & scheduling',
'icon' => 'fa-screwdriver-wrench',
'items' => [
[
'route' => 'admin.user_management',
'permissions_any' => [
'view_account_details',
'disable_users',
'update_user_role',
],
'active' => ['admin.user_management*'],
'icon' => 'fa-user-gear',
'label' => 'User Management',
],
[
'route' => 'admin.dentist-transitions.index',
'permission' => 'view_dentist_transitions',
'active' => ['admin.dentist-transitions*'],
'icon' => 'fa-people-arrows',
'label' => 'Dentist Continuity Policy',
],
[
'route' => 'admin.role_permissions',
'permissions_any' => [
'view_roles_permissions',
'create_custom_roles',
'update_role_permissions',
'delete_custom_roles',
],
'active' => ['admin.role_permissions'],
'icon' => 'fa-user-shield',
'label' => 'Roles & Permissions',
],
[
'route' => 'admin.service-types',
'permissions_any' => [
'view_service_type',
'create_service_type',
'delete_service_type',
'update_default_service_type',
],
'active' => ['admin.service-types*'],
'icon' => 'fa-list-check',
'label' => 'Service Types',
],
[
'route' => 'admin.clinic_schedule',
'permissions_any' => [
'view_clinic_schedule',
'update_clinic_schedule',
'create_clinic_schedule',
'delete_clinic_schedule',
],
'active' => ['admin.clinic_schedule*'],
'icon' => 'fa-calendar-days',
'label' => 'Clinic Schedule',
],
[
'route' => 'admin.academic_periods',
'permissions_any' => [
'view_academic_periods',
'update_academic_period',
'create_academic_period',
'delete_academic_period',
],
'active' => ['admin.academic_periods*'],
'icon' => 'fa-school',
'label' => 'Academic Periods',
],
[
'route' => 'admin.inventory',
'permissions_any' => [
'view_inventory',
'add_inventory',
'update_inventory',
'delete_inventory',
],
'active' => ['admin.inventory*'],
'icon' => 'fa-boxes-stacked',
'label' => 'Inventory',
],
[
'route' => 'admin.document-template',
'permission' => 'manage_document_templates',
'active' => ['admin.document-template*'],
'icon' => 'fa-file-pen',
'label' => 'Document Templates',
],
],
],
[
'section' => 'System',
'label' => 'System',
'sublabel' => 'Admin & configuration',
'icon' => 'fa-server',
'items' => [
[
'route' => 'admin.system_settings',
'permissions_any' => ['manage_system_settings', 'set_notification_rules'],
'active' => ['admin.system_settings*', 'admin.retention.*'],
'icon' => 'fa-sliders',
'label' => 'System Settings',
],
[
'route' => 'admin.assign-cms-access',
'permissions_any' => [
'view_cms_integration',
'create_cms_integration',
'update_cms_integration',
],
'active' => ['admin.assign-cms-access'],
'icon' => 'fa-user-shield',
'label' => 'Assign CMS Access',
],
[
'route' => 'admin.faculty.integration',
'permissions_any' => [
'view_faculty_integration',
'create_faculty_integration',
'update_faculty_integration',
],
'active' => ['admin.faculty.integration'],
'icon' => 'fa-user-plus',
'label' => 'Faculty Integration',
],
[
'route' => 'admin.system_logs',
'permissions_any' => ['view_system_logs', 'export_system_logs', 'archive_system_logs'],
'active' => ['admin.system_logs'],
'icon' => 'fa-clipboard-list',
'label' => 'System Logs',
],
[
'route' => 'admin.session_management.index',
'permission' => 'manage_audit_trail',
'active' => ['admin.session_management.*'],
'icon' => 'fa-shield-halved',
'label' => 'Session Dashboard',
],
],
],
],

'dentist' => [
[
'section' => 'Navigation',
'label' => 'Navigation',
'sublabel' => 'Dental clinic tools',
'icon' => 'fa-tooth',
'items' => [
[
'route' => 'dentist.dentist.dashboard',
'permission' => 'access_dentist_dashboard',
'active' => ['dentist.dentist.dashboard'],
'icon' => 'fa-chart-line',
'label' => 'Dashboard',
],
[
'route' => 'dentist.dentist.patients',
'permission' => 'view_patient_profiles',
'active' => ['dentist.dentist.patients'],
'icon' => 'fa-users',
'label' => 'Patients',
],
[
'route' => 'dentist.dental-records.index',
'permission' => 'view_dental_records',
'active' => ['dentist.dental-records*'],
'icon' => 'fa-tooth',
'label' => 'Dental Records',
],
[
'route' => 'dentist.walk-in.index',
'permission' => 'manage_walk_in_patients',
'active' => ['dentist.walk-in.*'],
'icon' => 'fa-person-walking',
'label' => 'Walk-in',
],
[
'route' => 'dentist.existing-record.index',
'permission' => 'manage_existing_records',
'active' => ['dentist.existing-record.*'],
'icon' => 'fa-folder-open',
'label' => 'Add Existing Record',
],
[
'route' => 'dentist.dentist.appointments',
'permissions_any' => [
'view_appointments',
'reschedule_appointments',
'cancel_appointments',
'create_follow_up_appointments',
'create_procedure_records',
],
'active' => ['dentist.dentist.appointments*'],
'icon' => 'fa-calendar-check',
'label' => 'Appointments',
],
[
'route' => 'dentist.dentist.clinic_schedule',
'permissions_any' => [
'view_clinic_schedule',
'update_clinic_schedule',
'create_clinic_schedule',
'delete_clinic_schedule',
'manage_clinic_schedule',
],
'active' => ['dentist.dentist.clinic_schedule*'],
'icon' => 'fa-calendar-days',
'label' => 'Clinic Schedule',
],
[
'route' => 'dentist.dentist.documentrequests',
'permissions_any' => [
'view_document_requests',
'approve_document_requests',
'reject_document_requests',
],
'active' => ['dentist.dentist.documentrequests*'],
'icon' => 'fa-file-circle-check',
'label' => 'Document Requests',
],
[
'route' => 'dentist.dentist.inventory',
'permissions_any' => [
'view_inventory',
'add_inventory',
'update_inventory',
'delete_inventory',
],
'active' => ['dentist.dentist.inventory*'],
'icon' => 'fa-box',
'label' => 'Inventory',
],
[
'route' => 'dentist.dentist.report',
'permissions_any' => ['view_reports', 'create_report_files'],
'active' => ['dentist.dentist.report*', 'dentist.dentist.reports.*'],
'icon' => 'fa-file',
'label' => 'Reports',
],
],
],
[
'section' => 'System',
'label' => 'System',
'sublabel' => 'Admin & configuration',
'icon' => 'fa-server',
'items' => [
[
'route' => 'dentist.user_management',
'permissions_any' => [
'view_account_details',
'disable_users',
'update_user_role',
],
'active' => ['dentist.user_management*'],
'icon' => 'fa-user-gear',
'label' => 'User Management',
],
[
'route' => 'dentist.system_settings',
'permissions_any' => ['manage_system_settings', 'set_notification_rules'],
'active' => ['dentist.system_settings*'],
'icon' => 'fa-sliders',
'label' => 'System Settings',
],
[
'route' => 'dentist.role_permissions',
'permissions_any' => [
'view_roles_permissions',
'create_custom_roles',
'update_role_permissions',
'delete_custom_roles',
],
'active' => ['dentist.role_permissions*'],
'icon' => 'fa-user-shield',
'label' => 'Roles & Permissions',
],
[
'route' => 'dentist.service-types',
'permissions_any' => [
'view_service_type',
'create_service_type',
'delete_service_type',
'update_default_service_type',
],
'active' => ['dentist.service-types*'],
'icon' => 'fa-list-check',
'label' => 'Service Types',
],
[
'route' => 'dentist.academic_periods',
'permissions_any' => [
'view_academic_periods',
'update_academic_period',
'create_academic_period',
'delete_academic_period',
],
'active' => ['dentist.academic_periods*'],
'icon' => 'fa-school',
'label' => 'Academic Periods',
],
[
'route' => 'dentist.assign-cms-access',
'permissions_any' => [
'view_cms_integration',
'create_cms_integration',
'update_cms_integration',
],
'active' => ['dentist.assign-cms-access*'],
'icon' => 'fa-user-shield',
'label' => 'Assign CMS Access',
],
[
'route' => 'dentist.faculty.integration',
'permissions_any' => [
'view_faculty_integration',
'create_faculty_integration',
'update_faculty_integration',
],
'active' => ['dentist.faculty.integration*'],
'icon' => 'fa-user-plus',
'label' => 'Faculty Integration',
],
[
'route' => 'dentist.system_logs',
'permissions_any' => ['view_system_logs', 'export_system_logs', 'archive_system_logs'],
'active' => ['dentist.system_logs*'],
'icon' => 'fa-clipboard-list',
'label' => 'System Logs',
],
[
'route' => 'dentist.reports',
'permission' => 'view_ai_reports',
'active' => ['dentist.reports*'],
'icon' => 'fa-wand-magic-sparkles',
'label' => 'AI Reports',
],
[
'route' => 'dentist.document-template',
'permission' => 'manage_document_templates',
'active' => ['dentist.document-template*'],
'icon' => 'fa-file-pen',
'label' => 'Document Templates',
],
],
],
],

'patient' => [
[
'section' => 'Navigation',
'label' => 'Patient',
'sublabel' => 'Self-service tools',
'icon' => 'fa-user',
'items' => [
[
'route' => 'homepage',
'permission' => 'access_patient_dashboard',
'active' => ['homepage'],
'paths' => ['patient/dashboard'],
'icon' => 'fa-house',
'label' => 'Home',
],
[
'route' => 'patient.appointment.index',
'permissions_any' => ['view_own_appointments', 'book_appointments'],
'active' => ['patient.appointment.*', 'book.appointment.*', 'appointment.*'],
'paths' => ['patient/appointment*', 'patient/book-appointment*'],
'icon' => 'fa-calendar-check',
'label' => 'Appointments',
],
[
'route' => 'patient.record',
'permission' => 'view_own_records',
'active' => ['patient.record'],
'paths' => ['patient/record*'],
'icon' => 'fa-folder-open',
'label' => 'Dental Records',
],
[
'route' => 'patient.about.us',
'active' => ['patient.about.us'],
'paths' => ['patient/about*'],
'icon' => 'fa-circle-info',
'label' => 'About Us',
],
],
],
],
];

$sidebarCategoryMap = [
'admin' => [
[
'section' => 'Clinical Operations',
'label' => 'Clinical Operations',
'sublabel' => 'Patients & clinical work',
'icon' => 'fa-hospital',
'item_labels' => [
'Dashboard',
'Patients',
'Dental Records',
'Walk-in',
'Add Existing Record',
'Dentist Continuity Policy',
'Faculty Integration',
],
],
[
'section' => 'Appointment Management',
'label' => 'Appointment Management',
'sublabel' => 'Scheduling & periods',
'icon' => 'fa-calendar-check',
'item_labels' => [
'Appointments',
'Clinic Schedule',
'Academic Periods',
],
],
[
'section' => 'Clinical Services',
'label' => 'Clinical Services',
'sublabel' => 'Documents & services',
'icon' => 'fa-file-circle-check',
'item_labels' => [
'Document Requests',
'Service Types',
'Document Templates',
],
],
[
'section' => 'Resource Management',
'label' => 'Resource Management',
'sublabel' => 'Inventory & analytics',
'icon' => 'fa-boxes-stacked',
'item_labels' => [
'Inventory',
'Reports',
'AI Reports',
],
],
[
'section' => 'System Administration',
'label' => 'System Administration',
'sublabel' => 'Access & system controls',
'icon' => 'fa-server',
'item_labels' => [
'User Management',
'Assign CMS Access',
'Roles & Permissions',
'System Logs',
'Session Dashboard',
'System Settings',
],
],
],

'dentist' => [
[
'section' => 'Clinical Operations',
'label' => 'Clinical Operations',
'sublabel' => 'Patients & clinical work',
'icon' => 'fa-hospital',
'item_labels' => [
'Dashboard',
'Patients',
'Dental Records',
'Walk-in',
'Add Existing Record',
'Dentist Continuity Policy',
'Faculty Integration',
],
],
[
'section' => 'Appointment Management',
'label' => 'Appointment Management',
'sublabel' => 'Scheduling & periods',
'icon' => 'fa-calendar-check',
'item_labels' => [
'Appointments',
'Clinic Schedule',
'Academic Periods',
],
],
[
'section' => 'Clinical Services',
'label' => 'Clinical Services',
'sublabel' => 'Documents & services',
'icon' => 'fa-file-circle-check',
'item_labels' => [
'Document Requests',
'Service Types',
'Document Templates',
],
],
[
'section' => 'Resource Management',
'label' => 'Resource Management',
'sublabel' => 'Inventory & analytics',
'icon' => 'fa-boxes-stacked',
'item_labels' => [
'Inventory',
'Reports',
'AI Reports',
],
],
[
'section' => 'System Administration',
'label' => 'System Administration',
'sublabel' => 'Access & system controls',
'icon' => 'fa-server',
'item_labels' => [
'User Management',
'Assign CMS Access',
'Roles & Permissions',
'System Logs',
'System Settings',
],
],
],
];

if (isset($sidebarCategoryMap[$sidebarRole])) {
$allSidebarItems = collect($sidebarGroups[$sidebarRole])
->flatMap(fn($group) => $group['items'])
->keyBy('label');

$sidebarGroups[$sidebarRole] = collect($sidebarCategoryMap[$sidebarRole])
->map(function ($category) use ($allSidebarItems) {
$category['items'] = collect($category['item_labels'])
->map(fn($label) => $allSidebarItems->get($label))
->filter()
->values()
->all();

unset($category['item_labels']);

return $category;
})
->all();
}

$groups = $sidebarGroups[$sidebarRole] ?? $sidebarGroups['patient'];

if ($authUser) {
$isSuperAdmin = $authUser->role?->slug === 'super_admin';

$groups = collect($groups)
->map(function ($group) use ($authUser, $isSuperAdmin) {
$group['items'] = array_values(
array_filter($group['items'], function ($item) use ($authUser, $isSuperAdmin) {
if ($isSuperAdmin) {
return true;
}

$requiredPermission = $item['permission'] ?? null;
$requiredAnyPermissions = $item['permissions_any'] ?? [];

if ($requiredPermission) {
return $authUser->hasPermission($requiredPermission);
}

if ($requiredAnyPermissions !== []) {
foreach ($requiredAnyPermissions as $permission) {
if ($authUser->hasPermission($permission)) {
return true;
}
}

return false;
}

return true;
}),
);

return $group;
})
->filter(fn($group) => !empty($group['items']))
->values()
->all();
}
$resolveItemUrl = function ($item) {
try {
if (!empty($item['url'])) {
return $item['url'];
}

if (!empty($item['route']) && Route::has($item['route'])) {
return route($item['route']);
}
} catch (\Throwable $e) {
return null;
}

return null;
};

$isItemActive = function ($item) {
foreach ($item['active'] ?? [$item['route']] as $pattern) {
if (request()->routeIs($pattern)) {
return true;
}
}

foreach ($item['paths'] ?? [] as $path) {
if (request()->is($path)) {
return true;
}
}

return false;
};

$isGroupActive = function ($group) use ($isItemActive) {
foreach ($group['items'] as $item) {
if ($isItemActive($item)) {
return true;
}
}

return false;
};

$isGroupedSidebar = in_array($sidebarRole, ['admin', 'dentist'], true);
$shouldShowDrawer = $isGroupedSidebar;
@endphp

<aside id="sidebar" class="global-sidebar sidebar-{{ $sidebarRole }} {{ $isGroupedSidebar ? 'sidebar-grouped' : '' }}">
    <div class="sidebar-inner">
        <div class="toggle-row flex justify-end mb-3">
            <button type="button" id="sidebarToggleBtn" class="sidebar-toggle-btn" aria-label="Toggle sidebar"
                data-sidebar-toggle>
                <i id="sidebarIcon" class="fa-solid fa-xmark"></i>
            </button>
        </div>

        @foreach ($groups as $group)
        @php
        $groupActive = $isGroupActive($group);

        $groupPanelId = 'sidebar-group-' . $sidebarRole . '-' . $loop->index;
        @endphp

        @if (!$isGroupedSidebar)
        <div class="nav-section-label">
            {{ $group['section'] }}
        </div>
        @endif

        <div class="
            nav-group
            {{ $groupActive ? 'is-expanded' : '' }}
        ">
            @if ($isGroupedSidebar)
            <button type="button" class="
                    sidebar-group-trigger
                    {{ $groupActive ? 'active-group' : '' }}
                " data-sidebar-group-toggle aria-expanded="{{ $groupActive ? 'true' : 'false' }}"
                aria-controls="{{ $groupPanelId }}">
                <div class="sidebar-group-icon">
                    <i class="
                            fa-solid
                            {{ $group['icon'] }}
                        "></i>
                </div>

                <div class="sidebar-group-text">
                    <span class="group-label">
                        {{ $group['label'] }}
                    </span>

                </div>

                <span class="sidebar-group-chevron" aria-hidden="true">
                    <i class="
                            fa-solid
                            fa-chevron-down
                        "></i>
                </span>

                <span class="sidebar-item-tooltip">
                    {{ $group['label'] }}
                </span>
            </button>
            @endif

            <div id="{{ $groupPanelId }}" class="group-body"
                data-group-label="{{ $isGroupedSidebar ? $group['label'] : '' }}"
                data-group-sublabel="{{ $isGroupedSidebar ? $group['sublabel'] : '' }}">
                @foreach ($group['items'] as $item)
                @php
                $itemUrl = $resolveItemUrl($item);
                @endphp

                @if ($itemUrl)
                <a href="{{ $itemUrl }}" class="
                            sidebar-item
                            {{ $isItemActive($item) ? 'active' : '' }}
                        ">
                    @if ($isGroupedSidebar)
                    <span class="sidebar-item-inline-icon">
                        <i class="
                                        fa-solid
                                        {{ $item['icon'] }}
                                    "></i>
                    </span>
                    @else
                    <span class="sidebar-item-icon">
                        <i class="
                                        fa-solid
                                        {{ $item['icon'] }}
                                    "></i>
                    </span>
                    @endif

                    <span class="sidebar-item-text">
                        {{ $item['label'] }}
                    </span>

                    <span class="sidebar-item-tooltip">
                        {{ $item['label'] }}
                    </span>
                </a>
                @endif
                @endforeach
            </div>
        </div>

        @if (!$loop->last)
        <div class="nav-sep"></div>
        @endif
        @endforeach
    </div>

    <div class="sidebar-bottom">
        <div class="sidebar-theme-block mb-2">
            <div class="theme-toggle-container sidebar-theme-expanded">
                <button type="button" class="theme-option active" data-theme-choice="light" aria-label="Light mode">
                    <i class="fa-solid fa-sun"></i>
                </button>

                <button type="button" class="theme-option" data-theme-choice="dark" aria-label="Dark mode">
                    <i class="fa-regular fa-moon"></i>
                </button>

                <div class="theme-indicator"></div>
            </div>

            <div class="sidebar-theme-collapsed" data-sidebar-theme-dropdown>
                <button type="button" class="sidebar-theme-mini-btn sidebar-mini-control" data-sidebar-theme-trigger
                    aria-label="Switch Mode">
                    <i class="fa-solid fa-sun" data-sidebar-theme-icon></i>
                    <span class="sidebar-item-tooltip">Switch Mode</span>
                </button>

                <div class="sidebar-theme-popover">
                    <button type="button" class="sidebar-theme-popover-option theme-option active"
                        data-theme-choice="light">
                        <i class="fa-solid fa-sun"></i>
                        <span>Light</span>
                    </button>

                    <button type="button" class="sidebar-theme-popover-option theme-option" data-theme-choice="dark">
                        <i class="fa-regular fa-moon"></i>
                        <span>Dark</span>
                    </button>
                </div>
            </div>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="js-logout-form">
            @csrf

            <button type="submit" class="logout-btn sidebar-mini-control">
                <span class="sidebar-control-icon">
                    <i class="fa-solid fa-right-from-bracket"></i>
                </span>

                <span class="sidebar-item-text">Log Out</span>
                <span class="sidebar-item-tooltip">Log Out</span>
            </button>
        </form>
    </div>
</aside>

@if ($shouldShowDrawer)
<div id="mobileDrawerOverlay" data-drawer-close></div>

<div id="mobileDrawer">
    <div class="drawer-header">
        <div class="drawer-header-left">
            <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" class="drawer-logo" alt="DMS">

            <div>
                <div class="drawer-title">PUP TAGUIG</div>
                <div class="drawer-subtitle">Dental Clinic</div>
            </div>
        </div>

        <button type="button" class="drawer-close" data-drawer-close aria-label="Close menu">
            <i class="fa-solid fa-xmark"></i>
        </button>
    </div>

    <div class="drawer-user">
        <span class="patient-avatar patient-avatar-md drawer-user-avatar" data-patient-avatar
            data-patient-name="{{ $drawerDisplayName }}" data-patient-url="{{ $drawerAvatarUrl }}"
            aria-label="{{ $drawerDisplayName }}">
        </span>

        <div>
            <div class="drawer-user-name">{{ $drawerDisplayName }}</div>
            <div class="drawer-user-role">{{ $drawerDisplayRole }}</div>
        </div>
    </div>

    <div class="drawer-inner">
        @foreach ($groups as $group)
        @php
        $groupActive = $isGroupActive($group);

        $drawerGroupId = 'drawer-group-' . $sidebarRole . '-' . $loop->index;
        @endphp

        <div class="drawer-group {{ $groupActive ? 'is-expanded' : '' }}">
            <button type="button" class="drawer-group-header" data-drawer-group-toggle
                aria-expanded="{{ $groupActive ? 'true' : 'false' }}" aria-controls="{{ $drawerGroupId }}">
                <span class="drawer-group-icon" aria-hidden="true">
                    <i class="fa-solid {{ $group['icon'] }}"></i>
                </span>

                <span class="drawer-group-label">
                    {{ $group['label'] }}
                </span>

                <span class="drawer-group-chevron" aria-hidden="true">
                    <i class="fa-solid fa-chevron-down"></i>
                </span>
            </button>

            <div id="{{ $drawerGroupId }}" class="drawer-group-body">
                @foreach ($group['items'] as $item)
                @php
                $itemUrl = $resolveItemUrl($item);
                @endphp

                @if ($itemUrl)
                <a href="{{ $itemUrl }}" class="drawer-link {{ $isItemActive($item) ? 'active' : '' }}">
                    <span class="drawer-link-icon" aria-hidden="true">
                        <i class="sidebar-item-inline-icon fa-solid {{ $item['icon'] }}"></i>
                    </span>

                    <span class="drawer-link-text">
                        {{ $item['label'] }}
                    </span>
                </a>
                @endif
                @endforeach
            </div>
        </div>

        @if (!$loop->last)
        <div class="drawer-sep"></div>
        @endif
        @endforeach
    </div>

    <div class="drawer-bottom">
        <div class="theme-toggle-container mb-2">
            <button type="button" class="theme-option active" data-theme-choice="light" aria-label="Light mode">
                <i class="fa-solid fa-sun"></i>
            </button>

            <button type="button" class="theme-option" data-theme-choice="dark" aria-label="Dark mode">
                <i class="fa-regular fa-moon"></i>
            </button>

            <div class="theme-indicator" aria-hidden="true"></div>
        </div>

        <form action="{{ route('logout') }}" method="POST" class="js-logout-form">
            @csrf

            <button type="submit" class="drawer-logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i>
                <span>Log Out</span>
            </button>
        </form>
    </div>
</div>
@endif
