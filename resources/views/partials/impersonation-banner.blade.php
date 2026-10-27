@if (session('impersonated_role') && in_array(session('impersonator_role'), ['super_admin', 'admin']))
    @php
        $impRole = strtolower(session('impersonated_role'));

        $bannerTheme = match ($impRole) {
            'admin' => [
                'glass' => 'border-red-200/50 bg-red-100/55 text-red-900',
                'pill' => 'bg-red-500/10 text-red-800 border-red-300/40',
                'btn' => 'bg-red-700/90 hover:bg-red-800 text-white',
                'icon' => 'text-red-700',
            ],
            'dentist' => [
                'glass' => 'border-emerald-200/50 bg-emerald-100/55 text-emerald-900',
                'pill' => 'bg-emerald-500/10 text-emerald-800 border-emerald-300/40',
                'btn' => 'bg-emerald-700/90 hover:bg-emerald-800 text-white',
                'icon' => 'text-emerald-700',
            ],
            default => [
                'glass' => 'border-blue-200/50 bg-blue-100/55 text-blue-900',
                'pill' => 'bg-blue-500/10 text-blue-800 border-blue-300/40',
                'btn' => 'bg-blue-700/90 hover:bg-blue-800 text-white',
                'icon' => 'text-blue-700',
            ],
        };
    @endphp

    <div id="impersonationChipWrap">
        <div id="impersonationChip" class="{{ $bannerTheme['glass'] }}">
            <i class="fa-solid fa-user-secret text-sm {{ $bannerTheme['icon'] }}"></i>

            <div id="impersonationChipText">
                <span id="impersonationChipTitle">
                    Viewing as {{ ucfirst(session('impersonated_role')) }}
                </span>

                <span class="impersonation-desktop-dot opacity-50 text-xs">•</span>

                <span
                    class="impersonation-desktop-pill inline-flex items-center rounded-full border px-2 py-0.5 text-[10px] font-bold uppercase tracking-wide {{ $bannerTheme['pill'] }}">
                    Impersonation Active
                </span>
            </div>

            <div id="impersonationChipActions">
                <button type="button" data-ui-click="action-004507b18ecc"
                    class="{{ $bannerTheme['btn'] }} rounded-lg px-3 py-1.5 text-xs font-bold shadow-sm">
                    Return
                </button>

                <button type="button" id="dismissImpersonationChip"
                    class="inline-flex h-8 w-8 items-center justify-center rounded-lg bg-white/45 hover:bg-white/75 text-gray-700 transition">
                    <i class="fa-solid fa-xmark text-xs"></i>
                </button>
            </div>
        </div>
    </div>

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        document.addEventListener('DOMContentLoaded', function() {
            const wrap = document.getElementById('impersonationChipWrap');
            const dismissBtn = document.getElementById('dismissImpersonationChip');
            const storageKey = 'impersonation_banner_dismissed';

            if (sessionStorage.getItem(storageKey) === '1' && wrap) {
                wrap.style.display = 'none';
            }

            dismissBtn?.addEventListener('click', function() {
                sessionStorage.setItem(storageKey, '1');

                if (!wrap) return;
                wrap.style.transition = 'opacity .22s ease, transform .22s ease';
                wrap.style.opacity = '0';
                wrap.style.transform = 'translateY(-8px) scale(.98)';

                setTimeout(() => {
                    wrap.style.display = 'none';
                }, 220);
            });
        });

        function returnToAdmin() {
            fetch("{{ route('admin.stop_impersonation') }}", {
                method: "POST",
                headers: {
                    "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').content
                }
            }).then(() => {
                window.location.href = "{{ route('admin.role_permissions') }}";
            });
        }
    </script>
@endif
