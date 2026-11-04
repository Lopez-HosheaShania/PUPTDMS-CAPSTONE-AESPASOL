
export const ODONTOGRAM_TREATMENT_COLORS = Object.freeze({
    D: '#ef4444',
    RC: '#ef4444',
    M: '#111827',
    F: '#2563eb',
    I: '#ef4444',
    RF: '#ef4444',
    MO: '#111827',
    IM: '#111827',
    J: '#2563eb',
    A: '#2563eb',
    AB: '#2563eb',
    P: '#2563eb',
    IN: '#2563eb',
    LC: '#2563eb',
    TP: '#2563eb',
    RM: '#2563eb',
    X: '#2563eb',
    XO: '#2563eb',
    '✓': '#111827',
    CM: '#111827',
    SP: '#111827'
});

export function getOdontogramTreatmentColor(code, fallback = null) {
    const normalized = String(code ?? '')
        .trim()
        .toUpperCase();

    const key = ['PT', '+'].includes(normalized)
        ? '✓'
        : normalized;

    return ODONTOGRAM_TREATMENT_COLORS[key] || fallback || '#111827';
}

window.OdontogramTreatmentColors = ODONTOGRAM_TREATMENT_COLORS;
