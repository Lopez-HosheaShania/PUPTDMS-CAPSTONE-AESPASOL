import * as THREE from 'three';

export const ODONTOGRAM_SURFACE_KEYS = ['top', 'left', 'center', 'right', 'bottom'];
const ENAMEL = '#f7edd6';
const crownGeometryCache = new Map();
const hitGeometry = new THREE.SphereGeometry(1, 8, 6);
const hitMaterial = new THREE.MeshBasicMaterial({ visible: false });
const SEGMENTS = 48;
const SIDE_RINGS = 12;
const CAP_RINGS = 14;
const CENTER_RING = SIDE_RINGS + 3;
const ARCHES = {
    upper: { width: 3.12, depth: 3.56 },
    lower: { width: 2.88, depth: 3.40 }
};
const ARCH_START = Math.PI + 0.06;
const ARCH_END = -0.06;
const TOOTH_WIDTH_SCALE = 1.10;
let dentalTextures = null;
const occlusalTextureCache = new Map();

const clamp01 = value => Math.min(1, Math.max(0, value));
const smooth = value => { const t = clamp01(value); return t * t * (3 - 2 * t); };

function hash2(x, y, seed = 0) {
    const value = Math.sin(x * 127.1 + y * 311.7 + seed * 73.13) * 43758.5453123;
    return value - Math.floor(value);
}

function makeDataTexture(bytes, width, height, repeat = true) {
    const texture = new THREE.DataTexture(bytes, width, height, THREE.RGBAFormat);
    texture.wrapS = repeat ? THREE.RepeatWrapping : THREE.ClampToEdgeWrapping;
    texture.wrapT = repeat ? THREE.RepeatWrapping : THREE.ClampToEdgeWrapping;
    texture.magFilter = THREE.LinearFilter;
    texture.minFilter = repeat ? THREE.LinearMipmapLinearFilter : THREE.LinearFilter;
    texture.generateMipmaps = repeat;
    texture.colorSpace = THREE.NoColorSpace;
    texture.needsUpdate = true;
    return texture;
}

function getDentalTextures() {
    if (dentalTextures) return dentalTextures;
    const size = 256;
    const enamel = new Uint8Array(size * size * 4);
    const gingiva = new Uint8Array(size * size * 4);
    const tongue = new Uint8Array(size * size * 4);
    const tongueColor = new Uint8Array(size * size * 4);
    const optical = new Uint8Array(2 * 64 * 4);
    for (let y = 0; y < size; y++) {
        for (let x = 0; x < size; x++) {
            const index = (y * size + x) * 4;
            const grain = hash2(x, y);
            const gx = x / size * 12, gy = y / size * 12;
            let pore = 0;
            for (let oy = -1; oy <= 1; oy++) for (let ox = -1; ox <= 1; ox++) {
                const cx = Math.floor(gx) + ox, cy = Math.floor(gy) + oy;
                const tx = (cx + 12) % 12, ty = (cy + 12) % 12;
                const px = cx + 0.15 + 0.70 * hash2(tx, ty, 1);
                const py = cy + 0.15 + 0.70 * hash2(tx, ty, 2);
                const radius = 0.12 + 0.10 * hash2(tx, ty, 3);
                pore += Math.exp(-((gx - px) ** 2 + (gy - py) ** 2) / (radius * radius));
            }
            const striae = Math.sin(y / size * Math.PI * 40 + .6 * Math.sin(x / size * Math.PI * 6));
            const enamelHeight = 128 + 3 * striae + (grain - 0.5) * 4;
            const tx = x / size * 26, ty = y / size * 26;
            let papilla = 0, fungiform = 0;
            for (let oy = -1; oy <= 1; oy++) for (let ox = -1; ox <= 1; ox++) {
                const cx = Math.floor(tx) + ox, cy = Math.floor(ty) + oy;
                const hx = (cx + 26) % 26, hy = (cy + 26) % 26;
                const dx = tx - cx - .18 - .64 * hash2(hx, hy, 7);
                const dy = ty - cy - .18 - .64 * hash2(hx, hy, 8);
                const wide = hash2(hx, hy, 9) > .94;
                const spot = Math.exp(-(dx * dx + dy * dy * (wide ? 1 : 1.8)) / (wide ? .08 : .036));
                papilla += spot; if (wide) fungiform += spot;
            }
            tongue.set([Math.min(245, 112 + papilla * 84 + (grain - .5) * 8), 215 + grain * 27, 128, 255], index);
            tongueColor.set([254, 250 - fungiform * 31, 247 - fungiform * 26, 255], index);
            enamel.set([enamelHeight, 210 + grain * 18, 128, 255], index);
            gingiva.set([Math.max(35, 157 - pore * 58 + (grain - 0.5) * 5), 202 + grain * 27, 128, 255], index);
        }
    }
    for (let y = 0; y < 64; y++) {
        const edge = smooth((y / 63 - 0.60) / 0.34);
        for (let x = 0; x < 2; x++) {
            optical.set([Math.round(255 * (0.06 + 0.32 * edge)), Math.round(255 * (1 - 0.50 * edge)), 0, 255], (y * 2 + x) * 4);
        }
    }
    dentalTextures = {
        enamel: makeDataTexture(enamel, size, size),
        gingiva: makeDataTexture(gingiva, size, size),
        optical: makeDataTexture(optical, 2, 64, false),
        tongue: makeDataTexture(tongue, size, size),
        tongueColor: makeDataTexture(tongueColor, size, size),
        vessels: createVascularTexture(),
        emptyFissure: makeDataTexture(new Uint8Array([153, 0, 0, 255]), 1, 1, false)
    };
    dentalTextures.tongueColor.colorSpace = THREE.SRGBColorSpace;
    return dentalTextures;
}

function createVascularTexture() {
    const width = 512, height = 256, field = new Float32Array(width * height), bytes = new Uint8Array(width * height * 4);
    function stroke(ax, ay, bx, by, radius, strength) {
        const dx = bx - ax, dy = by - ay, length = dx * dx + dy * dy || 1;
        for (let y = Math.max(0, Math.floor(Math.min(ay, by) - radius * 3)); y <= Math.min(height - 1, Math.ceil(Math.max(ay, by) + radius * 3)); y++) {
            for (let x = Math.floor(Math.min(ax, bx) - radius * 3); x <= Math.ceil(Math.max(ax, bx) + radius * 3); x++) {
                const t = clamp01(((x - ax) * dx + (y - ay) * dy) / length);
                const distance = (x - ax - dx * t) ** 2 + (y - ay - dy * t) ** 2;
                const index = y * width + (x % width + width) % width;
                field[index] = Math.max(field[index], strength * Math.exp(-distance / (radius * radius)));
            }
        }
    }
    function vessel(x, y, angle, length, radius, seed, branch = true) {
        const steps = 16;
        for (let i = 0; i < steps; i++) {
            const bend = angle + .24 * Math.sin(i * .8 + seed) + (hash2(seed, i, 14) - .5) * .28;
            const nx = x + Math.cos(bend) * length / steps, ny = y + Math.sin(bend) * length / steps;
            stroke(x, y, nx, ny, radius * (1 - .65 * i / steps), .45 + .35 * hash2(seed, i, 15));
            if (branch && (i === 5 || i === 10)) vessel(nx, ny, bend + (i === 5 ? .65 : -.65), length * .43, radius * .58, seed + i * 3, false);
            x = nx; y = ny;
        }
    }
    for (let i = 0; i < 17; i++) {
        const upper = i % 2 === 0;
        vessel((i + .5) / 17 * width, upper ? 12 : height - 12, upper ? 1.3 : -1.5,
            72 + hash2(i, 1, 19) * 75, .9 + hash2(i, 2, 19) * .65, i + 1);
    }
    for (let y = 0; y < height; y++) for (let x = 0; x < width; x++) {
        const i = y * width + x, blush = .014 * Math.sin(x / width * Math.PI * 8 + Math.sin(y / height * Math.PI * 4));
        const vein = field[i], grain = (hash2(x, y, 22) - .5) * .008;
        bytes.set([.995 - grain, .985 - .23 * vein - blush - grain,
        .985 - .15 * vein - .6 * blush - grain, 1].map(value => Math.round(255 * clamp01(value))), i * 4);
    }
    const texture = makeDataTexture(bytes, width, height);
    texture.colorSpace = THREE.SRGBColorSpace;
    texture.repeat.set(1 / 18, .72);
    texture.offset.set(0, .5);
    return texture;
}

