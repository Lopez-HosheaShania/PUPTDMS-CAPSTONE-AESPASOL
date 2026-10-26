import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const template = readFileSync(new URL('../../resources/views/dentist/dentist-odontogram.blade.php', import.meta.url), 'utf8');
const start = template.indexOf('            function getProcedureTime24Hour(field)');
const end = template.indexOf("            applyTreatmentBtn.addEventListener('click'", start);

function setup(startValue = '', durationValue = '') {
    const errors = new Map();
    const focused = [];
    let dirty = false;
    const field = value => ({ value, listeners: {}, addEventListener(name, callback) { this.listeners[name] = callback; } });
    const timeIn = field(startValue);
    const duration = field(durationValue);
    const context = vm.createContext({
        procedureTimeInInput: timeIn, procedureDurationInput: duration,
        markProcedureDiscardDirty() { dirty = true; },
        window: {
            validateFormInputField(input) { errors.set(input, input.value ? '' : 'Required'); return !!input.value; },
            showFormInputValidationMessage(input, message) { errors.set(input, message); },
            focusGlobalInvalidField(input) { focused.push(input); },
        },
    });
    vm.runInContext(template.slice(start, end), context);
    return { timeIn, duration, errors, focused, isDirty: () => dirty,
        seconds: () => vm.runInContext('parseProcedureDurationSeconds()', context),
        validate: () => vm.runInContext('validateProcedureTimingFields()', context) };
}

test('new workspace requires a start time and duration', () => {
    const app = setup();
    assert.equal(app.validate(), false);
    assert.equal(app.focused[0], app.timeIn);
    const missingDuration = setup('12:00');
    assert.equal(missingDuration.validate(), false);
    assert.equal(missingDuration.focused[0], missingDuration.duration);
});

test('entered duration is retained exactly including seconds and zero', () => {
    for (const [value, seconds] of [['00:10:00', 600], ['01:02:03', 3723], ['00:00:00', 0], ['100:00:00', 360000]]) {
        const app = setup('12:00', value);
        assert.equal(app.duration.value, value);
        assert.equal(app.seconds(), seconds);
        assert.equal(app.validate(), true);
    }
});

test('malformed durations are rejected without changing the entered value', () => {
    for (const value of ['-01:00:00', '00:60:00', '00:00:60', '1:30', 'abc']) {
        const app = setup('12:00', value);
        assert.equal(app.validate(), false);
        assert.equal(app.focused[0], app.duration);
        assert.equal(app.duration.value, value);
    }
});

test('Flatpickr changes and duration edits mark unsaved changes without overwriting duration', () => {
    const app = setup('11:30', '00:30:00');
    app.timeIn.value = '23:30';
    app.timeIn.listeners.change();
    assert.equal(app.duration.value, '00:30:00');
    assert.equal(app.isDirty(), true);
    app.duration.value = '01:30:00';
    app.duration.listeners.input();
    assert.equal(app.seconds(), 5400);
    assert.equal(app.validate(), true);
});

test('validation focuses the visible Flatpickr input', () => {
    const app = setup();
    const classes = new Set();
    const visible = { classList: {
        toggle(name, enabled) { enabled ? classes.add(name) : classes.delete(name); },
        remove(name) { classes.delete(name); },
    } };
    app.timeIn._flatpickr = { altInput: visible };
    assert.equal(app.validate(), false);
    assert.equal(app.focused[0], visible);
    assert.equal(classes.has('is-invalid'), true);
    app.timeIn.value = '00:30';
    app.timeIn.listeners.change();
    assert.equal(classes.has('is-invalid'), false);
});

test('duration fills seconds first, then minutes, then hours while typing', () => {
    const app = setup('12:00');
    for (const [digit, expected] of [['1', '00:00:01'], ['2', '00:00:12'], ['3', '00:01:23'], ['4', '00:12:34'], ['5', '01:23:45'], ['6', '12:34:56']]) {
        app.duration.value += digit;
        app.duration.listeners.input();
        assert.equal(app.duration.value, expected);
    }
    assert.equal(app.isDirty(), true);
});

test('pasting a formatted duration retains its value and places the caret at the end', () => {
    const app = setup('12:00', '01:30:45');
    app.duration.selectionStart = 5;
    app.duration.setSelectionRange = (start, end) => {
        assert.equal(start, 8);
        assert.equal(end, 8);
    };
    app.duration.listeners.input();
    assert.equal(app.duration.value, '01:30:45');
    assert.equal(app.seconds(), 5445);
});

test('backspace shifts digits right and clearing restores the placeholder', () => {
    const app = setup('12:00', '00:01:23');
    app.duration.value = app.duration.value.slice(0, -1);
    app.duration.listeners.input();
    assert.equal(app.duration.value, '00:00:12');
    app.duration.value = '';
    app.duration.listeners.input();
    assert.equal(app.duration.value, '');
    assert.equal(app.validate(), false);
    app.duration.value = 'ab1c23';
    app.duration.listeners.input();
    assert.equal(app.duration.value, '00:01:23');
});

test('repeated backspace restores the placeholder after the last duration digit', () => {
    const app = setup('12:00', '00:01:23');
    for (const expected of ['00:00:12', '00:00:01', '']) {
        app.duration.value = app.duration.value.slice(0, -1);
        app.duration.listeners.input({ inputType: 'deleteContentBackward' });
        assert.equal(app.duration.value, expected);
    }
    app.duration.listeners.change();
    assert.equal(app.duration.value, '');
    assert.equal(app.validate(), false);
});

test('deleting a zero duration restores the placeholder but typing zero is retained', () => {
    const app = setup('12:00', '00:00:00');
    app.duration.value = '00:00:0';
    app.duration.listeners.input({ inputType: 'deleteContentForward' });
    assert.equal(app.duration.value, '');
    app.duration.value = '0';
    app.duration.listeners.input({ inputType: 'insertText' });
    assert.equal(app.duration.value, '00:00:00');
});
