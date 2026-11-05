import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const template = readFileSync(new URL('../../resources/views/shared/assign-cms-access.blade.php', import.meta.url), 'utf8');
const script = template.slice(template.indexOf('            function renderNoResults('), template.indexOf('            function toggleUserSearchClear('));

function setup(responses) {
    function element() {
        return {
            children: [], listeners: {}, textContent: '',
            set innerHTML(value) { this.children = []; },
            appendChild(child) { this.children.push(child); },
            append(...children) { this.children.push(...children); },
            addEventListener(name, handler) { this.listeners[name] = handler; },
        };
    }
    let requests = 0;
    const resultsBox = element();
    const context = vm.createContext({
        resultsBox, searchInput: { value: '' }, fullUserList: [], fullListLoaded: false, usersFetchPromise: null,
        document: { createElement: element }, URLSearchParams,
        console: { error() {} }, showResults() {}, fillUser() {},
        rankUsers(users, query) { return users.filter(user => !query || user.full_name.includes(query)); },
        async fetch() {
            const response = responses[requests++];
            if (response instanceof Error) throw response;
            return { ok: true, async json() { return { success: true, data: response }; } };
        },
    });
    vm.runInContext(script, context);
    return { resultsBox, context, open: () => vm.runInContext('openUserSearch()', context), requests: () => requests };
}

test('connection failure shows retry and a successful retry replaces the error with users', async () => {
    const app = setup([new Error('CMS unavailable'), [{ full_name: 'CMS Patient', email: 'cms@example.test' }]]);
    await app.open();
    assert.match(app.resultsBox.children[0].textContent, /Unable to load CMS users/);
    const retry = app.resultsBox.children[1];
    assert.equal(retry.textContent, 'Retry');
    retry.listeners.click({ preventDefault() {} });
    await new Promise(setImmediate);
    assert.equal(app.requests(), 2);
    assert.equal(app.resultsBox.children[0].children[0].textContent, 'CMS Patient');
});

test('a successful empty directory shows no users without an error or retry', async () => {
    const app = setup([[]]);
    await app.open();
    assert.equal(app.resultsBox.children.length, 1);
    assert.equal(app.resultsBox.children[0].textContent, 'No users available.');
});

test('concurrent focus and click share a request and the successful list is reused', async () => {
    const app = setup([[{ full_name: 'CMS Patient' }]]);
    await Promise.all([app.open(), app.open()]);
    await app.open();
    assert.equal(app.requests(), 1);
});
