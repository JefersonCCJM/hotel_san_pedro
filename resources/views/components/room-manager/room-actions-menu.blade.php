@props(['room', 'currentDate'])

@php
    $isFutureDate = $currentDate->isFuture();
    $isPastDate = $currentDate->isPast() && !$currentDate->isToday();
@endphp

<div class="relative inline-block text-right">
    <button type="button"
        x-show="actionsMenuOpen !== {{ $room->id }}"
        @click.stop="openActionsMenu({{ $room->id }}, $event)"
        class="inline-flex items-center justify-center w-9 h-9 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500"
        x-cloak>
        <span class="text-lg leading-none">⋮</span>
        <span class="sr-only">Acciones</span>
    </button>

    <div x-show="actionsMenuOpen === {{ $room->id }}" 
         x-transition
         @click.outside="closeActionsMenu()"
         @keydown.escape.window="closeActionsMenu()"
         class="absolute right-0 top-full mt-2 w-56 rounded-lg bg-white shadow-xl ring-1 ring-gray-200 divide-y divide-gray-100 z-50"
         x-cloak>
        <div class="py-1">
            @if(!$isFutureDate && !$isPastDate && $room->display_status !== \App\Enums\RoomStatus::OCUPADA && $room->display_status !== \App\Enums\RoomStatus::PENDIENTE_CHECKOUT)
                <button type="button"
                    wire:click="openQuickRent({{ $room->id }})"
                    wire:loading.attr="disabled"
                    @click="closeActionsMenu()"
                    class="w-full flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-blue-50 hover:text-blue-700 disabled:opacity-50">
                    <i class="fas fa-key text-blue-600 mr-3 w-5"></i>
                    <span class="flex-1 text-left">Ocupar habitación</span>
                </button>
                <button type="button"
                    wire:click="openQuickRent({{ $room->id }})"
                    wire:loading.attr="disabled"
                    @click="closeActionsMenu()"
                    class="w-full flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-emerald-50 hover:text-emerald-700 disabled:opacity-50">
                    <i class="fas fa-calendar-check text-emerald-600 mr-3 w-5"></i>
                    <span class="flex-1 text-left">Reservar</span>
                </button>
            @endif
            @php
                $isFreeAndClean = $room->display_status === \App\Enums\RoomStatus::LIBRE && 
                                  isset($room->cleaning_status) && 
                                  ($room->cleaning_status['code'] ?? null) === 'limpia';
            @endphp
            @if(!$isFutureDate && !$isPastDate && !$isFreeAndClean)
                <button type="button"
                    @click="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', {{ $room->total_debt ?? 0 }}, {{ $room->current_reservation->id ?? 'null' }}); closeActionsMenu();"
                    class="w-full flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-yellow-50 hover:text-yellow-700">
                    <i class="fas fa-door-open text-yellow-600 mr-3 w-5"></i>
                    <span class="flex-1 text-left">Liberar</span>
                </button>
            @endif
        </div>
        <div class="py-1">
            @if(!$isPastDate)
                <button type="button"
                    wire:click="openRoomEdit({{ $room->id }})"
                    wire:loading.attr="disabled"
                   @click="closeActionsMenu()"
                    class="w-full flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-indigo-50 hover:text-indigo-700 disabled:opacity-50">
                    <i class="fas fa-edit text-indigo-600 mr-3 w-5"></i>
                    <span class="flex-1 text-left">Editar habitación</span>
                </button>
            @endif
            <button type="button"
                wire:click="openRoomDetail({{ $room->id }})"
                wire:loading.attr="disabled"
                @click="closeActionsMenu()"
                class="w-full flex items-center px-4 py-2.5 text-sm text-gray-700 hover:bg-gray-50 hover:text-gray-900 disabled:opacity-50">
                <i class="fas fa-history text-gray-500 mr-3 w-5"></i>
                <span class="flex-1 text-left">Ver historial</span>
            </button>
            @if(!$isFutureDate && !$isPastDate && $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT && isset($room->current_reservation) && $room->current_reservation)
                <div class="flex border-t border-gray-100 mt-1 pt-1">
                    <button type="button"
                        wire:click="continueStay({{ $room->id }})"
                        wire:loading.attr="disabled"
                        @click="closeActionsMenu()"
                        class="flex-1 flex items-center justify-center px-3 py-2 text-xs font-semibold text-emerald-700 hover:bg-emerald-50 disabled:opacity-50">
                        <i class="fas fa-redo-alt mr-1.5"></i>
                        <span>Continuar</span>
                    </button>
                    <button type="button"
                        @click="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', {{ $room->total_debt ?? 0 }}, {{ $room->current_reservation->id ?? 'null' }}, true); closeActionsMenu();"
                        class="flex-1 flex items-center justify-center px-3 py-2 text-xs font-semibold text-red-700 hover:bg-red-50 disabled:opacity-50">
                        <i class="fas fa-times mr-1.5"></i>
                        <span>Cancelar</span>
                    </button>
                </div>
            @endif
        </div>
    </div>
</div>
