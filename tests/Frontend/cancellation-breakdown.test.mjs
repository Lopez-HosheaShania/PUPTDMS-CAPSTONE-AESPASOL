import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const view = readFileSync(new URL('../../resources/views/shared/reports.blade.php', import.meta.url), 'utf8');
const start = view.indexOf("    document.addEventListener('DOMContentLoaded', () => {", view.indexOf("@section('scripts')"));
const script = view.slice(start, view.indexOf('</script>', start))
    .replace(/@json\(route\(\$analyticsRoutePrefix\.'\.reports\.cancellation-breakdown'\)\)/, '"/breakdown"');

function element() {
    return {
        hidden: false, textContent: '', dataset: {}, listeners: {}, children: [],
        addEventListener(name, callback) { this.listeners[name] = callback; },
        setAttribute() {}, removeAttribute() {},
        replaceChildren() { this.children = []; },
        append(...nodes) { this.children.push(...nodes); }
    };
}

function setup(fetch) {
    const ids = new Map();
    const get = id => {
        if (!ids.has(id)) ids.set(id, element());
        return ids.get(id);
    };
    const reasons = element();
    const rate = element();
    const period = element();
    const metrics = ['total_scheduled', 'total_cancelled', 'cancellation_rate', 'total_no_shows', 'no_show_rate'].map(key => {
        const node = element();
        node.dataset.cancellationMetric = key;
        return node;
    });
    get('cancellationBreakdownModal').querySelectorAll = () => metrics;
    get('cancellationLegacyNote').querySelector = () => get('legacyText');
    get('cancellationMonth').value = '2';
    get('cancellationMonth').selectedOptions = [{ textContent: 'February' }];
    get('cancellationYear').value = '2024';
    const document = {
        getElementById: get, createElement: element,
        querySelector: selector => selector.includes('Reasons') ? reasons : selector.includes('card-rate') ? rate : period,
        addEventListener(name, callback) { callback(); }
    };
    vm.runInNewContext(script, { document, window: { location: { origin: 'https://clinic.test' } }, fetch, URL, AbortController, setTimeout, clearTimeout });
    return { get, reasons, rate, period, metrics, change: () => get('cancellationMonth').listeners.change() };
}

const data = (overrides = {}) => ({
    available: true, period: 'February 2024', total_scheduled: 4, total_cancelled: 1,
    cancellation_rate: 25, total_no_shows: 0, no_show_rate: 0, legacy_no_shows: 0,
    reasons: [{ reason: '<img src=x onerror=alert(1)>', count: 1, percentage: 100 }], ...overrides
});

test('filters fetch the selected history without navigation and safely update the modal and card', async () => {
    let url;
    let options;
    const app = setup(async (target, args) => {
        url = target;
        options = args;
        return { ok: true, json: async () => data() };
    });
    await app.change();
    assert.equal(url.searchParams.get('month'), '2');
    assert.equal(url.searchParams.get('year'), '2024');
    assert.equal(options.cache, 'no-store');
    assert.equal(app.rate.textContent, '25%');
    assert.equal(app.period.textContent, 'February 2024');
    assert.equal(app.get('cancellationResults').hidden, false);
    assert.equal(app.get('cancellationLoading').hidden, true);
    assert.equal(app.get('cancellationError').hidden, true);
    assert.equal(app.reasons.children[0].children[0].children[0].textContent, '<img src=x onerror=alert(1)>');
});

test('a late response cannot replace the latest selected period', async () => {
    const pending = [];
    const app = setup((url, options) => new Promise(resolve => pending.push({ resolve, signal: options.signal })));
    const first = app.change();
    app.get('cancellationYear').value = '2023';
    const second = app.change();
    assert.equal(pending[0].signal.aborted, true);
    pending[1].resolve({ ok: true, json: async () => data({ period: 'February 2023', cancellation_rate: 50 }) });
    await second;
    pending[0].resolve({ ok: true, json: async () => data() });
    await first;
    assert.equal(app.period.textContent, 'February 2023');
    assert.equal(app.rate.textContent, '50%');
});

test('network failure hides stale numbers and permits retry for an empty period', async () => {
    let failed = true;
    const app = setup(async () => {
        if (failed) throw new Error('offline');
        return { ok: true, json: async () => data({ reasons: [], total_cancelled: 0, cancellation_rate: 0 }) };
    });
    await app.change();
    assert.equal(app.get('cancellationError').hidden, false);
    assert.equal(app.get('cancellationResults').hidden, true);
    assert.equal(app.rate.textContent, 'Unavailable');
    failed = false;
    await app.get('retryCancellationBreakdown').listeners.click();
    assert.equal(app.get('cancellationError').hidden, true);
    assert.equal(app.get('cancellationResults').hidden, false);
    assert.equal(app.get('cancellationReasons').hidden, true);
    assert.equal(app.rate.textContent, '0%');
});
