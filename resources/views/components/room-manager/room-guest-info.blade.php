@props(['room', 'stay'])

@php
    // SINGLE SOURCE OF TRUTH: Este componente recibe $stay explícitamente
    // GUARD CLAUSE OBLIGATORIO: Si no hay stay, no hay información de huésped para mostrar
    if (!$stay) {
        echo '<span class="text-xs text-gray-400 italic">Sin huésped</span>';
        return;
    }

    // Obtener reserva desde la stay (Single Source of Truth)
    $reservation = $stay->reservation;
    
    // GUARD CLAUSE: Si no hay reserva, mostrar mensaje apropiado
    if (!$reservation) {
        echo '<span class="text-xs text-amber-600 italic">Sin reserva asociada</span>';
        return;
    }
    
    // SINGLE SOURCE OF TRUTH: Cliente principal SIEMPRE viene de reservation->customer
    // 🔐 CRÍTICO: Verificar client_id directamente primero (puede ser NULL para walk-in sin asignar)
    // NO acceder a $reservation->customer directamente si client_id es NULL para evitar errores
    // 🔄 CRÍTICO: Si reservation tiene client_id pero customer no está cargado, forzar recarga
    // Esto evita problemas de caché cuando se actualiza el cliente después de cargar la vista
    if ($reservation->client_id !== null && !$reservation->relationLoaded('customer')) {
        $reservation->load('customer');
    }
    
    // Verificar client_id (columna real en BD) directamente
    $hasCustomerId = !empty($reservation->client_id) && $reservation->client_id !== null;
    $customer = $hasCustomerId ? ($reservation->customer ?? null) : null;
    
    // Obtener ReservationRoom asociado para acceder a huéspedes adicionales
    $reservationRoom = $reservation->reservationRooms
        ->firstWhere('room_id', $room->id);
    
    // SINGLE SOURCE OF TRUTH: Huéspedes adicionales SIEMPRE vienen de reservationRoom->getGuests()
    // Ruta: reservation_room_guests → reservation_guest_id → reservation_guests.guest_id → customers.id
    $additionalGuests = collect();
    if ($reservationRoom) {
        try {
            $additionalGuests = $reservationRoom->getGuests();
        } catch (\Exception $e) {
            // Silently handle error - no mostrar huéspedes adicionales si hay error
            \Log::warning('Error loading additional guests in room-guest-info', [
                'reservation_room_id' => $reservationRoom->id ?? null,
                'error' => $e->getMessage()
            ]);
            $additionalGuests = collect();
        }
    }
    
    // Calcular total de huéspedes (principal + adicionales)
    $totalGuests = $customer ? 1 : 0; // Cliente principal cuenta solo si existe
    if ($additionalGuests->isNotEmpty()) {
        $totalGuests += $additionalGuests->count();
    }
@endphp

@if($reservation && $customer)
    {{-- CASO NORMAL: Reserva con cliente asignado --}}
    <div class="flex flex-col cursor-pointer hover:opacity-80 transition-opacity" 
         x-data
         @click="
             @this.call('loadRoomGuests', {{ $room->id }}).then((data) => {
                 if (data && data.guests) {
                     window.dispatchEvent(new CustomEvent('open-guests-modal', { detail: data }));
                 }
             }).catch(() => {
                 // Silently handle error if reservation no longer exists
             });
         "
         title="Click para ver todos los huéspedes">
        {{-- Cliente principal --}}
        <span class="text-sm font-semibold text-gray-900">{{ $customer->name }}</span>
        
        {{-- Huéspedes adicionales --}}
        @if($additionalGuests->isNotEmpty())
            @foreach($additionalGuests as $guest)
                <span class="text-xs font-medium text-gray-700 mt-0.5">{{ $guest->name }}</span>
            @endforeach
        @endif
        
        {{-- Información de salida --}}
        @if($reservationRoom && $reservationRoom->check_out_date)
            <span class="text-xs text-blue-600 font-medium mt-1">
                Salida: {{ \Carbon\Carbon::parse($reservationRoom->check_out_date)->format('d/m/Y') }}
            </span>
        @endif
        
        {{-- Contador solo si hay más de un huésped --}}
        @if($totalGuests > 1)
            <span class="text-[10px] text-gray-500 mt-1">
                <i class="fas fa-users mr-1"></i>
                {{ $totalGuests }} huéspedes
            </span>
        @endif
    </div>
@elseif($reservation && !$customer)
    {{-- CASO EDGE: Reserva activa pero sin cliente asignado (walk-in sin asignar) --}}
    <div class="flex flex-col space-y-1">
        <div class="flex items-center gap-1.5">
            <i class="fas fa-exclamation-triangle text-yellow-600 text-xs"></i>
            <span class="text-sm text-yellow-700 font-semibold">Cliente no asignado</span>
        </div>
        <div class="text-xs text-gray-500">
            La reserva existe pero no hay cliente principal asignado.
        </div>
        @if($additionalGuests->isNotEmpty())
            <div class="text-xs text-gray-600 mt-1">
                <i class="fas fa-users mr-1"></i>
                {{ $additionalGuests->count() }} huésped(es) adicional(es)
            </div>
        @endif
        <button type="button"
                wire:click="openAssignGuests({{ $room->id }})"
                class="text-xs text-blue-600 hover:text-blue-800 underline font-medium mt-1">
            Asignar huésped
        </button>
    </div>
@else
    {{-- CASO EDGE: Stay activo pero sin reserva asociada (inconsistencia de datos) --}}
    <div class="flex flex-col space-y-1">
        <div class="flex items-center gap-1.5">
            <i class="fas fa-exclamation-circle text-orange-600 text-xs"></i>
            <span class="text-sm text-orange-700 font-semibold">Sin cuenta asociada</span>
        </div>
        <div class="text-xs text-gray-500">
            No hay reserva ligada a esta estadía.
        </div>
        <button type="button"
                wire:click="openRoomDetail({{ $room->id }})"
                class="text-xs text-blue-600 hover:text-blue-800 underline font-medium mt-1">
            Ver detalles
        </button>
    </div>
@endif

