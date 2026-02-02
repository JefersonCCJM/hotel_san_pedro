<div id="create-reservation-modal" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm overflow-y-auto h-full w-full hidden z-50 transition-all duration-300">
    <div class="relative top-4 mx-auto p-0 border-0 w-full max-w-7xl shadow-2xl bg-white overflow-hidden transform transition-all my-8">
        <!-- Header del Modal -->
        <div class="bg-emerald-600 px-6 py-5 text-white relative sticky top-0 z-40">
            <button onclick="closeCreateReservationModal()" class="absolute top-4 right-4 text-white/80 hover:text-white transition-colors">
                <i class="fas fa-times text-xl"></i>
            </button>
            <div class="flex items-center space-x-4">
                <div class="h-12 w-12 rounded-xl bg-white/20 backdrop-blur-md flex items-center justify-center text-xl shadow-inner">
                    <i class="fas fa-plus"></i>
                </div>
                <div>
                    <h3 class="text-xl font-black tracking-tight">Nueva Reserva</h3>
                    <p class="text-emerald-100 font-bold text-xs uppercase tracking-widest opacity-80">Crear una nueva reserva</p>
                </div>
            </div>
        </div>

        <!-- Contenido -->
        <div class="p-0">
            @livewire('reservations.reservation-create', [
                'rooms' => $modalRooms ?? [],
                'roomsData' => $modalRoomsData ?? [],
                'customers' => $modalCustomers ?? [],
                'identificationDocuments' => $modalIdentificationDocuments ?? [],
                'legalOrganizations' => $modalLegalOrganizations ?? [],
                'tributes' => $modalTributes ?? [],
                'municipalities' => $modalMunicipalities ?? [],
            ])
        </div>
    </div>
</div>

<script>
function openCreateReservationModal() {
    const modal = document.getElementById('create-reservation-modal');
    if (modal) {
        modal.classList.remove('hidden');
    }
}

function closeCreateReservationModal() {
    const modal = document.getElementById('create-reservation-modal');
    if (modal) {
        modal.classList.add('hidden');
    }
}

// Cerrar modal al hacer clic fuera
document.addEventListener('DOMContentLoaded', function() {
    const modal = document.getElementById('create-reservation-modal');
    if (modal) {
        modal.addEventListener('click', function(e) {
            if (e.target === modal) {
                closeCreateReservationModal();
            }
        });
    }
});

</script>
