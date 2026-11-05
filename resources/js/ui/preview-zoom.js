const globalPreviewZoomStates = new WeakMap();

function resolveGlobalPreviewZoomRoot(source = document) {
    let element = source;

    if (typeof source === 'string') {
        element = document.querySelector(source);
    }

    if (!element) return null;

    if (
        element.matches?.(
            '[data-global-preview-zoom]'
        )
    ) {
        return element;
    }

    return element.querySelector?.(
        '[data-global-preview-zoom]'
    ) || null;
}

function getGlobalPreviewZoomNumber(
    root,
    key,
    fallback
) {
    const value = Number(root.dataset[key]);

    return Number.isFinite(value)
        ? value
        : fallback;
}

function applyGlobalPreviewZoom(root) {
    const previewRoot =
        resolveGlobalPreviewZoomRoot(root);

    const state =
        globalPreviewZoomStates.get(
            previewRoot
        );

    if (
        !previewRoot ||
        !state ||
        !state.baseWidth ||
        !state.baseHeight
    ) {
        return;
    }

    const scaledWidth = Math.ceil(
        state.baseWidth * state.scale
    );

    const scaledHeight = Math.ceil(
        state.baseHeight * state.scale
    );

    state.content.style.transformOrigin =
        'top left';

    state.content.style.transform =
        `scale(${state.scale})`;

    state.canvas.style.width =
        `${scaledWidth}px`;

    state.canvas.style.height =
        `${scaledHeight}px`;

    if (state.value) {
        state.value.textContent =
            `${Math.round(
                state.scale * 100
            )}%`;
    }

    if (state.zoomOut) {
        state.zoomOut.disabled =
            state.scale <= state.min;
    }

    if (state.zoomIn) {
        state.zoomIn.disabled =
            state.scale >= state.max;
    }

    previewRoot.dataset.previewScale =
        String(state.scale);
}

function measureGlobalPreviewZoom(root) {
    const previewRoot =
        resolveGlobalPreviewZoomRoot(root);

    const state =
        globalPreviewZoomStates.get(
            previewRoot
        );

    if (!previewRoot || !state) return;

    const {
        stage,
        canvas,
        content
    } = state;

    const isIframe =
        content.tagName.toLowerCase() ===
        'iframe';

    content.style.transform = 'none';
    content.style.transformOrigin =
        'top left';

    canvas.style.width = '';
    canvas.style.height = '';

    let baseWidth = 0;
    let baseHeight = 0;

    if (isIframe) {
        content.style.position = 'absolute';
        content.style.top = '0';
        content.style.left = '0';

        const frameDocument =
            content.contentDocument ||
            content.contentWindow?.document;

        if (!frameDocument) return;

        const html =
            frameDocument.documentElement;

        const body =
            frameDocument.body;

        if (!html || !body) return;

        html.style.overflow = 'hidden';
        body.style.overflow = 'hidden';

        const availableWidth = Math.max(
            320,
            stage.clientWidth - 32
        );

        const contentWidth = Math.max(
            html.scrollWidth || 0,
            body.scrollWidth || 0,
            320
        );

        baseWidth = Math.min(
            availableWidth,
            contentWidth
        );

        content.style.width =
            `${baseWidth}px`;

        baseHeight = Math.max(
            680,
            html.scrollHeight || 0,
            body.scrollHeight || 0
        );

        content.style.height =
            `${baseHeight}px`;
    } else {
        content.style.position = 'relative';
        content.style.top = '';
        content.style.left = '';
        content.style.removeProperty('width');
        content.style.removeProperty('height');

        const rect =
            content.getBoundingClientRect();

        baseWidth = Math.max(
            Math.ceil(rect.width || 0),
            1
        );

        baseHeight = Math.max(
            Math.ceil(rect.height || 0),
            content.scrollHeight || 0,
            1
        );

        content.style.width =
            `${baseWidth}px`;

        content.style.height =
            `${baseHeight}px`;

        content.style.position = 'absolute';
        content.style.top = '0';
        content.style.left = '0';
    }

    state.baseWidth =
        Math.ceil(baseWidth);

    state.baseHeight =
        Math.ceil(baseHeight);

    applyGlobalPreviewZoom(
        previewRoot
    );
}

function setGlobalPreviewZoom(
    root,
    scale
) {
    const previewRoot =
        resolveGlobalPreviewZoomRoot(root);

    const state =
        globalPreviewZoomStates.get(
            previewRoot
        );

    if (!previewRoot || !state) return;

    const nextScale = Math.min(
        state.max,
        Math.max(
            state.min,
            Number(scale) || 1
        )
    );

    state.scale =
        Math.round(nextScale * 100) / 100;

    applyGlobalPreviewZoom(previewRoot);
}

function resetGlobalPreviewZoom(root) {
    const previewRoot =
        resolveGlobalPreviewZoomRoot(root);

    const state =
        globalPreviewZoomStates.get(
            previewRoot
        );

    if (!previewRoot || !state) return;

    state.scale = 1;

    applyGlobalPreviewZoom(previewRoot);

    state.stage.scrollTop = 0;
    state.stage.scrollLeft = 0;
}

