{{--
    SINCRONIZACIÓN EN TIEMPO REAL:
    - Mecanismo principal: Eventos Livewire (inmediatos cuando ambos componentes están montados)
    - Mecanismo fallback: Polling cada 5s (garantiza sincronización ≤5s si el evento se pierde)
    - NO se usan WebSockets para mantener simplicidad y evitar infraestructura adicional
    - El polling es eficiente porque usa eager loading y no hace N+1 queries
--}}
<div class="space-y-6" wire:poll.5s="refreshRoomsPolling" x-data="{
    quickRentModal: @entangle('quickRentModal'),
    roomDetailModal: @entangle('roomDetailModal'),
    newCustomerModal: @entangle('newCustomerModalOpen')
}">
    @include('components.rooms.header')
    @include('components.rooms.filters')
    @include('components.rooms.table')
    @include('components.rooms.room-detail-modal')
    @include('components.rooms.quick-rent-modal')
    @include('components.rooms.new-customer-modal')
    @include('components.rooms.scripts')
</div>
