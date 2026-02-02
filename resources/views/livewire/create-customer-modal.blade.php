<div x-show="$wire.isOpen" 
     x-transition
     class="fixed inset-0 z-[60] overflow-y-auto" 
     x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="$wire.close()" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 sticky top-0 bg-white z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Nuevo Cliente</h3>
                </div>
                <button @click="$wire.close()" class="text-gray-400 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            <!-- Form -->
            <div class="p-6 space-y-6">
                <!-- Información Básica -->
                <div class="space-y-4">
                    <div>
                        <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                            Nombre Completo <span class="text-red-500">*</span>
                        </label>
                        <input type="text" 
                               wire:model.blur="formData.name"
                               oninput="this.value = this.value.toUpperCase()"
                               class="w-full bg-gray-50 border rounded-lg px-4 py-2.5 text-sm font-bold uppercase mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['name']) ? 'border-red-300 focus:ring-red-500' : 'border-gray-200' }}">
                        @if(isset($errors['name']))
                            <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                {{ $errors['name'] }}
                            </p>
                        @endif
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                Tipo de Documento <span class="text-red-500">*</span>
                            </label>
                            <select wire:model.live="formData.identification_document_id"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccione...</option>
                                @foreach($identificationDocuments as $doc)
                                    <option value="{{ $doc->id }}">{{ $doc->name }}@if($doc->code) ({{ $doc->code }})@endif</option>
                                @endforeach
                            </select>
                            @if(isset($errors['identification_document_id']))
                                <p class="text-[10px] text-red-600 mt-1">{{ $errors['identification_document_id'] }}</p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                Teléfono <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   wire:model.blur="formData.phone"
                                   oninput="this.value = this.value.replace(/\D/g, ''); if(this.value.length > 10) this.value = this.value.slice(0, 10);"
                                   maxlength="10"
                                   class="w-full bg-gray-50 border rounded-lg px-4 py-2.5 text-sm font-bold mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['phone']) ? 'border-red-300 focus:ring-red-500' : 'border-gray-200' }}">
                            @if(isset($errors['phone']))
                                <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                    {{ $errors['phone'] }}
                                </p>
                            @endif
                        </div>
                    </div>

                    <div class="grid grid-cols-2 gap-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                Número de Identificación <span class="text-red-500">*</span>
                            </label>
                            <input type="text" 
                                   wire:model.blur="formData.identification"
                                   oninput="this.value = this.value.replace(/\D/g, '');"
                                   maxlength="15"
                                   placeholder="Ej: 123456789"
                                   class="w-full bg-gray-50 border rounded-lg px-4 py-2.5 text-sm font-bold mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['identification']) ? 'border-red-300 focus:ring-red-500' : 'border-gray-200' }}">
                            @if(isset($errors['identification']))
                                <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                    {{ $errors['identification'] }}
                                </p>
                            @endif
                            @if($identificationMessage)
                                <p class="text-[10px] mt-1 flex items-center {{ $identificationExists ? 'text-red-600' : 'text-emerald-600' }}">
                                    <i class="fas {{ $identificationExists ? 'fa-exclamation-circle' : 'fa-check-circle' }} mr-1 text-[8px]"></i>
                                    {{ $identificationMessage }}
                                </p>
                            @endif
                        </div>
                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                Correo Electrónico
                            </label>
                            <input type="email" 
                                   wire:model.blur="formData.email"
                                   class="w-full bg-gray-50 border rounded-lg px-4 py-2.5 text-sm font-bold mt-1 focus:ring-2 focus:ring-blue-500 focus:border-blue-500 {{ isset($errors['email']) ? 'border-red-300 focus:ring-red-500' : 'border-gray-200' }}">
                            @if(isset($errors['email']))
                                <p class="text-[10px] text-red-600 mt-1 flex items-center">
                                    <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                                    {{ $errors['email'] }}
                                </p>
                            @endif
                        </div>
                    </div>
                </div>

                <!-- Facturación Electrónica -->
                <div class="border-t border-gray-100 pt-4">
                    <div class="flex items-center justify-between mb-4">
                        <div>
                            <label class="block text-xs font-bold text-gray-700 mb-1">Facturación Electrónica DIAN</label>
                            <p class="text-[10px] text-gray-500">Activa si el cliente requiere facturación electrónica</p>
                        </div>
                        <label class="relative inline-flex items-center cursor-pointer">
                            <input type="checkbox" 
                                   wire:model.live="formData.requires_electronic_invoice"
                                   class="sr-only peer">
                            <div class="w-11 h-6 bg-gray-200 peer-focus:outline-none peer-focus:ring-4 peer-focus:ring-blue-300 rounded-full peer peer-checked:after:translate-x-full peer-checked:after:border-white after:content-[''] after:absolute after:top-[2px] after:left-[2px] after:bg-white after:border-gray-300 after:border after:rounded-full after:h-5 after:w-5 after:transition-all peer-checked:bg-blue-600"></div>
                        </label>
                    </div>

                    <div x-show="$wire.formData.requires_electronic_invoice" 
                         x-transition
                         class="space-y-4 mt-4" 
                         x-cloak>
                        <div class="bg-blue-50 border border-blue-200 rounded-lg p-3">
                            <p class="text-xs text-blue-800">
                                <i class="fas fa-info-circle mr-1"></i>
                                Complete los campos marcados con <span class="text-red-500 font-bold">*</span> para facturación electrónica.
                            </p>
                        </div>

                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                    Tipo de Documento <span class="text-red-500">*</span>
                                </label>
                                <select wire:model.live="formData.identification_document_id"
                                        class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                    <option value="">Seleccione...</option>
                                    @foreach($identificationDocuments as $doc)
                                        <option value="{{ $doc->id }}">{{ $doc->name }}@if($doc->code) ({{ $doc->code }})@endif</option>
                                    @endforeach
                                </select>
                                @if(isset($errors['identification_document_id']))
                                    <p class="text-[10px] text-red-600 mt-1">{{ $errors['identification_document_id'] }}</p>
                                @endif
                            </div>

                            @if($requiresDV)
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                        Dígito Verificador (DV) <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           wire:model="formData.dv"
                                           readonly
                                           class="w-full bg-gray-50 border border-gray-200 rounded-lg px-3 py-2 text-sm font-bold text-gray-600 cursor-not-allowed">
                                    <p class="text-[10px] text-blue-600 mt-1">
                                        <i class="fas fa-magic mr-1"></i> Calculado automáticamente
                                    </p>
                                </div>
                            @endif
                        </div>

                        @if($isJuridicalPerson)
                            <div class="grid grid-cols-2 gap-4">
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                        Razón Social <span class="text-red-500">*</span>
                                    </label>
                                    <input type="text" 
                                           wire:model.blur="formData.company"
                                           class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                    @if(isset($errors['company']))
                                        <p class="text-[10px] text-red-600 mt-1">{{ $errors['company'] }}</p>
                                    @endif
                                </div>
                                <div>
                                    <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                        Nombre Comercial
                                    </label>
                                    <input type="text" 
                                           wire:model.blur="formData.trade_name"
                                           class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm">
                                </div>
                            </div>
                        @endif

                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                Municipio <span class="text-red-500">*</span>
                            </label>
                            <select wire:model="formData.municipality_id"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
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
                                    <option value="{{ $municipality->factus_id }}">
                                        {{ $municipality->department }} - {{ $municipality->name }}
                                    </option>
                                    @if($loop->last)
                                        </optgroup>
                                    @endif
                                @endforeach
                            </select>
                            @if(isset($errors['municipality_id']))
                                <p class="text-[10px] text-red-600 mt-1">{{ $errors['municipality_id'] }}</p>
                            @endif
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                Tipo de Organización Legal
                            </label>
                            <select wire:model="formData.legal_organization_id"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccione...</option>
                                @foreach($legalOrganizations as $org)
                                    <option value="{{ $org->id }}">{{ $org->name }}</option>
                                @endforeach
                            </select>
                        </div>

                        <div>
                            <label class="block text-xs font-bold text-gray-400 uppercase tracking-widest ml-1 mb-2">
                                Régimen Tributario
                            </label>
                            <select wire:model="formData.tribute_id"
                                    class="w-full bg-white border border-gray-200 rounded-lg px-3 py-2 text-sm focus:ring-2 focus:ring-blue-500">
                                <option value="">Seleccione...</option>
                                @foreach($tributes as $tribute)
                                    <option value="{{ $tribute->id }}">{{ $tribute->name }}@if($tribute->code) ({{ $tribute->code }})@endif</option>
                                @endforeach
                            </select>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Footer -->
            <div class="flex gap-3 px-6 py-4 border-t border-gray-100 bg-gray-50 sticky bottom-0">
                <button type="button" 
                        @click="$wire.close()"
                        class="px-4 bg-gray-100 text-gray-700 py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-gray-200 transition-all">
                    Cancelar
                </button>
                <button type="button" 
                        wire:click="create"
                        wire:loading.attr="disabled"
                        wire:target="create"
                        onclick="console.log('Button clicked - calling create');"
                        class="flex-1 bg-blue-600 text-white py-2.5 rounded-xl text-xs font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                    <span wire:loading.remove wire:target="create">Crear Cliente</span>
                    <span wire:loading wire:target="create" class="flex items-center justify-center">
                        <i class="fas fa-spinner fa-spin mr-2"></i>
                        Creando...
                    </span>
                </button>
            </div>
            
            @if(isset($errors['general']))
                <div class="px-6 pb-4">
                    <p class="text-[10px] text-red-600 flex items-center">
                        <i class="fas fa-exclamation-circle mr-1 text-[8px]"></i>
                        {{ $errors['general'] }}
                    </p>
                </div>
            @endif
        </div>
    </div>
</div>

