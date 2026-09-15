import './bootstrap';

import './core/helpers';
import './core/theme';
import './core/sidebar';
import './core/modal';
import './core/toast';
import './core/session';

import './ui/header';

const featurePromises = new Map();

function loadFeature(
    key,
    loader
) {
    if (!featurePromises.has(key)) {
        const promise =
            loader()
                .catch(error => {
                    featurePromises.delete(
                        key
                    );

                    throw error;
                });

        featurePromises.set(
            key,
            promise
        );
    }

    return featurePromises.get(
        key
    );
}

function hasAny(
    selectors,
    root = document
) {
    return Boolean(
        root.querySelector(
            selectors
        )
    );
}

function loadPatientRecordModule() {
    return loadFeature(
        'patient-record',
        () =>
            import(
                './records/patient-record-modal'
            )
    );
}

window.openRecordModal =
    async function (source) {
        const module =
            await loadPatientRecordModule();

        return module.openRecordModal(
            source
        );
    };

window.closeRecordModal =
    async function () {
        const module =
            await loadPatientRecordModule();

        return module.closeRecordModal();
    };

function loadOdontogramPreviewModule() {
    return loadFeature(
        'odontogram-preview',
        () =>
            import(
                './odontogram/odontogram-preview'
            )
    );
}

window.loadOdontogramPreviewModule = loadOdontogramPreviewModule;

function initVisibleOdontogramPreviews() {
    const previews =
        document.querySelectorAll(
            '[data-odontogram-preview]'
        );

    if (!previews.length) {
        return;
    }

    const observer =
        new IntersectionObserver(
            entries => {
                entries.forEach(
                    async entry => {
                        if (
                            !entry.isIntersecting
                        ) {
                            return;
                        }

                        observer.unobserve(
                            entry.target
                        );

                        try {
                            const module =
                                await loadOdontogramPreviewModule();

                            module.initOdontogramPreviews?.(
                                entry.target
                            );
                        } catch (error) {
                            console.error(
                                'Unable to initialize odontogram preview.',
                                error
                            );
                        }
                    }
                );
            },
            {
                rootMargin:
                    '200px 0px'
            }
        );

    previews.forEach(
        preview => {
            if (
                preview.closest(
                    '.ui-modal'
                )
            ) {
                return;
            }

            observer.observe(
                preview
            );
        }
    );
}

if (
    document.readyState ===
    'loading'
) {
    document.addEventListener(
        'DOMContentLoaded',
        initVisibleOdontogramPreviews,
        {
            once: true
        }
    );
} else {
    initVisibleOdontogramPreviews();
}

function loadOdontogramThreeModule() {
    return loadFeature(
        'odontogram-three',
        () =>
            import(
                './odontogram/odontogram-primary-stacked-three'
            )
                .then(module => {
                    module.enableOdontogramPrimaryTeeth?.();

                    return module;
                })
    );
}

window.loadOdontogramThreeModule = loadOdontogramThreeModule;

function loadVoiceModule() {
    return loadFeature(
        'voice-input',
        () =>
            import(
                './ui/voice-logic'
            )
    );
}

document.addEventListener(
    'pointerdown',
    event => {
        const trigger =
            event.target.closest?.(
                [
                    '[data-global-voice-trigger]',
                    '.voice-search-mic.external[data-voice-trigger]'
                ].join(',')
            );

        if (!trigger) {
            return;
        }

        loadVoiceModule()
            .then(() => {
                window
                    .initGlobalVoiceInputs?.(
                        document
                    );
            })
            .catch(error => {
                console.error(
                    'Unable to initialize voice input.',
                    error
                );
            });
    },
    true
);

function loadBookingWorkflowModule() {
    return loadFeature(
        'booking-workflow',
        () =>
            import(
                './forms/booking-workflow'
            )
    );
}

window.loadBookingWorkflowModule = loadBookingWorkflowModule;

function loadSkeletonModule() {
    return loadFeature(
        'skeleton',
        () =>
            import(
                './ui/skeleton'
            )
    );
}

window.swapSkeletonContent =
    async function (...args) {
        const module =
            await loadSkeletonModule();

        return module.swapSkeletonContent(
            ...args
        );
    };

window.renderWithStagger =
    async function (...args) {
        const module =
            await loadSkeletonModule();

        return module.renderWithStagger(
            ...args
        );
    };

window.runEnterpriseLoading =
    async function (...args) {
        const module =
            await loadSkeletonModule();

        return module.runEnterpriseLoading(
            ...args
        );
    };

window.setDashboardLoadingStatus =
    async function (...args) {
        const module =
            await loadSkeletonModule();

        return module.setDashboardLoadingStatus(
            ...args
        );
    };