function supplementalFissures(x, z, profile) {
    if (profile.type === 'incisor' || profile.type === 'canine') return 0;
    const m = x * profile.mesialSign, inside = Math.exp(-((Math.hypot(m, z) / .83) ** 8));
    let detail = 0;
    for (const side of [-1, 1]) {
        const line = z - side * (.25 + .30 * m + .045 * Math.sin(m * 9 + side));
        detail += .40 * Math.exp(-((line / .020) ** 2)) * gaussian(m, z, side * .32, side * .34, .29, .27);
        const fork = m - side * (.50 - .22 * z + .045 * Math.sin(z * 12));
        detail += .34 * Math.exp(-((fork / .022) ** 2)) * gaussian(m, z, side * .45, -.15, .20, .39);
    }
    if (profile.type === 'molar' && !profile.isUpper && profile.position === 6) {
        detail += .70 * Math.exp(-(((m + .56 + .17 * z) / .034) ** 2)) * smooth((z + .02) / .24);
    }
    detail += .65 * gaussian(m, z, .39, -.025, .047, .055) + .55 * gaussian(m, z, -.39, .035, .045, .058);
    if (profile.isUpper && profile.type === 'molar') detail *= 1 - .85 * Math.exp(-(((m + z + .10) / .18) ** 2));
    return clamp01(detail * inside);
}

function getOcclusalTexture(profile) {
    if (profile.type === 'incisor' || profile.type === 'canine') return getDentalTextures().emptyFissure;
    const key = `${profile.isUpper}:${profile.position}:${profile.type}`;
    if (occlusalTextureCache.has(key)) return occlusalTextureCache.get(key);
    const size = 256, bytes = new Uint8Array(size * size * 4), canonical = { ...profile, mesialSign: 1 };
    for (let y = 0; y < size; y++) for (let x = 0; x < size; x++) {
        const u = x / (size - 1) * 2 - 1, v = y / (size - 1) * 2 - 1;
        const primary = fissureMask(u, v, canonical), fine = supplementalFissures(u, v, canonical);
        const dark = clamp01(.78 * primary + .68 * fine);
        const relief = clamp01(.60 - .34 * primary - .18 * fine);
        bytes.set([Math.round(relief * 255), Math.round(dark * 255), Math.round(dark * 180), 255], (y * size + x) * 4);
    }
    const texture = makeDataTexture(bytes, size, size, false);
    texture.generateMipmaps = true; texture.minFilter = THREE.LinearMipmapLinearFilter;
    occlusalTextureCache.set(key, texture);
    return texture;
}

function addDentalScattering(material, kind, profile = null) {
    const enamel = kind === 'enamel';
    const uniforms = {
        dentalScatter: { value: enamel ? .095 : .075 },
        dentalTint: { value: new THREE.Color(enamel ? '#f2ddb0' : '#ffc3b5') },
        dentalDimming: { value: 1 }
    };
    if (enamel) Object.assign(uniforms, {
        dentalDetailMap: { value: getOcclusalTexture(profile) },
        dentalDetailScale: { value: new THREE.Vector3(profile.width * .91 * profile.mesialSign, profile.depth * .90, profile.tilt) },
        dentalDetailStrength: { value: 1 },
        dentalGrooveColor: { value: new THREE.Color('#d9c8ab') }
    });
    material.userData.dentalUniforms = uniforms;
    material.customProgramCacheKey = () => `odontogram-optics-v3-${enamel ? 'enamel' : 'mucosa'}`;
    material.onBeforeCompile = shader => {
        const outputChunk = shader.fragmentShader.includes('#include <opaque_fragment>') ? '#include <opaque_fragment>' : '#include <output_fragment>';
        if (!shader.vertexShader.includes('#include <common>') || !shader.fragmentShader.includes('#include <common>')
            || !shader.vertexShader.includes('#include <begin_vertex>') || !shader.fragmentShader.includes('#include <map_fragment>')
            || !shader.fragmentShader.includes('#include <lights_fragment_end>') || !shader.fragmentShader.includes('#include <normal_fragment_maps>')
            || !shader.fragmentShader.includes(outputChunk)) return;
        Object.assign(shader.uniforms, uniforms);
        shader.vertexShader = shader.vertexShader
            .replace('#include <common>', '#include <common>\nattribute vec3 dentalEffect;\nvarying vec3 vDentalEffect;\nvarying vec3 vDentalLocal;')
            .replace('#include <begin_vertex>', '#include <begin_vertex>\nvDentalEffect = dentalEffect;\nvDentalLocal = position;');
        shader.fragmentShader = shader.fragmentShader.replace('#include <common>', `#include <common>
            varying vec3 vDentalEffect;
            varying vec3 vDentalLocal;
            uniform float dentalScatter;
            uniform vec3 dentalTint;
            uniform float dentalDimming;
            ${enamel ? `uniform sampler2D dentalDetailMap;
                uniform vec3 dentalDetailScale;
                uniform float dentalDetailStrength;
                uniform vec3 dentalGrooveColor;` : ''}`);
        if (enamel) {
            shader.fragmentShader = shader.fragmentShader.replace('#include <map_fragment>', `#include <map_fragment>
                vec2 dentalUV = vec2(vDentalLocal.x / dentalDetailScale.x,
                    (vDentalLocal.z - abs(vDentalLocal.y) * dentalDetailScale.z) / dentalDetailScale.y) * .5 + .5;
                vec3 dentalDetail = texture2D(dentalDetailMap, dentalUV).rgb;
                float dentalDetailMask = vDentalEffect.y * dentalDetailStrength;
                diffuseColor.rgb *= mix(vec3(1.0), dentalGrooveColor, dentalDetail.g * dentalDetailMask * .32);`);
        } else {
            shader.fragmentShader = shader.fragmentShader.replace('#include <map_fragment>', `
                vec3 dentalUnmapped = diffuseColor.rgb;
                #include <map_fragment>
                diffuseColor.rgb = mix(dentalUnmapped, diffuseColor.rgb, vDentalEffect.y);`);
        }
        shader.fragmentShader = shader.fragmentShader
            .replace('#include <normal_fragment_maps>', `
                vec3 dentalSmoothNormal = normal;
                #include <normal_fragment_maps>
                normal = normalize(mix(dentalSmoothNormal, normal, vDentalEffect.z));
                ${enamel ? `float dentalRelief = (dentalDetail.r - .60) * .006 * dentalDetailMask;
                    vec3 dentalDx = dFdx(-vViewPosition), dentalDy = dFdy(-vViewPosition);
                    vec3 dentalR1 = cross(dentalDy, normal), dentalR2 = cross(normal, dentalDx);
                    float dentalDet = dot(dentalDx, dentalR1);
                    vec3 dentalGradient = sign(dentalDet) * (dFdx(dentalRelief) * dentalR1 + dFdy(dentalRelief) * dentalR2);
                    normal = normalize(max(abs(dentalDet), 0.00000001) * normal - dentalGradient);` : ''}`)
            .replace('#include <lights_fragment_end>', `#include <lights_fragment_end>
                vec3 dentalTransmittedLight = vec3(0.0);
                #if NUM_DIR_LIGHTS > 0
                    for (int dentalLight = 0; dentalLight < NUM_DIR_LIGHTS; dentalLight++) {
                        float backlight = pow(clamp(dot(-normal, directionalLights[dentalLight].direction) + .22, 0.0, 1.0), 2.0);
                        float edgeLight = pow(1.0 - clamp(dot(normal, normalize(vViewPosition)), 0.0, 1.0), 3.0);
                        dentalTransmittedLight += directionalLights[dentalLight].color * (backlight + .04 * edgeLight);
                    }
                #endif
                reflectedLight.indirectDiffuse += diffuseColor.rgb * dentalTint
                    * dentalTransmittedLight * dentalScatter * vDentalEffect.x;`)
            .replace(outputChunk, `outgoingLight *= dentalDimming;\n${outputChunk}`);
    };
    return material;
}

