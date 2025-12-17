@extends('layouts.app')

@section('title', $product->name)
@section('header', 'Detalles del Producto')

@section('content')
<div class="max-w-7xl mx-auto space-y-4 sm:space-y-6">
    <!-- Header del Producto -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col lg:flex-row lg:items-center lg:justify-between gap-4 lg:gap-6">
            <div class="flex items-start space-x-3 sm:space-x-4 flex-1">
                <div class="p-3 sm:p-4 rounded-xl bg-blue-50 text-blue-600 shadow-sm flex-shrink-0">
                    <i class="fas fa-box text-2xl sm:text-3xl"></i>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center sm:space-x-3 gap-2 mb-3">
                        <h1 class="text-xl sm:text-2xl lg:text-3xl font-bold text-gray-900 truncate">{{ $product->name }}</h1>
                        @if($product->status == 'active')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-emerald-50 text-emerald-700">
                                <i class="fas fa-check-circle mr-1.5"></i>
                                Activo
                            </span>
                        @elseif($product->status == 'inactive')
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-gray-100 text-gray-700">
                                <i class="fas fa-pause-circle mr-1.5"></i>
                                Inactivo
                            </span>
                        @else
                            <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs sm:text-sm font-semibold bg-red-50 text-red-700">
                                <i class="fas fa-times-circle mr-1.5"></i>
                                Descontinuado
                            </span>
                        @endif
                    </div>
                    
                    <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
                        <div class="flex items-center space-x-2 text-sm">
                            <i class="fas fa-barcode text-gray-400"></i>
                            <span class="text-gray-500">SKU:</span>
                            <span class="font-semibold text-gray-900">{{ $product->sku }}</span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm">
                            <i class="fas fa-tag text-gray-400"></i>
                            <span class="text-gray-500">Precio:</span>
                            <span class="font-bold text-emerald-600 text-base sm:text-lg">${{ number_format($product->price, 2) }}</span>
                        </div>
                        <div class="flex items-center space-x-2 text-sm">
                            <i class="fas fa-warehouse text-gray-400"></i>
                            <span class="text-gray-500">Stock:</span>
                            <span class="font-semibold text-gray-900">{{ $product->quantity }} unidades</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-col sm:flex-row gap-3">
                @can('edit_products')
                <a href="{{ route('products.edit', $product) }}" 
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500">
                    <i class="fas fa-edit mr-2"></i>
                    Editar
                </a>
                @endcan
                
                <a href="{{ route('products.index') }}" 
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 hover:border-blue-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm hover:shadow-md">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Información principal -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
            <!-- Información básica -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                    <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                        <i class="fas fa-info text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Básica</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 block">Código SKU</label>
                            <div class="flex items-center space-x-2">
                                <div class="p-2 rounded-lg bg-gray-50 text-gray-600">
                                    <i class="fas fa-barcode text-sm"></i>
                            </div>
                                <span class="text-base sm:text-lg font-semibold text-gray-900">{{ $product->sku }}</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 block">Categoría</label>
                            <div class="flex items-center space-x-2">
                                <div class="p-2 rounded-lg bg-gray-50 text-gray-600">
                                    <i class="fas fa-folder text-sm"></i>
                            </div>
                                <span class="inline-flex items-center px-3 py-1 rounded-full text-sm font-semibold"
                                      data-color="{{ $product->category->color }}">
                                    {{ $product->category->name }}
                                </span>
                            </div>
                        </div>
                    </div>
                    
                    <div class="space-y-4">
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 block">ID del Producto</label>
                            <div class="flex items-center space-x-2">
                                <div class="p-2 rounded-lg bg-gray-50 text-gray-600">
                                    <i class="fas fa-hashtag text-sm"></i>
                            </div>
                                <span class="text-base sm:text-lg font-semibold text-gray-900">#{{ $product->id }}</span>
                            </div>
                        </div>
                        
                        <div>
                            <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5 block">Fecha de Creación</label>
                            <div class="flex items-center space-x-2">
                                <div class="p-2 rounded-lg bg-gray-50 text-gray-600">
                                    <i class="fas fa-calendar text-sm"></i>
                            </div>
                                <span class="text-sm text-gray-900">{{ $product->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Información financiera -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                    <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                        <i class="fas fa-dollar-sign text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Financiera</h2>
                </div>
                
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="p-4 sm:p-5 bg-emerald-50 rounded-xl border border-emerald-100">
                        <div class="flex items-center justify-center mb-3">
                            <div class="p-2.5 rounded-lg bg-emerald-100 text-emerald-600">
                                <i class="fas fa-tag text-sm"></i>
                            </div>
                        </div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 text-center">Precio de Venta</div>
                        <div class="text-xl sm:text-2xl font-bold text-emerald-700 text-center">${{ number_format($product->price, 2) }}</div>
                    </div>
                    
                    <div class="p-4 sm:p-5 bg-blue-50 rounded-xl border border-blue-100">
                        <div class="flex items-center justify-center mb-3">
                            <div class="p-2.5 rounded-lg bg-blue-100 text-blue-600">
                                <i class="fas fa-shopping-cart text-sm"></i>
                            </div>
                        </div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 text-center">Precio de Costo</div>
                        <div class="text-xl sm:text-2xl font-bold text-blue-700 text-center">
                            {{ $product->cost_price ? '$' . number_format($product->cost_price, 2) : 'N/A' }}
                        </div>
                    </div>
                    
                    @if($product->cost_price)
                    <div class="p-4 sm:p-5 bg-violet-50 rounded-xl border border-violet-100">
                        <div class="flex items-center justify-center mb-3">
                            <div class="p-2.5 rounded-lg bg-violet-100 text-violet-600">
                                <i class="fas fa-chart-line text-sm"></i>
                            </div>
                        </div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 text-center">Ganancia</div>
                        <div class="text-xl sm:text-2xl font-bold text-violet-700 text-center">
                            ${{ number_format($product->price - $product->cost_price, 2) }}
                        </div>
                    </div>
                    @else
                    <div class="p-4 sm:p-5 bg-gray-50 rounded-xl border border-gray-100">
                        <div class="flex items-center justify-center mb-3">
                            <div class="p-2.5 rounded-lg bg-gray-100 text-gray-600">
                                <i class="fas fa-percentage text-sm"></i>
                            </div>
                        </div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1 text-center">Margen</div>
                        <div class="text-base font-semibold text-gray-600 text-center">No calculable</div>
                    </div>
                    @endif
                </div>
            </div>
        </div>
        
        <!-- Panel lateral -->
        <div class="space-y-4 sm:space-y-6">
            <!-- Estado del Stock -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                    <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                        <i class="fas fa-boxes text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Estado del Stock</h2>
                </div>
                
                <div class="space-y-4 sm:space-y-5">
                    <div class="text-center">
                        <div class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">{{ $product->quantity }}</div>
                        <div class="text-xs sm:text-sm text-gray-500">Unidades disponibles</div>
                    </div>
                    
                    @php
                        $maxStock = max(100, $product->quantity * 2);
                        $stockPercentage = $product->quantity > 0 ? min(100, ($product->quantity / $maxStock) * 100) : 0;
                        $stockColor = $product->quantity > $product->low_stock_threshold ? 'bg-emerald-500' : ($product->quantity > 0 ? 'bg-amber-500' : 'bg-red-500');
                    @endphp
                    
                    <div class="space-y-2">
                        <div class="flex justify-between text-xs sm:text-sm">
                            <span class="text-gray-600">Nivel de stock</span>
                            <span class="font-semibold text-gray-900">{{ round($stockPercentage) }}%</span>
                        </div>
                        <div class="w-full bg-gray-200 rounded-full h-2.5">
                            <div class="h-2.5 rounded-full {{ $stockColor }} transition-all duration-300"
                                 style="width: {{ $stockPercentage }}%"></div>
                        </div>
                    </div>
                    
                    @if($product->quantity == 0)
                        <div class="p-3 sm:p-4 bg-red-50 border border-red-200 rounded-xl">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-circle text-red-500 mr-2 mt-0.5"></i>
                                <div>
                                    <div class="text-xs sm:text-sm font-semibold text-red-800">Sin stock</div>
                                    <div class="text-xs text-red-600 mt-0.5">El producto no está disponible</div>
                                </div>
                            </div>
                        </div>
                    @elseif($product->hasLowStock())
                        <div class="p-3 sm:p-4 bg-amber-50 border border-amber-200 rounded-xl">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-amber-500 mr-2 mt-0.5"></i>
                                <div>
                                    <div class="text-xs sm:text-sm font-semibold text-amber-800">Stock bajo</div>
                                    <div class="text-xs text-amber-600 mt-0.5">Quedan {{ $product->quantity }} unidades (límite: {{ $product->low_stock_threshold }})</div>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="p-3 sm:p-4 bg-emerald-50 border border-emerald-200 rounded-xl">
                            <div class="flex items-start">
                                <i class="fas fa-check-circle text-emerald-500 mr-2 mt-0.5"></i>
                                <div>
                                    <div class="text-xs sm:text-sm font-semibold text-emerald-800">Stock suficiente</div>
                                    <div class="text-xs text-emerald-600 mt-0.5">Disponible para consumo</div>
                                </div>
                            </div>
                        </div>
                    @endif
                    
                    <div class="pt-3 sm:pt-4 border-t border-gray-100">
                        <div class="grid grid-cols-2 gap-3 sm:gap-4 text-xs sm:text-sm">
                            <div>
                                <div class="text-gray-500 mb-1">Límite de alerta</div>
                                <div class="font-semibold text-gray-900">{{ $product->low_stock_threshold }}</div>
                            </div>
                            <div>
                                <div class="text-gray-500 mb-1">Stock inicial</div>
                                <div class="font-semibold text-gray-900">{{ $product->initial_stock }}</div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Estadísticas del Producto -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                    <div class="p-2 rounded-xl bg-violet-50 text-violet-600">
                        <i class="fas fa-chart-bar text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Estadísticas</h2>
                </div>
                
                <div class="space-y-3">
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                                <i class="fas fa-shopping-cart text-xs"></i>
                            </div>
                            <span class="text-xs sm:text-sm text-gray-600">Vendido</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ $product->sold_quantity ?? 0 }} unidades</span>
                    </div>
                    
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600">
                                <i class="fas fa-warehouse text-xs"></i>
                            </div>
                            <span class="text-xs sm:text-sm text-gray-600">Stock inicial</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">{{ $product->initial_stock }} unidades</span>
                    </div>
                    
                    @if($product->initial_stock > 0)
                    <div class="flex items-center justify-between p-3 bg-gray-50 rounded-xl">
                        <div class="flex items-center space-x-2">
                            <div class="p-1.5 rounded-lg bg-violet-50 text-violet-600">
                                <i class="fas fa-percentage text-xs"></i>
                            </div>
                            <span class="text-xs sm:text-sm text-gray-600">Tasa de venta</span>
                        </div>
                        <span class="text-sm font-semibold text-gray-900">
                            {{ round((($product->sold_quantity ?? 0) / $product->initial_stock) * 100, 1) }}%
                        </span>
                    </div>
                    @endif
                </div>
            </div>
            
            <!-- Acciones rápidas -->
            @can('edit_products')
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                    <div class="p-2 rounded-xl bg-indigo-50 text-indigo-600">
                        <i class="fas fa-bolt text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Acciones</h2>
                </div>
                
                <div class="space-y-3">
                    <a href="{{ route('products.edit', $product) }}" 
                       class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border-2 border-indigo-600 bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 hover:border-indigo-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 shadow-sm hover:shadow-md">
                        <i class="fas fa-edit mr-2"></i>
                        Editar Producto
                    </a>
                    
                    @can('delete_products')
                    <form method="POST" action="{{ route('products.destroy', $product) }}" 
                          onsubmit="return confirm('¿Estás seguro de que quieres eliminar este producto? Esta acción no se puede deshacer.')">
                        @csrf
                        @method('DELETE')
                        <button type="submit" 
                                class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border-2 border-red-600 bg-red-600 text-white text-sm font-semibold hover:bg-red-700 hover:border-red-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm hover:shadow-md">
                            <i class="fas fa-trash mr-2"></i>
                            Eliminar Producto
                        </button>
                    </form>
                    @endcan
                </div>
            </div>
            @endcan
        </div>
    </div>
</div>

@push('scripts')
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Aplicar colores a las etiquetas de categoría
    document.querySelectorAll('[data-color]').forEach(function(element) {
        const color = element.getAttribute('data-color');
        if (color) {
            element.style.backgroundColor = color + '20';
            element.style.color = color;
        }
    });
});
</script>
@endpush
@endsection
