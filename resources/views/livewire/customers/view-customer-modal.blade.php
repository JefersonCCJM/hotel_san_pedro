<div x-show="$wire.isOpen" 
     x-transition
     class="fixed inset-0 z-[60] overflow-y-auto" 
     x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="$wire.close()" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-4xl overflow-hidden transform transition-all max-h-[90vh] overflow-y-auto">
            <!-- Header -->
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 sticky top-0 bg-white z-10">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                        <i class="fas fa-user"></i>
                    </div>
                    <h3 class="text-lg font-bold text-gray-900">Detalles del Cliente</h3>
                </div>
                <button @click="$wire.close()" class="text-gray-400 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            @if($customer)
            <!-- Content -->
            <div class="p-6 space-y-6">
                <!-- Información Básica -->
                <div class="bg-white rounded-xl border border-gray-100 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-user-circle mr-2 text-blue-600"></i>
                        Información Básica
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Nombre Completo</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">{{ $customer->name }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">ID del Cliente</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">#{{ $customer->id }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Estado</label>
                                <div class="mt-1">
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
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Teléfono</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">{{ $customer->phone ?: 'No registrado' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Correo Electrónico</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">{{ $customer->email ?: 'No registrado' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-gray-500 uppercase tracking-wider">Dirección</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">{{ $customer->address ?: 'No registrada' }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Información de Facturación Electrónica -->
                @if($customer->requires_electronic_invoice && $customer->taxProfile)
                <div class="bg-blue-50 rounded-xl border border-blue-200 p-6">
                    <h4 class="text-lg font-semibold text-gray-900 mb-4 flex items-center">
                        <i class="fas fa-file-invoice mr-2 text-blue-600"></i>
                        Información de Facturación Electrónica
                    </h4>
                    <div class="grid grid-cols-1 md:grid-cols-2 gap-6">
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Tipo de Documento</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">{{ $customer->taxProfile->identificationDocument?->name ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Número de Identificación</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">
                                    {{ $customer->taxProfile->identification }}
                                    @if($customer->taxProfile->dv)
                                        <span class="text-blue-600">-{{ $customer->taxProfile->dv }}</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Organización Legal</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">{{ $customer->taxProfile->legalOrganization?->name ?? 'N/A' }}</p>
                            </div>
                        </div>
                        <div class="space-y-4">
                            <div>
                                <label class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Tipo de Tributo</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">
                                    {{ $customer->taxProfile->tribute?->name ?? 'N/A' }}
                                    @if($customer->taxProfile->tribute?->code)
                                        <span class="text-blue-600">({{ $customer->taxProfile->tribute->code }})</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Municipio</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">
                                    {{ $customer->taxProfile->municipality?->name ?? 'N/A' }}
                                    @if($customer->taxProfile->municipality)
                                        <span class="text-blue-600">({{ $customer->taxProfile->municipality->department }})</span>
                                    @endif
                                </p>
                            </div>
                            <div>
                                <label class="text-xs font-semibold text-blue-500 uppercase tracking-wider">Email para Facturación</label>
                                <p class="text-sm text-gray-900 font-medium mt-1">{{ $customer->taxProfile->email ?: 'No especificado' }}</p>
                            </div>
                        </div>
                    </div>
                </div>
                @else
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-6">
                    <div class="text-center text-gray-500">
                        <i class="fas fa-info-circle text-2xl mb-2"></i>
                        <p class="text-sm">Este cliente no tiene configurada la facturación electrónica</p>
                    </div>
                </div>
                @endif

                <!-- Información de Registro -->
                <div class="bg-gray-50 rounded-xl border border-gray-200 p-4">
                    <div class="flex items-center justify-between text-sm text-gray-600">
                        <div class="flex items-center">
                            <i class="fas fa-calendar-alt mr-2"></i>
                            <span>Registrado el: {{ $customer->created_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @if($customer->updated_at->ne($customer->created_at))
                        <div class="flex items-center">
                            <i class="fas fa-edit mr-2"></i>
                            <span>Actualizado el: {{ $customer->updated_at->format('d/m/Y H:i') }}</span>
                        </div>
                        @endif
                    </div>
                </div>
            </div>
            @endif

            <!-- Footer -->
            <div class="flex justify-end px-6 py-4 border-t border-gray-100 bg-gray-50">
                <button @click="$wire.close()" 
                        class="inline-flex items-center justify-center px-6 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                    <i class="fas fa-times mr-2"></i>
                    Cerrar
                </button>
            </div>
        </div>
    </div>
</div>
