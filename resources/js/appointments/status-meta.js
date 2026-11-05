const APPOINTMENT_STATUS_META = {
    today: {
        label: 'Today',
        className: 'status-today',
        accentClass: 'accent-today',
        statClass: 's-today',
        icon: 'fa-calendar-day'
    },

    upcoming: {
        label: 'Upcoming',
        className: 'status-upcoming',
        accentClass: 'accent-upcoming',
        statClass: 's-upcoming',
        icon: 'fa-hourglass-half'
    },

    rescheduled: {
        label: 'Rescheduled',
        className: 'status-rescheduled',
        accentClass: 'accent-rescheduled',
        statClass: 's-rescheduled',
        icon: 'fa-rotate'
    },

    completed: {
        label: 'Completed',
        className: 'status-completed',
        accentClass: 'accent-completed',
        statClass: 's-completed',
        icon: 'fa-circle-check'
    },

    cancelled: {
        label: 'Cancelled',
        className: 'status-cancelled',
        accentClass: 'accent-cancelled',
        statClass: 's-cancelled',
        icon: 'fa-circle-xmark'
    },

    default: {
        label: 'Status',
        className: 'status-default',
        accentClass: 'accent-default',
        statClass: 's-default',
        icon: 'fa-circle'
    }
};

function getAppointmentStatusMeta(
    status
) {
    const value =
        String(status || '')
            .toLowerCase()
            .trim()
            .replaceAll(
                ' ',
                '_'
            );

    let key =
        value;

    if (
        value === 'scheduled' ||
        value === 'scheduled_today'
    ) {
        key =
            value ===
                'scheduled_today'
                ? 'today'
                : 'upcoming';

    } else if (
        value.includes(
            'resched'
        )
    ) {
        key =
            'rescheduled';

    } else if (
        value.includes(
            'complete'
        )
    ) {
        key =
            'completed';

    } else if (
        value.includes(
            'cancel'
        )
    ) {
        key =
            'cancelled';

    } else if (
        value.includes(
            'upcoming'
        ) ||
        value.includes(
            'pending'
        ) ||
        value.includes(
            'confirmed'
        )
    ) {
        key =
            'upcoming';
    }

    return (
        APPOINTMENT_STATUS_META[
        key
        ] ||
        APPOINTMENT_STATUS_META
            .default
    );
}

window.APPOINTMENT_STATUS_META = APPOINTMENT_STATUS_META;
window.getAppointmentStatusMeta = getAppointmentStatusMeta;