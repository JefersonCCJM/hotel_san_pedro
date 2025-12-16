@extends('layouts.app')

@section('title', $customer->name)
@section('header', 'Detalles del Cliente')

@section('content')
<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="h-12 w-12 sm:h-14 sm:w-14 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center flex-shrink-0">
                    @php
                        $initials = strtoupper(substr($customer->name, 0, 2));
                    @endphp
                    <span class="text-lg sm:text-xl font-bold">{{ $initials }}</span>
                </div>
                <div class="flex-1 min-w-0">
                    <div class="flex flex-col sm:flex-row sm:items-center gap-2 sm:gap-3 mb-2">
                        <h1 class="text-xl sm:text-2xl font-bold text-gray-900 truncate">{{ $customer->name }}</h1>
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
                    </div>
                    <div class="flex flex-wrap items-center gap-3 sm:gap-4 text-xs sm:text-sm text-gray-500">
                        <div class="flex items-center space-x-1.5">
                            <i class="fas fa-hashtag"></i>
                            <span>ID: <span class="font-semibold text-gray-900">#{{ $customer->id }}</span></span>
                        </div>
                        @if($customer->requires_electronic_invoice && $customer->taxProfile)
                            <span class="hidden sm:inline text-gray-300">•</span>
                            <div class="flex items-center space-x-1.5">
                                <i class="fas fa-file-invoice"></i>
                                <span class="font-semibold text-emerald-600">Facturación Electrónica</span>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            <div class="flex flex-col sm:flex-row gap-3 sm:gap-3">
                @can('edit_customers')
                <a href="{{ route('customers.edit', $customer) }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center px-4 sm:px-5 py-3 sm:py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm sm:text-base font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 min-h-[44px]">
                    <i class="fas fa-edit mr-2"></i>
                    <span>Editar Cliente</span>
                </a>
                @endcan

                <a href="{{ route('customers.index') }}"
                   class="w-full sm:w-auto inline-flex items-center justify-center px-4 sm:px-5 py-3 sm:py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm sm:text-base font-semibold hover:bg-emerald-700 hover:border-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md min-h-[44px]">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 sm:gap-6">
        <!-- Contenido Principal -->
        <div class="lg:col-span-2 space-y-4 sm:space-y-6">
            <!-- Información Personal -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-3 mb-4 sm:mb-6">
                    <div class="p-2.5 rounded-xl bg-emerald-50 text-emerald-600">
                        <i class="fas fa-info-circle text-lg"></i>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900">Información Personal</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="space-y-4">
                            <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                ID del Cliente
                            </label>
                            <div class="flex items-center space-x-2 text-sm text-gray-900">
                                <i class="fas fa-hashtag text-gray-400"></i>
                                <span class="font-semibold">#{{ $customer->id }}</span>
                            </div>
                        </div>

                        @if($customer->email)
                            <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                Correo Electrónico
                            </label>
                            <div class="flex items-center space-x-2 text-sm">
                                <i class="fas fa-envelope text-gray-400"></i>
                                <a href="mailto:{{ $customer->email }}" class="text-emerald-600 hover:text-emerald-700 hover:underline">
                                        {{ $customer->email }}
                                    </a>
                            </div>
                        </div>
                        @endif

                        @if($customer->phone)
                            <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                Teléfono
                            </label>
                            <div class="flex items-center space-x-2 text-sm">
                                <i class="fas fa-phone text-gray-400"></i>
                                <a href="tel:{{ $customer->phone }}" class="text-emerald-600 hover:text-emerald-700 hover:underline">
                                        {{ $customer->phone }}
                                    </a>
                            </div>
                        </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                            <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                Fecha de Registro
                            </label>
                            <div class="flex items-center space-x-2 text-sm text-gray-900">
                                <i class="fas fa-calendar-plus text-gray-400"></i>
                                <span>{{ $customer->created_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>

                            <div>
                            <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                Última Actualización
                            </label>
                            <div class="flex items-center space-x-2 text-sm text-gray-900">
                                <i class="fas fa-calendar-edit text-gray-400"></i>
                                <span>{{ $customer->updated_at->format('d/m/Y H:i') }}</span>
                            </div>
                        </div>
                    </div>
                </div>

                @if($customer->address || $customer->city || $customer->state || $customer->zip_code)
                <div class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-100">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Dirección
                    </label>
                    <div class="flex items-start space-x-2 text-sm text-gray-900">
                        <i class="fas fa-map-marker-alt text-gray-400 mt-0.5"></i>
                        <div>
                                @if($customer->address)
                                    <div>{{ $customer->address }}</div>
                                @endif
                                @if($customer->city || $customer->state || $customer->zip_code)
                                <div class="text-gray-600 mt-0.5">
                                    {{ trim(implode(', ', array_filter([$customer->city, $customer->state, $customer->zip_code]))) }}
                                    </div>
                                @endif
                        </div>
                    </div>
                </div>
                @endif

                @if($customer->notes)
                <div class="mt-4 sm:mt-6 pt-4 sm:pt-6 border-t border-gray-100">
                    <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-2">
                        Notas Adicionales
                    </label>
                    <div class="flex items-start space-x-2 text-sm text-gray-700 leading-relaxed">
                        <i class="fas fa-sticky-note text-gray-400 mt-0.5"></i>
                        <p class="flex-1">{{ $customer->notes }}</p>
                    </div>
                </div>
                @endif
            </div>

            <!-- Perfil Fiscal -->
            @if($customer->requires_electronic_invoice && $customer->taxProfile)
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-3 mb-4 sm:mb-6">
                    <div class="p-2.5 rounded-xl bg-blue-50 text-blue-600">
                        <i class="fas fa-file-invoice text-lg"></i>
                    </div>
                    <div class="flex-1">
                        <h2 class="text-lg sm:text-xl font-bold text-gray-900">Perfil Fiscal</h2>
                        <p class="text-xs text-gray-500 mt-0.5">Información para facturación electrónica DIAN</p>
                    </div>
                    @if($customer->hasCompleteTaxProfileData())
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700">
                            <i class="fas fa-check-circle mr-1.5"></i>
                            Completo
                        </span>
                    @else
                        <span class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-amber-50 text-amber-700">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            Incompleto
                        </span>
                    @endif
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="space-y-4">
                        @if($customer->taxProfile->identificationDocument)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Tipo de Documento
                                </label>
                                <div class="flex items-center space-x-2 text-sm text-gray-900">
                                    <i class="fas fa-id-card text-gray-400"></i>
                                    <span class="font-semibold">{{ $customer->taxProfile->identificationDocument->code }}</span>
                                    <span class="text-gray-500">-</span>
                                    <span>{{ $customer->taxProfile->identificationDocument->name }}</span>
                                </div>
                            </div>
                        @endif

                        @if($customer->taxProfile->identification)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Número de Identificación
                                </label>
                                <div class="flex items-center space-x-2 text-sm text-gray-900">
                                    <i class="fas fa-hashtag text-gray-400"></i>
                                    <span class="font-mono font-semibold">{{ $customer->taxProfile->identification }}</span>
                                    @if($customer->taxProfile->dv)
                                        <span class="text-gray-500">-</span>
                                        <span class="font-mono">{{ $customer->taxProfile->dv }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($customer->taxProfile->company)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Razón Social
                                </label>
                                <div class="flex items-center space-x-2 text-sm text-gray-900">
                                    <i class="fas fa-building text-gray-400"></i>
                                    <span class="font-semibold">{{ $customer->taxProfile->company }}</span>
                                </div>
                            </div>
                        @endif

                        @if($customer->taxProfile->trade_name)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Nombre Comercial
                                </label>
                                <div class="flex items-center space-x-2 text-sm text-gray-900">
                                    <i class="fas fa-store text-gray-400"></i>
                                    <span>{{ $customer->taxProfile->trade_name }}</span>
                                </div>
                            </div>
                        @endif
                    </div>

                    <div class="space-y-4">
                        @if($customer->taxProfile->municipality)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Municipio
                                </label>
                                <div class="flex items-center space-x-2 text-sm text-gray-900">
                                    <i class="fas fa-map-marker-alt text-gray-400"></i>
                                    <span>{{ $customer->taxProfile->municipality->name }}</span>
                                    @if($customer->taxProfile->municipality->department)
                                        <span class="text-gray-500">,</span>
                                        <span class="text-gray-600">{{ $customer->taxProfile->municipality->department }}</span>
                                    @endif
                                </div>
                            </div>
                        @endif

                        @if($customer->taxProfile->legalOrganization)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Tipo de Organización
                                </label>
                                <div class="flex items-center space-x-2 text-sm text-gray-900">
                                    <i class="fas fa-sitemap text-gray-400"></i>
                                    <span>{{ $customer->taxProfile->legalOrganization->name }}</span>
                                </div>
                            </div>
                        @endif

                        @if($customer->taxProfile->tribute)
                            <div>
                                <label class="block text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1.5">
                                    Régimen Tributario
                                </label>
                                <div class="flex items-center space-x-2 text-sm text-gray-900">
                                    <i class="fas fa-receipt text-gray-400"></i>
                                    <span class="font-semibold">{{ $customer->taxProfile->tribute->code }}</span>
                                    <span class="text-gray-500">-</span>
                                    <span>{{ $customer->taxProfile->tribute->name }}</span>
                                </div>
                            </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif
        </div>

        <!-- Panel Lateral -->
        <div class="space-y-4 sm:space-y-6">
            <!-- Estadísticas -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-3 mb-4 sm:mb-6">
                    <div class="p-2 rounded-xl bg-violet-50 text-violet-600">
                        <i class="fas fa-chart-bar text-lg"></i>
                    </div>
                    <h2 class="text-lg sm:text-xl font-bold text-gray-900">Estadísticas</h2>
                </div>

                <div class="space-y-4">
                    <!-- Cliente Desde -->
                    <div class="p-4 bg-violet-50 rounded-xl border border-violet-100">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <div class="p-2 rounded-lg bg-violet-600 text-white">
                                    <i class="fas fa-calendar text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">Cliente Desde</span>
                            </div>
                        </div>
                        <div class="text-lg sm:text-xl font-bold text-violet-600">
                            {{ $customer->created_at->format('M Y') }}
                        </div>
                    </div>

                    @if($customer->requires_electronic_invoice && $customer->taxProfile)
                    <!-- Estado Facturación Electrónica -->
                    <div class="p-4 bg-blue-50 rounded-xl border border-blue-100">
                        <div class="flex items-center justify-between mb-2">
                            <div class="flex items-center space-x-2">
                                <div class="p-2 rounded-lg bg-blue-600 text-white">
                                    <i class="fas fa-file-invoice text-sm"></i>
                                </div>
                                <span class="text-sm font-semibold text-gray-700">Facturación Electrónica</span>
                            </div>
                        </div>
                        <div class="text-sm font-semibold {{ $customer->hasCompleteTaxProfileData() ? 'text-emerald-600' : 'text-amber-600' }}">
                            @if($customer->hasCompleteTaxProfileData())
                                <i class="fas fa-check-circle mr-1"></i>
                                Perfil Completo
                            @else
                                <i class="fas fa-exclamation-circle mr-1"></i>
                                Perfil Incompleto
                            @endif
                        </div>
                    </div>
                    @endif
                </div>
            </div>

            <!-- Acciones Rápidas -->
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                    <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                        <div class="p-2 sm:p-2.5 rounded-xl bg-indigo-50 text-indigo-600">
                            <i class="fas fa-bolt text-base sm:text-lg"></i>
                        </div>
                        <h2 class="text-base sm:text-lg md:text-xl font-bold text-gray-900">Acciones Rápidas</h2>
                    </div>

                <div class="space-y-3">
                    @can('edit_customers')
                    <a href="{{ route('customers.edit', $customer) }}"
                       class="w-full inline-flex items-center justify-center px-4 sm:px-5 py-3 sm:py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm sm:text-base font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 min-h-[44px]">
                        <i class="fas fa-edit mr-2"></i>
                        Editar Cliente
                    </a>
                    @endcan
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
