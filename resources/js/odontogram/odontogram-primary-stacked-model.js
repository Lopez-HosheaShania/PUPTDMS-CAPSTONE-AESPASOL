import * as THREE from 'three';
import { createOdontogramPresentation } from './odontogram-model';

// Retain the installed module path and public entry point. This version builds
// an independent primary presentation; it never edits the adult presentation.
export const PRIMARY_UPPER = Object.freeze([55, 54, 53, 52, 51, 61, 62, 63, 64, 65]);
export const PRIMARY_LOWER = Object.freeze([85, 84, 83, 82, 81, 71, 72, 73, 74, 75]);
const clamp = x => Math.max(0, Math.min(1, x));
const smooth = x => { const t = clamp(x); return t * t * (3 - 2 * t); };

function archRadii(teeth) {
    let xx = 0, xz = 0, zz = 0, x = 0, z = 0;
    for (const tooth of teeth) {
        const p = tooth.userData.visualGroup.position, a = p.x * p.x, b = p.z * p.z;
        xx += a * a; xz += a * b; zz += b * b; x += a; z += b;
    }
    const determinant = xx * zz - xz * xz;
    return { width: Math.sqrt(determinant / (x * zz - z * xz)), depth: Math.sqrt(determinant / (z * xx - x * xz)) };
}

function projection(x, z, radii) {
    let angle = Math.atan2(z / radii.depth, x / radii.width);
    if (angle < -Math.PI / 2) angle += Math.PI * 2;
    for (let i = 0; i < 8; i++) {
        const c = Math.cos(angle), s = Math.sin(angle), dx = radii.width * c - x, dz = radii.depth * s - z;
        const tx = -radii.width * s, tz = radii.depth * c;
        const denominator = tx * tx + tz * tz - dx * radii.width * c - dz * radii.depth * s;
        if (Math.abs(denominator) < 1e-10) break;
        const step = (dx * tx + dz * tz) / denominator;
        angle -= step;
        if (Math.abs(step) < 1e-10) break;
    }
    angle = Math.max(-.06, Math.min(Math.PI + .06, angle));
    return [radii.width * Math.cos(angle), radii.depth * Math.sin(angle)];
}

function weldedTopology(geometry) {
    const positions = geometry.getAttribute('position'), vertices = [], map = new Map(), ids = [];
    for (let i = 0; i < positions.count; i++) {
        const point = [positions.getX(i), positions.getY(i), positions.getZ(i)];
        const key = point.map(value => Math.round(value * 1e6)).join(':');
        if (!map.has(key)) { map.set(key, vertices.length); vertices.push({ point, copies: [], neighbors: new Set() }); }
        const id = map.get(key); ids.push(id); vertices[id].copies.push(i);
    }
    const edges = new Map(), index = geometry.index.array;
    for (let i = 0; i < index.length; i += 3) {
        const [a, b, c] = [ids[index[i]], ids[index[i + 1]], ids[index[i + 2]]];
        for (const [u, v] of [[a, b], [b, c], [c, a]]) {
            vertices[u].neighbors.add(v); vertices[v].neighbors.add(u);
            const key = u < v ? `${u}:${v}` : `${v}:${u}`;
            const edge = edges.get(key) || { u, v, count: 0 }; edge.count++; edges.set(key, edge);
        }
    }
    const boundary = new Set();
    for (const edge of edges.values()) if (edge.count === 1) { boundary.add(edge.u); boundary.add(edge.v); }
    return { vertices, boundary };
}

function finishGeometry(geometry, topology = weldedTopology(geometry)) {
    geometry.getAttribute('position').needsUpdate = true;
    geometry.computeVertexNormals();
    const normals = geometry.getAttribute('normal');
    for (const vertex of topology.vertices) {
        const sum = new THREE.Vector3();
        for (const i of vertex.copies) {
            sum.x += normals.getX(i); sum.y += normals.getY(i); sum.z += normals.getZ(i);
        }
        sum.normalize();
        for (const i of vertex.copies) normals.setXYZ(i, sum.x, sum.y, sum.z);
    }
    normals.needsUpdate = true;
    geometry.computeBoundingSphere();
}

