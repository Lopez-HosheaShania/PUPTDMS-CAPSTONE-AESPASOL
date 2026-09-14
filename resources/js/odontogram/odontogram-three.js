import * as THREE from 'three';
import { OrbitControls } from 'three/examples/jsm/controls/OrbitControls.js';
import { RoomEnvironment } from 'three/examples/jsm/environments/RoomEnvironment.js';
import { createOdontogramPresentation, ODONTOGRAM_SURFACE_KEYS } from './odontogram-model';

const ADULT_UPPER = [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28];
const ADULT_LOWER = [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];
const ENAMEL = '#f7edd6';
const activeOdontogramStates = new Set();
const normalizeOdontogramData = data => Array.isArray(data) ? data : Object.values(data || {});

export function createOdontogramThreeScene({
    container, data = [], mode = 'preview', onToothClick = null, onToothHover = null, onReady = null,
    wireframe = false, showSurfaceBoundaries = mode === 'editor', showSoftTissue = true
}) {
    if (!container) return null;
    const width = container.clientWidth || 700, height = container.clientHeight || 480;
    const scene = new THREE.Scene();
    const camera = new THREE.PerspectiveCamera(40, width / height, 0.05, 100);
    const renderer = new THREE.WebGLRenderer({ antialias: true, alpha: true });
    renderer.setPixelRatio(Math.min(window.devicePixelRatio || 1, mode === 'preview' ? 1.25 : 1.75));
    renderer.setSize(width, height, false);
    renderer.toneMapping = Reflect.get(THREE, 'NeutralToneMapping') ?? THREE.ACESFilmicToneMapping;
    renderer.toneMappingExposure = 1.0;
    renderer.setClearColor(0x000000, 0);
    scene.background = null;
    if ('transmissionResolutionScale' in renderer) renderer.transmissionResolutionScale = mode === 'preview' ? .5 : .75;
    if ('outputColorSpace' in renderer) renderer.outputColorSpace = THREE.SRGBColorSpace;
    renderer.shadowMap.enabled = mode !== 'preview';
    renderer.shadowMap.type = THREE.PCFSoftShadowMap;
    container.querySelectorAll('canvas').forEach(canvas => canvas.remove());
    container.appendChild(renderer.domElement);
    renderer.domElement.setAttribute('aria-label', '3D odontogram. Click a surface to select it; drag to rotate.');
    renderer.domElement.style.touchAction = 'none';
    renderer.domElement.style.background = 'transparent';
    const controls = new OrbitControls(camera, renderer.domElement);
    controls.enableDamping = mode !== 'preview';
    controls.dampingFactor = 0.08;
    controls.minDistance = 1.5;
    controls.maxDistance = 40;
    controls.minPolarAngle = 0.06;
    controls.maxPolarAngle = Math.PI - 0.06;
    controls.target.set(0, 0, 0);
    const state = {
        container, mode, scene, camera, renderer, controls,
        teethMeshes: [], pickObjects: [], data: normalizeOdontogramData(data),
        raycaster: new THREE.Raycaster(), pointer: new THREE.Vector2(),
        selectedTooth: null, selectedSurfaceKey: null, selectedMesh: null,
        selectedTargets: [], hoverPart: null, onToothClick, onToothHover,
        wireframe: Boolean(wireframe), showSurfaceBoundaries: Boolean(showSurfaceBoundaries),
        showSoftTissue: Boolean(showSoftTissue), focusedInnerArch: null, oralTissues: [], fullViewSize: new THREE.Vector3(),
        visualOptions: {}, environmentTarget: null,
        initialCameraPosition: new THREE.Vector3(), initialControlsTarget: controls.target.clone(),
        cameraAnimationFrame: null, animationFrame: null, hasUserCameraChange: false,
        renderScene: () => renderer.render(scene, camera)
    };
    controls.addEventListener('start', () => {
        state.hasUserCameraChange = true;
        cancelCameraAnimation(state);
    });
    if (mode === 'preview') controls.addEventListener('change', state.renderScene);
    addOdontogramLights(state);
    addOdontogramEnvironment(state);
    const presentation = createOdontogramPresentation({ scene, teethMeshes: state.teethMeshes, upperTeeth: ADULT_UPPER, lowerTeeth: ADULT_LOWER });
    state.model = presentation.root; state.jaws = presentation.jaws; state.oralTissues = presentation.oralTissues;
    scene.updateMatrixWorld(true);
    const bounds = new THREE.Box3();
    state.model.traverse(object => { if (object.isMesh && object.visible) bounds.expandByObject(object); });
    bounds.getSize(state.fullViewSize); bounds.getCenter(state.initialControlsTarget);
    controls.target.copy(state.initialControlsTarget);
    setFullViewPosition(state); camera.position.copy(state.initialCameraPosition); controls.update();
    syncOdontogramOralTissues(state);
    scene.traverse(object => {
        if (object.isMesh && (object.userData.surfaceKey || object.userData.isGum || object.userData.isOralTissue)) state.pickObjects.push(object);
    });
    scene.updateMatrixWorld(true);
    bindOdontogramPointerEvents(state);
    syncOdontogramThreeTheme(state);
    activeOdontogramStates.add(state);
    updateOdontogramThreeScene(state, data);
    if (mode !== 'preview') startOdontogramAnimation(state);
    onReady?.(state);
    return state;
}

