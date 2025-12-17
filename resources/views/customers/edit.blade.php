@extends('layouts.app')

@section('title', 'Editar Cliente')
@section('header', 'Editar Cliente')

@section('content')
<div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <i class="fas fa-user-edit text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Editar Cliente</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Modifica la información de {{ $customer->name }}</p>
            </div>
        </div>
    </div>


    <form method="POST" action="{{ route('customers.update', $customer) }}" id="customer-form" 
          x-data="customerForm()" 
          x-init="init()"
          @submit.prevent="submitForm">
        @csrf
        @method('PUT')

        <!-- Información Personal -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-user text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Personal</h2>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <!-- Nombre completo -->
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Nombre completo <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-id-card text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="name"
                               name="name"
                               x-model="formData.name"
                               @blur="validateField('name')"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('name') border-red-300 focus:ring-red-500 @enderror"
                               :class="errors.name ? 'border-red-300 focus:ring-red-500' : ''"
                               placeholder="Ej: Juan Pérez García">
                    </div>
                    <p x-show="!errors.name" class="mt-1.5 text-xs text-gray-500 flex items-start">
                        <i class="fas fa-info-circle mr-1.5 mt-0.5 text-gray-400"></i>
                        <span>Nombre completo del cliente para identificación y facturación</span>
                    </p>
                    <p x-show="errors.name" x-text="errors.name" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak></p>
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 md:gap-6">
                    <!-- Email -->
                    <div>
                        <label for="email" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Correo electrónico
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-envelope text-gray-400 text-sm"></i>
                            </div>
                            <input type="email"
                                   id="email"
                                   name="email"
                                   x-model="formData.email"
                                   @blur="validateField('email')"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('email') border-red-300 focus:ring-red-500 @enderror"
                                   :class="errors.email ? 'border-red-300 focus:ring-red-500' : ''"
                                   placeholder="juan.perez@email.com">
                        </div>
                        <p x-show="!errors.email" class="mt-1.5 text-xs text-gray-500 flex items-start">
                            <i class="fas fa-info-circle mr-1.5 mt-0.5 text-gray-400"></i>
                            <span>Email para comunicaciones y envío de facturas electrónicas (opcional)</span>
                        </p>
                        <p x-show="errors.email" x-text="errors.email" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak></p>
                        @error('email')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="phone" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Teléfono
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="phone"
                                   name="phone"
                                   x-model="formData.phone"
                                   @input="formatPhone()"
                                   @blur="validateField('phone')"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('phone') border-red-300 focus:ring-red-500 @enderror"
                                   :class="errors.phone ? 'border-red-300 focus:ring-red-500' : ''"
                                   placeholder="+1 (555) 123-4567">
                        </div>
                        <p x-show="!errors.phone" class="mt-1.5 text-xs text-gray-500 flex items-start">
                            <i class="fas fa-info-circle mr-1.5 mt-0.5 text-gray-400"></i>
                            <span>Número de contacto principal. Puede incluir código de país (opcional)</span>
                        </p>
                        <p x-show="errors.phone" x-text="errors.phone" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak></p>
                        @error('phone')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Información de Dirección -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-emerald-50 text-emerald-600">
                    <i class="fas fa-map-marker-alt text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información de Dirección</h2>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <!-- Dirección -->
                <div>
                    <label for="address" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Dirección
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-home text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="address"
                               name="address"
                               value="{{ old('address', $customer->address) }}"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('address') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="Calle, número, colonia">
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500 flex items-start">
                        <i class="fas fa-info-circle mr-1.5 mt-0.5 text-gray-400"></i>
                        <span>Dirección completa para envíos y facturación (opcional)</span>
                    </p>
                    @error('address')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-3 gap-4 sm:gap-5 md:gap-6">
                    <!-- Ciudad -->
                    <div>
                        <label for="city" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Ciudad
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-city text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="city"
                                   name="city"
                                   value="{{ old('city', $customer->city) }}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('city') border-red-300 focus:ring-red-500 @enderror"
                                   placeholder="Ciudad">
                        </div>
                        @error('city')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Estado -->
                    <div>
                        <label for="state" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Estado
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-flag text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="state"
                                   name="state"
                                   value="{{ old('state', $customer->state) }}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('state') border-red-300 focus:ring-red-500 @enderror"
                                   placeholder="Estado">
                        </div>
                        @error('state')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Código postal -->
                    <div>
                        <label for="zip_code" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Código postal
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-mail-bulk text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="zip_code"
                                   name="zip_code"
                                   value="{{ old('zip_code', $customer->zip_code) }}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('zip_code') border-red-300 focus:ring-red-500 @enderror"
                                   placeholder="12345">
                        </div>
                        @error('zip_code')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>
            </div>
        </div>

        <!-- Información Adicional -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-violet-50 text-violet-600">
                    <i class="fas fa-sticky-note text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información Adicional</h2>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <!-- Notas -->
                <div>
                    <label for="notes" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Notas adicionales
                    </label>
                    <div class="relative">
                        <textarea id="notes"
                                  name="notes"
                                  rows="3"
                                  class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all resize-none @error('notes') border-red-300 focus:ring-red-500 @enderror"
                                  placeholder="Información adicional sobre el cliente...">{{ old('notes', $customer->notes) }}</textarea>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">
                        Información relevante sobre preferencias o historial del cliente
                    </p>
                    @error('notes')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Estado -->
                <div class="bg-gray-50 rounded-xl p-4 sm:p-5 border border-gray-200">
                    <label class="inline-flex items-center cursor-pointer">
                        <input type="checkbox"
                               name="is_active"
                               value="1"
                               {{ old('is_active', $customer->is_active) ? 'checked' : '' }}
                               class="h-4 w-4 sm:h-5 sm:w-5 text-emerald-600 border-gray-300 rounded focus:ring-emerald-500 focus:ring-2 transition-colors">
                        <span class="ml-3 text-sm font-medium text-gray-700">Cliente activo</span>
                    </label>
                    <p class="mt-2 text-xs text-gray-500 flex items-start">
                        <i class="fas fa-info-circle mr-1.5 mt-0.5 text-gray-400"></i>
                        <span>Los clientes inactivos no aparecerán en los formularios de reservas y facturación</span>
                    </p>
                </div>
            </div>
        </div>

        <!-- Facturación Electrónica DIAN -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center justify-between mb-4">
                <div class="flex items-center space-x-3">
                    <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                        <i class="fas fa-file-invoice text-sm"></i>
                    </div>
                    <div>
                        <h2 class="text-base sm:text-lg font-semibold text-gray-900">
                            Facturación Electrónica DIAN
                        </h2>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Activa esta opción si el cliente requiere facturación electrónica
                        </p>
                    </div>
                </div>
                <label class="relative inline-flex items-center cursor-pointer">
                    <input type="checkbox"
                           name="requires_electronic_invoice"
                           value="1"
                           x-model="requiresElectronicInvoice"
                           class="sr-only peer">
                    <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600"></div>
                </label>
            </div>

            <!-- Campos DIAN (mostrar/ocultar dinámicamente) -->
            <div x-show="requiresElectronicInvoice"
                 x-transition:enter="transition ease-out duration-200"
                 x-transition:enter-start="opacity-0 transform scale-95"
                 x-transition:enter-end="opacity-100 transform scale-100"
                 class="mt-6 space-y-5 border-t border-gray-200 pt-6" x-cloak>
                
                <!-- Mensaje informativo -->
                <div class="bg-blue-50 border border-blue-200 rounded-lg p-4">
                    <div class="flex items-start">
                        <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-3"></i>
                        <div class="text-sm text-blue-800">
                            <p class="font-semibold mb-1">Campos Obligatorios para Facturación Electrónica</p>
                            <p class="text-xs">Complete todos los campos marcados con <span class="text-red-500 font-bold">*</span> para poder generar facturas electrónicas válidas según la normativa DIAN. Los campos opcionales pueden completarse más tarde.</p>
                        </div>
                    </div>
                </div>

                <!-- Tipo de Documento -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Tipo de Documento <span class="text-red-500">*</span>
                        </label>
                        <select name="identification_document_id"
                                x-model="identificationDocumentId"
                                @change="updateRequiredFields()"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm"
                                :required="requiresElectronicInvoice"
                                :class="errors.identification_document_id ? 'border-red-300 focus:ring-red-500' : ''">
                            <option value="">Seleccione...</option>
                            @foreach($identificationDocuments as $doc)
                                <option value="{{ $doc->id }}"
                                        data-code="{{ $doc->code }}"
                                        data-requires-dv="{{ $doc->requires_dv ? 'true' : 'false' }}"
                                        {{ (string)old('identification_document_id', $customer->taxProfile->identification_document_id ?? '') === (string)$doc->id ? 'selected' : '' }}>
                                    {{ $doc->name }}@if($doc->code) ({{ $doc->code }})@endif
                                </option>
                            @endforeach
                        </select>
                        <p x-show="errors.identification_document_id" x-text="errors.identification_document_id" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak></p>
                        @error('identification_document_id')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Identificación -->
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Número de Identificación <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="identification"
                               x-model="identification"
                               @input="calculateDV()"
                               @blur="validateField('identification')"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm"
                               :required="requiresElectronicInvoice"
                               :class="errors.identification ? 'border-red-300 focus:ring-red-500' : ''">
                        <p x-show="errors.identification" x-text="errors.identification" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak></p>
                        @error('identification')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Dígito Verificador (solo si el documento lo requiere) -->
                <div x-show="requiresDV" class="grid grid-cols-1 sm:grid-cols-2 gap-5" x-cloak>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Dígito Verificador (DV) <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="dv"
                               x-model="dv"
                               maxlength="1"
                               :required="requiresElectronicInvoice && requiresDV"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <p class="mt-1 text-xs text-gray-500">
                            Se calcula automáticamente para NIT
                        </p>
                        @error('dv')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Razón Social / Nombre Comercial (solo para personas jurídicas) -->
                <div x-show="isJuridicalPerson" class="grid grid-cols-1 sm:grid-cols-2 gap-5" x-cloak>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Razón Social / Empresa <span class="text-red-500">*</span>
                        </label>
                        <input type="text"
                               name="company"
                               x-model="formData.company"
                               @blur="validateField('company')"
                               :required="requiresElectronicInvoice && isJuridicalPerson"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm"
                               :class="errors.company ? 'border-red-300 focus:ring-red-500' : ''">
                        <p x-show="errors.company" x-text="errors.company" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak></p>
                        @error('company')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Nombre Comercial
                        </label>
                        <input type="text"
                               name="trade_name"
                               value="{{ old('trade_name', $customer->taxProfile->trade_name ?? '') }}"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        @error('trade_name')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Nombres (solo para personas naturales) -->
                <div x-show="!isJuridicalPerson" class="grid grid-cols-1 sm:grid-cols-2 gap-5" x-cloak>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Nombres
                        </label>
                        <input type="text"
                               name="names"
                               value="{{ old('names', $customer->taxProfile->names ?? '') }}"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm"
                               placeholder="Nombres completos de la persona natural">
                        <p class="mt-1 text-xs text-gray-500">
                            Solo aplica para personas naturales
                        </p>
                        @error('names')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <!-- Tipo de Organización Legal (opcional) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                        Tipo de Organización Legal
                    </label>
                    <select name="legal_organization_id"
                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <option value="">Seleccione...</option>
                        @foreach($legalOrganizations as $org)
                            <option value="{{ $org->id }}" {{ (string)old('legal_organization_id', $customer->taxProfile->legal_organization_id ?? '') === (string)$org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                    @error('legal_organization_id')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Municipio -->
                <div>
                    <label for="municipality_id" class="block text-xs font-semibold text-gray-700 mb-2">
                        Municipio <span class="text-red-500">*</span>
                    </label>
                    @if($municipalities->isEmpty())
                        <div class="bg-yellow-50 border border-yellow-200 rounded-lg p-4">
                            <div class="flex items-start">
                                <i class="fas fa-exclamation-triangle text-yellow-600 mt-0.5 mr-3"></i>
                                <div class="text-sm text-yellow-800">
                                    <p class="font-semibold mb-1">No hay municipios disponibles</p>
                                    <p class="text-xs">Ejecuta el comando <code class="bg-yellow-100 px-1 rounded">php artisan factus:sync-municipalities</code> para sincronizar los municipios desde Factus.</p>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="municipality_id" value="{{ old('municipality_id', $customer->taxProfile->municipality_id ?? '') }}">
                    @else
                        <select name="municipality_id"
                                id="municipality_id"
                                x-model="formData.municipality_id"
                                @change="validateField('municipality_id')"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm focus:ring-2 focus:ring-emerald-500 focus:border-transparent"
                                :required="requiresElectronicInvoice"
                                :class="errors.municipality_id ? 'border-red-300 focus:ring-red-500' : ''">
                            <option value="">Seleccione un municipio...</option>
                            @php
                                $currentDepartment = null;
                                $selectedMunicipalityId = old('municipality_id', $customer->taxProfile->municipality_id ?? null);
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
                        <p x-show="!errors.municipality_id" class="mt-1 text-xs text-gray-500">
                            Seleccione el municipio según el departamento
                        </p>
                        <p x-show="errors.municipality_id" x-text="errors.municipality_id" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak></p>
                        @error('municipality_id')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    @endif
                </div>

                <!-- Régimen Tributario (opcional) -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                        Régimen Tributario
                    </label>
                    <select name="tribute_id"
                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <option value="">Seleccione...</option>
                        @foreach($tributes as $tribute)
                            <option value="{{ $tribute->id }}" {{ (string)old('tribute_id', $customer->taxProfile->tribute_id ?? '') === (string)$tribute->id ? 'selected' : '' }}>
                                {{ $tribute->name }} ({{ $tribute->code }})
                            </option>
                        @endforeach
                    </select>
                    @error('tribute_id')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Información de Contacto Adicional -->
                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5">
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Dirección Fiscal
                        </label>
                        <input type="text"
                               name="tax_address"
                               value="{{ old('tax_address', $customer->taxProfile->tax_address ?? '') }}"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm"
                               placeholder="Dirección para facturación">
                        <p class="mt-1 text-xs text-gray-500">
                            Si no se especifica, se usará la dirección principal del cliente
                        </p>
                        @error('tax_address')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Email Fiscal
                        </label>
                        <input type="email"
                               name="tax_email"
                               value="{{ old('tax_email', $customer->taxProfile->tax_email ?? '') }}"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm"
                               placeholder="email@ejemplo.com">
                        <p class="mt-1 text-xs text-gray-500">
                            Email para envío de facturas electrónicas. Si no se especifica, se usará el email principal.
                        </p>
                        @error('tax_email')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                </div>

                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                        Teléfono Fiscal
                    </label>
                    <input type="text"
                           name="tax_phone"
                           value="{{ old('tax_phone', $customer->taxProfile->tax_phone ?? '') }}"
                           class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm"
                           placeholder="Número de teléfono">
                    <p class="mt-1 text-xs text-gray-500">
                        Si no se especifica, se usará el teléfono principal del cliente
                    </p>
                    @error('tax_phone')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Información del Sistema (Solo Lectura) -->
        <div class="bg-gray-50 rounded-xl border border-gray-200 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-gray-100 text-gray-600">
                    <i class="fas fa-info-circle text-sm"></i>
                </div>
                <div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información del Sistema</h2>
                    <p class="text-xs text-gray-500 mt-0.5">Esta información es de solo lectura y no puede modificarse</p>
                </div>
            </div>

            <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 md:gap-6">
                <div class="bg-white rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm">
                            <i class="fas fa-hashtag text-sm"></i>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">ID del Cliente</div>
                            <div class="text-base sm:text-lg font-bold text-gray-900">#{{ $customer->id }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-gray-50 rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-xl bg-emerald-50 text-emerald-600 flex items-center justify-center shadow-sm">
                            <i class="fas fa-history text-sm"></i>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Actividad Reciente</div>
                            <div class="text-xs text-gray-500 italic">Próximamente: Reservas</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-xl bg-violet-50 text-violet-600 flex items-center justify-center shadow-sm">
                            <i class="fas fa-calendar-plus text-sm"></i>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Fecha de Registro</div>
                            <div class="text-sm font-semibold text-gray-900">{{ $customer->created_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>

                <div class="bg-white rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-xl bg-gray-50 text-gray-600 flex items-center justify-center shadow-sm">
                            <i class="fas fa-calendar-edit text-sm"></i>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Última Actualización</div>
                            <div class="text-sm font-semibold text-gray-900">{{ $customer->updated_at->format('d/m/Y H:i') }}</div>
                        </div>
                    </div>
                </div>

                @if($customer->requires_electronic_invoice && $customer->taxProfile)
                <div class="bg-white rounded-xl p-4 border border-gray-200">
                    <div class="flex items-center space-x-3">
                        <div class="h-10 w-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center shadow-sm">
                            <i class="fas fa-file-invoice text-sm"></i>
                        </div>
                        <div>
                            <div class="text-xs font-semibold text-gray-500 uppercase tracking-wider mb-1">Facturación Electrónica</div>
                            <div class="text-sm font-semibold {{ $customer->hasCompleteTaxProfileData() ? 'text-emerald-600' : 'text-amber-600' }}">
                                {{ $customer->hasCompleteTaxProfileData() ? 'Perfil Completo' : 'Perfil Incompleto' }}
                            </div>
                        </div>
                    </div>
                </div>
                @endif
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="flex flex-col sm:flex-row items-stretch sm:items-center justify-between gap-3 sm:gap-4 pt-4 border-t border-gray-100">
            <div class="text-xs sm:text-sm text-gray-500 flex items-center">
                <i class="fas fa-info-circle mr-1.5"></i>
                Los campos marcados con <span class="text-red-500 ml-1">*</span> son obligatorios
            </div>

            <div class="flex flex-col sm:flex-row items-stretch sm:items-center gap-3">
                <a href="{{ route('customers.index') }}"
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-gray-500">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>

                <button type="submit"
                        class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-emerald-600 bg-emerald-600 text-white text-sm font-semibold hover:bg-emerald-700 hover:border-emerald-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-emerald-500 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed"
                        :disabled="loading"
                        x-bind:class="loading ? 'opacity-50 cursor-not-allowed' : ''">
                    <template x-if="!loading">
                        <span>
                            <i class="fas fa-save mr-2"></i>
                            Actualizar Cliente
                        </span>
                    </template>
                    <template x-if="loading">
                        <span>
                            <i class="fas fa-spinner fa-spin mr-2"></i>
                            Procesando...
                        </span>
                    </template>
                </button>
            </div>
        </div>
    </form>
</div>

@push('scripts')
<script>
function customerForm() {
    return {
        loading: false,
        requiresElectronicInvoice: @json((bool) old('requires_electronic_invoice', $customer->requires_electronic_invoice)),
        identificationDocumentId: @json(old('identification_document_id', $customer->taxProfile->identification_document_id ?? null)),
        identification: @json(old('identification', $customer->taxProfile->identification ?? '')),
        dv: @json(old('dv', $customer->taxProfile->dv ?? '')),
        requiresDV: false,
        isJuridicalPerson: false,
        
        formData: {
            name: @json(old('name', $customer->name)),
            email: @json(old('email', $customer->email)),
            phone: @json(old('phone', $customer->phone)),
            company: @json(old('company', $customer->taxProfile->company ?? '')),
            municipality_id: @json(old('municipality_id', $customer->taxProfile->municipality_id ?? ''))
        },
        
        errors: {},

        init() {
            this.updateRequiredFields();
        },

        updateRequiredFields() {
            const select = document.querySelector('select[name="identification_document_id"]');
            if (select && this.identificationDocumentId) {
                select.value = this.identificationDocumentId;
            }

            const selectedOption = select?.options[select?.selectedIndex];

            if (selectedOption) {
                this.requiresDV = selectedOption.dataset.requiresDv === 'true';
                this.isJuridicalPerson = selectedOption.dataset.code === 'NIT';

                if (this.requiresDV && this.isJuridicalPerson && this.identification) {
                    this.calculateDV();
                }
            }
        },

        calculateDV() {
            if (this.requiresDV && this.identification && this.identification.length >= 5) {
                // Logic can be added here
            }
        },

        formatPhone() {
            let value = this.formData.phone.replace(/\D/g, '');
            if (value.length > 0 && !this.formData.phone.startsWith('+')) {
                this.formData.phone = '+' + value;
            }
        },
        
        validateField(field) {
            this.errors[field] = null;
            
            if (field === 'name') {
                if (!this.formData.name || this.formData.name.trim() === '') {
                    this.errors.name = 'El nombre es obligatorio.';
                }
            }
            
            if (field === 'email') {
                if (this.formData.email && !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(this.formData.email)) {
                    this.errors.email = 'Ingrese un correo electrónico válido.';
                }
            }

            if (field === 'phone') {
                if (this.formData.phone && !/^[\+]?[1-9][\d\s\-\(\)]{7,15}$/.test(this.formData.phone)) {
                    this.errors.phone = 'Ingrese un número de teléfono válido.';
                }
            }
            
            if (this.requiresElectronicInvoice) {
                if (field === 'identification' && !this.identification) {
                    this.errors.identification = 'La identificación es obligatoria para facturación electrónica.';
                }
                if (field === 'identification_document_id' && !this.identificationDocumentId) {
                    this.errors.identification_document_id = 'El tipo de documento es obligatorio.';
                }
                if (field === 'company' && this.isJuridicalPerson && !this.formData.company) {
                    this.errors.company = 'La razón social es obligatoria para NIT.';
                }
                if (field === 'municipality_id' && !this.formData.municipality_id) {
                    this.errors.municipality_id = 'El municipio es obligatorio.';
                }
            }
        },
        
        submitForm() {
            this.errors = {};
            
            // Validate all necessary fields
            this.validateField('name');
            this.validateField('email');
            this.validateField('phone');
            
            if (this.requiresElectronicInvoice) {
                this.validateField('identification');
                this.validateField('identification_document_id');
                this.validateField('municipality_id');
                if (this.isJuridicalPerson) {
                    this.validateField('company');
                }
            }
            
            const hasErrors = Object.values(this.errors).some(error => error !== null);
            
            if (hasErrors) {
                // Scroll to first error
                const firstError = Object.keys(this.errors).find(key => this.errors[key] !== null);
                const element = document.getElementsByName(firstError)[0] || document.getElementById(firstError);
                if (element) {
                    element.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    element.focus();
                }
                return;
            }
            
            this.loading = true;
            this.$el.submit();
        }
    }
}
</script>
@endpush
@endsection
