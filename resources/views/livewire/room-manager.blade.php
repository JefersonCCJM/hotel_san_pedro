{{-- 
    SINCRONIZACIÓN EN TIEMPO REAL:
    - Mecanismo principal: Eventos Livewire (inmediatos cuando ambos componentes están montados)
    - Mecanismo fallback: Polling cada 5s (garantiza sincronización ≤5s si el evento se pierde)
    - NO se usan WebSockets para mantener simplicidad y evitar infraestructura adicional
    - El polling es eficiente porque usa eager loading y no hace N+1 queries
--}}
<div class="space-y-6" 
     wire:poll.5s="refreshRoomsPolling"
     x-data="{ 
    quickRentModal: @entangle('quickRentModal'),
        roomDetailModal: @entangle('roomDetailModal'),
        roomEditModal: @entangle('roomEditModal'),
        createRoomModal: @entangle('createRoomModal'),
        actionsMenuOpen: null,
        init() {
            const handleScroll = () => {
                if (this.actionsMenuOpen !== null) {
                    this.closeActionsMenu();
                }
            };
            window.addEventListener('scroll', handleScroll, true);
            document.addEventListener('scroll', handleScroll, true);
            this.$el.addEventListener('scroll', handleScroll, true);
        },
        openActionsMenu(roomId, event) {
            event.stopPropagation();
            if (this.actionsMenuOpen === roomId) {
                this.closeActionsMenu();
                return;
            }
            this.actionsMenuOpen = roomId;
        },
        closeActionsMenu() {
            this.actionsMenuOpen = null;
        }
}"
     @scroll.window="closeActionsMenu()">
    
    <!-- HEADER -->
    <x-room-manager.header :roomsCount="isset($rooms) ? $rooms->total() : (isset($releaseHistory) ? (method_exists($releaseHistory, 'total') ? $releaseHistory->total() : $releaseHistory->count()) : 0)" />

    <!-- PESTAÑAS -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm mb-6">
        <div class="border-b border-gray-200">
            <nav class="flex space-x-8 px-6" aria-label="Tabs">
                <button 
                    wire:click="switchTab('rooms')"
                    :class="$wire.activeTab === 'rooms' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-colors">
                    <i class="fas fa-door-open mr-2"></i>
                    Habitaciones
                            </button>
                <button 
                    wire:click="switchTab('history')"
                    :class="$wire.activeTab === 'history' ? 'border-blue-500 text-blue-600' : 'border-transparent text-gray-500 hover:text-gray-700 hover:border-gray-300'"
                    class="whitespace-nowrap py-4 px-1 border-b-2 font-bold text-sm transition-colors">
                    <i class="fas fa-history mr-2"></i>
                    Historial de Liberaciones
                            </button>
            </nav>
        </div>
    </div>

    @if($activeTab === 'rooms')
        <!-- FILTROS -->
        <x-room-manager.filters 
            :statuses="$statuses" 
            :ventilationTypes="$ventilationTypes" 
            :currentDate="$currentDate" 
            :daysInMonth="$daysInMonth" 
        />

        <!-- TABLA DE HABITACIONES -->
        <x-room-manager.rooms-table 
            :rooms="$rooms" 
            :currentDate="$currentDate" 
        />
    @elseif($activeTab === 'history')
        <!-- HISTORIAL DE LIBERACIONES -->
        <x-room-manager.release-history 
            :releaseHistory="$releaseHistory" 
        />
                                    @endif

    <!-- MODAL: DETALLE CUENTA -->
    <x-room-manager.room-detail-modal 
        :detailData="$detailData" 
        :showAddSale="$showAddSale"
        :showAddDeposit="$showAddDeposit"
    />
    
    <!-- MODAL: REGISTRAR PAGO (dentro del contexto del componente para usar @this) -->
    <x-notifications.payment-modal />

    <!-- MODAL: ARRENDAMIENTO RÁPIDO -->
    <x-room-manager.quick-rent-modal 
        :rentForm="$rentForm" 
        :additionalGuests="$additionalGuests" 
        :checkInDate="$date"
    />

    <!-- MODAL: CREAR CLIENTE -->
    <livewire:create-customer-modal />

    <!-- MODAL: DETALLE HISTORIAL DE LIBERACIÓN -->
    <x-room-manager.release-history-detail-modal 
        :releaseHistoryDetail="$releaseHistoryDetail" 
        :releaseHistoryDetailModal="$releaseHistoryDetailModal"
    />

    <!-- MODAL: CONFIRMACIÓN DE LIBERACIÓN -->
    <x-room-manager.room-release-confirmation-modal />

    <!-- MODAL: HUÉSPEDES -->
    <x-room-manager.guests-modal />

    <!-- MODAL: CREAR HABITACIÓN -->
    <x-room-manager.create-room-modal />

    <!-- MODAL: EDITAR HABITACIÓN -->
    @if($roomEditData)
        <x-room-manager.room-edit-modal 
            :room="$roomEditData['room']" 
            :statuses="$roomEditData['statuses']"
            :ventilation_types="$roomEditData['ventilation_types']"
            :isOccupied="$roomEditData['isOccupied']"
        />
    @endif

    <!-- SCRIPTS -->
    <x-room-manager.scripts />
</div>
