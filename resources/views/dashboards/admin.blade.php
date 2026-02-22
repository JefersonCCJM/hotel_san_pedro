@extends('layouts.app')

@section('title', 'Dashboard')
@section('header', 'Dashboard')

@section('content')
<div class="space-y-5 sm:space-y-8">
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col gap-4 lg:flex-row lg:items-start lg:justify-between">
            <div>
                <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Control de Turnos</p>
                <h3 class="text-lg font-black {{ $shiftOperationsEnabled ? 'text-emerald-700' : 'text-red-700' }}">
                    {{ $shiftOperationsEnabled ? 'Apertura habilitada para recepcion' : 'Apertura bloqueada para recepcion' }}
                </h3>
                <p class="text-xs text-gray-500 mt-1">
                    Ultima actualizacion:
                    {{ optional($shiftOperationsUpdatedAt)->format('d/m/Y H:i') ?? 'N/A' }}
                    @if(!empty($shiftOperationsUpdatedBy))
                        por {{ $shiftOperationsUpdatedBy }}
                    @endif
                </p>
                @if(!empty($shiftOperationsNote))
                    <p class="text-xs text-gray-500 mt-1">Nota: {{ $shiftOperationsNote }}</p>
                @endif
                @if($operationalShift)
                    <p class="text-xs text-blue-700 mt-2 font-semibold">
                        Turno operativo abierto:
                        {{ strtoupper($operationalShift->type->value) }}
                        @if($operationalShift->opened_at)
                            desde {{ $operationalShift->opened_at->format('d/m/Y H:i') }}
                        @endif
                        @if($operationalShift->openedBy)
                            ({{ $operationalShift->openedBy->name }})
                        @endif
                    </p>
                @endif
            </div>

            @can('manage_shift_handovers')
                <div class="w-full lg:w-auto flex flex-col gap-3">
                    <form action="{{ route('shift.operations.toggle') }}" method="POST" class="flex flex-col sm:flex-row gap-2">
                        @csrf
                        <input type="hidden" name="enabled" value="{{ $shiftOperationsEnabled ? 0 : 1 }}">
                        <input
                            type="text"
                            name="note"
                            maxlength="500"
                            placeholder="{{ $shiftOperationsEnabled ? 'Motivo para bloquear aperturas' : 'Motivo para habilitar aperturas' }}"
                            class="w-full sm:w-80 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500"
                        >
                        <button
                            type="submit"
                            class="px-4 py-2 rounded-lg text-sm font-bold text-white {{ $shiftOperationsEnabled ? 'bg-red-600 hover:bg-red-700' : 'bg-emerald-600 hover:bg-emerald-700' }}"
                        >
                            {{ $shiftOperationsEnabled ? 'Desactivar turnos' : 'Activar turnos' }}
                        </button>
                    </form>

                    @if($operationalShift)
                        <form action="{{ route('shift.force-close') }}" method="POST" class="flex flex-col sm:flex-row gap-2" onsubmit="return confirm('Esto cerrara el turno operativo actual y su acta activa. Deseas continuar?');">
                            @csrf
                            <input
                                type="text"
                                name="reason"
                                maxlength="255"
                                value="Cierre forzado desde panel administrador"
                                class="w-full sm:w-80 px-3 py-2 border border-gray-200 rounded-lg text-sm focus:ring-2 focus:ring-blue-500 focus:border-blue-500"
                                required
                            >
                            <button type="submit" class="px-4 py-2 rounded-lg text-sm font-bold text-white bg-blue-600 hover:bg-blue-700">
                                Cerrar turno operativo
                            </button>
                        </form>
                    @endif
                </div>
            @endcan
        </div>
    </div>
    <!-- Estadísticas principales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
        <!-- Caja Disponible (Turno activo) -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Caja Disponible (Turno)</p>
                    @if(($cashbox['has_active_shift'] ?? false) === true)
                        <p class="text-2xl sm:text-3xl font-black text-gray-900 mb-1">
                            ${{ number_format((float) ($cashbox['cash_available'] ?? 0), 0, ',', '.') }}
                        </p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                            Turno #{{ $cashbox['shift_id'] }} · {{ strtoupper($cashbox['shift_type'] ?? '') }} · {{ $cashbox['receptionist'] ?? 'N/A' }}
                        </p>
                    @elseif(isset($cashbox['cash_available']) && $cashbox['cash_available'] > 0)
                        <p class="text-2xl sm:text-3xl font-black text-amber-600 mb-1">
                            ${{ number_format((float) $cashbox['cash_available'], 0, ',', '.') }}
                        </p>
                        <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                            Último Cierre ({{ $cashbox['last_shift_date'] ?? 'N/A' }})
                        </p>
                    @else
                        <p class="text-lg font-black text-amber-700 mb-1">Sin dinero en caja</p>
                        <p class="text-xs text-gray-500">Inicie un turno para ver el flujo de dinero.</p>
                    @endif

                    @if(($cashbox['has_active_shift'] ?? false) === true)
                        <div class="mt-3 flex gap-2 flex-wrap">
                            <a href="{{ route('cash-outflows.index') }}" class="inline-flex items-center px-3 py-1.5 rounded-lg bg-rose-50 text-rose-700 text-xs font-black hover:bg-rose-100 transition-colors">
                                <i class="fas fa-receipt mr-2"></i> Gastos (Caja)
                            </a>
                        </div>
                    @endif
                </div>
                <div class="p-2 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 transition-colors duration-300">
                    <i class="fas fa-cash-register text-lg sm:text-xl"></i>
                </div>
            </div>
        </div>

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

        <!-- Total Reservas -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Total Reservas</p>
                    <p class="text-2xl sm:text-3xl font-bold text-gray-900 mb-2">{{ $stats['total_reservations'] }}</p>
                </div>
                <div class="p-2 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 transition-colors duration-300">
                    <i class="fas fa-calendar-check text-lg sm:text-xl"></i>
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

        <!-- Ingresos Totales (Histórico) -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 hover:shadow-md transition-all duration-300 group">
            <div class="flex items-start justify-between">
                <div class="flex-1">
                    <p class="text-xs font-medium text-gray-500 uppercase tracking-wider mb-1">Ingresos Totales</p>
                    <p class="text-2xl sm:text-3xl font-black text-emerald-700 mb-1">
                        ${{ number_format($stats['total_revenue'], 0, ',', '.') }}
                    </p>
                    <p class="text-[10px] text-gray-400 font-bold uppercase tracking-widest">
                        Acumulado Histórico
                    </p>
                </div>
                <div class="p-2 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600 group-hover:bg-emerald-100 transition-colors duration-300">
                    <i class="fas fa-chart-line text-lg sm:text-xl"></i>
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

            @can('create_reservations')
            <a href="{{ route('reservations.create') }}" class="group flex flex-col items-center p-4 sm:p-6 rounded-xl border-2 border-gray-100 hover:border-emerald-200 hover:bg-emerald-50 transition-all duration-300">
                <div class="p-2 sm:p-3 rounded-xl bg-emerald-100 text-emerald-600 mb-2 sm:mb-3 group-hover:bg-emerald-600 group-hover:text-white transition-colors duration-300">
                    <i class="fas fa-calendar-plus text-lg sm:text-xl"></i>
                </div>
                <span class="text-xs sm:text-sm font-medium text-gray-700 group-hover:text-gray-900 text-center">Nueva Reserva</span>
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
