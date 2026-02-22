<div class="{{ $isModal ? '' : 'max-w-4xl mx-auto' }}">
    <form method="POST" action="{{ route('sales.update', $sale) }}" id="edit-sale-form" x-data="{ submitting: false }" @submit.prevent="
        submitting = true;
        @this.call('validateBeforeSubmit').then((result) => {
            if (result === true) {
                $el.submit();
            } else {
                submitting = false;
            }
        });
    ">
        @csrf
        @method('PUT')
        
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-indigo-50 text-indigo-600">
                <i class="fas fa-edit text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Editar Venta</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Venta #{{ $sale->id }} - {{ $sale->sale_date->format('d/m/Y') }}</p>
            </div>
        </div>
    </div>

    <div class="space-y-4 sm:space-y-6">
        <!-- Información de la Venta -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                <div class="p-2 rounded-lg bg-blue-50 text-blue-600">
                    <i class="fas fa-info text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Información de la Venta</h2>
            </div>
            
            <div class="grid grid-cols-1 sm:grid-cols-2 gap-4 sm:gap-6">
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Fecha
                    </label>
                    <div class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600">
                        {{ $sale->sale_date->format('d/m/Y') }}
                    </div>
                </div>

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Recepcionista
                    </label>
                    <div class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600">
                        {{ $sale->user->name }}
                    </div>
                </div>

                @if($sale->room)
                    <div>
                        <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Habitación
                        </label>
                        <div class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600">
                            Hab. {{ $sale->room->room_number }}
                        </div>
                    </div>
                @endif

                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Total
                    </label>
                    <div class="px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm font-bold text-gray-900">
                        ${{ number_format($sale->total, 2, ',', '.') }}
                    </div>
                </div>
            </div>
        </div>

        <!-- Método de Pago -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                <div class="p-2 rounded-lg bg-green-50 text-green-600">
                    <i class="fas fa-money-bill-wave text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Método de Pago</h2>
            </div>
            
            <div class="space-y-4 sm:space-y-6">
                <div>
                    <label for="payment_method" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select id="payment_method" 
                                name="payment_method"
                                wire:model.live="payment_method"
                                class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent appearance-none bg-white @error('payment_method') border-red-300 focus:ring-red-500 @enderror">
                            <option value="efectivo">Efectivo</option>
                            <option value="transferencia">Transferencia</option>
                            <option value="ambos">Ambos</option>
                            <option value="pendiente">Pendiente</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    @error('payment_method')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                    
                    @if($payment_method === 'ambos')
                        <div class="mt-4 grid grid-cols-1 sm:grid-cols-2 gap-4">
                            <div>
                                <label for="cash_amount" class="block text-xs font-semibold text-gray-700 mb-2">
                                    Monto en Efectivo <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm">$</span>
                                    </div>
                                    <input type="text" 
                                           id="cash_amount"
                                           name="cash_amount"
                                           wire:model.live.debounce.500ms="cash_amount"
                                           oninput="maskCurrency(event)"
                                           class="block w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('cash_amount') border-red-300 focus:ring-red-500 @enderror"
                                           placeholder="0">
                                </div>
                                @error('cash_amount')
                                    <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            
                            <div>
                                <label for="transfer_amount" class="block text-xs font-semibold text-gray-700 mb-2">
                                    Monto por Transferencia <span class="text-red-500">*</span>
                                </label>
                                <div class="relative">
                                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                        <span class="text-gray-500 text-sm">$</span>
                                    </div>
                                    <input type="text" 
                                           id="transfer_amount"
                                           name="transfer_amount"
                                           wire:model.live.debounce.500ms="transfer_amount"
                                           oninput="maskCurrency(event)"
                                           class="block w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent @error('transfer_amount') border-red-300 focus:ring-red-500 @enderror"
                                           placeholder="0">
                                </div>
                                @error('transfer_amount')
                                    <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            
                            <div class="sm:col-span-2">
                                <div class="p-3 rounded-xl bg-gray-50 border border-gray-100 space-y-2">
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-gray-500 font-medium">Suma ingresada:</span>
                                        <span class="font-bold text-gray-900">${{ number_format($this->suma_pagos, 0, ',', '.') }}</span>
                                    </div>
                                    <div class="flex justify-between items-center text-xs">
                                        <span class="text-gray-500 font-medium">Total de la venta:</span>
                                        <span class="font-bold text-gray-900">${{ number_format($sale->total, 0, ',', '.') }}</span>
                                    </div>
                                    
                                    <div class="pt-2 border-t border-gray-200 flex justify-between items-center">
                                        <span class="text-xs font-bold uppercase tracking-wider text-gray-600">
                                            {{ $this->diferencia_pagos < 0 ? 'Faltante:' : ($this->diferencia_pagos > 0 ? 'Sobrante:' : 'Estado:') }}
                                        </span>
                                        @if(abs($this->diferencia_pagos) < 0.01)
                                            <span class="text-xs font-bold text-emerald-600 flex items-center">
                                                <i class="fas fa-check-circle mr-1"></i> Cuadrado
                                            </span>
                                        @else
                                            <span class="text-sm font-black {{ $this->diferencia_pagos < 0 ? 'text-red-600' : 'text-amber-600' }}">
                                                ${{ number_format(abs($this->diferencia_pagos), 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </div>
                    @endif
                </div>

                @if($sale->room_id)
                    <div>
                        <label for="debt_status" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Estado de Deuda <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="debt_status" 
                                    name="debt_status"
                                    wire:model="debt_status"
                                    @if($payment_method !== 'pendiente') disabled @endif
                                    class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent appearance-none bg-white disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-500 @error('debt_status') border-red-300 focus:ring-red-500 @enderror">
                                <option value="pagado">Pagado</option>
                                <option value="pendiente">Pendiente</option>
                            </select>
                            <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                                <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                            </div>
                        </div>
                        <p class="mt-1.5 text-xs text-gray-500">
                            @if($payment_method === 'pendiente')
                                Seleccione "Pendiente" si el pago aún no se ha realizado
                            @else
                                <span class="text-amber-600">
                                    <i class="fas fa-lock mr-1"></i>
                                    Bloqueado automáticamente en "Pagado" porque el método de pago no es "Pendiente"
                                </span>
                            @endif
                        </p>
                        @error('debt_status')
                            <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                <i class="fas fa-exclamation-circle mr-1.5"></i>
                                {{ $message }}
                            </p>
                        @enderror
                    </div>
                @endif

<div>
                    <label for="notes" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Notas
                    </label>
                    <textarea id="notes" 
                              name="notes"
                              wire:model="notes"
                              rows="3"
                              class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-transparent transition-all @error('notes') border-red-300 focus:ring-red-500 @enderror"
                              placeholder="Notas adicionales sobre la venta..."></textarea>
                    @error('notes')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>
            </div>
        </div>

        <!-- Botones de Acción -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                @if($isModal)
                    <button type="button"
                            wire:click="$dispatch('sales-close-modal')"
                            class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-times mr-2"></i>
                        Cerrar
                    </button>
                @else
                    <a href="{{ route('sales.show', $sale) }}" 
                       class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Cancelar
                    </a>
                @endif
                
                <button type="submit" 
                        x-bind:disabled="submitting"
                        class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border-2 border-indigo-600 bg-indigo-600 text-white text-sm font-semibold hover:bg-indigo-700 hover:border-indigo-700 transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                    <i class="fas fa-save mr-2" x-show="!submitting"></i>
                    <i class="fas fa-spinner fa-spin mr-2" x-show="submitting"></i>
                    <span x-show="!submitting">Actualizar Venta</span>
                    <span x-show="submitting">Procesando...</span>
                </button>
            </div>
        </div>
    </div>
    
    </form>
</div>

@if (session()->has('success'))
    <div x-data="{ show: true }" 
         x-show="show" 
         x-init="setTimeout(() => show = false, 3000)"
         class="fixed top-4 right-4 bg-green-500 text-white px-6 py-3 rounded-lg shadow-lg z-50">
        <div class="flex items-center">
            <i class="fas fa-check-circle mr-2"></i>
            <span>{{ session('success') }}</span>
        </div>
    </div>
@endif
