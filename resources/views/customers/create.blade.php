@extends('layouts.app')

@section('title', 'Nuevo Cliente')
@section('header', 'Nuevo Cliente')

@section('content')
<div class="max-w-4xl mx-auto space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-emerald-50 text-emerald-600">
                <i class="fas fa-user-plus text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Nuevo Cliente</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Registra un nuevo cliente en el sistema con toda su información</p>
            </div>
        </div>
    </div>

    <form method="POST" action="{{ route('customers.store') }}" id="customer-form"
          x-data="customerForm()"
          @submit.prevent="submitForm">
        @csrf

        <!-- Información del Cliente -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 sm:space-x-3 mb-4 sm:mb-6">
                <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-user text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información del Cliente</h2>
            </div>

            <div class="space-y-5 sm:space-y-6">
                <!-- Nombre completo -->
                <div>
                    <label for="name" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Nombre completo <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                            <i class="fas fa-user text-gray-400 text-sm"></i>
                        </div>
                        <input type="text"
                               id="name"
                               name="name"
                               x-model="formData.name"
                               oninput="this.value = this.value.toUpperCase()"
                               style="text-transform: uppercase !important;"
                               class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all uppercase @error('name') border-red-300 focus:ring-red-500 @enderror"
                               placeholder="EJ: JUAN PÉREZ GARCÍA">
                    </div>
                    @error('name')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <div class="grid grid-cols-1 md:grid-cols-2 gap-4 sm:gap-5 md:gap-6">
                    <!-- Identificación -->
                    <div>
                        <label for="identification" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Número de identificación <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-id-card text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="identification"
                                   name="identification"
                                   x-model="formData.identification"
                                   @input="formData.identification = formData.identification.replace(/\D/g, ''); validateIdentification()"
                                   @blur="checkIdentification()"
                                   maxlength="10"
                                   pattern="\d{6,10}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('identification') border-red-300 focus:ring-red-500 @else border-gray-300 @enderror"
                                   :class="errors.identification || (identificationMessage && identificationExists) ? 'border-red-300 focus:ring-red-500' : ''"
                                   placeholder="Ej: 12345678">
                        </div>
                        <p x-show="errors.identification" x-text="errors.identification" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak>
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                        </p>
                        <p x-show="identificationMessage && !errors.identification" 
                           :class="identificationExists ? 'text-red-600' : 'text-emerald-600'"
                           class="mt-1.5 text-xs flex items-center" x-cloak>
                            <i :class="identificationExists ? 'fas fa-exclamation-circle' : 'fas fa-check-circle'" class="mr-1.5"></i>
                            <span x-text="identificationMessage"></span>
                        </p>
                        @error('identification')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>

                    <!-- Teléfono -->
                    <div>
                        <label for="phone" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Teléfono (opcional)
                        </label>
                        <div class="relative">
                            <div class="absolute inset-y-0 left-0 pl-3 sm:pl-4 flex items-center pointer-events-none">
                                <i class="fas fa-phone text-gray-400 text-sm"></i>
                            </div>
                            <input type="text"
                                   id="phone"
                                   name="phone"
                                   x-model="formData.phone"
                                   @input="formData.phone = formData.phone.replace(/\D/g, ''); validatePhone()"
                                   maxlength="10"
                                   pattern="\d{10}"
                                   class="block w-full pl-10 sm:pl-11 pr-3 sm:pr-4 py-2.5 border rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-emerald-500 focus:border-transparent transition-all @error('phone') border-red-300 focus:ring-red-500 @else border-gray-300 @enderror"
                                   :class="errors.phone ? 'border-red-300 focus:ring-red-500' : ''"
                                   placeholder="Ej: 3001234567">
                        </div>
                        <p x-show="errors.phone" x-text="errors.phone" class="mt-1.5 text-xs text-red-600 flex items-center" x-cloak>
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                        </p>
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
                                        {{ (string)old('identification_document_id') === (string)$doc->id ? 'selected' : '' }}>
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
                               readonly
                               :required="requiresElectronicInvoice && requiresDV"
                               class="block w-full px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-lg text-sm text-gray-600 cursor-not-allowed font-bold"
                               placeholder="Automático">
                        <p class="mt-1 text-xs text-blue-600">
                            <i class="fas fa-magic mr-1"></i> Calculado automáticamente por el sistema
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
                               value="{{ old('trade_name') }}"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        @error('trade_name')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
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
                                    <p class="text-xs">Por favor, contacte al administrador del sistema para configurar los municipios necesarios para la facturación electrónica.</p>
                                </div>
                            </div>
                        </div>
                        <input type="hidden" name="municipality_id" value="">
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
                                        {{ old('municipality_id') == $municipality->factus_id ? 'selected' : '' }}>
                                    {{ $municipality->department }} - {{ $municipality->name }}
                                </option>
                                @if($loop->last)
                                    </optgroup>
                                @endif
                            @endforeach
                        </select>
                        <p x-show="!errors.municipality_id" class="mt-1 text-xs text-gray-500">
                            Búsqueda rápida por nombre o departamento
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

                <!-- Tipo de Organización Legal -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                        Tipo de Organización Legal
                    </label>
                    <select name="legal_organization_id"
                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <option value="">Seleccione...</option>
                        @foreach($legalOrganizations as $org)
                            <option value="{{ $org->id }}" {{ (string)old('legal_organization_id') === (string)$org->id ? 'selected' : '' }}>
                                {{ $org->name }}
                            </option>
                        @endforeach
                    </select>
                </div>

                <!-- Régimen Tributario -->
                <div>
                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                        Régimen Tributario
                    </label>
                    <select name="tribute_id"
                            class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm">
                        <option value="">Seleccione...</option>
                        @foreach($tributes as $tribute)
                            <option value="{{ $tribute->id }}" {{ (string)old('tribute_id') === (string)$tribute->id ? 'selected' : '' }}>
                                {{ $tribute->name }} ({{ $tribute->code }})
                            </option>
                        @endforeach
                    </select>
                </div>
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
                        :disabled="loading">
                    <template x-if="!loading">
                        <span>
                            <i class="fas fa-user-plus mr-2"></i>
                            Crear Cliente
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