function addOdontogramLights(state) {
    const sky = new THREE.HemisphereLight(0xf5f7fa, 0xb4a4a0, .55);
    const key = new THREE.DirectionalLight(0xfffcf7, 1.55);
    key.position.set(-5, 8, 10);
    key.castShadow = state.mode !== 'preview';
    if (key.castShadow) {
        key.shadow.mapSize.set(2048, 2048);
        Object.assign(key.shadow.camera, { left: -7, right: 7, top: 7, bottom: -7, near: 0.5, far: 35 });
        key.shadow.normalBias = 0.012;
        key.shadow.bias = -0.0001;
        key.shadow.radius = 3;
    }
    const fill = new THREE.DirectionalLight(0xf1f5ff, .32);
    fill.position.set(7, -6, 8);
    const rim = new THREE.DirectionalLight(0xffffff, .85);
    rim.position.set(-3, 4, -7);
    state.scene.add(sky, key, fill, rim);
}

function addOdontogramEnvironment(state) {
    const environment = new RoomEnvironment(state.renderer);
    const pmrem = new THREE.PMREMGenerator(state.renderer);
    try {
        state.environmentTarget = pmrem.fromScene(environment, .035);
        state.scene.environment = state.environmentTarget.texture;
    } finally {
        environment.traverse?.((object) => {
            object.geometry?.dispose?.();

            if (Array.isArray(object.material)) {
                object.material.forEach(material => {
                    material?.dispose?.();
                });
            } else {
                object.material?.dispose?.();
            }
        });

        pmrem.dispose();
    }
}

function setFullViewPosition(state) {
    const size = state.fullViewSize;
    const halfFov = state.camera.fov * Math.PI / 360;
    const distance = Math.max(size.y, size.x / state.camera.aspect) / (2 * Math.tan(halfFov)) * 1.13 + size.z / 2;
    state.initialCameraPosition.copy(state.initialControlsTarget)
        .add(new THREE.Vector3(.08, .07, distance));
}

export function resizeOdontogramThreeScene(state) {
    if (!state?.renderer || !state?.container) return;
    const width = state.container.clientWidth, height = state.container.clientHeight;
    if (width < 10 || height < 10) return;
    state.camera.aspect = width / height;
    state.camera.updateProjectionMatrix();
    state.renderer.setSize(width, height, false);
    setFullViewPosition(state);
    if (!state.hasUserCameraChange && !state.cameraAnimationFrame) {
        state.camera.position.copy(state.initialCameraPosition);
        state.controls.target.copy(state.initialControlsTarget);
        state.controls.update();
    }
    state.renderScene();
}

function cancelCameraAnimation(state) {
    if (state.cameraAnimationFrame) cancelAnimationFrame(state.cameraAnimationFrame);
    state.cameraAnimationFrame = null;
}

