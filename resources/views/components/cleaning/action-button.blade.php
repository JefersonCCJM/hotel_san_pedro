@props([
    'roomId' => null,
    'roomNumber' => null,
    'canMarkClean' => false,
    'status' => [],
])

@if($canMarkClean)
    <button 
        wire:click="markAsClean({{ $roomId }})"
        wire:loading.attr="disabled"
        type="button"
        onclick="console.log('Button clicked, roomId: {{ $roomId }}')"
        class="w-full py-4 px-6 bg-gradient-to-r from-green-600 to-green-700 hover:from-green-700 hover:to-green-800 disabled:from-gray-400 disabled:to-gray-500 text-white font-bold rounded-xl transition-all duration-200 flex items-center justify-center space-x-3 shadow-lg hover:shadow-xl disabled:cursor-not-allowed disabled:opacity-60 text-base">
        <i class="fas fa-check-circle" wire:loading.remove></i>
        <i class="fas fa-spinner fa-spin" wire:loading></i>
        <span wire:loading.remove>Marcar como Limpia</span>
        <span wire:loading>Procesando...</span>
    </button>
@else
    <div class="w-full py-4 px-6 bg-gray-100 border-2 border-gray-300 text-gray-600 font-bold rounded-xl text-center text-sm">
        @if($status['code'] === 'ocupada')
            <i class="fas fa-user-slash mr-2"></i>Habitación ocupada
        @elseif($status['code'] === 'pendiente_checkout')
            <i class="fas fa-clock mr-2"></i>Pendiente checkout
        @elseif($status['code'] === 'libre')
            <i class="fas fa-check-circle mr-2"></i>Habitación disponible
        @else
            <i class="fas fa-ban mr-2"></i>No disponible
        @endif
    </div>
@endif

