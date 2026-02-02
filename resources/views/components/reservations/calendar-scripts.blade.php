@push('scripts')
<script>
// Tooltip functionality for calendar cells
document.addEventListener('DOMContentLoaded', function() {
    let tooltip = null;
    const cells = document.querySelectorAll('[data-tooltip]');
    
    cells.forEach(cell => {
        cell.addEventListener('mouseenter', function(e) {
            const tooltipAttr = this.getAttribute('data-tooltip');
            console.log('🔍 Tooltip data:', tooltipAttr);
            
            if (!tooltipAttr || tooltipAttr.trim() === '') {
                console.log('❌ Empty tooltip - skipping');
                return;
            }
            
            let tooltipData;
            try {
                tooltipData = JSON.parse(tooltipAttr);
            } catch (error) {
                console.error('❌ JSON parse error:', error, 'Raw data:', tooltipAttr);
                return;
            }
            
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
    console.log('🔥 openReservationDetail called with:', data);
    const modal = document.getElementById('reservation-detail-modal');

    // Validar que todos los datos necesarios existan
    if (!data || !data.id) {
        console.error('❌ Invalid reservation data:', data);
        return;
    }

    // Llenar los campos de la modal
    document.getElementById('modal-customer-name').innerText = data.customer_name || 'Cliente no disponible';
    document.getElementById('modal-customer-name-header').innerText = data.customer_name || 'Cliente no disponible';
    document.getElementById('modal-reservation-id').innerText = 'Reserva #' + data.id;
    document.getElementById('modal-room-info').innerText = data.rooms || 'Sin habitaciones';
    document.getElementById('modal-dates').innerText = (data.check_in || 'N/A') + ' - ' + (data.check_out || 'N/A');
    document.getElementById('modal-checkin-time').innerText = data.check_in_time || 'N/A';
    document.getElementById('modal-guests-count').innerText = data.guests_count || '0';
    // document.getElementById('modal-payment-method').innerText = data.payment_method || 'N/A'; // Eliminado de la modal
    document.getElementById('modal-total').innerText = '$' + (data.total || '0');
    document.getElementById('modal-deposit').innerText = '$' + (data.deposit || '0');
    document.getElementById('modal-balance').innerText = '$' + (data.balance || '0');
    document.getElementById('modal-notes').innerText = data.notes || 'Sin notas adicionales';
    
    // Nuevos campos adicionales
    document.getElementById('modal-customer-id').innerText = data.customer_identification || '-';
    document.getElementById('modal-customer-phone').innerText = data.customer_phone || '-';
    document.getElementById('modal-status').innerText = data.status || 'Activa';
    
    // Calcular noches
    if (data.check_in && data.check_out) {
        try {
            const checkIn = new Date(data.check_in.split('/').reverse().join('-'));
            const checkOut = new Date(data.check_out.split('/').reverse().join('-'));
            const nights = Math.ceil((checkOut - checkIn) / (1000 * 60 * 60 * 24));
            document.getElementById('modal-nights').innerText = nights + ' noche' + (nights !== 1 ? 's' : '');
        } catch (e) {
            document.getElementById('modal-nights').innerText = '-';
        }
    } else {
        document.getElementById('modal-nights').innerText = '-';
    }

    // Configurar los botones de acción
    const editBtn = document.getElementById('modal-edit-btn');
    const pdfBtn = document.getElementById('modal-pdf-btn');
    const deleteBtn = document.getElementById('modal-delete-btn');

    if (editBtn && data.edit_url) {
        editBtn.href = data.edit_url;
        editBtn.style.display = 'flex';
    } else {
        editBtn.style.display = 'none';
    }

    if (pdfBtn && data.pdf_url) {
        pdfBtn.href = data.pdf_url;
        pdfBtn.style.display = 'flex';
    } else {
        pdfBtn.style.display = 'none';
    }

    if (deleteBtn && data.id) {
        deleteBtn.onclick = () => {
            closeReservationDetail();
            openDeleteModal(data.id);
        };
        deleteBtn.style.display = 'flex';
    } else {
        deleteBtn.style.display = 'none';
    }

    console.log('🔥 Modal found:', modal);
    console.log('🔥 Showing modal for reservation:', data.id);
    
    // Mostrar la modal
    modal.classList.remove('hidden');
    document.body.style.overflow = 'hidden';
    
    // Agregar animación de entrada
    setTimeout(() => {
        modal.querySelector('.relative').classList.add('scale-100');
        modal.querySelector('.relative').classList.remove('scale-95');
    }, 10);
    
    console.log('🔥 Modal should be visible now');
}

function closeReservationDetail() {
    console.log('🔥 Closing modal');
    const modal = document.getElementById('reservation-detail-modal');
    if (modal) {
        modal.classList.add('hidden');
        document.body.style.overflow = 'auto';
        console.log('🔥 Modal closed');
    } else {
        console.error('❌ Modal not found for closing');
    }
}

// Función de prueba para verificar que la modal existe
function testModal() {
    const modal = document.getElementById('reservation-detail-modal');
    console.log('🧪 Test Modal - Modal exists:', !!modal);
    if (modal) {
        console.log('🧪 Test Modal - Modal classes:', modal.className);
        console.log('🧪 Test Modal - Modal hidden:', modal.classList.contains('hidden'));
    }
}

// Ejecutar prueba cuando se carga la página
document.addEventListener('DOMContentLoaded', function() {
    setTimeout(testModal, 1000);
});

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