@push('styles')
<link href="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/css/tom-select.css" rel="stylesheet">
<style>
    .ts-control { border-radius: 0.75rem !important; padding: 0.625rem 0.75rem !important; }
    .ts-dropdown { border-radius: 0.75rem !important; margin-top: 0.5rem !important; }
</style>
@endpush

@push('scripts')
<script src="https://cdn.jsdelivr.net/npm/tom-select@2.3.1/dist/js/tom-select.complete.min.js"></script>
<script>
function customerForm() {
    return {
        loading: false,
        requiresElectronicInvoice: @json((bool) old('requires_electronic_invoice', false)),
        identificationDocumentId: @json(old('identification_document_id')),
        dv: @json(old('dv')),
        requiresDV: false,
        isJuridicalPerson: false,
        identificationMessage: '',
        identificationExists: false,
        municipalitySelect: null,
        errors: {},

        formData: {
            name: @json(old('name', '')),
            identification: @json(old('identification', '')),
            phone: @json(old('phone', '')),
            email: @json(old('email', '')),
            address: @json(old('address', '')),
            company: @json(old('company', '')),
            municipality_id: @json(old('municipality_id', ''))
        },

        errors: {},

        init() {
            this.updateRequiredFields();
            
            this.$nextTick(() => {
                this.municipalitySelect = new TomSelect('#municipality_id', {
                    create: false,
                    maxOptions: 1200,
                    placeholder: 'Buscar municipio...',
                    render: {
                        optgroup_header: function(data, escape) {
                            return '<div class="optgroup-header font-bold text-gray-900 bg-gray-50 px-2 py-1">' + escape(data.label) + '</div>';
                        }
                    }
                });
                
                // If there's an old value, set it in TomSelect
                if (this.formData.municipality_id) {
                    this.municipalitySelect.setValue(this.formData.municipality_id);
                }
                
                this.municipalitySelect.on('change', (value) => {
                    this.formData.municipality_id = value;
                    this.validateField('municipality_id');
                });
            });

            this.$watch('formData.identification', (value) => {
                if (this.isJuridicalPerson) {
                    this.calculateDV(value);
                }
            });
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
                
                if (this.isJuridicalPerson) {
                    this.calculateDV(this.formData.identification);
                } else {
                    this.dv = '';
                }
            }
        },

        calculateDV(nit) {
            if (!nit || !this.isJuridicalPerson) {
                this.dv = '';
                return;
            }

            // Algorithm for DIAN Verification Digit
            const weights = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
            let sum = 0;
            const nitStr = nit.toString().replace(/\D/g, '');
            
            for (let i = 0; i < nitStr.length; i++) {
                sum += parseInt(nitStr.charAt(nitStr.length - 1 - i)) * weights[i];
            }
            
            const remainder = sum % 11;
            this.dv = remainder < 2 ? remainder : 11 - remainder;
        },

        validateIdentification() {
            this.errors.identification = null;
            this.identificationMessage = '';
            this.identificationExists = false;

            const identification = this.formData.identification?.trim() || '';
            const digitCount = identification.replace(/\D/g, '').length;

            if (identification && digitCount < 6) {
                this.errors.identification = 'El número de documento debe tener mínimo 6 dígitos.';
                return false;
            }

            if (identification && digitCount > 10) {
                this.errors.identification = 'El número de documento debe tener máximo 10 dígitos.';
                return false;
            }

            // Only allow digits
            if (identification && !/^\d+$/.test(identification)) {
                this.errors.identification = 'El número de documento solo puede contener dígitos.';
                return false;
            }

            return true;
        },

        validatePhone() {
            this.errors.phone = null;

            const phone = this.formData.phone?.trim() || '';
            
            // If phone is empty, it's valid (optional field)
            if (!phone) {
                return true;
            }

            const digitCount = phone.replace(/\D/g, '').length;

            if (digitCount !== 10) {
                this.errors.phone = 'El número de teléfono debe tener exactamente 10 dígitos.';
                return false;
            }

            // Only allow digits
            if (!/^\d+$/.test(phone)) {
                this.errors.phone = 'El número de teléfono solo puede contener dígitos.';
                return false;
            }

            return true;
        },

        async checkIdentification() {
            if (!this.validateIdentification()) return;
            if (!this.formData.identification || this.formData.identification.length < 6) return;
            
            this.identificationMessage = 'Verificando...';
            this.identificationExists = false;
            
            try {
                const response = await fetch(`{{ route('api.customers.check-identification') }}?identification=${this.formData.identification}`);
                if (!response.ok) throw new Error('Error en la validación');
                
                const data = await response.json();
                
                if (data.exists) {
                    this.identificationExists = true;
                    this.identificationMessage = `Este cliente ya está registrado como: ${data.name}`;
                } else {
                    this.identificationExists = false;
                    this.identificationMessage = 'Documento disponible';
                }
            } catch (error) {
                console.error('Error checking identification:', error);
                this.identificationMessage = 'No se pudo verificar el documento';
                this.identificationExists = false;
            }
        },

        validateField(field) {
            this.errors[field] = null;

            if (field === 'name' && !this.formData.name) {
                this.errors.name = 'El nombre es obligatorio.';
            }

            if (this.requiresElectronicInvoice) {
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
            this.validateField('name');
            this.validateIdentification();
            this.validatePhone();

            if (this.requiresElectronicInvoice) {
                this.validateField('identification_document_id');
                this.validateField('municipality_id');
                if (this.isJuridicalPerson) {
                    this.validateField('company');
                }
            }

            const hasErrors = Object.values(this.errors).some(error => error !== null);

            if (hasErrors || this.identificationExists) {
                if (this.identificationExists) {
                    Swal.fire({
                        icon: 'error',
                        title: 'Documento Duplicado',
                        text: this.identificationMessage,
                        confirmButtonColor: '#059669',
                    });
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