function animateOdontogramCamera(state, position, target, duration = 550) {
    cancelCameraAnimation(state);
    if (window.matchMedia?.('(prefers-reduced-motion: reduce)').matches) {
        state.camera.position.copy(position); state.controls.target.copy(target);
        state.controls.update(); state.renderScene();
        return;
    }
    const start = performance.now();
    const from = state.camera.position.clone(), fromTarget = state.controls.target.clone();
    const step = now => {
        const progress = Math.min((now - start) / duration, 1);
        const eased = progress < 0.5 ? 4 * progress ** 3 : 1 - (-2 * progress + 2) ** 3 / 2;
        state.camera.position.lerpVectors(from, position, eased);
        state.controls.target.lerpVectors(fromTarget, target, eased);
        state.controls.update(); state.renderScene();
        state.cameraAnimationFrame = progress < 1 ? requestAnimationFrame(step) : null;
    };
    state.cameraAnimationFrame = requestAnimationFrame(step);
}

export function focusOdontogramThreeTooth(state, mesh, surfaceKey = null) {
    if (!state || !mesh) return;
    state.hasUserCameraChange = true;
    const group = mesh.userData.visualGroup;
    const point = mesh.getWorldPosition(new THREE.Vector3());
    const upper = ADULT_UPPER.includes(Number(mesh.userData.tooth));
    state.focusedInnerArch = surfaceKey === (upper ? 'bottom' : 'top') ? (upper ? 'upper' : 'lower') : null;
    syncOdontogramOralTissues(state);
    const outwardKey = upper ? 'top' : 'bottom';
    const direction = upper ? -1 : 1;
    const localOffset = new THREE.Vector3(0.25, direction * 2.0, 3.4);
    if (surfaceKey === 'center') localOffset.set(0.2, direction * 3.7, 2.1);
    if (surfaceKey === 'left') localOffset.set(-3.5, direction * 1.8, 0.7);
    if (surfaceKey === 'right') localOffset.set(3.5, direction * 1.8, 0.7);
    if (surfaceKey && ['top', 'bottom'].includes(surfaceKey) && surfaceKey !== outwardKey) localOffset.z = -3.4;
    if (group) localOffset.applyQuaternion(group.getWorldQuaternion(new THREE.Quaternion()));
    const scale = Math.max(1, 0.8 / state.camera.aspect);
    animateOdontogramCamera(state, point.clone().add(localOffset.multiplyScalar(scale)), point);
}

export function resetOdontogramThreeCamera(state) {
    if (!state) return;
    state.hasUserCameraChange = false;
    state.focusedInnerArch = null;
    syncOdontogramOralTissues(state);
    setFullViewPosition(state);
    animateOdontogramCamera(state, state.initialCameraPosition.clone(), state.initialControlsTarget.clone());
}

export function getOdontogramThreeToothMesh(state, tooth) {
    return state?.teethMeshes.find(mesh => Number(mesh.userData.tooth) === Number(tooth)) || null;
}

function getWholeToothVisual(record) {
    if (!record) return null;
    if (record.status?.code) return record.status;
    const hasSurfaces = ODONTOGRAM_SURFACE_KEYS.some(key => record.surfaces?.[key]?.code);
    return hasSurfaces ? null : (record.threeD || record.three_d || null);
}

function refreshSurfaceOutlines(state) {
    const accent = state.selectionColor || '#b42345';
    for (const mesh of state.teethMeshes) {
        const primary = Number(state.selectedTooth) === mesh.userData.tooth;
        const boundary = mesh.userData.boundaryOutline;
        boundary.visible = state.showSurfaceBoundaries !== false && !state.wireframe;
        boundary.material.opacity = primary ? .40 : .23;
        for (const key of ODONTOGRAM_SURFACE_KEYS) {
            const outline = mesh.userData.surfaceOutlines[key];
            const part = mesh.userData.surfaceParts[key];
            const selected = state.selectedTargets.some(target => Number(target.tooth) === mesh.userData.tooth
                && (['whole', 'status', '3d'].includes(target.targetType) || target.surfaceKey === key));
            const hovered = state.hoverPart === part;
            outline.visible = selected || hovered;
            outline.material.color.set(selected ? accent : '#d79638');
            outline.material.opacity = 1;
            outline.renderOrder = 4;
        }
    }
}

