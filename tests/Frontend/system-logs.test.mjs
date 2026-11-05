import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const template = readFileSync(new URL('../../resources/views/admin/system-logs.blade.php', import.meta.url), 'utf8');
const escapeFunction = template.slice(template.indexOf('            function escapeSlHtml('), template.indexOf('            function formatSlDetailLabel('));
const renderFunction = template.slice(template.indexOf('            function slRenderRows('), template.indexOf('            function slRenderPagebar('));

function render(log) {
    const table = { innerHTML: '' };
    const grid = { innerHTML: '' };
    const context = vm.createContext({
        document: { getElementById: id => id === 'slTableBody' ? table : grid },
        window: {}, restoreSlCurrentView() {}, showEmptyState() {},
    });
    vm.runInContext(escapeFunction + renderFunction, context);
    context.logs = [log];
    vm.runInContext('slRenderRows(logs)', context);
    return [table.innerHTML, grid.innerHTML];
}

test('both views retain useful short multiline details without CSP-blocked inline styles', () => {
    for (const html of render({ id: 1, description: 'Changed a setting.', full_description: 'Before: 1\nAfter: 2' })) {
        assert.match(html, /Before: 1\nAfter: 2/);
        assert.match(html, /data-show-more-panel/);
        assert.doesNotMatch(html, /\sstyle=/);
    }
});

test('both views escape untrusted actor names and detail text', () => {
    for (const html of render({ id: 2, actor_name: '<script>alert(1)</script>', description: 'Updated a record.', full_description: '<img src=x onerror=alert(1)>' })) {
        assert.doesNotMatch(html, /<script>|<img/);
        assert.match(html, /&lt;script&gt;/);
        assert.match(html, /&lt;img/);
    }
});

test('both views avoid duplicate disclosures when detail and summary are the same', () => {
    for (const html of render({ id: 3, description: 'Viewed system logs.', full_description: 'Viewed system logs.' })) {
        assert.doesNotMatch(html, /data-show-more-panel/);
    }
});