window.finishDashboardLoading =
    async function (...args) {
        const module =
            await loadSkeletonModule();

        return module.finishDashboardLoading(
            ...args
        );
    };

if (
    hasAny(
        '#mainContent.patient-records-page'
    )
) {
    loadFeature(
        'patient-records-page',
        () =>
            import(
                './records/patient-records-page'
            )
    )
        .then(module => {
            module
                .initPatientRecordsPage?.();
        })
        .catch(error => {
            console.error(
                'Unable to initialize patient records page.',
                error
            );
        });
}

if (
    hasAny(
        '.step-content'
    )
) {
    loadBookingWorkflowModule()
        .catch(error => {
            console.error(
                'Unable to load booking workflow.',
                error
            );
        });
}

if (
    hasAny(
        '[data-booking-signature]'
    )
) {
    import(
        './forms/booking-signature'
    );
}

function loadSearchBarModule() {
    return loadFeature(
        'search-bar',
        () =>
            import(
                './ui/search-bar'
            )
    );
}

async function ensureSearchBarReady(
    root = document
) {
    const module =
        await loadSearchBarModule();

    module;

    window
        .initGlobalSearchBars?.(
            root
        );
}

if (
    hasAny(
        '[data-global-search-bar]'
    )
) {
    ensureSearchBarReady(
        document
    )
        .catch(error => {
            console.error(
                'Unable to initialize search bars.',
                error
            );
        });
}

if (
    hasAny(
        '[data-show-more]'
    )
) {
    loadFeature(
        'show-more',
        () =>
            import(
                './ui/show-more'
            )
    )
        .then(() => {
            window.ShowMore
                ?.init?.(
                    document
                );
        })
        .catch(error => {
            console.error(
                'Unable to initialize show more.',
                error
            );
        });
}

function loadViewToggleModule() {
    return loadFeature(
        'view-toggle',
        () =>
            import(
                './ui/view-toggle'
            )
    );
}

document.addEventListener(
    'pointerdown',
    event => {
        const toggle =
            event.target.closest?.(
                '[data-global-view-toggle]'
            );

        if (!toggle) {
            return;
        }

        loadViewToggleModule()
            .then(() => {
                window
                    .initGlobalViewToggles?.(
                        document
                    );
            })
            .catch(error => {
                console.error(
                    'Unable to initialize view toggle.',
                    error
                );
            });
    },
    true
);

function loadFilterSelectModule() {
    return loadFeature(
        'filter-select',
        () =>
            import(
                './ui/filter-select'
            )
    );
}

function loadFilterDrawerModule() {
    return loadFeature(
        'filter-drawer',
        () =>
            import(
                './ui/filter-drawer'
            )
    );
}

function loadFilterControlsModule() {
    return loadFeature(
        'filter-controls',
        () =>
            import(
                './ui/filter-controls'
            )
    );
}

document.addEventListener(
    'pointerdown',
    event => {
        const trigger =
            event.target.closest?.(
                '[data-filter-select-trigger]'
            );

        if (!trigger) {
            return;
        }

        loadFilterSelectModule()
            .then(module => {
                module
                    .initGlobalFilterSelects(
                        document
                    );
            })
            .catch(error => {
                console.error(
                    'Unable to initialize filter select.',
                    error
                );
            });
    },
    true
);

window.openFilterDrawer =
    async function (...args) {
        const module =
            await loadFilterDrawerModule();

        return module.openFilterDrawer(
            ...args
        );
    };

window.closeFilterDrawer =
    async function (...args) {
        const module =
            await loadFilterDrawerModule();

        return module.closeFilterDrawer(
            ...args
        );
    };

window.openFilterPanel =
    async function (...args) {
        const module =
            await loadFilterDrawerModule();

        return module.openFilterPanel(
            ...args
        );
    };

window.closeFilterPanel =
    async function (...args) {
        const module =
            await loadFilterDrawerModule();

        return module.closeFilterPanel(
            ...args
        );
    };

window.initGlobalFilterSelects =
    async function (...args) {
        const module =
            await loadFilterSelectModule();

        return module.initGlobalFilterSelects(
            ...args
        );
    };

window.setGlobalFilterSelectValue =
    async function (...args) {
        const module =
            await loadFilterSelectModule();

        return module.setGlobalFilterSelectValue(
            ...args
        );
    };

window.closeGlobalFilterSelect =
    async function (...args) {
        const module =
            await loadFilterSelectModule();

        return module.closeGlobalFilterSelect(
            ...args
        );
    };

window.syncFilterTagGroup =
    async function (...args) {
        const module =
            await loadFilterControlsModule();

        return module.syncFilterTagGroup(
            ...args
        );
    };

