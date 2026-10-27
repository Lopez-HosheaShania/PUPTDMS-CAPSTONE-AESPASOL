import { readFileSync } from 'node:fs';
import vm from 'node:vm';
import test from 'node:test';
import assert from 'node:assert/strict';

const template = readFileSync(new URL('../../resources/views/dentist/dentist-odontogram.blade.php', import.meta.url), 'utf8');
const start = template.indexOf('            function getProcedureTime24Hour(field)');
const end = template.indexOf("            applyTreatmentBtn.addEventListener('click'", start);

function setup(startValue = '', endValue = '') {
    const errors = new Map();
    const focused = [];
    let dirty = false;
    const field = value => ({ value, dataset: {}, listeners: {}, setAttribute() {}, addEventListener(name, callback) { this.listeners[name] = callback; } });
    const timeIn = field(startValue);
    const timeProcessed = field(endValue);
    timeIn.id = 'procedure_started_at';
    timeProcessed.id = 'procedure_completed_at';
    const duration = field('');
    const context = vm.createContext({
        procedureTimeInInput: timeIn, procedureTimeProcessedInput: timeProcessed, procedureDurationInput: duration,
        markProcedureDiscardDirty() { dirty = true; },
        window: {
            validateFormInputField(input) { errors.set(input, input.value ? '' : 'Required'); return !!input.value; },
            showFormInputValidationMessage(input, message) { errors.set(input, message); },
            focusGlobalInvalidField(input) { focused.push(input); },
        },
    });
    vm.runInContext(template.slice(start, end), context);
    return { timeIn, timeProcessed, duration, errors, focused, isDirty: () => dirty,
        time24: () => vm.runInContext('[procedureTimeInInput, procedureTimeProcessedInput].map(getProcedureTime24Hour)', context),
        validate: () => vm.runInContext('validateProcedureTimingFields()', context) };
}

test('new workspace leaves both manual values and duration empty', () => {
    const app = setup();
    assert.equal(app.timeIn.value, '');
    assert.equal(app.timeProcessed.value, '');
    assert.equal(app.duration.value, '');
    assert.equal(app.validate(), false);
    assert.equal(app.focused[0], app.timeIn);
});

test('typed hours and minutes calculate duration in the original HH:MM:SS format', () => {
    const app = setup('09:50', '10:20');
    assert.equal(app.duration.value, '00:30:00');
    assert.equal(app.validate(), true);
    assert.equal(app.timeIn.value, '09:50');
    assert.equal(app.timeProcessed.value, '10:20');
});

test('earlier finish shows a field error and retains entered values', () => {
    const app = setup('10:00', '09:00');
    assert.equal(app.validate(), false);
    assert.match(app.errors.get(app.timeProcessed), /on or after Time In/);
    assert.equal(app.timeProcessed.value, '09:00');
});

test('equal times are valid', () => {
    const app = setup('10:00', '10:00');
    assert.equal(app.validate(), true);
    assert.equal(app.duration.value, '00:00:00');
});

test('editing clears old feedback, updates duration, and marks unsaved changes', () => {
    const app = setup('10:00', '09:00');
    app.validate();
    app.timeProcessed.value = '10:20';
    app.timeProcessed.listeners.input();
    assert.equal(app.errors.get(app.timeProcessed), '');
    assert.equal(app.duration.value, '00:20:00');
    assert.equal(app.isDirty(), true);
});

test('time-only duration is independent of the browser timezone', () => {
    const app = setup('01:30', '03:30');
    assert.equal(app.duration.value, '02:00:00');
});

test('malformed typed times and seconds do not produce a duration', () => {
    for (const value of ['24:30', '25:30', '09:60', '09:30:15', 'abc']) {
        const app = setup(value, '11:30');
        assert.equal(app.duration.value, '');
        assert.equal(app.validate(), false);
    }
});

test('Flatpickr change updates duration from the stored 24-hour value', () => {
    const app = setup('11:30', '12:00');
    app.timeProcessed.value = '13:00';
    app.timeProcessed.listeners.change();
    assert.equal(app.duration.value, '01:30:00');
    assert.equal(app.time24()[1], '13:00');
    assert.equal(app.validate(), true);
    assert.equal(app.isDirty(), true);
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

