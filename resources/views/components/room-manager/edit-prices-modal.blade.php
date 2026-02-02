@props(['editPricesForm'])

@if ($editPricesForm && isset($editPricesForm['nights']))
    @php
        $nightsTotal = collect($editPricesForm['nights'])->sum('price');
    @endphp
    <div x-data="{
        calculateNightsTotal() {
                return this.editPricesForm.nights.reduce((total, night) => total + parseFloat(night.price || 0), 0);
            },
            updateTotalFromNights() {
                const nightsTotal = this.calculateNightsTotal();
                this.$wire.set('editPricesForm.total_amount', nightsTotal);
            }
    }" x-show="$wire.editPricesModal" x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0" x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200" x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0" class="fixed inset-0 z-50 overflow-y-auto" style="display: none;">
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="$wire.cancelEditPrices()"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-xl shadow-xl max-w-4xl w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-blue-600 to-blue-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-dollar-sign text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Editar Precios</h3>
                                <p class="text-blue-100 text-sm">Modificar valores de noches y total</p>
                            </div>
                        </div>
                        <button type="button" @click="$wire.cancelEditPrices()"
                            class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 max-h-[60vh] overflow-y-auto">
                    <!-- Lista de Noches -->
                    <div class="space-y-3">
                        <h4 class="text-sm font-semibold text-gray-700 mb-3">Detalles por Noche</h4>

                        @foreach ($editPricesForm['nights'] as $index => $night)
                            <div
                                class="flex items-center space-x-3 p-3 bg-white border border-gray-200 rounded-lg hover:border-blue-300 transition-colors">
                                <!-- Fecha -->
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-gray-500">Fecha</label>
                                    <div class="text-sm font-semibold text-gray-900">
                                        {{ \Carbon\Carbon::parse($night['date'])->format('d/m/Y') }}
                                    </div>
                                </div>

                                <!-- Precio -->
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-gray-500">Precio</label>
                                    <div class="relative">
                                        <span
                                            class="absolute left-2 top-1/2 transform -translate-y-1/2 text-gray-500 text-sm">$</span>
                                        <input type="number"
                                            wire:model.live="editPricesForm.nights.{{ $index }}.price"
                                            class="block w-full pl-6 pr-2 py-1.5 border border-gray-300 rounded text-sm font-medium text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent"
                                            step="0.01" min="0">
                                    </div>
                                </div>

                                <!-- Estado -->
                                <div class="flex-1">
                                    <label class="text-xs font-medium text-gray-500">Estado</label>
                                    <div class="flex items-center space-x-2">
                                        @if ($night['is_paid'])
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-emerald-100 text-emerald-700">
                                                <i class="fas fa-check-circle mr-1"></i> Pagada
                                            </span>
                                        @else
                                            <span
                                                class="inline-flex items-center px-2 py-1 rounded-full text-xs font-medium bg-red-100 text-red-700">
                                                <i class="fas fa-clock mr-1"></i> Pendiente
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-between">
                        <div class="text-sm text-gray-500">
                            <span class="font-medium">Total de noches:</span>
                            <span
                                class="font-semibold text-gray-900">${{ number_format($nightsTotal, 0, ',', '.') }}</span>
                        </div>

                        <div class="flex items-center space-x-3">
                            <button type="button" @click="$wire.cancelEditPrices()"
                                class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                                Cancelar
                            </button>
                            <button type="button" wire:click="updatePrices()"
                                class="px-4 py-2 text-sm font-medium text-white bg-blue-600 border border-blue-600 rounded-lg hover:bg-blue-700 transition-colors">
                                <i class="fas fa-save mr-2"></i> Guardar Cambios
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
