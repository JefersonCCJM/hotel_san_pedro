@extends('layouts.app')

@section('title', 'Reportes')
@section('header', 'Centro de Reportes')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-violet-50 text-violet-600">
                    <i class="fas fa-chart-pie text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Centro de Reportes</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Análisis y estadísticas de tu negocio</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Reportes disponibles -->
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Estadísticas Rápidas -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-center justify-between mb-4">
                <div class="h-12 w-12 sm:h-14 sm:w-14 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shadow-sm">
                    <i class="fas fa-chart-pie text-lg sm:text-xl"></i>
                </div>
                <span class="text-xs sm:text-sm text-gray-500 font-semibold uppercase tracking-wider">Resumen</span>
            </div>

            <h3 class="text-lg sm:text-xl font-semibold text-gray-900 mb-2">Vista General</h3>
            <p class="text-sm text-gray-600 mb-4 leading-relaxed">
                Resumen rápido de las métricas más importantes de tu negocio en tiempo real.
            </p>
        </div>
    </div>

    <!-- Accesos rápidos -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
            <div class="p-2 rounded-xl bg-gray-50 text-gray-600">
                <i class="fas fa-bolt text-sm"></i>
            </div>
            <h2 class="text-base sm:text-lg font-semibold text-gray-900">Accesos Rápidos</h2>
        </div>
        <div class="grid grid-cols-1 sm:grid-cols-3 gap-3 sm:gap-4">
            <a href="{{ route('products.index') }}"
               class="flex items-center p-3 sm:p-4 bg-violet-50 rounded-xl border-2 border-violet-100 hover:bg-violet-100 hover:border-violet-200 transition-all duration-200 group">
                <div class="h-10 w-10 rounded-xl bg-violet-600 text-white flex items-center justify-center mr-3 group-hover:scale-110 transition-transform duration-200">
                    <i class="fas fa-boxes"></i>
                </div>
                <span class="text-sm font-semibold text-gray-700">Inventario</span>
            </a>
        </div>
    </div>
</div>
@endsection
