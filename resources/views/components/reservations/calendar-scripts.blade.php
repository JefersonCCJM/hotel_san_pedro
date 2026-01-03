@push('scripts')
<script>
// Tooltip functionality for calendar cells
document.addEventListener('DOMContentLoaded', function() {
    let tooltip = null;
    const cells = document.querySelectorAll('[data-tooltip]');
    
    cells.forEach(cell => {
        cell.addEventListener('mouseenter', function(e) {
            const tooltipData = JSON.parse(this.getAttribute('data-tooltip'));
            
            // Remove existing tooltip
            if (tooltip) {
                tooltip.remove();
            }
            
            // Create tooltip element
            tooltip = document.createElement('div');
            tooltip.className = 'fixed z-50 px-3 py-2 bg-gray-900 text-white text-xs rounded-lg shadow-xl pointer-events-none';
            tooltip.style.opacity = '0';
            tooltip.style.transition = 'opacity 0.2s';
            
            // Build tooltip content
            let content = `<div class="space-y-1">`;
            content += `<div class="font-bold text-emerald-400">Hab. ${tooltipData.room} - ${tooltipData.beds}</div>`;
            content += `<div class="text-gray-300">${tooltipData.date}</div>`;
            content += `<div class="text-gray-300">Estado: <span class="font-semibold">${tooltipData.status}</span></div>`;
            
            if (tooltipData.customer) {
                content += `<div class="pt-1 border-t border-gray-700">`;
                content += `<div class="text-gray-300">Cliente: <span class="font-semibold text-white">${tooltipData.customer}</span></div>`;
                if (tooltipData.check_in) {
                    content += `<div class="text-gray-300">Check-in: ${tooltipData.check_in}</div>`;
                }
                if (tooltipData.check_out) {
                    content += `<div class="text-gray-300">Check-out: ${tooltipData.check_out}</div>`;
                }
                content += `</div>`;
            }
            content += `</div>`;
            
            tooltip.innerHTML = content;
            document.body.appendChild(tooltip);
            
            // Position tooltip
            const rect = this.getBoundingClientRect();
            const tooltipRect = tooltip.getBoundingClientRect();
            const scrollX = window.pageXOffset || document.documentElement.scrollLeft;
            const scrollY = window.pageYOffset || document.documentElement.scrollTop;
            
            let left = rect.left + scrollX + (rect.width / 2) - (tooltipRect.width / 2);
            let top = rect.top + scrollY - tooltipRect.height - 8;
            
            // Adjust if tooltip goes off screen
            if (left < 10) left = 10;
            if (left + tooltipRect.width > window.innerWidth - 10) {
                left = window.innerWidth - tooltipRect.width - 10;
            }
            if (top < 10) {
                top = rect.bottom + scrollY + 8;
            }
            
            tooltip.style.left = left + 'px';
            tooltip.style.top = top + 'px';
            
            // Fade in
            setTimeout(() => {
                tooltip.style.opacity = '1';
            }, 10);
        });
        
        cell.addEventListener('mouseleave', function() {
            if (tooltip) {
                tooltip.style.opacity = '0';
                setTimeout(() => {
                    if (tooltip) {
                        tooltip.remove();
                        tooltip = null;
                    }
                }, 200);
            }
        });
    });
});

function openReservationDetail(data) {
    const modal = document.getElementById('reservation-detail-modal');

    document.getElementById('modal-customer-name').innerText = data.customer_name;
    document.getElementById('modal-reservation-id').innerText = 'Reserva #' + data.id;
    document.getElementById('modal-room-info').innerText = 'Hab. ' + data.room_number + ' (' + data.beds_count + ')';
    document.getElementById('modal-dates').innerText = data.check_in + ' - ' + data.check_out;
    document.getElementById('modal-checkin-time').innerText = data.check_in_time;
    document.getElementById('modal-guests-count').innerText = data.guests_count;
    document.getElementById('modal-payment-method').innerText = data.payment_method;
    document.getElementById('modal-total').innerText = '$' + data.total;
    document.getElementById('modal-deposit').innerText = '$' + data.deposit;
    document.getElementById('modal-balance').innerText = '$' + data.balance;
    document.getElementById('modal-notes').innerText = data.notes;

    document.getElementById('modal-edit-btn').href = data.edit_url;
    document.getElementById('modal-pdf-btn').href = data.pdf_url;
    document.getElementById('modal-delete-btn').onclick = () => {
        closeReservationDetail();
        openDeleteModal(data.id);
    };

    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
}

function closeReservationDetail() {
    document.getElementById('reservation-detail-modal').classList.add('hidden');
    document.body.style.overflow = 'auto';
}

function openDeleteModal(id) {
    // Check if reservation has started - if so, show release confirmation modal instead
    fetch('{{ route("reservations.release-data", ":id") }}'.replace(':id', id))
        .then(response => response.json())
        .then(data => {
            // If reservation exists (is active), show release modal with account info
            if (data.reservation) {
                // Add reservation_id to data for cancellation
                data.reservation_id = id;
                data.cancel_url = '{{ route("reservations.destroy", ":id") }}'.replace(':id', id);
                window.dispatchEvent(new CustomEvent('open-release-confirmation', {
                    detail: data
                }));
            } else {
                // No active reservation, use simple delete modal
                const modal = document.getElementById('delete-modal');
                const form = document.getElementById('delete-form');
                form.action = '{{ route("reservations.destroy", ":id") }}'.replace(':id', id);
                modal.classList.remove('hidden');
            }
        })
        .catch(error => {
            console.error('Error loading release data:', error);
            // Fallback to simple delete modal
            const modal = document.getElementById('delete-modal');
            const form = document.getElementById('delete-form');
            form.action = '{{ route("reservations.destroy", ":id") }}'.replace(':id', id);
            modal.classList.remove('hidden');
        });
}

function closeDeleteModal() {
    document.getElementById('delete-modal').classList.add('hidden');
}

function confirmDeleteWithPin(form) {
    form.submit();
}
</script>
@endpush

