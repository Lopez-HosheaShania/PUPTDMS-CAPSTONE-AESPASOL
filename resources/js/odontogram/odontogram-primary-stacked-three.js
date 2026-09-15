import * as THREE from 'three';
import * as base from './odontogram-three';
import { addOdontogramPrimaryArches, PRIMARY_UPPER, PRIMARY_LOWER } from './odontogram-primary-stacked-model';

export {
    resizeOdontogramThreeScene, resetOdontogramThreeCamera, getOdontogramThreeToothMesh,
    setOdontogramThreeWireframe, setOdontogramThreeSoftTissue
} from './odontogram-three';

const primaryNumbers = new Set([...PRIMARY_UPPER, ...PRIMARY_LOWER]);
const viewForTooth = tooth => primaryNumbers.has(Number(tooth)) ? 'baby' : 'adult';

function captureView(presentation, pickObjects) {
    const bounds = new THREE.Box3(), fullViewSize = new THREE.Vector3(), center = new THREE.Vector3();
    presentation.root.traverse(object => { if (object.isMesh && object.visible) bounds.expandByObject(object); });
    bounds.getSize(fullViewSize); bounds.getCenter(center);
    return { ...presentation, pickObjects, fullViewSize, center };
}

function syncToggle(state) {
    const toggle = state.dentitionToggle;
    if (!toggle) return;
    toggle.control.dataset.view = state.activeDentition;
    toggle.input.checked = state.activeDentition === 'baby';
    state.renderer.domElement.setAttribute('aria-label', `${toggle.input.checked ? 'Baby' : 'Adult'} teeth. Click a surface to select it; drag to rotate.`);
}

function guardViewGestures(state) {
    const canvas = state.renderer.domElement, onClick = state.onToothClick;
    state.dentitionEpoch = 0; state.dentitionPressEpoch = null;
    canvas.addEventListener('pointerdown', event => {
        if (event.button === 0 && event.isPrimary !== false) state.dentitionPressEpoch = state.dentitionEpoch;
    });
    for (const type of ['pointerup', 'pointercancel', 'lostpointercapture']) {
        canvas.addEventListener(type, () => { state.dentitionPressEpoch = null; });
    }
    state.onToothClick = (tooth, mesh, event, surfaceKey) => {
        if (mesh && event?.pointerId != null && state.dentitionPressEpoch != null
            && state.dentitionPressEpoch !== state.dentitionEpoch) return;
        onClick?.(tooth, mesh, event, surfaceKey);
    };
}

function mountToggle(state) {
    const document = state.container.ownerDocument || globalThis.document;
    if (document.defaultView?.getComputedStyle?.(state.container)?.position === 'static') state.container.style.position = 'relative';
    state.container.querySelectorAll('[data-odontogram-dentition-toggle]').forEach(control => control.remove());
    const control = document.createElement('label');
    control.className = 'odontogram-dentition-toggle';
    control.setAttribute('data-odontogram-dentition-toggle', '');
    const label = (text, view) => {
        const span = document.createElement('span'); span.textContent = text;
        span.setAttribute('data-dentition-label', view); return span;
    };
    const wrapper = document.createElement('span'); wrapper.className = 'global-switch';
    const input = document.createElement('input');
    input.type = 'checkbox'; input.className = 'global-switch-input';
    input.setAttribute('role', 'switch'); input.setAttribute('aria-label', 'Show Baby Teeth');
    if (state.container.id) input.setAttribute('aria-controls', state.container.id);
    const track = document.createElement('span'); track.className = 'global-switch-track'; track.setAttribute('aria-hidden', 'true');
    wrapper.appendChild(input); wrapper.appendChild(track);
    control.appendChild(label('Adult Teeth', 'adult')); control.appendChild(wrapper); control.appendChild(label('Baby Teeth', 'baby'));
    for (const type of ['pointerdown', 'pointerup', 'click', 'dblclick']) control.addEventListener(type, event => event.stopPropagation());
    input.addEventListener('change', event => setOdontogramDentition(state, input.checked ? 'baby' : 'adult', { event }));
    state.container.appendChild(control); state.dentitionToggle = { control, input }; syncToggle(state);
}

export function setOdontogramDentition(state, dentition = 'adult', { clearSelection = true, event = null } = {}) {
    if (!state?.dentitionViews) return;
    const key = dentition === 'primary' ? 'baby' : dentition;
    if (!['adult', 'baby'].includes(key)) throw new Error('Dentition must be adult or baby.');
    if (state.activeDentition === key) { syncToggle(state); return; }
    state.switchingDentition = true;
    try {
        // Let OrbitControls finish real pointer events normally. Ignore a tooth
        // click if its press began before the view changed.
        state.dentitionEpoch++;
        state.activeDentition = key;
        for (const [name, view] of Object.entries(state.dentitionViews)) view.root.visible = name === key;
        const view = state.dentitionViews[key];
        state.model = view.root; state.jaws = view.jaws; state.pickObjects = view.pickObjects; state.oralTissues = view.oralTissues;
        state.fullViewSize.copy(view.fullViewSize); state.initialControlsTarget.copy(view.center);
        state.hoverPart = null; state.renderer.domElement.style.cursor = 'grab';
        if (clearSelection) {
            // Reuse the editor's empty-canvas callback, including picker, legend
            // and multi-selection cleanup. No treatment/history data is removed.
            if (state.mode === 'editor') state.onToothClick?.(null, null, { shiftKey: false, originalEvent: event }, null);
            state.visualOptions = { ...state.visualOptions, selectedTooth: null, selectedSurfaceKey: null, selectedTargetType: null, selectedTargets: [] };
        }
        state.onToothHover?.(null, null, event, null);
        base.updateOdontogramThreeScene(state, state.data, state.visualOptions);
        base.resetOdontogramThreeCamera(state);
        state.scene.updateMatrixWorld(true); syncToggle(state); state.renderScene();
        state.onDentitionChange?.(key, state);
    } finally { state.switchingDentition = false; }
}

