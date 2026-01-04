@props(['room'])

@php
    $hasReservation = isset($room->current_reservation) && $room->current_reservation;
    $hasGuestName = isset($room->guest_name) && $room->guest_name;

    // Use RoomDisplayStatus (calculated UI status)
    $displayStatus = $room->display_status ?? null;
    $shouldShowDebtInfo = in_array($displayStatus, [
        \App\Enums\RoomDisplayStatus::OCUPADA,
        \App\Enums\RoomDisplayStatus::PENDIENTE_CHECKOUT,
    ]) && ($hasReservation || $hasGuestName);

    // Base price: prefer first rate (lowest min_guests), fallback to base_price_per_night
    $basePrice = null;
    if (isset($room->rates) && $room->rates instanceof \Illuminate\Support\Collection && $room->rates->isNotEmpty()) {
        $basePrice = $room->rates->sortBy('min_guests')->first()->price_per_night ?? null;
    }
    if ($basePrice === null && isset($room->base_price_per_night)) {
        $basePrice = $room->base_price_per_night;
    }
@endphp

@if($shouldShowDebtInfo)
    <div class="flex flex-col space-y-1">
        @if($hasReservation && isset($room->is_night_paid))
            @if($room->is_night_paid)
                <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                    <i class="fas fa-moon mr-1"></i> NOCHE PAGA
                </span>
            @else
                <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                    <i class="fas fa-moon mr-1"></i> NOCHE PENDIENTE
                </span>
            @endif
        @endif

        @if(isset($room->total_debt) && $room->total_debt > 0)
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
        <span class="text-sm font-semibold text-gray-900">${{ number_format($basePrice ?? 0, 0, ',', '.') }}</span>
        <span class="text-xs text-gray-400">precio base</span>
    </div>
@endif

