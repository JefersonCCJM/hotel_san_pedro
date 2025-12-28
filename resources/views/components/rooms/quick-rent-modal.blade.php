{{-- Quick Rent Modal Component --}}
<div x-show="quickRentModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="quickRentModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-lg overflow-hidden transform transition-all p-8 space-y-6">
            <div class="flex items-center justify-between border-b border-gray-100 pb-4">
                <div class="flex items-center space-x-3">
                    <div
                        class="w-10 h-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center">
                        <i class="fas fa-key"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Arrendar Hab. {{ $rentForm['room_number'] }}</h3>
                </div>
                <button @click="quickRentModal = false" class="text-gray-400 hover:text-gray-900"><i
                        class="fas fa-times"></i></button>
            </div>

            <div class="space-y-6">
                <div class="space-y-4">
                    <!-- Customer Selection (Main Customer) -->
                    <div class="space-y-1.5">
                        <div class="flex items-center justify-between">
                            <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">HUÉSPED
                                PRINCIPAL</label>
                            <button type="button" wire:click="openNewCustomerModal"
                                class="text-xs font-bold text-blue-600 hover:text-blue-700 flex items-center gap-1">
                                <i class="fas fa-plus-circle text-xs"></i>
                                Crear Cliente
                            </button>
                        </div>
                        <div class="relative" x-data="{ open: @entangle('showCustomerDropdown') }" @click.away="open = false; $wire.closeCustomerDropdown()">
                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-search text-sm"></i>
                            </div>
                            <input type="text"
                                wire:model.live.debounce.150ms="customerSearchTerm"
                                wire:click="openCustomerDropdown"
                                wire:focus="openCustomerDropdown"
                                wire:keydown.escape="closeCustomerDropdown"
                                class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                placeholder="Buscar por nombre, identificación o teléfono...">

                            @if ($this->rentForm['customer_id'])
                                <button type="button" wire:click="clearCustomerSelection"
                                    class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                    <i class="fas fa-times"></i>
                                </button>
                            @endif

                            <!-- Dropdown Results -->
                            @if($showCustomerDropdown)
                                <div class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-xl shadow-lg max-h-96 overflow-y-auto"
                                     wire:ignore.self>
                                    @php
                                        $filteredCustomers = $this->filteredCustomers;
                                    @endphp

                                    @if(count($filteredCustomers) > 0)
                                        @foreach($filteredCustomers as $customer)
                                            @php
                                                $customerIdValue = $customer['id'] ?? null;
                                                $customerName = $customer['name'] ?? '';
                                                $taxProfile = $customer['taxProfile'] ?? null;
                                                $identification = ($taxProfile && isset($taxProfile['identification'])) ? $taxProfile['identification'] : 'S/N';
                                                $phone = $customer['phone'] ?? 'S/N';
                                                $isSelected = (string)$customerIdValue === (string)$this->rentForm['customer_id'];
                                            @endphp
                                            @if($customerIdValue)
                                                <button type="button"
                                                        wire:click="selectCustomer({{ $customerIdValue }})"
                                                        class="w-full text-left px-4 py-3 hover:bg-emerald-50 transition-colors {{ $isSelected ? 'bg-emerald-100' : '' }} border-b border-gray-100 last:border-b-0">
                                                    <div class="flex items-center justify-between">
                                                        <div class="flex-1">
                                                            <div class="font-medium text-gray-900 text-sm">
                                                                {{ $customerName }}
                                                            </div>
                                                            <div class="text-xs text-gray-500 mt-0.5">
                                                                <span class="mr-2"><i class="fas fa-id-card mr-1"></i>{{ $identification }}</span>
                                                                @if($phone !== 'S/N')
                                                                    <span><i class="fas fa-phone mr-1"></i>{{ $phone }}</span>
                                                                @endif
                                                            </div>
                                                        </div>
                                                        @if($isSelected)
                                                            <i class="fas fa-check-circle text-emerald-600"></i>
                                                        @endif
                                                    </div>
                                                </button>
                                            @endif
                                        @endforeach

                                        @if(empty($this->customerSearchTerm) && count($this->customers ?? []) > 5)
                                            <div class="px-4 py-2 text-xs text-gray-500 text-center border-t border-gray-100 bg-gray-50">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Mostrando los 5 clientes más recientes. Escribe para buscar más.
                                            </div>
                                        @endif
                                    @else
                                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                                            @if(empty($this->customerSearchTerm))
                                                <i class="fas fa-users text-2xl mb-2 opacity-50"></i>
                                                <p>No hay clientes disponibles</p>
                                            @else
                                                <i class="fas fa-search text-2xl mb-2 opacity-50"></i>
                                                <p>No se encontraron clientes</p>
                                                <p class="text-xs mt-1">Intenta con otro término de búsqueda</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            @endif
                        </div>

                        @error('rentForm.customer_id')
                            <span class="text-[10px] font-bold text-red-600 block mt-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div class="space-y-1.5">
                            <label
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">PERSONAS</label>
                            <input type="number" wire:model.live="rentForm.people"
                                max="{{ $rentForm['max_capacity'] ?? 1 }}" min="1"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold @error('rentForm.people') border-red-500 @enderror">
                            @error('rentForm.people')
                                <span class="text-[10px] font-bold text-red-600 block mt-1">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                </span>
                            @enderror
                            @if (!$errors->has('rentForm.people') && isset($rentForm['max_capacity']))
                                <span class="text-[10px] text-gray-500 block mt-1">
                                    Capacidad máxima: {{ $rentForm['max_capacity'] }} persona(s)
                                </span>
                            @endif
                        </div>
                        <div class="space-y-1.5">
                            <label
                                class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">CHECK-OUT</label>
                            <input type="date" wire:model.live="rentForm.check_out"
                                class="w-full bg-gray-50 border border-gray-200 rounded-lg px-4 py-2.5 text-sm font-bold">
                        </div>
                    </div>

                    <!-- Guests Assignment Section -->
                    @php
                        $peopleCount = (int)($rentForm['people'] ?? 1);
                        $guestsCount = count($quickRentGuests);
                    @endphp
                    <div class="space-y-3">
                        <label class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">
                            HUÉSPEDES ASIGNADOS ({{ $guestsCount }}/{{ $peopleCount }})
                        </label>

                        @if($peopleCount === 0 || $peopleCount < 1)
                            <div class="text-center py-4 text-gray-400 bg-gray-50 rounded-xl border border-gray-100">
                                <i class="fas fa-info-circle text-lg mb-2 opacity-50"></i>
                                <p class="text-xs">Selecciona la cantidad de personas primero</p>
                            </div>
                        @else
                            <div class="space-y-2 max-h-64 overflow-y-auto">
                                @for($i = 0; $i < $peopleCount; $i++)
                                    @php
                                        // Match guest by position: slot 0 = first guest, slot 1 = second guest, etc.
                                        $guest = isset($quickRentGuests[$i]) && !empty($quickRentGuests[$i]) ? $quickRentGuests[$i] : null;
                                    @endphp
                                    @if($guest !== null)
                                        <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                            <div class="flex items-center space-x-3">
                                                <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs">
                                                    <i class="fas fa-user"></i>
                                                </div>
                                                <div>
                                                    <p class="font-bold text-gray-900 text-sm">{{ $guest['name'] ?? '' }}</p>
                                                    <div class="flex items-center space-x-2 text-xs text-gray-500 mt-0.5">
                                                        <span>ID: {{ $guest['identification'] ?? 'S/N' }}</span>
                                                        <span class="text-gray-300">•</span>
                                                        <span>Tel: {{ $guest['phone'] ?? 'S/N' }}</span>
                                                    </div>
                                                </div>
                                            </div>
                                            <button type="button" wire:click="removeQuickRentGuest({{ $i }})"
                                                    class="text-red-500 hover:text-red-700 transition-colors p-1">
                                                <i class="fas fa-times"></i>
                                            </button>
                                        </div>
                                    @else
                                        <div class="relative" x-data="{ open: false }" x-id="['guest-selector']">
                                            <button type="button" 
                                                    @click="open = !open"
                                                    class="w-full flex items-center justify-between p-3 bg-gray-50 rounded-xl border-2 border-dashed border-gray-300 hover:border-blue-400 hover:bg-blue-50 transition-colors">
                                                <div class="flex items-center space-x-3">
                                                    <div class="w-8 h-8 rounded-full bg-gray-200 text-gray-400 flex items-center justify-center text-xs">
                                                        <i class="fas fa-user-plus"></i>
                                                    </div>
                                                    <div class="text-left">
                                                        <p class="font-medium text-gray-500 text-sm">Huésped {{ $i + 1 }}</p>
                                                        <p class="text-[10px] text-gray-400 mt-0.5">Clic para asignar</p>
                                                    </div>
                                                </div>
                                                <i class="fas fa-chevron-down text-gray-400 text-xs transition-transform" :class="{ 'rotate-180': open }"></i>
                                            </button>
                                            
                                            <div x-show="open" 
                                                 @click.away="open = false"
                                                 x-cloak
                                                 class="absolute z-50 w-full mt-1 bg-white border border-gray-300 rounded-xl shadow-lg max-h-64 overflow-y-auto">
                                                @php
                                                    $filteredCustomers = $this->filteredCustomers;
                                                    $existingGuestIds = array_column($quickRentGuests, 'id');
                                                @endphp
                                                
                                                @if(count($filteredCustomers) > 0)
                                                    @foreach($filteredCustomers as $customer)
                                                        @php
                                                            $customerIdValue = $customer['id'] ?? null;
                                                            $customerName = $customer['name'] ?? '';
                                                            $taxProfile = $customer['taxProfile'] ?? null;
                                                            $identification = ($taxProfile && isset($taxProfile['identification'])) ? $taxProfile['identification'] : 'S/N';
                                                            $phone = $customer['phone'] ?? 'S/N';
                                                            $isAlreadyAdded = in_array($customerIdValue, $existingGuestIds);
                                                        @endphp
                                                        @if($customerIdValue && !$isAlreadyAdded)
                                                            <button type="button"
                                                                    wire:click="addGuestToQuickRent({{ $customerIdValue }}, {{ $i }})"
                                                                    @click="open = false"
                                                                    class="w-full text-left px-4 py-3 hover:bg-emerald-50 transition-colors border-b border-gray-100 last:border-b-0">
                                                                <div class="font-medium text-gray-900 text-sm">{{ $customerName }}</div>
                                                                <div class="text-xs text-gray-500 mt-0.5">
                                                                    <span class="mr-2"><i class="fas fa-id-card mr-1"></i>{{ $identification }}</span>
                                                                    @if($phone !== 'S/N')
                                                                        <span><i class="fas fa-phone mr-1"></i>{{ $phone }}</span>
                                                                    @endif
                                                                </div>
                                                            </button>
                                                        @endif
                                                    @endforeach
                                                @else
                                                    <div class="px-4 py-4 text-center text-sm text-gray-500">
                                                        <i class="fas fa-search text-xl mb-2 opacity-50"></i>
                                                        <p>No se encontraron clientes disponibles</p>
                                                    </div>
                                                @endif
                                            </div>
                                        </div>
                                    @endif
                                @endfor
                            </div>
                        @endif

                        @error('quickRentGuests')
                            <span class="text-[10px] font-bold text-red-600 block mt-1">
                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <div class="grid grid-cols-2 gap-4 p-6 bg-gray-50 rounded-2xl border border-gray-100">
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Total Hospedaje</p>
                            <input type="number" wire:model="rentForm.total"
                                class="bg-transparent text-lg font-bold text-gray-900 focus:outline-none w-full">
                        </div>
                        <div class="space-y-1">
                            <p class="text-[10px] font-bold text-gray-400 uppercase">Abono Inicial</p>
                            <input type="number" wire:model="rentForm.deposit"
                                class="bg-transparent text-lg font-bold text-emerald-600 focus:outline-none w-full">
                        </div>
                        <div class="space-y-1 col-span-2 pt-2 border-t border-gray-200 mt-2">
                            <p class="text-[10px] font-bold text-gray-400 uppercase mb-1">Método de Pago del Abono
                            </p>
                            <select wire:model="rentForm.payment_method"
                                class="w-full bg-white border border-gray-200 rounded-lg px-3 py-1.5 text-xs font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                <option value="efectivo">Efectivo</option>
                                <option value="transferencia">Transferencia</option>
                            </select>
                        </div>
                    </div>
                </div>

                <button wire:click="storeQuickRent" wire:loading.attr="disabled" wire:target="storeQuickRent"
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
