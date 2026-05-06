<aside id="sidebar">
    <div class="sidebar-inner">
        <div class="toggle-row flex justify-end mb-3">
            <button type="button" onclick="toggleSidebar()" id="sidebarToggleBtn" class="sidebar-toggle-btn" aria-label="Toggle sidebar">
                <i id="sidebarIcon" class="fa-solid fa-xmark"></i>
            </button>
        </div>
        <div class="nav-section-label">Navigation</div>
        <nav style="display:flex;flex-direction:column;gap:2px;">
            @foreach([
                ['route'=>'dentist.dentist.dashboard','icon'=>'fa-chart-line','label'=>'Dashboard'],
                ['route'=>'dentist.dentist.patients','icon'=>'fa-users','label'=>'Patients'],
                ['route'=>'dentist.dentist.appointments','icon'=>'fa-calendar-check','label'=>'Appointments'],
                ['route'=>'dentist.dentist.documentrequests','icon'=>'fa-file-circle-check','label'=>'Document Requests'],
                ['route'=>'dentist.dentist.inventory','icon'=>'fa-box','label'=>'Inventory'],
                ['route'=>'dentist.dentist.report','icon'=>'fa-file','label'=>'Reports'],
            ] as $nav)
            <a href="{{ route($nav['route']) }}"
                class="sidebar-nav-item {{ request()->routeIs($nav['route']) ? 'active' : '' }}">
                <span class="sidebar-nav-icon"><i class="fa-solid {{ $nav['icon'] }}"></i></span>
                <span class="sidebar-nav-text">{{ $nav['label'] }}</span>
                <span class="sidebar-tooltip">{{ $nav['label'] }}</span>
            </a>
            @endforeach
        </nav>
    </div>
</aside>