<script>
    (() => {
        function makeCalendarDot(colorClass, text = '') {
            const sizeClass = text ? 'min-w-[16px] h-4 px-1 text-[9px] font-bold' : 'w-4 h-4 text-[9px]';
            return `
                <span class="calendar-badge absolute -top-1 -right-1 ${sizeClass} rounded-full ${colorClass} leading-none flex items-center justify-center border">
                    ${text}
                </span>
            `;
        }

        function makeCalendarIconBadge(colorClass, iconClass) {
            return `
                <span class="calendar-badge absolute -top-1 -right-1 w-4 h-4 rounded-full ${colorClass}
                    text-[10px] leading-none flex items-center justify-center border">
                    <i class="${iconClass} text-[8px]"></i>
                </span>
            `;
        }

        function makeTodayBadge() {
            return makeCalendarIconBadge(
                'calendar-badge-today',
                'fa-solid fa-calendar-day'
            );
        }

        function makeFullyBookedBadge() {
            return makeCalendarIconBadge(
                'calendar-badge-full',
                'fa-solid fa-user-check'
            );
        }

        function makeHolidayStar() {
            return `
            <span class="calendar-badge calendar-badge-holiday absolute -top-1 -right-1 w-4 h-4 rounded-full text-[10px] leading-none
        flex items-center justify-center border">
                <i class="fa-solid fa-star text-[8px]"></i>
            </span>
        `;
        }

        function makeWorkingHolidayBadge() {
            return `
                <span
                    class="
                        calendar-badge
                        calendar-badge-working-holiday
                        absolute -top-1 -right-1
                        w-4 h-4 rounded-full
                        text-[10px] leading-none
                        flex items-center justify-center
                        border
                    "
                    aria-hidden="true"
                >
                    <i class="fa-solid fa-briefcase text-[8px]"></i>
                </span>
            `;
        }

        function makeClinicClosedBadge() {
            return `
                <span class="calendar-badge calendar-badge-closed absolute -top-1 -right-1 w-4 h-4 rounded-full text-[10px] leading-none
                    flex items-center justify-center border">
                    <i class="fa-solid fa-minus text-[8px]"></i>
                </span>
            `;
        }

        function makeCompletedAppointmentBadge() {
            return `
            <span
                class="completed-appointment-badge"
                aria-hidden="true"
            >
                <i class="fa-solid fa-check"></i>
            </span>
        `;
        }

        function makeMyAppointmentBadge(isFollowUp = false) {
            const iconClass = isFollowUp ?
                'fa-solid fa-calendar-plus' :
                'fa-solid fa-calendar-check';

            return `
            <span class="calendar-badge calendar-badge-appointment absolute -top-1 -right-1 min-w-[16px] h-4 px-1 rounded-full text-[9px] leading-none
        flex items-center justify-center border">
                <i class="${iconClass} text-[8px]"></i>
            </span>
        `;
        }

        const calendarConfig = {
            mode: @json($mode ?? 'booking'),
            calendarContainerId: @json($calendarContainerId ?? 'calendarSkeletonContainer'),
            calGridId: @json($calGridId ?? 'calGrid'),
            calMonthLabelId: @json($calMonthLabelId ?? 'calMonthLabel'),
            calYearLabelId: @json($calYearLabelId ?? 'calYearLabel'),
            dateInputId: @json($dateInputId),
            timeInputId: @json($timeInputId),
            dateBannerId: @json($dateBannerId ?? 'dateBanner'),
            workingHolidayNoticeId: @json($workingHolidayNoticeId ?? null),
            slotPlaceholderId: @json($slotPlaceholderId ?? 'slotPlaceholder'),
            slotContainerId: @json($slotContainerId ?? 'slotContainer'),
            slotGridId: @json($slotGridId ?? 'slotGrid'),
            selectedSlotDisplayId: @json($selectedSlotDisplayId ?? 'selectedSlotDisplay'),
            selectedSlotTextId: @json($selectedSlotTextId ?? 'selectedSlotText'),
            selectedTimePillId: @json($selectedTimePillId ?? 'selectedTimePill'),
            selectedTimeTextId: @json($selectedTimeTextId ?? 'selectedTimeText'),
            datePillId: @json($datePillId ?? 'datePill'),
            dateErrorId: @json($dateErrorId ?? 'dateError'),
            timeErrorId: @json($timeErrorId ?? 'timeError'),
            clearSlotButtonId: @json($clearSlotButtonId ?? 'clearSlotSelectionBtn'),
            calendarWrapSelector: @json($calendarWrapSelector ?? '.cal-wrap'),
            slotsWrapSelector: @json($slotsWrapSelector ?? '.slots-wrap'),
            slotEndpoint: @json($slotEndpoint),
            bookingUrl: @json($bookingUrl ?? null),
            maxFutureMonths: @json($maxFutureMonths ?? 6),
            historyMonths: @json($historyMonths ?? 12),
            appointmentHistoryUrl: @json($appointmentHistoryUrl ?? null),

            scheduleRules: @json($scheduleRules ?? []),
            blockedDates: @json($blockedDates ?? []),
            apptCounts: @json($appointmentCountsPerDay ?? []),
            appointmentDetails: @json($appointmentDetails ?? []),
            holidaysMap: @json($philippineHolidays ?? []),
            personalAppointments: @json($personalAppointments ?? []),
            completedAppointments: @json($completedAppointments ?? []),
            disallowToday: @json($disallowToday ?? true),
            allowPastDates: @json($allowPastDates ?? false),
            allowAllDates: @json($allowAllDates ?? false),
            allowAllDatesExceptHolidays: @json($allowAllDatesExceptHolidays ?? false),
            disableWeekends: @json($disableWeekends ?? false),
            allowHolidaySelection: @json($allowHolidaySelection ?? false),
            allowToggleOffDate: @json($allowToggleOffDate ?? true),
            useDynamicScheduleRules: @json($useDynamicScheduleRules ?? false),
            renderStyle: @json($renderStyle ?? 'patient'),
            enableMonthYearShortcut: @json($enableMonthYearShortcut ?? false),
        };

        const calendarInstanceKey = calendarConfig.calendarContainerId;

        window.__appointmentCalendars = window.__appointmentCalendars || {};

        let selectedDate = null;
        let selectedTime = null;
        let activeCalendarFilter = 'all';
        let focusedDateIso = null;
        let hasCalendarRenderedOnce = false;
        let dashboardLoadingTimer = null;
        const dashboardSlotCache = new Map();
        let sharedCalendarSource = null;

        function ensureSharedCalendarSource() {
            if (
                !sharedCalendarSource &&
                typeof window.createCalendarSource ===
                    'function'
            ) {
                sharedCalendarSource =
                    window.createCalendarSource(
                        calendarConfig
                    );

                if (
                    calendarConfig.dateInputId
                ) {
                    window.__appCalendarSources =
                        window.__appCalendarSources ||
                        {};

                    window.__appCalendarSources[
                        calendarConfig.dateInputId
                    ] =
                        sharedCalendarSource;
                }
            }

            return sharedCalendarSource;
        }

        window.__appCalendarSources = window.__appCalendarSources || {};

        ensureSharedCalendarSource();

        const todayDate = new Date();
        todayDate.setHours(0, 0, 0, 0);

        function pad(n) {
            return String(n).padStart(2, "0");
        }

        function getDayAbbrFromDate(dateObj) {
            return dateObj.toLocaleDateString('en-US', {
                weekday: 'short'
            }).replace('.', '');
        }

        function normalizeDays(days) {
            if (Array.isArray(days)) return days;

            if (typeof days === "string") {
                try {
                    const parsed = JSON.parse(days);
                    if (Array.isArray(parsed)) return parsed;
                } catch (e) {
                    return days.split(",").map(d => d.trim());
                }
            }

            return [];
        }

        function isRuleActive(rule) {
            return (
                rule?.is_active === true ||
                rule?.is_active === 1 ||
                rule?.is_active === '1'
            );
        }

        function getRuleForDate(dateObj) {
            const source =
                ensureSharedCalendarSource();

            if (source) {
                return source.getRuleForDate(
                    dateObj
                );
            }

            if (
                !calendarConfig
                    .useDynamicScheduleRules
            ) {
                return null;
            }

            const dayAbbr =
                getDayAbbrFromDate(
                    dateObj
                );

            return (
                calendarConfig.scheduleRules ||
                []
            ).find(rule => {
                const days =
                    normalizeDays(
                        rule.days
                    );

                return (
                    isRuleActive(rule) &&
                    days.includes(dayAbbr)
                );
            }) || null;
        }

        function getMaxPerDay(dateObj) {
            const source =
                ensureSharedCalendarSource();

            if (source) {
                return source.getMaxPerDay(
                    dateObj
                );
            }

            const rule =
                getRuleForDate(
                    dateObj
                );

            return rule?.max_slots ?? 0;
        }

        function getHolidayRecord(iso) {
            const source =
                ensureSharedCalendarSource();

            if (source) {
                return source.getHoliday(
                    iso
                );
            }

            return calendarConfig
                .holidaysMap?.[iso] ||
                null;
        }

        function getHolidayName(iso) {
            if (
                sharedCalendarSource
                    ?.getHolidayName
            ) {
                return sharedCalendarSource
                    .getHolidayName(iso);
            }

            const holiday =
                getHolidayRecord(iso);

            if (!holiday) {
                return null;
            }

            if (
                typeof holiday ===
                'string'
            ) {
                return holiday;
            }

            return String(
                holiday.name ||
                'Philippine Holiday'
            );
        }

        function holidayBlocksBooking(iso) {
            const source =
                ensureSharedCalendarSource();

            if (
                source?.isHolidayBlocked
            ) {
                return source
                    .isHolidayBlocked(
                        iso
                    );
            }

            const holiday =
                getHolidayRecord(iso);

            if (!holiday) {
                return false;
            }

            if (
                typeof holiday ===
                'string'
            ) {
                return true;
            }

            if (
                typeof holiday
                    .is_blocked_for_booking ===
                'boolean'
            ) {
                return holiday
                    .is_blocked_for_booking;
            }

            if (
                holiday.type ===
                    'special_working' ||
                holiday.is_working_day ===
                    true
            ) {
                return false;
            }

            if (
                holiday.type === 'islamic' &&
                holiday.eid_confirmed === false
            ) {
                return false;
            }

            return true;
        }

        function syncWorkingHolidayNotice(
            iso = null
        ) {
            if (
                !calendarConfig
                    .workingHolidayNoticeId
            ) {
                return;
            }

            const notice =
                document.getElementById(
                    calendarConfig
                        .workingHolidayNoticeId
                );

            if (!notice) {
                return;
            }

            const holiday =
                iso ?
                    getHolidayRecord(iso) :
                    null;

            const isWorkingHoliday =
                !!holiday &&
                !holidayBlocksBooking(iso);

            notice.classList.toggle(
                'hidden',
                !isWorkingHoliday
            );

            notice.setAttribute(
                'aria-hidden',
                isWorkingHoliday ?
                    'false' :
                    'true'
            );
        }

        function isDateSchedulable(dateObj, iso) {
            const source = ensureSharedCalendarSource();

            if (source) {
                return source
                    .isDateSchedulable(
                        dateObj,
                        iso
                    );
            }

            if (calendarConfig.blockedDates.includes(iso)) {
                return false;
            }

            if (holidayBlocksBooking(iso)) {
                return false;
            }

            if (!calendarConfig.useDynamicScheduleRules) {
                return true;
            }

            const rule = getRuleForDate(dateObj);
            const status = String(rule?.status || '').trim().toLowerCase();

            if (
                !rule ||
                !isRuleActive(rule) ||
                status === 'closed'
            ) {
                return false;
            }

            return true;
        }

        async function fetchSlotsForDate(iso) {
            const response = await fetch(`${calendarConfig.slotEndpoint}?date=${encodeURIComponent(iso)}`, {
                headers: {
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                }
            });

            if (!response.ok) {
                throw new Error('Failed to load slots.');
            }

            return response.json();
        }

        function getSelectedDateValue() {
            const dateInput = document.getElementById(calendarConfig.dateInputId);
            const inputValue = String(dateInput?.value || '').trim();

            if (inputValue !== '') {
                return inputValue;
            }

            return String(selectedDate || '').trim();
        }

        function hasSelectedDateValue() {
            return getSelectedDateValue() !== '';
        }

        function getAppointmentSlotPeriod(timeValue = '') {
            const value =
                String(timeValue)
                    .trim()
                    .toUpperCase();

            if (
                /\bAM\b/.test(value)
            ) {
                return 'am';
            }

            if (
                /\bPM\b/.test(value)
            ) {
                return 'pm';
            }

            const hour =
                Number.parseInt(
                    value.split(':')[0],
                    10
                );

            if (
                Number.isFinite(hour)
            ) {
                return hour < 12 ?
                    'am' :
                    'pm';
            }

            return 'am';
        }

        function getLegendItemsForMode(mode) {
            if (calendarConfig.allowAllDatesExceptHolidays) {
                return calendarConfig
                .disableWeekends
                ? [
                    'today',
                    'workingHoliday',
                    'holiday',
                    'clinicClosed'
                ]
                : [
                    'today',
                    'workingHoliday',
                    'holiday'
                ];
            }

            if (mode === 'dentist' || mode === 'dentist-dashboard') {
                return ['today', 'hasPatients', 'fullyBooked', 'workingHoliday', 'holiday','clinicClosed'];
            }

            if (mode === 'patient-dashboard') {
                return [
                    'myAppointment',
                    'completedAppointment',
                    'today',
                    'fullyBooked',
                    'workingHoliday',
                    'holiday',
                    'clinicClosed'
                ];
            }

            if (mode === 'patient-appointment') {
                return [
                    'myAppointment',
                    'today',
                    'fullyBooked',
                    'workingHoliday',
                    'holiday',
                    'clinicClosed'
                ];
            }

            return [
                'today',
                'hasPatients',
                'fullyBooked',
                'workingHoliday',
                'holiday',
                'clinicClosed'
            ];
        }

        function renderUnifiedCalendarLegend(mode) {
            const items = getLegendItemsForMode(mode);

            return `
                <div class="cal-legend mt-4">
                    ${items.map(key => `
                        <div class="cal-legend-item">
                            ${CALENDAR_THEME.statuses[key].legendIcon}
                        </div>
                    `).join("")}
                </div>
            `;
        }

        const CALENDAR_THEME = {
            statuses: {
                myAppointment: {
                    key: "myAppointment",
                    label: "My Appointment",
                    dotClass: "calendar-badge-appointment",
                    tooltipClass: "calendar-tooltip-appointment",
                    legendIcon: `
            <span class="cal-pill cal-pill-blue">
                <i class="fa-solid fa-calendar-check text-[10px]"></i>
                My Appointment
            </span>
        `,
                    badge: (isFollowUp = false) =>
                        makeMyAppointmentBadge(isFollowUp),
                },
                completedAppointment: {
                    key: "completedAppointment",
                    label: "Completed Visit",
                    tooltipClass: "calendar-tooltip-completed",

                    legendIcon: `
            <span class="cal-pill cal-pill-green">
                <i class="fa-solid fa-circle-check text-[10px]"></i>
                Completed Visit
            </span>
        `,

                    badge: () => makeCompletedAppointmentBadge(),
                },
                today: {
                    key: "today",
                    label: "Today",
                    dotClass: "calendar-badge-today",
                    tooltipClass: "calendar-tooltip-today",

                    legendIcon: `
                        <span class="cal-pill cal-pill-maroon">
                            <i class="fa-solid fa-calendar-day text-[10px]"></i>
                            Today
                        </span>
                    `,

                    badge: () => makeTodayBadge(),
                },

                hasPatients: {
                    key: "hasPatients",
                    label: "Has Patients",
                    dotClass: "calendar-badge-completed",
                    tooltipClass: "calendar-tooltip-completed",

                    legendIcon: `
                        <span class="cal-pill cal-pill-green">
                            <span
                                class="
                                    calendar-badge
                                    calendar-badge-completed
                                    inline-flex
                                    min-w-[16px]
                                    h-4
                                    px-1
                                    rounded-full
                                    items-center
                                    justify-center
                                    border
                                    text-[9px]
                                    font-bold
                                    leading-none">
                                1
                            </span>
                            Has Patients
                        </span>
                    `,
                },

                fullyBooked: {
                    key: "fullyBooked",
                    label: "Fully Booked",
                    dotClass: "calendar-badge-full",
                    tooltipClass: "calendar-tooltip-full",

                    legendIcon: `
                        <span class="cal-pill cal-pill-red">
                            <i class="fa-solid fa-user-check text-[10px]"></i>
                            Fully Booked
                        </span>
                    `,

                    badge: () => makeFullyBookedBadge(),
                },
                workingHoliday: {
                    key: "workingHoliday",
                    label: "Working Holiday · Regular Schedule",
                    tooltipClass:
                        "calendar-tooltip-working-holiday",

                    legendIcon: `
                        <span class="cal-pill cal-pill-working-holiday">
                            <i class="fa-solid fa-briefcase text-[10px]"></i>
                            Working Holiday · Regular Schedule
                        </span>
                    `,

                    badge: () =>
                        makeWorkingHolidayBadge(),
                },
                holiday: {
                    key: "holiday",
                    label: "Non-Working Holiday · Clinic Closed",
                    tooltipClass:
                        "calendar-tooltip-holiday",

                    legendIcon: `
                        <span class="cal-pill cal-pill-yellow">
                            <i class="fa-solid fa-star text-[10px]"></i>
                            Non-Working Holiday · Clinic Closed
                        </span>
                    `,

                    badge: () =>
                        makeHolidayStar(),
                },
                clinicClosed: {
                    key: "clinicClosed",
                    label: "Clinic Closed",
                    dotClass: "calendar-badge-closed",
                    tooltipClass: "calendar-tooltip-closed",
                    legendIcon: `
                        <span class="cal-pill cal-pill-gray">
                            <i class="fa-solid fa-circle-minus text-[10px]"></i>
                            Unavailable
                        </span>
                    `,
                    badge: () => makeClinicClosedBadge(),
                },
                todayNotAvailable: {
                    key: "todayNotAvailable",
                    label: "Today not available",
                    dotClass: "calendar-badge-closed",
                    tooltipClass: "calendar-tooltip-neutral",

                    legendIcon: `
            <span class="cal-pill cal-pill-gray">
                <i class="fa-solid fa-circle-minus text-[10px]"></i>
                Today not available
            </span>
        `,
                },
            }
        };

        function resolveCalendarDayState(year, month, day) {
            const iso = `${year}-${pad(month + 1)}-${pad(day)}`;
            const cellDate = new Date(year, month, day);
            cellDate.setHours(0, 0, 0, 0);

            const isToday =
                cellDate.getTime() ===
                todayDate.getTime();

            const isPast = cellDate < todayDate;

            const dayOfWeek = cellDate.getDay();

            const isWeekend =
                dayOfWeek === 0 ||
                dayOfWeek === 6;

            const isPastOrToday = calendarConfig.allowPastDates ?
                (calendarConfig.disallowToday ? isToday : false) :
                (calendarConfig.disallowToday ? cellDate <= todayDate : isPast);

            const holiday = getHolidayRecord(iso);
            const holidayName = getHolidayName(iso);
            const isHoliday = !!holiday;
            const isHolidayBlocked = holidayBlocksBooking(iso);

            const isClosed =
                !isDateSchedulable(
                    cellDate,
                    iso
                );

            const maxPerDay = calendarConfig.useDynamicScheduleRules ? getMaxPerDay(cellDate) : 0;
            const count = calendarConfig.apptCounts?.[iso] ?? 0;
            const isFull = !isClosed && maxPerDay > 0 ? count >= maxPerDay : false;

            const myAppointment = calendarConfig.personalAppointments?.[iso] || null;
            const completedAppointments =
                calendarConfig.completedAppointments?.[iso] || [];

            const hasCompletedAppointment =
                Array.isArray(completedAppointments) &&
                completedAppointments.length > 0;
            const hasPatients = count > 0;

            const isBookingMode = calendarConfig.mode === 'booking';

            let isDisabled;

            if (
                calendarConfig
                    .allowAllDatesExceptHolidays
            ) {
                const holidayBlocked =
                    isHolidayBlocked &&
                    !calendarConfig
                        .allowHolidaySelection;

                const weekendBlocked =
                    calendarConfig
                        .disableWeekends ===
                    true &&
                    isWeekend;

                isDisabled =
                    holidayBlocked ||
                    weekendBlocked;

            } else if (
                calendarConfig.allowAllDates
            ) {
                isDisabled = false;
            } else {
                isDisabled =
                    isPastOrToday ||
                    (
                        isHolidayBlocked &&
                        !calendarConfig
                            .allowHolidaySelection
                    ) ||
                    isClosed ||
                    isFull;
            }

            if (
                calendarConfig.mode ===
                'dentist-dashboard'
            ) {
                isDisabled =
                    isHolidayBlocked ||
                    isClosed;
            }

            const isSelected = iso === selectedDate;

            return {
                iso,
                cellDate,
                isToday,
                isPast,
                isWeekend,
                isPastOrToday,
                holiday,
                holidayName,
                isHolidayBlocked,
                isHoliday,
                isClosed,
                isFull,
                myAppointment,
                completedAppointments,
                hasCompletedAppointment,
                hasPatients,
                count,
                isBookingMode,
                isDisabled,
                isSelected
            };
        }

        function resetDashboardAvailabilityPanel() {
            const panel = getDashboardAvailabilityPanel();

            if (!panel) return;

            panel.innerHTML = `
                <div class="dashboard-calendar-side-empty">
                    <div class="dashboard-calendar-side-empty-icon">
                        <i class="fa-solid fa-calendar-check"></i>
                    </div>

                    <span class="dashboard-calendar-eyebrow">
                        Check availability
                    </span>

                    <strong>Select an available date</strong>

                    <p>
                        Choose a future date from the calendar to view available
                        appointment times.
                    </p>
                </div>
            `;
        }

        function getCalendarDayDecorations(state, variant = 'patient') {
            let cellClass = "cal-cell";
            const allowAllDates =
                calendarConfig.allowAllDates === true;

            const allowAllDatesExceptHolidays =
                calendarConfig
                    .allowAllDatesExceptHolidays === true;

            const disableWeekends =
                calendarConfig
                    .disableWeekends ===
                true;

            const ignoreAvailabilityRestrictions =
                allowAllDates ||
                allowAllDatesExceptHolidays;

            const isDentistDashboard =
                calendarConfig.mode ===
                'dentist-dashboard';

            let badgeHtml = "";
            let tooltipHtml = "";
            let tooltip = "";
            let tooltipClass = "calendar-tooltip-neutral";

            if (isDentistDashboard) {

                if (state.isToday) {
                    cellClass +=
                        " today";

                } else if (
                    state.isHoliday &&
                    !allowAllDates
                ) {
                    cellClass +=
                        state.isHolidayBlocked
                            ? " holiday holiday-closed"
                            : " holiday holiday-working";

                    if (
                        state.isHolidayBlocked
                    ) {
                        cellClass +=
                            " disabled";
                    }

                } else if (
                    state.isFull
                ) {
                    cellClass +=
                        " full";

                } else if (
                    state.hasPatients
                ) {
                    cellClass +=
                        " has-patients font-bold";

                } else if (
                    state.isClosed
                ) {
                    cellClass +=
                        " clinic-closed disabled";
                }

            } else if (
                variant !== 'dentist'
            ) {
                if (
                    state.myAppointment &&
                    state.isSelected
                ) {
                    cellClass +=
                        " selected";

                } else if (
                    state.myAppointment
                ) {
                    cellClass +=
                        " my-appointment";

                } else if (
                    state.isSelected &&
                    state.hasCompletedAppointment
                ) {
                    cellClass +=
                        " completed-appointment selected-history";

                } else if (
                    state.hasCompletedAppointment
                ) {
                    cellClass +=
                        " completed-appointment";

                } else if (
                    state.isSelected &&
                    !state.isDisabled
                ) {
                    cellClass +=
                        " selected";

                } else if (
                    state.isHoliday &&
                    !allowAllDates
                ) {
                    cellClass +=
                        state.isHolidayBlocked
                            ? " holiday holiday-closed"
                            : " holiday holiday-working";

                    if (
                        state.isHolidayBlocked &&
                        !calendarConfig
                            .allowHolidaySelection
                    ) {
                        cellClass +=
                            " disabled";
                    }

                } else if (
                    allowAllDatesExceptHolidays &&
                    disableWeekends &&
                    state.isWeekend
                ) {
                    cellClass +=
                        " clinic-closed disabled";

                } else if (
                    !ignoreAvailabilityRestrictions &&
                    state.isToday
                ) {
                    if (
                        state.isBookingMode &&
                        calendarConfig.disallowToday
                    ) {
                        cellClass +=
                            " same-day-unavailable disabled";
                    } else {
                        cellClass +=
                            " today";
                    }

                } else if (
                    !ignoreAvailabilityRestrictions &&
                    state.isFull
                ) {
                    cellClass +=
                        " full disabled";

                } else if (
                    !ignoreAvailabilityRestrictions &&
                    state.isClosed
                ) {
                    cellClass +=
                        " clinic-closed disabled";

                } else if (
                    !ignoreAvailabilityRestrictions &&
                    state.isPastOrToday &&
                    state.isBookingMode
                ) {
                    cellClass +=
                        " past-date disabled";

                } else if (
                    !ignoreAvailabilityRestrictions &&
                    state.isPast &&
                    !state.hasCompletedAppointment
                ) {
                    cellClass +=
                        " past-date disabled";
                }

            } else {

                if (
                    state.isSelected &&
                    !state.isDisabled
                ) {
                    cellClass +=
                        " selected";

                } else if (
                    !allowAllDates &&
                    state.isToday
                ) {
                    cellClass +=
                        " today disabled";

                } else if (
                    state.isHoliday &&
                    !allowAllDates
                ) {
                    cellClass +=
                        state.isHolidayBlocked
                            ? " holiday holiday-closed"
                            : " holiday holiday-working";

                    if (
                        state.isHolidayBlocked &&
                        !calendarConfig
                            .allowHolidaySelection
                    ) {
                        cellClass +=
                            " disabled";
                    }

                } else if (
                    !allowAllDates &&
                    state.isFull
                ) {
                    cellClass +=
                        " full disabled";

                } else if (
                    !allowAllDates &&
                    (
                        state.isClosed ||
                        state.isPastOrToday
                    )
                ) {
                    cellClass +=
                        " clinic-closed disabled";
                }
            }

            if (state.myAppointment && !state.isBookingMode) {
                badgeHtml += CALENDAR_THEME.statuses.myAppointment.badge(
                    String(state.myAppointment)
                        .toLowerCase()
                        .includes('follow-up')
                );

                const isFollowUp =
                    String(state.myAppointment)
                        .toLowerCase()
                        .includes('follow-up');

                const appointmentIcon = isFollowUp ?
                    'fa-solid fa-calendar-plus' :
                    'fa-solid fa-calendar-check';

                tooltip = `
        <i class="${appointmentIcon} mr-1"></i>
        ${state.myAppointment}
    `;

                tooltipClass =
                    CALENDAR_THEME.statuses.myAppointment.tooltipClass;
            }

            if (
                state.hasCompletedAppointment &&
                calendarConfig.mode === 'patient-dashboard'
            ) {
                badgeHtml +=
                    CALENDAR_THEME.statuses.completedAppointment.badge();

                if (!state.myAppointment) {
                    const firstVisit =
                        state.completedAppointments[0];

                    tooltip = `
                <i class="fa-solid fa-circle-check mr-1"></i>
                Completed: ${firstVisit?.service || 'Dental Visit'}
            `;

                    tooltipClass =
                        CALENDAR_THEME.statuses.completedAppointment.tooltipClass;
                }
            }

            if (state.isHoliday && !allowAllDates) {
                const holidayTheme =
                    state.isHolidayBlocked
                        ? CALENDAR_THEME
                            .statuses
                            .holiday
                        : CALENDAR_THEME
                            .statuses
                            .workingHoliday;

                badgeHtml +=
                    holidayTheme.badge();

                if (!tooltip) {
                    if (
                        state.isHolidayBlocked
                    ) {
                        tooltip = `
                            <strong>
                                <i class="fa-solid fa-star"></i>
                                ${state.holidayName}
                            </strong>

                            <span>
                                Non-Working Holiday · Clinic closed.
                            </span>
                        `;
                    } else {
                        tooltip = `
                            <strong>
                                <i class="fa-solid fa-briefcase"></i>
                                ${state.holidayName}
                            </strong>

                            <span>
                                Working Holiday · Regular schedule applies
                            </span>
                        `;
                    }

                    tooltipClass =
                        holidayTheme
                            .tooltipClass;
                }
            } else if (
                !ignoreAvailabilityRestrictions &&
                state.isFull &&
                !state.isToday
            ) {
                if (!state.myAppointment && !state.isClosed) {
                    badgeHtml += variant === 'dentist'
                        ? CALENDAR_THEME.statuses.fullyBooked.badge()
                        : makeCalendarDot(
                            CALENDAR_THEME.statuses.fullyBooked.dotClass
                        );
                }

                if (!tooltip) {
                    tooltip = state.isBookingMode
                        ? "Full Slot"
                        : "Fully Booked";

                    tooltipClass =
                        CALENDAR_THEME.statuses.fullyBooked.tooltipClass;
                }

            } else if (
                variant === 'dentist' &&
                state.hasPatients &&
                !state.isToday &&
                (
                    !state.isPast ||
                    isDentistDashboard
                ) &&
                !state.isHoliday
            ) {
                badgeHtml += makeCalendarDot(
                    CALENDAR_THEME.statuses.hasPatients.dotClass,
                    state.count > 0 ? String(state.count) : ''
                );

                cellClass += " has-patients font-bold";

                if (!tooltip) {
                    tooltip =
                        `${state.count} Appointment${state.count > 1 ? 's' : ''}`;

                    tooltipClass =
                        CALENDAR_THEME.statuses.hasPatients.tooltipClass;
                }

            } else if (
                !ignoreAvailabilityRestrictions &&
                state.isClosed &&
                !state.isPast &&
                !state.isToday
            ) {
                if (!state.myAppointment) {
                    badgeHtml +=
                        CALENDAR_THEME.statuses.clinicClosed.badge();
                }

                if (!tooltip) {
                    tooltip = `
                        <i class="fa-solid fa-circle-minus mr-1"></i>
                        Clinic Closed
                    `;

                    tooltipClass =
                        CALENDAR_THEME.statuses.clinicClosed.tooltipClass;
                }

            } else if (
                !ignoreAvailabilityRestrictions &&
                state.isPast &&
                !state.hasCompletedAppointment
            ) {
                if (!tooltip) {
                    tooltip = `
                        <i class="fa-solid fa-clock-rotate-left mr-1"></i>
                        Past date
                    `;

                    tooltipClass = "calendar-tooltip-neutral";
                }
            }

            if (
                !state.myAppointment &&
                allowAllDatesExceptHolidays &&
                disableWeekends &&
                state.isWeekend
            ) {
                tooltip = `
            <i class="fa-solid fa-circle-minus mr-1"></i>
            Clinic closed on weekends
        `;

                tooltipClass =
                    "calendar-tooltip-neutral";

            } else if (
                state.isHoliday &&
                !allowAllDates
            ) { } else if (
                !state.myAppointment &&
                !ignoreAvailabilityRestrictions &&
                state.isToday &&
                calendarConfig.disallowToday
            ) {
                tooltip = `
            <i class="fa-solid fa-calendar-day mr-1"></i>
            Same-day booking is not allowed
        `;

                tooltipClass =
                    "calendar-tooltip-neutral";

            } else if (
                !state.myAppointment &&
                !ignoreAvailabilityRestrictions &&
                state.isPast &&
                !state.hasCompletedAppointment
            ) {
                tooltip = `
            <i class="fa-solid fa-clock-rotate-left mr-1"></i>
            Past date — booking not allowed
        `;

                tooltipClass =
                    "calendar-tooltip-neutral";

            } else if (
                !state.myAppointment &&
                !ignoreAvailabilityRestrictions &&
                state.isClosed
            ) {
                tooltip = `
            <i class="fa-solid fa-circle-minus mr-1"></i>
            Clinic closed on this date
        `;

                tooltipClass =
                    "calendar-tooltip-neutral";

            } else if (
                state.isToday &&
                !tooltip &&
                !state.myAppointment
            ) {
                tooltip = `
            <i class="fa-solid fa-calendar-day mr-1"></i>
            Today
        `;

                tooltipClass =
                    CALENDAR_THEME
                        .statuses
                        .today
                        .tooltipClass;
            }

            if (
                state.isToday &&
                !state.isHoliday &&
                !state.myAppointment &&
                !state.hasCompletedAppointment
            ) {
                badgeHtml = CALENDAR_THEME.statuses.today.badge();

                tooltip = `
                    <i class="fa-solid fa-calendar-day mr-1"></i>
                    Today
                `;

                tooltipClass =
                    CALENDAR_THEME.statuses.today.tooltipClass;
            }

            if (tooltip) {
                const day = state.cellDate.getDay();
                const tooltipSide = day >= 5 ? "tooltip-left" : day <= 1 ? "tooltip-right" : "tooltip-center";

                tooltipHtml = `
                    <div
                        class="
                            day-smart-tooltip
                            ${tooltipSide}
                            absolute
                            bottom-[calc(100%+10px)]
                            z-[9999]
                            pointer-events-none">
                        <div
                            class="
                                calendar-tooltip
                                ${tooltipClass}
                                relative
                                text-[0.65rem]
                                px-3
                                py-2.5
                                rounded-lg
                                shadow-xl
                                after:content-['']
                                after:absolute
                                after:top-full
                                after:border-4
                                after:border-transparent">
                            ${tooltip}
                        </div>
                    </div>
                `;
            }

            return {
                cellClass,
                badgeHtml,
                tooltipHtml
            };
        }

        function renderCalendarLoading() {
            const container = document.getElementById(calendarConfig.calendarContainerId);
            if (!container) return;

            const dayHeaderSkeleton = Array.from({
                length: 7
            }).map(() =>
                '<div class="h-4 skeleton-line rounded mx-2"></div>'
            ).join("");

            const dayCellSkeleton = Array.from({
                length: 35
            }).map(() =>
                '<div class="flex items-center justify-center py-1.5">' +
                '<div class="w-10 h-10 rounded-xl skeleton-line"></div>' +
                '</div>'
            ).join("");

            container.innerHTML =
                '<div class="skeleton-shell space-y-5 p-5 sm:p-6">' +
                '<div class="flex items-center justify-between mb-5">' +
                '<div class="w-8 h-8 rounded-full skeleton-block"></div>' +
                '<div class="text-center space-y-2">' +
                '<div class="h-5 w-28 skeleton-block rounded mx-auto"></div>' +
                '<div class="h-3 w-16 skeleton-line rounded mx-auto"></div>' +
                '</div>' +
                '<div class="w-8 h-8 rounded-full skeleton-block"></div>' +
                '</div>' +

                '<div class="border-t border-gray-100 mb-3"></div>' +

                '<div class="grid grid-cols-7 gap-0.5 mb-2">' +
                dayHeaderSkeleton +
                '</div>' +

                '<div class="grid grid-cols-7 gap-1">' +
                dayCellSkeleton +
                '</div>' +
                '</div>';
        }

        function renderSlotLoading(iso) {
            syncWorkingHolidayNotice(iso);

            const slotPlaceholder = document.getElementById(calendarConfig.slotPlaceholderId);
            const slotContainer = document.getElementById(calendarConfig.slotContainerId);
            const slotGrid = document.getElementById(calendarConfig.slotGridId);
            const banner = document.getElementById(calendarConfig.dateBannerId);
            const pill = document.getElementById(calendarConfig.datePillId);

            const [y, m, d] = iso.split("-");
            const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

            if (banner && calendarConfig.renderStyle !== 'dentist') {
                banner.innerHTML =
                    `<i class="fa-regular fa-calendar mr-2"></i>${MONTHS[parseInt(m) - 1]} ${parseInt(d)}, ${y}`;
                banner.classList.remove("hidden");
                banner.style.display = "block";
            }

            if (pill) {
                pill.innerHTML =
                    `<i class="fa-regular fa-calendar mr-1"></i>${MONTHS[parseInt(m) - 1]} ${parseInt(d)}, ${y}`;
                pill.classList.add("show");
            }

            if (slotPlaceholder) {
                slotPlaceholder.classList.add("hidden");
                slotPlaceholder.style.display = "none";
            }

            if (slotContainer) {
                slotContainer.classList.remove("hidden");
                slotContainer.style.display = "block";
            }

            if (slotGrid) {
                slotGrid.style.display =
                    'grid';

                slotGrid.className =
                    'appointment-slot-grid slot-grid-ui';

                slotGrid.innerHTML = `
                    <div class="appointment-slot-period">
                        <div class="appointment-slot-period-heading">
                            AM
                        </div>

                        <div class="appointment-slot-period-grid">
                            ${Array.from({
                    length: 4
                }).map(() => `
                                <div class="px-4 py-3 rounded-xl border border-gray-100 bg-gray-50">
                                    <div class="h-4 w-20 skeleton-block rounded"></div>
                                </div>
                            `).join('')}
                        </div>
                    </div>

                    <div class="appointment-slot-period">
                        <div class="appointment-slot-period-heading">
                            PM
                        </div>

                        <div class="appointment-slot-period-grid">
                            ${Array.from({
                    length: 4
                }).map(() => `
                                <div class="px-4 py-3 rounded-xl border border-gray-100 bg-gray-50">
                                    <div class="h-4 w-20 skeleton-block rounded"></div>
                                </div>
                            `).join('')}
                        </div>
                    </div>
                `;
            }
        }

        function isCurrentMonthView(year, month) {
            return year === todayDate.getFullYear() && month === todayDate.getMonth();
        }


        function getMonthBounds() {
            const isDashboard =
                calendarConfig.mode === 'patient-dashboard';

            const minimum = (isDashboard || calendarConfig.allowPastDates) ?
                new Date(
                    todayDate.getFullYear(),
                    todayDate.getMonth() -
                    Number(calendarConfig.historyMonths || 12),
                    1
                ) :
                new Date(
                    todayDate.getFullYear(),
                    todayDate.getMonth(),
                    1
                );

            const maxFutureMonths =
                Number.isFinite(
                    Number(
                        calendarConfig
                            .maxFutureMonths
                    )
                ) ?
                    Number(
                        calendarConfig
                            .maxFutureMonths
                    ) :
                    6;

            const maximum =
                new Date(
                    todayDate.getFullYear(),
                    todayDate.getMonth() +
                    maxFutureMonths,
                    1
                );

            return {
                minimum,
                maximum
            };
        }

        function getVisibleMonthOptions() {
            const {
                minimum,
                maximum
            } = getMonthBounds();

            const options = [];
            const cursor = new Date(maximum);

            while (cursor >= minimum) {
                options.push({
                    year: cursor.getFullYear(),
                    month: cursor.getMonth(),
                    label: cursor.toLocaleDateString(
                        'en-US', {
                        month: 'long',
                        year: 'numeric'
                    }
                    )
                });

                cursor.setMonth(
                    cursor.getMonth() - 1
                );
            }

            return options;
        }

        function getMonthSummary(year, month) {
            const totalDays = new Date(year, month + 1, 0).getDate();
            let available = 0;
            let unavailable = 0;
            let myAppointments = 0;

            for (let day = 1; day <= totalDays; day++) {
                const state = resolveCalendarDayState(year, month, day);

                if (!state.isDisabled) available++;
                if (state.isDisabled && !state.isPast) unavailable++;
                if (state.myAppointment) myAppointments++;
            }

            return {
                available,
                unavailable,
                myAppointments
            };
        }

        function getCalendarDateStateFromIso(iso) {
            const [year, month, day] = String(iso).split('-').map(Number);

            if (!year || !month || !day) return null;

            return resolveCalendarDayState(year, month - 1, day);
        }

        function getDateCellSelector(iso) {
            return `#${calendarConfig.calendarContainerId} [data-date="${iso}"]`;
        }

        function focusCalendarDate(iso) {
            if (!iso) return;

            focusedDateIso = iso;

            requestAnimationFrame(() => {
                const target = document.querySelector(getDateCellSelector(iso));
                target?.focus({
                    preventScroll: true
                });
            });
        }

        function navigateCalendarFocus(currentIso, deltaDays) {
            const state = getCalendarDateStateFromIso(currentIso);
            if (!state) return;

            const candidate = new Date(state.cellDate);
            candidate.setDate(candidate.getDate() + deltaDays);

            const {
                minimum,
                maximum
            } = getMonthBounds();
            const maximumDay = new Date(maximum.getFullYear(), maximum.getMonth() + 1, 0);

            const minimumDay = new Date(
                minimum.getFullYear(),
                minimum.getMonth(),
                1
            );

            if (candidate < minimumDay || candidate > maximumDay) {
                return;
            }

            const nextIso = `${candidate.getFullYear()}-${pad(candidate.getMonth() + 1)}-${pad(candidate.getDate())}`;

            if (
                candidate.getFullYear() !== currentYear ||
                candidate.getMonth() !== currentMonth
            ) {
                currentYear = candidate.getFullYear();
                currentMonth = candidate.getMonth();
                renderCalendar();
            }

            focusCalendarDate(nextIso);
        }

        function applyCalendarFilter(filter = activeCalendarFilter) {
            activeCalendarFilter = filter || 'all';

            const container = document.getElementById(calendarConfig.calendarContainerId);
            if (!container) return;

            container.querySelectorAll('[data-calendar-filter]').forEach(button => {
                const active = button.dataset.calendarFilter === activeCalendarFilter;
                button.classList.toggle('active', active);
                button.setAttribute('aria-pressed', active ? 'true' : 'false');
            });

            container.querySelectorAll('.cal-cell-wrap[data-date-wrap]').forEach(wrapper => {
                const cell = wrapper.querySelector('[data-date]');
                if (!cell) return;

                const state = getCalendarDateStateFromIso(cell.dataset.date);
                let visible = true;

                if (activeCalendarFilter === 'available') {
                    visible = Boolean(state && !state.isDisabled);
                } else if (
                    activeCalendarFilter === 'appointment'
                ) {
                    visible = Boolean(
                        state?.myAppointment ||
                        state?.hasCompletedAppointment
                    );
                }

                wrapper.classList.toggle('calendar-filter-dimmed', !visible);
                wrapper.setAttribute('aria-hidden', visible ? 'false' : 'true');
            });
        }

        async function findEarliestAvailableDate() {
            const button = document.querySelector(
                `#${calendarConfig.calendarContainerId} [data-calendar-filter="earliest"]`
            );

            button?.classList.add('is-loading');
            button?.setAttribute('aria-busy', 'true');

            const {
                minimum,
                maximum
            } = getMonthBounds();
            const endDate = new Date(maximum.getFullYear(), maximum.getMonth() + 1, 0);
            const cursor = new Date(calendarConfig.allowPastDates ? minimum.getTime() : Math.max(todayDate.getTime(),
                minimum.getTime()));

            try {
                while (cursor <= endDate) {
                    const state = resolveCalendarDayState(
                        cursor.getFullYear(),
                        cursor.getMonth(),
                        cursor.getDate()
                    );

                    if (!state.isDisabled) {
                        let payload = dashboardSlotCache.get(state.iso);

                        if (!payload) {
                            payload = await fetchSlotsForDate(state.iso);
                            dashboardSlotCache.set(state.iso, payload);
                        }

                        const slots = Array.isArray(payload?.slots) ? payload.slots : [];
                        const available = slots.some(slot => {
                            if (typeof slot === 'string') return true;

                            return !(
                                slot.is_taken ||
                                slot.taken ||
                                slot.booked ||
                                slot.available === false
                            );
                        });

                        if (available) {
                            currentYear = cursor.getFullYear();
                            currentMonth = cursor.getMonth();
                            selectedDate = state.iso;
                            renderCalendar();
                            await selectDate(state.iso);
                            focusCalendarDate(state.iso);
                            return;
                        }
                    }

                    cursor.setDate(cursor.getDate() + 1);
                }

                if (typeof window.showToast === 'function') {
                    window.showToast({
                        type: 'info',
                        title: 'No available dates',
                        message: 'No open appointment slot was found in the visible booking range.'
                    });
                }
            } catch (_) {
                if (typeof window.showToast === 'function') {
                    window.showToast({
                        type: 'error',
                        title: 'Availability check failed',
                        message: 'Unable to search for the earliest appointment slot.'
                    });
                }
            } finally {
                button?.classList.remove('is-loading');
                button?.removeAttribute('aria-busy');
            }
        }

        function bindCalendarToolbar() {
            const container = document.getElementById(calendarConfig.calendarContainerId);
            if (!container) return;

            const monthPicker = container.querySelector('[data-calendar-month-picker]');
            const monthSelect = container.querySelector('[data-calendar-month-select]');
            const yearSelect = container.querySelector('[data-calendar-year-select]');

            monthPicker?.addEventListener('change', event => {
                const [year, month] = String(event.target.value)
                    .split('-')
                    .map(Number);

                if (!year || Number.isNaN(month)) return;

                clearTimeout(dashboardLoadingTimer);
                dashboardLoadingTimer = null;

                currentYear = year;
                currentMonth = month;
                selectedDate = null;
                focusedDateIso = null;

                renderCalendar();
            });

            function updateCalendarFromSplitSelectors() {
                const selectedMonth = Number(monthSelect?.value);
                const selectedYear = Number(yearSelect?.value);

                if (Number.isNaN(selectedMonth) || Number.isNaN(selectedYear)) return;

                const candidate = new Date(selectedYear, selectedMonth, 1);
                const {
                    minimum,
                    maximum
                } = getMonthBounds();

                if (candidate < minimum || candidate > maximum) {
                    return;
                }

                clearTimeout(dashboardLoadingTimer);
                dashboardLoadingTimer = null;

                currentYear = selectedYear;
                currentMonth = selectedMonth;
                selectedDate = null;
                focusedDateIso = null;

                renderCalendar();
            }

            monthSelect?.addEventListener('change', updateCalendarFromSplitSelectors);
            yearSelect?.addEventListener('change', updateCalendarFromSplitSelectors);

            container.querySelectorAll('[data-calendar-filter]').forEach(button => {
                button.addEventListener('click', async () => {
                    const filter = button.dataset.calendarFilter || 'all';

                    if (filter === 'earliest') {
                        applyCalendarFilter('earliest');
                        await findEarliestAvailableDate();
                        return;
                    }

                    applyCalendarFilter(filter);
                });
            });
        }

        async function initializeRenderedCalendar() {
            const calendarContainer =
                document.getElementById(
                    calendarConfig.calendarContainerId
                );

            if (!calendarContainer) {
                return;
            }

            if (
                typeof window.initCustomSelects ===
                'function'
            ) {
                await window.initCustomSelects(
                    calendarContainer
                );
            }

            const calendarSelectWrappers =
                calendarContainer.querySelectorAll(
                    '.calendar-split-picker .custom-select'
                );

            for (
                const wrapper
                of calendarSelectWrappers
            ) {
                if (
                    typeof window.syncCustomSelect ===
                    'function'
                ) {
                    await window.syncCustomSelect(
                        wrapper
                    );
                }
            }

            bindCalendarClicks(
                `#${calendarConfig.calendarContainerId} [data-date]`
            );

            bindCalendarToolbar();

            calendarContainer
            .querySelectorAll(
                '[data-calendar-nav]'
            )
            .forEach(button => {
                if (
                    button.dataset
                        .calendarNavBound ===
                    'true'
                ) {
                    return;
                }

                button.dataset
                    .calendarNavBound =
                    'true';

                button.addEventListener(
                    'click',
                    () => {
                        changeCalendarMonth(
                            Number(
                                button.dataset
                                    .calendarNav
                            )
                        );
                    }
                );
            });

            if (
                calendarConfig.mode ===
                'dentist-dashboard'
            ) {
                window.PatientUI
                    ?.initAvatars
                    ?.(
                        calendarContainer
                    );
            }

            applyCalendarFilter(
                activeCalendarFilter
            );

            if (focusedDateIso) {
                focusCalendarDate(
                    focusedDateIso
                );
            }
        }

        function renderUnifiedCalendar(year, month) {
            const MONTHS = ["January", "February", "March", "April", "May", "June", "July", "August", "September",
                "October", "November", "December"
            ];
            const DAYS_PATIENT = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];
            const DAYS_DENTIST = ["Su", "Mo", "Tu", "We", "Th", "Fr", "Sa"];

            const isDentist = calendarConfig.renderStyle === 'dentist';
            const isDashboard = calendarConfig.mode === 'patient-dashboard';
            const showMonthYearShortcut = isDashboard || calendarConfig.enableMonthYearShortcut === true;
            const dayLabels = isDentist ? DAYS_DENTIST : DAYS_PATIENT;

            const firstDow = new Date(year, month, 1).getDay();
            const totalDays = new Date(year, month + 1, 0).getDate();
            const summary = getMonthSummary(year, month);

            const {
                minimum,
                maximum
            } = getMonthBounds();
            const currentViewDate = new Date(year, month, 1);
            const prevDisabled = currentViewDate <= minimum;
            const nextDisabled = currentViewDate >= maximum;

            const monthOptions = getVisibleMonthOptions().map(option => `
                <option value="${option.year}-${option.month}"
                    ${option.year === year && option.month === month ? 'selected' : ''}>
                    ${option.label}
                </option>
            `).join('');

            const visibleMonthOptions = getVisibleMonthOptions();
            const visibleYears = [...new Set(visibleMonthOptions.map(option => option.year))];

            const splitMonthOptions = MONTHS.map((label, index) => `
                <option value="${index}" ${index === month ? 'selected' : ''}>
                    ${label}
                </option>
            `).join('');
            const splitYearOptions = visibleYears.map(optionYear => `
                <option value="${optionYear}" ${optionYear === year ? 'selected' : ''}>
                    ${optionYear}
                </option>
            `).join('');

            const header = dayLabels.map((d, i) => `
                <div class="${i === 0 || i === 6 ? 'cal-day-weekend' : 'cal-day-label'} text-center text-[0.6rem] font-bold py-1 pb-2 uppercase tracking-widest">
                    ${d}
                </div>
            `).join("");

            let cells = "";
            for (let i = 0; i < firstDow; i++) cells += `<div aria-hidden="true"></div>`;

            for (let d = 1; d <= totalDays; d++) {
                const state = resolveCalendarDayState(year, month, d);
                const ui = getCalendarDayDecorations(state, isDentist ? 'dentist' : 'patient');
                const isDentistDashboard =
                    calendarConfig.mode ===
                    'dentist-dashboard';

                const dayAppointments =
                    isDentistDashboard &&
                    Array.isArray(
                        calendarConfig
                            .appointmentDetails?.[
                                state.iso
                            ]
                    )
                        ? calendarConfig
                            .appointmentDetails[
                                state.iso
                            ]
                        : [];

                const hasDayAppointments =
                    dayAppointments.length > 0;

                const isHoverDevice =
                    window.matchMedia(
                        '(hover:hover) and (pointer:fine)'
                    ).matches;

                let dashboardHoverHtml = '';

                if (
                    isDentistDashboard &&
                    hasDayAppointments &&
                    !state.isHolidayBlocked &&
                    isHoverDevice &&
                    typeof window.buildDayHoverCard
                        === 'function'
                ) {
                    const column =
                        (
                            firstDow +
                            d -
                            1
                        ) % 7;

                    let alignment =
                        'hover-align-center';

                    if (column <= 1) {
                        alignment =
                            'hover-align-right';
                    } else if (column >= 4) {
                        alignment =
                            'hover-align-left';
                    }

                    const calendarRow =
                        Math.floor(
                            (
                                firstDow +
                                d -
                                1
                            ) / 7
                        );

                    const placement =
                        calendarRow >= 3
                            ? 'hover-top'
                            : 'hover-bottom';

                    dashboardHoverHtml =
                        window.buildDayHoverCard(
                            state.iso,
                            dayAppointments,
                            placement,
                            alignment
                        );
                }

                cells += `
                    <div class="cal-cell-wrap relative flex items-center justify-center group"
                        data-date-wrap
                        data-calendar-state="${state.isDisabled ? 'unavailable' : 'available'}">
                        ${dashboardHoverHtml
                            ? ''
                            : ui.tooltipHtml
                        }

                        ${dashboardHoverHtml}
                        <div class="${ui.cellClass}"
                            data-date="${state.iso}"
                            data-disabled="${state.isDisabled ? 1 : 0}"
                            data-past="${state.isPast ? 1 : 0}"
                            data-my-appointment="${state.myAppointment ? 1 : 0}"
                            data-saturday="${state.cellDate.getDay() === 6 ? 1 : 0}"
                            aria-label="${formatCalendarDateLabel(state.iso)}">
                            <span>${d}</span>
                            ${ui.badgeHtml}
                        </div>
                    </div>
                `;
            }

            const dashboardToolbar = isDashboard ? `
                <div class="dashboard-calendar-toolbar">
                    <div class="dashboard-calendar-summary" aria-live="polite">
                        <span>
                            <i class="fa-solid fa-calendar-check"></i>
                            <strong>${summary.available}</strong> bookable dates
                        </span>

                        ${summary.myAppointments ? `
                            <span>
                                <i class="fa-solid fa-calendar-check"></i>
                                <strong>${summary.myAppointments}</strong> my appointment
                            </span>
                        ` : ''}
                    </div>

                    <div class="dashboard-calendar-filters" aria-label="Calendar filters">
                        <button type="button" data-calendar-filter="all" aria-pressed="true">
                            All
                        </button>

                        <button type="button" data-calendar-filter="available" aria-pressed="false">
                            Available
                        </button>

                        <button type="button" data-calendar-filter="earliest" aria-pressed="false">
                            <i class="fa-solid fa-bolt"></i>
                            Earliest Date
                        </button>

                        <button type="button" data-calendar-filter="appointment" aria-pressed="false">
                            My visits
                        </button>
                    </div>
                </div>
            ` : '';

            const monthControl = showMonthYearShortcut ? `
        <div
            class="calendar-split-picker"
            aria-label="Choose month and year"
        >
            <div class="calendar-split-picker-item">
                <span class="sr-only">
                    Choose month
                </span>

                <select
                    ${isDashboard
                    ? 'data-calendar-month-picker'
                    : 'data-calendar-month-select'
                }
                    class="js-custom-select calendar-month-picker"
                    data-placeholder="Choose month"
                    aria-label="Choose month"
                >
                    ${isDashboard
                    ? monthOptions
                    : splitMonthOptions
                }
                </select>
            </div>

            ${isDashboard ? '' : `
                <div
                    class="
                        calendar-split-picker-item
                        calendar-year-picker-wrap
                    "
                >
                    <span class="sr-only">
                        Choose year
                    </span>

                    <select
                        data-calendar-year-select
                        class="
                            js-custom-select
                            calendar-month-picker
                            calendar-year-picker
                        "
                        data-placeholder="Choose year"
                        aria-label="Choose year"
                    >
                        ${splitYearOptions}
                    </select>
                </div>
            `}
        </div>
    ` : `
                <div class="text-center">
                    <p class="cal-month-label text-base font-extrabold">
                        ${MONTHS[month]}
                    </p>

                    <p class="calendar-year-label text-[0.65rem] font-semibold tracking-widest">
                        ${year}
                    </p>
                </div>
            `;

            const calendarBody = `
                <div class="calendar-main-header">
                    <button
                        type="button"
                        class="cal-nav-btn w-8 h-8 rounded-full border flex items-center justify-center  ${prevDisabled ? 'opacity-40 cursor-not-allowed' : ''}"
                        ${prevDisabled? 'disabled': `data-calendar-nav="-1"`}
                        aria-label="Previous month"
                    >
                        <i class="fa-solid fa-chevron-left"></i>
                    </button>

                    ${monthControl}

                    <button
                        type="button"
                        class="cal-nav-btn w-8 h-8 rounded-full border flex items-center justify-center  ${nextDisabled ? 'opacity-40 cursor-not-allowed' : ''}"
                        ${nextDisabled ? 'disabled' : `data-calendar-nav="1"`}
                        aria-label="Next month"
                    >
                        <i class="fa-solid fa-chevron-right"></i>
                    </button>
                </div>

                ${dashboardToolbar}

                <hr class="calendar-divider mb-3">

                <div
                    class="cal-grid"
                    role="grid"
                    aria-label="${MONTHS[month]} ${year}"
                >
                    ${header}${cells}
                </div>

                ${renderUnifiedCalendarLegend(calendarConfig.mode)}
            `;

            const dashboardSidePanel = isDashboard ? `
                <aside
                    class="dashboard-calendar-side-panel"
                    data-dashboard-availability
                    aria-live="polite"
                >
                    <div class="dashboard-calendar-side-empty">
                        <div class="dashboard-calendar-side-empty-icon">
                            <i class="fa-solid fa-calendar-check"></i>
                        </div>

                        <span class="dashboard-calendar-eyebrow">
                            Check availability
                        </span>

                        <strong>Select an available date</strong>

                        <p>
                            Choose a future date from the calendar to view available
                            appointment times.
                        </p>
                    </div>
                </aside>
            ` : '';

            const markup = isDashboard ?
                `
                    <div class="cal-shell dashboard-calendar-shell">
                        <div class="dashboard-calendar-layout">
                            <div class="dashboard-calendar-main">
                                ${calendarBody}
                            </div>

                            ${dashboardSidePanel}
                        </div>
                    </div>
                ` :
                `
                    <div class="cal-shell">
                        ${calendarBody}
                    </div>
                `;

            const container = document.getElementById(
                calendarConfig.calendarContainerId
            );

            if (!container) return;

            const isInitialAnimatedRender = !hasCalendarRenderedOnce &&
                calendarConfig.mode !== 'booking';

            if (
                isInitialAnimatedRender &&
                typeof window.swapSkeletonContent === 'function'
            ) {
                window.swapSkeletonContent(
                    calendarConfig.calendarContainerId,
                    markup
                );
            } else {
                container.innerHTML = markup;

                container.classList.remove(
                    'skeleton-fade-leave',
                    'skeleton-fade-enter'
                );

                container.style.pointerEvents = '';
            }

            hasCalendarRenderedOnce = true;

            if (isInitialAnimatedRender) {
                let initAttempts = 0;

                const initializeCalendarWhenReady = () => {
                    const customSelect =
                        container.querySelector(
                            'select.js-custom-select'
                        );

                    if (
                        customSelect ||
                        initAttempts >= 10
                    ) {
                        initializeRenderedCalendar();
                        return;
                    }

                    initAttempts++;

                    requestAnimationFrame(
                        initializeCalendarWhenReady
                    );
                };

                requestAnimationFrame(
                    initializeCalendarWhenReady
                );

            } else {
                initializeRenderedCalendar();
            }
        }

        function renderCalendar() {
            renderUnifiedCalendar(currentYear, currentMonth);
        }

        function bindCalendarClicks(selector) {
            const canSelectWithoutInput =
                calendarConfig.mode ===
                    'patient-dashboard' ||
                calendarConfig.mode ===
                    'patient-appointment' ||
                calendarConfig.mode ===
                    'dentist' ||
                calendarConfig.mode ===
                    'dentist-dashboard';

            if (!calendarConfig.dateInputId && !canSelectWithoutInput) {
                return;
            }

            document.querySelectorAll(selector).forEach(el => {
                if (el.dataset.calendarClickBound === 'true') return;

                el.dataset.calendarClickBound = 'true';

                const state = getCalendarDateStateFromIso(el.dataset.date);
                const isDisabled =
                    el.dataset.disabled === '1';

                const isCompletedAppointment =
                    state?.hasCompletedAppointment === true;

                const dayAppointments =
                    calendarConfig.mode ===
                        'dentist-dashboard' &&
                    Array.isArray(
                        calendarConfig
                            .appointmentDetails?.[
                                state?.iso
                            ]
                    )
                        ? calendarConfig
                            .appointmentDetails[
                                state.iso
                            ]
                        : [];

                const hasDashboardAppointments =
                    dayAppointments.length > 0;

                const isInteractive =
                    calendarConfig.mode ===
                        'dentist-dashboard'
                        ? hasDashboardAppointments
                        : (
                            !isDisabled ||
                            Boolean(
                                state?.myAppointment
                            ) ||
                            isCompletedAppointment
                        );

                el.setAttribute(
                    'tabindex',
                    isInteractive ? '0' : '-1'
                );

                el.setAttribute(
                    'role',
                    isInteractive ? 'button' : 'presentation'
                );

                el.setAttribute(
                    'aria-disabled',
                    isInteractive ? 'false' : 'true'
                );

                const activateDate = () => {
                    if (
                            calendarConfig.mode ===
                            'dentist-dashboard'
                        ) {
                            if (
                                !hasDashboardAppointments
                            ) {
                                return;
                            }

                            window.openDayAppointmentsModal?.(
                                state.iso,
                                JSON.stringify(
                                    dayAppointments
                                )
                            );

                            return;
                        }

                    if (state?.myAppointment) {
                        selectedDate = state.iso;
                        focusedDateIso = state.iso;

                        renderCalendar();

                        if (calendarConfig.mode === 'patient-dashboard') {
                            selectDate(state.iso);
                        }

                        return;
                    }

                    if (isCompletedAppointment) {
                        selectedDate = state.iso;
                        focusedDateIso = state.iso;

                        renderCalendar();
                        renderCompletedAppointmentPanel(state);

                        return;
                    }

                    if (isDisabled) return;

                    focusedDateIso = el.dataset.date;
                    selectDate(el.dataset.date);
                };

                el.addEventListener('click', activateDate);

                el.addEventListener('keydown', event => {
                    const keyMap = {
                        ArrowLeft: -1,
                        ArrowRight: 1,
                        ArrowUp: -7,
                        ArrowDown: 7
                    };

                    if (event.key in keyMap) {
                        event.preventDefault();
                        navigateCalendarFocus(el.dataset.date, keyMap[event.key]);
                        return;
                    }

                    if (event.key !== 'Enter' && event.key !== ' ') return;

                    event.preventDefault();
                    activateDate();
                });

                if (isInteractive) {
                    el.addEventListener('focus', () => {
                        focusedDateIso = el.dataset.date;
                    });
                }
            });
        }

        function clearSlotSelectionUI() {
            const dateInput = document.getElementById(calendarConfig.dateInputId);
            const timeInput = document.getElementById(calendarConfig.timeInputId);
            const banner = document.getElementById(calendarConfig.dateBannerId);
            const pill = document.getElementById(calendarConfig.datePillId);
            const slotPlaceholder = document.getElementById(calendarConfig.slotPlaceholderId);
            const slotContainer = document.getElementById(calendarConfig.slotContainerId);
            const slotGrid = document.getElementById(calendarConfig.slotGridId);
            const timePill =
                document.getElementById(
                    calendarConfig.selectedTimePillId
                );

            const timeText =
                document.getElementById(
                    calendarConfig.selectedTimeTextId
                );

            const clearSlotBtn =
                document.getElementById(
                    calendarConfig.clearSlotButtonId
                );

            selectedDate = null;
            selectedTime = null;

            syncWorkingHolidayNotice();

            if (dateInput) dateInput.value = "";
            if (timeInput) timeInput.value = "";

            if (banner) {
                banner.classList.add("hidden");
                banner.style.display = "none";
                banner.innerHTML = "";
            }

            if (pill) {
                pill.classList.remove("show");
                pill.innerHTML = "";
            }

            if (slotContainer) slotContainer.classList.add("hidden");
            if (slotGrid) {
                slotGrid.innerHTML = "";
                slotGrid.style.display = "none";
            }

            if (timePill) {
                timePill.classList.remove("show");
                timePill.classList.add("hidden");
                timePill.style.display = "none";
            }
            if (timeText) timeText.textContent = "";

            if (slotPlaceholder) {
                slotPlaceholder.classList.remove("hidden");
                slotPlaceholder.style.display = "flex";
            }

            if (clearSlotBtn) {
                clearSlotBtn.classList.add('hidden');
                clearSlotBtn.setAttribute('aria-hidden', 'true');
            }

            renderCalendar();
        }

        async function selectDate(iso) {
            if (calendarConfig.mode === 'patient-dashboard') {
                selectedDate = iso;
                selectedTime = null;

                renderCalendar();

                clearTimeout(dashboardLoadingTimer);

                const cachedPayload = dashboardSlotCache.get(iso);

                if (cachedPayload) {
                    renderDashboardAvailability(cachedPayload, iso);
                    return;
                }

                dashboardLoadingTimer = setTimeout(() => {
                    if (selectedDate === iso) {
                        renderDashboardAvailabilityLoading(iso);
                    }
                }, 250);

                try {
                    const payload = await fetchSlotsForDate(iso);

                    dashboardSlotCache.set(iso, payload);
                    clearTimeout(dashboardLoadingTimer);
                    dashboardLoadingTimer = null;

                    if (selectedDate !== iso) return;

                    renderDashboardAvailability(payload, iso);
                } catch (error) {
                    clearTimeout(dashboardLoadingTimer);
                    dashboardLoadingTimer = null;

                    if (selectedDate !== iso) return;

                    renderDashboardAvailability({
                        slots: [],
                        message: 'Unable to load availability for this date.'
                    }, iso);
                }

                return;
            }

            if (calendarConfig.mode === 'patient-appointment') {
                return;
            }

            if (calendarConfig.mode === 'dentist') {
                selectedDate = iso;
                selectedTime = null;
                renderCalendar();
                renderSlotLoading(iso);

                try {
                    const payload = await fetchSlotsForDate(iso);
                    renderSlots(payload, iso);
                } catch (error) {
                    renderSlots({
                        slots: [],
                        message: 'Unable to load available slots.'
                    }, iso);
                }
                return;
            }

            if (calendarConfig.allowToggleOffDate && selectedDate === iso) {
                clearSlotSelectionUI();
                return;
            }

            selectedDate = iso;
            selectedTime = null;

            const dateInput = document.getElementById(calendarConfig.dateInputId);
            const timeInput = document.getElementById(calendarConfig.timeInputId);

            if (dateInput) {
                dateInput.value =
                    iso;

                dateInput.dispatchEvent(
                    new Event(
                        'change', {
                        bubbles: true
                    }
                    )
                );
            }

            if (timeInput) {
                timeInput.value =
                    '';

                timeInput.dispatchEvent(
                    new Event(
                        'change', {
                        bubbles: true
                    }
                    )
                );
            }
            if (typeof markFormDirty === "function") markFormDirty();

            renderCalendar();
            renderSlotLoading(iso);

            try {
                const payload = await fetchSlotsForDate(iso);
                renderSlots(payload, iso);
            } catch (error) {
                renderSlots({
                    slots: [],
                    message: 'Unable to load available slots.'
                }, iso);
            }
        }

        window.__appointmentCalendars[calendarInstanceKey] = {
            ...(window.__appointmentCalendars[
                calendarInstanceKey
            ] || {}),

            selectDate,

            render() {
                renderCalendar();
            },

            renderLoading() {
                renderCalendarLoading();
            },

            setSelectedDate(value = null) {
                selectedDate = value || null;
            },

            setSelectedTime(value = null) {
                selectedTime = value || null;
            },

            getSelectedDate() {
                return selectedDate;
            },

            getSelectedTime() {
                return selectedTime;
            },
        };

        if (
            calendarConfig.dateInputId &&
            !window.selectDate
        ) {
            window.selectDate =
                selectDate;
        }

        function formatCalendarDateLabel(iso) {
            const [year, month, day] = iso.split('-').map(Number);

            return new Date(year, month - 1, day).toLocaleDateString('en-US', {
                weekday: 'long',
                month: 'long',
                day: 'numeric',
                year: 'numeric'
            });
        }

        function getDashboardAvailabilityPanel() {
            const container = document.getElementById(
                calendarConfig.calendarContainerId
            );

            if (!container) return null;

            return container.querySelector(
                '[data-dashboard-availability]'
            );
        }

        function scrollSelectedCalendarDetailsIntoView() {
            if (
                calendarConfig.mode !==
                'patient-dashboard'
            ) {
                return;
            }

            requestAnimationFrame(() => {
                const container =
                    document.getElementById(
                        calendarConfig.calendarContainerId
                    );

                const panel =
                    getDashboardAvailabilityPanel();

                if (!container || !panel) {
                    return;
                }

                const isStackedLayout =
                    window.matchMedia(
                        '(max-width: 1100px)'
                    ).matches;

                if (!isStackedLayout) {
                    return;
                }

                const legend =
                    container.querySelector(
                        '.cal-legend'
                    );

                const target =
                    legend || panel;

                const header =
                    document.querySelector(
                        '.main-header, header'
                    );

                const headerHeight =
                    header?.getBoundingClientRect()
                        .height || 0;

                const extraOffset =
                    window.innerWidth <= 640 ?
                        10 :
                        18;

                const targetTop =
                    target.getBoundingClientRect().top +
                    window.scrollY -
                    headerHeight -
                    extraOffset;

                window.scrollTo({
                    top: Math.max(0, targetTop),
                    behavior: 'smooth'
                });
            });
        }

        function renderCompletedAppointmentPanel(state) {
            const panel = getDashboardAvailabilityPanel();

            if (!panel) return;

            const appointments =
                Array.isArray(state.completedAppointments) ?
                    state.completedAppointments : [];

            if (!appointments.length) {
                resetDashboardAvailabilityPanel();
                return;
            }

            const historyUrl =
                calendarConfig.appointmentHistoryUrl || '#';

            panel.innerHTML = `
            <div class="dashboard-calendar-side-content history-panel">
                <div class="dashboard-calendar-side-top">
                    <div>
                        <span class="dashboard-calendar-eyebrow">
                            Completed visit
                        </span>

                        <strong class="dashboard-calendar-side-date">
                            ${formatCalendarDateLabel(state.iso)}
                        </strong>
                    </div>

                    <span class="dashboard-calendar-status completed">
                        <i class="fa-solid fa-circle-check"></i>
                        Completed
                    </span>
                </div>

                <div class="completed-visit-list">
                    ${appointments.map(appointment => `
                        <article class="completed-visit-card">
                            <div class="completed-visit-heading">
                                <span class="completed-visit-icon">
                                    <i class="fa-solid fa-tooth"></i>
                                </span>

                                <div>
                                    <strong>
                                        ${escapeCalendarText(
                appointment.service ||
                'Dental Appointment'
            )}
                                    </strong>

                                    <span>
                                        <i class="fa-regular fa-clock"></i>
                                        ${escapeCalendarText(
                appointment.time ||
                'Time not recorded'
            )}
                                    </span>
                                </div>
                            </div>

                            <div class="completed-visit-details">
                                <div>
                                    <span>Dentist</span>
                                    <strong>
                                        ${escapeCalendarText(
                appointment.dentist ||
                'Assigned Dentist'
            )}
                                    </strong>
                                </div>

                                ${appointment.duration ? `
                                    <div>
                                        <span>Duration</span>
                                        <strong>
                                            ${escapeCalendarText(
                appointment.duration
            )}
                                        </strong>
                                    </div>
                                ` : ''}
                            </div>

                            ${appointment.remarks ? `
                                <div class="completed-visit-note">
                                    <span>Remarks</span>
                                    <p>
                                        ${escapeCalendarText(
                appointment.remarks
            )}
                                    </p>
                                </div>
                            ` : ''}
                        </article>
                    `).join('')}
                </div>

                <div class="dashboard-calendar-side-footer">
                    <span>
                        This appointment is part of your dental visit history.
                    </span>

                    <a
                        href="${historyUrl}"
                        class="dashboard-calendar-history-btn"
                    >
                        <i class="fa-solid fa-folder-open"></i>
                        View dental records
                    </a>
                </div>
            </div>
        `;

            scrollSelectedCalendarDetailsIntoView();
        }

        function escapeCalendarText(value) {
            return String(value ?? '')
                .replaceAll('&', '&amp;')
                .replaceAll('<', '&lt;')
                .replaceAll('>', '&gt;')
                .replaceAll('"', '&quot;')
                .replaceAll("'", '&#039;');
        }

        function renderDashboardAvailabilityLoading(iso) {
            const panel = getDashboardAvailabilityPanel();

            if (!panel) return;

            panel.innerHTML = `
                <div class="dashboard-calendar-side-content">
                    <div class="dashboard-calendar-side-top">
                        <div>
                            <span class="dashboard-calendar-eyebrow">
                                Checking availability
                            </span>

                            <strong class="dashboard-calendar-side-date">
                                ${formatCalendarDateLabel(iso)}
                            </strong>
                        </div>
                    </div>

                    <div class="dashboard-calendar-side-state loading">
                        <i class="fa-solid fa-spinner fa-spin"></i>

                        <p>
                            Checking available appointment times…
                        </p>
                    </div>
                </div>
            `;
        }

        function renderDashboardAvailability(payload, iso) {
            const panel = getDashboardAvailabilityPanel();

            if (!panel) return;

            dashboardSlotCache.set(iso, payload);

            const slots =
                Array.isArray(payload?.slots)
                    ? payload.slots
                    : [];

            const availableSlots =
                slots.filter(slot => {
                    if (typeof slot === 'string') {
                        return true;
                    }

                    return !(
                        slot.is_taken ||
                        slot.taken ||
                        slot.booked ||
                        slot.available === false
                    );
                });

            const earliestSlot =
                availableSlots.length
                    ? (
                        typeof availableSlots[0] === 'string'
                            ? availableSlots[0]
                            : availableSlots[0]?.time
                    )
                    : null;

            if (!availableSlots.length) {
                panel.innerHTML = `
                    <div class="dashboard-calendar-side-content">

                        <div class="dashboard-calendar-side-top">

                            <div>
                                <span class="dashboard-calendar-eyebrow">
                                    Selected date
                                </span>

                                <strong class="dashboard-calendar-side-date">
                                    ${formatCalendarDateLabel(iso)}
                                </strong>
                            </div>

                            <span class="dashboard-calendar-status unavailable">
                                <i class="fa-solid fa-circle-xmark"></i>
                                No slots
                            </span>

                        </div>

                        <div class="dashboard-calendar-side-state unavailable">

                            <i class="fa-regular fa-calendar-xmark"></i>

                            <p>
                                ${
                                    payload?.message ||
                                    'No available appointment slots for this date.'
                                }
                            </p>

                        </div>

                    </div>
                `;

                scrollSelectedCalendarDetailsIntoView();

                return;
            }

            const defaultFooterText =
                'Select a time to continue';

            panel.innerHTML = `
                <div class="dashboard-calendar-side-content">

                    <div class="dashboard-calendar-side-top">

                        <div>
                            <span class="dashboard-calendar-eyebrow">
                                Selected date
                            </span>

                            <strong class="dashboard-calendar-side-date">
                                ${formatCalendarDateLabel(iso)}
                            </strong>
                        </div>

                        <span class="dashboard-calendar-status available">

                            <i class="fa-solid fa-circle-check"></i>

                            ${availableSlots.length}

                            ${
                                availableSlots.length === 1
                                    ? 'time slot'
                                    : 'time slots'
                            }

                        </span>

                    </div>

                    <div class="dashboard-calendar-side-section">

                        <span class="dashboard-calendar-side-label">
                            Select an available time
                        </span>

                        <p class="text-xs text-gray-500 dark:text-gray-400 mb-3">
                            Tap a time slot to select it.
                        </p>

                        <div class="dashboard-calendar-preview-slots">

                            ${availableSlots.map(slot => {
                                const time =
                                    typeof slot === 'string'
                                        ? slot
                                        : slot.time;

                                const safeTime =
                                    escapeCalendarText(time);

                                return `
                                    <button
                                        type="button"
                                        class="
                                            slot-chip
                                            inline-flex
                                            items-center
                                            gap-2
                                            px-3
                                            py-2
                                            rounded-xl
                                            border
                                            text-xs
                                            font-bold
                                            cursor-pointer
                                        "
                                        data-dashboard-time="${safeTime}"
                                        aria-pressed="false"
                                    >
                                        <i class="fa-regular fa-clock"></i>

                                        <span>
                                            ${safeTime}
                                        </span>
                                    </button>
                                `;
                            }).join('')}

                        </div>

                    </div>

                    <div class="dashboard-calendar-side-footer">

                        <span data-dashboard-selection-label>
                            ${defaultFooterText}
                        </span>

                        <a
                            href="#"
                            class="
                                dashboard-calendar-book-btn
                                opacity-50
                                pointer-events-none
                            "
                            data-dashboard-book-link
                            aria-disabled="true"
                            tabindex="-1"
                        >
                            <i class="fa-solid fa-calendar-plus"></i>

                            Book this date
                        </a>

                    </div>

                </div>
            `;

            const bookLink =
                panel.querySelector(
                    '[data-dashboard-book-link]'
                );

            const selectionLabel =
                panel.querySelector(
                    '[data-dashboard-selection-label]'
                );

            const timeButtons =
                panel.querySelectorAll(
                    '[data-dashboard-time]'
                );

            function disableDashboardBookLink() {
                if (!bookLink) return;

                bookLink.href = '#';

                bookLink.classList.add(
                    'opacity-50',
                    'pointer-events-none'
                );

                bookLink.setAttribute(
                    'aria-disabled',
                    'true'
                );

                bookLink.setAttribute(
                    'tabindex',
                    '-1'
                );
            }

            function enableDashboardBookLink(time) {
                if (
                    !bookLink ||
                    !calendarConfig.bookingUrl
                ) {
                    return;
                }

                const targetUrl =
                    new URL(
                        calendarConfig.bookingUrl,
                        window.location.origin
                    );

                targetUrl.searchParams.set(
                    'date',
                    iso
                );

                targetUrl.searchParams.set(
                    'time',
                    time
                );

                bookLink.href =
                    targetUrl.toString();

                bookLink.classList.remove(
                    'opacity-50',
                    'pointer-events-none'
                );

                bookLink.setAttribute(
                    'aria-disabled',
                    'false'
                );

                bookLink.removeAttribute(
                    'tabindex'
                );
            }

            timeButtons.forEach(button => {
                button.addEventListener(
                    'click',
                    () => {
                        const time =
                            String(
                                button.dataset
                                    .dashboardTime || ''
                            ).trim();

                        if (!time) return;
                        if (selectedTime === time) {
                            selectedTime = null;

                            button.classList.remove(
                                'selected'
                            );

                            button.setAttribute(
                                'aria-pressed',
                                'false'
                            );

                            const buttonIcon =
                                button.querySelector('i');

                            if (buttonIcon) {
                                buttonIcon.className =
                                    'fa-regular fa-clock';
                            }

                            if (selectionLabel) {
                                selectionLabel.textContent =
                                    defaultFooterText;
                            }

                            disableDashboardBookLink();

                            return;
                        }

                        timeButtons.forEach(option => {
                            option.classList.remove(
                                'selected'
                            );

                            option.setAttribute(
                                'aria-pressed',
                                'false'
                            );

                            const optionIcon =
                                option.querySelector('i');

                            if (optionIcon) {
                                optionIcon.className =
                                    'fa-regular fa-clock';
                            }
                        });

                        selectedTime = time;

                        button.classList.add(
                            'selected'
                        );

                        button.setAttribute(
                            'aria-pressed',
                            'true'
                        );

                        const selectedIcon =
                            button.querySelector('i');

                        if (selectedIcon) {
                            selectedIcon.className =
                                'fa-solid fa-circle-check';
                        }

                        if (selectionLabel) {
                            selectionLabel.textContent =
                                `Selected time: ${time}`;
                        }

                        enableDashboardBookLink(
                            time
                        );
                    }
                );
            });

            bookLink?.addEventListener(
                'click',
                event => {
                    if (!selectedTime) {
                        event.preventDefault();
                    }
                }
            );

            scrollSelectedCalendarDetailsIntoView();
        }

        function renderSlots(payload, iso) {
            const slotPlaceholder = document.getElementById(calendarConfig.slotPlaceholderId);
            const slotContainer = document.getElementById(calendarConfig.slotContainerId);
            const slotGrid = document.getElementById(calendarConfig.slotGridId);
            const banner = document.getElementById(calendarConfig.dateBannerId);
            const pill = document.getElementById(calendarConfig.datePillId);
            const timePill = document.getElementById(calendarConfig.selectedTimePillId);
            const timeText =
                document.getElementById(
                    calendarConfig.selectedTimeTextId
                );

            const selectedDisplay =
                document.getElementById(
                    calendarConfig.selectedSlotDisplayId
                );

            const selectedDisplayText =
                document.getElementById(
                    calendarConfig.selectedSlotTextId
                );

            const clearSlotBtn =
                document.getElementById(
                    calendarConfig.clearSlotButtonId
                );

            const slots = payload?.slots || [];
            const remaining = payload?.remaining ?? 0;
            const maxSlots = payload?.max_slots ?? 0;

            if (slotGrid) {
                slotGrid.innerHTML = '';

                slotGrid.style.display =
                    'grid';

                slotGrid.className =
                    'appointment-slot-grid slot-grid-ui';
            }

            if (timePill) {
                timePill.classList.remove("show");
                timePill.classList.add("hidden");
                timePill.style.display = "none";
            }
            if (timeText) timeText.textContent = "";

            selectedTime = null;

            if (selectedDisplayText) {
                selectedDisplayText.textContent =
                    '';
            }

            if (selectedDisplay) {
                selectedDisplay.classList.add(
                    'hidden'
                );

                selectedDisplay.style
                    .removeProperty(
                        'display'
                    );
            }

            if (clearSlotBtn) {
                clearSlotBtn.classList.add(
                    'hidden'
                );

                clearSlotBtn.setAttribute(
                    'aria-hidden',
                    'true'
                );

                clearSlotBtn.style
                    .removeProperty(
                        'display'
                    );
            }

            const [y, m, d] = iso.split("-");
            const MONTHS = ["Jan", "Feb", "Mar", "Apr", "May", "Jun", "Jul", "Aug", "Sep", "Oct", "Nov", "Dec"];

            if (banner) {
                if (calendarConfig.renderStyle === 'dentist') {
                    banner.classList.add("hidden");
                    banner.style.display = "none";
                    banner.innerHTML = "";
                } else {
                    const slotAvailabilityClass =
                        remaining <= 2 ?
                            "slot-availability-low" :
                            "slot-availability-good";
                    banner.innerHTML = `
                        <i class="fa-regular fa-calendar mr-2"></i>
                        ${MONTHS[parseInt(m) - 1]} ${parseInt(d)}, ${y}

                        <span class="slot-availability-count ${slotAvailabilityClass}">
                            (${remaining}/${maxSlots} slots left)
                        </span>
                    `;
                    banner.classList.remove("hidden");
                    banner.style.display = "block";
                }
            }

            if (pill) {
                pill.innerHTML = `
                    <i class="fa-regular fa-calendar mr-1"></i>
                    ${MONTHS[parseInt(m) - 1]} ${parseInt(d)}, ${y}

                    <span class="slot-pill-availability">
                        ${remaining}/${maxSlots} slots left
                    </span>
                `;
                pill.classList.add("show");
            }

            if (slotPlaceholder) {
                slotPlaceholder.classList.add("hidden");
                slotPlaceholder.style.display = "none";
            }

            if (slotContainer) {
                slotContainer.classList.remove("hidden");
                slotContainer.style.display = "block";
            }

            if (!slots.length) {
                if (slotGrid) {
                    slotGrid.innerHTML =
                        `<div class="slot-empty-message text-sm italic py-4 text-center w-full">${payload?.message || 'No available slots for this date.'}</div>`;
                }
                if (slotPlaceholder && calendarConfig.renderStyle === 'dentist') {
                    slotPlaceholder.style.display = "flex";
                    slotPlaceholder.innerHTML = `
                        <i class="fa-regular fa-calendar-xmark"></i>
                        <span>${payload?.message || 'No available slots for this date.'}</span>
                    `;
                }
                if (clearSlotBtn) {
                    clearSlotBtn.classList.add('hidden');
                    clearSlotBtn.setAttribute('aria-hidden', 'true');
                }
                return;
            }

            const slotPeriodLists = {};

            [{
                key: 'am',
                label: 'AM'
            },
            {
                key: 'pm',
                label: 'PM'
            }
            ].forEach(period => {

                const hasSlots =
                    slots.some(slot => {
                        const timeValue =
                            typeof slot ===
                                'string' ?
                                slot :
                                slot.time;

                        return (
                            getAppointmentSlotPeriod(
                                timeValue
                            ) === period.key
                        );
                    });

                if (
                    !hasSlots ||
                    !slotGrid
                ) {
                    return;
                }

                const group = document.createElement('section');

                group.className =
                    'appointment-slot-period';

                group.dataset.slotPeriod =
                    period.key;

                const heading =
                    document.createElement(
                        'div'
                    );

                heading.className =
                    'appointment-slot-period-heading';

                heading.textContent =
                    period.label;

                const list =
                    document.createElement(
                        'div'
                    );

                list.className =
                    'appointment-slot-period-grid';

                group.append(
                    heading,
                    list
                );

                slotGrid.appendChild(
                    group
                );

                slotPeriodLists[
                    period.key
                ] = list;
            });

            slots.forEach(slot => {
                const timeValue = typeof slot === 'string' ? slot : slot.time;
                const disabled = typeof slot === 'object' ?
                    (slot.is_taken || slot.taken || slot.booked || slot.available === false) :
                    false;

                const chip = document.createElement("div");

                if (
                    calendarConfig.renderStyle ===
                    'dentist'
                ) {
                    chip.className = "slot-chip border " +
                        (
                            disabled ?
                                "disabled line-through opacity-60 cursor-not-allowed pointer-events-none" :
                                "cursor-pointer"
                        );

                    chip.innerHTML =
                        disabled ?
                            `<i class="fa-solid fa-ban "></i><span>${timeValue}</span>` :
                            `<i class="fa-regular fa-clock "></i><span>${timeValue}</span>`;
                } else {
                    chip.className = "slot-chip border " +
                        (
                            disabled ?
                                "disabled line-through opacity-60 cursor-not-allowed" :
                                "cursor-pointer"
                        );

                    chip.innerHTML =
                        disabled ?
                            `<i class=" opacity-70 fa-solid fa-ban"></i><span>${timeValue}</span>` :
                            `<i class=" opacity-70 fa-regular fa-clock"></i><span>${timeValue}</span>`;
                }

                chip.dataset.time = timeValue;

                if (!disabled) {
                    chip.addEventListener("click", () => {
                        const calendarWrap =
                        document.querySelector(
                            calendarConfig
                                .calendarWrapSelector
                        );

                    const slotsWrap =
                        document.querySelector(
                            calendarConfig
                                .slotsWrapSelector
                        );

                    const dateErrorKey =
                        calendarWrap
                            ?.dataset
                            .globalErrorKey ||
                        calendarConfig
                            .dateInputId;

                    const timeErrorKey =
                        slotsWrap
                            ?.dataset
                            .globalErrorKey ||
                        calendarConfig
                            .timeInputId;

                    const timeInput =
                        document.getElementById(
                            calendarConfig.timeInputId
                        );

                        const currentDisplay = document.getElementById(calendarConfig
                            .selectedSlotDisplayId || "selectedSlotDisplay");
                        const currentDisplayTxt = document.getElementById(calendarConfig
                            .selectedSlotTextId || "selectedSlotText");
                        const currentTimePill = document.getElementById(calendarConfig.selectedTimePillId ||
                            "selectedTimePill");
                        const currentTimeText = document.getElementById(calendarConfig.selectedTimeTextId ||
                            "selectedTimeText");

                        if (!hasSelectedDateValue()) {
                            window.showGlobalGroupError?.(
                                calendarWrap,
                                dateErrorKey,
                                'Please select a date first.'
                            );

                            window.showGlobalGroupError?.(
                                slotsWrap,
                                timeErrorKey,
                                'Please select a date first.'
                            );

                            return;
                        }

                        if (selectedTime === timeValue) {
                            chip.classList.remove(
                                "selected"
                            );
                            chip.setAttribute("aria-pressed", "false");

                            selectedTime = null;
                            if (timeInput) {
                                timeInput.value = "";
                                timeInput.dispatchEvent(new Event("change", {
                                    bubbles: true
                                }));
                            }

                            if (currentDisplayTxt) currentDisplayTxt.textContent = "";
                            currentDisplay?.classList.add("hidden");

                            if (currentTimeText) currentTimeText.textContent = "";
                            if (currentTimePill) {
                                currentTimePill.classList.remove("show");
                                currentTimePill.classList.add("hidden");
                                currentTimePill.style.display = "none";
                            }

                            clearSlotBtn?.classList.add('hidden');
                            clearSlotBtn?.setAttribute('aria-hidden', 'true');

                            if (typeof markFormDirty === "function") markFormDirty();
                            return;
                        }

                        slotGrid
                            .querySelectorAll(
                                ".slot-chip"
                            )
                            .forEach(c => {
                                c.classList.remove(
                                    "selected"
                                );

                                c.setAttribute(
                                    "aria-pressed",
                                    "false"
                                );
                            });

                        chip.classList.add(
                            "selected"
                        );

                        chip.setAttribute(
                            "aria-pressed",
                            "true"
                        );

                        selectedTime = timeValue;
                        if (timeInput) {
                            timeInput.value = timeValue;
                            timeInput.dispatchEvent(new Event("change", {
                                bubbles: true
                            }));
                        }

                        if (currentDisplayTxt) currentDisplayTxt.textContent = timeValue;
                        currentDisplay?.classList.remove("hidden");

                        if (currentTimeText) currentTimeText.textContent = timeValue;
                        if (currentTimePill) {
                            currentTimePill.classList.remove("hidden");
                            currentTimePill.classList.add("show");
                            currentTimePill.style.display = "block";
                        }

                        clearSlotBtn?.classList.remove('hidden');
                        clearSlotBtn?.removeAttribute('aria-hidden');

                        if (typeof markFormDirty === "function") markFormDirty();
                    });
                }

                const period = getAppointmentSlotPeriod(timeValue);

                slotPeriodLists[
                    period
                ]?.appendChild(
                    chip
                );
            });
        }

        let currentYear = new Date().getFullYear();
        let currentMonth = new Date().getMonth();

        let calendarMonthAnimating = false;

        async function changeCalendarMonth(dir) {
            if (calendarMonthAnimating) {
                return;
            }

            const direction = Number(dir);

            const candidate = new Date(
                currentYear,
                currentMonth + direction,
                1
            );

            const {
                minimum,
                maximum
            } = getMonthBounds();

            if (
                candidate < minimum ||
                candidate > maximum
            ) {
                return;
            }

            calendarMonthAnimating = true;

            clearTimeout(dashboardLoadingTimer);
            dashboardLoadingTimer = null;

            const container =
                document.getElementById(
                    calendarConfig.calendarContainerId
                );

            const currentPanel =
                container?.querySelector(
                    '.dashboard-calendar-main'
                ) ||
                container?.querySelector(
                    '.cal-shell'
                );

            if (currentPanel) {
                const outgoingClass =
                    direction > 0 ?
                        'global-carousel-out-left' :
                        'global-carousel-out-right';

                currentPanel.classList.add(
                    outgoingClass
                );

                await new Promise(resolve => {
                    window.setTimeout(
                        resolve,
                        180
                    );
                });
            }

            currentYear =
                candidate.getFullYear();

            currentMonth =
                candidate.getMonth();

            selectedDate = null;
            focusedDateIso = null;

            renderCalendar();

            requestAnimationFrame(() => {
                const nextContainer =
                    document.getElementById(
                        calendarConfig.calendarContainerId
                    );

                const nextPanel =
                    nextContainer?.querySelector(
                        '.dashboard-calendar-main'
                    ) ||
                    nextContainer?.querySelector(
                        '.cal-shell'
                    );

                if (!nextPanel) {
                    calendarMonthAnimating = false;
                    return;
                }

                const incomingClass =
                    direction > 0 ?
                        'global-carousel-in-right' :
                        'global-carousel-in-left';

                nextPanel.classList.add(
                    incomingClass
                );

                window.setTimeout(() => {
                    nextPanel.classList.remove(
                        incomingClass
                    );

                    calendarMonthAnimating = false;
                }, 300);
            });
        };

        window.__appointmentCalendars[calendarInstanceKey] = {
            ...(window.__appointmentCalendars[
                calendarInstanceKey
            ] || {}),

            changeMonth:
                changeCalendarMonth,
        };

        function monthHasBookableDate(
            year,
            month,
            startFromToday = false
        ) {
            const totalDays =
                new Date(
                    year,
                    month + 1,
                    0
                ).getDate();

            let startDay = 1;

            if (
                startFromToday &&
                year === todayDate.getFullYear() &&
                month === todayDate.getMonth()
            ) {

                startDay =
                    todayDate.getDate() +
                    (
                        calendarConfig.disallowToday ?
                            1 :
                            0
                    );
            }

            for (
                let day = startDay; day <= totalDays; day++
            ) {
                const state =
                    resolveCalendarDayState(
                        year,
                        month,
                        day
                    );

                if (!state.isDisabled) {
                    return true;
                }
            }

            return false;
        }


        function findFirstBookableMonth() {
            const {
                minimum,
                maximum
            } = getMonthBounds();

            const cursor =
                new Date(
                    todayDate.getFullYear(),
                    todayDate.getMonth(),
                    1
                );

            if (cursor < minimum) {
                cursor.setTime(
                    minimum.getTime()
                );
            }

            while (cursor <= maximum) {
                const year =
                    cursor.getFullYear();

                const month =
                    cursor.getMonth();

                const isCurrentMonth =
                    year ===
                    todayDate.getFullYear() &&
                    month ===
                    todayDate.getMonth();

                if (
                    monthHasBookableDate(
                        year,
                        month,
                        isCurrentMonth
                    )
                ) {
                    return {
                        year,
                        month
                    };
                }

                cursor.setMonth(
                    cursor.getMonth() + 1
                );
            }

            return null;
        }

        document.addEventListener("DOMContentLoaded", async function () {
            try {
                if (
                    typeof window
                        .loadDatePickerModule ===
                    'function'
                ) {
                    await window
                        .loadDatePickerModule();
                }
            } catch (error) {
                console.error(
                    'Unable to load calendar source module.',
                    error
                );
            }

            ensureSharedCalendarSource();

            const queryParams =
                new URLSearchParams(
                    window.location.search
                );

            const queryDate =
                queryParams.get(
                    'date'
                );

            const queryTime =
                queryParams.get(
                    'time'
                );

            const queryDateState =
                queryDate
                    ? getCalendarDateStateFromIso(
                        queryDate
                    )
                    : null;

            if (
                calendarConfig.mode ===
                'patient-dashboard'
            ) {
                currentYear =
                    todayDate.getFullYear();

                currentMonth =
                    todayDate.getMonth();
            }

            if (
                calendarConfig.mode ===
                'booking'
            ) {
                if (
                    queryDateState &&
                    !queryDateState.isDisabled
                ) {
                    currentYear =
                        queryDateState
                            .cellDate
                            .getFullYear();

                    currentMonth =
                        queryDateState
                            .cellDate
                            .getMonth();

                    selectedDate = null;

                    focusedDateIso =
                        queryDateState.iso;
                } else {
                    const firstBookableMonth =
                        findFirstBookableMonth();

                    if (firstBookableMonth) {
                        currentYear =
                            firstBookableMonth.year;

                        currentMonth =
                            firstBookableMonth.month;
                    }
                }
            }

            if (
                calendarConfig.mode !==
                'booking'
            ) {
                renderCalendarLoading();
            }

            setTimeout(
                async () => {
                    renderCalendar();

                   if (
                        calendarConfig.mode ===
                        'booking' &&
                        queryDateState &&
                        !queryDateState.isDisabled
                    ) {
                        await selectDate(
                            queryDateState.iso
                        );

                        if (queryTime) {
                            const queryTimeChip =
                                Array.from(
                                    document.querySelectorAll(
                                        `#${calendarConfig.slotGridId} .slot-chip`
                                    )
                                )
                                .find(chip => {
                                    return (
                                        String(
                                            chip.dataset.time || ''
                                        ).trim() ===
                                        String(
                                            queryTime
                                        ).trim()
                                        &&
                                        !chip.classList.contains(
                                            'disabled'
                                        )
                                    );
                                });

                            if (queryTimeChip) {
                                queryTimeChip.click();

                            } else {
                                window.showToast?.({
                                    type: 'info',
                                    title: 'Time slot unavailable',
                                    message:
                                        'The time you selected is no longer available. Please choose another available slot.',
                                    duration: 4000,
                                });
                            }
                        }
                    }
                },
                calendarConfig.mode ===
                    'booking'
                    ? 0
                    : 650
            );
        }
    );
    })();
</script>