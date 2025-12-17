@extends('layouts.app')

@section('title', 'Clientes')
@section('header', 'Gestión de Clientes')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fas fa-users text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gestión de Clientes</h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="text-xs sm:text-sm text-gray-500">
                            <span class="font-semibold text-gray-900">{{ $customers->total() }}</span> clientes registrados
                        </span>
                        <span class="text-gray-300 hidden sm:inline">•</span>
                        <span class="text-xs sm:text-sm text-gray-500 hidden sm:inline">
                            <i class="fas fa-database mr-1"></i> Base de datos de clientes
                        </span>
                    </div>
                </div>
            </div>
            
            <a href="{{ route('customers.create') }}"
               class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 hover:border-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md">
                <i class="fas fa-plus mr-2"></i>
                <span>Nuevo Cliente</span>
            </a>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <form method="GET" action="{{ route('customers.index') }}" class="space-y-4" id="filter-form">
            <div class="flex items-center justify-between mb-4">
                <h3 class="text-sm font-semibold text-gray-700">Filtros de Búsqueda</h3>
                @if(request('search') || request('status'))
                    <a href="{{ route('customers.index') }}" 
                       class="text-xs text-emerald-600 hover:text-emerald-700 font-medium flex items-center">
                        <i class="fas fa-times mr-1"></i>
                        Limpiar filtros
                    </a>
                @endif
            </div>
            
            <div class="grid grid-cols-1 md:grid-cols-3 gap-4 sm:gap-5">
                <div>
                    <label for="search" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        Buscar Cliente
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                            <i class="fas fa-search text-gray-400 text-sm"></i>
                        </div>
                        <input type="text" 
                               id="search" 
                               name="search" 
                               value="{{ request('search') }}" 
                               class="block w-full pl-10 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all"
                               placeholder="Nombre, email o teléfono...">
                    </div>
                    @if(request('search'))
                        <p class="mt-1.5 text-xs text-gray-500 flex items-center">
                            <i class="fas fa-info-circle mr-1.5"></i>
                            Buscando: "{{ request('search') }}"
                        </p>
                    @endif
                </div>
                
                <div>
                    <label for="status" class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">
                        Estado del Cliente
                    </label>
                    <div class="relative">
                        <select id="status" 
                                name="status"
                                class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent appearance-none bg-white">
                            <option value="">Todos los estados</option>
                            <option value="active" {{ request('status') == 'active' ? 'selected' : '' }}>Activo</option>
                            <option value="inactive" {{ request('status') == 'inactive' ? 'selected' : '' }}>Inactivo</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                </div>
                
                <div class="flex items-end">
                    <button type="submit"
                            class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500">
                        <i class="fas fa-filter mr-2"></i>
                        Filtrar
                    </button>
                </div>
            </div>
            
            @if(request('search') || request('status'))
                <div class="pt-3 border-t border-gray-100">
                    <div class="flex items-center text-xs text-gray-600">
                        <i class="fas fa-info-circle mr-2 text-emerald-600"></i>
                        <span>
                            Mostrando 
                            <span class="font-semibold text-gray-900">{{ $customers->total() }}</span> 
                            {{ $customers->total() === 1 ? 'cliente' : 'clientes' }}
                            @if(request('search'))
                                que coinciden con "{{ request('search') }}"
                            @endif
                            @if(request('status'))
                                con estado {{ request('status') == 'active' ? 'activo' : 'inactivo' }}
                            @endif
                        </span>
                    </div>
                </div>
            @endif
        </form>
    </div>
    
    <!-- Tabla de clientes - Desktop -->
    <div class="hidden lg:block bg-white rounded-xl border border-gray-100 overflow-hidden">
        <div class="overflow-x-auto -mx-6 lg:mx-0">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50">
                    <tr>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Cliente
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Contacto
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Ubicación
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Registro y Actividad
                        </th>
                        <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Estado
                        </th>
                        <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                            Acciones
                        </th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-100">
                    @forelse($customers as $customer)
                    <tr class="hover:bg-gray-50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white text-sm font-semibold shadow-sm flex-shrink-0">
                                    {{ strtoupper(substr($customer->name, 0, 1)) }}
                                </div>
                                <div class="ml-3">
                                    <div class="text-sm font-semibold text-gray-900">{{ $customer->name }}</div>
                                    <div class="text-xs text-gray-500 mt-0.5">ID: {{ $customer->id }}</div>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="text-sm space-y-1">
                                @if($customer->email)
                                    <div class="flex items-center text-gray-700">
                                        <i class="fas fa-envelope text-gray-400 mr-2 text-xs"></i>
                                        <span class="truncate max-w-xs">{{ $customer->email }}</span>
                                    </div>
                                @endif
                                @if($customer->phone)
                                    <div class="flex items-center text-gray-700">
                                        <i class="fas fa-phone text-gray-400 mr-2 text-xs"></i>
                                        <span>{{ $customer->phone }}</span>
                                    </div>
                                @endif
                                @if(!$customer->email && !$customer->phone)
                                    <span class="text-xs text-gray-400">Sin contacto</span>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-4">
                            <div class="text-sm text-gray-700">
                                @if($customer->address)
                                    <div class="flex items-center mb-1">
                                        <i class="fas fa-map-marker-alt text-gray-400 mr-1.5 text-xs"></i>
                                        <span class="truncate max-w-xs">{{ $customer->address }}</span>
                                    </div>
                                @endif
                                @if($customer->city || $customer->state)
                                    <div class="text-xs text-gray-500">
                                        {{ $customer->city }}{{ $customer->city && $customer->state ? ', ' : '' }}{{ $customer->state }}
                                    </div>
                                @endif
                                @if(!$customer->address && !$customer->city && !$customer->state)
                                    <span class="text-xs text-gray-400">Sin ubicación</span>
                                @endif
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex flex-col space-y-1.5">
                                <div class="flex items-center space-x-2">
                                    @if($customer->requires_electronic_invoice && $customer->taxProfile)
                                        <div class="p-1.5 rounded-lg bg-blue-50 text-blue-600" title="Facturación Electrónica">
                                            <i class="fas fa-file-invoice text-xs"></i>
                                        </div>
                                    @endif
                                    <div class="text-xs text-gray-500">
                                        {{ $customer->created_at->format('d/m/Y') }}
                                    </div>
                                </div>
                                <div class="flex items-center">
                                    <div class="p-1 rounded-lg bg-emerald-50 text-emerald-600 mr-2">
                                        <i class="fas fa-history text-[10px]"></i>
                                    </div>
                                    <span class="text-[10px] text-gray-500 italic">Próximamente: Reservas</span>
                                </div>
                            </div>
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap">
                            @if($customer->is_active)
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                                    <i class="fas fa-check-circle mr-1.5"></i>
                                    Activo
                                </span>
                            @else
                                <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                                    <i class="fas fa-times-circle mr-1.5"></i>
                                    Inactivo
                                </span>
                            @endif
                        </td>
                        
                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2 sm:space-x-3">
                                <a href="{{ route('customers.show', $customer) }}"
                                   class="p-2 sm:p-1.5 text-blue-600 hover:text-blue-700 hover:bg-blue-50 rounded-lg transition-colors min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 flex items-center justify-center"
                                   title="Ver detalles">
                                    <i class="fas fa-eye text-sm sm:text-base"></i>
                                </a>
                                
                                <a href="{{ route('customers.edit', $customer) }}"
                                   class="p-2 sm:p-1.5 text-indigo-600 hover:text-indigo-700 hover:bg-indigo-50 rounded-lg transition-colors min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 flex items-center justify-center"
                                   title="Editar">
                                    <i class="fas fa-edit text-sm sm:text-base"></i>
                                </a>
                                
                                <button type="button"
                                        onclick="openDeleteModal({{ $customer->id }}, {{ json_encode($customer->name) }})"
                                        class="p-2 sm:p-1.5 text-red-600 hover:text-red-700 hover:bg-red-50 rounded-lg transition-colors min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 flex items-center justify-center"
                                        title="Eliminar">
                                    <i class="fas fa-trash text-sm sm:text-base"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-16 text-center">
                            <div class="flex flex-col items-center">
                                @if(request('search') || request('status'))
                                    <div class="p-4 rounded-full bg-amber-50 text-amber-600 mb-4">
                                        <i class="fas fa-search text-3xl"></i>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 mb-2">No se encontraron resultados</p>
                                    <p class="text-sm text-gray-500 mb-4">
                                        No hay clientes que coincidan con los filtros aplicados.
                                    </p>
                                    <a href="{{ route('customers.index') }}"
                                       class="inline-flex items-center justify-center px-4 sm:px-5 py-3 sm:py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm sm:text-base font-semibold hover:bg-emerald-700 transition-all min-h-[44px]">
                                        <i class="fas fa-times mr-2"></i>
                                        Limpiar filtros
                                    </a>
                                @else
                                    <div class="p-4 rounded-full bg-gray-50 text-gray-400 mb-4">
                                        <i class="fas fa-users text-3xl"></i>
                                    </div>
                                    <p class="text-lg font-semibold text-gray-900 mb-2">No hay clientes registrados</p>
                                    <p class="text-sm text-gray-500 mb-4">
                                        Comienza agregando tu primer cliente al sistema.
                                    </p>
                                    <a href="{{ route('customers.create') }}"
                                       class="inline-flex items-center justify-center px-4 sm:px-5 py-3 sm:py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm sm:text-base font-semibold hover:bg-emerald-700 transition-all min-h-[44px]">
                                        <i class="fas fa-plus mr-2"></i>
                                        <span class="hidden sm:inline">Crear Primer Cliente</span>
                                        <span class="sm:hidden">Crear Cliente</span>
                                    </a>
                                @endif
                            </div>
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        <!-- Paginación Desktop -->
        @if($customers->hasPages())
        <div class="bg-white px-6 py-4 border-t border-gray-100">
            <div class="flex items-center justify-between">
                <div class="text-sm text-gray-600">
                    Mostrando 
                    <span class="font-semibold text-gray-900">{{ $customers->firstItem() }}</span>
                    a 
                    <span class="font-semibold text-gray-900">{{ $customers->lastItem() }}</span>
                    de 
                    <span class="font-semibold text-gray-900">{{ $customers->total() }}</span>
                    {{ $customers->total() === 1 ? 'cliente' : 'clientes' }}
                </div>
                <div class="flex-1">
                    {{ $customers->appends(request()->query())->links() }}
                </div>
            </div>
        </div>
        @endif
    </div>
    
    <!-- Cards de clientes - Mobile/Tablet -->
    <div class="lg:hidden space-y-4">
        @forelse($customers as $customer)
        <div class="bg-white rounded-xl border border-gray-100 p-4 hover:shadow-md transition-shadow duration-200">
            <div class="flex items-start justify-between mb-4">
                <div class="flex items-center space-x-3 flex-1 min-w-0">
                    <div class="h-12 w-12 rounded-full bg-gradient-to-br from-emerald-500 to-emerald-600 flex items-center justify-center text-white text-base font-semibold shadow-sm flex-shrink-0">
                        {{ strtoupper(substr($customer->name, 0, 1)) }}
                    </div>
                    <div class="min-w-0 flex-1">
                        <h3 class="text-sm font-semibold text-gray-900 truncate">{{ $customer->name }}</h3>
                        <p class="text-xs text-gray-500 mt-0.5">ID: {{ $customer->id }}</p>
                    </div>
                </div>
                
                <div class="flex items-center space-x-2 ml-2">
                    @if($customer->is_active)
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                            <i class="fas fa-check-circle mr-1"></i>
                            Activo
                        </span>
                    @else
                        <span class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-gray-100 text-gray-700">
                            <i class="fas fa-times-circle mr-1"></i>
                            Inactivo
                        </span>
                    @endif
                </div>
            </div>
            
            <div class="space-y-3 mb-4">
                <!-- Contacto -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Contacto</p>
                    <div class="space-y-1.5">
                        @if($customer->email)
                            <div class="flex items-center text-sm text-gray-700">
                                <i class="fas fa-envelope text-gray-400 mr-2 text-xs w-4"></i>
                                <span class="truncate">{{ $customer->email }}</span>
                            </div>
                        @endif
                        @if($customer->phone)
                            <div class="flex items-center text-sm text-gray-700">
                                <i class="fas fa-phone text-gray-400 mr-2 text-xs w-4"></i>
                                <span>{{ $customer->phone }}</span>
                            </div>
                        @endif
                        @if(!$customer->email && !$customer->phone)
                            <span class="text-xs text-gray-400">Sin contacto</span>
                        @endif
                    </div>
                </div>
                
                <!-- Ubicación -->
                @if($customer->address || $customer->city || $customer->state)
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Ubicación</p>
                    <div class="text-sm text-gray-700">
                        @if($customer->address)
                            <div class="flex items-start mb-1">
                                <i class="fas fa-map-marker-alt text-gray-400 mr-2 text-xs mt-0.5 w-4"></i>
                                <span class="flex-1">{{ $customer->address }}</span>
                            </div>
                        @endif
                        @if($customer->city || $customer->state)
                            <div class="text-xs text-gray-500 ml-6">
                                {{ $customer->city }}{{ $customer->city && $customer->state ? ', ' : '' }}{{ $customer->state }}
                            </div>
                        @endif
                    </div>
                </div>
                @endif
                
                <!-- Información y Actividad -->
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">Información y Actividad</p>
                    <div class="space-y-2">
                        <div class="flex items-center space-x-2">
                            <div class="flex items-center text-xs text-gray-600">
                                <i class="fas fa-calendar text-gray-400 mr-2 text-xs w-4"></i>
                                <span>Registrado: {{ $customer->created_at->format('d/m/Y') }}</span>
                            </div>
                            @if($customer->requires_electronic_invoice && $customer->taxProfile)
                                <div class="flex items-center text-xs text-blue-600">
                                    <i class="fas fa-file-invoice text-blue-400 mr-2 text-xs w-4"></i>
                                    <span>Facturación Electrónica</span>
                                </div>
                            @endif
                        </div>
                        <div class="flex items-center p-2 bg-gray-50 rounded-lg">
                            <div class="p-1.5 rounded-lg bg-white border border-gray-200 text-gray-400 mr-2">
                                <i class="fas fa-history text-xs"></i>
                            </div>
                            <span class="text-xs text-gray-500 italic">Próximamente: Reservas</span>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Acciones -->
            <div class="flex items-center justify-end space-x-2 sm:space-x-3 pt-3 border-t border-gray-100">
                <a href="{{ route('customers.show', $customer) }}"
                   class="p-3 sm:p-2 text-blue-600 hover:bg-blue-50 rounded-lg transition-colors min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 flex items-center justify-center"
                   title="Ver">
                    <i class="fas fa-eye text-base sm:text-sm"></i>
                </a>
                
                <a href="{{ route('customers.edit', $customer) }}"
                   class="p-3 sm:p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 flex items-center justify-center"
                   title="Editar">
                    <i class="fas fa-edit text-base sm:text-sm"></i>
                </a>
                
                <button type="button"
                        onclick="openDeleteModal({{ $customer->id }}, {{ json_encode($customer->name) }})"
                        class="p-3 sm:p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors min-w-[44px] min-h-[44px] sm:min-w-0 sm:min-h-0 flex items-center justify-center"
                        title="Eliminar">
                    <i class="fas fa-trash text-base sm:text-sm"></i>
                </button>
            </div>
        </div>
        @empty
        <div class="bg-white rounded-xl border border-gray-100 p-12 text-center">
            @if(request('search') || request('status'))
                <div class="p-4 rounded-full bg-amber-50 text-amber-600 mb-4 inline-block">
                    <i class="fas fa-search text-3xl"></i>
                </div>
                <p class="text-lg font-semibold text-gray-900 mb-2">No se encontraron resultados</p>
                <p class="text-sm text-gray-500 mb-4">
                    No hay clientes que coincidan con los filtros aplicados.
                </p>
                <a href="{{ route('customers.index') }}" 
                   class="inline-flex items-center px-4 py-2 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-all">
                    <i class="fas fa-times mr-2"></i>
                    Limpiar filtros
                </a>
            @else
                <div class="p-4 rounded-full bg-gray-50 text-gray-400 mb-4 inline-block">
                    <i class="fas fa-users text-3xl"></i>
                </div>
                <p class="text-lg font-semibold text-gray-900 mb-2">No hay clientes registrados</p>
                <p class="text-sm text-gray-500 mb-4">
                    Comienza agregando tu primer cliente al sistema.
                </p>
                <a href="{{ route('customers.create') }}" 
                   class="inline-flex items-center px-4 py-2 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 transition-all">
                    <i class="fas fa-plus mr-2"></i>
                    Crear Primer Cliente
                </a>
            @endif
        </div>
        @endforelse
        
        <!-- Paginación Mobile -->
        @if($customers->hasPages())
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="text-center text-sm text-gray-600 mb-3">
                Mostrando 
                <span class="font-semibold text-gray-900">{{ $customers->firstItem() }}</span>
                a 
                <span class="font-semibold text-gray-900">{{ $customers->lastItem() }}</span>
                de 
                <span class="font-semibold text-gray-900">{{ $customers->total() }}</span>
                {{ $customers->total() === 1 ? 'cliente' : 'clientes' }}
            </div>
            {{ $customers->appends(request()->query())->links() }}
        </div>
        @endif
    </div>
