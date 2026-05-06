<div id="dashboardLoadingStatus"
    class="dashboard-loading-status rounded-[1rem] border border-gray-200 bg-white/70 px-4 py-3 text-xs font-bold text-gray-500 shadow-sm backdrop-blur-md">
    <div class="flex items-center justify-between gap-3">
        <span id="dashboardLoadingText" class="inline-flex items-center gap-2">
            <span class="w-2 h-2 rounded-full bg-[#8B0000] dashboard-loading-dot"></span>
            {{ $label ?? 'Preparing your dashboard' }}
        </span>
        <span id="dashboardLoadingPercent" class="text-[#8B0000]">0%</span>
    </div>
    <div class="mt-2 h-1.5 rounded-full bg-gray-100 overflow-hidden">
        <div id="dashboardLoadingBar" class="h-full w-0 rounded-full bg-[#8B0000] dashboard-loading-bar"></div>
    </div>
</div>
