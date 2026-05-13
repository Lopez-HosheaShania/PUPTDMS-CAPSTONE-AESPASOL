<script>
    let selectedCancelUrl = null;
    let selectedCancelAppointmentId = null;

    function extractAppointmentIdFromCancelUrl(url) {
        const match = String(url || '').match(/appointments\/(\d+)/);
        return match ? match[1] : null;
    }

    function cancelAppointmentFromModal(url, patientName = 'this patient', appointmentDate = '—') {
        selectedCancelUrl = url;
        selectedCancelAppointmentId = extractAppointmentIdFromCancelUrl(url);
        document.getElementById('cancelPatientName').textContent = patientName;
        document.getElementById('cancelAppointmentDate').textContent = appointmentDate;
        document.querySelectorAll('input[name="cancelReason"]').forEach(r => r.checked = false);
        clearReasonError();
        const confirmBtn = document.getElementById('confirmCancelBtn');
        confirmBtn.disabled = false;
        confirmBtn.innerHTML = '<i class="fa-solid fa-ban text-xs mr-1.5"></i>Yes, Cancel';
        document.getElementById('cancelAppointmentModal').classList.remove('hidden');
    }

    function closeCancelAppointmentModal() {
        document.getElementById('cancelAppointmentModal').classList.add('hidden');
        selectedCancelUrl = null;
        selectedCancelAppointmentId = null;
    }

    function handleCancelBackdropClick(e) {
        if (e.target === document.getElementById('cancelAppointmentModal')) {
            closeCancelAppointmentModal();
        }
    }

    function clearReasonError() {
        document.getElementById('cancelReasonChips').classList.remove('invalid', 'chips-error-shake');
        document.getElementById('reasonError').classList.add('hidden');
    }

    document.querySelectorAll('input[name="cancelReason"]').forEach(r => {
        r.addEventListener('change', clearReasonError);
    });

    async function confirmCancelAppointment() {
        const selectedReason = document.querySelector('input[name="cancelReason"]:checked')?.value || null;

        if (!selectedReason) {
            const chips = document.getElementById('cancelReasonChips');
            document.getElementById('reasonError').classList.remove('hidden');
            chips.classList.add('invalid');
            chips.classList.remove('chips-error-shake');
            void chips.offsetWidth;
            chips.classList.add('chips-error-shake');
            return;
        }

        if (!selectedCancelUrl) {
            return;
        }

        const btn = document.getElementById('confirmCancelBtn');
        btn.disabled = true;
        btn.innerHTML = '<i class="fa-solid fa-circle-notch fa-spin text-xs mr-1.5"></i>Cancelling…';

        try {
            const response = await fetch(selectedCancelUrl, {
                method: 'POST',
                headers: {
                    'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute(
                        'content') || '',
                    'X-Requested-With': 'XMLHttpRequest',
                    'Accept': 'application/json',
                    'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                    reason: selectedReason
                })
            });

            const data = await response.json();

            if (response.ok && data.success) {
                if (selectedCancelAppointmentId) {
                    sessionStorage.setItem(`appointmentCancelReason:${selectedCancelAppointmentId}`, selectedReason);
                }
                closeCancelAppointmentModal();
                if (typeof closeDayAppointmentsModal === 'function') {
                    closeDayAppointmentsModal();
                }
                sessionStorage.setItem('dentistToast', JSON.stringify({
                    title: 'Appointment cancelled',
                    message: `${document.getElementById('cancelPatientName')?.textContent || 'Appointment'} was cancelled successfully.`,
                    tone: 'danger',
                    duration: 3500
                }));

                window.location.reload();
            } else {
                btn.disabled = false;
                btn.innerHTML = '<i class="fa-solid fa-ban text-xs mr-1.5"></i>Yes, Cancel';
            }
        } catch (error) {
            btn.disabled = false;
            btn.innerHTML = '<i class="fa-solid fa-ban text-xs mr-1.5"></i>Yes, Cancel';
        }
    }
</script>
