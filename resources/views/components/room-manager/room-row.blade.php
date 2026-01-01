@props(['room', 'currentDate'])

<tr class="{{ $room->display_status->cardBgColor() }} transition-colors duration-150 group" wire:key="room-{{ $room->id }}">
    <td class="px-6 py-4 whitespace-nowrap">
        <div class="flex items-center">
            <div class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center mr-3 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                <i class="fas fa-door-closed"></i>
            </div>
            <div wire:click="openRoomDetail({{ $room->id }})" class="cursor-pointer">
                <div class="text-sm font-semibold text-gray-900">Hab. {{ $room->room_number }}</div>
                <div class="text-xs text-gray-500">
                    {{ $room->beds_count }} {{ $room->beds_count == 1 ? 'Cama' : 'Camas' }} • Cap. {{ $room->max_capacity }}
                </div>
            </div>
        </div>
    </td>

    <td class="px-6 py-4 whitespace-nowrap text-center">
        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $room->display_status->color() }}">
            <span class="w-1.5 h-1.5 rounded-full mr-2" style="background-color: currentColor"></span>
            {{ $room->display_status->label() }}
        </span>
    </td>

    <td class="px-6 py-4 whitespace-nowrap text-center" x-data="{ open: false }">
        @php($cleaning = $room->cleaning_status)
        <div class="relative inline-block">
            <button 
                type="button"
                @click.stop="open = !open"
                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $cleaning['color'] }} hover:opacity-80 transition-opacity cursor-pointer"
                title="Click para cambiar estado de limpieza">
                <i class="fas {{ $cleaning['icon'] }} mr-1.5"></i>
                {{ $cleaning['label'] }}
            </button>

            <!-- Dropdown para cambiar estado -->
            <div 
                x-show="open"
                @click.outside="open = false"
                @keydown.escape.window="open = false"
                x-transition
                x-cloak
                class="absolute left-1/2 transform -translate-x-1/2 mt-2 w-48 bg-white rounded-lg shadow-xl ring-1 ring-gray-200 z-50"
                style="display: none;">
                <div class="py-1">
                    @if($cleaning['code'] === 'pendiente')
                        <button 
                            type="button"
                            wire:click="updateCleaningStatus({{ $room->id }}, 'limpia')"
                            wire:target="updateCleaningStatus({{ $room->id }}, 'limpia')"
                            wire:loading.attr="disabled"
                            @click="open = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-green-50 hover:text-green-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                            <i class="fas fa-check-circle text-green-600 mr-2"></i>
                            <span>Marcar como Limpia</span>
                            <i class="fas fa-spinner fa-spin ml-auto text-xs" wire:loading wire:target="updateCleaningStatus({{ $room->id }}, 'limpia')"></i>
                        </button>
                    @else
                        <button 
                            type="button"
                            wire:click="updateCleaningStatus({{ $room->id }}, 'pendiente')"
                            wire:target="updateCleaningStatus({{ $room->id }}, 'pendiente')"
                            wire:loading.attr="disabled"
                            @click="open = false"
                            class="w-full text-left px-4 py-2 text-sm text-gray-700 hover:bg-yellow-50 hover:text-yellow-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center">
                            <i class="fas fa-broom text-yellow-600 mr-2"></i>
                            <span>Marcar como Pendiente</span>
                            <i class="fas fa-spinner fa-spin ml-auto text-xs" wire:loading wire:target="updateCleaningStatus({{ $room->id }}, 'pendiente')"></i>
                        </button>
                    @endif
                </div>
            </div>
        </div>
    </td>

    <td class="px-6 py-4 whitespace-nowrap text-center">
        @if($room->ventilation_type)
            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                <i class="fas fa-wind mr-1.5"></i>
                {{ $room->ventilation_label }}
            </span>
        @else
            <span class="text-xs text-gray-400 italic">No asignado</span>
        @endif
    </td>

    <td class="px-6 py-4 whitespace-nowrap">
        @if($room->display_status === \App\Enums\RoomStatus::OCUPADA || $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT)
            @if(isset($room->current_reservation) && $room->current_reservation)
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-900">{{ $room->current_reservation->customer->name ?? 'N/A' }}</span>
                    <span class="text-xs text-blue-600 font-medium">
                        Salida: {{ \Carbon\Carbon::parse($room->current_reservation->check_out_date)->format('d/m/Y') }}
                    </span>
                </div>
            @elseif(isset($room->guest_name) && $room->guest_name)
                <div class="flex flex-col">
                    <span class="text-sm font-semibold text-gray-900">{{ $room->guest_name }}</span>
                    @if(isset($room->check_out_date) && $room->check_out_date)
                        <span class="text-xs text-blue-600 font-medium">
                            Salida: {{ \Carbon\Carbon::parse($room->check_out_date)->format('d/m/Y') }}
                        </span>
                    @endif
                </div>
            @else
                <span class="text-xs text-gray-400 italic">Sin arrendatario</span>
            @endif
        @else
            <span class="text-xs text-gray-400 italic">Sin arrendatario</span>
        @endif
    </td>

    <td class="px-6 py-4 whitespace-nowrap">
        @if(($room->display_status === \App\Enums\RoomStatus::OCUPADA || $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT) && isset($room->current_reservation) && $room->current_reservation)
            <div class="flex flex-col space-y-1">
                @if($room->is_night_paid)
                    <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                        <i class="fas fa-moon mr-1"></i> NOCHE PAGA
                    </span>
                @else
                    <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                        <i class="fas fa-moon mr-1"></i> NOCHE PENDIENTE
                    </span>
                @endif

                @if($room->total_debt > 0)
                    <div class="flex flex-col">
                        <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Saldo Total</span>
                        <span class="text-sm font-bold text-red-700">${{ number_format($room->total_debt, 0, ',', '.') }}</span>
                    </div>
                @else
                    <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 w-fit">
                        <i class="fas fa-check-circle mr-1"></i> Al día
                    </span>
                @endif
            </div>
        @else
            <div class="flex flex-col">
                <span class="text-sm font-semibold text-gray-900">${{ number_format($room->active_prices[1] ?? 0, 0, ',', '.') }}</span>
                <span class="text-xs text-gray-400">precio base</span>
            </div>
        @endif
    </td>

    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" style="position: relative; overflow: visible;">
        @if($currentDate->isPast() && !$currentDate->isToday())
            <span class="text-xs text-gray-400 italic">Histórico</span>
        @else
            <x-room-manager.room-actions-menu :room="$room" />
        @endif
    </td>
</tr>