function refreshGlobalPreviewZoom(root) {
    const previewRoot =
        resolveGlobalPreviewZoomRoot(root);

    if (!previewRoot) return;

    if (
        !globalPreviewZoomStates.has(
            previewRoot
        )
    ) {
        initGlobalPreviewZoom(previewRoot);
    }

    requestAnimationFrame(() => {
        measureGlobalPreviewZoom(
            previewRoot
        );
    });
}

function getGlobalPreviewZoom(root) {
    const previewRoot =
        resolveGlobalPreviewZoomRoot(root);

    return (
        globalPreviewZoomStates.get(
            previewRoot
        )?.scale || 1
    );
}

function initGlobalPreviewZoom(
    root = document
) {
    const scope =
        root &&
            typeof root.querySelectorAll ===
            'function'
            ? root
            : document;

    const previews = [];

    if (
        scope.matches?.(
            '[data-global-preview-zoom]'
        )
    ) {
        previews.push(scope);
    }

    scope
        .querySelectorAll?.(
            '[data-global-preview-zoom]'
        )
        .forEach(preview => {
            previews.push(preview);
        });

    previews.forEach(preview => {
        if (
            globalPreviewZoomStates.has(
                preview
            )
        ) {
            return;
        }

        const stage =
            preview.querySelector(
                '[data-preview-stage]'
            );

        const canvas =
            preview.querySelector(
                '[data-preview-canvas]'
            );

        const content =
            preview.querySelector(
                '[data-preview-content]'
            );

        if (
            !stage ||
            !canvas ||
            !content
        ) {
            return;
        }

        const state = {
            stage,
            canvas,
            content,

            zoomOut:
                preview.querySelector(
                    '[data-preview-zoom-out]'
                ),

            zoomIn:
                preview.querySelector(
                    '[data-preview-zoom-in]'
                ),

            zoomReset:
                preview.querySelector(
                    '[data-preview-zoom-reset]'
                ),

            value:
                preview.querySelector(
                    '[data-preview-zoom-value]'
                ),

            min:
                getGlobalPreviewZoomNumber(
                    preview,
                    'previewMin',
                    0.5
                ),

            max:
                getGlobalPreviewZoomNumber(
                    preview,
                    'previewMax',
                    2
                ),

            step:
                getGlobalPreviewZoomNumber(
                    preview,
                    'previewStep',
                    0.1
                ),

            scale: 1,
            baseWidth: 0,
            baseHeight: 0
        };

        globalPreviewZoomStates.set(
            preview,
            state
        );

        content.style.transition =
            'transform 180ms ease';

        state.zoomOut?.addEventListener(
            'click',
            () => {
                setGlobalPreviewZoom(
                    preview,
                    state.scale -
                    state.step
                );
            }
        );

        state.zoomIn?.addEventListener(
            'click',
            () => {
                setGlobalPreviewZoom(
                    preview,
                    state.scale +
                    state.step
                );
            }
        );

        state.zoomReset?.addEventListener(
            'click',
            () => {
                resetGlobalPreviewZoom(
                    preview
                );
            }
        );

        if (
            content.tagName.toLowerCase() ===
            'iframe'
        ) {
            content.addEventListener(
                'load',
                () => {
                    refreshGlobalPreviewZoom(
                        preview
                    );
                }
            );
        }

        requestAnimationFrame(() => {
            measureGlobalPreviewZoom(
                preview
            );
        });
    });
}

document.addEventListener(
    'ui-modal:opened',
    event => {
        const modal =
            event.detail?.modal ||
            document;

        initGlobalPreviewZoom(
            modal
        );
    }
);

document.addEventListener(
    'keydown',
    event => {
        if (
            !event.ctrlKey &&
            !event.metaKey
        ) {
            return;
        }

        const openModal =
            document.querySelector(
                '.ui-modal.open'
            );

        const previewRoot =
            openModal?.querySelector(
                '[data-global-preview-zoom]'
            );

        if (!previewRoot) return;

        const state =
            globalPreviewZoomStates.get(
                previewRoot
            );

        if (!state) return;

        if (
            event.key === '+' ||
            event.key === '='
        ) {
            event.preventDefault();

            setGlobalPreviewZoom(
                previewRoot,
                state.scale + state.step
            );
        }

        if (event.key === '-') {
            event.preventDefault();

            setGlobalPreviewZoom(
                previewRoot,
                state.scale - state.step
            );
        }

        if (event.key === '0') {
            event.preventDefault();

            resetGlobalPreviewZoom(
                previewRoot
            );
        }
    }
);

let globalPreviewResizeFrame = null;

window.addEventListener(
    'resize',
    () => {
        window.cancelAnimationFrame(
            globalPreviewResizeFrame
        );

        globalPreviewResizeFrame =
            window.requestAnimationFrame(
                () => {
                    document
                        .querySelectorAll(
                            [
                                '.ui-modal.open ',
                                '[data-global-preview-zoom]'
                            ].join('')
                        )
                        .forEach(preview => {
                            refreshGlobalPreviewZoom(
                                preview
                            );
                        });
                }
            );
    }
);

window.initGlobalPreviewZoom = initGlobalPreviewZoom;
window.refreshGlobalPreviewZoom = refreshGlobalPreviewZoom;
window.setGlobalPreviewZoom = setGlobalPreviewZoom;
window.resetGlobalPreviewZoom = resetGlobalPreviewZoom;
window.getGlobalPreviewZoom = getGlobalPreviewZoom;