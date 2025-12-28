<div>
    <!-- Header Contextual -->
    <div class="mb-6 bg-white rounded-2xl border border-gray-100 shadow-sm">
        <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
            <div class="flex items-center space-x-3">
                <div class="p-3 rounded-2xl bg-emerald-100 text-emerald-600 shadow-sm">
                    <i class="fas fa-calendar-plus text-2xl"></i>
                </div>
                <div>
                    <h1 class="text-2xl font-bold text-gray-900 leading-tight">Nueva Reserva</h1>
                    <p class="text-sm text-gray-500">Configura la estancia y pagos del huésped</p>
                </div>
            </div>

            <div class="flex items-center space-x-3">
                <a href="{{ route('reservations.index') }}" class="px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                    Cancelar
                </a>
                <button type="submit" form="reservation-form"
                        @if($this->loading) disabled @endif
                        class="px-6 py-2 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-all flex items-center">
                    <i class="fas fa-save mr-2"></i>
                    Confirmar Reserva
                </button>
            </div>
        </div>
    </div>


    <form id="reservation-form" method="POST" action="{{ route('reservations.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6" onsubmit="return validateFormBeforeSubmit(event)">
        @csrf

        <!-- Columna Principal (2/3) -->
        <div class="lg:col-span-2 space-y-6">

            <!-- SECCIÓN 1: CLIENTE -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm">
                <div class="p-5 border-b border-gray-50 flex items-center justify-between bg-gray-50/50">
                    <div class="flex items-center space-x-2">
                        <i class="fas fa-user-circle text-blue-500"></i>
                        <h2 class="font-bold text-gray-800">Información del Cliente</h2>
                    </div>
                    <button type="button" wire:click="openNewCustomerModal" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center">
                        <i class="fas fa-plus-circle mr-1"></i> NUEVO CLIENTE
                    </button>
                </div>
                <div class="p-6">
                    @if(!$this->datesCompleted && ($this->checkIn || $this->checkOut))
                        <div class="mb-4 p-3 bg-amber-50 border border-amber-200 rounded-xl">
                            <p class="text-xs text-amber-800 flex items-center">
                                <i class="fas fa-info-circle mr-2"></i>
                                <span>Por favor, completa las fechas de Check-In y Check-Out para continuar con el resto del formulario.</span>
                            </p>
                        </div>
                    @endif
                    <div class="grid grid-cols-1 gap-4">
                        <div class="relative" id="customer-selector-container">
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Seleccionar Huésped</label>

                            <!-- Search Input -->
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-search text-sm"></i>
                                </div>
                                <input type="text"
                                       wire:model.debounce.300ms="customerSearchTerm"
                                       wire:click="openCustomerDropdown"
                                       wire:focus="openCustomerDropdown"
                                       wire:keydown.escape="closeCustomerDropdown"
                                       @if(!$this->datesCompleted) disabled @endif
                                       class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @if(!$this->datesCompleted) bg-gray-100 cursor-not-allowed @endif"
                                       placeholder="Buscar por nombre, identificación o teléfono...">

                                @if($this->customerId)
                                    <button type="button"
                                            wire:click="clearCustomerSelection"
                                            class="absolute inset-y-0 right-0 pr-3 flex items-center text-gray-400 hover:text-gray-600">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif
                            </div>

                            <!-- Hidden input for form submission -->
                            <input type="hidden" name="customer_id" value="{{ old('customer_id', $this->customerId) }}">
                            @error('customer_id')
                                <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                                </span>
                            @enderror

                            <!-- Dropdown Results -->
                            @if($showCustomerDropdown && $this->datesCompleted)
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
                                                $identification = $customer['taxProfile']['identification'] ?? 'S/N';
                                                $phone = $customer['phone'] ?? 'S/N';
                                                $isSelected = (string)$customerIdValue === (string)$this->customerId;
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

                            <!-- Selected Customer Display -->
                            @if($this->customerId && !$showCustomerDropdown)
                                @php
                                    $selectedCustomer = collect($this->customers)->first(function($customer) {
                                        return (string)($customer['id'] ?? '') === (string)$this->customerId;
                                    });
                                @endphp
                                @if($selectedCustomer)
                                    <div class="mt-2 p-3 bg-emerald-50 rounded-xl border border-emerald-200 flex items-center justify-between">
                                        <div class="flex items-center space-x-3">
                                            <i class="fas fa-check-circle text-emerald-600"></i>
                                            <div>
                                                <div class="font-medium text-gray-900 text-sm">
                                                    {{ $selectedCustomer['name'] ?? '' }}
                                                </div>
                                                <div class="text-xs text-gray-600">
                                                    {{ $selectedCustomer['taxProfile']['identification'] ?? 'S/N' }}
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button"
                                                wire:click="clearCustomerSelection"
                                                class="text-gray-400 hover:text-gray-600">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endif
                            @endif
                        </div>

                        <!-- Info Preview del Cliente Seleccionado (solo si no está abierto el dropdown) -->
                        @if($this->selectedCustomerInfo && !$showCustomerDropdown)
                            <div class="mt-2 p-3 bg-blue-50 rounded-xl flex items-center justify-between border border-blue-100 transition-all animate-fadeIn">
                                <div class="flex items-center space-x-4 text-sm text-blue-800">
                                    <div class="flex items-center">
                                        <i class="fas fa-id-card mr-2 opacity-60"></i>
                                        <span>{{ $this->selectedCustomerInfo['id'] ?? 'S/N' }}</span>
                                    </div>
                                    <div class="flex items-center border-l border-blue-200 pl-4">
                                        <i class="fas fa-phone mr-2 opacity-60"></i>
                                        <span>{{ $this->selectedCustomerInfo['phone'] ?? 'S/N' }}</span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full uppercase">Verificado</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: HABITACIÓN Y FECHAS -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-visible">
                <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex items-center">
                    <i class="fas fa-bed text-emerald-500 mr-2"></i>
                    <h2 class="font-bold text-gray-800">Estancia y Habitación</h2>
                </div>
                <div class="p-6 space-y-6">
                    <!-- FECHAS PRIMERO -->
                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                        <!-- Fecha Entrada -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Check-In</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-calendar-alt text-sm"></i>
                                </div>
                                <input type="date" name="check_in_date" wire:model.live="checkIn" value="{{ old('check_in_date', $this->checkIn) }}" required
                                       class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('checkIn') border-red-500 @enderror">
                                @error('checkIn')
                                    <span class="mt-1 text-[10px] font-bold text-red-500 uppercase tracking-tighter block">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>

                        <!-- Fecha Salida -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Check-Out</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-door-open text-sm"></i>
                                </div>
                                <input type="date" name="check_out_date" wire:model.live="checkOut" value="{{ old('check_out_date', $this->checkOut) }}" required
                                       class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('checkOut') border-red-500 @enderror @error('check_out_date') border-red-500 @enderror">
                            </div>
                            @error('checkOut')
                                <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                                </span>
                            @enderror
                            @error('check_out_date')
                                <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <!-- Hora de Ingreso -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Hora de Ingreso</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-clock text-sm"></i>
                                </div>
                                <input type="time" name="check_in_time" wire:model.live="checkInTime" value="{{ old('check_in_time', $this->checkInTime) }}"
                                       class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('checkInTime') border-red-500 @enderror @error('check_in_time') border-red-500 @enderror">
                                @error('checkInTime')
                                    <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                                    </span>
                                @enderror
                                @error('check_in_time')
                                    <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>
                        </div>
                    </div>

                    <div class="space-y-4">
                        <!-- Selector de Habitaciones (Múltiples) -->
                        <div>
                            <div class="flex items-center justify-between mb-2">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider">Habitaciones Disponibles</label>
                                <button type="button" wire:click="toggleMultiRoomMode"
                                        class="text-xs font-bold text-emerald-600 hover:text-emerald-800 flex items-center">
                                    <i class="fas {{ $this->showMultiRoomSelector ? 'fa-check-circle' : 'fa-plus-circle' }} mr-1"></i>
                                    <span>{{ $this->showMultiRoomSelector ? 'Usar una habitación' : 'Seleccionar múltiples habitaciones' }}</span>
                                </button>
                            </div>

                            <!-- Selector unificado (panel scrollable) para single y multi -->
                            <div class="space-y-3">
                                @if(!$this->datesCompleted)
                                    <div class="bg-amber-50 text-amber-700 border-amber-100 p-3 rounded-xl border text-xs font-medium flex items-center">
                                        <i class="fas fa-exclamation-circle mr-2"></i>
                                        <span>Completa las fechas para ver las habitaciones disponibles</span>
                                    </div>
                                @endif

                                <div class="space-y-2">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Seleccionar habitación
                                    </label>
                                    @if($this->datesCompleted)
                                        <div class="border border-gray-300 rounded-xl bg-white max-h-72 overflow-y-auto @error('room_id') border-red-500 @enderror @error('room_ids') border-red-500 @enderror">
                                            @php
                                                $filteredRooms = $this->filteredRooms;
                                            @endphp

                                            @if(is_array($filteredRooms) && count($filteredRooms) > 0)
                                                <div class="px-4 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest bg-gray-50 border-b border-gray-100 sticky top-0 flex items-center justify-between">
                                                    <span>
                                                        <i class="fas fa-hand-point-up mr-1"></i>
                                                        Desliza para ver más habitaciones
                                                    </span>
                                                    @if($this->showMultiRoomSelector && is_array($this->selectedRoomIds) && count($this->selectedRoomIds) > 0)
                                                        <button type="button" wire:click="clearSelectedRooms"
                                                                class="text-[10px] font-black text-red-600 hover:text-red-800 uppercase tracking-widest">
                                                            Limpiar
                                                        </button>
                                                    @endif
                                                </div>
                                                @foreach($filteredRooms as $room)
                                                    @php
                                                        $roomId = (int)($room['id'] ?? 0);
                                                        $roomNumber = (string)($room['room_number'] ?? '');
                                                        $beds = (int)($room['beds_count'] ?? 0);
                                                        $capacity = (int)($room['max_capacity'] ?? 0);
                                                        $isSelectedSingle = !$this->showMultiRoomSelector && !empty($this->roomId) && (int)$this->roomId === $roomId;
                                                        $isSelectedMulti = $this->showMultiRoomSelector && is_array($this->selectedRoomIds) && in_array($roomId, array_map('intval', $this->selectedRoomIds), true);
                                                        $isSelected = $isSelectedSingle || $isSelectedMulti;
                                                    @endphp
                                                    @if($roomId > 0)
                                                        <button type="button"
                                                                wire:click="selectRoom({{ $roomId }})"
                                                                class="w-full text-left px-4 py-3 transition-colors border-b border-gray-100 last:border-b-0 {{ $isSelected ? 'bg-emerald-50' : 'hover:bg-emerald-50' }}">
                                                            <div class="flex items-center justify-between">
                                                                <div class="flex-1">
                                                                    <div class="font-bold text-gray-900 text-sm">
                                                                        Habitación {{ $roomNumber }}
                                                                    </div>
                                                                    <div class="text-xs text-gray-500 mt-0.5">
                                                                        <span class="mr-2"><i class="fas fa-bed mr-1"></i>{{ $beds }} {{ $beds == 1 ? 'Cama' : 'Camas' }}</span>
                                                                        <span><i class="fas fa-users mr-1"></i>Capacidad {{ $capacity }}</span>
                                                                    </div>
                                                                </div>
                                                                @if($this->showMultiRoomSelector)
                                                                    <i class="fas {{ $isSelected ? 'fa-check-square text-emerald-600' : 'fa-square text-gray-300' }}"></i>
                                                                @else
                                                                    <i class="fas {{ $isSelected ? 'fa-check-circle text-emerald-600' : 'fa-circle text-gray-300' }}"></i>
                                                                @endif
                                                            </div>
                                                        </button>
                                                    @endif
                                                @endforeach
                                            @else
                                                <div class="px-4 py-6 text-center text-sm text-gray-500">
                                                    <i class="fas fa-door-closed text-2xl mb-2 opacity-50"></i>
                                                    <p>No hay habitaciones disponibles para estas fechas</p>
                                                </div>
                                            @endif
                                        </div>
                                    @else
                                        <div class="border border-gray-200 rounded-xl bg-gray-50 p-4 text-xs text-gray-500">
                                            Selecciona las fechas para ver las habitaciones disponibles.
                                        </div>
                                    @endif
                                </div>

                                @error('room_id')
                                    <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                                    </span>
                                @enderror
                                @error('room_ids')
                                    <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                                    </span>
                                @enderror

                                <!-- Chips seleccionadas -->
                                @if(!$this->showMultiRoomSelector && $this->selectedRoom)
                                    <div class="flex flex-wrap items-center gap-2">
                                        <span class="inline-flex items-center px-3 py-1.5 rounded-xl bg-emerald-50 text-emerald-700 text-xs font-bold border border-emerald-100">
                                            <i class="fas fa-bed mr-2"></i>
                                            Habitación {{ $this->selectedRoom['number'] ?? $this->selectedRoom['room_number'] ?? '' }}
                                        </span>
                                    </div>
                                @endif

                                @if($this->showMultiRoomSelector && is_array($this->selectedRoomIds) && count($this->selectedRoomIds) > 0)
                                    <!-- Intentionally hidden: user requested no counter/summary below in multi mode -->
                                @endif
                            </div>
                        </div>

                        <!-- Detalles Habitación (modo una habitación) -->
                        @if(!$this->showMultiRoomSelector && $this->selectedRoom)
                            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex flex-col justify-center space-y-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500 font-medium italic">Precio por noche:</span>
                                    <span class="font-bold text-gray-900">${{ number_format($this->priceForGuests, 0, ',', '.') }}</span>
                                </div>
                                @php
                                    $assignedCount = $this->getRoomGuestsCount($this->roomId);
                                @endphp
                                @if($assignedCount > 0)
                                    <div class="text-[10px] text-gray-500 italic text-center">
                                        <span>Para {{ $assignedCount }} {{ $assignedCount == 1 ? 'persona' : 'personas' }}</span>
                                    </div>
                                @endif
                                <div class="flex justify-between items-center">
                                    <span class="px-2 py-1 bg-white border border-gray-200 rounded-lg text-[10px] font-bold text-gray-600 uppercase">{{ $this->selectedRoom['beds'] ?? 0 }} {{ ($this->selectedRoom['beds'] ?? 0) == 1 ? 'Cama' : 'Camas' }}</span>
                                    <div class="flex items-center text-xs text-gray-600">
                                        <i class="fas fa-users mr-1.5 opacity-60"></i>
                                        <span>Capacidad: {{ $this->selectedRoom['capacity'] ?? 0 }} pers.</span>
                                    </div>
                                </div>
                            </div>
                        @endif

                        <!-- Intentionally hidden: user requested no selected-rooms list below in multi mode -->
                    </div>

                    <!-- Intentionally hidden: user requested no "Total de Personas" UI in multi mode -->
                </div>
            </div>

            <!-- SECCIÓN 2.5: ASIGNACIÓN DE HUÉSPEDES (Modo una habitación) -->
            @if(!$this->showMultiRoomSelector && $this->showGuestAssignmentPanel)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                        <div class="flex items-center">
                            <i class="fas fa-users text-purple-500 mr-2"></i>
                            <h2 class="font-bold text-gray-800">Asignación de Huéspedes a la Habitación</h2>
                        </div>
                        <div class="flex items-center space-x-3">
                            @if($this->selectedRoom && $this->canAssignMoreGuests)
                                <span class="text-xs text-gray-600 font-medium">
                                    {{ $this->availableSlots }} espacio(s) disponible(s)
                                </span>
                            @endif
                            <button type="button"
                                    wire:click="openGuestModal(null)"
                                    @if(!$this->canAssignMoreGuests) disabled @endif
                                    class="px-4 py-2 text-xs font-bold text-white rounded-xl transition-all flex items-center {{ $this->canAssignMoreGuests ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-400 cursor-not-allowed' }}">
                                <i class="fas fa-plus mr-2"></i>
                                Asignar Persona
                            </button>
                        </div>
                    </div>
                    <div class="p-6">
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                            <p class="text-xs text-blue-800 font-medium">
                                <i class="fas fa-info-circle mr-2"></i>
                                Cliente principal: {{ $this->selectedCustomerInfo ? ($this->selectedCustomerInfo['id'] ?? 'No seleccionado') : 'No seleccionado' }}
                                <span class="text-gray-400 mx-2">•</span>
                                Capacidad de la habitación: {{ $this->selectedRoom ? ($this->selectedRoom['capacity'] ?? 0) : 0 }} personas
                            </p>
                        </div>
                        @if(count($this->assignedGuests) === 0)
                            <div class="text-center py-8 text-gray-400">
                                <i class="fas fa-user-plus text-4xl mb-3 opacity-50"></i>
                                <p class="text-sm">No hay personas adicionales asignadas aún</p>
                                @if($this->canAssignMoreGuests)
                                    <p class="text-xs mt-1">Haz clic en "Asignar Persona" para agregar huéspedes adicionales</p>
                                @else
                                    <p class="text-xs mt-1 text-amber-600">La habitación ha alcanzado su capacidad máxima</p>
                                @endif
                            </div>
                        @else
                            <div class="space-y-3">
                                @foreach($this->assignedGuests as $index => $guest)
                                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                        <div class="flex items-center space-x-3">
                                            <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold">
                                                <i class="fas fa-user"></i>
                                            </div>
                                            <div>
                                                <p class="font-bold text-gray-900 text-sm">{{ $guest['name'] ?? '' }}</p>
                                                <div class="flex items-center space-x-3 text-xs text-gray-500 mt-1">
                                                    <span>ID: {{ $guest['identification'] ?? 'S/N' }}</span>
                                                    <span class="text-gray-300">•</span>
                                                    <span>Tel: {{ $guest['phone'] ?? 'S/N' }}</span>
                                                </div>
                                            </div>
                                        </div>
                                        <button type="button" wire:click="removeGuest(null, {{ $index }})"
                                                class="text-red-500 hover:text-red-700 transition-colors">
                                            <i class="fas fa-times"></i>
                                        </button>
                                    </div>
                                @endforeach
                            </div>
                        @endif
                    </div>
                </div>
            @endif

            <!-- SECCIÓN 2.6: ASIGNACIÓN DE HUÉSPEDES POR HABITACIÓN (Modo múltiples habitaciones) -->
            @if($this->showMultiRoomSelector && is_array($this->selectedRoomIds) && count($this->selectedRoomIds) > 0)
                <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                    <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex items-center">
                        <i class="fas fa-users text-purple-500 mr-2"></i>
                        <h2 class="font-bold text-gray-800">Asignación de Huéspedes por Habitación</h2>
                    </div>
                    <div class="p-6 space-y-6">
                        <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                            <p class="text-xs text-blue-800 font-medium">
                                <i class="fas fa-info-circle mr-2"></i>
                                Asigna huéspedes a cada habitación según su capacidad. El cliente principal puede ser asignado opcionalmente a una habitación.
                            </p>
                        </div>
                        @foreach($this->selectedRoomIds as $roomId)
                            @php
                                $room = $this->getRoomById($roomId);
                                $roomGuests = $this->getRoomGuests($roomId);
                                $roomGuestsCount = $this->getRoomGuestsCount($roomId);
                            @endphp
                            @if($room)
                                <div class="border border-gray-200 rounded-xl p-4 bg-gray-50">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-bed text-emerald-500"></i>
                                            <h3 class="font-bold text-gray-900">Habitación {{ $room['number'] ?? $roomId }}</h3>
                                            <span class="text-xs text-gray-500">
                                                (Capacidad: {{ $room['capacity'] ?? 0 }} pers.)
                                            </span>
                                        </div>
                                        <button type="button"
                                                wire:click="openGuestModal({{ $roomId }})"
                                                @if(!$this->canAssignMoreGuestsToRoom($roomId)) disabled @endif
                                                class="px-3 py-1.5 text-xs font-bold text-white rounded-lg transition-all flex items-center {{ $this->canAssignMoreGuestsToRoom($roomId) ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-400 cursor-not-allowed' }}">
                                            <i class="fas fa-plus mr-1"></i>
                                            Asignar
                                        </button>
                                    </div>
                                    @if($roomGuestsCount === 0)
                                        <div class="text-center py-4 text-gray-400">
                                            <i class="fas fa-user-plus text-2xl mb-2 opacity-50"></i>
                                            <p class="text-xs">No hay huéspedes asignados a esta habitación</p>
                                        </div>
                                    @else
                                        <div class="space-y-2">
                                            @foreach($roomGuests as $index => $guest)
                                                <div class="flex items-center justify-between p-2 bg-white rounded-lg border border-gray-200">
                                                    <div class="flex items-center space-x-2">
                                                        <div class="w-8 h-8 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center text-xs">
                                                            <i class="fas fa-user"></i>
                                                        </div>
                                                        <div>
                                                            <p class="font-bold text-gray-900 text-xs">{{ $guest['name'] ?? '' }}</p>
                                                            <div class="flex items-center space-x-2 text-[10px] text-gray-500">
                                                                <span>{{ $guest['identification'] ?? 'S/N' }}</span>
                                                                <span class="text-gray-300">•</span>
                                                                <span>{{ $guest['phone'] ?? 'S/N' }}</span>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <button type="button" wire:click="removeGuest({{ $roomId }}, {{ $index }})"
                                                            class="text-red-500 hover:text-red-700 transition-colors text-xs">
                                                        <i class="fas fa-times"></i>
                                                    </button>
                                                </div>
                                            @endforeach
                                            <div class="text-xs text-gray-600 mt-2">
                                                {{ $roomGuestsCount }} / {{ $room['capacity'] ?? 0 }} personas asignadas
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    </div>
                </div>
            @endif

            <!-- SECCIÓN 3: NOTAS -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex items-center">
                    <i class="fas fa-sticky-note text-amber-500 mr-2"></i>
                    <h2 class="font-bold text-gray-800">Observaciones y Requerimientos</h2>
                </div>
                <div class="p-6">
                    <textarea name="notes" wire:model="notes" rows="3" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('notes') border-red-500 @enderror"
                              placeholder="Ej: Solicitud especial, alergias, llegada tarde, decoración para aniversario...">{{ old('notes', $this->notes) }}</textarea>
                    @error('notes')
                        <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                        </span>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Columna Lateral: Resumen Económico (1/3) -->
        <div class="space-y-6">
            <div class="bg-gray-800 rounded-2xl shadow-xl overflow-hidden sticky top-24 border border-gray-700">
                <div class="p-5 border-b border-gray-700 bg-gray-900/50">
                    <div class="flex items-center justify-between">
                        <h2 class="text-lg font-bold text-white tracking-tight">Resumen de Cobro</h2>
                        <i class="fas fa-wallet text-gray-400"></i>
                    </div>
                </div>

                <div class="p-6 space-y-6">
                    <!-- Valor Total -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Total Estancia</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">$</span>
                            <input type="number" name="total_amount" wire:model.live="total" value="{{ old('total_amount', $this->total) }}" step="1" required
                                   class="block w-full pl-8 pr-4 py-4 bg-gray-700 border-none rounded-xl text-xl font-black text-white focus:ring-2 focus:ring-emerald-500 transition-all @error('total_amount') border-2 border-red-500 @enderror">
                        </div>
                        @error('total_amount')
                            <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                            </span>
                        @enderror
                        @if($this->autoCalculatedTotal > 0 && $total != $this->autoCalculatedTotal)
                            <button type="button" wire:click="restoreSuggestedTotal" class="text-[10px] font-bold text-emerald-400 hover:text-emerald-300 underline uppercase tracking-tighter">
                                Restaurar total sugerido: ${{ number_format($this->autoCalculatedTotal, 0, ',', '.') }}
                            </button>
                        @endif
                    </div>

                    <!-- Abono / Depósito -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Abono Inicial</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">$</span>
                            <input type="number" name="deposit" wire:model.live="deposit" value="{{ old('deposit', $this->deposit) }}" step="1" required
                                   class="block w-full pl-8 pr-4 py-3 bg-gray-700 border-none rounded-xl text-lg font-bold text-white focus:ring-2 focus:ring-blue-500 transition-all @error('deposit') border-2 border-red-500 @enderror">
                        </div>
                        @error('deposit')
                            <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Saldo Pendiente -->
                    <div class="pt-6 border-t border-gray-700 space-y-4">
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Saldo Pendiente</span>
                                <p class="text-3xl font-black {{ $this->balance < 0 ? 'text-red-400' : 'text-white' }}">${{ number_format($this->balance, 0, ',', '.') }}</p>
                            </div>
                            <div class="mb-1">
                                @if($this->balance <= 0)
                                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-500/30">
                                        Liquidado
                                    </span>
                                @else
                                    <span class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-full text-[10px] font-black uppercase tracking-widest border border-amber-500/30">
                                        Pendiente
                                    </span>
                                @endif
                            </div>
                        </div>

                        <!-- Alertas de Pago -->
                        @if($this->balance < 0)
                            <div class="p-3 bg-red-500/20 border border-red-500/30 rounded-xl text-[10px] font-bold text-red-400 text-center animate-bounce uppercase tracking-tighter">
                                <i class="fas fa-exclamation-triangle mr-1"></i> El abono supera el total de la reserva
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Footer del Resumen -->
                <div class="px-6 py-4 bg-black/20 text-center">
                    <input type="hidden" name="reservation_date" value="{{ date('Y-m-d') }}">

                    <!-- Single room mode: guest_ids (backward compatibility) -->
                    @if(!$this->showMultiRoomSelector)
                        @php
                            $totalGuestsSingleRoom = $this->getRoomGuestsCount($this->roomId);
                        @endphp
                        <input type="hidden" name="room_id" value="{{ $this->roomId }}">
                        <input type="hidden" name="guests_count" value="{{ $totalGuestsSingleRoom }}">
                        @foreach($this->assignedGuests as $index => $guest)
                            @if(isset($guest['id']))
                                <input type="hidden" name="guest_ids[{{ $index }}]" value="{{ $guest['id'] }}">
                            @endif
                        @endforeach
                        @error('guests_count')
                            <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                            </span>
                        @enderror
                    @endif

                    <!-- Multiple rooms mode: room_ids and room_guests -->
                    @if($this->showMultiRoomSelector)
                        @php
                            $totalGuestsMultiRoom = $this->calculateTotalGuestsCount();
                        @endphp
                        <input type="hidden" name="guests_count" value="{{ $totalGuestsMultiRoom }}">
                        @foreach($this->selectedRoomIds as $roomId)
                            <input type="hidden" name="room_ids[]" value="{{ $roomId }}">
                            @php
                                $roomGuests = $this->getRoomGuests($roomId);
                            @endphp
                            @foreach($roomGuests as $index => $guest)
                                @if(isset($guest['id']))
                                    <input type="hidden" name="room_guests[{{ $roomId }}][{{ $index }}]" value="{{ $guest['id'] }}">
                                @endif
                            @endforeach
                        @endforeach
                        @error('guests_count')
                            <span class="mt-1 text-xs font-medium text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i> {{ $message }}
                            </span>
                        @enderror
                    @endif

                    <p class="text-[10px] text-gray-500 font-medium">Fecha de Registro: <span class="font-bold">{{ date('d/m/Y') }}</span></p>
                </div>
            </div>

            <!-- Widget de Ayuda -->
            <div class="bg-blue-50 border border-blue-100 rounded-2xl p-5 shadow-sm">
                <div class="flex items-start space-x-3">
                    <div class="bg-blue-600 rounded-full p-2 text-white text-[10px]">
                        <i class="fas fa-info"></i>
                    </div>
                    <div>
                        <h4 class="text-sm font-bold text-blue-900 mb-1">Nota rápida</h4>
                        <p class="text-xs text-blue-700 leading-relaxed">Asegúrate de confirmar la disponibilidad de la habitación antes de procesar el pago inicial.</p>
                    </div>
                </div>
            </div>
        </div>

        <!-- Hidden submit button inside form as backup -->
        <button type="submit" id="reservation-form-submit" style="display: none;">Submit</button>
    </form>

    <!-- MODAL: CREAR NUEVO CLIENTE -->
    @if($newCustomerModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('newCustomerModalOpen') }" x-show="open"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             style="display: block;">
            <div class="flex items-center justify-center min-h-screen px-4 pt-4 pb-20 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                     @click="$wire.closeNewCustomerModal()"></div>

                <!-- Modal panel -->
                <div class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-2xl sm:w-full"
                     @click.stop>
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Crear Nuevo Cliente</h3>
                        </div>
                        <button type="button" wire:click="closeNewCustomerModal"
                                class="text-gray-400 hover:text-gray-900 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-6 max-h-[80vh] overflow-y-auto">
                        <form wire:submit.prevent="createMainCustomer">
                            <!-- Nombre completo -->
                            <div class="mb-4">
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                    Nombre completo <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                        <i class="fas fa-user text-sm"></i>
                                    </div>
                                    <input type="text" wire:model="newMainCustomer.name"
                                           oninput="this.value = this.value.toUpperCase()"
                                           class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 uppercase @error('newMainCustomer.name') border-red-500 @enderror"
                                           placeholder="EJ: JUAN PÉREZ GARCÍA">
                                </div>
                                @error('newMainCustomer.name')
                                    <span class="mt-1 text-xs font-medium text-red-600 block">
                                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                    </span>
                                @enderror
                            </div>

                            <!-- Identificación y Teléfono -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Número de identificación <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <i class="fas fa-id-card text-sm"></i>
                                        </div>
                                        <input type="text" wire:model.live="newMainCustomer.identification"
                                               wire:blur="checkMainCustomerIdentification"
                                               maxlength="10"
                                               class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newMainCustomer.identification') border-red-500 @enderror"
                                               placeholder="Ej: 12345678">
                                    </div>
                                    @if($mainCustomerIdentificationMessage)
                                        <span class="mt-1 text-[10px] font-bold {{ $mainCustomerIdentificationExists ? 'text-red-500' : 'text-emerald-600' }} uppercase tracking-tighter block">
                                            <i class="fas {{ $mainCustomerIdentificationExists ? 'fa-exclamation-triangle' : 'fa-check-circle' }} mr-1"></i>
                                            {{ $mainCustomerIdentificationMessage }}
                                        </span>
                                    @endif
                                    @error('newMainCustomer.identification')
                                        <span class="mt-1 text-xs font-medium text-red-600 block">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Teléfono <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <i class="fas fa-phone text-sm"></i>
                                        </div>
                                        <input type="text" wire:model="newMainCustomer.phone"
                                               maxlength="20"
                                               class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newMainCustomer.phone') border-red-500 @enderror"
                                               placeholder="Ej: 3001234567">
                                    </div>
                                    @error('newMainCustomer.phone')
                                        <span class="mt-1 text-xs font-medium text-red-600 block">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                        </span>
                                    @enderror
                                    <p class="mt-1 text-[10px] text-gray-500">Máximo 20 caracteres</p>
                                </div>
                            </div>

                            <!-- Email y Dirección -->
                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Email
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <i class="fas fa-envelope text-sm"></i>
                                        </div>
                                        <input type="email" wire:model="newMainCustomer.email"
                                               class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newMainCustomer.email') border-red-500 @enderror"
                                               placeholder="ejemplo@correo.com">
                                    </div>
                                    @error('newMainCustomer.email')
                                        <span class="mt-1 text-xs font-medium text-red-600 block">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Dirección
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <i class="fas fa-map-marker-alt text-sm"></i>
                                        </div>
                                        <input type="text" wire:model="newMainCustomer.address"
                                               class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                               placeholder="Dirección del cliente">
                                    </div>
                                </div>
                            </div>

                            <!-- Facturación Electrónica DIAN -->
                            <div class="border-t border-gray-200 pt-6 mt-6">
                                <div class="flex items-center justify-between mb-4">
                                    <div class="flex items-center space-x-3">
                                        <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                                            <i class="fas fa-file-invoice text-sm"></i>
                                        </div>
                                        <div>
                                            <h3 class="text-sm font-semibold text-gray-900">
                                                Facturación Electrónica DIAN
                                            </h3>
                                            <p class="text-xs text-gray-500 mt-0.5">
                                                Activa esta opción si el cliente requiere facturación electrónica
                                            </p>
                                        </div>
                                    </div>
                                    <label class="relative inline-flex items-center cursor-pointer">
                                        <input type="checkbox" wire:model.live="newMainCustomer.requiresElectronicInvoice"
                                               class="sr-only peer">
                                        <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                                    </label>
                                </div>

                                <!-- Campos DIAN (mostrar/ocultar dinámicamente) -->
                                @if($newMainCustomer['requiresElectronicInvoice'] ?? false)
                                    <div class="mt-6 space-y-4 border-t border-gray-200 pt-6">
                                        <!-- Mensaje informativo -->
                                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                            <div class="flex items-start">
                                                <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-2"></i>
                                                <div class="text-xs text-blue-800">
                                                    <p class="font-semibold mb-1">Campos Obligatorios para Facturación Electrónica</p>
                                                    <p class="text-[10px]">Complete todos los campos marcados con <span class="text-red-500 font-bold">*</span> para poder generar facturas electrónicas válidas según la normativa DIAN.</p>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Tipo de Documento -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                Tipo de Documento <span class="text-red-500">*</span>
                                            </label>
                                            <select wire:model.live="newMainCustomer.identificationDocumentId"
                                                    wire:change="updateMainCustomerRequiredFields"
                                                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newMainCustomer.identificationDocumentId') border-red-500 @enderror">
                                                <option value="">Seleccione...</option>
                                                @if(is_array($identificationDocuments))
                                                    @foreach($identificationDocuments as $doc)
                                                        <option value="{{ $doc['id'] ?? '' }}">
                                                            {{ $doc['name'] ?? '' }}@if(isset($doc['code']) && $doc['code']) ({{ $doc['code'] }})@endif
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('newMainCustomer.identificationDocumentId')
                                                <span class="mt-1 text-xs font-medium text-red-600 block">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                </span>
                                            @enderror
                                        </div>

                                        <!-- Dígito Verificador (solo si el documento lo requiere) -->
                                        @if($mainCustomerRequiresDV)
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                    Dígito Verificador (DV) <span class="text-red-500">*</span>
                                                </label>
                                                <input type="text" wire:model="newMainCustomer.dv"
                                                       maxlength="1"
                                                       readonly
                                                       class="block w-full px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-xl text-sm text-gray-600 cursor-not-allowed font-bold">
                                                <p class="mt-1 text-xs text-blue-600">
                                                    <i class="fas fa-magic mr-1"></i> Calculado automáticamente por el sistema
                                                </p>
                                            </div>
                                        @endif

                                        <!-- Razón Social / Nombre Comercial (solo para personas jurídicas) -->
                                        @if($mainCustomerIsJuridicalPerson)
                                            <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                        Razón Social / Empresa <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" wire:model="newMainCustomer.company"
                                                           class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newMainCustomer.company') border-red-500 @enderror"
                                                           placeholder="Razón social">
                                                    @error('newMainCustomer.company')
                                                        <span class="mt-1 text-xs font-medium text-red-600 block">
                                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                        </span>
                                                    @enderror
                                                </div>
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                        Nombre Comercial
                                                    </label>
                                                    <input type="text" wire:model="newMainCustomer.tradeName"
                                                           class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                                           placeholder="Nombre comercial">
                                                </div>
                                            </div>
                                        @endif

                                        <!-- Municipio -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                Municipio <span class="text-red-500">*</span>
                                            </label>
                                            <select wire:model="newMainCustomer.municipalityId"
                                                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newMainCustomer.municipalityId') border-red-500 @enderror">
                                                <option value="">Seleccione un municipio...</option>
                                                @if(is_array($municipalities))
                                                    @php
                                                        $currentDepartment = null;
                                                    @endphp
                                                    @foreach($municipalities as $municipality)
                                                        @if($currentDepartment !== ($municipality['department'] ?? null))
                                                            @if($currentDepartment !== null)
                                                                </optgroup>
                                                            @endif
                                                            <optgroup label="{{ $municipality['department'] ?? '' }}">
                                                            @php
                                                                $currentDepartment = $municipality['department'] ?? null;
                                                            @endphp
                                                        @endif
                                                        <option value="{{ $municipality['factus_id'] ?? $municipality['id'] ?? '' }}">
                                                            {{ ($municipality['department'] ?? '') }} - {{ $municipality['name'] ?? '' }}
                                                        </option>
                                                        @if($loop->last)
                                                            </optgroup>
                                                        @endif
                                                    @endforeach
                                                @endif
                                            </select>
                                            @error('newMainCustomer.municipalityId')
                                                <span class="mt-1 text-xs font-medium text-red-600 block">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                </span>
                                            @enderror
                                        </div>

                                        <!-- Tipo de Organización Legal -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                Tipo de Organización Legal
                                            </label>
                                            <select wire:model="newMainCustomer.legalOrganizationId"
                                                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                                <option value="">Seleccione...</option>
                                                @if(is_array($legalOrganizations))
                                                    @foreach($legalOrganizations as $org)
                                                        <option value="{{ $org['id'] ?? '' }}">
                                                            {{ $org['name'] ?? '' }}
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>

                                        <!-- Régimen Tributario -->
                                        <div>
                                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                Régimen Tributario
                                            </label>
                                            <select wire:model="newMainCustomer.tributeId"
                                                    class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                                <option value="">Seleccione...</option>
                                                @if(is_array($tributes))
                                                    @foreach($tributes as $tribute)
                                                        <option value="{{ $tribute['id'] ?? '' }}">
                                                            {{ $tribute['name'] ?? '' }}@if(isset($tribute['code']) && $tribute['code']) ({{ $tribute['code'] }})@endif
                                                        </option>
                                                    @endforeach
                                                @endif
                                            </select>
                                        </div>
                                    </div>
                                @endif
                            </div>

                            <!-- Footer -->
                            <div class="flex items-center justify-end space-x-3 pt-4 border-t border-gray-100 mt-6">
                                <button type="button" wire:click="closeNewCustomerModal"
                                        class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all">
                                    Cancelar
                                </button>
                                <button type="button" wire:click="createMainCustomer" wire:loading.attr="disabled"
                                        class="px-6 py-2 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center">
                                    <span wire:loading.remove wire:target="createMainCustomer">
                                        <i class="fas fa-save mr-2"></i> Crear Cliente
                                    </span>
                                    <span wire:loading wire:target="createMainCustomer" class="flex items-center">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Creando...
                                    </span>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <!-- MODAL: ASIGNAR HUÉSPED -->
    @if($guestModalOpen)
        <div class="fixed inset-0 z-50 overflow-y-auto" x-data="{ open: @entangle('guestModalOpen') }" x-show="open"
             x-transition:enter="ease-out duration-300" x-transition:enter-start="opacity-0"
             x-transition:enter-end="opacity-100" x-transition:leave="ease-in duration-200"
             x-transition:leave-start="opacity-100" x-transition:leave-end="opacity-0"
             style="display: block;">
            <div class="flex items-center justify-center min-h-screen px-4 py-4 text-center sm:block sm:p-0">
                <!-- Background overlay -->
                <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"
                     @click="$wire.closeGuestModal()"></div>

                <!-- Modal panel -->
                <div class="relative bg-white rounded-2xl text-left shadow-xl transform transition-all w-full max-w-3xl h-[90vh] flex flex-col mx-auto"
                     @click.stop>
                    <!-- Header -->
                    <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50 sticky top-0 z-10">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                                <i class="fas fa-user-plus"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Asignar Persona a la Habitación</h3>
                        </div>
                        <button type="button" wire:click="closeGuestModal"
                                class="text-gray-400 hover:text-gray-900 transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <!-- Content -->
                    <div class="px-6 py-6 flex-1 overflow-hidden flex flex-col">
                        <!-- Info de capacidad -->
                        @if($currentRoomForGuestAssignment !== null)
                            @php
                                $room = $this->getRoomById($currentRoomForGuestAssignment);
                                $roomGuestsCount = $this->getRoomGuestsCount($currentRoomForGuestAssignment);
                                $availableSlots = ($room['capacity'] ?? $room['max_capacity'] ?? 0) - $roomGuestsCount;
                                $roomNumber = $room['number'] ?? $room['room_number'] ?? $currentRoomForGuestAssignment;
                            @endphp
                            <div class="mb-4 p-3 bg-purple-50 border border-purple-100 rounded-xl">
                                <p class="text-xs text-purple-800 font-medium">
                                    <i class="fas fa-info-circle mr-2"></i>
                                    Habitación: <span class="font-bold">{{ $roomNumber }}</span>
                                    <span class="text-gray-400 mx-2">•</span>
                                    Capacidad: <span class="font-bold">{{ $room['capacity'] ?? $room['max_capacity'] ?? 0 }}</span> personas
                                    <span class="text-gray-400 mx-2">•</span>
                                    Espacios disponibles: <span class="font-bold">{{ $availableSlots }}</span>
                                </p>
                            </div>
                        @else
                            @if($this->selectedRoom)
                                <div class="mb-4 p-3 bg-purple-50 border border-purple-100 rounded-xl">
                                    <p class="text-xs text-purple-800 font-medium">
                                        <i class="fas fa-info-circle mr-2"></i>
                                        Capacidad de la habitación: <span class="font-bold">{{ $this->selectedRoom['capacity'] ?? $this->selectedRoom['max_capacity'] ?? 0 }}</span> personas
                                        <span class="text-gray-400 mx-2">•</span>
                                        Espacios disponibles: <span class="font-bold">{{ $this->availableSlots }}</span>
                                    </p>
                                </div>
                            @endif
                        @endif

                        <!-- Tabs: Buscar / Crear -->
                        <div class="flex border-b border-gray-200 mb-6">
                            <button type="button" wire:click="setGuestModalTab('search')"
                                    class="px-4 py-2 font-bold text-sm transition-colors {{ $guestModalTab === 'search' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-500' }}">
                                <i class="fas fa-search mr-2"></i> Buscar Persona
                            </button>
                            <button type="button" wire:click="setGuestModalTab('create')"
                                    class="px-4 py-2 font-bold text-sm transition-colors {{ $guestModalTab === 'create' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-500' }}">
                                <i class="fas fa-plus mr-2"></i> Crear Nueva Persona
                            </button>
                        </div>

                        <!-- Tab: Buscar -->
                        @if($guestModalTab === 'search')
                            <div class="flex flex-col gap-4 flex-1 min-h-0">
                                <div id="guest-search-container">
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Buscar por nombre, documento o teléfono
                                    </label>
                                    <!-- Search Input -->
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <i class="fas fa-search text-sm"></i>
                                        </div>
                                        <input type="text"
                                               wire:model.debounce.300ms="guestSearchTerm"
                                               wire:click="openGuestSearchDropdown"
                                               wire:focus="openGuestSearchDropdown"
                                               wire:keydown.escape="closeGuestDropdown"
                                               class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500"
                                               placeholder="Buscar por nombre, identificación o teléfono...">
                                    </div>
                                </div>

                                <!-- Results Panel (scrollable) -->
                                <div class="border border-gray-300 rounded-xl shadow-sm bg-white flex-1 min-h-0 overflow-y-scroll">
                                    @php
                                        $filteredGuests = $this->filteredGuests;
                                    @endphp

                                    @if(count($filteredGuests) > 0)
                                        <div class="px-4 py-2 text-[10px] font-bold text-gray-500 uppercase tracking-widest bg-gray-50 border-b border-gray-100 sticky top-0">
                                            <i class="fas fa-hand-point-up mr-1"></i>
                                            Desliza para ver más personas
                                        </div>
                                        @foreach($filteredGuests as $customer)
                                            @php
                                                $customerIdValue = $customer['id'] ?? null;
                                                $customerName = $customer['name'] ?? '';
                                                $identification = $customer['taxProfile']['identification'] ?? 'S/N';
                                                $phone = $customer['phone'] ?? 'S/N';
                                            @endphp
                                            @if($customerIdValue)
                                                <button type="button"
                                                        wire:click="selectGuestForAssignment({{ $customerIdValue }})"
                                                        class="w-full text-left px-4 py-3 hover:bg-purple-50 transition-colors border-b border-gray-100 last:border-b-0">
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
                                                        <i class="fas fa-check-circle text-purple-600"></i>
                                                    </div>
                                                </button>
                                            @endif
                                        @endforeach

                                        @if(empty($this->guestSearchTerm) && count($this->customers ?? []) > 5)
                                            <div class="px-4 py-2 text-xs text-gray-500 text-center border-t border-gray-100 bg-gray-50 sticky bottom-0">
                                                <i class="fas fa-info-circle mr-1"></i>
                                                Mostrando los 5 clientes más recientes. Escribe para buscar más.
                                            </div>
                                        @endif
                                    @else
                                        <div class="px-4 py-6 text-center text-sm text-gray-500">
                                            <i class="fas fa-search text-2xl mb-2 opacity-50"></i>
                                            <p>No se encontraron clientes</p>
                                            @if(!empty($this->guestSearchTerm))
                                                <p class="text-xs mt-1">Intenta con otro término de búsqueda</p>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Tab: Crear -->
                        @if($guestModalTab === 'create')
                            <div class="space-y-4 overflow-y-auto pr-2">
                                <h4 class="text-sm font-bold text-gray-800 flex items-center">
                                    <i class="fas fa-user mr-2 text-purple-500"></i>
                                    Información del Cliente
                                </h4>

                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Nombre Completo <span class="text-red-500">*</span>
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                            <i class="fas fa-user text-sm"></i>
                                        </div>
                                        <input type="text" wire:model="newCustomer.name"
                                               oninput="this.value = this.value.toUpperCase()"
                                               class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 uppercase @error('newCustomer.name') border-red-500 @enderror"
                                               placeholder="EJ: JUAN PÉREZ GARCÍA">
                                    </div>
                                    @error('newCustomer.name')
                                        <span class="mt-1 text-xs font-medium text-red-600 block">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                            Número de identificación <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fas fa-id-card text-sm"></i>
                                            </div>
                                            <input type="text" wire:model.live="newCustomer.identification"
                                                   wire:blur="checkCustomerIdentification"
                                                   maxlength="10"
                                                   class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 @error('newCustomer.identification') border-red-500 @enderror"
                                                   placeholder="Ej: 12345678">
                                        </div>
                                        @if($customerIdentificationMessage)
                                            <span class="mt-1 text-xs font-medium {{ $customerIdentificationExists ? 'text-red-600' : 'text-emerald-600' }} block">
                                                <i class="fas {{ $customerIdentificationExists ? 'fa-exclamation-triangle' : 'fa-check-circle' }} mr-1"></i>
                                                {{ $customerIdentificationMessage }}
                                            </span>
                                        @endif
                                        @error('newCustomer.identification')
                                            <span class="mt-1 text-xs font-medium text-red-600 block">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                            Teléfono <span class="text-red-500">*</span>
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fas fa-phone text-sm"></i>
                                            </div>
                                            <input type="text" wire:model="newCustomer.phone"
                                                   maxlength="20"
                                                   class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 @error('newCustomer.phone') border-red-500 @enderror"
                                                   placeholder="Ej: 3001234567">
                                        </div>
                                        @error('newCustomer.phone')
                                            <span class="mt-1 text-xs font-medium text-red-600 block">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                            </span>
                                        @enderror
                                    </div>
                                </div>

                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                            Correo electrónico
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fas fa-envelope text-sm"></i>
                                            </div>
                                            <input type="email" wire:model="newCustomer.email"
                                                   class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 @error('newCustomer.email') border-red-500 @enderror"
                                                   placeholder="ejemplo@correo.com">
                                        </div>
                                        @error('newCustomer.email')
                                            <span class="mt-1 text-xs font-medium text-red-600 block">
                                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                            </span>
                                        @enderror
                                    </div>

                                    <div>
                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                            Dirección
                                        </label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                                <i class="fas fa-map-marker-alt text-sm"></i>
                                            </div>
                                            <input type="text" wire:model="newCustomer.address"
                                                   class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500"
                                                   placeholder="Dirección del cliente">
                                        </div>
                                    </div>
                                </div>

                                <!-- Facturación Electrónica DIAN -->
                                <div class="border-t border-gray-200 pt-6 mt-6">
                                    <div class="flex items-center justify-between mb-4">
                                        <div class="flex items-center space-x-3">
                                            <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                                                <i class="fas fa-file-invoice text-sm"></i>
                                            </div>
                                            <div>
                                                <h3 class="text-sm font-semibold text-gray-900">
                                                    Facturación Electrónica DIAN
                                                </h3>
                                                <p class="text-xs text-gray-500 mt-0.5">
                                                    Activa esta opción si el cliente requiere facturación electrónica
                                                </p>
                                            </div>
                                        </div>
                                        <label class="relative inline-flex items-center cursor-pointer">
                                            <input type="checkbox" wire:model.live="newCustomer.requiresElectronicInvoice"
                                                   wire:change="updateCustomerRequiredFields"
                                                   class="sr-only peer">
                                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-purple-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-purple-600"></div>
                                        </label>
                                    </div>

                                    <!-- Campos DIAN (mostrar/ocultar dinámicamente) -->
                                    @if($newCustomer['requiresElectronicInvoice'] ?? false)
                                        <div class="mt-6 space-y-4 border-t border-gray-200 pt-6">
                                            <!-- Mensaje informativo -->
                                            <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                                <div class="flex items-start">
                                                    <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-2"></i>
                                                    <div class="text-xs text-blue-800">
                                                        <p class="font-semibold mb-1">Campos Obligatorios para Facturación Electrónica</p>
                                                        <p class="text-[10px]">Complete todos los campos marcados con <span class="text-red-500 font-bold">*</span> para poder generar facturas electrónicas válidas según la normativa DIAN.</p>
                                                    </div>
                                                </div>
                                            </div>

                                            <!-- Tipo de Documento -->
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                    Tipo de Documento <span class="text-red-500">*</span>
                                                </label>
                                                <select wire:model.live="newCustomer.identificationDocumentId"
                                                       wire:change="updateCustomerRequiredFields"
                                                       class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 @error('newCustomer.identificationDocumentId') border-red-500 @enderror">
                                                    <option value="">Seleccione...</option>
                                                    @if(is_array($identificationDocuments))
                                                        @foreach($identificationDocuments as $doc)
                                                            <option value="{{ $doc['id'] ?? '' }}">
                                                                {{ $doc['name'] ?? '' }}@if(isset($doc['code']) && $doc['code']) ({{ $doc['code'] }})@endif
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('newCustomer.identificationDocumentId')
                                                    <span class="mt-1 text-xs font-medium text-red-600 block">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                    </span>
                                                @enderror
                                            </div>

                                            <!-- Dígito Verificador (solo si el documento lo requiere) -->
                                            @if($customerRequiresDV)
                                                <div>
                                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                        Dígito Verificador (DV) <span class="text-red-500">*</span>
                                                    </label>
                                                    <input type="text" wire:model="newCustomer.dv"
                                                           maxlength="1"
                                                           readonly
                                                           class="block w-full px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-xl text-sm text-gray-600 cursor-not-allowed font-bold">
                                                    <p class="mt-1 text-xs text-blue-600">
                                                        <i class="fas fa-magic mr-1"></i> Calculado automáticamente por el sistema
                                                    </p>
                                                </div>
                                            @endif

                                            <!-- Razón Social / Nombre Comercial (solo para personas jurídicas) -->
                                            @if($customerIsJuridicalPerson)
                                                <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                            Razón Social / Empresa <span class="text-red-500">*</span>
                                                        </label>
                                                        <input type="text" wire:model="newCustomer.company"
                                                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 @error('newCustomer.company') border-red-500 @enderror"
                                                               placeholder="Razón social">
                                                        @error('newCustomer.company')
                                                            <span class="mt-1 text-xs font-medium text-red-600 block">
                                                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                            </span>
                                                        @enderror
                                                    </div>
                                                    <div>
                                                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                            Nombre Comercial
                                                        </label>
                                                        <input type="text" wire:model="newCustomer.tradeName"
                                                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500"
                                                               placeholder="Nombre comercial">
                                                    </div>
                                                </div>
                                            @endif

                                            <!-- Municipio -->
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                    Municipio <span class="text-red-500">*</span>
                                                </label>
                                                <select wire:model="newCustomer.municipalityId"
                                                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500 @error('newCustomer.municipalityId') border-red-500 @enderror">
                                                    <option value="">Seleccione un municipio...</option>
                                                    @if(is_array($municipalities))
                                                        @php
                                                            $currentDepartment = null;
                                                        @endphp
                                                        @foreach($municipalities as $municipality)
                                                            @if($currentDepartment !== ($municipality['department'] ?? null))
                                                                @if($currentDepartment !== null)
                                                                    </optgroup>
                                                                @endif
                                                                <optgroup label="{{ $municipality['department'] ?? '' }}">
                                                                @php
                                                                    $currentDepartment = $municipality['department'] ?? null;
                                                                @endphp
                                                            @endif
                                                            <option value="{{ $municipality['factus_id'] ?? $municipality['id'] ?? '' }}">
                                                                {{ ($municipality['department'] ?? '') }} - {{ $municipality['name'] ?? '' }}
                                                            </option>
                                                            @if($loop->last)
                                                                </optgroup>
                                                            @endif
                                                        @endforeach
                                                    @endif
                                                </select>
                                                @error('newCustomer.municipalityId')
                                                    <span class="mt-1 text-xs font-medium text-red-600 block">
                                                        <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                                    </span>
                                                @enderror
                                            </div>

                                            <!-- Tipo de Organización Legal -->
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                    Tipo de Organización Legal
                                                </label>
                                                <select wire:model="newCustomer.legalOrganizationId"
                                                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500">
                                                    <option value="">Seleccione...</option>
                                                    @if(is_array($legalOrganizations))
                                                        @foreach($legalOrganizations as $org)
                                                            <option value="{{ $org['id'] ?? '' }}">
                                                                {{ $org['name'] ?? '' }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>

                                            <!-- Régimen Tributario -->
                                            <div>
                                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                    Régimen Tributario
                                                </label>
                                                <select wire:model="newCustomer.tributeId"
                                                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500">
                                                    <option value="">Seleccione...</option>
                                                    @if(is_array($tributes))
                                                        @foreach($tributes as $tribute)
                                                            <option value="{{ $tribute['id'] ?? '' }}">
                                                                {{ $tribute['name'] ?? '' }}@if(isset($tribute['code']) && $tribute['code']) ({{ $tribute['code'] }})@endif
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                </select>
                                            </div>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        @endif

                        <!-- Footer -->
                        <div class="flex items-center justify-end space-x-3 px-6 py-4 border-t border-gray-100 bg-white sticky bottom-0">
                            <button type="button" wire:click="closeGuestModal"
                                    class="px-4 py-2 text-sm font-semibold text-gray-700 bg-white border border-gray-300 rounded-xl hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            @if($guestModalTab === 'create')
                                <button type="button" wire:click="createAndAddGuest"
                                        wire:loading.attr="disabled"
                                        @if($currentRoomForGuestAssignment !== null ? !$this->canAssignMoreGuestsToRoom($currentRoomForGuestAssignment) : !$this->canAssignMoreGuests) disabled @endif
                                        class="px-6 py-2 text-sm font-bold text-white bg-purple-600 rounded-xl hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center">
                                    <span wire:loading.remove wire:target="createAndAddGuest">
                                        <i class="fas fa-check mr-2"></i> Crear y Asignar
                                    </span>
                                    <span wire:loading wire:target="createAndAddGuest" class="flex items-center">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Creando...
                                    </span>
                                </button>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
</div>

@push('scripts')
<script>
    function validateFormBeforeSubmit(event) {
        const form = event.target;
        const customerId = form.querySelector('input[name="customer_id"]')?.value;
        const roomId = form.querySelector('input[name="room_id"]')?.value;
        const roomIds = Array.from(form.querySelectorAll('input[name="room_ids[]"]')).filter(input => input.checked || input.type === 'hidden').map(input => input.value);
        const checkInDate = form.querySelector('input[name="check_in_date"]')?.value;
        const checkOutDate = form.querySelector('input[name="check_out_date"]')?.value;
        const totalAmount = form.querySelector('input[name="total_amount"]')?.value;
        const deposit = form.querySelector('input[name="deposit"]')?.value;
        const guestsCount = form.querySelector('input[name="guests_count"]')?.value;

        let errors = [];

        if (!customerId || customerId === '') {
            errors.push('Debe seleccionar un cliente.');
        }

        if ((!roomId || roomId === '') && (!roomIds || roomIds.length === 0 || roomIds[0] === '')) {
            errors.push('Debe seleccionar al menos una habitación.');
        }

        if (!checkInDate || checkInDate === '') {
            errors.push('La fecha de check-in es obligatoria.');
        }

        if (!checkOutDate || checkOutDate === '') {
            errors.push('La fecha de check-out es obligatoria.');
        }

        if (checkInDate && checkOutDate && new Date(checkOutDate) <= new Date(checkInDate)) {
            errors.push('La fecha de check-out debe ser posterior a la fecha de check-in.');
        }

        if (!totalAmount || parseFloat(totalAmount) <= 0) {
            errors.push('El monto total debe ser mayor a cero.');
        }

        if (!deposit || deposit === '') {
            errors.push('El abono inicial es obligatorio.');
        }

        if (!guestsCount || parseInt(guestsCount) < 1) {
            errors.push('Debe asignar al menos un huésped.');
        }

        if (errors.length > 0) {
            event.preventDefault();
            // Los errores se mostrarán automáticamente con @@error en Blade
            // Scroll al primer error
            setTimeout(() => {
                const firstError = form.querySelector('.border-red-500, .text-red-600');
                if (firstError) {
                    firstError.scrollIntoView({ behavior: 'smooth', block: 'center' });
                }
            }, 100);
            return false;
        }

        return true;
    }

    document.addEventListener('DOMContentLoaded', function() {
        // Cierra el dropdown de clientes al hacer clic fuera de su contenedor
        document.addEventListener('click', function(event) {
            const container = document.getElementById('customer-selector-container');
            if (!container) return;

            // Obtener el input y el dropdown
            const input = container.querySelector('input[type="text"]');
            const dropdown = container.querySelector('.absolute.z-50');

            // No cerrar si el clic fue dentro del contenedor (input o dropdown)
            if (container.contains(event.target)) {
                return;
            }

            // Cerrar solo si el clic fue fuera del contenedor
            @this.set('showCustomerDropdown', false);
        });

        // Close guest dropdown when clicking outside
        document.addEventListener('click', function(event) {
            const container = document.getElementById('guest-search-container');
            if (!container) return;

            // No cerrar si el clic fue dentro del contenedor
            if (container.contains(event.target)) {
                return;
            }

            // Cerrar solo si el clic fue fuera del contenedor
            @this.set('showGuestDropdown', false);
        });
    });
</script>
@endpush


