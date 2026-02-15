@push('scripts')
<script>
(function () {
    // Use the exact same reservation detail modal behavior as reservations/index.
    window.__useReservationCalendarScripts = true;

    window.openReservationDetailFromEncoded = function (element) {
        try {
            const encodedPayload = element?.dataset?.reservationPayload || '';
            if (!encodedPayload) {
                throw new Error('No se encontro informacion de la reserva.');
            }

            const payloadJson = atob(encodedPayload);
            const payload = JSON.parse(payloadJson);
            const openWithOfficialModal = () => {
                if (typeof window.openReservationDetail !== 'function') {
                    return false;
                }
                window.openReservationDetail(payload);
                return true;
            };

            if (openWithOfficialModal()) {
                return;
            }

            // If scripts are still loading, retry briefly before showing an error.
            let attemptsLeft = 8;
            const retryTimer = window.setInterval(() => {
                if (openWithOfficialModal()) {
                    window.clearInterval(retryTimer);
                    return;
                }

                attemptsLeft -= 1;
                if (attemptsLeft > 0) {
                    return;
                }

                window.clearInterval(retryTimer);
                window.dispatchEvent(new CustomEvent('notify', {
                    detail: { type: 'error', message: 'No fue posible abrir el detalle de la reserva.' },
                }));
            }, 50);
        } catch (error) {
            window.dispatchEvent(new CustomEvent('notify', {
                detail: { type: 'error', message: 'No fue posible abrir el detalle de la reserva.' },
            }));
            console.error('[Reservation Detail] Invalid payload', error);
        }
    };
})();
</script>
@endpush
