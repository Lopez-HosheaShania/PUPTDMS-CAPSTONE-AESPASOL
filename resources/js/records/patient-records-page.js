const requestControllers = {
    records: null,
    documentRequests: null,
};

function getPage() {
    return document.querySelector(
        '#mainContent.patient-records-page'
    );
}

function getPageData() {
    return (
        window.patientRecordsPageData ||
        {}
    );
}

function setPageData(data) {
    window.patientRecordsPageData = data;
}

function getSectionConfig(type) {
    if (type === 'documentRequests') {
        return {
            dataKey: 'documentRequests',

            sectionSelector:
                '#documentRequestHistory',

            loadingSelector:
                '#documentRequestHistory',

            pageParam:
                'document_requests_page',

            perPageParam:
                'document_requests_per_page',

            paginationTop:
                'documentRequestsPaginationTop',

            paginationBottom:
                'documentRequestsPaginationBottom',

            infoTop:
                'documentRequestsPageInfoTop',

            infoBottom:
                'documentRequestsPageInfoBottom',

            barTop:
                'documentRequestsPagebarTop',

            barBottom:
                'documentRequestsPagebarBottom',

            label: 'requests',
        };
    }

    return {
        dataKey: 'records',

        sectionSelector:
            '#visitHistorySection',

        loadingSelector:
            '#visitHistorySection',

        pageParam:
            'records_page',

        perPageParam:
            'per_page',

        paginationTop:
            'recordsPaginationTop',

        paginationBottom:
            'recordsPaginationBottom',

        infoTop:
            'recordsPageInfoTop',

        infoBottom:
            'recordsPageInfoBottom',

        barTop:
            'recordsPagebarTop',

        barBottom:
            'recordsPagebarBottom',

        label: 'visits',
    };
}

function renderPagination(
    type
) {
    if (
        typeof window.renderGlobalPagination !==
        'function'
    ) {
        return;
    }

    const config =
        getSectionConfig(type);

    const data =
        getPageData()[
        config.dataKey
        ];

    if (!data) {
        return;
    }

    window.renderGlobalPagination({
        currentPage:
            data.currentPage,

        lastPage:
            data.lastPage,

        total:
            data.total,

        from:
            data.from,

        to:
            data.to,

        containers: [
            document.getElementById(
                config.paginationTop
            ),

            document.getElementById(
                config.paginationBottom
            ),
        ],

        infoElements: [
            document.getElementById(
                config.infoTop
            ),

            document.getElementById(
                config.infoBottom
            ),
        ],

        bars: [
            document.getElementById(
                config.barTop
            ),

            document.getElementById(
                config.barBottom
            ),
        ],

        itemLabel:
            config.label,

        onPageChange:
            page => {
                loadRecordsSection(
                    type,
                    {
                        page,
                    }
                );
            },
    });
}

function replaceSectionFromHtml(
    html,
    type
) {
    const config =
        getSectionConfig(type);

    const parser =
        new DOMParser();

    const responseDocument =
        parser.parseFromString(
            html,
            'text/html'
        );

    const incoming =
        responseDocument.querySelector(
            config.sectionSelector
        );

    const current =
        document.querySelector(
            config.sectionSelector
        );

    if (
        !incoming ||
        !current
    ) {
        throw new Error(
            `Unable to update ${type}.`
        );
    }

    current.replaceWith(
        incoming
    );

    const incomingData =
        responseDocument.querySelector(
            '#patientRecordsPageData'
        );

    if (incomingData) {
        setPageData(
            JSON.parse(
                incomingData.textContent ||
                '{}'
            )
        );
    }
}

async function loadRecordsSection(
    type,
    {
        page = null,
        perPage = null,
    } = {}
) {
    const config =
        getSectionConfig(type);

    const url =
        new URL(
            window.location.href
        );

    if (page !== null) {
        url.searchParams.set(
            config.pageParam,
            page
        );
    }

    if (perPage !== null) {
        url.searchParams.set(
            config.perPageParam,
            perPage
        );

        url.searchParams.set(
            config.pageParam,
            1
        );
    }

    requestControllers[type]
        ?.abort();

    requestControllers[type] =
        new AbortController();

    const section =
        document.querySelector(
            config.loadingSelector
        );

    section?.classList.add(
        'is-loading'
    );

    try {
        const response =
            await fetch(
                url.toString(),
                {
                    headers: {
                        'X-Requested-With':
                            'XMLHttpRequest',
                    },

                    signal:
                        requestControllers[type]
                            .signal,
                }
            );

        if (!response.ok) {
            throw new Error(
                `Request failed: ${response.status}`
            );
        }

        const html =
            await response.text();

        replaceSectionFromHtml(
            html,
            type
        );

        window.history.replaceState(
            {},
            '',
            url
        );

        renderPagination(
            type
        );

        window
            .initGlobalPageSizeSelects?.(
                document
            );

        if (
            type ===
            'documentRequests'
        ) {
            document
                .getElementById(
                    'documentRequestHistory'
                )
                ?.scrollIntoView({
                    behavior: 'smooth',
                    block: 'start',
                });
        }
    } catch (error) {
        if (
            error.name ===
            'AbortError'
        ) {
            return;
        }

        console.error(
            'Unable to update dental records.',
            error
        );
    } finally {
        document
            .querySelector(
                config.loadingSelector
            )
            ?.classList.remove(
                'is-loading'
            );
    }
}

function openRequestedRecord() {
    const params =
        new URLSearchParams(
            window.location.search
        );

    const appointmentId =
        params.get(
            'appointment'
        );

    if (!appointmentId) {
        return;
    }

    const button =
        document.querySelector(
            `.ui-action-btn[data-record][data-appointment-id="${CSS.escape(
                appointmentId
            )}"]`
        );

    if (!button) {
        return;
    }

    button.scrollIntoView({
        behavior: 'smooth',
        block: 'center',
    });

    window.setTimeout(
        () => {
            window
                .openRecordModal?.(
                    button
                );
        },
        250
    );
}

function initPatientRecordsPage() {
    if (!getPage()) {
        return;
    }

    renderPagination(
        'records'
    );

    renderPagination(
        'documentRequests'
    );

    window
        .initGlobalPageSizeSelects?.(
            document
        );

    openRequestedRecord();
}

window.changeRecordsPageSize =
    function (size) {
        loadRecordsSection(
            'records',
            {
                perPage:
                    size,
            }
        );
    };

window.changeDocumentRequestsPageSize =
    function (size) {
        loadRecordsSection(
            'documentRequests',
            {
                perPage:
                    size,
            }
        );
    };

export {
    initPatientRecordsPage,
};