function createEnamelMaterial(profile) {
    const texture = getDentalTextures();

    const material = new THREE.MeshPhysicalMaterial({
        color: ENAMEL,
        vertexColors: true,
        metalness: 0,
        roughness: .28,

        roughnessMap: texture.enamel,
        bumpMap: texture.enamel,
        bumpScale: .002,

        clearcoat: .38,
        clearcoatRoughness: .17,
        clearcoatRoughnessMap: texture.enamel,

        ior: 1.62,
        transmission: .16,
        transmissionMap: texture.optical,

        envMapIntensity: .80,
        opacity: 1,
        transparent: false
    });

    if ('thickness' in material) {
        material.thickness = .105;
    }

    if ('thicknessMap' in material) {
        material.thicknessMap = texture.optical;
    }

    if ('attenuationColor' in material) {
        material.attenuationColor.set('#f6e3b8');
    }

    if ('attenuationDistance' in material) {
        material.attenuationDistance = .34;
    }

    return addDentalScattering(
        material,
        'enamel',
        profile
    );
}

function createGingivaMaterial() {
    const texture = getDentalTextures();
    return addDentalScattering(new THREE.MeshPhysicalMaterial({
        color: '#ffffff', vertexColors: true, map: texture.vessels,
        roughness: .53, roughnessMap: texture.gingiva, bumpMap: texture.gingiva, bumpScale: .008,
        clearcoat: .32, clearcoatRoughness: .21,
        metalness: 0, ior: 1.4, envMapIntensity: .70, side: THREE.DoubleSide
    }), 'gingiva');
}

function createTongueMaterial() {
    const texture = getDentalTextures();
    return addDentalScattering(new THREE.MeshPhysicalMaterial({
        color: '#ffffff', vertexColors: true, map: texture.tongueColor,
        roughness: .55, roughnessMap: texture.tongue, bumpMap: texture.tongue, bumpScale: .012,
        clearcoat: .25, clearcoatRoughness: .24,
        metalness: 0, ior: 1.4, envMapIntensity: .68
    }), 'tongue');
}

export function getOdontogramToothType(toothNumber) {
    const number = Number(toothNumber);
    const position = number % 10;
    if (position <= 2) return 'incisor';
    if (position === 3) return 'canine';
    if (Math.floor(number / 10) >= 5) return 'molar';
    return position <= 5 ? 'premolar' : 'molar';
}

export function getOdontogramToothDimensions(type) {
    const dimensions = {
        incisor: { width: 0.32, height: 0.52, depth: 0.24 },
        canine: { width: 0.34, height: 0.57, depth: 0.29 },
        premolar: { width: 0.37, height: 0.46, depth: 0.39 },
        molar: { width: 0.46, height: 0.43, depth: 0.46 }
    };
    const size = dimensions[type] || dimensions.incisor;
    return { ...size, hitWidth: size.width, hitHeight: size.height, hitDepth: size.depth };
}

function toothProfile(toothNumber, isUpper) {
    const type = getOdontogramToothType(toothNumber);
    const position = Number(toothNumber) % 10;
    const primary = Math.floor(Number(toothNumber) / 10) >= 5;
    const upper = [[0, 0, 0], [.340, .840, .285], [.275, .760, .270], [.300, .880, .315],
    [.280, .680, .360], [.280, .640, .360], [.416, .620, .445], [.384, .590, .425], [.360, .555, .400]];
    const lower = [[0, 0, 0], [.208, .735, .235], [.230, .765, .250], [.275, .860, .295],
    [.278, .680, .315], [.288, .655, .350], [.440, .620, .405], [.418, .595, .395], [.380, .550, .370]];
    const index = primary && position >= 4 ? position + 2 : position;
    const [width, height, depth] = (isUpper ? upper : lower)[index] || upper[1];
    const mesialSign = [1, 4, 5, 8].includes(Math.floor(Number(toothNumber) / 10)) ? 1 : -1;
    const anterior = position <= 3;
    return {
        width: width * (primary ? .80 : 1) * TOOTH_WIDTH_SCALE,
        height: height * (primary ? .78 : 1),
        depth: depth * (primary ? .82 : 1),
        type,
        position: index,
        isUpper,
        mesialSign,
        tilt: isUpper ? (anterior ? .065 : .025) : (anterior ? .028 : -.045)
    };
}

function gaussian(x, z, cx, cz, sx, sz) {
    return Math.exp(-(((x - cx) / sx) ** 2 + ((z - cz) / sz) ** 2));
}

function fissureMask(x, z, profile) {
    if (profile.type === 'incisor' || profile.type === 'canine') return 0;
    const m = x * profile.mesialSign;
    const inside = Math.exp(-((Math.hypot(m, z) / 0.80) ** 6));
    let central = Math.exp(-(((z + 0.055 * Math.sin(m * 5.0)) / 0.045) ** 2));
    let branch = 0;
    if (profile.type === 'molar') {
        const lateral = profile.isUpper ? m + 0.28 * z - 0.12 : m + (profile.position === 6 ? 0.13 * Math.sin(z * 5) : 0);
        branch = Math.exp(-((lateral / 0.055) ** 2));
        if (profile.isUpper) {
            const ridge = Math.exp(-(((m + z + 0.10) / .18) ** 2));
            central *= 1 - 0.80 * ridge;
            branch *= 1 - 0.75 * ridge;
        }
    } else if (!profile.isUpper && profile.position === 5) {
        branch = Math.exp(-((m / .05) ** 2)) * smooth((-z - .02) / .30);
    }
    const pits = 0.35 * gaussian(m, z, .42, 0, .08, .08)
        + 0.30 * gaussian(m, z, -.40, .02, .08, .08);
    return clamp01((central + 0.75 * branch + pits) * inside);
}

function crownHeight(x, z, profile) {
    const { type, height, isUpper, position, mesialSign } = profile;
    const m = x * mesialSign;
    if (type === 'incisor') {
        const distalRound = .065 * Math.max(0, -m) ** 3;
        return height * (.99 - .045 * m ** 4 - distalRound - .065 * z ** 2);
    }
    if (type === 'canine') {
        const ridge = gaussian(m, z, .08, .10, .65, .68);
        return height * (.69 + .30 * ridge - .07 * Math.max(0, -m));
    }
    let cusps;
    if (type === 'premolar') {
        if (isUpper) {
            cusps = [[.02, .48, position === 4 ? .26 : .225, .61, .34], [-.03, -.44, .215, .58, .36]];
        } else if (position === 4) {
            cusps = [[.02, .43, .30, .66, .36], [.08, -.48, .085, .49, .32]];
        } else {
            cusps = [[0, .43, .25, .62, .37], [.37, -.43, .205, .34, .34], [-.36, -.44, .16, .34, .34]];
        }
    } else if (isUpper) {
        cusps = [[.43, .42, .215, .36, .38], [-.44, .43, .18, .35, .37],
        [.37, -.42, .27, .39, .38], [-.45, -.43, position === 8 ? .08 : .16, .32, .34]];
    } else if (position === 6) {
        cusps = [[.48, .43, .235, .35, .36], [-.20, .47, .20, .33, .34],
        [-.73, .24, .145, .24, .28], [.45, -.45, .25, .37, .35], [-.42, -.44, .235, .35, .35]];
    } else {
        cusps = [[.44, .44, .225, .36, .37], [-.43, .44, .215, .36, .37],
        [.43, -.43, .24, .37, .36], [-.43, -.44, .23, .36, .37]];
    }
    let relief = 0;
    for (const [cx, cz, amplitude, sx, sz] of cusps) relief += amplitude * gaussian(m, z, cx, cz, sx, sz);
    if (type === 'molar' && isUpper) {
        relief += .045 * Math.exp(-(((m + z + .10) / .20) ** 2)) * gaussian(m, z, 0, 0, .80, .80);
        if (position === 6) relief += .030 * gaussian(m, z, .56, -.84, .22, .20);
    }
    const marginal = .055 * Math.exp(-(((Math.abs(m) - .77) / .14) ** 2)) * Math.exp(-((z / .66) ** 4));
    return height * (.72 + relief + marginal - .035 * fissureMask(x, z, profile) - .008 * supplementalFissures(x, z, profile));
}

