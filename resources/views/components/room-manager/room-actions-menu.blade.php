@props(['room', 'currentDate'])

@php
    $isFutureDate = $currentDate->isFuture();
    $isPastDate = $currentDate->isPast() && !$currentDate->isToday();
    $isFreeAndClean = $room->display_status === \App\Enums\RoomStatus::LIBRE && 
                      isset($room->cleaning_status) && 
                      ($room->cleaning_status['code'] ?? null) === 'limpia';
@endphp

<div class="flex items-center justify-end gap-1.5">
    {{-- Ocupar habitación --}}
    @if(!$isFutureDate && !$isPastDate && $room->display_status !== \App\Enums\RoomStatus::OCUPADA && $room->display_status !== \App\Enums\RoomStatus::PENDIENTE_CHECKOUT)
        <button type="button"
            wire:click="openQuickRent({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Ocupar habitación"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 hover:border-blue-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
            <i class="fas fa-key text-sm"></i>
            <span class="sr-only">Ocupar habitación</span>
        </button>

        {{-- Reservar --}}
        <button type="button"
            wire:click="openQuickRent({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Reservar"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:border-emerald-300 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50">
            <i class="fas fa-calendar-check text-sm"></i>
            <span class="sr-only">Reservar</span>
        </button>
    @endif

    {{-- Liberar --}}
    @if(!$isFutureDate && !$isPastDate && !$isFreeAndClean)
        <button type="button"
            @click="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', {{ $room->total_debt ?? 0 }}, {{ $room->current_reservation->id ?? 'null' }});"
            title="Liberar"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-yellow-200 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 hover:border-yellow-300 transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-500">
            <i class="fas fa-door-open text-sm"></i>
            <span class="sr-only">Liberar</span>
        </button>
    @endif

    {{-- Editar habitación --}}
    @if(!$isPastDate)
        <button type="button"
            wire:click="openRoomEdit({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Editar habitación"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:border-indigo-300 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50">
            <i class="fas fa-edit text-sm"></i>
            <span class="sr-only">Editar habitación</span>
        </button>
    @endif

    {{-- Ver historial --}}
    <button type="button"
        wire:click="openRoomDetail({{ $room->id }})"
        wire:loading.attr="disabled"
        title="Ver historial"
        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 disabled:opacity-50">
        <i class="fas fa-history text-sm"></i>
        <span class="sr-only">Ver historial</span>
    </button>

    {{-- Botones especiales para pendiente de checkout --}}
    @if(!$isFutureDate && !$isPastDate && $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT && isset($room->current_reservation) && $room->current_reservation)
        <button type="button"
            wire:click="continueStay({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Continuar estadía"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50">
            <i class="fas fa-redo-alt text-sm"></i>
            <span class="sr-only">Continuar</span>
        </button>
        <button type="button"
            @click="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', {{ $room->total_debt ?? 0 }}, {{ $room->current_reservation->id ?? 'null' }}, true);"
            title="Cancelar estadía"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 hover:border-red-300 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500">
            <i class="fas fa-times text-sm"></i>
            <span class="sr-only">Cancelar</span>
        </button>
    @endif
</div>
