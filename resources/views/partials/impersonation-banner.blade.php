@if (session('role_preview_active') && in_array(session('preview_role'), ['patient', 'dentist'], true))
    @php
        $previewRole = strtolower(session('preview_role'));

        $bannerTheme =
            $previewRole === 'dentist'
                ? [
                    'glass' => 'border-emerald-200/50 bg-emerald-100/55 text-emerald-900',

                    'pill' => 'bg-emerald-500/10 text-emerald-800 border-emerald-300/40',

                    'btn' => 'bg-emerald-700/90 hover:bg-emerald-800 text-white',

                    'icon' => 'text-emerald-700',
                ]
                : [
                    'glass' => 'border-blue-200/50 bg-blue-100/55 text-blue-900',

                    'pill' => 'bg-blue-500/10 text-blue-800 border-blue-300/40',

                    'btn' => 'bg-blue-700/90 hover:bg-blue-800 text-white',

                    'icon' => 'text-blue-700',
                ];
    @endphp

    <div id="impersonationChipWrap">

        <div id="impersonationChip" class="{{ $bannerTheme['glass'] }}">

            <i
                class="
                    fa-solid
                    fa-eye
                    text-sm
                    {{ $bannerTheme['icon'] }}
                "></i>

            <div id="impersonationChipText">

                <span id="impersonationChipTitle">
                    Viewing as
                    {{ ucfirst($previewRole) }}
                </span>

                <span
                    class="
                        impersonation-desktop-dot
                        opacity-50
                        text-xs
                    ">
                    •
                </span>

                <span
                    class="
                        impersonation-desktop-pill
                        inline-flex
                        items-center
                        rounded-full
                        border
                        px-2
                        py-0.5
                        text-[10px]
                        font-bold
                        uppercase
                        tracking-wide
                        {{ $bannerTheme['pill'] }}
                    ">
                    Demo Mode
                </span>

                <span
                    class="
                        impersonation-desktop-dot
                        opacity-50
                        text-xs
                    ">
                    •
                </span>

                <span
                    class="
                        hidden
                        md:inline
                        text-xs
                        font-semibold
                        opacity-80
                    ">
                    Synthetic data only.
                    Actions are disabled.
                </span>

            </div>

            <div id="impersonationChipActions">

                <button type="button" data-ui-click="action-004507b18ecc" data-preview-return data-preview-allow
                    class="
        {{ $bannerTheme['btn'] }}
        rounded-lg
        px-3
        py-1.5
        text-xs
        font-bold
        shadow-sm
    ">
                    Return
                </button>

            </div>

        </div>

    </div>

    <script nonce="{{ \Illuminate\Support\Facades\Vite::cspNonce() }}">
        window.returnToAdminPreview = function() {
            window.GlobalLoading
                ?.show({
                    title: 'Returning to Admin…',

                    message: 'Closing role preview.',
                });

            fetch(
                    "{{ route('admin.role_preview.stop') }}", {
                        method: 'POST',

                        headers: {
                            'Accept': 'application/json',

                            'X-CSRF-TOKEN': document
                                .querySelector(
                                    'meta[name="csrf-token"]'
                                )
                                ?.content,
                        },
                    }
                )
                .then(
                    async response => {
                        let data = {};

                        try {
                            data =
                                await response.json();
                        } catch (_) {}

                        if (
                            response.status === 401 ||
                            response.status === 419 ||
                            data?.expired === true
                        ) {
                            window.GlobalLoading
                                ?.hide();

                            const handled =
                                await window
                                .handleInvalidSessionResponse
                                ?.(
                                    data,
                                    response.status
                                );

                            if (handled) {
                                return;
                            }
                        }

                        if (!response.ok) {
                            throw new Error(
                                data.message ||
                                'Unable to stop preview.'
                            );
                        }

                        window.location.href =
                            data.redirect ||
                            "{{ route('admin.role_permissions') }}";
                    }
                )
                .catch(
                    error => {
                        window.GlobalLoading
                            ?.hide();

                        window.showToast
                            ?.(
                                'Preview Error',
                                error.message ||
                                'Unable to return to Admin.',
                                'error'
                            );
                    }
                );
        };
    </script>
@endif