function perimeter(theta, profile) {
    const exponent = profile.type === 'molar' ? .68 : profile.type === 'premolar' ? .86 : .80;
    const c = Math.cos(theta), s = Math.sin(theta);
    let x = Math.sign(c) * Math.abs(c) ** exponent;
    let z = Math.sign(s) * Math.abs(s) ** exponent;
    const m = x * profile.mesialSign;
    if (profile.type === 'molar' && profile.isUpper) {
        x += .105 * z * profile.mesialSign;
        z *= 1 - .075 * Math.max(0, -m);
    }
    if (profile.type === 'molar' && !profile.isUpper && profile.position === 6) {
        z *= 1 - .11 * Math.max(0, -m);
    }
    return { x, z };
}

function crownPoint(ring, segment, profile, sideRings = SIDE_RINGS, capRings = CAP_RINGS, segments = SEGMENTS) {
    const edge = perimeter(segment / segments * Math.PI * 2 + Math.PI / 4, profile);
    const direction = profile.isUpper ? -1 : 1;
    const anterior = ['incisor', 'canine'].includes(profile.type);
    const rimDepth = anterior ? (profile.type === 'incisor' ? .24 : .55) : .90;
    const rimWidth = anterior ? .98 : .91;
    let x, z, height;
    if (ring <= sideRings) {
        const t = ring / sideRings, bulge = Math.sin(Math.PI * t);
        const width = .66 * (1 - t) + rimWidth * t + (anterior ? .08 : .19) * bulge;
        const depth = .72 * (1 - t) + rimDepth * t + (anterior ? .30 : .15) * bulge;
        x = edge.x * width; z = edge.z * depth;
        height = -.055 + (crownHeight(edge.x, edge.z, profile) + .055) * t
            + .024 * (1 - Math.abs(edge.x)) * (1 - t);
        if (anterior && edge.z < 0) {
            z += .20 * gaussian(edge.x, t, 0, .63, .67, .25);
            z -= .13 * gaussian(edge.x, t, .03 * profile.mesialSign, .23, .61, .16);
            z -= .05 * gaussian(Math.abs(edge.x), t, .74, .63, .16, .32);
        } else if (anterior) {
            z += .020 * bulge * Math.cos(edge.x * Math.PI * 2);
        }
    } else {
        const radius = 1 - (ring - sideRings) / capRings;
        x = edge.x * radius * rimWidth; z = edge.z * radius * rimDepth;
        height = crownHeight(edge.x * radius, edge.z * radius, profile);
    }
    return [x * profile.width, direction * height, z * profile.depth + Math.max(0, height) * profile.tilt];
}

function sideKey(segment, isUpper) {
    return [isUpper ? 'top' : 'bottom', 'left', isUpper ? 'bottom' : 'top', 'right'][
        Math.floor(segment / (SEGMENTS / 4))
    ];
}

function smoothRingSeams(geometry, rings, segments) {
    const normals = geometry.getAttribute('normal');
    for (let ring = 0; ring < rings; ring++) {
        const a = ring * (segments + 1), b = a + segments;
        const normal = new THREE.Vector3(normals.getX(a) + normals.getX(b),
            normals.getY(a) + normals.getY(b), normals.getZ(a) + normals.getZ(b)).normalize();
        normals.setXYZ(a, normal.x, normal.y, normal.z);
        normals.setXYZ(b, normal.x, normal.y, normal.z);
    }
    normals.needsUpdate = true;
}

function createCrownGeometry(profile) {
    const vertices = [], colors = [], uvs = [], effects = [], triangles = [];
    const surfaces = Object.fromEntries(ODONTOGRAM_SURFACE_KEYS.map(key => [key, []]));
    const edgeOwners = new Map();
    const ringCount = SIDE_RINGS + CAP_RINGS;
    const stride = SEGMENTS + 1;
    const cervical = new THREE.Color('#dcc9a3'), body = new THREE.Color('#f2ead3'), edgeColor = new THREE.Color('#dbc9a7');
    const addVertex = (point, uv, ring, segment) => {
        vertices.push(...point); uvs.push(...uv);
        const height = clamp01(Math.abs(point[1]) / profile.height);
        let color = cervical.clone().lerp(body, smooth(height / .57));
        color.lerp(edgeColor, smooth((height - .68) / .27) * .70);
        const radius = Math.max(0, 1 - Math.max(0, ring - SIDE_RINGS) / CAP_RINGS);
        const perimeterPoint = perimeter(segment / SEGMENTS * Math.PI * 2 + Math.PI / 4, profile);
        const fissure = ring >= SIDE_RINGS ? fissureMask(perimeterPoint.x * radius, perimeterPoint.z * radius, profile) : 0;
        color.multiplyScalar(1 - .055 * fissure);
        colors.push(color.r, color.g, color.b);
        effects.push(.15 + .85 * smooth((height - .34) / .63), ring >= SIDE_RINGS && !['incisor', 'canine'].includes(profile.type) ? 1 : 0, .72 + .28 * height);
    };
    for (let ring = 0; ring < ringCount; ring++) {
        for (let segment = 0; segment <= SEGMENTS; segment++) {
            const point = crownPoint(ring, segment, profile);
            addVertex(point, [segment / SEGMENTS, clamp01(Math.abs(point[1]) / profile.height)], ring, segment);
        }
    }
    const tip = vertices.length / 3;
    const tipPoint = crownPoint(ringCount, 0, profile);
    addVertex(tipPoint, [.5, clamp01(Math.abs(tipPoint[1]) / profile.height)], ringCount, 0);
    const base = vertices.length / 3;
    addVertex([0, (profile.isUpper ? 1 : -1) * .055, 0], [.5, 0], 0, 0);
    const weldId = index => index < tip && index % stride === SEGMENTS ? index - SEGMENTS : index;
    function face(a, b, c, surface) {
        const indices = profile.isUpper ? [a, c, b] : [a, b, c];
        triangles.push(...indices); surfaces[surface].push(...indices);
        for (const [v1, v2] of [[a, b], [b, c], [c, a]]) {
            const i = weldId(v1), j = weldId(v2);
            const key = i < j ? `${i}:${j}` : `${j}:${i}`;
            const owner = edgeOwners.get(key);
            if (!owner) edgeOwners.set(key, { a: i, b: j, surfaces: new Set([surface]) });
            else owner.surfaces.add(surface);
        }
    }
    for (let ring = 0; ring < ringCount - 1; ring++) {
        for (let segment = 0; segment < SEGMENTS; segment++) {
            const a = ring * stride + segment, b = a + 1, c = a + stride, d = c + 1;
            const key = ring >= CENTER_RING ? 'center' : sideKey(segment, profile.isUpper);
            face(a, c, b, key); face(b, c, d, key);
        }
    }
    for (let segment = 0; segment < SEGMENTS; segment++) {
        const last = (ringCount - 1) * stride;
        face(last + segment, tip, last + segment + 1, 'center');
        face(segment, segment + 1, base, sideKey(segment, profile.isUpper));
    }
    const joined = new THREE.BufferGeometry();
    joined.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));
    joined.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    joined.setAttribute('uv', new THREE.Float32BufferAttribute(uvs, 2));
    joined.setAttribute('dentalEffect', new THREE.Float32BufferAttribute(effects, 3));
    joined.setIndex(triangles); joined.computeVertexNormals();
    smoothRingSeams(joined, ringCount, SEGMENTS);
    const borderVertices = Object.fromEntries(ODONTOGRAM_SURFACE_KEYS.map(key => [key, []]));
    const boundaryVertices = [], normal = joined.getAttribute('normal');
    for (const edge of edgeOwners.values()) {
        if (edge.surfaces.size < 2 || edge.a === base || edge.b === base) continue;
        const line = [];
        for (const index of [edge.a, edge.b]) {
            line.push(vertices[index * 3] + normal.getX(index) * .002,
                vertices[index * 3 + 1] + normal.getY(index) * .002,
                vertices[index * 3 + 2] + normal.getZ(index) * .002);
        }
        boundaryVertices.push(...line);
        for (const key of edge.surfaces) borderVertices[key].push(...line);
    }
    const result = {};
    for (const key of ODONTOGRAM_SURFACE_KEYS) {
        const geometry = new THREE.BufferGeometry(), remap = new Map(), indices = [];
        const sourceIndices = [];
        for (const index of surfaces[key]) {
            if (!remap.has(index)) { remap.set(index, remap.size); sourceIndices.push(index); }
            indices.push(remap.get(index));
        }
        for (const name of ['position', 'normal', 'color', 'uv', 'dentalEffect']) {
            const source = joined.getAttribute(name), values = [];
            for (const index of sourceIndices) for (let axis = 0; axis < source.itemSize; axis++) values.push(source.array[index * source.itemSize + axis]);
            geometry.setAttribute(name, new THREE.Float32BufferAttribute(values, source.itemSize));
        }
        geometry.setIndex(indices); geometry.computeBoundingSphere();
        const outline = new THREE.BufferGeometry();
        outline.setAttribute('position', new THREE.Float32BufferAttribute(borderVertices[key], 3));
        result[key] = { geometry, outline };
    }
    const boundary = new THREE.BufferGeometry();
    boundary.setAttribute('position', new THREE.Float32BufferAttribute(boundaryVertices, 3));
    result.boundary = boundary;
    result.core = createDentinCore(profile);
    return result;
}