export function getOdontogramDentition(state) { return state?.activeDentition || 'adult'; }

export function updateOdontogramThreeScene(state, data = [], options = {}) {
    // Selecting a primary tooth in 2D and moving to 3D reveals the matching view.
    if (state?.dentitionViews && !state.switchingDentition && options.selectedTooth) {
        setOdontogramDentition(state, viewForTooth(options.selectedTooth), { clearSelection: false });
    }
    base.updateOdontogramThreeScene(state, data, options);
}

export function createOdontogramThreeScene(options) {
    const { onReady, dentition = 'adult', showDentitionToggle = true, onDentitionChange = null } = options;
    return base.createOdontogramThreeScene({
        ...options,
        onReady(state) {
            const adult = { root: state.model, jaws: state.jaws, oralTissues: state.oralTissues };
            const adultPicks = state.pickObjects;
            const primary = addOdontogramPrimaryArches(state);
            state.dentitionViews = {
                adult: { ...adult, pickObjects: adultPicks, fullViewSize: state.fullViewSize.clone(), center: state.initialControlsTarget.clone() },
                baby: captureView(primary, primary.pickObjects)
            };
            state.activeDentition = 'adult'; state.onDentitionChange = onDentitionChange;
            guardViewGestures(state);
            base.updateOdontogramThreeScene(state, state.data, state.visualOptions);
            if (showDentitionToggle) mountToggle(state);
            if (dentition !== 'adult') setOdontogramDentition(state, dentition, { clearSelection: false });
            onReady?.(state);
        }
    });
}

export function focusOdontogramThreeTooth(state, mesh, surfaceKey = null) {
    if (!state || !mesh) return;
    const tooth = Number(mesh.userData.tooth);
    setOdontogramDentition(state, viewForTooth(tooth), { clearSelection: false });
    if (!primaryNumbers.has(tooth)) return base.focusOdontogramThreeTooth(state, mesh, surfaceKey);
    const upper = PRIMARY_UPPER.includes(tooth), direction = upper ? -1 : 1;
    const outward = upper ? 'top' : 'bottom';
    state.hasUserCameraChange = true;
    state.focusedInnerArch = surfaceKey === (upper ? 'bottom' : 'top') ? (upper ? 'upper' : 'lower') : null;
    base.setOdontogramThreeSoftTissue(state, state.showSoftTissue);
    const target = mesh.getWorldPosition(new THREE.Vector3());
    const offset = new THREE.Vector3(.25, direction * 2.0, 3.4);
    if (surfaceKey === 'center') offset.set(.2, direction * 3.7, 2.1);
    if (surfaceKey === 'left') offset.set(-3.5, direction * 1.8, .7);
    if (surfaceKey === 'right') offset.set(3.5, direction * 1.8, .7);
    if (surfaceKey && ['top', 'bottom'].includes(surfaceKey) && surfaceKey !== outward) offset.z = -3.4;
    offset.applyQuaternion(mesh.userData.visualGroup.getWorldQuaternion(new THREE.Quaternion()));
    // Adult-sized offsets can cross the opposite primary jaw. Keep the same
    // surface approach directions, with distances fitted to this smaller arch.
    // Keep this distance inside the inter-arch space on narrow canvases too.
    offset.multiplyScalar(mesh.userData.primaryCameraScale * .75);
    animatePrimaryCamera(state, target.clone().add(offset), target);
}

function animatePrimaryCamera(state, position, target) {
    if (state.cameraAnimationFrame) cancelAnimationFrame(state.cameraAnimationFrame);
    state.cameraAnimationFrame = null;
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        state.camera.position.copy(position); state.controls.target.copy(target);
        state.controls.update(); state.renderScene(); return;
    }
    const start = performance.now(), from = state.camera.position.clone(), fromTarget = state.controls.target.clone();
    const step = now => {
        const progress = Math.min((now - start) / 550, 1);
        const eased = progress < .5 ? 4 * progress ** 3 : 1 - (-2 * progress + 2) ** 3 / 2;
        state.camera.position.lerpVectors(from, position, eased);
        state.controls.target.lerpVectors(fromTarget, target, eased);
        state.controls.update(); state.renderScene();
        state.cameraAnimationFrame = progress < 1 ? requestAnimationFrame(step) : null;
    };
    state.cameraAnimationFrame = requestAnimationFrame(step);
}

export const OdontogramWithPrimary = {
    create: createOdontogramThreeScene, update: updateOdontogramThreeScene,
    resize: base.resizeOdontogramThreeScene, resetCamera: base.resetOdontogramThreeCamera,
    focusTooth: focusOdontogramThreeTooth, getToothMesh: base.getOdontogramThreeToothMesh,
    setWireframe: base.setOdontogramThreeWireframe, setSoftTissue: base.setOdontogramThreeSoftTissue,
    setDentition: setOdontogramDentition, getDentition: getOdontogramDentition
};

export function enableOdontogramPrimaryTeeth() { window.Odontogram3D = OdontogramWithPrimary; }
