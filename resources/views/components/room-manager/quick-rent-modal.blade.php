@props(['rentForm', 'additionalGuests'])

<div x-show="quickRentModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="quickRentModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Arrendar Hab. {{ $rentForm['room_number'] }}</h3>
                </div>
                <button @click="quickRentModal = false" class="text-gray-400 hover:text-gray-900"><i class="fas fa-times text-xl"></i></button>
            </div>

            <div class="space-y-6">
                <div class="space-y-4">
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between mb-1">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">HUÉSPED PRINCIPAL</label>
                            <button type="button" 
                                    @click="$dispatch('open-create-customer-modal')"
                                    class="text-[9px] font-bold text-blue-600 hover:text-blue-800 uppercase tracking-tighter flex items-center gap-1">
                                <i class="fas fa-plus text-[8px]"></i>
                                Nuevo Cliente
                            </button>
                        </div>
                        <div wire:ignore>
                        <select id="quick_customer_id" class="w-full"></select>
                        </div>
                        @error('rentForm.customer_id')
                            <p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <div class="flex items-center justify-between mb-1">
                                <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">PERSONAS</label>
                                <span class="text-[9px] text-gray-500 font-medium">Cap. máx: {{ $rentForm['max_capacity'] ?? 1 }}</span>
                            </div>
                            @php
                                $additionalGuestsCount = is_array($additionalGuests) ? count($additionalGuests) : 0;
                                $principalCount = !empty($rentForm['customer_id']) ? 1 : 0;
                                $totalPeople = $principalCount + $additionalGuestsCount;
                                $maxCapacity = (int)($rentForm['max_capacity'] ?? 1);
                                $remaining = $maxCapacity - $totalPeople;
                            @endphp
                            <div class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 flex items-center justify-between">
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
                        <div class="space-y-1.5">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">CHECK-OUT</label>
                            <input type="date" wire:model.live="rentForm.check_out" class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold">
                        </div>
                    </div>

                    <!-- Huéspedes Adicionales -->
                    <div class="space-y-2 pt-2 border-t border-gray-100" x-data="{ showGuestSearch: false }">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">HUÉSPEDES ADICIONALES</label>
                            <button type="button" 
                                    x-show="!showGuestSearch"
                                    @click="showGuestSearch = true"
                                    class="text-[9px] font-bold text-emerald-600 hover:text-emerald-800 uppercase tracking-tighter flex items-center gap-1">
                                <i class="fas fa-user-plus text-[8px]"></i>
                                Agregar
                            </button>
                        </div>

                        <!-- Selector de búsqueda -->
                        <div x-show="showGuestSearch" 
                             x-transition
                             x-init="setTimeout(() => { 
                                const event = new CustomEvent('init-additional-guest-select');
                                document.dispatchEvent(event);
                             }, 100)"
                             class="space-y-2 p-3 bg-gray-50 rounded-lg border border-gray-200" 
                             x-cloak>
                            <div class="flex items-center justify-between mb-2">
                                <label class="text-[10px] font-bold text-gray-700 uppercase tracking-widest">Buscar Cliente</label>
                                <button type="button" 
                                        @click="showGuestSearch = false; if (typeof window.additionalGuestSelect !== 'undefined' && window.additionalGuestSelect) { window.additionalGuestSelect.destroy(); window.additionalGuestSelect = null; }"
                                        class="text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times text-xs"></i>
                                </button>
                            </div>
                            <div wire:ignore>
                                <select id="additional_guest_customer_id" class="w-full"></select>
                            </div>
                            <div class="flex gap-2">
                                <button type="button" 
                                        @click="showGuestSearch = false"
                                        class="flex-1 px-3 py-1.5 text-[10px] font-bold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50">
                                    Cancelar
                                </button>
                                <button type="button" 
                                        @click="$dispatch('open-create-customer-modal'); showGuestSearch = false"
                                        class="flex-1 px-3 py-1.5 text-[10px] font-bold text-blue-600 bg-blue-50 border border-blue-200 rounded-lg hover:bg-blue-100">
                                    <i class="fas fa-plus mr-1 text-[8px]"></i>
                                    Crear Nuevo
                                </button>
                            </div>
                        </div>

                        @if(!empty($additionalGuests) && is_array($additionalGuests))
                            <div class="space-y-2 max-h-32 overflow-y-auto">
                                @foreach($additionalGuests as $index => $guest)
                                    <div class="flex items-center justify-between p-2 bg-gray-50 rounded-lg border border-gray-200">
                                        <div class="flex-1">
                                            <p class="text-xs font-bold text-gray-900">{{ $guest['name'] }}</p>
                                            <p class="text-[10px] text-gray-500">ID: {{ $guest['identification'] }}</p>
                                        </div>
                                        <button type="button" 
                                                wire:click="removeGuest({{ $index }})"
                                                class="text-red-500 hover:text-red-700 ml-2">
                                            <i class="fas fa-times text-xs"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @else
                            <p class="text-[10px] text-gray-400 italic">No hay huéspedes adicionales registrados</p>
                        @endif
                        @error('additionalGuests')
                            <p class="text-[10px] text-red-600 mt-1">{{ $message }}</p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 p-6 bg-gray-50 rounded-2xl border border-gray-100">
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Total Hospedaje</p>
                            <input type="number" wire:model.live="rentForm.total" step="0.01" min="0" class="bg-transparent text-lg font-bold text-gray-900 focus:outline-none w-full">
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Abono Inicial</p>
                            <input type="number" wire:model="rentForm.deposit" class="bg-transparent text-lg font-bold text-emerald-600 focus:outline-none w-full">
                        </div>
                        <div class="space-y-1 col-span-2 pt-2 border-t border-gray-200 mt-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Método de Pago del Abono</p>
                            <select wire:model="rentForm.payment_method" class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button wire:click="storeQuickRent" 
                        wire:loading.attr="disabled"
                        wire:target="storeQuickRent"
                        class="w-full bg-blue-600 text-white py-4 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="storeQuickRent">Confirmar Arrendamiento</span>
                    <span wire:loading wire:target="storeQuickRent" class="flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Procesando...
                    </span>
                </button>
            </div>
        </div>
    </div>
</div>

