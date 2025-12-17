@extends('layouts.app')

@section('title', 'Nuevo Producto')
@section('header', 'Nuevo Producto')

@section('content')
<div class="max-w-4xl mx-auto">
    <form method="POST" action="{{ route('products.store') }}" id="product-form" x-data="{ loading: false, price: {{ old('price', 0) }}, costPrice: {{ old('cost_price', 0) }}, profit: 0 }" @submit="loading = true" x-init="$watch('price', val => profit = parseFloat(val || 0) - parseFloat(costPrice || 0)); $watch('costPrice', val => profit = parseFloat(price || 0) - parseFloat(val || 0))">
        @csrf
        
        <!-- Header -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 mb-4 sm:mb-6">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-plus text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Nuevo Producto</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Completa la información para agregar un nuevo producto al inventario</p>
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
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Básica</h2>
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
                               placeholder="Ej: iPhone 13 Pro Max 128GB - Negro"
                               required>
                        <p class="mt-1.5 text-xs text-gray-500">
                            Incluye marca, modelo, capacidad y color para mejor identificación
                        </p>
                        @error('name')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <!-- SKU -->
                    <div>
                            <label for="sku" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Código SKU <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               id="sku" 
                               name="sku" 
                               value="{{ old('sku') }}"
                                   class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('sku') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="Ej: IP13PM-128-SLV"
                               required>
                            <p class="mt-1.5 text-xs text-gray-500">
                            Código único para identificar el producto (máx. 50 caracteres)
                        </p>
                        @error('sku')
                                <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

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
                            <option value="">Seleccionar categoría</option>
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
                            Organiza tus productos por tipo o marca
                        </p>
                        @error('category_id')
                                <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    </div>
                </div>
            </div>

            <!-- Gestión de Stock -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                    <div class="p-2 rounded-lg bg-emerald-50 text-emerald-600">
                        <i class="fas fa-boxes text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Gestión de Stock</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <!-- Stock inicial -->
                    <div>
                        <label for="quantity" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Stock inicial <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="quantity"
                               name="quantity"
                               value="{{ old('quantity', 0) }}"
                               class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('quantity') border-red-300 focus:ring-red-500 @enderror"
                               min="0"
                               required>
                        <p class="mt-1.5 text-xs text-gray-500">
                            Cantidad de unidades disponibles al momento de crear el producto
                        </p>
                        @error('quantity')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Alerta de stock bajo -->
                    <div>
                        <label for="low_stock_threshold" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            <i class="fas fa-exclamation-triangle text-amber-500 mr-1"></i>
                            Alerta de stock bajo <span class="text-red-500">*</span>
                        </label>
                        <input type="number"
                               id="low_stock_threshold"
                               name="low_stock_threshold"
                               value="{{ old('low_stock_threshold', 10) }}"
                               class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('low_stock_threshold') border-red-300 focus:ring-red-500 @enderror"
                               min="0"
                               required>
                        <p class="mt-1.5 text-xs text-gray-500">
                            Se mostrará alerta cuando el stock sea menor o igual a este valor
                        </p>
                        @error('low_stock_threshold')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>

            <!-- Precios -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                    <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                        <i class="fas fa-dollar-sign text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Precios</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6 mb-4 sm:mb-6">
                    <!-- Precio de venta -->
                    <div>
                        <label for="price" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Precio de venta <span class="text-red-500">*</span>
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
                        <p class="mt-1.5 text-xs text-gray-500">
                            Precio al que se venderá el producto al cliente
                        </p>
                        @error('price')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Precio de costo -->
                    <div>
                        <label for="cost_price" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Precio de costo
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <span class="text-gray-500 text-sm">$</span>
                            </div>
                            <input type="number" 
                                   id="cost_price" 
                                   name="cost_price" 
                                   x-model.number="costPrice"
                                   value="{{ old('cost_price') }}"
                                   class="block w-full pl-8 sm:pl-10 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:border-transparent transition-all @error('cost_price') border-red-300 focus:ring-red-500 @enderror"
                                   step="0.01" 
                                   min="0" 
                                   placeholder="0.00">
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">
                            Precio al que se compró el producto (opcional, para calcular ganancias)
                        </p>
                        @error('cost_price')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
                
                <!-- Cálculo de ganancia -->
                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center space-x-2 mb-3">
                        <i class="fas fa-calculator text-gray-400 text-sm"></i>
                        <h3 class="text-xs sm:text-sm font-semibold text-gray-700">Cálculo de ganancia</h3>
                            </div>
                    <div class="grid grid-cols-3 gap-3 sm:gap-4 text-center">
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Precio de venta</p>
                            <p class="text-sm sm:text-base font-semibold text-gray-900" x-text="'$' + (price || 0).toFixed(2)">$0.00</p>
                            </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Precio de costo</p>
                            <p class="text-sm sm:text-base font-semibold text-gray-900" x-text="'$' + (costPrice || 0).toFixed(2)">$0.00</p>
                            </div>
                        <div>
                            <p class="text-xs text-gray-500 mb-1">Ganancia</p>
                            <p class="text-sm sm:text-base font-semibold" 
                               :class="profit > 0 ? 'text-emerald-600' : profit < 0 ? 'text-red-600' : 'text-gray-600'"
                               x-text="'$' + profit.toFixed(2)">$0.00</p>
                        </div>
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
                        <i class="fas fa-plus mr-2" x-show="!loading"></i>
                        <i class="fas fa-spinner fa-spin mr-2" x-show="loading"></i>
                        <span x-show="!loading">Crear Producto</span>
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
    // Auto-generar SKU basado en el nombre
    const nameInput = document.getElementById('name');
    const skuInput = document.getElementById('sku');
    
    if (nameInput && skuInput) {
    nameInput.addEventListener('blur', function() {
        if (this.value && !skuInput.value) {
            const sku = this.value
                .toUpperCase()
                .replace(/[^A-Z0-9\s]/g, '')
                .replace(/\s+/g, '-')
                .substring(0, 20);
            skuInput.value = sku;
            }
        });
    }
    
    // Formateo de precios al perder el foco
    const priceInput = document.getElementById('price');
    const costPriceInput = document.getElementById('cost_price');
    
    [priceInput, costPriceInput].forEach(input => {
        if (input) {
            input.addEventListener('blur', function() {
                if (this.value) {
                    this.value = parseFloat(this.value).toFixed(2);
                }
            });
        }
    });
});
</script>
@endpush
@endsection