function createDentinCore(profile) {
    const segments = 16, sideRings = 4, capRings = 6, rings = sideRings + capRings;
    const stride = segments + 1, vertices = [], indices = [];
    const centerY = (profile.isUpper ? -1 : 1) * profile.height * .42;
    const centerZ = profile.height * .42 * profile.tilt;
    const vertex = point => vertices.push(point[0] * .87, centerY + (point[1] - centerY) * .86, centerZ + (point[2] - centerZ) * .87);
    for (let ring = 0; ring < rings; ring++) for (let segment = 0; segment <= segments; segment++) vertex(crownPoint(ring, segment, profile, sideRings, capRings, segments));
    const tip = vertices.length / 3;
    vertex(crownPoint(rings, 0, profile, sideRings, capRings, segments));
    const base = vertices.length / 3;
    vertex([0, (profile.isUpper ? 1 : -1) * .055, 0]);
    const face = (a, b, c) => indices.push(...(profile.isUpper ? [a, c, b] : [a, b, c]));
    for (let ring = 0; ring < rings - 1; ring++) for (let segment = 0; segment < segments; segment++) {
        const a = ring * stride + segment, b = a + 1, c = a + stride, d = c + 1;
        face(a, c, b); face(b, c, d);
    }
    for (let segment = 0; segment < segments; segment++) {
        const last = (rings - 1) * stride;
        face(last + segment, tip, last + segment + 1); face(segment, segment + 1, base);
    }
    const geometry = new THREE.BufferGeometry();
    geometry.setAttribute('position', new THREE.Float32BufferAttribute(vertices, 3));
    const colors = [], cervical = new THREE.Color('#e0c89e'), body = new THREE.Color('#f0e1bf');
    for (let i = 0; i < vertices.length; i += 3) {
        const color = cervical.clone().lerp(body, smooth(Math.abs(vertices[i + 1]) / (profile.height * .68)));
        colors.push(color.r, color.g, color.b);
    }
    geometry.setAttribute('color', new THREE.Float32BufferAttribute(colors, 3));
    geometry.setIndex(indices); geometry.computeVertexNormals();
    smoothRingSeams(geometry, rings, segments);
    return geometry;
}

export function createOdontogramTooth(toothNumber, isUpper = true) {
    const profile = toothProfile(toothNumber, isUpper), cacheKey = JSON.stringify(profile);
    if (!crownGeometryCache.has(cacheKey)) crownGeometryCache.set(cacheKey, createCrownGeometry(profile));
    const geometries = crownGeometryCache.get(cacheKey), group = new THREE.Group();
    group.name = `tooth-${toothNumber}`;
    const hitMesh = new THREE.Mesh(hitGeometry, hitMaterial);
    hitMesh.visible = false;
    hitMesh.position.set(0, (isUpper ? -1 : 1) * profile.height * .48, profile.height * .48 * profile.tilt);
    group.add(hitMesh);
    const core = new THREE.Mesh(geometries.core, new THREE.MeshStandardMaterial({ color: '#ffffff', vertexColors: true, roughness: .65, metalness: 0 }));
    core.name = `tooth-${toothNumber}-dentin`;
    core.userData.isDentinCore = true;
    core.castShadow = false; core.receiveShadow = false;
    group.add(core);
    const surfaceParts = {}, surfaceOutlines = {};
    for (const key of ODONTOGRAM_SURFACE_KEYS) {
        const part = new THREE.Mesh(geometries[key].geometry, createEnamelMaterial(profile));
        part.name = `tooth-${toothNumber}-${key}`;
        part.userData = { tooth: Number(toothNumber), surfaceKey: key, toothMesh: hitMesh, colorable: true };
        part.castShadow = true; part.receiveShadow = true;
        group.add(part); surfaceParts[key] = part;
        const line = new THREE.LineSegments(geometries[key].outline, new THREE.LineBasicMaterial({
            color: 0xb42345, transparent: true, opacity: 1, depthWrite: false
        }));
        line.visible = false; line.renderOrder = 4; line.raycast = () => { };
        group.add(line); surfaceOutlines[key] = line;
    }
    const boundaryOutline = new THREE.LineSegments(geometries.boundary, new THREE.LineBasicMaterial({
        color: 0x8b8072, transparent: true, opacity: .23, depthWrite: false
    }));
    boundaryOutline.renderOrder = 2; boundaryOutline.raycast = () => { };
    group.add(boundaryOutline);
    hitMesh.userData = {
        tooth: Number(toothNumber), originalColor: ENAMEL, visualGroup: group,
        visualParts: Object.values(surfaceParts), colorableParts: Object.values(surfaceParts),
        surfaceParts, surfaceOutlines, boundaryOutline, core, profile
    };
    return { group, hitMesh };
}

function archPoint(angle, y = 0, isUpper = true) {
    const arch = isUpper ? ARCHES.upper : ARCHES.lower;
    return new THREE.Vector3(Math.cos(angle) * arch.width, y, Math.sin(angle) * arch.depth);
}

