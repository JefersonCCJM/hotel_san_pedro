{{-- New Customer Modal Component --}}
<div x-show="newCustomerModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="newCustomerModal = false; $wire.closeNewCustomerModal()"
            class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
            <!-- Header -->
            <div class="px-6 py-5 border-b border-gray-100 flex items-center justify-between bg-gray-50">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="text-xl font-bold text-gray-900">Crear Nuevo Cliente</h3>
                </div>
                <button type="button" @click="newCustomerModal = false; $wire.closeNewCustomerModal()"
                    class="text-gray-400 hover:text-gray-900 transition-colors">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Content -->
            <div class="px-6 py-6 max-h-[80vh] overflow-y-auto">
                <form wire:submit.prevent="createCustomer">
                    <!-- Nombre completo -->
                    <div class="mb-4">
                        <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                            Nombre completo <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <div
                                class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                <i class="fas fa-user text-sm"></i>
                            </div>
                            <input type="text" wire:model="newCustomer.name"
                                oninput="this.value = this.value.toUpperCase()"
                                class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 uppercase @error('newCustomer.name') border-red-500 @enderror"
                                placeholder="EJ: JUAN PÉREZ GARCÍA">
                        </div>
                        @error('newCustomer.name')
                            <span class="mt-1 text-xs font-medium text-red-600 block">
                                <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                            </span>
                        @enderror
                    </div>

                    <!-- Identificación y Teléfono -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Número de identificación <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-id-card text-sm"></i>
                                </div>
                                <input type="text" wire:model.live="newCustomer.identification"
                                    wire:blur="checkCustomerIdentification" maxlength="10"
                                    class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newCustomer.identification') border-red-500 @enderror"
                                    placeholder="Ej: 12345678">
                            </div>
                            @if ($customerIdentificationMessage)
                                <span
                                    class="mt-1 text-[10px] font-bold {{ $customerIdentificationExists ? 'text-red-500' : 'text-emerald-600' }} uppercase tracking-tighter block">
                                    <i
                                        class="fas {{ $customerIdentificationExists ? 'fa-exclamation-triangle' : 'fa-check-circle' }} mr-1"></i>
                                    {{ $customerIdentificationMessage }}
                                </span>
                            @endif
                            @error('newCustomer.identification')
                                <span class="mt-1 text-xs font-medium text-red-600 block">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Teléfono <span class="text-red-500">*</span>
                            </label>
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-phone text-sm"></i>
                                </div>
                                <input type="text" wire:model="newCustomer.phone" maxlength="20"
                                    class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newCustomer.phone') border-red-500 @enderror"
                                    placeholder="Ej: 3001234567">
                            </div>
                            @error('newCustomer.phone')
                                <span class="mt-1 text-xs font-medium text-red-600 block">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                </span>
                            @enderror
                        </div>
                    </div>

                    <!-- Email y Dirección -->
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4 mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Email
                            </label>
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-envelope text-sm"></i>
                                </div>
                                <input type="email" wire:model="newCustomer.email"
                                    class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newCustomer.email') border-red-500 @enderror"
                                    placeholder="ejemplo@correo.com">
                            </div>
                            @error('newCustomer.email')
                                <span class="mt-1 text-xs font-medium text-red-600 block">
                                    <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                </span>
                            @enderror
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                Dirección
                            </label>
                            <div class="relative">
                                <div
                                    class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400">
                                    <i class="fas fa-map-marker-alt text-sm"></i>
                                </div>
                                <input type="text" wire:model="newCustomer.address"
                                    class="block w-full pl-10 pr-4 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                    placeholder="Dirección del cliente">
                            </div>
                        </div>
                    </div>

                    <!-- Facturación Electrónica DIAN -->
                    <div class="border-t border-gray-200 pt-6 mt-6">
                        <div class="flex items-center justify-between mb-4">
                            <div class="flex items-center space-x-3">
                                <div class="p-2 rounded-xl bg-blue-50 text-blue-600">
                                    <i class="fas fa-file-invoice text-sm"></i>
                                </div>
                                <div>
                                    <h3 class="text-sm font-semibold text-gray-900">
                                        Facturación Electrónica DIAN
                                    </h3>
                                    <p class="text-xs text-gray-500 mt-0.5">
                                        Activa esta opción si el cliente requiere facturación electrónica
                                    </p>
                                </div>
                            </div>
                            <label class="relative inline-flex items-center cursor-pointer">
                                <input type="checkbox" wire:model.live="newCustomer.requiresElectronicInvoice"
                                    class="sr-only peer">
                                <div
                                    class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-emerald-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-emerald-600">
                                </div>
                            </label>
                        </div>

                        <!-- Campos DIAN (mostrar/ocultar dinámicamente) -->
                        @if ($newCustomer['requiresElectronicInvoice'] ?? false)
                            <div class="mt-6 space-y-4 border-t border-gray-200 pt-6">
                                <!-- Mensaje informativo -->
                                <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                                    <div class="flex items-start">
                                        <i class="fas fa-info-circle text-blue-600 mt-0.5 mr-2"></i>
                                        <div class="text-xs text-blue-800">
                                            <p class="font-semibold mb-1">Campos Obligatorios para Facturación
                                                Electrónica</p>
                                            <p class="text-[10px]">Complete todos los campos marcados con <span
                                                    class="text-red-500 font-bold">*</span> para poder generar
                                                facturas electrónicas válidas según la normativa DIAN.</p>
                                        </div>
                                    </div>
                                </div>

                                <!-- Tipo de Documento -->
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Tipo de Documento <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model.live="newCustomer.identificationDocumentId"
                                        wire:change="updateCustomerRequiredFields"
                                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newCustomer.identificationDocumentId') border-red-500 @enderror">
                                        <option value="">Seleccione...</option>
                                        @if (is_array($identificationDocuments))
                                            @foreach ($identificationDocuments as $doc)
                                                <option value="{{ $doc['id'] ?? '' }}">
                                                    {{ $doc['name'] ?? '' }}@if (!empty($doc['code'] ?? null))
                                                        ({{ $doc['code'] }})
                                                    @endif
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('newCustomer.identificationDocumentId')
                                        <span class="mt-1 text-xs font-medium text-red-600 block">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <!-- Dígito Verificador (solo si el documento lo requiere) -->
                                @if ($customerRequiresDV)
                                    <div>
                                        <label
                                            class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                            Dígito Verificador (DV) <span class="text-red-500">*</span>
                                        </label>
                                        <input type="text" wire:model="newCustomer.dv" maxlength="1" readonly
                                            class="block w-full px-3 py-2.5 border border-gray-200 bg-gray-50 rounded-xl text-sm text-gray-600 cursor-not-allowed font-bold">
                                        <p class="mt-1 text-xs text-blue-600">
                                            <i class="fas fa-magic mr-1"></i> Calculado automáticamente por el
                                            sistema
                                        </p>
                                    </div>
                                @endif

                                <!-- Razón Social / Nombre Comercial (solo para personas jurídicas) -->
                                @if ($customerIsJuridicalPerson)
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                Razón Social / Empresa <span class="text-red-500">*</span>
                                            </label>
                                            <input type="text" wire:model="newCustomer.company"
                                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newCustomer.company') border-red-500 @enderror"
                                                placeholder="Razón social">
                                            @error('newCustomer.company')
                                                <span class="mt-1 text-xs font-medium text-red-600 block">
                                                    <i class="fas fa-exclamation-triangle mr-1"></i>
                                                    {{ $message }}
                                                </span>
                                            @enderror
                                        </div>
                                        <div>
                                            <label
                                                class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                                Nombre Comercial
                                            </label>
                                            <input type="text" wire:model="newCustomer.tradeName"
                                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500"
                                                placeholder="Nombre comercial">
                                        </div>
                                    </div>
                                @endif

                                <!-- Municipio -->
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Municipio <span class="text-red-500">*</span>
                                    </label>
                                    <select wire:model="newCustomer.municipalityId"
                                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500 @error('newCustomer.municipalityId') border-red-500 @enderror">
                                        <option value="">Seleccione un municipio...</option>
                                        @if (is_array($municipalities))
                                            @foreach ($municipalities as $municipality)
                                                <option
                                                    value="{{ $municipality['factus_id'] ?? ($municipality['id'] ?? '') }}">
                                                    {{ $municipality['department'] ?? '' }} -
                                                    {{ $municipality['name'] ?? '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                    @error('newCustomer.municipalityId')
                                        <span class="mt-1 text-xs font-medium text-red-600 block">
                                            <i class="fas fa-exclamation-triangle mr-1"></i> {{ $message }}
                                        </span>
                                    @enderror
                                </div>

                                <!-- Tipo de Organización Legal -->
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Tipo de Organización Legal
                                    </label>
                                    <select wire:model="newCustomer.legalOrganizationId"
                                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">Seleccione...</option>
                                        @if (isset($legalOrganizations) && is_array($legalOrganizations))
                                            @foreach ($legalOrganizations as $org)
                                                <option value="{{ $org['id'] ?? '' }}">
                                                    {{ $org['name'] ?? '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>

                                <!-- Régimen Tributario -->
                                <div>
                                    <label
                                        class="block text-xs font-bold text-gray-500 uppercase tracking-wider mb-2">
                                        Régimen Tributario
                                    </label>
                                    <select wire:model="newCustomer.tributeId"
                                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm focus:ring-emerald-500 focus:border-emerald-500">
                                        <option value="">Seleccione...</option>
                                        @if (isset($tributes) && is_array($tributes))
                                            @foreach ($tributes as $tribute)
                                                <option value="{{ $tribute['id'] ?? '' }}">
                                                    {{ $tribute['name'] ?? '' }}
                                                </option>
                                            @endforeach
                                        @endif
                                    </select>
                                </div>
                            </div>
                        @endif
                    </div>

                    <!-- Botones -->
                    <div class="flex gap-3 pt-6 border-t border-gray-200 mt-6">
                        <button type="button" @click="newCustomerModal = false; $wire.closeNewCustomerModal()"
                            class="flex-1 px-4 py-2.5 border border-gray-300 rounded-xl text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button type="submit" wire:loading.attr="disabled" wire:target="createCustomer"
                            class="flex-1 px-4 py-2.5 bg-emerald-600 text-white rounded-xl text-sm font-semibold hover:bg-emerald-700 transition-colors disabled:opacity-50 disabled:cursor-not-allowed">
                            <span wire:loading.remove wire:target="createCustomer">Crear Cliente</span>
                            <span wire:loading wire:target="createCustomer"
                                class="flex items-center justify-center">
                                <i class="fas fa-spinner fa-spin mr-2"></i>
                                Creando...
                            </span>
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

