<div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <i class="fas fa-bed text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Nueva Habitación</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Registra una nueva habitación con su configuración y precios</p>
            </div>
        </div>
    </div>

    <form wire:submit="store" class="space-y-4 sm:space-y-6">
        <!-- Información Básica -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-info text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Básica</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <!-- Número de Habitación -->
                <div>
                    <label for="room_number" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Número de habitación <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-hashtag text-gray-400 text-sm"></i>
                        </div>
                        <input 
                            type="text" 
                            id="room_number"
                            wire:model.blur="room_number"
                            class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('room_number') border-red-300 focus:ring-red-500 @enderror"
                            placeholder="Ej: 101"
                        >
                    </div>
                    @error('room_number')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Tipo de Ventilación -->
                <div>
                    <label for="ventilation_type" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Tipo de ventilación <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-wind text-gray-400 text-sm"></i>
                        </div>
                        <select 
                            id="ventilation_type"
                            wire:model.blur="ventilation_type"
                            class="block w-full pl-10 sm:pl-11 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent appearance-none bg-white @error('ventilation_type') border-red-300 focus:ring-red-500 @enderror"
                        >
                            <option value="">Seleccionar...</option>
                            @foreach($ventilationTypes as $type)
                                <option value="{{ $type->value }}">{{ $type->label() }}</option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    @error('ventilation_type')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Capacidad -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fas fa-users text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Capacidad</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <!-- Número de Camas -->
                <div>
                    <label for="beds_count" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Número de camas <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-bed text-gray-400 text-sm"></i>
                        </div>
                        <input 
                            type="number" 
                            id="beds_count"
                            wire:model.live="beds_count"
                            min="1"
                            class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('beds_count') border-red-300 focus:ring-red-500 @enderror"
                            placeholder="Ej: 2"
                        >
                    </div>
                    @error('beds_count')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Capacidad Máxima -->
                <div>
                    <div class="flex items-center justify-between mb-2">
                        <label for="max_capacity" class="block text-xs sm:text-sm font-semibold text-gray-700">
                            Capacidad máxima <span class="text-red-500">*</span>
                        </label>
                        <label class="flex items-center gap-2 cursor-pointer group">
                            <span class="text-xs font-semibold text-gray-500 group-hover:text-emerald-600 transition-colors">
                                Auto
                            </span>
                            <input 
                                type="checkbox" 
                                wire:model.live="auto_calculate"
                                class="sr-only peer"
                            >
                            <div class="relative w-11 h-6 bg-gray-300 rounded-full peer peer-checked:bg-emerald-500 transition-colors">
                                <div class="absolute top-0.5 left-0.5 w-5 h-5 bg-white rounded-full transition-transform peer-checked:translate-x-5"></div>
                            </div>
                        </label>
                    </div>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user-friends text-gray-400 text-sm"></i>
                        </div>
                        <input 
                            type="number" 
                            id="max_capacity"
                            wire:model.blur="max_capacity"
                            min="1"
                            @if($auto_calculate) readonly @endif
                            class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('max_capacity') border-red-300 focus:ring-red-500 @enderror @if($auto_calculate) opacity-60 bg-gray-50 @endif"
                            placeholder="Ej: 4"
                        >
                    </div>
                    @error('max_capacity')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Precios por Ocupación -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-amber-50 text-amber-600">
                    <i class="fas fa-dollar-sign text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Precios por Ocupación</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                @for($i = 1; $i <= $max_capacity; $i++)
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            {{ $i }} {{ $i === 1 ? 'Persona' : 'Personas' }} <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-400 text-sm font-semibold">$</span>
                            </div>
                            <input 
                                type="number" 
                                wire:model.blur="occupancy_prices.{{ $i }}"
                                min="0"
                                step="1"
                                class="block w-full pl-8 sm:pl-9 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error("occupancy_prices.{$i}") border-red-300 focus:ring-red-500 @enderror"
                                placeholder="0"
                            >
                        </div>
                        @error("occupancy_prices.{$i}")
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                @endfor
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <p class="text-xs text-gray-500">
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios
                </p>
                
                <div class="flex flex-col sm:flex-row gap-3 sm:gap-3">
                    <a href="{{ route('rooms.index') }}" 
                       class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver
                    </a>
                    
                    <button type="submit" 
                            wire:loading.attr="disabled"
                            class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 hover:border-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2" wire:loading.remove wire:target="store"></i>
                        <i class="fas fa-spinner fa-spin mr-2" wire:loading wire:target="store"></i>
                        <span wire:loading.remove wire:target="store">Guardar Habitación</span>
                        <span wire:loading wire:target="store">Procesando...</span>
                    </button>
                </div>
            </div>
        </div>
    </form>
</div>