function toothPlacements(teethArray, isUpper) {
    const samples = [];
    let length = 0, previous = archPoint(ARCH_START, 0, isUpper);
    for (let i = 0; i <= 320; i++) {
        const angle = THREE.MathUtils.lerp(ARCH_START, ARCH_END, i / 320);
        const point = archPoint(angle, 0, isUpper);
        length += point.distanceTo(previous); samples.push({ length, angle }); previous = point;
    }
    const widths = teethArray.map(number => toothProfile(number, isUpper).width * 2 + .014);
    const total = widths.reduce((sum, width) => sum + width, 0);
    let travelled = 0;
    return teethArray.map((number, index) => {
        const distance = (travelled + widths[index] / 2) / total * length;
        travelled += widths[index];
        const sample = samples.findIndex(item => item.length >= distance);
        const a = samples[Math.max(0, sample - 1)], b = samples[Math.max(0, sample)];
        const angle = THREE.MathUtils.lerp(a.angle, b.angle, (distance - a.length) / (b.length - a.length || 1));
        return { number, angle };
    });
}

export function createOdontogramArch({ scene, teethArray, yPosition, isUpper = true, teethMeshes }) {
    const group = new THREE.Group(), arch = isUpper ? ARCHES.upper : ARCHES.lower;
    group.name = isUpper ? 'upper-teeth' : 'lower-teeth';
    for (const { number, angle } of toothPlacements(teethArray, isUpper)) {
        const tooth = createOdontogramTooth(number, isUpper), point = archPoint(angle, yPosition, isUpper);
        point.y += (isUpper ? 1 : -1) * .035 * Math.abs(Math.cos(angle)) ** 2;
        tooth.group.position.copy(point);
        tooth.group.rotation.y = Math.atan2(Math.cos(angle) / arch.width, Math.sin(angle) / arch.depth);
        group.add(tooth.group); teethMeshes.push(tooth.hitMesh);
    }
    scene.add(group);
    return group;
}

export function createOdontogramGumArch({ scene, yPosition, isUpper = true, teethArray = [] }) {
    const arch = isUpper ? ARCHES.upper : ARCHES.lower, towardTeeth = isUpper ? -1 : 1;
    const toothY = yPosition - (isUpper ? .28 : -.28);
    if (!teethArray.length) teethArray = isUpper
        ? [18, 17, 16, 15, 14, 13, 12, 11, 21, 22, 23, 24, 25, 26, 27, 28]
        : [48, 47, 46, 45, 44, 43, 42, 41, 31, 32, 33, 34, 35, 36, 37, 38];
    const placements = toothPlacements(teethArray, isUpper).map(item => ({ ...item, profile: toothProfile(item.number, isUpper) }));
    const positions = [], colors = [], uvs = [], effects = [], indices = [];
    const segments = 32, marginRows = 4, wallRows = 6, faceSegments = segments / 4;
    const marginColor = new THREE.Color('#923746'), attachedColor = new THREE.Color('#8e393a'), mucosaColor = new THREE.Color('#b25454');

    function profileAt(angle) {
        const next = placements.findIndex(item => item.angle <= angle);
        if (next <= 0) return placements[next === 0 ? 0 : placements.length - 1].profile;
        const a = placements[next - 1], b = placements[next];
        const t = smooth((a.angle - angle) / (a.angle - b.angle));
        return {
            depth: THREE.MathUtils.lerp(a.profile.depth, b.profile.depth, t),
            height: THREE.MathUtils.lerp(a.profile.height, b.profile.height, t)
        };
    }
    const neckY = angle => toothY - towardTeeth * .035 * Math.cos(angle) ** 2;
    const ridgeWidth = angle => profileAt(angle).depth * .76 + .13;
    const outward = angle => new THREE.Vector3(Math.cos(angle) / arch.width, 0, Math.sin(angle) / arch.depth).normalize();
    function tissuePoint(angle, radial, layer = 0) {
        const point = archPoint(angle, neckY(angle), isUpper), normal = outward(angle);
        const top = THREE.MathUtils.lerp(profileAt(angle).height * .11, -.13, Math.abs(radial) ** 1.65);
        const depth = THREE.MathUtils.lerp(top, -.52, Math.sin(layer * Math.PI / 2));
        const width = ridgeWidth(angle) * radial * (1 + .035 * Math.sin(layer * Math.PI) - .10 * smooth(layer));
        point.x += normal.x * width; point.z += normal.z * width; point.y += towardTeeth * depth;
        return point;
    }
    function vertex(point, color, stippling, scatter) {
        let angle = Math.atan2(point.z / arch.depth, point.x / arch.width);
        if (angle < -Math.PI / 2) angle += Math.PI * 2;
        const center = archPoint(angle, neckY(angle), isUpper), normal = outward(angle);
        const radial = (point.x - center.x) * normal.x + (point.z - center.z) * normal.z;
        const index = positions.length / 3;
        positions.push(point.x, point.y, point.z); colors.push(color.r, color.g, color.b);
        uvs.push((ARCH_START - angle) / (ARCH_START - ARCH_END) * 18, radial * 3 + (point.y - center.y) * 1.4);
        effects.push(scatter, .18 + .82 * smooth(Math.abs(point.y - center.y) / .50), stippling);
        return index;
    }
    function face(a, b, c, normal) {
        const ax = positions[b * 3] - positions[a * 3], ay = positions[b * 3 + 1] - positions[a * 3 + 1], az = positions[b * 3 + 2] - positions[a * 3 + 2];
        const bx = positions[c * 3] - positions[a * 3], by = positions[c * 3 + 1] - positions[a * 3 + 1], bz = positions[c * 3 + 2] - positions[a * 3 + 2];
        const facing = (ay * bz - az * by) * normal.x + (az * bx - ax * bz) * normal.y + (ax * by - ay * bx) * normal.z;
        indices.push(...(facing >= 0 ? [a, b, c] : [a, c, b]));
    }
    const crownNormal = new THREE.Vector3(0, towardTeeth, 0), basalNormal = new THREE.Vector3(0, -towardTeeth, 0);

    for (let index = 0; index < placements.length; index++) {
        const { angle, profile } = placements[index];
        const startAngle = index === 0 ? ARCH_START : (placements[index - 1].angle + angle) / 2;
        const endAngle = index === placements.length - 1 ? ARCH_END : (angle + placements[index + 1].angle) / 2;
        const cellAngle = x => x < 0 ? THREE.MathUtils.lerp(angle, startAngle, -x) : THREE.MathUtils.lerp(angle, endAngle, x);
        const center = archPoint(angle, neckY(angle), isUpper);
        const yaw = Math.atan2(Math.cos(angle) / arch.width, Math.sin(angle) / arch.depth);
        const boundary = [], topRings = [];
        for (let segment = 0; segment <= segments; segment++) {
            const theta = segment / segments * Math.PI * 2 + Math.PI / 4;
            const c = Math.cos(theta), s = Math.sin(theta), extent = Math.max(Math.abs(c), Math.abs(s));
            boundary.push({ x: c / extent, z: s / extent, angle: cellAngle(c / extent) });
        }
        for (let row = 0; row <= marginRows; row++) {
            const ring = [], t = row / marginRows;
            for (let segment = 0; segment <= segments; segment++) {
                const theta = segment / segments * Math.PI * 2 + Math.PI / 4;
                const margin = .070 + .105 * Math.abs(Math.cos(theta)) ** 5;
                const local = crownPoint(margin * SIDE_RINGS, segment / segments * SEGMENTS, profile);
                const radial = new THREE.Vector3(local[0], 0, local[2]).normalize();
                local[0] += radial.x * .003; local[2] += radial.z * .003;
                const inner = new THREE.Vector3(center.x + local[0] * Math.cos(yaw) + local[2] * Math.sin(yaw), center.y + local[1],
                    center.z - local[0] * Math.sin(yaw) + local[2] * Math.cos(yaw));
                const edge = boundary[segment], outer = tissuePoint(edge.angle, edge.z);
                const point = new THREE.Vector3(THREE.MathUtils.lerp(inner.x, outer.x, t), THREE.MathUtils.lerp(inner.y, outer.y, t), THREE.MathUtils.lerp(inner.z, outer.z, t));
                point.y += towardTeeth * .012 * Math.sin(t * Math.PI);
                const shade = marginColor.clone().lerp(attachedColor, smooth(t * 1.15));
                ring.push(vertex(point, shade, .06 + .84 * smooth(t), .9 - .25 * t));
            }
            topRings.push(ring);
        }
        for (let row = 0; row < marginRows; row++) for (let segment = 0; segment < segments; segment++) {
            const a = topRings[row][segment], b = topRings[row][segment + 1], c = topRings[row + 1][segment], d = topRings[row + 1][segment + 1];
            face(a, b, c, crownNormal); face(b, d, c, crownNormal);
        }
        const wallRings = [topRings[marginRows]];
        for (let row = 1; row <= wallRows; row++) {
            const t = row / wallRows, ring = [];
            for (const edge of boundary) {
                const shade = attachedColor.clone().lerp(mucosaColor, smooth((t - .35) / .65) * .70);
                ring.push(vertex(tissuePoint(edge.angle, edge.z, t), shade, .9 - .55 * smooth(t), .65 - .20 * t));
            }
            wallRings.push(ring);
        }
        for (let segment = 0; segment < segments; segment++) {
            const theta = (segment + .5) / segments * Math.PI * 2 + Math.PI / 4;
            const alongRidge = Math.abs(Math.sin(theta)) > Math.abs(Math.cos(theta));
            const atEnd = (index === 0 && Math.cos(theta) < 0) || (index === placements.length - 1 && Math.cos(theta) > 0);
            if (!alongRidge && !atEnd) continue;
            const edgeAngle = (boundary[segment].angle + boundary[segment + 1].angle) / 2;
            let normal;
            if (alongRidge) {
                normal = outward(edgeAngle);
                normal.x *= Math.sign(Math.sin(theta)); normal.z *= Math.sign(Math.sin(theta));
            } else {
                normal = new THREE.Vector3(arch.width * Math.sin(edgeAngle), 0, -arch.depth * Math.cos(edgeAngle));
                normal.x *= Math.sign(Math.cos(theta)); normal.z *= Math.sign(Math.cos(theta));
            }
            for (let row = 0; row < wallRows; row++) {
                const a = wallRings[row][segment], b = wallRings[row][segment + 1], c = wallRings[row + 1][segment], d = wallRings[row + 1][segment + 1];
                face(a, b, c, normal); face(b, d, c, normal);
            }
        }
        const floor = [];
        for (let row = 0; row <= faceSegments; row++) {
            const z = Math.tan(-Math.PI / 4 + row / faceSegments * Math.PI / 2), strip = [];
            for (let column = 0; column <= faceSegments; column++) {
                const x = Math.tan(-Math.PI / 4 + column / faceSegments * Math.PI / 2);
                strip.push(vertex(tissuePoint(cellAngle(x), z, 1), mucosaColor, .30, .45));
            }
            floor.push(strip);
        }
        for (let row = 0; row < faceSegments; row++) for (let column = 0; column < faceSegments; column++) {
            const a = floor[row][column], b = floor[row][column + 1], c = floor[row + 1][column], d = floor[row + 1][column + 1];
            face(a, b, c, basalNormal); face(b, d, c, basalNormal);
        }
    }
    const geometry = new THREE.BufferGeometry(), remap = new Map(), compact = { position: [], color: [], uv: [], dentalEffect: [] }, compactIndices = [];
    const attributes = { position: [positions, 3], color: [colors, 3], uv: [uvs, 2], dentalEffect: [effects, 3] };
    for (const index of indices) {
        const key = [positions[index * 3], positions[index * 3 + 1], positions[index * 3 + 2], uvs[index * 2], uvs[index * 2 + 1]].map(value => Math.round(value * 1e6)).join(':');
        if (!remap.has(key)) {
            remap.set(key, remap.size);
            for (const [name, [values, size]] of Object.entries(attributes)) for (let axis = 0; axis < size; axis++) compact[name].push(values[index * size + axis]);
        }
        compactIndices.push(remap.get(key));
    }
    for (const [name, values] of Object.entries(compact)) geometry.setAttribute(name, new THREE.Float32BufferAttribute(values, attributes[name][1]));
    geometry.setIndex(compactIndices); geometry.computeVertexNormals(); geometry.computeBoundingSphere();
    const normals = geometry.getAttribute('normal'), vertices = geometry.getAttribute('position'), seamNormals = new Map();
    const positionKey = i => [vertices.getX(i), vertices.getY(i), vertices.getZ(i)].map(value => Math.round(value * 1e6)).join(':');
    for (let i = 0; i < vertices.count; i++) {
        const key = positionKey(i), sum = seamNormals.get(key) || new THREE.Vector3();
        sum.x += normals.getX(i); sum.y += normals.getY(i); sum.z += normals.getZ(i); seamNormals.set(key, sum);
    }
    for (const normal of seamNormals.values()) normal.normalize();
    for (let i = 0; i < vertices.count; i++) {
        const normal = seamNormals.get(positionKey(i)); normals.setXYZ(i, normal.x, normal.y, normal.z);
    }
    const gum = new THREE.Mesh(geometry, createGingivaMaterial());
    gum.name = isUpper ? 'maxillary-gingiva' : 'mandibular-gingiva';
    gum.userData.isGum = true; gum.castShadow = true; gum.receiveShadow = true;
    scene.add(gum);
    return gum;
}