export function updateOdontogramThreeScene(state, data = [], options = {}) {
    if (!state) return;
    state.visualOptions = { ...options };
    state.data = normalizeOdontogramData(data);
    state.selectedTooth = options.selectedTooth ?? null;
    state.selectedSurfaceKey = options.selectedSurfaceKey ?? null;
    state.selectedMesh = getOdontogramThreeToothMesh(state, state.selectedTooth);
    state.selectedTargets = Array.isArray(options.selectedTargets) ? options.selectedTargets : [];
    if (!state.selectedTargets.length && state.selectedTooth) {
        state.selectedTargets = [{
            tooth: state.selectedTooth,
            targetType: options.selectedTargetType || (state.selectedSurfaceKey ? 'surface' : 'whole'),
            surfaceKey: state.selectedSurfaceKey
        }];
    }
    const records = new Map(state.data.filter(Boolean).map(record => [Number(record.tooth), record]));
    for (const mesh of state.teethMeshes) {
        const record = records.get(mesh.userData.tooth);
        const whole = getWholeToothVisual(record);
        const selected = state.selectedTargets.some(target => Number(target.tooth) === mesh.userData.tooth);
        const dim = options.dimUnselected && state.selectedTooth && !selected;
        mesh.userData.core.visible = !state.wireframe;
        for (const key of ODONTOGRAM_SURFACE_KEYS) {
            const part = mesh.userData.surfaceParts[key];
            const treatment = record?.surfaces?.[key]?.code ? record.surfaces[key] : whole;
            const color = treatment?.colorHex || ENAMEL;
            const material = part.material;
            const vertexColors = !treatment;
            const opticalEnamel = !treatment && !state.wireframe;
            if (material.vertexColors !== vertexColors || material.wireframe !== Boolean(state.wireframe)
                || (material.transmission > 0) !== opticalEnamel) material.needsUpdate = true;
            material.vertexColors = vertexColors;
            material.wireframe = Boolean(state.wireframe);
            material.transparent = false;
            material.opacity = 1;
            material.depthWrite = true;
            material.color.set(color);
            material.emissive.set(0x000000);
            material.roughness = treatment ? .44 : .28;
            material.clearcoat = treatment ? .12 : .38;
            material.transmission = opticalEnamel ? .16 : 0;
            material.bumpScale = treatment ? .0008 : .002;
            material.envMapIntensity = treatment ? .38 : .80;
            const uniforms = material.userData.dentalUniforms;
            uniforms.dentalScatter.value = opticalEnamel ? .095 : 0;
            uniforms.dentalDetailStrength.value = state.wireframe ? 0 : treatment ? .25 : 1;
            uniforms.dentalDimming.value = dim ? .80 : 1;
            part.userData.legend = treatment?.code || null;
            part.userData.originalColor = color;
        }
        mesh.userData.originalColor = whole?.colorHex || ENAMEL;
        mesh.userData.legend = whole?.code || null;
    }
    state.scene.traverse(object => {
        if (object.userData.isGum || object.userData.isOralTissue) object.material.wireframe = Boolean(state.wireframe);
    });
    refreshSurfaceOutlines(state);
    state.renderScene();
}

export function setOdontogramThreeWireframe(state, enabled = true) {
    if (!state) return;
    state.wireframe = Boolean(enabled);
    updateOdontogramThreeScene(state, state.data, state.visualOptions);
}

function syncOdontogramOralTissues(state) {
    for (const tissue of state.oralTissues) tissue.visible = state.showSoftTissue && tissue.userData.arch !== state.focusedInnerArch;
}

export function setOdontogramThreeSoftTissue(state, visible = true) {
    if (!state) return;
    state.showSoftTissue = Boolean(visible);
    syncOdontogramOralTissues(state);
    state.renderScene();
}

function isOdontogramObjectVisible(object) {
    for (let current = object; current; current = current.parent) if (current.visible === false) return false;
    return true;
}

