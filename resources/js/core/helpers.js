function getCsrfToken() {
    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
}

function escapeHtml(value = '') {
    return String(value)
        .replaceAll('&', '&amp;')
        .replaceAll('<', '&lt;')
        .replaceAll('>', '&gt;')
        .replaceAll('"', '&quot;')
        .replaceAll("'", '&#039;');
}

function debounce(callback, wait = 250) {
    let timeout;

    return function (...args) {
        clearTimeout(timeout);
        timeout = setTimeout(() => callback.apply(this, args), wait);
    };
}

async function requestJson(url, options = {}) {
    const response = await fetch(url, {
        headers: {
            'Accept': 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': getCsrfToken(),
            ...(options.headers || {})
        },
        ...options
    });

    const data = await response.json().catch(() => null);

    if (!response.ok) {
        const error = new Error('Request failed');
        error.response = response;
        error.data = data;
        throw error;
    }

    return data;
}

function formatDateForInput(date) {
    const y = date.getFullYear();
    const m = String(date.getMonth() + 1).padStart(2, "0");
    const d = String(date.getDate()).padStart(2, "0");
    return `${y}-${m}-${d}`;
}

function setQuickDateRange(days, fromId, toId) {
    const to = new Date();
    const from = new Date();

    from.setDate(to.getDate() - Number(days));

    const fromInput = document.getElementById(fromId);
    const toInput = document.getElementById(toId);

    if (fromInput) fromInput.value = formatDateForInput(from);
    if (toInput) toInput.value = formatDateForInput(to);
}

function bindQuickDatePresets({
    groupId = "datePresetGroup",
    fromId,
    toId,
    onChange
}) {
    const group = document.getElementById(groupId);
    if (!group) return;

    group.addEventListener("click", function (e) {
        const btn = e.target.closest(".quick-date-chip");
        if (!btn) return;

        group.querySelectorAll(".quick-date-chip").forEach(b => {
            b.classList.remove("active");
        });

        btn.classList.add("active");

        setQuickDateRange(btn.getAttribute("data-range"), fromId, toId);

        if (typeof onChange === "function") onChange();
    });

    [fromId, toId].forEach(id => {
        const input = document.getElementById(id);
        if (!input) return;

        ["input", "change"].forEach(evt => {
            input.addEventListener(evt, function () {
                group.querySelectorAll(".quick-date-chip").forEach(b => {
                    b.classList.remove("active");
                });

                if (typeof onChange === "function") onChange();
            });
        });
    });
}

window.getCsrfToken = getCsrfToken;
window.escapeHtml = escapeHtml;
window.debounce = debounce;
window.requestJson = requestJson;
window.formatDateForInput = formatDateForInput;
window.setQuickDateRange = setQuickDateRange;
window.bindQuickDatePresets = bindQuickDatePresets;