@extends('layouts.app')

@section('title', 'Nueva Reserva')
@section('header', 'Nueva Reserva')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="reservationForm()">
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
                        :disabled="!isValid || loading"
                        class="px-6 py-2 text-sm font-bold text-white bg-emerald-600 rounded-xl hover:bg-emerald-700 disabled:opacity-50 disabled:cursor-not-allowed shadow-sm transition-all flex items-center">
                    <i class="fas fa-save mr-2" x-show="!loading"></i>
                    <i class="fas fa-spinner fa-spin mr-2" x-show="loading"></i>
                    Confirmar Reserva
                </button>
            </div>
        </div>
    </div>

    <form id="reservation-form" method="POST" action="{{ route('reservations.store') }}" class="grid grid-cols-1 lg:grid-cols-3 gap-6">
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
                    <button type="button" @click="openNewCustomerModal()" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center">
                        <i class="fas fa-plus-circle mr-1"></i> NUEVO CLIENTE
                    </button>
                </div>
                <div class="p-6">
                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Seleccionar Huésped</label>
                            <select name="customer_id" id="customer_id" x-model="customerId" required class="w-full">
                                <option value="">Buscar por nombre o identificación...</option>
                            </select>
                        </div>

                        <!-- Info Preview del Cliente Seleccionado -->
                        <template x-if="selectedCustomerInfo">
                            <div class="mt-2 p-3 bg-blue-50 rounded-xl flex items-center justify-between border border-blue-100 transition-all animate-fadeIn">
                                <div class="flex items-center space-x-4 text-sm text-blue-800">
                                    <div class="flex items-center">
                                        <i class="fas fa-id-card mr-2 opacity-60"></i>
                                        <span x-text="selectedCustomerInfo.id"></span>
                                    </div>
                                    <div class="flex items-center border-l border-blue-200 pl-4">
                                        <i class="fas fa-phone mr-2 opacity-60"></i>
                                        <span x-text="selectedCustomerInfo.phone"></span>
                                    </div>
                                </div>
                                <span class="text-[10px] font-bold bg-blue-200 text-blue-800 px-2 py-0.5 rounded-full uppercase">Verificado</span>
                            </div>
                        </template>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2: HABITACIÓN Y FECHAS -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex items-center">
                    <i class="fas fa-bed text-emerald-500 mr-2"></i>
                    <h2 class="font-bold text-gray-800">Estancia y Habitación</h2>
                </div>
                <div class="p-6 space-y-6">
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <!-- Habitación -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Habitación</label>
                            <select name="room_id" id="room_id" x-model="roomId" required class="w-full">
                                <option value="">Seleccionar número...</option>
                                @foreach($rooms as $room)
                                    <option value="{{ $room->id }}">
                                        Habitación {{ $room->room_number }} ({{ $room->beds_count }} {{ $room->beds_count == 1 ? 'Cama' : 'Camas' }})
                                    </option>
                                @endforeach
                            </select>

                            <!-- Status de Disponibilidad -->
                            <div x-show="roomId" class="mt-3">
                                <template x-if="isChecking">
                                    <span class="text-xs text-gray-500 flex items-center">
                                        <i class="fas fa-spinner fa-spin mr-2"></i> Verificando disponibilidad...
                                    </span>
                                </template>
                                <template x-if="!isChecking && availability !== null">
                                    <div :class="availability ? 'bg-emerald-50 text-emerald-700 border-emerald-100' : 'bg-red-50 text-red-700 border-red-100'"
                                         class="p-2.5 rounded-xl border text-xs font-bold flex items-center">
                                        <i :class="availability ? 'fas fa-check-circle' : 'fas fa-times-circle'" class="mr-2"></i>
                                        <span x-text="availability ? 'HABITACIÓN DISPONIBLE' : 'NO DISPONIBLE PARA ESTAS FECHAS'"></span>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <!-- Detalles Habitación -->
                        <template x-if="selectedRoom">
                            <div class="bg-gray-50 rounded-2xl p-4 border border-gray-100 flex flex-col justify-center space-y-3">
                                <div class="flex justify-between items-center text-sm">
                                    <span class="text-gray-500 font-medium italic">Precio por noche:</span>
                                    <span class="font-bold text-gray-900" x-text="formatCurrency(getPriceForGuests())"></span>
                                </div>
                                <template x-if="guestsCount > 0">
                                    <div class="text-[10px] text-gray-500 italic text-center">
                                        <span x-text="'Para ' + guestsCount + (guestsCount == 1 ? ' persona' : ' personas')"></span>
                                    </div>
                                </template>
                                <div class="flex justify-between items-center">
                                    <span class="px-2 py-1 bg-white border border-gray-200 rounded-lg text-[10px] font-bold text-gray-600 uppercase" x-text="selectedRoom.beds + (selectedRoom.beds == 1 ? ' Cama' : ' Camas')"></span>
                                    <div class="flex items-center text-xs text-gray-600">
                                        <i class="fas fa-users mr-1.5 opacity-60"></i>
                                        <span x-text="'Capacidad: ' + selectedRoom.capacity + ' pers.'"></span>
                                    </div>
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="grid grid-cols-1 md:grid-cols-3 gap-6 pt-4 border-t border-gray-50">
                        <!-- Número de Personas -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Número de Personas</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-users text-sm"></i>
                                </div>
                                <input type="number" name="guests_count" x-model="guestsCount" min="1"
                                       :max="selectedRoom ? selectedRoom.capacity : 10" required
                                       class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            <template x-if="selectedRoom && guestsCount > selectedRoom.capacity">
                                <span class="mt-2 text-[10px] font-bold text-red-500 uppercase tracking-tighter">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> Excede la capacidad máxima de la habitación
                                </span>
                            </template>
                        </div>

                        <!-- Fecha Entrada -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Check-In</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-calendar-alt text-sm"></i>
                                </div>
                                <input type="date" name="check_in_date" x-model="checkIn" required
                                       class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                        </div>

                        <!-- Fecha Salida -->
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">Check-Out</label>
                            <div class="relative">
                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-door-open text-sm"></i>
                                </div>
                                <input type="date" name="check_out_date" x-model="checkOut" required
                                       class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                            </div>
                            <template x-if="nights > 0">
                                <div class="mt-2 text-[10px] font-black tracking-widest text-emerald-600 uppercase flex items-center">
                                    <i class="fas fa-moon mr-1.5"></i>
                                    <span x-text="nights + (nights === 1 ? ' NOCHE' : ' NOCHES')"></span>
                                </div>
                            </template>
                            <template x-if="nights < 1 && checkIn && checkOut">
                                <span class="mt-2 text-[10px] font-bold text-red-500 uppercase tracking-tighter">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> La fecha de salida debe ser posterior a la de entrada
                                </span>
                            </template>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SECCIÓN 2.5: ASIGNACIÓN DE HUÉSPEDES -->
            <div x-show="selectedRoom && selectedRoom.capacity > 1 && guestsCount > 1"
                 x-cloak
                 class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden"
                 style="display: none;">
                <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex items-center justify-between">
                    <div class="flex items-center">
                        <i class="fas fa-users text-purple-500 mr-2"></i>
                        <h2 class="font-bold text-gray-800">Asignación de Huéspedes a la Habitación</h2>
                    </div>
                    <div class="flex items-center space-x-3">
                        <template x-if="selectedRoom && canAssignMoreGuests">
                            <span class="text-xs text-gray-600 font-medium">
                                <span x-text="availableSlots"></span> espacio(s) disponible(s)
                            </span>
                        </template>
                        <button type="button"
                                @click="openGuestModal()"
                                :disabled="!canAssignMoreGuests"
                                :class="canAssignMoreGuests ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-400 cursor-not-allowed'"
                                class="px-4 py-2 text-xs font-bold text-white rounded-xl transition-all flex items-center">
                            <i class="fas fa-plus mr-2"></i>
                            Asignar Persona
                        </button>
                    </div>
                </div>
                <div class="p-6">
                    <div class="mb-4 p-3 bg-blue-50 border border-blue-100 rounded-xl">
                        <p class="text-xs text-blue-800 font-medium">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span x-text="'Cliente principal: ' + (selectedCustomerInfo ? selectedCustomerInfo.id : 'No seleccionado')"></span>
                            <span class="text-gray-400 mx-2">•</span>
                            <span x-text="'Capacidad de la habitación: ' + (selectedRoom ? selectedRoom.capacity : 0) + ' personas'"></span>
                        </p>
                    </div>
                    <template x-if="assignedGuests.length === 0">
                        <div class="text-center py-8 text-gray-400">
                            <i class="fas fa-user-plus text-4xl mb-3 opacity-50"></i>
                            <p class="text-sm">No hay personas adicionales asignadas aún</p>
                            <p class="text-xs mt-1" x-show="canAssignMoreGuests">Haz clic en "Asignar Persona" para agregar huéspedes adicionales</p>
                            <p class="text-xs mt-1 text-amber-600" x-show="!canAssignMoreGuests">La habitación ha alcanzado su capacidad máxima</p>
                        </div>
                    </template>
                    <template x-if="assignedGuests.length > 0">
                        <div class="space-y-3">
                            <template x-for="(guest, index) in assignedGuests" :key="guest.id">
                                <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl border border-gray-100">
                                    <div class="flex items-center space-x-3">
                                        <div class="w-10 h-10 rounded-full bg-purple-100 text-purple-600 flex items-center justify-center font-bold">
                                            <i class="fas fa-user"></i>
                                        </div>
                                        <div>
                                            <p class="font-bold text-gray-900 text-sm" x-text="guest.name"></p>
                                            <div class="flex items-center space-x-3 text-xs text-gray-500 mt-1">
                                                <span x-text="'ID: ' + (guest.identification || 'S/N')"></span>
                                                <span class="text-gray-300">•</span>
                                                <span x-text="'Tel: ' + (guest.phone || 'S/N')"></span>
                                            </div>
                                        </div>
                                    </div>
                                    <button type="button" @click="removeGuest(index)"
                                            class="text-red-500 hover:text-red-700 transition-colors">
                                        <i class="fas fa-times"></i>
                                    </button>
                                </div>
                            </template>
                        </div>
                    </template>
                </div>
            </div>

            <!-- SECCIÓN 3: NOTAS -->
            <div class="bg-white rounded-2xl border border-gray-100 shadow-sm overflow-hidden">
                <div class="p-5 border-b border-gray-50 bg-gray-50/50 flex items-center">
                    <i class="fas fa-sticky-note text-amber-500 mr-2"></i>
                    <h2 class="font-bold text-gray-800">Observaciones y Requerimientos</h2>
                </div>
                <div class="p-6">
                    <textarea name="notes" rows="3" class="w-full border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                              placeholder="Ej: Solicitud especial, alergias, llegada tarde, decoración para aniversario..."></textarea>
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
                            <input type="number" name="total_amount" x-model="total" step="1" required
                                   class="block w-full pl-8 pr-4 py-4 bg-gray-700 border-none rounded-xl text-xl font-black text-white focus:ring-2 focus:ring-emerald-500 transition-all">
                        </div>
                        <template x-if="autoCalculatedTotal > 0 && total != autoCalculatedTotal">
                            <button type="button" @click="total = autoCalculatedTotal" class="text-[10px] font-bold text-emerald-400 hover:text-emerald-300 underline uppercase tracking-tighter">
                                Restaurar total sugerido: <span x-text="formatCurrency(autoCalculatedTotal)"></span>
                            </button>
                        </template>
                    </div>

                    <!-- Abono / Depósito -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Abono Inicial</label>
                        <div class="relative">
                            <span class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">$</span>
                            <input type="number" name="deposit" x-model="deposit" step="1" required
                                   class="block w-full pl-8 pr-4 py-3 bg-gray-700 border-none rounded-xl text-lg font-bold text-white focus:ring-2 focus:ring-blue-500 transition-all">
                        </div>
                    </div>

                    <!-- Saldo Pendiente -->
                    <div class="pt-6 border-t border-gray-700 space-y-4">
                        <div class="flex justify-between items-end">
                            <div class="space-y-1">
                                <span class="text-[10px] font-black text-gray-500 uppercase tracking-widest">Saldo Pendiente</span>
                                <p class="text-3xl font-black text-white" :class="balance < 0 ? 'text-red-400' : 'text-white'" x-text="formatCurrency(balance)"></p>
                            </div>
                            <div class="mb-1">
                                <template x-if="balance <= 0">
                                    <span class="px-3 py-1 bg-emerald-500/20 text-emerald-400 rounded-full text-[10px] font-black uppercase tracking-widest border border-emerald-500/30">
                                        Liquidado
                                    </span>
                                </template>
                                <template x-if="balance > 0">
                                    <span class="px-3 py-1 bg-amber-500/20 text-amber-400 rounded-full text-[10px] font-black uppercase tracking-widest border border-amber-500/30">
                                        Pendiente
                                    </span>
                                </template>
                            </div>
                        </div>

                        <!-- Alertas de Pago -->
                        <template x-if="balance < 0">
                            <div class="p-3 bg-red-500/20 border border-red-500/30 rounded-xl text-[10px] font-bold text-red-400 text-center animate-bounce uppercase tracking-tighter">
                                <i class="fas fa-exclamation-triangle mr-1"></i> El abono supera el total de la reserva
                            </div>
                        </template>
                    </div>
                </div>

                <!-- Footer del Resumen -->
                <div class="px-6 py-4 bg-black/20 text-center">
                    <input type="hidden" name="reservation_date" value="{{ date('Y-m-d') }}">
                    <template x-for="(guest, index) in assignedGuests" :key="guest.id">
                        <template x-if="guest && guest.id">
                            <input type="hidden" :name="`guest_ids[${index}]`" :value="guest.id">
                        </template>
                    </template>
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
    </form>

    <!-- MODAL: CREAR NUEVO CLIENTE PRINCIPAL -->
    <div x-show="newCustomerModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.self="newCustomerModalOpen = false"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all"
                 @click.stop>
                <!-- Header del Modal -->
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Crear Nuevo Cliente</h3>
                    </div>
                    <button @click="newCustomerModalOpen = false" class="text-gray-400 hover:text-gray-900 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Contenido del Modal -->
                <div class="p-6 space-y-6">
                    <div class="p-3 bg-blue-50 border border-blue-100 rounded-xl">
                        <p class="text-xs text-blue-800 font-medium">
                            <i class="fas fa-info-circle mr-2"></i>
                            Complete los datos del cliente principal para la reserva
                        </p>
                    </div>

                    <div class="grid grid-cols-1 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Nombre Completo <span class="text-red-500">*</span>
                            </label>
                            <input type="text" x-model="newMainCustomer.name"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: María González">
                        </div>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                    Documento de Identidad <span class="text-red-500">*</span>
                                </label>
                                <input type="text" x-model="newMainCustomer.identification"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: 1234567890">
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                    Teléfono <span class="text-red-500">*</span>
                                </label>
                                <input type="text" x-model="newMainCustomer.phone"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500"
                                       placeholder="Ej: 3001234567">
                            </div>
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Email (Opcional)
                            </label>
                            <input type="email" x-model="newMainCustomer.email"
                                   class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-blue-500 focus:border-blue-500"
                                   placeholder="Ej: maria@example.com">
                        </div>
                    </div>
                    <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                        <button @click="newCustomerModalOpen = false"
                                class="px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                            Cancelar
                        </button>
                        <button @click="createAndSelectMainCustomer()"
                                :disabled="!newMainCustomer.name || !newMainCustomer.identification || !newMainCustomer.phone || creatingMainCustomer"
                                class="px-6 py-2 text-sm font-bold text-white bg-blue-600 rounded-xl hover:bg-blue-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center">
                            <i class="fas fa-spinner fa-spin mr-2" x-show="creatingMainCustomer"></i>
                            <i class="fas fa-check mr-2" x-show="!creatingMainCustomer"></i>
                            <span x-text="creatingMainCustomer ? 'Creando...' : 'Crear y Seleccionar'"></span>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- MODAL: ASIGNAR HUÉSPED -->
    <div x-show="guestModalOpen"
         x-cloak
         class="fixed inset-0 z-50 overflow-y-auto"
         @click.self="guestModalOpen = false"
         style="display: none;">
        <div class="flex items-center justify-center min-h-screen p-4">
            <div class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
            <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all"
                 @click.stop>
                <!-- Header del Modal -->
                <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                    <div class="flex items-center space-x-3">
                        <div class="w-10 h-10 rounded-xl bg-purple-50 text-purple-600 flex items-center justify-center">
                            <i class="fas fa-user-plus"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Asignar Persona a la Habitación</h3>
                    </div>
                    <button @click="guestModalOpen = false" class="text-gray-400 hover:text-gray-900 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <!-- Contenido del Modal -->
                <div class="p-6 space-y-6">
                    <!-- Info de capacidad -->
                    <template x-if="selectedRoom">
                        <div class="p-3 bg-purple-50 border border-purple-100 rounded-xl">
                            <p class="text-xs text-purple-800 font-medium">
                                <i class="fas fa-info-circle mr-2"></i>
                                Capacidad de la habitación: <span class="font-bold" x-text="selectedRoom.capacity"></span> personas
                                <span class="text-gray-400 mx-2">•</span>
                                Espacios disponibles: <span class="font-bold" x-text="availableSlots"></span>
                            </p>
                        </div>
                    </template>

                    <!-- Tabs: Buscar / Crear -->
                    <div class="flex border-b border-gray-200">
                        <button @click="guestModalTab = 'search'"
                                :class="guestModalTab === 'search' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-500'"
                                class="px-4 py-2 font-bold text-sm transition-colors">
                            <i class="fas fa-search mr-2"></i> Buscar Persona
                        </button>
                        <button @click="guestModalTab = 'create'"
                                :class="guestModalTab === 'create' ? 'border-b-2 border-purple-600 text-purple-600' : 'text-gray-500'"
                                class="px-4 py-2 font-bold text-sm transition-colors">
                            <i class="fas fa-plus mr-2"></i> Crear Nueva Persona
                        </button>
                    </div>

                    <!-- Tab: Buscar -->
                    <div x-show="guestModalTab === 'search'"
                         x-transition
                         class="space-y-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Buscar por nombre, documento o teléfono
                            </label>
                            <div wire:ignore id="guest-search-container">
                                <select id="guest-search-select" class="w-full"></select>
                            </div>
                        </div>
                        <div x-show="selectedGuestForAdd" class="p-4 bg-purple-50 rounded-xl border border-purple-100">
                            <div class="flex items-center justify-between">
                                <div>
                                    <p class="font-bold text-gray-900" x-text="selectedGuestForAdd?.name || ''"></p>
                                    <div class="flex items-center space-x-3 text-xs text-gray-500 mt-1">
                                        <span x-text="'ID: ' + (selectedGuestForAdd?.identification || 'S/N')"></span>
                                        <span class="text-gray-300">•</span>
                                        <span x-text="'Tel: ' + (selectedGuestForAdd?.phone || 'S/N')"></span>
                                    </div>
                                </div>
                                <button @click="addGuest()"
                                        :disabled="!canAssignMoreGuests"
                                        :class="canAssignMoreGuests ? 'bg-purple-600 hover:bg-purple-700' : 'bg-gray-400 cursor-not-allowed'"
                                        class="px-4 py-2 text-sm font-bold text-white rounded-xl transition-all">
                                    <i class="fas fa-check mr-2"></i> Asignar
                                </button>
                            </div>
                        </div>
                    </div>

                    <!-- Tab: Crear -->
                    <div x-show="guestModalTab === 'create'" class="space-y-4">
                        <div class="grid grid-cols-1 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                    Nombre Completo <span class="text-red-500">*</span>
                                </label>
                                <input type="text" x-model="newCustomer.name"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500"
                                       placeholder="Ej: María González">
                            </div>
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Documento de Identidad <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" x-model="newCustomer.identification"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500"
                                           placeholder="Ej: 1234567890">
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Teléfono <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" x-model="newCustomer.phone"
                                           class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500"
                                           placeholder="Ej: 3001234567">
                                </div>
                            </div>
                            <div>
                                <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                    Email (Opcional)
                                </label>
                                <input type="email" x-model="newCustomer.email"
                                       class="w-full px-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-purple-500 focus:border-purple-500"
                                       placeholder="Ej: maria@example.com">
                            </div>
                        </div>
                        <div class="flex justify-end space-x-3 pt-4 border-t border-gray-100">
                            <button @click="guestModalOpen = false"
                                    class="px-4 py-2 text-sm font-semibold text-gray-600 bg-white border border-gray-200 rounded-xl hover:bg-gray-50 transition-all">
                                Cancelar
                            </button>
                            <button @click="createAndAddGuest()"
                                    :disabled="!newCustomer.name || !newCustomer.identification || !newCustomer.phone || creatingCustomer || !canAssignMoreGuests"
                                    class="px-6 py-2 text-sm font-bold text-white bg-purple-600 rounded-xl hover:bg-purple-700 disabled:opacity-50 disabled:cursor-not-allowed transition-all flex items-center">
                                <i class="fas fa-spinner fa-spin mr-2" x-show="creatingCustomer"></i>
                                <i class="fas fa-check mr-2" x-show="!creatingCustomer"></i>
                                <span x-text="creatingCustomer ? 'Creando...' : 'Crear y Asignar'"></span>
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    /* Ensure TomSelect dropdown appears above modal */
    .ts-dropdown {
        z-index: 10000 !important;
    }

    /* Ensure the dropdown container in modal has proper z-index */
    [x-show="guestModalOpen"] .ts-dropdown,
    [x-show="guestModalOpen"] .ts-wrapper .ts-dropdown {
        z-index: 10001 !important;
        position: absolute !important;
    }

    /* Style for the search container */
    #guest-search-container {
        position: relative;
    }

    #guest-search-container .ts-wrapper {
        position: relative;
    }

    /* Modal z-index override */
    [x-show="guestModalOpen"] {
        z-index: 50;
    }

    [x-show="guestModalOpen"] .relative.bg-white {
        z-index: 51;
    }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
