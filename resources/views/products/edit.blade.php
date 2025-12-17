@extends('layouts.app')

@section('title', 'Editar Producto')
@section('header', 'Editar Producto')

@section('content')
<div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-indigo-50 text-indigo-600">
                <i class="fas fa-edit text-lg sm:text-xl"></i>
            </div>
            <div class="flex-1 min-w-0">
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Editar Producto</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1 truncate">Modifica la información del producto: <span class="font-semibold text-gray-900">{{ $product->name }}</span></p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('products.update', $product) }}" id="product-form" x-data="{ loading: false, price: {{ old('price', $product->price) }}, costPrice: {{ old('cost_price', $product->cost_price ?? 0) }}, profit: 0 }" @submit="loading = true" x-init="$watch('price', val => profit = parseFloat(val || 0) - parseFloat(costPrice || 0)); $watch('costPrice', val => profit = parseFloat(price || 0) - parseFloat(val || 0))">
        @csrf
        @method('PUT')

        <!-- Información Básica -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-info text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Básica</h2>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <!-- Nombre del producto -->
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Nombre del producto <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-tag text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="name"
                               name="name"
                               value="{{ old('name', $product->name) }}"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all @error('name') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="Ej: iPhone 13 Pro Max 128GB - Negro"
                               required>
                    </div>
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

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                    <!-- SKU -->
                    <div>
                        <label for="sku" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Código SKU <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-barcode text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="sku"
                                   name="sku"
                                   value="{{ old('sku', $product->sku) }}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all @error('sku') border-red-300 focus:ring-red-500 @enderror"
                                   placeholder="Ej: IP13PM-128-SLV"
                                   required>
                        </div>
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
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-folder text-gray-400 text-sm"></i>
                            </div>
                            <select id="category_id"
                                    name="category_id"
                                    class="block w-full pl-10 sm:pl-11 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white transition-all @error('category_id') border-red-300 focus:ring-red-500 @enderror"
                                    required>
                                <option value="">Seleccionar categoría</option>
                                @foreach($categories as $category)
                                    <option value="{{ $category->id }}"
                                        {{ old('category_id', $product->category_id) == $category->id ? 'selected' : '' }}>
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
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="fas fa-boxes text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Gestión de Stock</h2>
            </div>

            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
                <!-- Stock actual -->
                <div>
                    <label for="quantity" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Stock actual <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-warehouse text-gray-400 text-sm"></i>
                        </div>
                        <input type="number"
                               id="quantity"
                               name="quantity"
                               value="{{ old('quantity', $product->quantity) }}"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all @error('quantity') border-red-300 focus:ring-red-500 @enderror"
                               min="0"
                               required>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Cantidad de unidades disponibles actualmente
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
                        Alerta de stock bajo <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-exclamation-triangle text-gray-400 text-sm"></i>
                        </div>
                        <input type="number"
                               id="low_stock_threshold"
                               name="low_stock_threshold"
                               value="{{ old('low_stock_threshold', $product->low_stock_threshold) }}"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all @error('low_stock_threshold') border-red-300 focus:ring-red-500 @enderror"
                               min="0"
                               required>
                    </div>
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
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fas fa-dollar-sign text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Precios</h2>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-5 sm:gap-6">
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
                                   value="{{ old('price', $product->price) }}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all @error('price') border-red-300 focus:ring-red-500 @enderror"
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
                                   value="{{ old('cost_price', $product->cost_price) }}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all @error('cost_price') border-red-300 focus:ring-red-500 @enderror"
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
                <div class="bg-gray-50 rounded-xl p-4 sm:p-5 border border-gray-200">
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
        </div>

        <!-- Estado del Producto -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-violet-50 text-violet-600">
                    <i class="fas fa-toggle-on text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Estado del Producto</h2>
            </div>

            <div>
                <label for="status" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                    Estado
                </label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                        <i class="fas fa-power-off text-gray-400 text-sm"></i>
                    </div>
                    <select id="status"
                            name="status"
                            class="block w-full pl-10 sm:pl-11 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-violet-500 focus:border-transparent appearance-none bg-white transition-all @error('status') border-red-300 focus:ring-red-500 @enderror">
                        <option value="active" {{ old('status', $product->status) == 'active' ? 'selected' : '' }}>
                            Activo
                        </option>
                        <option value="inactive" {{ old('status', $product->status) == 'inactive' ? 'selected' : '' }}>
                            Inactivo
                        </option>
                        <option value="discontinued" {{ old('status', $product->status) == 'discontinued' ? 'selected' : '' }}>
                            Descontinuado
                        </option>
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

        <!-- Información del Sistema -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-gray-50 text-gray-600">
                    <i class="fas fa-info-circle text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información del Sistema</h2>
            </div>

            <div class="bg-gray-50 rounded-xl p-4 sm:p-5 border border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 text-sm">
                    <div>
                        <span class="font-semibold text-gray-700">ID del producto:</span>
                        <span class="text-gray-900 ml-2">{{ $product->id }}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">SKU actual:</span>
                        <span class="text-gray-900 ml-2 font-mono">{{ $product->sku }}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Fecha de creación:</span>
                        <span class="text-gray-900 ml-2">{{ $product->created_at->format('d/m/Y H:i') }}</span>
                    </div>
                    <div>
                        <span class="font-semibold text-gray-700">Última actualización:</span>
                        <span class="text-gray-900 ml-2">{{ $product->updated_at->format('d/m/Y H:i') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-4 border-t border-gray-100">
            <div class="text-xs sm:text-sm text-gray-500 flex items-center">
                <i class="fas fa-info-circle mr-1.5"></i>
                Los campos marcados con <span class="text-red-500 ml-1">*</span> son obligatorios
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <a href="{{ route('products.index') }}"
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-indigo-600 bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 hover:border-indigo-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm hover:shadow-md"
                        :disabled="loading">
                    <template x-if="!loading">
                        <i class="fas fa-save mr-2"></i>
                    </template>
                    <template x-if="loading">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                    </template>
                    <span x-text="loading ? 'Procesando...' : 'Actualizar Producto'">Actualizar Producto</span>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Formateo de precios al perder el foco
    const priceInput = document.getElementById('price');
    const costPriceInput = document.getElementById('cost_price');

    [priceInput, costPriceInput].forEach(input => {
        input.addEventListener('blur', function() {
            if (this.value) {
                this.value = parseFloat(this.value).toFixed(2);
            }
        });
    });
});
</script>
@endpush
@endsection