function fitGingiva(gum, sources, radii, ratio) {
    const geometry = gum.geometry, topology = weldedTopology(geometry), positions = geometry.getAttribute('position');
    const baseline = topology.vertices.map(({ point: [x, y, z] }) => {
        const [cx, cz] = projection(x, z, radii);
        return [x + (ratio - 1) * cx, y, z + (ratio - 1) * cz];
    });
    let offsets = topology.vertices.map(() => [0, 0, 0]);
    for (const id of topology.boundary) {
        const point = topology.vertices[id].point;
        let nearest = sources[0], distance = Infinity;
        for (const source of sources) {
            const d = Math.hypot(point[0] - source.x, point[2] - source.z);
            if (d < distance) { nearest = source; distance = d; }
        }
        // Exact rigid translation of the factory's cervical contours. Primary
        // crowns keep their own proportions; only their arch positions change.
        const target = [point[0] + nearest.x * (ratio - 1), point[1], point[2] + nearest.z * (ratio - 1)];
        offsets[id] = target.map((value, axis) => value - baseline[id][axis]);
    }
    for (let pass = 0; pass < 32; pass++) offsets = offsets.map((offset, id) => {
        if (topology.boundary.has(id)) return offset;
        const neighbors = topology.vertices[id].neighbors, average = [0, 0, 0];
        for (const n of neighbors) for (let axis = 0; axis < 3; axis++) average[axis] += offsets[n][axis];
        return average.map((sum, axis) => offset[axis] + (sum / (neighbors.size || 1) - offset[axis]) * .65);
    });
    topology.vertices.forEach((vertex, id) => {
        const point = baseline[id].map((value, axis) => value + offsets[id][axis]);
        for (const i of vertex.copies) positions.setXYZ(i, ...point);
    });
    finishGeometry(geometry, topology);
}

function fitPalate(mesh, radii, ratio) {
    const positions = mesh.geometry.getAttribute('position');
    for (let i = 0; i < positions.count; i++) {
        const x = positions.getX(i), y = positions.getY(i), z = positions.getZ(i);
        const radius = Math.hypot(x / radii.width, z / radii.depth), fade = smooth(radius / .8);
        const angle = Math.atan2(z / radii.depth, x / radii.width);
        const radial = [radii.width * Math.cos(angle), radii.depth * Math.sin(angle)];
        const projected = projection(x, z, radii), blend = smooth((radius - .65) / .15);
        const center = radial.map((v, k) => v + (projected[k] - v) * blend);
        positions.setXYZ(i, x + (ratio - 1) * center[0] * fade, y * .8, z + (ratio - 1) * center[1] * fade);
    }
    finishGeometry(mesh.geometry);
}

export function addOdontogramPrimaryArches(state) {
    if (!state?.model || !Array.isArray(state.teethMeshes)) throw new Error('Create the adult odontogram first.');
    if (state.primaryDentition) return state.primaryDentition;
    const teeth = [];
    const presentation = createOdontogramPresentation({ scene: state.scene, teethMeshes: teeth, upperTeeth: PRIMARY_UPPER, lowerTeeth: PRIMARY_LOWER });
    presentation.root.name = 'primary-dental-arches';
    presentation.root.visible = false;
    const gums = [], layout = [];
    for (const [numbers, isUpper] of [[PRIMARY_UPPER, true], [PRIMARY_LOWER, false]]) {
        const key = isUpper ? 'upper' : 'lower', jaw = presentation.jaws[key];
        const primary = teeth.filter(mesh => numbers.includes(mesh.userData.tooth));
        const adult = state.teethMeshes.filter(mesh => [isUpper ? 1 : 4, isUpper ? 2 : 3].includes(Math.floor(mesh.userData.tooth / 10)));
        const width = list => list.reduce((sum, mesh) => sum + mesh.userData.profile.width * 2 + .014, 0);
        const ratio = width(primary) / width(adult) * 1.14, radii = archRadii(primary);
        const content = primary[0].userData.visualGroup.parent.parent;
        const gum = content.children.find(object => object.userData.isGum);
        const sources = primary.map(mesh => ({ ...mesh.userData.visualGroup.position }));
        primary.forEach(mesh => {
            const group = mesh.userData.visualGroup;
            group.position.x *= ratio; group.position.z *= ratio;
            Object.assign(mesh.userData, { isPrimaryTooth: true, primaryArch: key, primaryCameraScale: ratio });
        });
        fitGingiva(gum, sources, radii, ratio);
        gum.name = `${key}-primary-gingiva`; gum.userData.isPrimaryGum = true; gums.push(gum);
        for (const tissue of presentation.oralTissues.filter(object => object.userData.arch === key)) {
            if (tissue.userData.tissueType === 'tongue') {
                const inset = .40, tissueScale = (radii.depth * ratio - inset) / (radii.depth - inset);
                tissue.scale.set(tissueScale, .72, tissueScale);
            } else fitPalate(tissue, radii, ratio);
        }
        content.position.z *= ratio; jaw.position.y *= .75;
        layout.push({ arch: key, toothNumbers: [...numbers], radiusRatio: ratio });
    }
    const pickObjects = [];
    presentation.root.traverse(object => {
        if (object.isMesh && (object.userData.surfaceKey || object.userData.isGum || object.userData.isOralTissue)) pickObjects.push(object);
    });
    state.primaryDentition = { ...presentation, teeth, gums, groups: Object.values(presentation.jaws), pickObjects, layout };
    state.teethMeshes.push(...teeth);
    state.scene.updateMatrixWorld(true);
    return state.primaryDentition;
}
