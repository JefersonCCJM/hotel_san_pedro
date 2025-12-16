@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-5 sm:space-y-8">
    <!-- Estadísticas principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <!-- Total Productos -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Productos</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $stats['total_products'] }}</p>
                </div>
                <div class="p-2 sm:p-3 rounded-xl bg-blue-50 text-blue-600 group-hover:bg-blue-100 transition-colors duration-300">
                    <i class="fas fa-boxes text-lg sm:text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Total Clientes -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Clientes</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $stats['total_customers'] }}</p>
                </div>
                <div class="p-2 sm:p-3 rounded-xl bg-violet-50 text-violet-600 group-hover:bg-violet-100 transition-colors duration-300">
                    <i class="fas fa-users text-lg sm:text-xl"></i>
                </div>
            </div>
        </div>
        
        <!-- Productos con bajo stock -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Bajo Stock</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $stats['low_stock_products'] }}</p>
                </div>
                <div class="p-2 sm:p-3 rounded-xl bg-red-50 text-red-600 group-hover:bg-red-100 transition-colors duration-300">
                    <i class="fas fa-exclamation-triangle text-lg sm:text-xl"></i>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Información adicional -->
    <div class="grid grid-cols-1 lg:grid-cols-2 gap-4 sm:gap-5">
        <!-- Productos con bajo stock -->
        <div class="bg-white rounded-xl border border-gray-100 p-5 sm:p-8">
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <h3 class="text-xs sm:text-sm font-semibold text-gray-900 uppercase tracking-wider">Productos con Bajo Stock</h3>
                <div class="p-2 rounded-lg bg-red-50 text-red-600">
                    <i class="fas fa-exclamation-triangle text-xs sm:text-sm"></i>
                </div>
            </div>
            <div>
                <p class="text-3xl sm:text-4xl font-bold text-gray-900 mb-2">{{ $stats['low_stock_products'] }}</p>
                <p class="text-xs text-gray-500">Productos con menos de 10 unidades</p>
            </div>
            @if($lowStockProducts->count() > 0)
                <div class="mt-4 space-y-2">
                    @foreach($lowStockProducts as $product)
                    <div class="flex items-center justify-between py-2 border-b border-gray-50 last:border-0">
                        <span class="text-sm text-gray-700">{{ $product->name }}</span>
                        <span class="text-sm font-semibold text-red-600">{{ $product->quantity }} unidades</span>
                    </div>
                    @endforeach
                </div>
            @endif
        </div>
    </div>
    
    <!-- Acciones rápidas -->
    <div class="bg-white rounded-xl border border-gray-100 p-5 sm:p-6">
        <h3 class="text-xs sm:text-sm font-semibold text-gray-900 uppercase tracking-wider mb-4 sm:mb-6">Acciones Rápidas</h3>
        <div class="grid grid-cols-2 sm:grid-cols-3 gap-3 sm:gap-4">
            @can('create_products')
            <a href="{{ route('products.create') }}" class="group flex flex-col items-center p-4 sm:p-6 rounded-xl border-2 border-gray-100 hover:border-blue-200 hover:bg-blue-50 transition-all duration-300">
                <div class="p-2 sm:p-3 rounded-xl bg-blue-100 text-blue-600 mb-2 sm:mb-3 group-hover:bg-blue-600 group-hover:text-white transition-colors duration-300">
                    <i class="fas fa-plus text-lg sm:text-xl"></i>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 group-hover:text-gray-900 text-center">Nuevo Producto</span>
            </a>
            @endcan
            
            @can('view_reports')
            <a href="{{ route('reports.index') }}" class="group flex flex-col items-center p-4 sm:p-6 rounded-xl border-2 border-gray-100 hover:border-violet-200 hover:bg-violet-50 transition-all duration-300">
                <div class="p-2 sm:p-3 rounded-xl bg-violet-100 text-violet-600 mb-2 sm:mb-3 group-hover:bg-violet-600 group-hover:text-white transition-colors duration-300">
                    <i class="fas fa-chart-bar text-lg sm:text-xl"></i>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 group-hover:text-gray-900 text-center">Ver Reportes</span>
            </a>
            @endcan
        </div>
    </div>
</div>
@endsection
