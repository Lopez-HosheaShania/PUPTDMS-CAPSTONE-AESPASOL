<nav id="mobileBottomNav" data-dashboard-url="{{ route('homepage') }}"
    class="bg-white/95 backdrop-blur-md shadow-[0_12px_40px_rgba(0,0,0,0.15)] rounded-full border border-gray-100 transition-colors duration-300 dark:bg-[#0d1117]/95 dark:border-[#21262d] dark:shadow-[0_12px_40px_rgba(0,0,0,0.6)]">

    <div class="grid grid-cols-5 items-center w-full h-[66px] relative z-10 px-1">
        @php $isHome = request()->routeIs('homepage') || request()->routeIs('patient.dashboard') || request()->is('patient/dashboard'); @endphp
        <a href="{{ route('homepage') }}"
            class="flex flex-col items-center justify-center w-full h-full relative pb-1 transition-all duration-300 {{ $isHome ? 'text-[#8B0000] dark:text-[#ff6b6b]' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500' }}">
            <i class="fa-solid fa-house text-[18px] mb-1 transition-transform duration-300 {{ $isHome ? 'scale-110' : '' }}"></i>
            <span class="text-[10px] font-bold">Home</span>
            <span class="absolute bottom-1 w-1 h-1 rounded-full bg-[#8B0000] dark:bg-[#ff6b6b] transition-opacity duration-300 {{ $isHome ? 'opacity-100' : 'opacity-0' }}"></span>
        </a>

        @php $isAppt = request()->routeIs('patient.appointment.*') || request()->is('patient/appointment*'); @endphp
        <a href="{{ route('patient.appointment.index') }}"
            class="flex flex-col items-center justify-center w-full h-full relative pb-1 transition-all duration-300 {{ $isAppt ? 'text-[#8B0000] dark:text-[#ff6b6b]' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500' }}">
            <i class="fa-solid fa-calendar-check text-[18px] mb-1 transition-transform duration-300 {{ $isAppt ? 'scale-110' : '' }}"></i>
            <span class="text-[10px] font-bold">Appointment</span>
            <span class="absolute bottom-1 w-1 h-1 rounded-full bg-[#8B0000] dark:bg-[#ff6b6b] transition-opacity duration-300 {{ $isAppt ? 'opacity-100' : 'opacity-0' }}"></span>
        </a>

        <div class="relative w-full h-full flex items-center justify-center">
            <button id="mobFab"
                class="w-[46px] h-[46px] rounded-full bg-gradient-to-tr from-[#8B0000] to-[#b5282a] text-white flex items-center justify-center shadow-[0_4px_12px_rgba(139,0,0,0.25)] active:scale-95 transition-transform duration-300"
                aria-label="Quick actions">
                <i class="fa-solid fa-plus text-xl"></i>
            </button>
        </div>

        @php $isRec = request()->routeIs('patient.record') || request()->is('patient/record*'); @endphp
        <a href="{{ route('patient.record') }}"
            class="flex flex-col items-center justify-center w-full h-full relative pb-1 transition-all duration-300 {{ $isRec ? 'text-[#8B0000] dark:text-[#ff6b6b]' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500' }}">
            <i class="fa-solid fa-folder-open text-[18px] mb-1 transition-transform duration-300 {{ $isRec ? 'scale-110' : '' }}"></i>
            <span class="text-[10px] font-bold">Record</span>
            <span class="absolute bottom-1 w-1 h-1 rounded-full bg-[#8B0000] dark:bg-[#ff6b6b] transition-opacity duration-300 {{ $isRec ? 'opacity-100' : 'opacity-0' }}"></span>
        </a>

        @php $isAbout = request()->routeIs('patient.about.us') || request()->is('patient/about*'); @endphp
        <a href="{{ route('patient.about.us') }}"
            class="flex flex-col items-center justify-center w-full h-full relative pb-1 transition-all duration-300 {{ $isAbout ? 'text-[#8B0000] dark:text-[#ff6b6b]' : 'text-gray-400 hover:text-gray-600 dark:text-gray-500' }}">
            <i class="fa-solid fa-circle-info text-[18px] mb-1 transition-transform duration-300 {{ $isAbout ? 'scale-110' : '' }}"></i>
            <span class="text-[10px] font-bold">About</span>
            <span class="absolute bottom-1 w-1 h-1 rounded-full bg-[#8B0000] dark:bg-[#ff6b6b] transition-opacity duration-300 {{ $isAbout ? 'opacity-100' : 'opacity-0' }}"></span>
        </a>
    </div>
</nav>

<div id="mobFabMenu"
    class="bg-white dark:bg-[#161b22] border border-gray-100 dark:border-[#21262d] rounded-2xl shadow-[0_12px_40px_rgba(0,0,0,0.15)] py-2 z-[10000] overflow-hidden origin-bottom">

    <a href="{{ route('patient.book.appointment') }}"
        class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-red-50 dark:hover:bg-[#21262d] transition-colors">
        <div class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/30 text-[#8B0000] dark:text-red-400 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-calendar-plus"></i>
        </div>
        Book Appointment
    </a>

    <div class="h-px bg-gray-100 dark:bg-[#21262d] mx-4"></div>

    <button type="button" data-quick-action="record"
        class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-red-50 dark:hover:bg-[#21262d] transition-colors w-full text-left">
        <div class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/30 text-[#8B0000] dark:text-red-400 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-file-medical"></i>
        </div>
        Request Record
    </button>

    <div class="h-px bg-gray-100 dark:bg-[#21262d] mx-4"></div>

    <button type="button" data-quick-action="clearance"
        class="flex items-center gap-3 px-4 py-3 text-sm font-semibold text-gray-700 dark:text-gray-200 hover:bg-red-50 dark:hover:bg-[#21262d] transition-colors w-full text-left">
        <div class="w-8 h-8 rounded-full bg-red-50 dark:bg-red-900/30 text-[#8B0000] dark:text-red-400 flex items-center justify-center flex-shrink-0">
            <i class="fa-solid fa-file-circle-check"></i>
        </div>
        Request Clearance
    </button>
</div>