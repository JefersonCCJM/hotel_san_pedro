{{-- 
    REAL-TIME SYNC:
    - Primary mechanism: Livewire events (immediate when both components are mounted)
    - Fallback: Polling every 5s (keeps sync within ≤5s if an event is missed)
    - No WebSockets to keep infrastructure simple
    - Polling is efficient thanks to eager loading (no N+1)
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

