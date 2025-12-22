@extends('layouts.app')

@section('title', 'Nuevo Producto')
@section('header', 'Nuevo Producto')

@section('content')
<div class="max-w-4xl mx-auto">
    <form method="POST" action="{{ route('products.store') }}" id="product-form" x-data="{ loading: false }" @submit="loading = true">
        @csrf
        
        <!-- Header -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 mb-4 sm:mb-6">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-plus text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Nuevo Producto</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Agregue bebidas o snacks al inventario del hotel</p>
                </div>
            </div>
        </div>

        <div class="space-y-4 sm:space-y-6">
            <!-- Información Básica -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                    <div class="p-2 rounded-lg bg-blue-50 text-blue-600">
                        <i class="fas fa-info text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información del Producto</h2>
                </div>
                
                <div class="space-y-4 sm:space-y-6">
                    <!-- Nombre del producto -->
                    <div>
                        <label for="name" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Nombre del producto <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="name" 
                               name="name" 
                               value="{{ old('name') }}"
                               class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('name') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="Ej: Coca Cola 350ml, Papas Margarita, etc."
                               required>
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                        <!-- Categoría -->
                        <div>
                            <label for="category_id" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                                Categoría <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <select id="category_id" 
                                        name="category_id"
                                        class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white @error('category_id') border-red-300 focus:ring-red-500 @enderror"
                                        required>
                                    <option value="">Seleccionar categoría...</option>
                                    @foreach($categories as $category)
                                        <option value="{{ $category->id }}" {{ old('category_id') == $category->id ? 'selected' : '' }}>
                                            {{ $category->name }}
                                        </option>
                                    @endforeach
                                </select>
                                <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                    <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                                </div>
                            </div>
                            <p class="mt-1.5 text-xs text-gray-500">
                                Bebidas o Mecato
                            </p>
                            @error('category_id')
                                <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>

                        <!-- Stock inicial -->
                        <div>
                            <label for="quantity" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                                Stock disponible <span class="text-red-500">*</span>
                            </label>
                            <input type="number"
                                   id="quantity"
                                   name="quantity"
                                   value="{{ old('quantity', '') }}"
                                   x-data="{ value: '{{ old('quantity', '') }}' }"
                                   x-model="value"
                                   @input="value = String(value).replace(/^0+/, ''); if (!value || parseInt(value) < 1) value = ''; $el.value = value;"
                                   @blur="if (!value || parseInt(value) < 1) { value = ''; $el.value = ''; }"
                                   class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('quantity') border-red-300 focus:ring-red-500 @enderror"
                                   min="1"
                                   step="1"
                                   pattern="[1-9][0-9]*"
                                   placeholder="Ej: 10, 25, 100"
                                   required>
                            @error('quantity')
                                <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i>
                                    {{ $message }}
                                </p>
                            @enderror
                        </div>
                    </div>
                </div>
            </div>

            <!-- Precios -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                    <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                        <i class="fas fa-dollar-sign text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Precio de Venta</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Precio de venta -->
                    <div>
                        <label for="price" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Precio al público <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">$</span>
                            </div>
                            <input type="number" 
                                   id="price" 
                                   name="price" 
                                   x-model.number="price"
                                   value="{{ old('price') }}"
                                   class="block w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('price') border-red-300 focus:ring-red-500 @enderror"
                                   step="0.01" 
                                   min="0" 
                                   placeholder="0.00" 
                                   required>
                        </div>
                        @error('price')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    
                    <!-- Campo oculto para mantener compatibilidad con SKU y otros campos requeridos en DB si los hay -->
                    <input type="hidden" name="low_stock_threshold" value="5">
                    <input type="hidden" name="status" value="active">
                </div>
            </div>
        </div>

        <!-- Estado del Producto -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                    <div class="p-2 rounded-lg bg-violet-50 text-violet-600">
                        <i class="fas fa-toggle-on text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Estado del Producto</h2>
                </div>
                
                    <div>
                    <label for="status" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Estado
                        </label>
                    <div class="relative">
                        <select id="status" 
                                name="status"
                                class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent appearance-none bg-white @error('status') border-red-300 focus:ring-red-500 @enderror">
                            <option value="active" {{ old('status', 'active') == 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ old('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                            <option value="discontinued" {{ old('status') == 'discontinued' ? 'selected' : '' }}>Descontinuado</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">
                            Los productos inactivos no aparecerán en el inventario activo
                        </p>
                        @error('status')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                </div>
            </div>

            <!-- Botones de acción -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                    <p class="text-xs text-gray-500">
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios
                    </p>
                
                    <div class="flex flex-col sm:flex-row gap-3 sm:gap-3">
                    <a href="{{ route('products.index') }}" 
                           class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                    </a>
                    
                    <button type="submit" 
                            x-bind:disabled="loading"
                                class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 hover:border-blue-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin mr-2" x-show="loading"></i>
                        <span x-show="!loading">Guardar Producto</span>
                        <span x-show="loading">Procesando...</span>
                    </button>
                    </div>
                </div>
                </div>
            </div>
        </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Formateo de precios al perder el foco
    const priceInput = document.getElementById('price');
    
    if (priceInput) {
        priceInput.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    }
});
</script>
@endpush
@endsection