function reservationForm() {
    return {
        loading: false,
        isChecking: false,
        availability: null,

        customerId: '',
        roomId: '',
        checkIn: '',
        checkOut: '',
        total: 0,
        deposit: 0,
        guestsCount: 1,

        rooms: @json($roomsData),
        customerSelect: null,
        roomSelect: null,

        // Guest assignment modal
        guestModalOpen: false,
        guestModalTab: 'search',
        assignedGuests: [],
        selectedGuestForAdd: null,
        newCustomer: {
            name: '',
            identification: '',
            phone: '',
            email: ''
        },
        creatingCustomer: false,
        guestSelect: null,

        // New customer modal (for main customer)
        newCustomerModalOpen: false,
        newMainCustomer: {
            name: '',
            identification: '',
            phone: '',
            email: ''
        },
        creatingMainCustomer: false,

        init() {
            this.initSelectors();

            // Re-calcular disponibilidad cuando cambien los datos clave
            this.$watch('roomId', () => this.checkAvailability());
            this.$watch('checkIn', () => {
                this.checkAvailability();
                this.recalculateTotal();
            });
            this.$watch('checkOut', () => {
                this.checkAvailability();
                this.recalculateTotal();
            });
            this.$watch('guestsCount', () => {
                this.recalculateTotal();
            });

            // Watch for guest modal tab changes to reinitialize selector
            this.$watch('guestModalTab', (newTab) => {
                if (newTab === 'search' && this.guestModalOpen) {
                    this.$nextTick(() => {
                        setTimeout(() => {
                            this.initGuestSelector();
                        }, 100);
                    });
                }
            });
        },

        initSelectors() {
            this.customerSelect = new TomSelect('#customer_id', {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'identification', 'phone'],
                loadThrottle: 400,
                maxOptions: 5,
                minLength: 0,
                placeholder: 'Buscar por nombre, identificación o teléfono...',
                dropdownParent: 'body',
                shouldLoad: () => {
                    return true; // Always allow loading
                },
                load: (query, callback) => {
                    const searchQuery = query || '';
                    const url = `/api/customers/search?q=${encodeURIComponent(searchQuery)}`;
                    fetch(url, {
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest'
                        }
                    })
                        .then(response => response.json())
                        .then(json => {
                            callback(json.results || []);
                        }).catch(() => {
                            callback();
                        });
                },
                render: {
                    option: function(item, escape) {
                        return `
                            <div class="px-4 py-3 border-b border-gray-50 hover:bg-emerald-50 transition-colors">
                                <div class="flex flex-col">
                                    <span class="font-bold text-gray-900 text-sm mb-1">${escape(item.name)}</span>
                                    <div class="flex items-center space-x-3">
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                            <i class="fas fa-id-card mr-1 opacity-50"></i> ID: ${escape(item.identification || 'S/N')}
                                        </span>
                                        <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-600">
                                            <i class="fas fa-phone mr-1 opacity-50"></i> ${escape(item.phone || 'S/N')}
                                        </span>
                                    </div>
                                </div>
                            </div>`;
                    },
                    item: function(item, escape) {
                        return `<div class="font-bold text-gray-800">${escape(item.name)} <span class="text-gray-400 font-normal ml-1">(${escape(item.identification || 'S/N')})</span></div>`;
                    },
                    no_results: (data) => `<div class="px-4 py-3 text-sm text-gray-500 italic">No se encontraron resultados para "${data.input}"</div>`,
                    loading: () => `<div class="px-4 py-3 text-sm text-gray-500 flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</div>`
                },
                onChange: (val) => this.customerId = val,
                onFocus: () => {
                    // Load last 5 customers when field receives focus
                    if (!this.customerSelect.isLoading && this.customerSelect.options.length === 0) {
                        this.customerSelect.load('');
                    }
                },
                onType: (str) => {
                    // Force load when user types
                    this.customerSelect.load(str || '');
                }
            });

            // Load initial customers when selector is ready
            this.$nextTick(() => {
                if (this.customerSelect) {
                    this.customerSelect.load('');
                }
            });

            this.roomSelect = new TomSelect('#room_id', {
                create: false,
                dropdownParent: 'body',
                render: {
                    option: function(data, escape) {
                        return `<div class="px-4 py-2 hover:bg-emerald-50 transition-colors"><strong>${escape(data.text)}</strong></div>`;
                    }
                },
                onChange: (val) => this.roomId = val
            });
        },

        get selectedRoom() {
            return this.rooms.find(r => r.id == this.roomId) || null;
        },

        get selectedCustomerInfo() {
            if (!this.customerId) return null;
            const option = this.customerSelect.options[this.customerId];
            if (!option) return null;
            return {
                id: option.identification || 'S/N',
                phone: option.phone || 'S/N'
            };
        },

        get nights() {
            if (!this.checkIn || !this.checkOut) return 0;
            const start = new Date(this.checkIn);
            const end = new Date(this.checkOut);
            const diff = Math.ceil((end - start) / (1000 * 60 * 60 * 24));
            return diff > 0 ? diff : 0;
        },

        getPriceForGuests() {
            if (!this.selectedRoom || !this.guestsCount || this.guestsCount <= 0) {
                // Return default price (1 person) if no guests selected
                const prices = this.selectedRoom?.occupancyPrices || {};
                return prices[1] || this.selectedRoom?.price || 0;
            }

            const prices = this.selectedRoom.occupancyPrices || {};
            const guestCount = Math.min(this.guestsCount, this.selectedRoom.capacity);

            // Get price for the number of guests, fallback to highest available or default
            if (prices[guestCount] !== undefined) {
                return prices[guestCount];
            }

            // If exact price not found, use the highest available price for that capacity or less
            let price = 0;
            for (let i = guestCount; i >= 1; i--) {
                if (prices[i] !== undefined) {
                    price = prices[i];
                    break;
                }
            }

            // Final fallback to legacy price
            return price || this.selectedRoom.price || 0;
        },

        get autoCalculatedTotal() {
            if (!this.selectedRoom || this.nights <= 0) return 0;
            const pricePerNight = this.getPriceForGuests();
            return pricePerNight * this.nights;
        },

        get balance() {
            return this.total - this.deposit;
        },

        get isValid() {
            return this.customerId &&
                   this.roomId &&
                   this.checkIn &&
                   this.checkOut &&
                   this.nights > 0 &&
                   this.availability === true &&
                   this.balance >= 0 &&
                   this.isWithinCapacity();
        },

        get canAssignMoreGuests() {
            if (!this.selectedRoom) return false;
            const totalGuests = 1 + this.assignedGuests.length; // 1 for main customer
            return totalGuests < this.selectedRoom.capacity;
        },

        get availableSlots() {
            if (!this.selectedRoom) return 0;
            const totalGuests = 1 + this.assignedGuests.length; // 1 for main customer
            return Math.max(0, this.selectedRoom.capacity - totalGuests);
        },

        isWithinCapacity() {
            if (!this.selectedRoom) return true;
            const totalGuests = 1 + this.assignedGuests.length; // 1 for main customer
            return totalGuests <= this.selectedRoom.capacity;
        },

        recalculateTotal() {
            // Solo auto-asignar si el total actual es 0 o coincide con el cálculo anterior
            // Esto permite al usuario editar el total manualmente si lo desea
            if (this.autoCalculatedTotal > 0) {
                this.total = this.autoCalculatedTotal;
            }
        },

        async checkAvailability() {
            if (!this.roomId || !this.checkIn || !this.checkOut || this.nights <= 0) {
                this.availability = null;
                return;
            }

            this.isChecking = true;
            try {
                const url = `{{ route('api.check-availability') }}?room_id=${this.roomId}&check_in_date=${this.checkIn}&check_out_date=${this.checkOut}`;
                const response = await fetch(url);
                const data = await response.json();
                this.availability = data.available;
            } catch (error) {
                console.error('Error checking availability:', error);
                this.availability = null;
            } finally {
                this.isChecking = false;
            }
        },

        formatCurrency(val) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency',
                currency: 'COP',
                minimumFractionDigits: 0
            }).format(val);
        },

        // Guest assignment methods
        openGuestModal() {
            this.guestModalOpen = true;
            this.guestModalTab = 'search';
            this.selectedGuestForAdd = null;
            this.newCustomer = { name: '', identification: '', phone: '', email: '' };

            // Initialize guest selector when modal opens
            this.$nextTick(() => {
                setTimeout(() => {
                    this.initGuestSelector();
                }, 100);
            });
        },

        initGuestSelector() {
            const selectElement = document.getElementById('guest-search-select');
            if (!selectElement) return;

            // Destroy existing instance if it exists
            if (this.guestSelect) {
                try {
                    this.guestSelect.destroy();
                } catch (e) {
                    console.warn('Error destroying guest select:', e);
                }
                this.guestSelect = null;
            }

            // Create new instance
            try {
                this.guestSelect = new TomSelect('#guest-search-select', {
                    valueField: 'id',
                    labelField: 'name',
                    searchField: ['name', 'identification', 'phone'],
                    loadThrottle: 300,
                    maxOptions: 5,
                    minLength: 0,
                    placeholder: 'Escribe nombre, documento o teléfono...',
                    dropdownParent: 'body',
                    shouldLoad: () => {
                        return true; // Always allow loading
                    },
                    load: (query, callback) => {
                        const searchQuery = query || '';
                        const url = `/api/customers/search?q=${encodeURIComponent(searchQuery)}`;
                        fetch(url, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                            .then(response => {
                                if (!response.ok) {
                                    throw new Error('Network response was not ok');
                                }
                                return response.json();
                            })
                            .then(json => {
                                const results = json.results || [];
                                // Limit to 5 results
                                callback(results.slice(0, 5));
                            }).catch((error) => {
                                console.error('Error loading guests:', error);
                                callback();
                            });
                    },
                    render: {
                        option: (item, escape) => {
                            return `
                                <div class="px-4 py-3 border-b border-gray-50 hover:bg-purple-50 transition-colors cursor-pointer">
                                    <div class="flex flex-col">
                                        <span class="font-bold text-gray-900 text-sm mb-1">${escape(item.name)}</span>
                                        <div class="flex items-center space-x-3">
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-gray-100 text-gray-600">
                                                <i class="fas fa-id-card mr-1 opacity-50"></i> ID: ${escape(item.identification || 'S/N')}
                                            </span>
                                            <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-medium bg-blue-50 text-blue-600">
                                                <i class="fas fa-phone mr-1 opacity-50"></i> ${escape(item.phone || 'S/N')}
                                            </span>
                                        </div>
                                    </div>
                                </div>`;
                        },
                        item: (item, escape) => {
                            return `<div class="font-bold text-gray-800">${escape(item.name)} <span class="text-gray-400 font-normal ml-1">(${escape(item.identification || 'S/N')})</span></div>`;
                        },
                        no_results: (data) => `<div class="px-4 py-3 text-sm text-gray-500 italic">No se encontraron resultados para "${escape(data.input)}"</div>`,
                        loading: () => `<div class="px-4 py-3 text-sm text-gray-500 flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i> Buscando...</div>`,
                        loading_more: () => `<div class="px-4 py-3 text-sm text-gray-500 flex items-center"><i class="fas fa-spinner fa-spin mr-2"></i> Cargando más resultados...</div>`
                    },
                    onChange: (val) => {
                        if (val) {
                            const option = this.guestSelect.options[val];
                            if (option) {
                                this.selectedGuestForAdd = {
                                    id: option.id,
                                    name: option.name,
                                    identification: option.identification || 'S/N',
                                    phone: option.phone || 'S/N'
                                };
                            }
                        } else {
                            this.selectedGuestForAdd = null;
                        }
                    },
                    onFocus: () => {
                        // Load clients when field receives focus
                        if (!this.guestSelect.isLoading && this.guestSelect.options.length === 0) {
                            this.guestSelect.load('');
                        }
                    },
                    onType: (str) => {
                        // Force load when user types
                        this.guestSelect.load(str || '');
                    }
                });

                // Load initial clients when selector is ready
                this.$nextTick(() => {
                    if (this.guestSelect) {
                        this.guestSelect.load('');
                    }
                });
            } catch (error) {
                console.error('Error initializing guest selector:', error);
            }
        },

        addGuest() {
            if (!this.selectedGuestForAdd || !this.selectedGuestForAdd.id) return;

            // Check if guest is already assigned
            const alreadyAssigned = this.assignedGuests.some(g => g.id === this.selectedGuestForAdd.id);
            if (alreadyAssigned) {
                alert('Este cliente ya está asignado a la habitación');
                return;
            }

            // Check if adding this guest would exceed capacity
            if (!this.canAssignMoreGuests) {
                alert('No se pueden asignar más huéspedes. La habitación ha alcanzado su capacidad máxima de ' + this.selectedRoom.capacity + ' personas.');
                return;
            }

            // Check if main customer is the same as the guest being added
            if (this.customerId && this.selectedGuestForAdd.id == this.customerId) {
                alert('El cliente principal ya está incluido en la reserva. No es necesario asignarlo nuevamente.');
                return;
            }

            this.assignedGuests.push({ ...this.selectedGuestForAdd });
            this.selectedGuestForAdd = null;
            if (this.guestSelect) {
                this.guestSelect.clear();
            }
        },

        removeGuest(index) {
            this.assignedGuests.splice(index, 1);
        },

        async createAndAddGuest() {
            if (!this.newCustomer.name || !this.newCustomer.identification || !this.newCustomer.phone) {
                alert('Por favor complete todos los campos requeridos');
                return;
            }

            this.creatingCustomer = true;
            try {
                const response = await fetch('{{ route("customers.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.newCustomer.name,
                        identification: this.newCustomer.identification,
                        phone: this.newCustomer.phone,
                        email: this.newCustomer.email || null,
                        is_active: true,
                        requires_electronic_invoice: false,
                        identification_document_id: 3 // Default to CC
                    })
                });

                const data = await response.json();

                if (data.success && data.customer) {
                    // Check if customer is already assigned
                    const alreadyAssigned = this.assignedGuests.some(g => g.id === data.customer.id);
                    if (alreadyAssigned) {
                        alert('Este cliente ya está asignado a la habitación');
                    } else if (!this.canAssignMoreGuests) {
                        alert('No se pueden asignar más huéspedes. La habitación ha alcanzado su capacidad máxima de ' + this.selectedRoom.capacity + ' personas.');
                    } else {
                        this.assignedGuests.push({
                            id: data.customer.id,
                            name: data.customer.name,
                            identification: data.customer.tax_profile?.identification || this.newCustomer.identification,
                            phone: this.newCustomer.phone
                        });
                        this.newCustomer = { name: '', identification: '', phone: '', email: '' };
                        this.guestModalOpen = false;
                    }
                } else {
                    const errors = data.errors || {};
                    const errorMessages = Object.values(errors).flat().join('\n');
                    alert('Error al crear el cliente: ' + (errorMessages || data.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error creating customer:', error);
                alert('Error al crear el cliente. Por favor intente nuevamente.');
            } finally {
                this.creatingCustomer = false;
            }
        },

        // New customer modal methods (for main customer)
        openNewCustomerModal() {
            this.newCustomerModalOpen = true;
            this.newMainCustomer = { name: '', identification: '', phone: '', email: '' };
        },

        async createAndSelectMainCustomer() {
            if (!this.newMainCustomer.name || !this.newMainCustomer.identification || !this.newMainCustomer.phone) {
                alert('Por favor complete todos los campos requeridos');
                return;
            }

            this.creatingMainCustomer = true;
            try {
                const response = await fetch('{{ route("customers.store") }}', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-CSRF-TOKEN': '{{ csrf_token() }}',
                        'X-Requested-With': 'XMLHttpRequest',
                        'Accept': 'application/json'
                    },
                    body: JSON.stringify({
                        name: this.newMainCustomer.name,
                        identification: this.newMainCustomer.identification,
                        phone: this.newMainCustomer.phone,
                        email: this.newMainCustomer.email || null,
                        is_active: true,
                        requires_electronic_invoice: false,
                        identification_document_id: 3 // Default to CC
                    })
                });

                const data = await response.json();

                if (data.success && data.customer) {
                    // Add the new customer to the selector
                    if (this.customerSelect) {
                        this.customerSelect.addOption({
                            id: data.customer.id,
                            name: data.customer.name,
                            identification: data.customer.tax_profile?.identification || this.newMainCustomer.identification,
                            phone: this.newMainCustomer.phone || 'S/N'
                        });
                        // Select the newly created customer
                        this.customerSelect.setValue(data.customer.id);
                        this.customerId = data.customer.id;
                    }
                    // Close modal and reset form
                    this.newMainCustomer = { name: '', identification: '', phone: '', email: '' };
                    this.newCustomerModalOpen = false;
                } else {
                    const errors = data.errors || {};
                    const errorMessages = Object.values(errors).flat().join('\n');
                    alert('Error al crear el cliente: ' + (errorMessages || data.message || 'Error desconocido'));
                }
            } catch (error) {
                console.error('Error creating customer:', error);
                alert('Error al crear el cliente. Por favor intente nuevamente.');
            } finally {
                this.creatingMainCustomer = false;
            }
        }
    }
}
</script>
@endpush
@endsection
