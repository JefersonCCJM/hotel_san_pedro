@props(['room', 'statuses', 'isOccupied', 'ventilation_types'])

@php
    $computedCapacity = old('max_capacity', $room->max_capacity ?? 2);
    $roomStatusValue = $room->status instanceof \App\Enums\RoomStatus
        ? $room->status->value
        : ((is_string($room->status) && $room->status !== '') ? $room->status : \App\Enums\RoomStatus::LIBRE->value);
    $standardPrices = [];
    for ($i = 1; $i <= $computedCapacity; $i++) {
        $rate = optional($room->rates)->first(function($r) use ($i) {
            return $i >= $r->min_guests && $i <= $r->max_guests;
        });
        $standardPrices[$i] = $rate?->price_per_night ?? ($room->base_price_per_night ?? 0);
    }
@endphp

<div x-show="roomEditModal"
     x-cloak
     class="fixed inset-0 z-50 overflow-y-auto"
     style="display: none;"
     x-data="{
        roomEditModal: @entangle('roomEditModal'),
        tab: 'config',
        beds: {{ old('beds_count', $room->beds_count ?? 1) }},
        capacity: {{ $computedCapacity }},
        autoCalculate: false,
        prices: {{ json_encode($standardPrices) }},

        // Para nueva tarifa especial
        showRateModal: false,
        newRate: {
            event_name: '',
            start_date: '',
            end_date: '',
            prices: {}
        },

        updateCapacity() {
            // Limitar el número de camas a máximo 15
            if(this.beds > 15) {
                this.beds = 15;
            }
            // Asegurar mínimo de 1
            if(this.beds < 1) {
                this.beds = 1;
            }

            if(this.autoCalculate) {
                this.capacity = this.beds * 2;
            }
            for (let i = 1; i <= this.capacity; i++) {
                if (this.prices[i] === undefined) this.prices[i] = this.prices[i-1] || 0;
                if (this.newRate.prices[i] === undefined) this.newRate.prices[i] = 0;
            }
        },
        formatCurrency(val) {
            return new Intl.NumberFormat('es-CO', {
                style: 'currency', currency: 'COP', minimumFractionDigits: 0
            }).format(val);
        }
    }"
     x-init="updateCapacity()">
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="roomEditModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden transform transition-all">
            <!-- Header -->
            <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-edit"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Editar Habitación #{{ $room->room_number }}</h3>
                </div>
                <button @click="roomEditModal = false" class="text-gray-400 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Tab Navigation -->
            <div class="px-8 pt-6">
                <div class="flex items-center space-x-2 bg-gray-50 p-1.5 rounded-xl w-fit">
                    <button @click="tab = 'config'"
                            :class="tab === 'config' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                        <i class="fas fa-cog mr-2"></i> Configuración
                    </button>
                    <button @click="tab = 'rates'"
                            :class="tab === 'rates' ? 'bg-white text-gray-900 shadow-sm' : 'text-gray-500 hover:text-gray-700'"
                            class="px-4 py-2 rounded-lg text-xs font-bold uppercase tracking-widest transition-all">
                        <i class="fas fa-calendar-star mr-2"></i> Tarifas Especiales
                    </button>
                </div>
            </div>

            <!-- Content -->
            <div class="p-8 space-y-8">
                <!-- Tab: Configuración Base -->
                <div x-show="tab === 'config'" x-transition>
                    <form action="{{ route('rooms.update', $room) }}" method="POST" class="space-y-8"
                          onsubmit="setTimeout(() => { window.location.reload(); }, 100);">
                        @csrf
                        @method('PUT')

                        <div class="grid grid-cols-1 lg:grid-cols-2 gap-8">
                            <div class="space-y-6">
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Número</label>
                                        <input type="text" name="room_number" value="{{ $room->room_number }}" required
                                            class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                    </div>
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Estado</label>
                                        <select name="status" required
                                            class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none appearance-none"
                                            {{ $isOccupied ? 'disabled' : '' }}>
                                            @foreach($statuses as $status)
                                                @if($isOccupied && ($status === \App\Enums\RoomStatus::OCUPADA || $status === \App\Enums\RoomStatus::LIBRE))
                                                    @continue
                                                @endif
                                                <option value="{{ $status->value }}" {{ $roomStatusValue === $status->value ? 'selected' : '' }}>{{ $status->label() }}</option>
                                            @endforeach
                                        </select>
                                        @if($isOccupied)
                                            <input type="hidden" name="status" value="{{ $roomStatusValue }}">
                                            <p class="text-xs text-amber-600 mt-1">
                                                <i class="fas fa-info-circle"></i> La ocupación se calcula desde reservas. Solo se pueden cambiar estados manuales.
                                            </p>
                                        @endif
                                    </div>
                                </div>
                                <div class="grid grid-cols-2 gap-4">
                                    <div class="space-y-2">
                                        <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Camas</label>
                                        <input type="number" name="beds_count" x-model="beds" @input="updateCapacity()" required min="1" max="15"
                                            oninput="if(this.value > 15) this.value = 15; if(this.value < 1) this.value = 1;"
                                            class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                    </div>
                                    <div class="space-y-2">
                                        <div class="flex justify-between items-center ml-1">
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest">Capacidad Máx.</label>
                                            <label class="flex items-center cursor-pointer">
                                                <input type="checkbox" x-model="autoCalculate" @change="updateCapacity()" class="sr-only peer">
                                                <div class="relative w-7 h-4 bg-gray-200 rounded-full peer peer-checked:bg-blue-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all"></div>
                                            </label>
                                        </div>
                                        <input type="number" name="max_capacity" x-model="capacity" @input="updateCapacity()" :readonly="autoCalculate" required
                                            class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none"
                                            :class="autoCalculate ? 'opacity-60' : ''">
                                    </div>
                                </div>

                                <div class="space-y-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Tipo de Ventilación</label>
                                    <select name="ventilation_type_id" required
                                        class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none appearance-none">
                                        <option value="">Seleccione...</option>
                                        @foreach($ventilation_types as $ventilationType)
                                            <option value="{{ $ventilationType->id }}" {{ (old('ventilation_type_id', $room->ventilation_type_id ?? null) == $ventilationType->id) ? 'selected' : '' }}>
                                                {{ $ventilationType->name }}
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            </div>

                            <div class="space-y-4">
                                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest flex items-center">
                                    <i class="fas fa-money-bill-wave mr-2 text-blue-600"></i> Precios Estándar
                                </h4>
                                <div class="bg-gray-50 rounded-xl p-4 border border-gray-100 space-y-3 max-h-[400px] overflow-y-auto">
                                    <template x-for="i in parseInt(capacity)" :key="i">
                                        <div class="space-y-1">
                                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1"
                                                   x-text="'Precio para ' + i + (i == 1 ? ' Persona' : ' Personas')"></label>
                                            <div class="relative">
                                                <div class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 font-bold text-sm">$</div>
                                                <input type="number" :name="'occupancy_prices[' + i + ']'" x-model="prices[i]" required
                                                    class="block w-full pl-8 pr-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                            </div>
                                        </div>
                                    </template>
                                </div>
                            </div>
                        </div>

                        <div class="pt-6 flex flex-col sm:flex-row gap-4 border-t border-gray-100">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm">
                                Actualizar Habitación
                            </button>
                            <button type="button" @click="roomEditModal = false" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>

                <!-- Tab: Tarifas Especiales -->
                <div x-show="tab === 'rates'" x-transition x-cloak>
                    <div class="space-y-6">
                        <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                            <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Tarifas por Fechas</h4>
                            <button @click="showRateModal = true" class="text-[10px] font-bold text-blue-600 uppercase hover:text-blue-800">
                                <i class="fas fa-plus mr-1"></i> Nueva Tarifa
                            </button>
                        </div>

                        <div class="space-y-3">
                            @forelse($room->rates->sortBy('start_date') as $rate)
                            <div class="group bg-gray-50 rounded-xl border border-gray-100 p-4 flex items-center justify-between hover:bg-white hover:shadow-sm transition-all">
                                <div class="flex items-center space-x-4">
                                    <div class="w-10 h-10 rounded-lg bg-amber-50 text-amber-600 flex items-center justify-center">
                                        <i class="fas fa-calendar-day"></i>
                                    </div>
                                    <div>
                                        <h4 class="text-sm font-bold text-gray-900">{{ $rate->event_name ?? 'Evento Especial' }}</h4>
                                        <p class="text-xs text-gray-500">
                                            @php
                                                // NOTA: Estos son fechas de eventos especiales, no horas de hotel
                                                // Mantenemos Carbon::parse para fechas de eventos (no relacionado con horarios hoteleros)
                                                $startDate = $rate->start_date ? \Carbon\Carbon::parse($rate->start_date)->format('d/m/Y') : 'Sin fecha';
                                                $endDate = $rate->end_date ? \Carbon\Carbon::parse($rate->end_date)->format('d/m/Y') : 'Sin fecha';
                                            @endphp
                                            {{ $startDate }} — {{ $endDate }}
                                        </p>
                                    </div>
                                </div>
                                <div class="flex items-center space-x-4">
                                    <div class="text-right">
                                        <p class="text-[9px] font-bold text-gray-400 uppercase">Precio Base</p>
                                        <p class="text-sm font-bold text-amber-600">${{ number_format($rate->occupancy_prices[1] ?? 0, 0, ',', '.') }}</p>
                                    </div>
                                    <form action="{{ route('rooms.rates.destroy', [$room, $rate]) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarifa especial?')">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="w-8 h-8 rounded-lg bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all flex items-center justify-center">
                                            <i class="fas fa-trash-alt text-xs"></i>
                                        </button>
                                    </form>
                                </div>
                            </div>
                            @empty
                            <div class="py-12 text-center border-2 border-dashed border-gray-100 rounded-xl">
                                <i class="fas fa-calendar-times text-4xl text-gray-200 mb-4"></i>
                                <p class="text-gray-400 font-medium text-sm">No hay tarifas especiales configuradas.</p>
                            </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Modal: Nueva Tarifa Especial -->
        <div x-show="showRateModal" class="fixed inset-0 z-[60] overflow-y-auto" x-cloak style="display: none;">
            <div class="flex items-center justify-center min-h-screen p-4">
                <div @click="showRateModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>

                <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all" @click.stop>
                    <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 rounded-xl bg-amber-50 text-amber-600 flex items-center justify-center">
                                <i class="fas fa-calendar-plus"></i>
                            </div>
                            <h3 class="text-xl font-bold text-gray-900">Nueva Tarifa Especial</h3>
                        </div>
                        <button @click="showRateModal = false" class="text-gray-400 hover:text-gray-900">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>

                    <form action="{{ route('rooms.rates.store', $room) }}" method="POST" class="p-8 space-y-6">
                        @csrf
                        <div class="space-y-2">
                            <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Nombre del Evento / Temporada</label>
                            <input type="text" name="event_name" placeholder="Ej: Semana Santa" required
                                   class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Desde</label>
                                <input type="date" name="start_date" required
                                       class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Hasta</label>
                                <input type="date" name="end_date" required
                                       class="block w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                            </div>
                        </div>

                        <div class="space-y-4 pt-4 border-t border-gray-100">
                            <label class="block text-[10px] font-bold text-gray-500 uppercase tracking-widest ml-1">Esquema de Precios para esta fecha</label>
                            <div class="grid grid-cols-2 gap-4 max-h-[300px] overflow-y-auto p-2">
                                <template x-for="i in parseInt(capacity)" :key="'rate-'+i">
                                    <div class="space-y-1">
                                        <label class="text-[9px] font-bold text-gray-400 uppercase" x-text="i + (i==1 ? ' Persona' : ' Personas')"></label>
                                        <div class="relative">
                                            <div class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-xs">$</div>
                                            <input type="number" :name="'occupancy_prices[' + i + ']'" x-model="newRate.prices[i]" required
                                                   class="block w-full pl-6 pr-3 py-2 bg-white border border-gray-200 rounded-lg text-sm font-bold text-gray-900 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 outline-none">
                                        </div>
                                    </div>
                                </template>
                            </div>
                        </div>

                        <div class="pt-6 flex space-x-4">
                            <button type="submit" class="flex-1 bg-blue-600 text-white py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm">
                                Guardar Tarifa Especial
                            </button>
                            <button type="button" @click="showRateModal = false" class="flex-1 bg-gray-100 text-gray-600 py-3 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">
                                Cancelar
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>
