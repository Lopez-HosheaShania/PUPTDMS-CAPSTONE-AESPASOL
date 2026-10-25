@php
    $previewOdontogramData = $odontogramData ?? [];
    $showEditButton = $showEditButton ?? false;
    $showHistoricalNotice = $showHistoricalNotice ?? false;
    $tallPreview = $tallPreview ?? false;
@endphp

<div @class([
    'odontogram-preview',
    'odontogram-preview-tall' => $tallPreview,
]) data-odontogram-preview data-odontogram='@json($previewOdontogramData, JSON_HEX_TAG | JSON_HEX_APOS | JSON_HEX_AMP | JSON_HEX_QUOT)'>

    @if ($showEditButton)
        <div class="odontogram-preview-head">

            <a href="#" id="recordModalEditBtn" class="ui-btn ui-btn-primary ui-btn-sm" hidden>

                <i class="fa-solid fa-pen-to-square"></i>

                <span>
                    Edit Odontogram
                </span>

            </a>

        </div>
    @endif

    <div class="odontogram-preview-layout">
        <div class="odontogram-preview-stage">
            @if ($showHistoricalNotice)
                <div id="recordHistoricalVersionNotice"
                    class="global-confirm-alert odontogram-preview-history-notice !items-center !gap-2.5 !px-3 !py-2.5"
                    hidden>

                    <i class="fa-solid fa-clock-rotate-left !mt-0"></i>

                    <div class="min-w-0 flex-1">

                        <div class="flex items-baseline gap-2 min-w-0">

                            <strong id="recordHistoricalVersionTitle" class="!mt-0 whitespace-nowrap">
                                Viewing version 1
                            </strong>

                            <span class="!mt-0 hidden sm:block">
                                Previous odontogram snapshot · read-only
                            </span>

                        </div>

                    </div>

                    <button type="button" id="recordBackToCurrentBtn"
                        class="ui-btn ui-btn-secondary ui-btn-sm ml-auto flex-shrink-0 whitespace-nowrap">

                        <span class="!inline-flex items-center gap-1.5 !mt-0 sm:!hidden">

                            <i class="fa-solid fa-arrow-left"></i>

                            <span class="!inline !mt-0">
                                Current
                                (<span data-record-latest-version class="!inline !mt-0">v1</span>)
                            </span>

                        </span>

                        <span class="!hidden sm:!inline-flex items-center !mt-0">

                            Return to latest
                            (<span data-record-latest-version class="!inline !mt-0">v1</span>)

                        </span>

                    </button>

                </div>
            @endif

            <div class="odontogram-loading" data-odontogram-preview-loading role="status" aria-live="polite"
                aria-label="Generating 3D odontogram model.">

                <div aria-hidden="true">
                    <span></span>
                    <span></span>
                    <span></span>
                </div>

                <p>
                    Generating 3D model...
                </p>

            </div>

            <div class="odontogram-preview-canvas" data-odontogram-preview-canvas>
            </div>

            <div class="odontogram-preview-tooltip" data-odontogram-preview-tooltip>
            </div>
        </div>

        <aside class="odontogram-preview-side-panel" data-odontogram-preview-panel aria-live="polite">
            <div class="empty-state odontogram-preview-panel-empty" data-odontogram-preview-empty>

                <div class="empty-state-icon">
                    <i class="fa-solid fa-tooth" aria-hidden="true">
                    </i>
                </div>

                <p class="empty-state-title">
                    Select a tooth
                </p>

                <p class="empty-state-sub">
                    Click any tooth in the 3D odontogram
                    to view its saved markings and dental information.
                </p>

            </div>

            <div class="odontogram-preview-panel-details" data-odontogram-preview-details hidden>
                <div class="odontogram-preview-panel-header">
                    <div class="odontogram-preview-panel-heading">
                        <div class="odontogram-preview-panel-icon">
                            <i class="fa-solid fa-tooth"></i>
                        </div>

                        <div>
                            <h3 class="odontogram-preview-panel-title" data-odontogram-preview-title>
                                Tooth
                            </h3>

                            <p class="odontogram-preview-panel-subtitle" data-odontogram-preview-subtitle>
                                Tooth information
                            </p>
                        </div>
                    </div>
                </div>

                <div class="odontogram-preview-panel-body">
                    <div class="odontogram-preview-detail-main">
                        <div>
                            <p class="odontogram-preview-detail-label" data-odontogram-preview-condition-label>
                                Overall Marking
                            </p>

                            <div class="odontogram-preview-condition" data-odontogram-preview-condition>
                                Healthy
                            </div>
                        </div>
                    </div>

                    <div class="odontogram-preview-info-grid">
                        <div class="odontogram-preview-info-item">
                            <span class="global-info-label">
                                FDI Number
                            </span>

                            <span class="global-info-value" data-odontogram-preview-fdi>
                                —
                            </span>
                        </div>

                        <div class="odontogram-preview-info-item">
                            <span class="global-info-label">
                                Quadrant
                            </span>

                            <span class="global-info-value" data-odontogram-preview-quadrant>
                                —
                            </span>
                        </div>

                        <div class="odontogram-preview-info-item">
                            <span class="global-info-label">
                                Tooth Type
                            </span>

                            <span class="global-info-value" data-odontogram-preview-type>
                                —
                            </span>
                        </div>

                        <div class="odontogram-preview-info-item">
                            <span class="global-info-label">
                                Arch
                            </span>

                            <span class="global-info-value" data-odontogram-preview-arch>
                                —
                            </span>
                        </div>
                    </div>

                    <section class="odontogram-preview-markings">
                        <p class="odontogram-preview-detail-label">
                            Saved Markings
                        </p>

                        <div class="odontogram-preview-marking-list" data-odontogram-preview-markings>
                        </div>
                    </section>
                </div>
            </div>
        </aside>
    </div>
</div>
