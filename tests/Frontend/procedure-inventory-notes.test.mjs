import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const view = readFileSync(new URL('../../resources/views/shared/inventory.blade.php', import.meta.url), 'utf8');
const endpointIndex = view.indexOf("const endpoint = @json(route('dentist.inventory.procedure-notes'));");
const source = view.slice(view.lastIndexOf('(() => {', endpointIndex), view.indexOf('</script>', endpointIndex))
    .replace("@json(route('dentist.inventory.procedure-notes'))", '"/inventory/procedure-notes"');

function element() {
    return {
        value: '', hidden: false, textContent: '', listeners: {}, children: [],
        addEventListener(name, callback) { this.listeners[name] = callback; },
        setAttribute() {}, removeAttribute() {},
        replaceChildren() { this.children = []; },
        append(node) { this.children.push(node); }
    };
}

function setup(fetch) {
    const nodes = new Map();
    const get = id => {
        if (!nodes.has(id)) nodes.set(id, element());
        return nodes.get(id);
    };
    const events = {};
    get('procedureInventoryNotesModal').id = 'procedureInventoryNotesModal';
    get('procedureInventoryNotesEmpty').textContent = 'No inventory notes have been recorded from procedures yet.';
    const document = { getElementById: get, createElement: element, addEventListener(name, callback) { events[name] = callback; } };
    const window = { location: { origin: 'https://clinic.test' } };
    vm.runInNewContext(source, { document, window, fetch, URL, AbortController, setTimeout, clearTimeout });
    return { get, open: () => events['ui-modal:opened']({ detail: { modal: get('procedureInventoryNotesModal') } }) };
}

const note = {
    procedure_id: 3, appointment_id: 4, patient_id: 5, patient_name: 'Test Patient',
    procedure_name: 'Tooth Extraction', procedure_date: 'Oct 23, 2026 09:20 AM',
    visit_date: 'Oct 23, 2026', dentist_name: 'Test Dentist',
    inventory_notes: "2 gauze\n<img src=x onerror=alert(1)>"
};

test('opening the modal loads all notes as plain text with line breaks', async () => {
    const requests = [];
    const app = setup(async (url, options) => {
        requests.push({ url, options });
        return { ok: true, json: async () => ({ data: [note], current_page: Number(url.searchParams.get('page')), last_page: 2 }) };
    });
    await app.open();
    assert.equal(requests[0].url.searchParams.has('q'), false);
    assert.equal(requests[0].url.searchParams.has('page'), false);
    assert.equal(requests[0].options.cache, 'no-store');
    const copy = app.get('procedureInventoryNotesEntries').children[0].children[0];
    assert.equal(copy.children[0].textContent, 'Test Patient');
    assert.equal(copy.children.at(-1).textContent, note.inventory_notes);
    assert.match(copy.children.at(-1).className, /whitespace-pre-wrap/);
    assert.equal(app.get('procedureInventoryNotesLoading').hidden, true);

});

test('empty results and network errors have readable states and retry works', async () => {
    let failed = true;
    const app = setup(async () => {
        if (failed) throw new Error('offline');
        return { ok: true, json: async () => ({ data: [], current_page: 1, last_page: 1 }) };
    });
    await app.open();
    assert.equal(app.get('procedureInventoryNotesError').hidden, false);
    assert.equal(app.get('procedureInventoryNotesEntries').children.length, 0);
    failed = false;
    await app.get('procedureInventoryNotesRetry').listeners.click();
    assert.equal(app.get('procedureInventoryNotesError').hidden, true);
    assert.equal(app.get('procedureInventoryNotesEmpty').hidden, false);
    assert.equal(app.get('procedureInventoryNotesEmpty').textContent, 'No inventory notes have been recorded from procedures yet.');
});

test('older requests cannot replace the latest list when reopening the modal', async () => {
    const requests = [];
    const app = setup((url, options) => new Promise(resolve => requests.push({ resolve, signal: options.signal })));
    const first = app.open();
    const second = app.open();
    assert.equal(requests[0].signal.aborted, true);
    requests[1].resolve({ ok: true, json: async () => ({ data: [], current_page: 1, last_page: 1 }) });
    await second;
    requests[0].resolve({ ok: true, json: async () => ({ data: [note], current_page: 1, last_page: 1 }) });
    await first;
    assert.equal(app.get('procedureInventoryNotesEntries').children.length, 0);
});
