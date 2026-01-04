@props(['room'])

@if(($room->display_status === \App\Enums\RoomDisplayStatus::OCUPADA || $room->display_status === \App\Enums\RoomDisplayStatus::PENDIENTE_CHECKOUT) && isset($room->current_reservation) && $room->current_reservation)
    <div class="flex flex-col cursor-pointer hover:opacity-80 transition-opacity" 
         x-data
         @click="
             @this.call('loadRoomGuests', {{ $room->id }}).then((data) => {
                 window.dispatchEvent(new CustomEvent('open-guests-modal', { detail: data }));
             });
         "
         title="Click para ver todos los huéspedes">
        <span class="text-sm font-semibold text-gray-900">{{ $room->current_reservation->customer?->name ?? 'N/A' }}</span>
        <span class="text-xs text-blue-600 font-medium">
            Salida: {{ \Carbon\Carbon::parse($room->current_reservation->check_out_date)->format('d/m/Y') }}
        </span>
        @php
            $totalGuests = 1;
            if (isset($room->current_reservation->total_guests) && $room->current_reservation->total_guests) {
                $totalGuests = $room->current_reservation->total_guests;
            } elseif (isset($room->current_reservation->guests_count)) {
                $totalGuests = $room->current_reservation->guests_count;
            }
        @endphp
        @if($totalGuests > 1)
            <span class="text-[10px] text-gray-500 mt-1">
                <i class="fas fa-users mr-1"></i>
                {{ $totalGuests }} huéspedes
            </span>
        @endif
    </div>
@elseif(($room->display_status === \App\Enums\RoomDisplayStatus::OCUPADA || $room->display_status === \App\Enums\RoomDisplayStatus::PENDIENTE_CHECKOUT) && isset($room->guest_name) && $room->guest_name)
    <div class="flex flex-col">
        <span class="text-sm font-semibold text-gray-900">{{ $room->guest_name }}</span>
        @if(isset($room->check_out_date) && $room->check_out_date)
            <span class="text-xs text-blue-600 font-medium">
                Salida: {{ \Carbon\Carbon::parse($room->check_out_date)->format('d/m/Y') }}
            </span>
        @endif
        @if(isset($room->snapshot_guests_count) && $room->snapshot_guests_count > 1)
            <span class="text-[10px] text-gray-500 mt-1">
                <i class="fas fa-users mr-1"></i>
                {{ $room->snapshot_guests_count }} huéspedes
            </span>
        @endif
    </div>
@else
    <span class="text-xs text-gray-400 italic">Sin arrendatario</span>
@endif