window.bindFilterTagGroup =
    async function (...args) {
        const module =
            await loadFilterControlsModule();

        return module.bindFilterTagGroup(
            ...args
        );
    };

window.updateShowResultsText =
    async function (...args) {
        const module =
            await loadFilterControlsModule();

        return module.updateShowResultsText(
            ...args
        );
    };

window.setGlobalFilterButtonState =
    async function (...args) {
        const module =
            await loadFilterControlsModule();

        return module.setGlobalFilterButtonState(
            ...args
        );
    };

const flatpickrSelectors = [
    '.js-flatpickr-date',
    '.js-flatpickr-date-min-today',
    '.js-flatpickr-date-max-today',
    '.js-flatpickr-date-range-from',
    '.js-flatpickr-date-range-to',
    '.js-flatpickr-time',
    '[data-month-only-picker]'
].join(',');

const customSelectSelectors = [
    'select.js-custom-select',
    '[data-global-selects] select:not([data-native-select])'
].join(',');

function loadCustomSelectModule() {
    return loadFeature(
        'custom-select',
        () =>
            import(
                './ui/custom-select'
            )
    );
}

window.initCustomSelects =
    async function (...args) {
        const module =
            await loadCustomSelectModule();

        return module.initCustomSelects(
            ...args
        );
    };

window.syncCustomSelect =
    async function (...args) {
        const module =
            await loadCustomSelectModule();

        return module.syncCustomSelect(
            ...args
        );
    };

if (hasAny(customSelectSelectors)) {
    loadCustomSelectModule()
        .then(module => {
            module.initCustomSelects(
                document
            );
        })
        .catch(error => {
            console.error(
                'Unable to initialize custom selects.',
                error
            );
        });
}

function loadDatePickerModule() {
    return loadFeature(
        'date-picker',
        () =>
            import(
                './ui/date-picker'
            )
    );
}

window.loadDatePickerModule = loadDatePickerModule;

async function ensureDatePickerReady(
    input
) {
    await loadCustomSelectModule();

    const module =
        await loadDatePickerModule();

    await module
        .initGlobalDatePickers(
            document
        );

    const instance =
        input?._flatpickr ||
        input?.previousElementSibling?._flatpickr;

    if (
        instance &&
        !instance.isOpen
    ) {
        instance.open();
    }
}

document.addEventListener(
    'pointerdown',
    event => {
        const pickerTarget =
            event.target.closest?.(
                `${flatpickrSelectors}, [data-flatpickr-trigger]`
            );

        const input = pickerTarget?.matches?.(
            flatpickrSelectors
        )
            ? pickerTarget
            : pickerTarget?.querySelector?.(
                flatpickrSelectors
            );

        if (!input) {
            return;
        }

        ensureDatePickerReady(
            input
        )
            .catch(error => {
                console.error(
                    'Unable to initialize date picker.',
                    error
                );
            });
    },
    true
);

document.addEventListener(
    'focusin',
    event => {
        const input =
            event.target.closest?.(
                flatpickrSelectors
            );

        if (!input) {
            return;
        }

        ensureDatePickerReady(
            input
        )
            .catch(error => {
                console.error(
                    'Unable to initialize date picker.',
                    error
                );
            });
    },
    true
);

if (
    hasAny(
        [
            'form[data-global-validation]',
            '[data-char-limit]',
            '[data-global-number-stepper]'
        ].join(',')
    )
) {
    import('./forms/validation');
}

let deleteConfirmModulePromise = null;

window.openDeleteConfirmModal =
    async function (options) {

        if (!deleteConfirmModulePromise) {
            deleteConfirmModulePromise =
                import(
                    './ui/delete-confirm'
                )
                    .catch(error => {
                        deleteConfirmModulePromise =
                            null;

                        throw error;
                    });
        }

        const module =
            await deleteConfirmModulePromise;

        return module.openDeleteConfirmModal(
            options
        );
    };

import './ui/empty-state';
import './ui/assistive';
import './ui/tooltips';
import './ui/refresh-watcher';
import './ui/back-to-top';

import './appointments/status-meta';

if (
    hasAny(
        '[data-patient-avatar]'
    )
) {
    loadFeature(
        'profile-avatar',
        () =>
            import(
                './ui/profile-avatar'
            )
    )
        .then(() => {
            window.PatientUI
                ?.initAvatars?.(
                    document
                );
        })
        .catch(error => {
            console.error(
                'Unable to initialize patient avatars.',
                error
            );
        });
}

if (
    hasAny(
        [
            '#currentDate',
            '#mobFab',
            '#mobFabMenu'
        ].join(',')
    )
) {
    import('./ui/header-controls');
}

