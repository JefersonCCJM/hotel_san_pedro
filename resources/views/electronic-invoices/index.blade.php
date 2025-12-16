@extends('layouts.app')

@section('title', 'Facturas Electrónicas')
@section('header', 'Facturas Electrónicas')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-file-invoice-dollar text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Facturas Electrónicas</h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="text-xs sm:text-sm text-gray-500">
                            <span class="font-semibold text-gray-900">{{ $invoices->total() }}</span> facturas registradas
                        </span>
                        <span class="text-gray-300 hidden sm:inline">•</span>
                        <span class="text-xs sm:text-sm text-gray-500 hidden sm:inline">
                            <i class="fas fa-file-invoice mr-1"></i> Facturación electrónica DIAN
                        </span>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6" x-data="{ filtersOpen: false }">
        <div class="flex items-center justify-between mb-4">
            <div class="flex items-center space-x-2 sm:space-x-3">
                <div class="p-2 rounded-xl bg-gray-50 text-gray-600">
                    <i class="fas fa-filter text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Filtros de Búsqueda</h2>
            </div>
            <button @click="filtersOpen = !filtersOpen"
                    class="inline-flex items-center px-3 sm:px-4 py-2 rounded-xl border border-gray-300 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-blue-500">
                <i class="fas mr-2" :class="filtersOpen ? 'fa-chevron-up' : 'fa-chevron-down'"></i>
                <span x-text="filtersOpen ? 'Ocultar' : 'Mostrar'"></span>
            </button>
        </div>

        <form method="GET" action="{{ route('electronic-invoices.index') }}"
              x-show="filtersOpen"
              x-transition:enter="transition ease-out duration-200"
              x-transition:enter-start="opacity-0 transform scale-95"
              x-transition:enter-end="opacity-100 transform scale-100"
              x-transition:leave="transition ease-in duration-150"
              x-transition:leave-start="opacity-100 transform scale-100"
              x-transition:leave-end="opacity-0 transform scale-95"
              class="space-y-4 sm:space-y-5 border-t border-gray-200 pt-4">

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-5">
                <!-- Identificación -->
                <div>
                    <label for="filter_identification" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        <i class="fas fa-id-card mr-1.5 text-gray-400"></i>
                        Identificación del Cliente
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-fingerprint text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="filter_identification"
                               name="filter_identification"
                               value="{{ request('filter_identification') }}"
                               placeholder="Ej: 123456789"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Nombres -->
                <div>
                    <label for="filter_names" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        <i class="fas fa-user mr-1.5 text-gray-400"></i>
                        Nombre del Cliente
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="filter_names"
                               name="filter_names"
                               value="{{ request('filter_names') }}"
                               placeholder="Ej: Juan Pérez"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Número de Factura -->
                <div>
                    <label for="filter_number" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        <i class="fas fa-hashtag mr-1.5 text-gray-400"></i>
                        Número de Factura
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-file-invoice text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="filter_number"
                               name="filter_number"
                               value="{{ request('filter_number') }}"
                               placeholder="Ej: SETP990000203"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Prefijo -->
                <div>
                    <label for="filter_prefix" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        <i class="fas fa-tag mr-1.5 text-gray-400"></i>
                        Prefijo
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-tag text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="filter_prefix"
                               name="filter_prefix"
                               value="{{ request('filter_prefix') }}"
                               placeholder="Ej: SETP"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Código de Referencia -->
                <div>
                    <label for="filter_reference_code" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        <i class="fas fa-barcode mr-1.5 text-gray-400"></i>
                        Código de Referencia
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-barcode text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="filter_reference_code"
                               name="filter_reference_code"
                               value="{{ request('filter_reference_code') }}"
                               placeholder="Ej: FV-00000001"
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 font-mono focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent transition-all">
                    </div>
                </div>

                <!-- Estado -->
                <div>
                    <label for="filter_status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        <i class="fas fa-info-circle mr-1.5 text-gray-400"></i>
                        Estado
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-info-circle text-gray-400 text-sm"></i>
                        </div>
                        <select id="filter_status"
                                name="filter_status"
                                class="block w-full pl-10 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white">
                            <option value="">Todos los estados</option>
                            <option value="1" {{ request('filter_status') == '1' ? 'selected' : '' }}>Aceptada</option>
                            <option value="0" {{ request('filter_status') == '0' ? 'selected' : '' }}>Pendiente</option>
                            <option value="rejected" {{ request('filter_status') == 'rejected' ? 'selected' : '' }}>Rechazada</option>
                            <option value="sent" {{ request('filter_status') == 'sent' ? 'selected' : '' }}>Enviada</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-2 border-t border-gray-200">
                <a href="{{ route('electronic-invoices.index') }}"
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-times mr-2"></i>
                    Limpiar
                </a>
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 hover:border-blue-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm hover:shadow-md">
                    <i class="fas fa-search mr-2"></i>
                    Buscar
                </button>
            </div>
        </form>
    </div>

    <!-- Vista Desktop: Tabla -->
    <div class="hidden lg:block bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-200">
                <thead class="bg-gray-50">
                    <tr>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Número
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Identificación
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Total
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Estado
                        </th>
                        <th scope="col" class="px-6 py-3 text-left text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Fecha
                        </th>
                        <th scope="col" class="px-6 py-3 text-right text-xs font-semibold text-gray-700 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-200">
                    @forelse($invoices as $invoice)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">{{ $invoice->document }}</div>
                            @if($invoice->reference_code)
                                <div class="text-xs text-gray-500 font-mono mt-0.5">{{ $invoice->reference_code }}</div>
                            @endif
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-semibold text-gray-900">
                                {{ $invoice->customer->name }}
                            </div>
                            @if($invoice->customer->taxProfile && $invoice->customer->taxProfile->company)
                                <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->customer->taxProfile->company }}</div>
                            @endif
                            @if($invoice->customer->email)
                                <div class="text-xs text-gray-400 mt-0.5">
                                    <i class="fas fa-envelope mr-1"></i>
                                    {{ $invoice->customer->email }}
                                </div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($invoice->customer->taxProfile)
                                <div class="text-sm font-semibold text-gray-900">{{ $invoice->customer->taxProfile->identification ?? '-' }}</div>
                                @if($invoice->customer->taxProfile->identificationDocument)
                                    <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->customer->taxProfile->identificationDocument->name }}</div>
                                @endif
                            @else
                                <div class="text-sm text-gray-400">-</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-semibold text-gray-900">
                                ${{ number_format($invoice->total, 2) }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($invoice->isAccepted())
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                                    <i class="fas fa-check-circle mr-1.5"></i>
                                    Aceptada
                                </span>
                            @elseif($invoice->isRejected())
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                                    <i class="fas fa-times-circle mr-1.5"></i>
                                    Rechazada
                                </span>
                            @elseif($invoice->status === 'sent')
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                                    <i class="fas fa-paper-plane mr-1.5"></i>
                                    Enviada
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                                    <i class="fas fa-clock mr-1.5"></i>
                                    Pendiente
                                </span>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-500">
                            <div class="flex items-center">
                                <i class="fas fa-calendar-alt mr-1.5 text-gray-400"></i>
                                {{ $invoice->created_at->format('d/m/Y') }}
                            </div>
                            <div class="text-xs text-gray-400 mt-0.5">
                                {{ $invoice->created_at->format('H:i') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end gap-2">
                                <a href="{{ route('electronic-invoices.show', $invoice) }}?return_to=index&{{ http_build_query(request()->query()) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-blue-600 bg-blue-600 text-white text-xs font-semibold hover:bg-blue-700 transition-colors">
                                    <i class="fas fa-eye mr-1"></i>
                                    Ver
                                </a>
                                <a href="{{ route('electronic-invoices.download-pdf', $invoice) }}"
                                   class="inline-flex items-center px-3 py-1.5 rounded-lg border border-red-600 bg-red-600 text-white text-xs font-semibold hover:bg-red-700 transition-colors"
                                   title="Descargar PDF">
                                    <i class="fas fa-file-pdf mr-1"></i>
                                    PDF
                                </a>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center justify-center">
                                <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                                    <i class="fas fa-file-invoice-dollar text-gray-400 text-2xl"></i>
                                </div>
                                <p class="text-sm font-semibold text-gray-900 mb-1">No se encontraron facturas</p>
                                <p class="text-xs text-gray-500">Intenta ajustar los filtros de búsqueda</p>
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>

        <!-- Paginación Desktop -->
        @if($invoices->hasPages())
        <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-700">
                    Mostrando
                    <span class="font-semibold">{{ $invoices->firstItem() }}</span>
                    a
                    <span class="font-semibold">{{ $invoices->lastItem() }}</span>
                    de
                    <span class="font-semibold">{{ $invoices->total() }}</span>
                    resultados
                </div>
                <div>
                    {{ $invoices->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>

    <!-- Vista Móvil: Cards -->
    <div class="lg:hidden space-y-4">
        @forelse($invoices as $invoice)
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-5 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between mb-4 pb-4 border-b border-gray-200">
                <div class="flex-1 min-w-0">
                    <div class="flex items-center space-x-2 mb-2">
                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600">
                            <i class="fas fa-file-invoice-dollar text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-gray-900 truncate">{{ $invoice->document }}</div>
                            @if($invoice->reference_code)
                                <div class="text-xs text-gray-500 font-mono truncate">{{ $invoice->reference_code }}</div>
                            @endif
                        </div>
                    </div>
                </div>
                <div>
                    @if($invoice->isAccepted())
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 border border-emerald-200">
                            <i class="fas fa-check-circle mr-1"></i>
                            Aceptada
                        </span>
                    @elseif($invoice->isRejected())
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-red-50 text-red-700 border border-red-200">
                            <i class="fas fa-times-circle mr-1"></i>
                            Rechazada
                        </span>
                    @elseif($invoice->status === 'sent')
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700 border border-blue-200">
                            <i class="fas fa-paper-plane mr-1"></i>
                            Enviada
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700 border border-amber-200">
                            <i class="fas fa-clock mr-1"></i>
                            Pendiente
                        </span>
                    @endif
                </div>
            </div>

            <div class="space-y-3">
                <!-- Cliente -->
                <div class="flex items-start space-x-3">
                    <div class="p-1.5 rounded-lg bg-emerald-50 text-emerald-600 flex-shrink-0">
                        <i class="fas fa-user text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Cliente</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $invoice->customer->name }}</div>
                        @if($invoice->customer->taxProfile && $invoice->customer->taxProfile->company)
                            <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->customer->taxProfile->company }}</div>
                        @endif
                        @if($invoice->customer->email)
                            <div class="text-xs text-gray-400 mt-1">
                                <i class="fas fa-envelope mr-1"></i>
                                {{ $invoice->customer->email }}
                            </div>
                        @endif
                    </div>
                </div>

                <!-- Identificación -->
                @if($invoice->customer->taxProfile)
                <div class="flex items-start space-x-3">
                    <div class="p-1.5 rounded-lg bg-violet-50 text-violet-600 flex-shrink-0">
                        <i class="fas fa-fingerprint text-xs"></i>
                    </div>
                    <div class="flex-1 min-w-0">
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Identificación</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $invoice->customer->taxProfile->identification ?? '-' }}</div>
                        @if($invoice->customer->taxProfile->identificationDocument)
                            <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->customer->taxProfile->identificationDocument->name }}</div>
                        @endif
                    </div>
                </div>
                @endif

                <!-- Total y Fecha -->
                <div class="grid grid-cols-2 gap-4 pt-2 border-t border-gray-100">
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Total</div>
                        <div class="text-base font-bold text-gray-900">${{ number_format($invoice->total, 2) }}</div>
                    </div>
                    <div>
                        <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha</div>
                        <div class="text-sm font-semibold text-gray-900">{{ $invoice->created_at->format('d/m/Y') }}</div>
                        <div class="text-xs text-gray-500 mt-0.5">{{ $invoice->created_at->format('H:i') }}</div>
                    </div>
                </div>
            </div>

            <!-- Acciones -->
            <div class="flex items-center gap-2 mt-4 pt-4 border-t border-gray-100">
                <a href="{{ route('electronic-invoices.show', $invoice) }}?return_to=index&{{ http_build_query(request()->query()) }}"
                   class="flex-1 inline-flex items-center justify-center px-4 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 hover:border-blue-700 transition-all duration-200">
                    <i class="fas fa-eye mr-2"></i>
                    Ver Detalles
                </a>
                <a href="{{ route('electronic-invoices.download-pdf', $invoice) }}"
                   class="inline-flex items-center justify-center px-3 py-2.5 rounded-xl border-2 border-red-600 bg-red-600 text-white text-sm font-semibold hover:bg-red-700 hover:border-red-700 transition-all duration-200"
                   title="Descargar PDF">
                    <i class="fas fa-file-pdf"></i>
                </a>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 p-8 sm:p-12">
            <div class="flex flex-col items-center justify-center text-center">
                <div class="h-16 w-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                    <i class="fas fa-file-invoice-dollar text-gray-400 text-2xl"></i>
                </div>
                <p class="text-sm font-semibold text-gray-900 mb-1">No se encontraron facturas</p>
                <p class="text-xs text-gray-500">Intenta ajustar los filtros de búsqueda</p>
            </div>
        </div>
        @endforelse

        <!-- Paginación Móvil -->
        @if($invoices->hasPages())
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="flex flex-col items-center justify-center space-y-4">
                <div class="text-xs sm:text-sm text-gray-700 text-center">
                    Mostrando
                    <span class="font-semibold">{{ $invoices->firstItem() }}</span>
                    a
                    <span class="font-semibold">{{ $invoices->lastItem() }}</span>
                    de
                    <span class="font-semibold">{{ $invoices->total() }}</span>
                    resultados
                </div>
                <div class="w-full">
                    {{ $invoices->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
</div>
@endsection