function finishOralGeometry(positions, colors, uvs, effects, indices) {
    const geometry = new THREE.BufferGeometry();
    for (const [name, values, size] of [['position', positions, 3], ['color', colors, 3], ['uv', uvs, 2], ['dentalEffect', effects, 3]]) {
        geometry.setAttribute(name, new THREE.Float32BufferAttribute(values, size));
    }
    geometry.setIndex(indices); geometry.computeVertexNormals(); geometry.computeBoundingSphere();
    return geometry;
}

export function createOdontogramTongue({ scene, yPosition = 0 }) {
    const positions = [], colors = [], uvs = [], effects = [], indices = [];
    const lengthSegments = 56, radialSegments = 64, stride = radialSegments + 1;
    const tipColor = new THREE.Color('#a95560'),
        dorsalColor = new THREE.Color('#933145'),
        ventralColor = new THREE.Color('#75373e');
    const add = (x, y, z, u, v, dorsal) => {
        positions.push(x, y + yPosition, z); uvs.push(u * 4, v * 7);
        const color = ventralColor.clone().lerp(dorsalColor, dorsal).lerp(tipColor, smooth((v - .48) / .52) * dorsal);
        const sulcus = Math.exp(-((x / .14) ** 2)) * smooth(v / .18) * smooth((1 - v) / .14) * dorsal;
        color.multiplyScalar(1 - .055 * sulcus);
        colors.push(color.r, color.g, color.b); effects.push(.65 + .35 * dorsal, dorsal, .18 + .82 * dorsal);
    };
    for (let row = 1; row < lengthSegments; row++) {
        const t = row / lengthSegments, envelope = Math.sin(Math.PI * t);
        const width = 2.05 * Math.sqrt(envelope) * (1 + .22 * Math.cos(Math.PI * t));
        const centerY = .15 + .10 * Math.cos(t * Math.PI);
        for (let segment = 0; segment <= radialSegments; segment++) {
            const theta = segment / radialSegments * Math.PI * 2, sine = Math.sin(theta), dorsal = Math.max(0, sine);
            const x = width * Math.cos(theta);
            const z = -.62 + 3.66 * t + .26 * Math.exp(-((x / .56) ** 2)) * smooth((.24 - t) / .24);
            let y = centerY + sine * (sine > 0 ? .54 : .36) * envelope ** .42;
            y -= .062 * Math.exp(-((x / .12) ** 2)) * dorsal ** 3 * smooth(t / .18) * smooth((1 - t) / .13);
            y -= .08 * Math.exp(-((x / .64) ** 2)) * smooth((.30 - t) / .30) * dorsal ** 3;
            for (const cx of [-1.35, -.96, -.57, -.19, .19, .57, .96, 1.35]) {
                const cz = .10 + .26 * Math.abs(cx);
                y += .020 * gaussian(x, z, cx, cz, .073, .078) * dorsal ** 4;
            }
            y += .006 * Math.sin(x * 5 + t * 13) * dorsal * envelope;
            add(x, y, z, segment / radialSegments, t, dorsal);
        }
    }
    const back = positions.length / 3; add(0, .25, -.36, .5, 0, .3);
    const tip = positions.length / 3; add(0, .05, 3.04, .5, 1, .5);
    for (let row = 0; row < lengthSegments - 2; row++) for (let segment = 0; segment < radialSegments; segment++) {
        const a = row * stride + segment, b = a + 1, c = a + stride, d = c + 1;
        indices.push(a, b, c, b, d, c);
    }
    for (let segment = 0; segment < radialSegments; segment++) {
        const last = (lengthSegments - 2) * stride;
        indices.push(back, segment + 1, segment, tip, last + segment, last + segment + 1);
    }
    const geometry = finishOralGeometry(positions, colors, uvs, effects, indices);
    smoothRingSeams(geometry, lengthSegments - 1, radialSegments);
    const tongue = new THREE.Mesh(geometry, createTongueMaterial());
    tongue.name = 'tongue'; tongue.userData = { isOralTissue: true, tissueType: 'tongue', arch: 'lower' };
    tongue.castShadow = true; tongue.receiveShadow = true;
    scene.add(tongue); return tongue;
}

