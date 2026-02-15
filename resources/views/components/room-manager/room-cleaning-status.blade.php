@props(['room', 'selectedDate'])

@php
    use App\Support\HotelTime;
    
    // SINGLE SOURCE OF TRUTH: El estado de limpieza SOLO depende de last_cleaned_at y stays
    // NUNCA usar getOperationalStatus() ni estados operativos aqui
    $cleaningStatus = $room->cleaningStatus($selectedDate);
    
    // El metodo cleaningStatus() retorna un array con 'code' que puede ser:
    // - 'limpia'  Habitacion limpia
    // - 'pendiente'  Pendiente por aseo
    // NUNCA retorna estados operativos como 'occupied', 'free_clean', etc.
    
    $statusConfig = match($cleaningStatus['code']) {
        'limpia' => [
            'label' => 'Limpia',
            'icon' => 'fa-check-circle',
            'color' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        ],
        'pendiente' => [
            'label' => 'Pendiente por aseo',
            'icon' => 'fa-broom',
            'color' => 'bg-yellow-100 text-yellow-700 border border-yellow-200',
        ],
        default => [
            // Fallback: si por alguna razon el codigo no es reconocido, mostrar como limpia
            'label' => 'Limpia',
            'icon' => 'fa-check-circle',
            'color' => 'bg-emerald-100 text-emerald-700 border border-emerald-200',
        ],
    };
    
    // Determinar si es fecha pasada (no permitir cambios en fechas historicas)
    $today = \Carbon\Carbon::today();
    $isPastDate = $selectedDate->copy()->startOfDay()->lt($today); // Mantener logica de fecha pasada
@endphp

<div 
    x-data="{ 
        showDropdown: false,
        currentStatus: '{{ $cleaningStatus['code'] }}',
        isLoading: false,
        isPastDate: @js($isPastDate)
    }"
    class="relative inline-block"
    @click.away="showDropdown = false">
    
    {{-- Badge clickeable (solo si NO es fecha pasada) --}}
    <button
        type="button"
        @click="!isPastDate && !isLoading && (showDropdown = !showDropdown)"
        :disabled="isPastDate || isLoading"
        :class="isPastDate || isLoading ? 'cursor-not-allowed opacity-75' : 'cursor-pointer hover:scale-105 transition-transform'"
        class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $statusConfig['color'] }}"
        title="{{ $isPastDate ? 'Estado historico (no editable)' : 'Clic para cambiar estado de limpieza' }}">
        <i class="fas {{ $statusConfig['icon'] }} mr-1.5"></i>
        <span x-text="currentStatus === 'limpia' ? 'Limpia' : 'Pendiente por aseo'"></span>
        @if(!$isPastDate)
            <i class="fas fa-chevron-down ml-1.5 text-[10px]"></i>
        @endif
    </button>
    
    {{-- Dropdown de opciones (solo si NO es fecha pasada) --}}
    @if(!$isPastDate)
        <div
            x-show="showDropdown"
            x-transition:enter="transition ease-out duration-100"
            x-transition:enter-start="transform opacity-0 scale-95"
            x-transition:enter-end="transform opacity-100 scale-100"
            x-transition:leave="transition ease-in duration-75"
            x-transition:leave-start="transform opacity-100 scale-100"
            x-transition:leave-end="transform opacity-0 scale-95"
            x-cloak
            class="absolute z-50 mt-1 w-48 bg-white rounded-lg shadow-lg border border-gray-200 py-1"
            style="display: none;">
            
            <button
                type="button"
                @click="
                    if (currentStatus !== 'limpia') {
                        isLoading = true;
                        @this.call('updateCleaningStatus', {{ $room->id }}, 'limpia').then(() => {
                            currentStatus = 'limpia';
                            showDropdown = false;
                            isLoading = false;
                        }).catch(() => {
                            isLoading = false;
                        });
                    }
                "
                :disabled="currentStatus === 'limpia' || isLoading"
                class="w-full text-left px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-emerald-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                :class="currentStatus === 'limpia' ? 'bg-emerald-50 text-emerald-700' : ''">
                <i class="fas fa-check-circle text-emerald-600"></i>
                <span>Marcar como limpia</span>
            </button>
            
            <button
                type="button"
                @click="
                    if (currentStatus !== 'pendiente') {
                        isLoading = true;
                        @this.call('updateCleaningStatus', {{ $room->id }}, 'pendiente').then(() => {
                            currentStatus = 'pendiente';
                            showDropdown = false;
                            isLoading = false;
                        }).catch(() => {
                            isLoading = false;
                        });
                    }
                "
                :disabled="currentStatus === 'pendiente' || isLoading"
                class="w-full text-left px-4 py-2 text-sm font-semibold text-gray-700 hover:bg-yellow-50 transition-colors disabled:opacity-50 disabled:cursor-not-allowed flex items-center space-x-2"
                :class="currentStatus === 'pendiente' ? 'bg-yellow-50 text-yellow-700' : ''">
                <i class="fas fa-broom text-yellow-600"></i>
                <span>Marcar como pendiente</span>
            </button>
        </div>
    @endif
</div>


