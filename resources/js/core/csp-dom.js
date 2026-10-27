import { actionHandlers } from './action-handlers';
import presentationCss from '../../css/csp-utilities.css?inline';

const events = ['click', 'change', 'submit', 'input', 'error', 'load', 'keydown', 'keyup', 'focus', 'blur'];
const bound = new WeakMap();
const styled = new WeakMap();
const presented = new WeakMap();
const presentations = new Map();
let presentationSheet;
let temporaryStyle;
try {
    presentationSheet = new CSSStyleSheet();
    presentationSheet.replaceSync(presentationCss);
} catch {
    temporaryStyle = document.createElement('style');
    temporaryStyle.nonce = document.querySelector('meta[name="csp-nonce"]')?.content || '';
    temporaryStyle.textContent = presentationCss;
    document.head.append(temporaryStyle);
    presentationSheet = temporaryStyle.sheet;
}
for (const rule of presentationSheet.cssRules) {
    for (const selector of rule.selectorText?.split(',') ?? []) {
        const match = selector.trim().match(/^\[data-ui-presentation=(?:"([^"]+)"|'([^']+)'|([-\w]+))\]$/);
        const id = match && (match[1] || match[2] || match[3]);
        if (id) presentations.set(id, Array.from(rule.style, property =>
            [property, rule.style.getPropertyValue(property), rule.style.getPropertyPriority(property)]));
    }
}
temporaryStyle?.remove();

function applyInitialPresentation(element) {
    const id = element.getAttribute('data-ui-presentation');
    if (!id || presented.get(element) === id) return;
    presented.set(element, id);
    for (const [property, value, priority] of presentations.get(id) ?? []) {
        element.style.setProperty(property, value, priority);
    }
}

export function applyPresentation(element) {
    const text = element.getAttribute('data-ui-style');
    if (!text || styled.get(element) === text) return;
    styled.set(element, text);
    // Split declarations outside strings/functions. Apply individual CSSOM
    // properties; setting style attributes or cssText is blocked by strict CSP.
    let start = 0, depth = 0, quote = null;
    const apply = declaration => {
        const colon = declaration.indexOf(':');
        if (colon < 1) return;
        const property = declaration.slice(0, colon).trim();
        if (!/^(?:--)?[a-z][a-z\d-]*$/i.test(property)) return;
        const raw = declaration.slice(colon + 1).trim();
        const important = /\s*!important\s*$/i.test(raw);
        element.style.setProperty(property, raw.replace(/\s*!important\s*$/i, ''), important ? 'important' : '');
    };
    for (let i = 0; i <= text.length; i++) {
        const c = text[i];
        if (quote) { if (c === '\\') i++; else if (c === quote) quote = null; }
        else if (c === '"' || c === "'") quote = c;
        else if (c === '(') depth++;
        else if (c === ')') depth--;
        if ((c === ';' && !quote && depth === 0) || i === text.length) {
            apply(text.slice(start, i)); start = i + 1;
        }
    }
}

export function bindCspElements(root = document) {
    const selector = events.map(event => `[data-ui-${event}]`).concat('[data-ui-style]', '[data-ui-presentation]').join(',');
    const elements = [...(root.querySelectorAll?.(selector) ?? [])];
    if (root.matches?.(selector)) elements.unshift(root);
    for (const element of elements) {
        // Preserve the original inline priority and .style reads for existing UI
        // code, while the external stylesheet also supplies the initial paint.
        applyInitialPresentation(element);
        applyPresentation(element);
        const registered = bound.get(element) ?? new Set();
        bound.set(element, registered);
        for (const type of events) {
            if (!element.hasAttribute(`data-ui-${type}`) || registered.has(type)) continue;
            registered.add(type);
            const listener = function(event) {
                // Preserve existing programmatic .onclick/.onchange overrides.
                if (typeof this[`on${type}`] === 'function') return;
                const handler = actionHandlers[this.getAttribute(`data-ui-${type}`)];
                if (typeof handler !== 'function') return;
                if (type === 'error' && this.tagName === 'IMG') {
                    if (this.dataset.uiFallbackUsed) return;
                    this.dataset.uiFallbackUsed = 'true';
                }
                if (handler.call(this, event) === false) event.preventDefault();
            };
            element.addEventListener(type, listener);
            // Image errors can precede execution of deferred module scripts.
            if (type === 'error' && element.tagName === 'IMG' && element.complete && !element.naturalWidth) {
                listener.call(element, new Event('error'));
            }
        }
    }
}

bindCspElements();
new MutationObserver(records => {
    for (const record of records) {
        if (record.type === 'attributes') bindCspElements(record.target);
        else for (const node of record.addedNodes) if (node.nodeType === 1) bindCspElements(node);
    }
}).observe(document.documentElement, { childList: true, subtree: true, attributes: true,
    attributeFilter: ['data-ui-style', 'data-ui-presentation', ...events.map(event => `data-ui-${event}`)] });
