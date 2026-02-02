@props(['room', 'currentDate'])

@php
    use App\Support\HotelTime;
    
    // SINGLE SOURCE OF TRUTH: Estado operativo desde BD basado en stays y fecha seleccionada
    $isFutureDate = $currentDate->isFuture();
    $isPastDate = $currentDate->isPast() && !$currentDate->isToday();
    $selectedDate = $currentDate instanceof \Carbon\Carbon ? $currentDate : \Carbon\Carbon::parse($currentDate);
    $operationalStatus = $room->getOperationalStatus($selectedDate);
    
    // CRITICAL: isPendingCheckout() solo retorna true para HOY
    // Nunca para fechas pasadas o futuras
    $isPendingCheckout = $room->isPendingCheckout($selectedDate);
    
    // Solo permitir acciones en fecha actual (no históricas ni futuras)
    $canPerformActions = !$isFutureDate && !$isPastDate;
@endphp

<div class="flex items-center justify-end gap-1.5">
    {{-- ESTADO: free_clean (Libre y limpia) --}}
    @if($operationalStatus === 'free_clean')
        @if($selectedDate->isFuture())
            {{-- Reservar (FECHA FUTURA) --}}
            <button type="button"
                wire:click="openQuickRent({{ $room->id }})"
                wire:loading.attr="disabled"
                title="Reservar"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:border-emerald-300 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50">
                <i class="fas fa-calendar-check text-sm"></i>
                <span class="sr-only">Reservar</span>
            </button>
        @elseif($canPerformActions)
            {{-- Ocupar habitación (HOY) --}}
            <button type="button"
                wire:click="openQuickRent({{ $room->id }})"
                wire:loading.attr="disabled"
                title="Ocupar habitación"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-blue-200 bg-blue-50 text-blue-600 hover:bg-blue-100 hover:border-blue-300 transition-colors focus:outline-none focus:ring-2 focus:ring-blue-500 disabled:opacity-50">
                <i class="fas fa-key text-sm"></i>
                <span class="sr-only">Ocupar habitación</span>
            </button>

            {{-- Reservar (HOY) --}}
            <button type="button"
                wire:click="openQuickRent({{ $room->id }})"
                wire:loading.attr="disabled"
                title="Reservar"
                class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-600 hover:bg-emerald-100 hover:border-emerald-300 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50">
                <i class="fas fa-calendar-check text-sm"></i>
                <span class="sr-only">Reservar</span>
            </button>
        @endif
    @endif

    {{-- ESTADO: occupied (Ocupada) - NO pendiente de checkout --}}
    @if($operationalStatus === 'occupied' && !$isPendingCheckout && $canPerformActions && $selectedDate->isToday())
        {{-- Liberar: Solo si NO está pendiente de checkout Y es HOY --}}
        <button type="button"
            @click="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', 0, null, false);"
            title="Liberar"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-yellow-200 bg-yellow-50 text-yellow-600 hover:bg-yellow-100 hover:border-yellow-300 transition-colors focus:outline-none focus:ring-2 focus:ring-yellow-500">
            <i class="fas fa-door-open text-sm"></i>
            <span class="sr-only">Liberar</span>
        </button>
    @endif

    {{-- ESTADO: pending_checkout (Pendiente por checkout) - SOLO PARA HOY --}}
    @if($operationalStatus === 'pending_checkout' && $canPerformActions && $selectedDate->isToday())
        {{-- Continuar Estadía --}}
        <button type="button"
            wire:click="continueStay({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Continuar estadía"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-emerald-200 bg-emerald-50 text-emerald-700 hover:bg-emerald-100 hover:border-emerald-300 transition-colors focus:outline-none focus:ring-2 focus:ring-emerald-500 disabled:opacity-50">
            <i class="fas fa-redo-alt text-sm"></i>
            <span class="sr-only">Continuar</span>
        </button>
        
        {{-- Cancelar Estadía --}}
        <button type="button"
            wire:click="releaseRoom({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Cancelar estadía"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-red-200 bg-red-50 text-red-700 hover:bg-red-100 hover:border-red-300 transition-colors focus:outline-none focus:ring-2 focus:ring-red-500 disabled:opacity-50">
            <i class="fas fa-times text-sm"></i>
            <span class="sr-only">Cancelar</span>
        </button>
    @endif

    {{-- ESTADO: pending_cleaning (Pendiente por aseo) --}}
    @if($operationalStatus === 'pending_cleaning' && $canPerformActions && $selectedDate->isToday())
        {{-- Marcar como limpia: Solo si es HOY --}}
        <button type="button"
            wire:click="markRoomAsClean({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Marcar como limpia"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-green-200 bg-green-50 text-green-600 hover:bg-green-100 hover:border-green-300 transition-colors focus:outline-none focus:ring-2 focus:ring-green-500 disabled:opacity-50">
            <i class="fas fa-broom text-sm"></i>
            <span class="sr-only">Marcar como limpia</span>
        </button>
    @endif

    {{-- SIEMPRE VISIBLES (excepto fecha pasada para editar) --}}
    
    {{-- Editar habitación (no disponible en fechas pasadas ni con stays activos) --}}
    @if(!$isPastDate && !in_array($operationalStatus, ['occupied', 'pending_checkout']))
        <button type="button"
            wire:click="openRoomEdit({{ $room->id }})"
            wire:loading.attr="disabled"
            title="Editar habitación"
            class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-indigo-200 bg-indigo-50 text-indigo-600 hover:bg-indigo-100 hover:border-indigo-300 transition-colors focus:outline-none focus:ring-2 focus:ring-indigo-500 disabled:opacity-50">
            <i class="fas fa-edit text-sm"></i>
            <span class="sr-only">Editar habitación</span>
        </button>
    @endif

    {{-- Historial del día (siempre disponible) --}}
    <button type="button"
        wire:click="openRoomDailyHistory({{ $room->id }})"
        wire:loading.attr="disabled"
        title="Historial del día"
        class="inline-flex items-center justify-center w-8 h-8 rounded-lg border border-gray-200 bg-white text-gray-600 hover:bg-gray-50 hover:border-gray-300 transition-colors focus:outline-none focus:ring-2 focus:ring-gray-500 disabled:opacity-50">
        <i class="fas fa-history text-sm"></i>
        <span class="sr-only">Historial del día</span>
    </button>
</div>