</div>

<!-- Modal de Confirmación de Eliminación -->
<div id="delete-modal" class="fixed inset-0 bg-gray-600 bg-opacity-50 overflow-y-auto h-full w-full hidden z-50" style="display: none;">
    <div class="relative top-10 sm:top-20 mx-auto p-4 sm:p-6 border w-11/12 sm:w-96 shadow-xl rounded-xl bg-white max-h-[90vh] overflow-y-auto">
        <div class="mt-3">
            <!-- Header del modal -->
            <div class="flex items-center justify-between mb-4 pb-4 border-b border-gray-200">
                <div class="flex items-center space-x-3">
                    <div class="p-2.5 rounded-xl bg-red-50 text-red-600">
                        <i class="fas fa-exclamation-triangle text-lg"></i>
                    </div>
                    <h3 class="text-lg sm:text-xl font-bold text-gray-900">Eliminar Cliente</h3>
                </div>
                <button type="button" onclick="closeDeleteModal()"
                        class="text-gray-400 hover:text-gray-600 focus:outline-none transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Contenido del modal -->
            <div class="mb-6">
                <p class="text-sm text-gray-600 mb-4">
                    ¿Estás seguro de que deseas eliminar este cliente? Esta acción no se puede deshacer.
                </p>
                
                <div class="bg-red-50 border border-red-200 rounded-xl p-4">
                    <div class="flex items-start space-x-3">
                        <div class="p-2 rounded-lg bg-red-100 text-red-600 flex-shrink-0">
                            <i class="fas fa-user text-sm"></i>
                        </div>
                        <div class="flex-1 min-w-0">
                            <div class="text-sm font-semibold text-gray-900 mb-1" id="delete-customer-name"></div>
                            <div class="text-xs text-gray-500">La información del cliente será eliminada permanentemente</div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer del modal -->
            <form id="delete-form" method="POST" class="inline">
                @csrf
                @method('DELETE')
                <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-end gap-3 pt-4 border-t border-gray-200">
                    <button type="button" onclick="closeDeleteModal()"
                            class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                        <i class="fas fa-times mr-2"></i>
                        Cancelar
                    </button>

                    <button type="submit"
                            class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-red-600 bg-red-600 text-white text-sm font-semibold hover:bg-red-700 hover:border-red-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-red-500 shadow-sm hover:shadow-md">
                        <i class="fas fa-trash mr-2"></i>
                        Eliminar Cliente
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

@push('scripts')
<script>
function openDeleteModal(customerId, customerName) {
    const modal = document.getElementById('delete-modal');
    const form = document.getElementById('delete-form');
    const nameElement = document.getElementById('delete-customer-name');
    
    // Establecer la acción del formulario
    form.action = '{{ route("customers.destroy", ":id") }}'.replace(':id', customerId);
    
    // Establecer el nombre del cliente
    nameElement.textContent = customerName;
    
    // Mostrar el modal
    modal.classList.remove('hidden');
    modal.style.display = 'block';
    document.body.style.overflow = 'hidden';
}

function closeDeleteModal() {
    const modal = document.getElementById('delete-modal');
    modal.classList.add('hidden');
    modal.style.display = 'none';
    document.body.style.overflow = 'auto';
}

// Cerrar modal al hacer clic fuera
document.getElementById('delete-modal').addEventListener('click', function(e) {
    if (e.target === this) {
        closeDeleteModal();
    }
});

// Cerrar modal con ESC
document.addEventListener('keydown', function(e) {
    if (e.key === 'Escape') {
        const modal = document.getElementById('delete-modal');
        if (!modal.classList.contains('hidden')) {
            closeDeleteModal();
        }
    }
});
</script>
@endpush
@endsection
