@props(['room', 'stay'])

@php
    // SINGLE SOURCE OF TRUTH: Este componente recibe $stay explícitamente
    // GUARD CLAUSE OBLIGATORIO: Si no hay stay, no hay información de cuenta para mostrar
    if (!$stay) {
        echo '<span class="text-xs text-gray-400 italic">Cuenta cerrada</span>';
        return;
    }

    // Obtener reserva desde la stay (Single Source of Truth)
    $reservation = $stay->reservation;
    
    // Calcular valores financieros
    $paymentsTotal = 0;
    $totalAmount = 0;
    $balanceDue = 0;
    $salesDebt = 0;
    
    if ($reservation) {
        // Eager load payments y sales si no están cargados
        $reservation->loadMissing(['payments', 'sales']);
        
        $paymentsTotal = (float)($reservation->payments?->sum('amount') ?? 0);
        $totalAmount = (float)($reservation->total_amount ?? 0);
        $salesDebt = (float)($reservation->sales?->where('is_paid', false)->sum('total') ?? 0);
        
        // Preferir balance_due almacenado (source of truth) si existe
        if ($reservation->balance_due !== null) {
            $balanceDue = (float)$reservation->balance_due + $salesDebt;
        } else {
            $balanceDue = ($totalAmount - $paymentsTotal) + $salesDebt;
        }
    }
    
    $paid = $paymentsTotal;
@endphp

@if($reservation)
    {{-- CASO NORMAL: Hay reserva activa con cuenta --}}
    <div class="flex flex-col space-y-1">
        {{-- Badge de estado de noche --}}
        @if(isset($room->is_night_paid))
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

        {{-- Estado financiero --}}
        @if($balanceDue > 0 && $paid > 0)
            {{-- Pago parcial --}}
            <div class="flex flex-col">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Saldo Total</span>
                <span class="text-sm font-bold text-yellow-700">${{ number_format($balanceDue, 0, ',', '.') }}</span>
                <span class="text-[10px] text-gray-500">Abonado: ${{ number_format($paid, 0, ',', '.') }}</span>
            </div>
            <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-700 border border-yellow-200">
                <i class="fas fa-exclamation-circle mr-1"></i> Parcial
            </span>
        @elseif($balanceDue > 0)
            {{-- Pendiente de pago --}}
            <div class="flex flex-col">
                <span class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Saldo Total</span>
                <span class="text-sm font-bold text-red-700">${{ number_format($balanceDue, 0, ',', '.') }}</span>
            </div>
            <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-red-100 text-red-700 border border-red-200">
                <i class="fas fa-exclamation-triangle mr-1"></i> Pendiente
            </span>
        @else
            {{-- Al día --}}
            <span class="inline-flex items-center w-fit px-2 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                <i class="fas fa-check-circle mr-1"></i> Al día
            </span>
        @endif
    </div>
@else
    {{-- CASO EDGE: Stay activo pero sin reserva asociada (inconsistencia de datos) --}}
    <div class="flex flex-col space-y-1">
        <span class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-semibold bg-yellow-100 text-yellow-700 border border-yellow-200">
            <i class="fas fa-exclamation-triangle mr-1"></i> Sin cuenta asociada
        </span>
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

