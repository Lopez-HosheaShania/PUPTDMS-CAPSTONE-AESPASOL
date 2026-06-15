<!-- Mobile drawer overlay -->
<div id="mobileDrawerOverlay" onclick="closeDrawer()"></div>

<!-- Mobile drawer -->
<div id="mobileDrawer">
    <div class="drawer-header">
        <div class="drawer-header-left">
            <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" class="drawer-logo" alt="DMS">
            <div>
                <div class="drawer-title">PUP TAGUIG</div>
                <div class="drawer-subtitle">Dental Clinic</div>
            </div>
        </div>
        <button class="drawer-close" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <div class="drawer-user">
        <img src="https://i.pravatar.cc/40" class="drawer-avatar" alt="Avatar">
        <div>
            <div class="drawer-user-name">Admin</div>
            <div class="drawer-user-role">Administrator</div>
        </div>
    </div>
    <div class="drawer-inner">
        <div class="drawer-group">
            <div class="drawer-group-header">
                <i class="drawer-group-icon fa-solid fa-hospital"></i>
                <span class="drawer-group-label">Clinic Management</span>
            </div>

            <a href="{{ route('admin.admin.dashboard') }}"
                class="drawer-link {{ request()->routeIs('admin.admin.dashboard') ? 'active' : '' }}">
                <i class="fa-solid fa-chart-line"></i> Dashboard
            </a>

            <a href="{{ route('admin.patient_directory') }}"
                class="drawer-link {{ request()->routeIs('admin.patient_directory') ? 'active' : '' }}">
                <i class="fa-solid fa-users"></i> Patients
            </a>

            <a href="{{ route('admin.dental-records.index') }}""
                class="nav-link {{ request()->routeIs('admin.dental-records*') ? 'active' : '' }}">
                <i class="fa-solid fa-tooth"></i> Dental Records
            </a>

            <a href="{{ route('admin.admin.appointments') }}"
                class="drawer-link {{ request()->routeIs('admin.admin.appointments') ? 'active' : '' }}">
                <i class="fa-solid fa-calendar-check"></i> Appointments
            </a>

            <a href="{{ route('admin.document-requests.index') }}"
                class="drawer-link {{ request()->routeIs('admin.document-requests*') ? 'active' : '' }}">
                <i class="fa-solid fa-file-circle-check"></i> Document Request
            </a>

            <a href="{{ route('admin.reports') }}"
                class="drawer-link {{ request()->routeIs('admin.reports') ? 'active' : '' }}">
                <i class="fa-solid fa-file"></i> Reports
            </a>
        </div>

        <div class="drawer-sep"></div>
        <div class="drawer-group">
            <div class="drawer-group-header"><i class="drawer-group-icon fa-solid fa-screwdriver-wrench"></i><span
                    class="drawer-group-label">Maintenance</span></div>
            
            <a href="{{ route('admin.user_management') }}"
                class="drawer-link {{ request()->routeIs('admin.user_management*') ? 'active' : '' }}"><i
                    class="fa-solid fa-user-gear"></i> User Management
            </a>

            <a href="{{ route('admin.role_permissions') }}"
                class="drawer-link {{ request()->routeIs('admin.role_permissions') ? 'active' : '' }}">
                <i class="fa-solid fa-user-shield"></i> Roles & Permissions
            </a>

            <a href="{{ route('admin.service-types') }}"
                class="drawer-link {{ request()->routeIs('admin.service-types*') ? 'active' : '' }}"><i
                    class="fa-solid fa-list-check"></i> Service Types
            </a>

            <a href="{{ route('admin.clinic_schedule') }}"
                class="drawer-link {{ request()->routeIs('admin.clinic_schedule*') ? 'active' : '' }}"><i
                    class="fa-solid fa-calendar-days"></i> Clinic Schedule
            </a>

            <a href="{{ route('admin.academic_periods') }}"
                class="drawer-link {{ request()->routeIs('admin.academic_periods*') ? 'active' : '' }}"><i
                    class="fa-solid fa-school"></i> Academic Periods
            </a>

            <a href="{{ route('admin.inventory') }}"
                class="drawer-link {{ request()->routeIs('admin.inventory*') ? 'active' : '' }}">
                <i class="fa-solid fa-boxes-stacked"></i> Inventory
            </a>

            <a href="{{ route('admin.document-template') }}"
                    class="drawer-link {{ request()->routeIs('admin.document-template*') ? 'active' : '' }}">
                    <i class="fa-solid fa-file-pen"></i>
                    Document Templates
            </a>
        </div>

        <div class="drawer-sep"></div>
        <div class="drawer-group">
            <div class="drawer-group-header"><i class="drawer-group-icon fa-solid fa-server"></i><span
                    class="drawer-group-label">System</span>
            </div>

            <a href="{{ route('admin.system_settings') }}"
                class="drawer-link {{ request()->routeIs('admin.system_settings*') ? 'active' : '' }}">
                <i class="fa-solid fa-sliders"></i> System Settings
            </a>

            <a href="{{ route('admin.assign-cms-access') }}"
                class="drawer-link {{ request()->routeIs('admin.assign-cms-access') ? 'active' : '' }}">
                <i class="fa-solid fa-user-shield"></i> Assign CMS Access
            </a>

            <a href="{{ route('admin.faculty.integration') }}"
                class="drawer-link {{ request()->routeIs('admin.faculty.integration') ? 'active' : '' }}">
                <i class="fa-solid fa-user-plus"></i> Faculty Integration
            </a>

            <a href="{{ route('admin.data_backup') }}"
                class="drawer-link {{ request()->routeIs('admin.data_backup') ? 'active' : '' }}">
                <i class="fa-solid fa-database"></i> Data Backup
            </a>

            <a href="{{ route('admin.system_logs') }}"
                class="drawer-link {{ request()->routeIs('admin.system_logs') ? 'active' : '' }}"><i
                    class="fa-solid fa-clipboard-list"></i> System Logs
            </a>
        </div>
    </div>
    
    <div class="drawer-bottom">
        <div class="theme-toggle-container" id="drawerThemeToggle" style="margin-bottom:10px;">
            <button type="button" class="theme-option active" data-theme="light"><i
                    class="fa-solid fa-sun"></i></button>
            <button type="button" class="theme-option" data-theme="dark"><i class="fa-regular fa-moon"></i></button>
            <div class="theme-indicator" aria-hidden="true"></div>
        </div>
        <form action="{{ route('logout') }}" method="POST">
            @csrf
            <button type="submit" class="logout-btn"><span class="logout-icon"><i
                        class="fa-solid fa-right-from-bracket" style="color:#ef4444;"></i></span><span>Log
                    out</span></button>
        </form>
    </div>
</div>