if (
    hasAny(
        [
            '[data-search-input]',
            '[data-clearable-input]'
        ].join(',')
    )
) {
    import('./ui/input-clear');
}

if (
    hasAny(
        '[data-patient-name]'
    )
) {
    import('./ui/patient-name');
}

if (
    hasAny(
        '[data-global-preview-zoom]'
    )
) {
    import('./ui/preview-zoom');
}

if (
    hasAny(
        '[data-global-page-size]'
    )
) {
    import('./ui/page-size')
        .then(() => {
            window
                .initGlobalPageSizeSelects?.(
                    document
                );
        })
        .catch(error => {
            console.error(
                'Unable to initialize page size controls.',
                error
            );
        });
}

function loadPaginationBarModule() {
    return loadFeature(
        'pagination-bar',
        () =>
            import(
                './ui/pagination-bar'
            )
    );
}

window.loadPaginationBarModule = loadPaginationBarModule;

if (
    hasAny(
        '.global-pagebar'
    )
) {
    loadPaginationBarModule()
        .catch(error => {
            console.error(
                'Unable to initialize pagination.',
                error
            );
        });
}

let chartJsPromise =
    null;

async function loadChartJs() {
    if (window.Chart) {
        return window.Chart;
    }

    if (!chartJsPromise) {
        chartJsPromise =
            import(
                'chart.js/auto'
            )
                .then(module => {
                    window.Chart =
                        module.default;

                    return module.default;
                })
                .catch(error => {
                    chartJsPromise =
                        null;

                    throw error;
                });
    }

    return chartJsPromise;
}

window.loadChartJs = loadChartJs;

document.addEventListener(
    'ui-modal:opened',
    async function (event) {
        const modal =
            event.detail?.modal ||
            document;

        if (
            hasAny(
                flatpickrSelectors,
                modal
            )
        ) {
            try {
                await loadCustomSelectModule();

                const datePickerModule =
                    await loadDatePickerModule();

                await datePickerModule
                    .initGlobalDatePickers(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize modal date pickers.',
                    error
                );
            }
        }

        if (
            hasAny(
                [
                    '[data-char-limit]',
                    'form[data-global-validation]',
                    '[data-global-number-stepper]'
                ].join(','),
                modal
            )
        ) {
            try {
                await import(
                    './forms/validation'
                );

                window
                    .initCharLimitFields?.(
                        modal
                    );

                window
                    .bindFormInputValidation?.(
                        modal
                    );

                window
                    .initGlobalNumberSteppers?.(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize modal validation.',
                    error
                );
            }
        }

        if (
            hasAny(
                '[data-global-search-bar]',
                modal
            )
        ) {
            try {
                await import(
                    './ui/search-bar'
                );

                window
                    .initGlobalSearchBars?.(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize modal search.',
                    error
                );
            }
        }

        if (
            hasAny(
                customSelectSelectors,
                modal
            )
        ) {
            try {
                await import(
                    './ui/custom-select'
                );

                window
                    .initCustomSelects?.(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize custom selects.',
                    error
                );
            }
        }

        if (
            hasAny(
                [
                    '[data-search-input]',
                    '[data-clearable-input]'
                ].join(','),
                modal
            )
        ) {
            try {
                await import(
                    './ui/input-clear'
                );

                window
                    .initSearchClearButtons?.(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize input clear controls.',
                    error
                );
            }
        }

        if (
            hasAny(
                '[data-patient-name]',
                modal
            )
        ) {
            try {
                await import(
                    './ui/patient-name'
                );

                window
                    .initGlobalPatientNames?.(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize patient names.',
                    error
                );
            }
        }

        if (
            hasAny(
                '[data-global-preview-zoom]',
                modal
            )
        ) {
            try {
                await import(
                    './ui/preview-zoom'
                );

                window
                    .initGlobalPreviewZoom?.(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize preview zoom.',
                    error
                );
            }
        }

        if (
            hasAny(
                '[data-patient-avatar]',
                modal
            )
        ) {
            try {
                await import(
                    './ui/profile-avatar'
                );

                window.PatientUI
                    ?.initAvatars?.(
                        modal
                    );
            } catch (error) {
                console.error(
                    'Unable to initialize patient avatars.',
                    error
                );
            }
        }

        const hasOdontogramPreview =
            modal.matches?.(
                '[data-odontogram-preview]'
            ) ||
            modal.querySelector?.(
                '[data-odontogram-preview]'
            );

        if (!hasOdontogramPreview) {
            return;
        }

        try {
            const module =
                await loadOdontogramPreviewModule();

            module
                .initOdontogramPreviews?.(
                    modal
                );
        } catch (error) {
            console.error(
                'Unable to load odontogram preview.',
                error
            );
        }
    }
);
