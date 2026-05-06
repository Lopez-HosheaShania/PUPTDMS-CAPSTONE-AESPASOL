<div id="mobileDrawerOverlay" onclick="closeDrawer()"></div>

<div id="mobileDrawer">
    <div class="drawer-header">
        <div class="drawer-brand">
            <img src="{{ asset('images/PUP.png') }}" style="width:26px;height:26px;object-fit:contain;" alt="PUP">
            <img src="{{ asset('images/PUPT-DMS-Logo.png') }}" style="width:24px;height:24px;object-fit:contain;" alt="DMS">
            <span class="drawer-brand-text">PUP Taguig<br>Dental Clinic</span>
        </div>
        <button class="drawer-close-btn" onclick="closeDrawer()"><i class="fa-solid fa-xmark"></i></button>
    </div>
    <nav class="drawer-nav">
        <div class="drawer-section-label">Navigation</div>
        @foreach([
            ['route'=>'dentist.dentist.dashboard','icon'=>'fa-chart-line','label'=>'Dashboard'],
            ['route'=>'dentist.dentist.patients','icon'=>'fa-users','label'=>'Patients'],
            ['route'=>'dentist.dentist.appointments','icon'=>'fa-calendar-check','label'=>'Appointments'],
            ['route'=>'dentist.dentist.documentrequests','icon'=>'fa-file-circle-check','label'=>'Document Requests'],
            ['route'=>'dentist.dentist.inventory','icon'=>'fa-box','label'=>'Inventory'],
            ['route'=>'dentist.dentist.report','icon'=>'fa-file','label'=>'Reports'],
        ] as $nav)
        <a href="{{ route($nav['route']) }}"
            class="drawer-nav-link {{ request()->routeIs($nav['route']) ? 'active' : '' }}">
            <span class="dnav-icon"><i class="fa-solid {{ $nav['icon'] }}"></i></span>
            {{ $nav['label'] }}
        </a>
        @endforeach
    </nav>
    <div class="drawer-footer">
        <form method="POST" action="{{ route('logout') }}" style="margin:0;">
            @csrf
            <button type="submit" class="drawer-logout-btn">
                <i class="fa-solid fa-right-from-bracket"></i> Log out
            </button>
        </form>
    </div>
</div>