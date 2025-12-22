@extends('layouts.app')

@section('title', 'Nueva Habitación')
@section('header', 'Crear Nueva Habitación')

@section('content')
<div class="max-w-5xl mx-auto" x-data="{ 
    beds: {{ old('beds_count', 1) }}, 
    capacity: {{ old('max_capacity', 2) }},
    autoCalculate: true,
    prices: {{ json_encode(old('occupancy_prices', [1 => 0, 2 => 0])) }},
    
    updateCapacity() {
        if(this.autoCalculate) {
            this.capacity = this.beds * 2;
        }
        // Inicializar nuevos campos de precio si la capacidad aumenta
        for (let i = 1; i <= this.capacity; i++) {
            if (!this.prices[i]) {
                // Sugerir precio basado en el anterior o 0
                this.prices[i] = this.prices[i-1] || 0;
            }
        }
    },
    formatCurrency(val) {
        return new Intl.NumberFormat('es-CO', {
            style: 'currency',
            currency: 'COP',
            minimumFractionDigits: 0
        }).format(val);
    }
}" x-init="updateCapacity()">
    <div class="bg-white rounded-3xl border border-gray-100 shadow-xl overflow-hidden">
        <div class="bg-emerald-600 px-8 py-10 text-white relative">
            <div class="flex items-center space-x-4">
                <div class="h-16 w-16 rounded-2xl bg-white/20 backdrop-blur-md flex items-center justify-center text-2xl shadow-inner">
                    <i class="fas fa-plus"></i>
                </div>
                <div>
                    <h1 class="text-3xl font-black tracking-tight">Nueva Habitación</h1>
                    <p class="text-emerald-100 font-bold text-sm uppercase tracking-widest opacity-80">Definición de Precios por Ocupación</p>
                </div>
            </div>
        </div>

        <form action="{{ route('rooms.store') }}" method="POST" class="p-8 space-y-8">
            @csrf

            <div class="grid grid-cols-1 lg:grid-cols-2 gap-12">
                <!-- Columna Izquierda: Configuración -->
                <div class="space-y-8">
                    <section class="space-y-6">
                        <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center">
                            <i class="fas fa-cog mr-2 text-emerald-500"></i> Configuración Base
                        </h3>
                        
                            <div class="space-y-2">
                                <label for="room_number" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Número</label>
                                <input type="text" name="room_number" id="room_number" value="{{ old('room_number') }}" required
                                    class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-gray-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-bold">
                            </div>
                        <input type="hidden" name="status" value="{{ \App\Enums\RoomStatus::LIBRE->value }}">

                        <div class="grid grid-cols-2 gap-6">
                            <div class="space-y-2">
                                <label for="beds_count" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1">Camas</label>
                                <input type="number" name="beds_count" id="beds_count" x-model="beds" @input="updateCapacity()" required min="1"
                                    class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-gray-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-bold">
                            </div>
                            <div class="space-y-2">
                                <div class="flex justify-between items-center ml-1">
                                    <label for="max_capacity" class="block text-[10px] font-black text-gray-400 uppercase tracking-widest">Capacidad Máx.</label>
                                    <label class="flex items-center cursor-pointer">
                                        <input type="checkbox" x-model="autoCalculate" @change="updateCapacity()" class="sr-only peer">
                                        <div class="w-7 h-4 bg-gray-200 rounded-full peer peer-checked:bg-emerald-600 peer-checked:after:translate-x-full after:content-[''] after:absolute after:top-[2px] after:start-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-3 after:w-3 after:transition-all"></div>
                                    </label>
                                </div>
                                <input type="number" name="max_capacity" id="max_capacity" x-model="capacity" @input="updateCapacity()" :readonly="autoCalculate" required min="1"
                                    class="block w-full px-4 py-4 bg-gray-50 border border-gray-200 rounded-2xl text-gray-900 focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all font-bold"
                                    :class="autoCalculate ? 'opacity-60 grayscale' : ''">
                            </div>
                        </div>
                    </section>
                </div>

                <!-- Columna Derecha: Definición de Precios Dinámica -->
                <div class="space-y-6">
                    <h3 class="text-sm font-black text-gray-900 uppercase tracking-widest flex items-center">
                        <i class="fas fa-money-bill-wave mr-2 text-emerald-500"></i> Definición de Precios
                    </h3>

                    <div class="bg-gray-50 rounded-3xl p-6 border border-gray-100 space-y-4">
                        <template x-for="i in parseInt(capacity)" :key="i">
                            <div class="space-y-1">
                                <label class="block text-[10px] font-black text-gray-400 uppercase tracking-widest ml-1" 
                                    x-text="'Precio para ' + i + (i == 1 ? ' Persona' : ' Personas')"></label>
                                <div class="relative group">
                                    <div class="absolute inset-y-0 left-0 pl-4 flex items-center pointer-events-none text-gray-400 font-bold">$</div>
                                    <input type="number" :name="'occupancy_prices[' + i + ']'" x-model="prices[i]" required step="1"
                                        class="block w-full pl-8 pr-4 py-3 bg-white border border-gray-200 rounded-xl text-gray-900 font-bold focus:ring-2 focus:ring-emerald-500/20 focus:border-emerald-500 transition-all">
                                </div>
                            </div>
                        </template>
                    </div>

                    <div class="p-4 bg-emerald-50 rounded-2xl border border-emerald-100">
                        <div class="flex items-center text-emerald-700 text-xs font-bold">
                            <i class="fas fa-info-circle mr-2"></i>
                            <span>Los precios se guardarán individualmente para cada nivel de ocupación.</span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="pt-8 flex flex-col sm:flex-row gap-4 border-t border-gray-100">
                <button type="submit" class="flex-1 bg-emerald-600 text-white py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-emerald-700 shadow-xl shadow-emerald-600/20 transition-all active:scale-95">
                    <i class="fas fa-save mr-2"></i> Guardar Habitación
                </button>
                <a href="{{ route('rooms.index') }}" class="flex-1 bg-gray-100 text-gray-600 py-4 rounded-2xl font-black text-sm uppercase tracking-widest hover:bg-gray-200 text-center transition-all">
                    Cancelar
                </a>
            </div>
        </form>
    </div>
</div>
@endsection
