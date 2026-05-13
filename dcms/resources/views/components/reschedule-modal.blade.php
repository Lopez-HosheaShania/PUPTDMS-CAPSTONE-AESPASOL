<div id="rescheduleModal"
    class="fixed inset-0 bg-black/50 flex items-end sm:items-center justify-center backdrop-blur-sm hidden z-[9999] p-0 sm:p-4"
    onclick="handleRescheduleBackdropClick(event)">
    <div
        class="reschedule-modal-panel bg-white w-full sm:max-w-5xl rounded-t-2xl sm:rounded-2xl overflow-hidden shadow-2xl flex flex-col">

        <div
            class="relative bg-gradient-to-r from-amber-500 via-amber-400 to-yellow-400 px-5 sm:px-6 py-4">
            <button type="button" onclick="closeRescheduleModal()"
                class="absolute top-3.5 right-3.5 w-8 h-8 rounded-full bg-white/15 hover:bg-white/25 text-white/80 hover:text-white flex items-center justify-center transition text-sm">
                <i class="fa-solid fa-xmark"></i>
            </button>

            <div class="flex items-start gap-3 pr-10">
                <div
                    class="w-11 h-11 rounded-2xl bg-white/20 border border-white/25 shadow-sm flex items-center justify-center flex-shrink-0 backdrop-blur-sm">
                    <i class="fa-regular fa-calendar-check text-white text-lg"></i>
                </div>

                <div class="min-w-0">
                    <p class="text-[10px] font-bold uppercase tracking-[0.18em] text-white/70 mb-1">
                        Appointment Update
                    </p>
                    <h2 class="text-white font-bold text-lg leading-tight">
                        Reschedule Appointment
                    </h2>
                    <p class="text-white/80 text-[12px] mt-1 leading-relaxed">
                        Choose a new date and time, then save the changes.
                    </p>
                </div>
            </div>
        </div>

        <div class="reschedule-modal-body px-5 sm:px-6 py-4 sm:py-5 bg-gray-50 overflow-y-auto">
            <div
                class="bg-white border border-[#f1ece7] rounded-2xl px-4 sm:px-5 py-4 mb-4 shadow-[0_4px_18px_rgba(0,0,0,0.04)]">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-3">
                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">
                            <i class="fa-solid fa-user fa-xs mr-1"></i>Patient
                        </div>
                        <div class="text-[14px] font-bold text-gray-800 truncate" id="resPatientName">—</div>
                    </div>

                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">
                            <i class="fa-regular fa-clock fa-xs mr-1"></i>Current Schedule
                        </div>
                        <div class="text-[13px] font-medium text-gray-700" id="resCurrentSchedule">—</div>
                    </div>

                    <div>
                        <div class="text-[11px] font-semibold uppercase tracking-wide text-gray-400 mb-1">
                            <i class="fa-solid fa-tooth fa-xs mr-1"></i>Service
                        </div>
                        <div class="text-[13px] font-medium text-gray-700" id="resServiceType">—</div>
                    </div>
                </div>
            </div>

            <form id="rescheduleForm" method="POST">
                @csrf
                @method('PUT')

                <input type="hidden" name="service_type" value="">
                <input type="hidden" id="new_appointment_date" name="new_appointment_date" required>
                <input type="hidden" id="new_appointment_time" name="new_appointment_time" required>

                <div class="section-label">
                    <i class="fa-regular fa-calendar fa-xs"></i> New Date & Time
                </div>

                <div id="dateError" class="error-msg" style="display:none;">
                    <i class="fa-solid fa-circle-exclamation"></i> Please select a date.
                </div>

                <div class="two-col mb-2 sm:mb-3">
                    <div class="cal-wrap">
                        <div id="calGridWrapReschedule"></div>
                    </div>

                    <div class="slots-wrap">
                        <div class="section-label" style="margin-bottom:.6rem;">
                            <i class="fa-regular fa-clock fa-xs"></i> Time Slot
                        </div>

                        <div id="dateBanner" class="hidden"></div>
                        <div class="slots-date-pill" id="datePill"></div>

                        <div id="slotPlaceholder" class="slots-placeholder">
                            <i class="fa-regular fa-calendar-xmark"></i>
                            <span>Select a date to see available slots</span>
                        </div>

                        <div id="slotContainer" class="hidden">
                            <div id="slotGrid" class="slots-grid" style="display:none;"></div>
                        </div>

                        <div id="selectedTimePill"
                            class="hidden mt-4 w-full rounded-2xl border border-[#f0d5d5] bg-[linear-gradient(135deg,#fff7f7,#fff1f1)] px-4 py-3 shadow-sm">
                            <div class="flex items-center gap-3">
                                <div
                                    class="flex h-8 w-8 items-center justify-center rounded-full bg-[#8B0000] text-white shadow-sm">
                                    <i class="fa-solid fa-circle-check text-sm"></i>
                                </div>
                                <div class="min-w-0">
                                    <p class="text-[11px] font-semibold uppercase tracking-[0.14em] text-[#b35b5b]">
                                        Selected Time
                                    </p>
                                    <p id="selectedTimeText" class="text-[15px] font-bold text-[#8B0000] leading-tight">
                                    </p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <div id="timeError" class="error-msg" style="display:none;">
                    <i class="fa-solid fa-circle-exclamation"></i> Please select a time slot.
                </div>

                <div class="section-label mt-5 sm:mt-6">
                    <i class="fa-regular fa-message fa-xs"></i>
                    Reason for Rescheduling
                    <span style="font-weight:400;text-transform:none;letter-spacing:0;">(optional)</span>
                </div>

                <div class="reason-wrap w-full">
                    <textarea id="reschedule_reason" name="reschedule_reason" rows="3"
                        placeholder="e.g. Patient requested a later date…" class="reason-textarea w-full min-h-[112px] resize-none"></textarea>
                </div>

                <div class="btn-row flex flex-col-reverse sm:flex-row gap-3">
                    <button type="button" class="btn btn-cancel" id="cancelBtn">
                        <i class="fa-solid fa-xmark"></i> Cancel
                    </button>
                    <button type="submit" class="btn btn-confirm" id="confirmRescheduleBtn">
                        <i class="fa-solid fa-check"></i> Confirm Reschedule
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>