export function createOdontogramPalate({ scene, yPosition = 0, isUpper = true, teethArray }) {
    const positions = [], colors = [], uvs = [], effects = [], indices = [];
    const rings = 36, segments = 64, stride = segments + 1, arch = isUpper ? ARCHES.upper : ARCHES.lower;
    const placements = toothPlacements(teethArray, isUpper).map(item => ({ ...item, profile: toothProfile(item.number, isUpper) }));
    const oralColor = new THREE.Color(isUpper ? '#b65e5e' : '#934b4b'), anteriorColor = new THREE.Color(isUpper ? '#934a4a' : '#713d3d');
    function depthAt(angle) {
        const next = placements.findIndex(item => item.angle <= angle);
        if (next <= 0) return placements[next === 0 ? 0 : placements.length - 1].profile.depth;
        const a = placements[next - 1], b = placements[next];
        return THREE.MathUtils.lerp(a.profile.depth, b.profile.depth, smooth((a.angle - angle) / (a.angle - b.angle)));
    }
    function point(radius, angle, backing = false) {
        const normal = new THREE.Vector3(Math.cos(angle) / arch.width, 0, Math.sin(angle) / arch.depth).normalize();
        const inset = depthAt(angle) * .76 + .11;
        const x = radius * (Math.cos(angle) * arch.width - normal.x * inset);
        let z = radius * (Math.sin(angle) * arch.depth - normal.z * inset);
        z += (isUpper ? .58 : .20) * Math.exp(-((x / .98) ** 4)) * Math.exp(-((z / .58) ** 2));
        let y = isUpper ? .18 + .77 * (1 - radius ** 2) : -.20 - .09 * (1 - radius ** 2);
        if (isUpper && !backing) {
            y -= .022 * Math.exp(-((x / .060) ** 2)) * smooth((z - .55) / .45) * smooth((3.17 - z) / .25);
            for (let ridge = 0; ridge < 5; ridge++) {
                const path = 2.02 + ridge * .185 - .20 * Math.abs(x) + .032 * Math.sin(Math.abs(x) * 7 + ridge);
                const fade = smooth((1.58 - Math.abs(x)) / .55) * smooth(Math.abs(x) / .17);
                y -= .037 * Math.exp(-(((z - path) / .057) ** 2)) * fade;
            }
            y -= .056 * gaussian(x, z, 0, 2.96, .12, .17);
        }
        if (backing) y += (isUpper ? 1 : -1) * (.14 + .14 * radius ** 4);
        return [x, y + yPosition, z];
    }
    function add(radius, angle, backing) {
        const [x, y, z] = point(radius, angle, backing), color = oralColor.clone().lerp(anteriorColor, smooth((z - 1.45) / 1.7));
        positions.push(x, y, z); colors.push(color.r, color.g, color.b); uvs.push((x / 6 + .5) * 18, z * .32);
        effects.push(.7, isUpper ? .35 : .62, isUpper ? .28 : .15);
    }
    for (const backing of [false, true]) {
        add(0, Math.PI / 2, backing);
        for (let ring = 1; ring <= rings; ring++) for (let segment = 0; segment <= segments; segment++) {
            add(ring / rings, THREE.MathUtils.lerp(ARCH_START, ARCH_END, segment / segments), backing);
        }
    }
    const layerSize = 1 + rings * stride;
    const index = (ring, segment) => ring === 0 ? 0 : 1 + (ring - 1) * stride + segment;
    const frontFace = (a, b, c) => {
        const sign = (positions[b * 3 + 2] - positions[a * 3 + 2]) * (positions[c * 3] - positions[a * 3])
            - (positions[b * 3] - positions[a * 3]) * (positions[c * 3 + 2] - positions[a * 3 + 2]);
        if ((sign > 0) === isUpper) [b, c] = [c, b];
        indices.push(a, b, c, a + layerSize, c + layerSize, b + layerSize);
    };
    for (let segment = 0; segment < segments; segment++) frontFace(0, index(1, segment), index(1, segment + 1));
    for (let ring = 1; ring < rings; ring++) for (let segment = 0; segment < segments; segment++) {
        const a = index(ring, segment), b = index(ring, segment + 1), c = index(ring + 1, segment), d = index(ring + 1, segment + 1);
        frontFace(a, b, c); frontFace(b, d, c);
    }
    const boundary = [];
    for (let segment = 0; segment <= segments; segment++) boundary.push(index(rings, segment));
    for (let ring = rings - 1; ring >= 0; ring--) boundary.push(index(ring, segments));
    for (let ring = 1; ring < rings; ring++) boundary.push(index(ring, 0));
    for (let i = 0; i < boundary.length; i++) {
        const a = boundary[i], b = boundary[(i + 1) % boundary.length], c = a + layerSize, d = b + layerSize;
        indices.push(...(isUpper ? [a, b, c, b, d, c] : [a, c, b, b, c, d]));
    }
    const mesh = new THREE.Mesh(finishOralGeometry(positions, colors, uvs, effects, indices), createGingivaMaterial());
    mesh.name = isUpper ? 'hard-palate' : 'mouth-floor';
    mesh.userData = { isOralTissue: true, tissueType: isUpper ? 'palate' : 'floor', arch: isUpper ? 'upper' : 'lower' };
    mesh.castShadow = true; mesh.receiveShadow = true;
    scene.add(mesh); return mesh;
}

export function createOdontogramPresentation({ scene, teethMeshes, upperTeeth, lowerTeeth }) {
    const root = new THREE.Group(), jaws = {}, oralTissues = [];
    root.name = 'dental-arches';
    for (const [teethArray, isUpper] of [[upperTeeth, true], [lowerTeeth, false]]) {
        const jaw = new THREE.Group(), content = new THREE.Group(), key = isUpper ? 'upper' : 'lower';
        jaw.name = `${key}-jaw`; jaw.position.y = isUpper ? 1.82 : -2.0;
        jaw.rotation.x = isUpper ? -.70 : .82;
        content.position.z = -1.70; jaw.add(content);
        createOdontogramGumArch({ scene: content, teethArray, yPosition: isUpper ? .28 : -.28, isUpper });
        createOdontogramArch({ scene: content, teethArray, yPosition: 0, isUpper, teethMeshes });
        oralTissues.push(createOdontogramPalate({ scene: content, isUpper, teethArray }));
        if (!isUpper) oralTissues.push(createOdontogramTongue({ scene: content }));
        root.add(jaw); jaws[key] = jaw;
    }
    scene.add(root);
    return { root, jaws, oralTissues };
}
