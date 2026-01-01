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
    <x-room-manager.header :roomsCount="$rooms->total()" />

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

    <!-- MODAL: DETALLE CUENTA -->
    <x-room-manager.room-detail-modal 
        :detailData="$detailData" 
        :showAddSale="$showAddSale"
        :showAddDeposit="$showAddDeposit"
    />

    <!-- MODAL: ARRENDAMIENTO RÁPIDO -->
    <x-room-manager.quick-rent-modal 
        :rentForm="$rentForm" 
        :additionalGuests="$additionalGuests" 
    />

    <!-- MODAL: CREAR CLIENTE -->
    <livewire:create-customer-modal />

    <!-- SCRIPTS -->
    <x-room-manager.scripts />
</div>
