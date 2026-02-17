@props(['assignGuestsForm'])

<div x-show="assignGuestsModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
    @if($assignGuestsForm)
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="$wire.closeAssignGuests()" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">
                        {{ !empty($assignGuestsForm['has_customer']) ? 'Editar Ocupación' : 'Completar Reserva' }}
                    </h3>
                </div>
                <button @click="$wire.closeAssignGuests()" class="text-gray-400 hover:text-gray-900"><i class="fas fa-times text-xl"></i></button>
            </div>

            <div class="space-y-6">
                {{-- Fechas de ocupación --}}
                <div class="space-y-1.5">
                    <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">FECHAS DE OCUPACION</label>
                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-3">
                        <div class="space-y-1">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Entrada</label>
                            <input type="date"
                                   wire:model.live="assignGuestsForm.check_in_date"
                                   class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            @error('assignGuestsForm.check_in_date')
                                <p class="text-[10px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                        <div class="space-y-1">
                            <label class="text-[10px] font-semibold text-gray-500 uppercase tracking-wider">Salida</label>
                            <input type="date"
                                   wire:model.live="assignGuestsForm.check_out_date"
                                   min="{{ $assignGuestsForm['check_in_date'] ?? now()->toDateString() }}"
                                   class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            @error('assignGuestsForm.check_out_date')
                                <p class="text-[10px] text-red-600">{{ $message }}</p>
                            @enderror
                        </div>
                    </div>
                    <p class="text-[10px] text-gray-500">
                        Puede ajustar check-in y check-out para esta ocupación activa.
                    </p>
                </div>

                {{-- Cliente Principal (OBLIGATORIO) --}}
                <div class="space-y-1.5">
                    <div class="flex items-center justify-between mb-1">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">
                            CLIENTE PRINCIPAL <span class="text-red-500">*</span>
                        </label>
                        <button type="button" 
                                @click="$dispatch('open-create-customer-modal')"
                                class="text-[9px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-tighter flex items-center gap-1">
                            <i class="fas fa-plus text-[8px]"></i>
                            Nuevo Cliente
                        </button>
                    </div>
                    <div wire:ignore>
                        <select id="assign_guests_customer_id" 
                                class="w-full bg-white border border-gray-200 rounded-xl px-3 py-2.5 text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                data-placeholder="Seleccione un cliente">
                            <option value="">Seleccione un cliente...</option>
                        </select>
                    </div>
                    @error('assignGuestsForm.client_id')
                        <p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>
                    @enderror
                </div>

                {{-- Información de Capacidad y Huéspedes --}}
                @php
                    $additionalGuestsCount = !empty($assignGuestsForm['additional_guests']) && is_array($assignGuestsForm['additional_guests']) 
                        ? count($assignGuestsForm['additional_guests']) 
                        : 0;
                    $principalCount = !empty($assignGuestsForm['client_id']) ? 1 : 0;
                    $totalPeople = $principalCount + $additionalGuestsCount;
                    $maxCapacity = (int)($assignGuestsForm['max_capacity'] ?? 1);
                    $remaining = $maxCapacity - $totalPeople;
                @endphp
                <div class="space-y-1.5 p-4 bg-gray-50 rounded-lg border border-gray-200">
                    <div class="flex items-center justify-between mb-2">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest">HUÉSPEDES</label>
                        <span class="text-[9px] text-gray-500 font-medium">Cap. máx: {{ $maxCapacity }}</span>
                    </div>
                    <div class="w-full bg-white border border-gray-200 rounded-lg px-4 py-2.5 flex items-center justify-between">
                        <div class="flex items-center space-x-2">
                            <i class="fas fa-users text-gray-400 text-sm"></i>
                            <span class="text-sm font-bold text-gray-900">{{ $totalPeople }} {{ $totalPeople == 1 ? 'persona' : 'personas' }}</span>
                        </div>
                        <span class="text-[10px] text-gray-500">/ {{ $maxCapacity }}</span>
                    </div>
                    @if($totalPeople === 0)
                        <p class="text-[10px] text-amber-600 mt-1 flex items-center">
                            <i class="fas fa-exclamation-triangle mr-1 text-[8px]"></i>
                            Debe seleccionar un cliente principal
                        </p>
                    @elseif($totalPeople > $maxCapacity)
                        <p class="text-[10px] text-red-600 mt-1 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                            Excede la capacidad máxima. Total: {{ $totalPeople }}/{{ $maxCapacity }}
                        </p>
                    @elseif($remaining > 0)
                        <p class="text-[10px] text-gray-500 mt-1">
                            <i class="fas fa-info-circle mr-1 text-[8px]"></i>
                            Puede agregar {{ $remaining }} {{ $remaining == 1 ? 'persona más' : 'personas más' }}
                        </p>
                    @else
                        <p class="text-[10px] text-emerald-600 mt-1">
                            <i class="fas fa-check-circle mr-1 text-[8px]"></i>
                            Capacidad máxima alcanzada ({{ $totalPeople }}/{{ $maxCapacity }})
                        </p>
                    @endif
                </div>

                {{-- Huéspedes Adicionales (OPCIONAL) --}}
                <div class="space-y-2 pt-2 border-t border-gray-100" x-data="{ showGuestSearch: false }">
                    <div class="flex items-center justify-between">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">HUÉSPEDES ADICIONALES</label>
                        <button type="button" 
                                x-show="!showGuestSearch"
                                @click="showGuestSearch = true"
                                class="text-[9px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-tighter flex items-center gap-1">
                            <i class="fas fa-user-plus text-[8px]"></i>
                            Agregar
                        </button>
                    </div>

                    {{-- Lista de huéspedes adicionales actuales --}}
                    @if(!empty($assignGuestsForm['additional_guests']) && count($assignGuestsForm['additional_guests']) > 0)
                        <div class="space-y-1.5">
                            @foreach($assignGuestsForm['additional_guests'] as $index => $guest)
                                <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-200">
                                    <div class="flex items-center space-x-2">
                                        <i class="fas fa-user text-gray-400 text-xs"></i>
                                        <div>
                                            <span class="text-xs font-semibold text-gray-900">{{ $guest['name'] ?? 'N/A' }}</span>
                                            @if(!empty($guest['identification']))
                                                <span class="text-[10px] text-gray-500 ml-2">{{ $guest['identification'] }}</span>
                                            @endif
                                        </div>
                                    </div>
                                    <button type="button"
                                            wire:click="$wire.removeAssignGuest({{ $index }})"
                                            class="text-red-500 hover:text-red-700">
                                        <i class="fas fa-times text-xs"></i>
                                    </button>
                                </div>
                            @endforeach
                        </div>
                    @endif

                    {{-- Selector de búsqueda para agregar huésped --}}
                    <div x-show="showGuestSearch" 
                         x-transition
                         x-init="setTimeout(() => { 
                            const event = new CustomEvent('init-assign-additional-guest-select');
                            document.dispatchEvent(event);
                         }, 100)"
                         class="space-y-2 p-3 bg-gray-50 rounded-lg border border-gray-200" 
                         x-cloak>
                        <div class="flex items-center justify-between mb-2">
                            <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest">Buscar Cliente</label>
                            <button type="button" 
                                    @click="showGuestSearch = false; if (typeof window.assignAdditionalGuestSelect !== 'undefined' && window.assignAdditionalGuestSelect) { window.assignAdditionalGuestSelect.destroy(); window.assignAdditionalGuestSelect = null; }"
                                    class="text-gray-400 hover:text-gray-600">
                                <i class="fas fa-times text-xs"></i>
                            </button>
                        </div>
                        <div wire:ignore>
                            <select id="assign_additional_guest_customer_id" class="w-full"></select>
                        </div>
                        <div class="flex gap-2">
                            <button type="button" 
                                    @click="showGuestSearch = false"
                                    class="flex-1 px-3 py-1.5 text-[10px] font-bold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                                Cancelar
                            </button>
                            <button type="button" 
                                    @click="$dispatch('open-create-customer-modal-for-additional'); showGuestSearch = false"
                                    class="flex-1 px-3 py-1.5 text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                <i class="fas fa-plus mr-1 text-[8px]"></i>
                                Crear Nuevo
                            </button>
                        </div>
                    </div>
                </div>

                {{-- Opción: Cambiar precio del hospedaje (OPCIONAL) --}}
                <div class="space-y-2 pt-2 border-t border-gray-100">
                    <label class="flex items-center space-x-2 cursor-pointer">
                        <input type="checkbox" 
                               wire:model.live="assignGuestsForm.override_total_amount"
                               class="w-4 h-4 text-blue-600 border-gray-300 rounded focus:ring-blue-500">
                        <span class="text-xs font-semibold text-gray-700">Cambiar precio del hospedaje</span>
                    </label>

                    @if($assignGuestsForm['override_total_amount'] ?? false)
                        <div class="p-4 bg-yellow-50 border border-yellow-200 rounded-lg">
                            <label class="text-[10px] font-bold text-yellow-700 uppercase tracking-widest ml-1">
                                NUEVO TOTAL DEL HOSPEDAJE
                            </label>
                            <input type="number" 
                                   wire:model="assignGuestsForm.total_amount"
                                   step="0.01" 
                                   min="{{ max(0.01, $assignGuestsForm['current_paid_amount'] ?? 0) }}"
                                   class="w-full mt-2 px-4 py-2.5 bg-white border-2 border-yellow-300 rounded-lg text-lg font-bold text-gray-900 focus:ring-2 focus:ring-yellow-500 focus:border-yellow-500 outline-none"
                                   placeholder="0.00">
                            <p class="text-[10px] text-yellow-600 mt-1">
                                Mínimo permitido: ${{ number_format($assignGuestsForm['current_paid_amount'] ?? 0, 0, ',', '.') }} (ya pagado)
                            </p>
                            @error('assignGuestsForm.total_amount')
                                <p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>
                            @enderror
                        </div>
                    @else
                        <div class="p-4 bg-gray-50 border border-gray-200 rounded-lg">
                            <div class="flex items-center justify-between">
                                <span class="text-[10px] font-bold text-gray-600 uppercase">Precio Actual (SSOT)</span>
                                <span class="text-lg font-bold text-gray-900">${{ number_format($assignGuestsForm['total_amount'] ?? 0, 0, ',', '.') }}</span>
                            </div>
                            <p class="text-[10px] text-gray-500 mt-1">
                                El precio actual se mantendrá. Activa la opción arriba para cambiarlo.
                            </p>
                        </div>
                    @endif
                </div>

                {{-- Botones de acción --}}
                @php
                    // 🔐 VALIDACIÓN: Deshabilitar botón si se excede la capacidad
                    $isCapacityExceeded = $totalPeople > $maxCapacity;
                    $buttonClass = $isCapacityExceeded ? 'bg-gray-400 cursor-not-allowed' : 'bg-blue-600 hover:bg-blue-700';
                    $buttonText = $isCapacityExceeded ? 'No se puede guardar (Excede capacidad)' : 'Guardar Asignación';
                @endphp
                <div class="flex gap-3 pt-4 border-t border-gray-100">
                    <button type="button"
                            @click="$wire.closeAssignGuests()"
                            class="flex-1 px-4 py-3 text-sm font-bold text-gray-700 bg-gray-100 rounded-xl hover:bg-gray-200 transition-colors">
                        Cancelar
                    </button>
                    <button type="button"
                            wire:click="submitAssignGuests"
                            @if($isCapacityExceeded) disabled @endif
                            class="flex-1 px-4 py-3 text-sm font-bold text-white {{ $buttonClass }} rounded-xl transition-colors shadow-sm disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-check mr-2"></i>
                        {{ $buttonText }}
                    </button>
                </div>
            </div>
        </div>
    </div>
    @endif
</div>
