import '../forms/validation.js';

function initMonthlyReport() {
    const panel = document.querySelector('[data-monthly-report]');
    const form = panel?.closest('form');
    if (!form) return;
    const type = form.querySelector('#reportType');
    const from = form.querySelector('#dateFrom');
    const to = form.querySelector('#dateTo');
    const load = panel.querySelector('[data-monthly-load]');
    const zero = panel.querySelector('[data-monthly-zero]');
    const status = panel.querySelector('[data-monthly-status]');
    const confirm = panel.querySelector('[data-monthly-confirm]');
    const inputs = [...panel.querySelectorAll('[data-monthly-count]')];
    let period = '';
    let controller;
    let revision = 0;
    const isMonthly = () => type.selectedOptions[0]?.dataset.documentType === 'monthly_report';
    const selectedPeriod = () => `${from.value}|${to.value || from.value}`;

    function updateTotals() {
        const total = fields => fields.some(input => input.value === '' || !input.validity.valid)
            ? '—' : fields.reduce((sum, input) => sum + Number(input.value), 0).toLocaleString();
        panel.querySelectorAll('[data-monthly-column-total]').forEach(output => {
            output.textContent = total(inputs.filter(input => input.dataset.column === output.dataset.monthlyColumnTotal));
        });
        panel.querySelectorAll('[data-monthly-row-total]').forEach(output => {
            output.textContent = total(inputs.filter(input => input.dataset.row === output.dataset.monthlyRowTotal && input.dataset.column !== 'actual_patient'));
        });
        panel.querySelector('[data-monthly-grand-total]').textContent = total(inputs.filter(input => input.dataset.column !== 'actual_patient'));
    }

    function enableCounts(enabled) {
        inputs.forEach(input => {
            input.disabled = !enabled;
            input.required = enabled;
            input.closest('[data-global-number-stepper]').querySelectorAll('button').forEach(button => { button.disabled = !enabled; });
        });
        confirm.disabled = !enabled;
        confirm.required = enabled;
        zero.disabled = !enabled;
    }

    function invalidate() {
        revision++;
        controller?.abort();
        period = '';
        confirm.checked = false;
        inputs.forEach(input => {
            input.value = '';
            window.clearFormInputValidation?.(input);
        });
        window.clearFormInputValidation?.(confirm);
        enableCounts(false);
        load.disabled = !from.value;
        load.textContent = 'Load counts for selected dates';
        status.textContent = 'Select dates and load counts. Changing dates clears the previous counts.';
        updateTotals();
    }

    function syncType() {
        const active = isMonthly();
        panel.hidden = !active;
        panel.classList.toggle('hidden', !active);
        form.closest('.ui-modal-card')?.classList.toggle('modal-xl', active);
        form.closest('.ui-modal-card')?.classList.toggle('modal-lg', !active);
        invalidate();
    }

    load.addEventListener('click', async () => {
        if (!window.validateFormInputField(from) || !window.validateFormInputField(to)) return;
        if (to.value && to.value < from.value) {
            window.showFormInputValidationMessage(to, 'End date must be on or after the start date.');
            return;
        }
        invalidate();
        const requestRevision = revision;
        controller = new AbortController();
        load.disabled = true;
        load.textContent = 'Loading counts…';
        status.textContent = 'Loading completed visits…';
        try {
            const csrf = document.querySelector('meta[name="csrf-token"]')?.content;
            const response = await fetch(panel.dataset.previewUrl, {
                method: 'POST', credentials: 'same-origin', signal: controller.signal,
                headers: { 'Content-Type': 'application/json', 'X-CSRF-TOKEN': csrf, Accept: 'application/json' },
                body: JSON.stringify({ document_template_id: type.value, date_from: from.value, date_to: to.value || null }),
            });
            const data = await response.json();
            if (!response.ok) throw new Error(Object.values(data.errors || {}).flat()[0] || data.message || 'Unable to load counts.');
            if (requestRevision !== revision || !isMonthly()) return;
            period = data.counts_period;
            inputs.forEach(input => { input.value = data.counts[input.dataset.row][input.dataset.column] ?? ''; });
            enableCounts(true);
            status.textContent = `${data.completed_visits} completed visit(s) loaded. Blank means no reliable count is available. Tooth suggestions may include existing treatment; review them before confirming.`
                + (data.unclassified_visits ? ` ${data.unclassified_visits} visit(s) have a patient classification outside these four groups; review and assign their counts manually.` : '');
            updateTotals();
        } catch (error) {
            if (error.name !== 'AbortError' && requestRevision === revision) status.textContent = error.message;
        } finally {
            if (requestRevision === revision) {
                load.disabled = false;
                load.textContent = 'Reload counts (replaces edits)';
            }
        }
    });

    zero.addEventListener('click', () => {
        inputs.filter(input => input.value === '').forEach(input => {
            input.value = '0';
            window.clearFormInputValidation?.(input);
        });
        confirm.checked = false;
        updateTotals();
    });
    inputs.forEach(input => input.addEventListener('input', () => {
        confirm.checked = false;
        window.clearFormInputValidation?.(confirm);
        updateTotals();
    }));
    type.addEventListener('change', syncType);
    for (const field of [from, to]) {
        field.addEventListener('change', invalidate);
        field.addEventListener('input', invalidate);
    }
    form.addEventListener('reset', () => setTimeout(syncType, 0));
    window.MonthlyDentalReportForm = {
        ready: () => Boolean(period && period === selectedPeriod() && isMonthly()),
        appendTo(data) {
            if (!this.ready()) throw new Error('Load the monthly counts again for the selected dates.');
            inputs.forEach(input => data.append(input.name, input.value));
            data.append('counts_period', period);
            data.append('counts_confirmed', confirm.checked ? '1' : '0');
        },
    };
    window.initGlobalNumberSteppers?.(panel);
    syncType();
}

if (document.readyState === 'loading') document.addEventListener('DOMContentLoaded', initMonthlyReport, { once: true });
else initMonthlyReport();