function getPointerHit(state, event) {
    const rect = state.renderer.domElement.getBoundingClientRect();
    if (!rect.width || !rect.height) return null;
    state.pointer.set((event.clientX - rect.left) / rect.width * 2 - 1,
        -(event.clientY - rect.top) / rect.height * 2 + 1);
    state.scene.updateMatrixWorld(true);
    state.camera.updateMatrixWorld(true);
    state.raycaster.setFromCamera(state.pointer, state.camera);
    const hit = state.raycaster.intersectObjects(state.pickObjects.filter(isOdontogramObjectVisible), false)[0];
    return hit?.object.userData.surfaceKey ? hit : null;
}

function bindOdontogramPointerEvents(state) {
    const canvas = state.renderer.domElement;
    const pointers = new Set();
    let press = null;
    function hover(hit, event) {
        const part = hit?.object || null;
        if (state.hoverPart !== part) {
            state.hoverPart = part; refreshSurfaceOutlines(state); state.renderScene();
        }
        canvas.style.cursor = part ? 'pointer' : 'grab';
        state.onToothHover?.(part?.userData.tooth || null, part?.userData.toothMesh || null,
            event, part?.userData.surfaceKey || null);
    }
    canvas.addEventListener('pointerdown', event => {
        pointers.add(event.pointerId);
        if (pointers.size > 1) { press = null; return; }
        if (event.button !== 0 || event.isPrimary === false) return;
        press = { id: event.pointerId, x: event.clientX, y: event.clientY, moved: false };
        hover(null, event);
    });
    canvas.addEventListener('pointermove', event => {
        if (press) {
            if (Math.hypot(event.clientX - press.x, event.clientY - press.y) > 6) press.moved = true;
            if (press.moved) canvas.style.cursor = 'grabbing';
            return;
        }
        if (!event.buttons && !pointers.size) hover(getPointerHit(state, event), event);
    });
    canvas.addEventListener('pointerup', event => {
        pointers.delete(event.pointerId);
        const click = press;
        press = null;
        if (!click || click.id !== event.pointerId || click.moved || event.button !== 0
            || Math.hypot(event.clientX - click.x, event.clientY - click.y) > 6) return;
        const hit = getPointerHit(state, event);
        const part = hit?.object;
        if (part || state.mode === 'editor') state.onToothClick?.(
            part?.userData.tooth || null, part?.userData.toothMesh || null, event,
            part?.userData.surfaceKey || null
        );
    }, true);
    canvas.addEventListener('pointercancel', event => { pointers.delete(event.pointerId); press = null; hover(null, event); });
    canvas.addEventListener('lostpointercapture', event => { pointers.delete(event.pointerId); press = null; });
    canvas.addEventListener('pointerleave', event => {
        if (press) press.moved = true;
        hover(null, event);
    });
}

function getCurrentOdontogramTheme() {
    const root = document.documentElement;
    return root.getAttribute('data-theme') === 'dark' || root.classList.contains('dark') ? 'dark' : 'light';
}

function syncOdontogramThreeTheme(state, theme = getCurrentOdontogramTheme()) {
    const dark = theme === 'dark';
    state.selectionColor = dark ? '#ff8398' : '#b42345';
    state.scene.background = null;
    state.renderer.setClearColor(0x000000, 0);
    refreshSurfaceOutlines(state);
    state.renderScene();
}

window.addEventListener('global-theme-change', event => {
    activeOdontogramStates.forEach(state => syncOdontogramThreeTheme(state, event.detail?.theme));
});

function startOdontogramAnimation(state) {
    const animate = () => {
        if (!state.container.isConnected) {
            cancelCameraAnimation(state);
            state.controls.dispose();
            state.environmentTarget?.dispose();
            state.renderer.dispose();
            activeOdontogramStates.delete(state);
            return;
        }
        state.animationFrame = requestAnimationFrame(animate);
        if (state.container.getClientRects().length && !document.hidden) {
            state.controls.update(); state.renderScene();
        }
    };
    animate();
}

window.Odontogram3D = {
    create: createOdontogramThreeScene, update: updateOdontogramThreeScene,
    resize: resizeOdontogramThreeScene, resetCamera: resetOdontogramThreeCamera,
    focusTooth: focusOdontogramThreeTooth, getToothMesh: getOdontogramThreeToothMesh,
    setWireframe: setOdontogramThreeWireframe, setSoftTissue: setOdontogramThreeSoftTissue
};
