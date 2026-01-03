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
        <x-room-manager.room-cleaning-status :room="$room" />
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
        <x-room-manager.room-guest-info :room="$room" />
    </td>

    <td class="px-6 py-4 whitespace-nowrap">
        <x-room-manager.room-payment-info :room="$room" />
    </td>

    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium" style="position: relative; overflow: visible;">
        @if($currentDate->isPast() && !$currentDate->isToday())
            <span class="text-xs text-gray-400 italic">Histórico</span>
        @else
            <x-room-manager.room-actions-menu :room="$room" :currentDate="$currentDate" />
        @endif
    </td>
</tr>

