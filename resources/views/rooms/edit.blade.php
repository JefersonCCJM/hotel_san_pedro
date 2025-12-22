@extends('layouts.app')

@section('title', 'Editar Habitación')
@section('header', 'Editar Habitación #' . $room->room_number)

@section('content')
<div class="max-w-7xl mx-auto" x-data="{ 
    tab: 'config',
    beds: {{ old('beds_count', $room->beds_count) }}, 
    capacity: {{ old('max_capacity', $room->max_capacity) }},
    autoCalculate: false,
    prices: {{ json_encode(old('occupancy_prices', $room->occupancy_prices ?? [1 => 0, 2 => 0])) }},
    
    // Para nueva tarifa especial
    showRateModal: false,
    newRate: {
        event_name: '',
        start_date: '',
        end_date: '',
        prices: {}
    },

    updateCapacity() {
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
}" x-init="updateCapacity()">

    <!-- Tab Navigation -->
    <div class="flex items-center space-x-2 mb-6 bg-white p-1.5 rounded-2xl border border-gray-100 shadow-sm w-fit">
        <button @click="tab = 'config'" :class="tab === 'config' ? 'bg-indigo-600 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50'"
            class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
            <i class="fas fa-cog mr-2"></i> Configuración
        </button>
        <button @click="tab = 'rates'" :class="tab === 'rates' ? 'bg-amber-500 text-white shadow-md' : 'text-gray-500 hover:bg-gray-50'"
            class="px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest transition-all">
            <i class="fas fa-calendar-star mr-2"></i> Tarifas Especiales
        </button>
    </div>

    <!-- Tab: Configuración Base -->
    <div x-show="tab === 'config'" x-transition>
        <div class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
            <div class="bg-indigo-600 px-8 py-10 text-white relative">
                <div class="flex items-center space-x-4">
                    <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl shadow-inner">
                        <i class="fas fa-edit"></i>
                    </div>
                    <div>
                        <h1 class="text-3xl font-black tracking-tight">Editar Habitación #{{ $room->room_number }}</h1>
                        <p class="text-indigo-100 font-bold text-sm uppercase tracking-widest opacity-80">Configuración Base y Precios Estándar</p>
                    </div>
                </div>
            </div>

            <form action="{{ route('rooms.update', $room) }}" method="POST" class="p-8 space-y-8">
                @csrf
                @method('PUT')

                <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                    <div class="space-y-8">
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Número</label>
                                <input type="text" name="room_number" value="{{ $room->room_number }}" required class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold">
                            </div>
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Estado</label>
                                <select name="status" required class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold appearance-none" {{ $isOccupied ? 'disabled' : '' }}>
                                    @foreach($statuses as $status)
                                        @if($isOccupied && ($status === \App\Enums\RoomStatus::OCUPADA || $status === \App\Enums\RoomStatus::LIBRE))
                                            @continue
                                        @endif
                                        <option value="{{ $status->value }}" {{ $room->status == $status ? 'selected' : '' }}>{{ $status->label() }}</option>
                                    @endforeach
                                </select>
                                @if($isOccupied)
                                    <input type="hidden" name="status" value="{{ $room->status->value }}">
                                    <p class="text-xs text-amber-600 mt-1">
                                        <i class="fas fa-info-circle"></i> La ocupación se calcula desde reservas. Solo se pueden cambiar estados manuales (Mantenimiento, Limpieza, Sucia).
                                    </p>
                                @endif
                            </div>
                        </div>
                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Camas</label>
                                <input type="number" name="beds_count" x-model="beds" @input="updateCapacity()" required class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold">
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center ml-1">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Capacidad Máx.</label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="autoCalculate" @change="updateCapacity()" class="sr-only peer">
                                        <div class="w-7 h-4 bg-gray-200 rounded-full peer peer-checked:bg-indigo-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all"></div>
                                    </label>
                                </div>
                                <input type="number" name="max_capacity" x-model="capacity" @input="updateCapacity()" :readonly="autoCalculate" required class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl font-bold" :class="autoCalculate ? 'opacity-60 grayscale' : ''">
                            </div>
                        </div>
                    </div>

                    <div class="space-y-6">
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center"><i class="fas fa-money-bill-wave mr-2 text-indigo-500"></i> Precios Estándar</h3>
                        <div class="bg-gray-50 rounded-3xl p-6 border border-gray-100 space-y-4 max-h-[400px] overflow-y-auto">
                            <template x-for="i in parseInt(capacity)" :key="i">
                                <div class="space-y-1">
                                    <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" x-text="'Precio para ' + i + (i == 1 ? ' Persona' : ' Personas')"></label>
                                    <div class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-4 flex items-center text-gray-400 font-bold">$</div>
                                        <input type="number" :name="'occupancy_prices[' + i + ']'" x-model="prices[i]" required class="block w-full pl-8 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-900 font-bold">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>
                </div>

                <div class="pt-8 flex flex-col sm:flex-row gap-4 border-t border-gray-100">
                    <button type="submit" class="flex-1 bg-indigo-600 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-indigo-700 shadow-xl transition-all">Actualizar Habitación</button>
                    <a href="{{ route('rooms.index') }}" class="flex-1 bg-gray-100 text-gray-600 py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-gray-200 text-center transition-all">Cancelar</a>
                </div>
            </form>
        </div>
    </div>

    <!-- Tab: Tarifas Especiales -->
    <div x-show="tab === 'rates'" x-transition x-cloak>
        <div class="grid grid-cols-1 lg:grid-cols-3 gap-8">
            <!-- Listado de Tarifas -->
            <div class="lg:col-span-2 space-y-6">
                <div class="bg-white rounded-3xl border border-gray-100 shadow-xl p-8">
                    <div class="flex items-center justify-between mb-8">
                        <h2 class="text-2xl font-black text-gray-900 tracking-tight">Tarifas por Fechas</h2>
                        <button @click="showRateModal = true" class="bg-amber-500 text-white px-6 py-2.5 rounded-xl text-xs font-black uppercase tracking-widest hover:bg-amber-600 transition-all shadow-lg shadow-amber-500/20">
                            <i class="fas fa-plus mr-2"></i> Nueva Tarifa
                        </button>
                    </div>

                    <div class="space-y-4">
                        @forelse($room->rates->sortBy('start_date') as $rate)
                        <div class="group bg-gray-50 rounded-2xl border border-gray-100 p-6 flex items-center justify-between hover:bg-white hover:shadow-lg transition-all border-l-4 border-l-amber-500">
                            <div class="flex items-center space-x-6">
                                <div class="w-12 h-12 rounded-xl bg-amber-100 text-amber-600 flex items-center justify-center text-xl">
                                    <i class="fas fa-calendar-day"></i>
                                </div>
                                <div>
                                    <h4 class="font-black text-gray-900 uppercase tracking-tight">{{ $rate->event_name ?? 'Evento Especial' }}</h4>
                                    <p class="text-xs font-bold text-gray-500">
                                        {{ $rate->start_date->format('d/m/Y') }} — {{ $rate->end_date->format('d/m/Y') }}
                                    </p>
                                </div>
                            </div>
                            <div class="flex items-center space-x-4">
                                <div class="text-right mr-4">
                                    <p class="text-[10px] font-black text-gray-400 uppercase tracking-widest">Precio Base</p>
                                    <p class="text-sm font-black text-amber-600">{{ number_format($rate->occupancy_prices[1] ?? 0, 0, ',', '.') }}</p>
                                </div>
                                <form action="{{ route('rooms.rates.destroy', [$room, $rate]) }}" method="POST" onsubmit="return confirm('¿Eliminar esta tarifa especial?')">
                                    @csrf @method('DELETE')
                                    <button type="submit" class="w-10 h-10 rounded-xl bg-red-50 text-red-500 hover:bg-red-500 hover:text-white transition-all">
                                        <i class="fas fa-trash-alt"></i>
                                    </button>
                                </form>
                            </div>
                        </div>
                        @empty
                        <div class="py-12 text-center border-2 border-dashed border-gray-100 rounded-3xl">
                            <i class="fas fa-calendar-times text-4xl text-gray-200 mb-4"></i>
                            <p class="text-gray-400 font-bold">No hay tarifas especiales configuradas.</p>
                        </div>
                        @endforelse
                    </div>
                </div>
            </div>

            <!-- Ayuda / Info -->
            <div class="space-y-6">
                <div class="bg-amber-600 rounded-3xl p-8 text-white shadow-xl">
                    <i class="fas fa-bolt text-3xl mb-4 opacity-50"></i>
                    <h3 class="text-xl font-black mb-2 uppercase tracking-tight">Tarifas Dinámicas</h3>
                    <p class="text-sm font-medium opacity-90 leading-relaxed">
                        Las tarifas especiales tienen prioridad sobre los precios estándar. Úsalas para temporadas altas, festivos o fines de semana.
                    </p>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Nueva Tarifa Especial -->
    <div x-show="showRateModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
        <div class="flex items-center justify-center min-h-screen p-4">
            <div @click="showRateModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm transition-opacity"></div>
            
            <div class="relative bg-white rounded-3xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
                <div class="bg-amber-500 px-8 py-6 text-white flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-calendar-plus text-xl"></i>
                        <h3 class="text-xl font-black uppercase tracking-tight">Nueva Tarifa Especial</h3>
                    </div>
                    <button @click="showRateModal = false" class="hover:rotate-90 transition-transform">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <form action="{{ route('rooms.rates.store', $room) }}" method="POST" class="p-8 space-y-6">
                    @csrf
                    <div class="space-y-2">
                        <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Nombre del Evento / Temporada</label>
                        <input type="text" name="event_name" placeholder="Ej: Semana Santa" required class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold">
                    </div>

                    <div class="grid grid-cols-2 gap-6">
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Desde</label>
                            <input type="date" name="start_date" required class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold">
                        </div>
                        <div class="space-y-2">
                            <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Hasta</label>
                            <input type="date" name="end_date" required class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl font-bold">
                        </div>
                    </div>

                    <div class="space-y-4 pt-4 border-t border-gray-100">
                        <label class="block text-[10px] font-black text-gray-500 uppercase tracking-widest ml-1">Esquema de Precios para esta fecha</label>
                        <div class="grid grid-cols-2 gap-4 max-h-[300px] overflow-y-auto p-2">
                            <template x-for="i in parseInt(capacity)" :key="'rate-'+i">
                                <div class="space-y-1">
                                    <label class="text-[9px] font-black text-gray-400 uppercase" x-text="i + (i==1 ? ' Persona' : ' Personas')"></label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center text-gray-400 text-xs">$</div>
                                        <input type="number" :name="'occupancy_prices[' + i + ']'" x-model="newRate.prices[i]" required class="block w-full pl-6 pr-3 py-2 bg-gray-50 border border-gray-200 rounded-lg text-sm font-bold">
                                    </div>
                                </div>
                            </template>
                        </div>
                    </div>

                    <div class="pt-6 flex space-x-4">
                        <button type="submit" class="flex-1 bg-amber-500 text-white py-4 rounded-xl font-black text-xs uppercase tracking-widest hover:bg-amber-600 transition-all">Guardar Tarifa Especial</button>
                        <button type="button" @click="showRateModal = false" class="flex-1 bg-gray-100 text-gray-500 py-4 rounded-xl font-black text-xs uppercase tracking-widest">Cancelar</button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
@endsection
