@extends('layouts.app')

@section('title', 'Nueva Reserva')
@section('header', 'Nueva Reserva')

@section('content')
<div class="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8" x-data="reservationForm()">
    <!-- Header Contextual -->
    <div class="mb-6 flex flex-col md:flex-row md:items-center md:justify-between gap-4">
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
                    <a href="{{ route('customers.create') }}" target="_blank" class="text-xs font-bold text-blue-600 hover:text-blue-800 flex items-center">
                        <i class="fas fa-plus-circle mr-1"></i> NUEVO CLIENTE
                    </a>
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
                                    <span class="font-bold text-gray-900" x-text="formatCurrency(selectedRoom.price)"></span>
                                </div>
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

                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6 pt-4 border-t border-gray-50">
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

                    <!-- Método de Pago -->
                    <div class="space-y-2">
                        <label class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Método de Pago del Abono</label>
                        <select name="payment_method" required class="block w-full px-4 py-3 bg-gray-700 border-none rounded-xl text-sm font-bold text-white focus:ring-2 focus:ring-blue-500 transition-all outline-none appearance-none">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                        </select>
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
</div>

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    /* UX/IX Improvements for TomSelect */
    .ts-wrapper.single .ts-control {
        border-radius: 0.85rem !important;
        padding: 0.75rem 1rem !important;
        border: 1px solid #e2e8f0 !important;
        background-color: #f8fafc !important;
        transition: all 0.2s ease;
    }
    .ts-wrapper.single.focus .ts-control {
        border-color: #10b981 !important;
        background-color: #fff !important;
        box-shadow: 0 0 0 3px rgba(16, 185, 129, 0.1) !important;
    }
    .ts-dropdown {
        border-radius: 1rem !important;
        margin-top: 0.5rem !important;
        box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1) !important;
        border: 1px solid #f1f5f9 !important;
        z-index: 9999 !important; /* Ensure it's above everything when appended to body */
    }
    .ts-dropdown-content {
        max-height: 400px !important;
    }
    .ts-dropdown .active {
        background-color: #f0fdf4 !important;
        color: #064e3b !important;
    }
    .animate-fadeIn { animation: fadeIn 0.3s ease-out forwards; }
    @keyframes fadeIn { from { opacity: 0; transform: translateY(5px); } to { opacity: 1; transform: translateY(0); } }
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

        rooms: @json($roomsData),
        customerSelect: null,
        roomSelect: null,

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
        },

        initSelectors() {
            this.customerSelect = new TomSelect('#customer_id', {
                valueField: 'id',
                labelField: 'name',
                searchField: ['name', 'identification', 'phone'],
                loadThrottle: 400,
                maxOptions: 10,
                placeholder: 'Buscar por nombre, identificación o teléfono...',
                dropdownParent: 'body',
                load: function(query, callback) {
                    if (!query.length) return callback();
                    const url = `/api/customers/search?q=${encodeURIComponent(query)}`;
                    fetch(url)
                        .then(response => response.json())
                        .then(json => {
                            callback(json.results);
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
                onChange: (val) => this.customerId = val
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

        get autoCalculatedTotal() {
            if (!this.selectedRoom || this.nights <= 0) return 0;
            return this.selectedRoom.price * this.nights;
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
                   this.balance >= 0;
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
        }
    }
}
</script>
@endpush
@endsection
