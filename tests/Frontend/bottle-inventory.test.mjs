import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const view = readFileSync(new URL('../../resources/views/shared/inventory.blade.php', import.meta.url), 'utf8');
const start = view.indexOf('        var editBottleOriginal = null;');
const source = view.slice(start, view.indexOf('    </script>', start));

function setup() {
    const elements = new Map();
    const get = id => {
        if (!elements.has(id)) elements.set(id, { value: '', textContent: '', style: {}, dataset: {}, removeAttribute() {}, setAttribute() {}, addEventListener() {}, closest() { return this; } });
        return elements.get(id);
    };
    const document = { getElementById: get, addEventListener() {} };
    const context = vm.createContext({ document });
    vm.runInContext(source, context);
    return { context, get };
}

test('bottle capacity appears only for Bottle and ordinary consumed fields keep existing behavior', () => {
    const app = setup();
    app.get('addUnit').value = 'Bottle';
    app.get('addQty').value = '3';
    app.get('addBottleCapacity').value = '100';
    app.context.computeAddBalance();
    assert.equal(app.get('addBottleFields').hidden, false);
    assert.equal(app.get('addBottleCapacity').required, true);
    assert.equal(app.get('addUsed').disabled, false);
    assert.equal(app.get('addUsedLabel').textContent, 'Consumed (mL)');
    assert.equal(app.get('addUsed').step, '0.01');
    assert.equal(app.get('addBalance').value, '3 bottles | 300 mL total');
    app.get('addUnit').value = 'Piece';
    app.context.computeAddBalance();
    app.get('addUsed').value = '1';
    app.context.computeAddBalance();
    assert.equal(app.get('addBottleFields').hidden, true);
    assert.equal(app.get('addBottleCapacity').disabled, true);
    assert.equal(app.get('addUsed').disabled, false);
    assert.equal(app.get('addBalance').value, 2);
});

for (const [remaining, consumed, expected] of [
    ['100', '0.5', '99.5'], ['0.5', '0.5', '100'], ['20', '25', '95'], ['50', '170', '80']
]) {
    test(`consuming ${consumed} mL from ${remaining} mL previews the active bottle correctly`, () => {
        const app = setup();
        app.context.editBottleOriginal = { is_volume_bottle: true, qty: 3, current_bottle_remaining_ml: remaining };
        app.get('editUnit').value = 'Bottle';
        app.get('editQty').value = '3';
        app.get('editBottleCapacity').value = '100';
        app.get('editUsed').value = consumed;
        app.context.computeEditBalance();
        assert.equal(app.get('editBottleRemaining').value, expected);
        assert.equal(app.get('editBottleCapacity').readOnly, true);
        assert.equal(app.get('editUnit').readOnly, true);
    });
}

test('overconsumption is shown as an error rather than a negative remaining volume', () => {
    const app = setup();
    app.context.editBottleOriginal = { is_volume_bottle: true, qty: 1, current_bottle_remaining_ml: '25' };
    app.get('editUnit').value = 'Bottle';
    app.get('editQty').value = '1';
    app.get('editBottleCapacity').value = '100';
    app.get('editUsed').value = '30';
    app.context.computeEditBalance();
    assert.equal(app.get('editBottleRemaining').value, 'Unavailable');
    assert.equal(app.get('editBalance').value, 'Consumption exceeds available volume');
});

test('all inline inventory scripts parse after Blade variables are rendered', () => {
    for (const match of view.matchAll(/<script>([\s\S]*?)<\/script>/g)) {
        const rendered = match[1].replace(/@json\([^\n]*\)/g, 'null')
            .replace(/@if[^\n]*|@endif|@else/g, '');
        new vm.Script(rendered);
    }
});
