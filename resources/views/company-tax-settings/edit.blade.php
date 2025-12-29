@extends('layouts.app')

@section('title', 'Configuración Fiscal de la Empresa')
@section('header', 'Configuración Fiscal de la Empresa')

@section('content')
<div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                <i class="fas fa-building text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Configuración Fiscal de la Empresa</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Datos fiscales necesarios para emitir facturas electrónicas DIAN</p>
            </div>
        </div>
    </div>

    @if(session('success'))
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-center">
                <i class="fas fa-check-circle text-green-600 mr-3"></i>
                <p class="text-sm text-green-800">{{ session('success') }}</p>
            </div>
        </div>
    @endif

    <!-- Estado de Configuración -->
    @if($company && $company->isConfigured())
        <div class="bg-green-50 border border-green-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-check-circle text-green-600 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-semibold text-green-800 mb-1">
                        Configuración Completa
                    </h3>
                    <p class="text-xs text-green-700">
                        Todos los campos obligatorios están configurados. Puedes generar facturas electrónicas.
                    </p>
                </div>
            </div>
        </div>
    @elseif($company)
        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-exclamation-triangle text-yellow-600 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-semibold text-yellow-800 mb-2">
                        Configuración Incompleta
                    </h3>
                    <p class="text-xs text-yellow-700 mb-2">
                        Faltan los siguientes campos obligatorios para poder generar facturas electrónicas:
                    </p>
                    <ul class="list-disc list-inside text-xs text-yellow-700 space-y-1">
                        @foreach($company->getMissingFields() as $field)
                            <li>{{ $field }}</li>
                        @endforeach
                    </ul>
                </div>
            </div>
        </div>
    @else
        <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
            <div class="flex items-start">
                <div class="flex-shrink-0">
                    <i class="fas fa-info-circle text-blue-600 text-xl"></i>
                </div>
                <div class="ml-3 flex-1">
                    <h3 class="text-sm font-semibold text-blue-800 mb-1">
                        Configuración Inicial
                    </h3>
                    <p class="text-xs text-blue-700">
                        Completa los datos fiscales de tu empresa para habilitar la facturación electrónica DIAN.
                    </p>
                </div>
            </div>
        </div>
    @endif

    <form method="POST" action="{{ route('company-tax-settings.update') }}" id="company-tax-form"
          x-data="{ loading: false }"
          @submit="loading = true">
        @csrf
        @method('PUT')

        <!-- Datos Fiscales -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-file-invoice text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Datos Fiscales</h2>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <!-- Nombre de la Empresa -->
                <div>
                    <label for="company_name" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Razón Social / Nombre de la Empresa <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-building text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="company_name"
                               name="company_name"
                               value="{{ old('company_name', $company->company_name ?? '') }}"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('company_name') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="Ej: Mi Empresa S.A.S."
                               required>
                    </div>
                    @error('company_name')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- NIT y DV -->
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-5 sm:gap-6">
                    <div class="sm:col-span-2">
                        <label for="nit" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            NIT <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="nit"
                                   name="nit"
                                   value="{{ old('nit', $company->nit ?? '') }}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('nit') border-red-300 focus:ring-red-500 @enderror"
                                   placeholder="123456789"
                                   required>
                        </div>
                        @error('nit')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label for="dv" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Dígito Verificador (DV) <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               id="dv"
                               name="dv"
                               value="{{ old('dv', $company->dv ?? '') }}"
                               maxlength="1"
                               class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('dv') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="0"
                               required>
                        @error('dv')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Email -->
                <div>
                    <label for="email" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Email <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-envelope text-gray-400 text-sm"></i>
                        </div>
                        <input type="email"
                               id="email"
                               name="email"
                               value="{{ old('email', $company->email ?? '') }}"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('email') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="contacto@empresa.com"
                               required>
                    </div>
                    @error('email')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Municipio -->
                <div>
                    <label for="municipality_id" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Municipio <span class="text-red-500">*</span>
                    </label>
                    @if($municipalities->isEmpty())
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 mr-3"></i>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-semibold mb-1">No hay municipios disponibles</p>
                                    <p class="text-xs">Por favor, contacte al administrador del sistema para configurar los municipios necesarios para la facturación electrónica.</p>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="municipality_id" value="{{ old('municipality_id', $company->municipality_id ?? '') }}">
                    @else
                        <select name="municipality_id"
                                id="municipality_id"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent @error('municipality_id') border-red-300 focus:ring-red-500 @enderror"
                                required>
                            <option value="">Seleccione un municipio...</option>
                            @php
                                $currentDepartment = null;
                                $selectedMunicipalityId = old('municipality_id', $company->municipality_id ?? null);
                            @endphp
                            @foreach($municipalities as $municipality)
                                @if($currentDepartment !== $municipality->department)
                                    @if($currentDepartment !== null)
                                        </optgroup>
                                    @endif
                                    <optgroup label="{{ $municipality->department }}">
                                    @php
                                        $currentDepartment = $municipality->department;
                                    @endphp
                                @endif
                                <option value="{{ $municipality->factus_id }}"
                                        {{ $selectedMunicipalityId == $municipality->factus_id ? 'selected' : '' }}>
                                    {{ $municipality->name }}
                                </option>
                                @if($loop->last)
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        <p class="mt-1 text-xs text-gray-500">
                            Seleccione el municipio según el departamento
                        </p>
                    @endif
                    @error('municipality_id')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Actividad Económica (Opcional) -->
                <div>
                    <label for="economic_activity" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Actividad Económica (Código CIIU)
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-briefcase text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="economic_activity"
                               name="economic_activity"
                               value="{{ old('economic_activity', $company->economic_activity ?? '') }}"
                               maxlength="10"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('economic_activity') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="Ej: 1234">
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Código CIIU de la actividad económica principal (opcional)
                    </p>
                    @error('economic_activity')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Logo URL (Opcional) -->
                <div>
                    <label for="logo_url" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        URL del Logo
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-image text-gray-400 text-sm"></i>
                        </div>
                        <input type="url"
                               id="logo_url"
                               name="logo_url"
                               value="{{ old('logo_url', $company->logo_url ?? '') }}"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('logo_url') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="https://ejemplo.com/logo.png">
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">
                        URL del logo de la empresa para incluir en los PDFs de facturación (opcional)
                    </p>
                    @error('logo_url')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        @if($company && $company->hasFactusId())
            <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                    <div class="p-2 rounded-xl bg-gray-50 text-gray-600">
                        <i class="fas fa-info-circle text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información del Sistema</h2>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                    <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                        <div class="flex items-center space-x-3">
                            <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm">
                                <i class="fas fa-hashtag text-sm"></i>
                            </div>
                            <div>
                                <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ID Factus</div>
                                <div class="text-base sm:text-lg font-bold text-gray-900">#{{ $company->factus_company_id }}</div>
                            </div>
                        </div>
                    </div>

                    @if($company->municipality)
                        <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                            <div class="flex items-center space-x-3">
                                <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm">
                                    <i class="fas fa-map-marker-alt text-sm"></i>
                                </div>
                                <div>
                                    <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Municipio</div>
                                    <div class="text-sm font-semibold text-gray-900">{{ $company->municipality->name }}</div>
                                    <div class="text-xs text-gray-500">{{ $company->municipality->department }}</div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>
            </div>
        @endif

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-4 pt-4 border-t border-gray-100">
            <div class="text-xs sm:text-sm text-gray-500 flex items-center">
                <i class="fas fa-info-circle mr-1.5"></i>
                Los campos marcados con <span class="text-red-500 ml-1">*</span> son obligatorios
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <button type="submit"
                        class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 hover:border-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md"
                        :disabled="loading">
                    <template x-if="!loading">
                        <i class="fas fa-save mr-2"></i>
                    </template>
                    <template x-if="loading">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                    </template>
                    <span x-text="loading ? 'Guardando...' : 'Guardar Configuración'">Guardar Configuración</span>
                </button>
            </div>
        </div>
    </form>
</div>
@endsection
