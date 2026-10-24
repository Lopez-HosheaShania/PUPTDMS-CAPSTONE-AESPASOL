import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const template = readFileSync(new URL('../../resources/views/components/booking/personal-information-verification.blade.php', import.meta.url), 'utf8');
const source = template.match(/<script>([\s\S]*)<\/script>/)[1]
    .replace(/@json\(route\('book.appointment.verify-personal-information'\)\)/g, '"/verify"')
    .replace(/@json\(session\('booking_verification_error'\)\)/g, 'null');

function setup(fetch) {
    const listeners = {};
    const elements = {};
    elements.personalInformationVerificationClose = { textContent: 'Close' };
    const navigations = [];
    const modals = [];
    const window = {
        location: { origin: 'https://clinic.test', assign: url => navigations.push(url) },
        openModal: id => modals.push(id), addEventListener() {}
    };
    const document = {
        getElementById: id => elements[id] ??= { style: {}, classList: { toggle() {} }, focus() {} },
        querySelector: () => ({ focus() {} }),
        addEventListener: (name, handler) => { listeners[name] = handler; }
    };
    vm.runInNewContext(source, { window, document, fetch, URL, AbortSignal });
    return { window, listeners, elements, navigations, modals };
}

test('100% continues to the selected booking URL and keeps date/time parameters', async () => {
    const app = setup(async () => ({ ok: true, json: async () => ({ verified: true }) }));
    const url = 'https://clinic.test/book-appointment?date=2026-10-12&time=09%3A00';
    await app.window.verifyPersonalInformationBeforeBooking(url);
    assert.deepEqual(app.navigations, [url]);
    assert.deepEqual(app.modals, []);
});

test('maintenance blocks both navigation and final submission, then permits retry after disabling', async () => {
    let maintenance = true;
    let submissions = 0;
    const app = setup(async () => ({ ok: !maintenance, json: async () => maintenance
        ? { code: 'booking_maintenance', verified: false }
        : { verified: true } }));
    await app.window.verifyPersonalInformationBeforeBooking('/book-appointment');
    await app.window.verifyPersonalInformationBeforeBooking(() => { submissions++; });
    assert.deepEqual(app.navigations, []);
    assert.equal(submissions, 0);
    assert.deepEqual(app.modals, ['bookingMaintenanceModal', 'bookingMaintenanceModal']);
    maintenance = false;
    await app.window.verifyPersonalInformationBeforeBooking(() => { submissions++; });
    assert.equal(submissions, 1);
    assert.equal(app.modals.length, 2);
});

test('incomplete status displays the completion modal and stops navigation', async () => {
    const app = setup(async () => ({ ok: false, json: async () => ({
        code: 'personal_information_incomplete', title: 'Complete Your Personal Information', message: 'Complete GUISIS first.'
    }) }));
    await app.window.verifyPersonalInformationBeforeBooking('/book-appointment');
    assert.deepEqual(app.navigations, []);
    assert.deepEqual(app.modals, ['personalInformationVerificationModal']);
    assert.equal(app.elements.personalInformationVerificationTitle.textContent, 'Incomplete information');
    assert.equal(app.elements.personalInformationVerificationClose.textContent, 'Close');
    assert.match(template, /href="https:\/\/www\.guisis\.dllbsit2027\.com\/" target="_blank" rel="noopener noreferrer"/);
    assert.doesNotMatch(template, /style="display: none;" hidden>Go to GUISIS/);
});

for (const [name, fetch] of [
    ['network failure', async () => { throw new Error('offline'); }],
    ['invalid response', async () => ({ ok: true, json: async () => ({}) })],
    ['expired session response', async () => ({ ok: false, json: async () => { throw new Error('HTML'); } })]
]) {
    test(`${name} displays the verification error and stops navigation`, async () => {
        const app = setup(fetch);
        await app.window.verifyPersonalInformationBeforeBooking('/book-appointment');
        assert.deepEqual(app.navigations, []);
        assert.equal(app.elements.personalInformationVerificationTitle.textContent, 'Unable to Verify Information');
        assert.equal(app.elements.personalInformationVerificationClose.textContent, 'Close');
        assert.doesNotMatch(source, /guisisLink\.hidden/);
    });
}

test('clicks on regular, alternate, and reserved booking links are intercepted', () => {
    const app = setup(async () => ({ ok: true, json: async () => ({ verified: true }) }));
    const calls = [];
    app.window.verifyPersonalInformationBeforeBooking = url => calls.push(url);
    for (const path of ['/book-appointment', '/patient/book-appointment', '/patient/appointment/create', '/book-appointment/reserved/42']) {
        let stopped = false;
        const link = { href: `https://clinic.test${path}`, getAttribute: () => null, hasAttribute: () => false };
        app.listeners.click({ button: 0, target: { closest: () => link }, preventDefault() {}, stopImmediatePropagation() { stopped = true; } });
        assert.equal(stopped, true);
    }
    assert.equal(calls.length, 4);
});

test('repeat clicks share the pending verification and clear loading state', async () => {
    let resolve;
    let requests = 0;
    const app = setup(() => { requests++; return new Promise(done => { resolve = done; }); });
    const attributes = {};
    const trigger = { setAttribute: (key, value) => { attributes[key] = value; }, removeAttribute: key => { delete attributes[key]; } };
    const pending = app.window.verifyPersonalInformationBeforeBooking('/book-appointment', trigger);
    await app.window.verifyPersonalInformationBeforeBooking('/book-appointment', trigger);
    assert.equal(requests, 1);
    assert.equal(attributes['aria-busy'], 'true');
    resolve({ ok: true, json: async () => ({ verified: true }) });
    await pending;
    assert.equal(attributes['aria-busy'], undefined);
});

const dashboard = readFileSync(new URL('../../resources/views/patient/index.blade.php', import.meta.url), 'utf8');
const calendarActions = dashboard.slice(dashboard.indexOf('        function scrollToCalendar(event)'),
    dashboard.indexOf('    </script>', dashboard.indexOf('        function scrollToCalendar(event)')));

for (const complete of [false, true]) {
    test(`dashboard calendar action ${complete ? 'scrolls after verification' : 'shows the modal without scrolling when incomplete'}`, async () => {
        const app = setup(async () => ({ ok: complete, json: async () => complete ? { verified: true } : {
            code: 'personal_information_incomplete', title: 'Complete Your Personal Information', message: 'Complete GUISIS first.'
        } }));
        let scrolls = 0;
        const calendar = { scrollIntoView() { scrolls++; }, classList: { add() {}, remove() {} } };
        app.window.setTimeout = () => {};
        const context = vm.createContext({ window: app.window, document: { getElementById: () => calendar } });
        vm.runInContext(calendarActions, context);
        await context.scrollToCalendar({ preventDefault() {} });
        assert.equal(scrolls, complete ? 1 : 0);
        assert.equal(app.modals.length, complete ? 0 : 1);
        assert.deepEqual(app.navigations, []);
    });
}
