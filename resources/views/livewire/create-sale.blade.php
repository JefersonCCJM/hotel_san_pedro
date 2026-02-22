<div class="{{ $isModal ? '' : 'max-w-6xl mx-auto' }}">
    <form method="POST" action="{{ route('sales.store') }}" id="sale-form" x-data="{ submitting: false }" @submit.prevent="
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

    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 mb-4 sm:mb-6">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-green-50 text-green-600">
                <i class="fas fa-plus text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Nueva Venta</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Registre una nueva venta de productos</p>
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

            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-3 gap-4 sm:gap-6">
                <!-- Fecha de Venta (Inmodificable) -->
                <div>
                    <label class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Fecha de Venta
                    </label>
                    <div class="px-3 sm:px-4 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm text-gray-600 flex items-center cursor-not-allowed shadow-sm">
                        <i class="fas fa-calendar-day mr-2 text-green-600"></i>
                        <span class="font-medium">{{ \Carbon\Carbon::parse($sale_date)->translatedFormat('d \d\e F, Y') }}</span>
                    </div>
                    <input type="hidden" name="sale_date" value="{{ $sale_date }}">
                </div>

                <!-- Habitación (Opcional) -->
                <div class="sm:col-span-2">
                    <label for="room_id" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Relacionar con Habitación (Opcional)
                    </label>
                    <div class="relative">
                        <select id="room_id"
                                name="room_id"
                                wire:model.live="room_id"
                                class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent appearance-none bg-white @error('room_id') border-red-300 focus:ring-red-500 @enderror">
                            <option value="">Venta Normal</option>
                            @foreach($rooms as $room)
                                @php
                                    $customerName = $room->current_reservation && $room->current_reservation->customer
                                        ? $room->current_reservation->customer->name
                                        : '';
                                @endphp
                                <option value="{{ $room->id }}">
                                    Habitación {{ $room->room_number }}@if($customerName) - {{ $customerName }}@endif
                                </option>
                            @endforeach
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    @if($room_id)
                        @php
                            $selectedRoom = collect($rooms)->firstWhere('id', $room_id);
                            $customerName = $selectedRoom && $selectedRoom->current_reservation && $selectedRoom->current_reservation->customer
                                ? $selectedRoom->current_reservation->customer->name
                                : '';
                        @endphp
                        @if($customerName)
                            <div class="mt-2 p-2 bg-blue-50 border border-blue-200 rounded-lg">
                                <p class="text-xs text-blue-700">
                                    <i class="fas fa-info-circle mr-1"></i>
                                    Huésped: {{ $customerName }}
                                </p>
                            </div>
                        @endif
                    @endif
                    @error('room_id')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Método de Pago -->
                <div class="sm:col-span-2">
                    <label for="payment_method" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Método de Pago <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select id="payment_method"
                                name="payment_method"
                                wire:model.live="payment_method"
                                class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent appearance-none bg-white @error('payment_method') border-red-300 focus:ring-red-500 @enderror"
                                required>
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

                    <!-- Campos para pago mixto -->
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
                                           class="block w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('cash_amount') border-red-300 focus:ring-red-500 @enderror"
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
                                           class="block w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('transfer_amount') border-red-300 focus:ring-red-500 @enderror"
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
                                        <span class="font-bold text-gray-900">{{ formatCurrency($this->total) }}</span>
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

                <!-- Estado de Deuda (Controlado Automáticamente) -->
                <div class="sm:col-span-2">
                    <label for="debt_status" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Estado de Deuda
                    </label>
                    <div class="relative">
                        <select id="debt_status_select"
                                wire:model="debt_status"
                                disabled
                                class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-200 rounded-xl text-sm text-gray-500 focus:outline-none appearance-none bg-gray-50 cursor-not-allowed">
                            <option value="pagado">Pagado</option>
                            <option value="pendiente">Pendiente</option>
                        </select>
                        <input type="hidden" name="debt_status" value="{{ $debt_status }}">
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-lock text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs">
                        @if($payment_method !== 'pendiente')
                            <span class="text-emerald-600 font-medium flex items-center">
                                <i class="fas fa-check-circle mr-1.5"></i> Sincronizado: Pagado (Venta cobrada)
                            </span>
                        @else
                            <span class="text-amber-600 font-medium flex items-center">
                                <i class="fas fa-clock mr-1.5"></i> Sincronizado: Pendiente (Venta a crédito)
                            </span>
                        @endif
                    </p>
                </div>

                <!-- Notas -->
                <div class="sm:col-span-2">
                    <label for="notes" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Notas
                    </label>
                    <textarea id="notes"
                              name="notes"
                              wire:model="notes"
                              rows="2"
                              class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 placeholder-gray-400 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all @error('notes') border-red-300 focus:ring-red-500 @enderror"
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

        <!-- Productos -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex items-center space-x-2 mb-4 sm:mb-6">
                <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                    <i class="fas fa-box text-sm"></i>
                </div>
                <h2 class="text-base sm:text-lg font-semibold text-gray-900">Selección de Productos</h2>
            </div>

            <!-- Agregar Producto -->
            <div class="mb-4 p-4 bg-gray-50 rounded-xl border border-gray-200">
                <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                    <div class="sm:col-span-2">
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Producto <span class="text-red-500">*</span>
                        </label>
                        <select wire:model="selectedProduct"
                                class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Seleccionar producto...</option>
                            @foreach($products as $product)
                                <option value="{{ $product->id }}">
                                    {{ $product->name }} - Stock: {{ $product->quantity }} - ${{ number_format($product->price, 2, ',', '.') }}
                                </option>
                            @endforeach
                        </select>
                        @error('selectedProduct')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label class="block text-xs font-semibold text-gray-700 mb-2">
                            Cantidad <span class="text-red-500">*</span>
                        </label>
                        @php
                            $maxStock = 999;
                            $currentSubtotal = 0;
                            if($selectedProduct) {
                                $prod = collect($products)->firstWhere('id', $selectedProduct);
                                $maxStock = $prod ? $prod->quantity : 999;
                                $currentSubtotal = $prod ? ($prod->price * ($selectedQuantity ?: 0)) : 0;
                            }
                        @endphp
                        <div class="space-y-2">
                            <input type="number"
                                   wire:model.live="selectedQuantity"
                                   min="1"
                                   step="1"
                                   class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('selectedQuantity') border-red-300 focus:ring-red-500 @enderror"
                                   required>
                            @if($currentSubtotal > 0)
                                <p class="text-[10px] font-black text-green-600 uppercase tracking-widest text-right">
                                    Subtotal: {{ formatCurrency($currentSubtotal) }}
                                </p>
                            @endif
                        </div>
                        @error('selectedQuantity')
                            <p class="mt-1 text-xs text-red-600 font-bold uppercase tracking-tighter">{{ $message }}</p>
                        @enderror
                        @if($selectedProduct && $maxStock < 999)
                            <p class="mt-1 text-[10px] text-gray-500">
                                Stock disponible: <span class="font-semibold">{{ $maxStock }}</span> unidades
                            </p>
                        @endif
                    </div>
                </div>
                <button type="button"
                        wire:click="addItem"
                        class="mt-4 inline-flex items-center justify-center px-3 py-2 rounded-lg border-2 border-green-600 bg-green-600 text-white text-xs font-semibold hover:bg-green-700 transition-all">
                    <i class="fas fa-plus mr-1.5"></i>
                    Agregar Producto
                </button>
            </div>

            <!-- Lista de Items -->
            @if(count($items) > 0)
                <div class="space-y-4">
                    @foreach($items as $index => $item)
                        @php
                            $product = \App\Models\Product::find($item['product_id']);
                            $itemTotal = $product ? $product->price * $item['quantity'] : 0;
                        @endphp
                        <div class="border border-gray-200 rounded-xl p-4 bg-white hover:bg-gray-50 transition-colors">
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4 items-center">
                                <div class="sm:col-span-2">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                                        Producto
                                    </label>
                                    <p class="text-sm font-bold text-gray-900">{{ $item['product_name'] ?? 'N/A' }}</p>
                                    <p class="text-[10px] text-gray-500 font-medium uppercase tracking-tighter mt-0.5">{{ $item['product_category'] ?? '' }}</p>
                                </div>
                                <div>
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                                        Cantidad
                                    </label>
                                    <div class="flex items-center space-x-2">
                                        <input type="number"
                                               wire:model.live="items.{{ $index }}.quantity"
                                               wire:change="calculateTotal"
                                               min="1"
                                               class="block w-20 px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('items.'.$index.'.quantity') border-red-500 @enderror">
                                        <span class="text-xs text-gray-400">x {{ formatCurrency($item['product_price']) }}</span>
                                    </div>
                                    @error('items.'.$index.'.quantity')
                                        <p class="mt-1 text-[8px] text-red-600 font-bold uppercase">{{ $message }}</p>
                                    @enderror
                                    @if(isset($item['stock_available']))
                                        <p class="mt-0.5 text-[8px] text-gray-400">
                                            Stock: <span class="font-semibold">{{ $item['stock_available'] }}</span>
                                        </p>
                                    @endif
                                </div>
                                <div class="text-right">
                                    <label class="block text-[10px] font-bold text-gray-400 uppercase tracking-widest mb-1">
                                        Subtotal
                                    </label>
                                    <p class="text-sm font-black text-gray-900">{{ formatCurrency($item['product_price'] * $item['quantity']) }}</p>
                                </div>
                            </div>
                            <div class="mt-3 pt-3 border-t border-gray-100 flex justify-end">
                                <button type="button"
                                        wire:click="removeItem({{ $index }})"
                                        class="text-rose-500 hover:text-rose-700 text-[10px] font-bold uppercase flex items-center bg-rose-50 px-2 py-1 rounded-md transition-colors">
                                    <i class="fas fa-trash-alt mr-1.5"></i>
                                    Quitar
                                </button>
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="text-center py-8 text-gray-500">
                    <i class="fas fa-box-open text-3xl mb-2"></i>
                    <p class="text-sm">No hay productos agregados. Seleccione un producto y haga clic en "Agregar Producto" para comenzar.</p>
                </div>
            @endif

                <div class="mt-6 pt-6 border-t-2 border-dashed border-gray-100">
                    <div class="flex flex-col items-end space-y-2">
                        <div class="flex items-center space-x-4">
                            <span class="text-xs font-bold text-gray-500 uppercase tracking-widest">Resumen de Venta</span>
                            <div class="h-px w-20 bg-gray-100"></div>
                        </div>
                        <div class="flex items-center space-x-6">
                            <div class="text-right">
                                <p class="text-[10px] font-bold text-gray-400 uppercase">Total Productos</p>
                                <p class="text-lg font-black text-gray-900">{{ collect($items)->sum('quantity') }}</p>
                            </div>
                            <div class="text-right bg-green-50 px-6 py-3 rounded-2xl border border-green-100">
                                <p class="text-[10px] font-bold text-green-600 uppercase tracking-widest">Total a Pagar</p>
                                <p class="text-3xl font-black text-green-700">{{ formatCurrency($this->total) }}</p>
                            </div>
                        </div>
                    </div>
                </div>

            @error('items')
                <p class="mt-2 text-xs text-red-600 flex items-center font-medium">
                    <i class="fas fa-exclamation-circle mr-1.5 text-sm"></i>
                    Debe agregar el producto a la lista usando el botón "+ Agregar Producto" o seleccionar uno válido.
                </p>
            @enderror
        </div>

        <!-- Botones de Acción -->
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
                <p class="text-xs text-gray-500">
                    Los campos marcados con <span class="text-red-500">*</span> son obligatorios
                </p>

                <div class="flex flex-col sm:flex-row gap-3">
                    @if($isModal)
                        <button type="button"
                                wire:click="$dispatch('sales-close-modal')"
                                class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-times mr-2"></i>
                            Cerrar
                        </button>
                    @else
                        <a href="{{ route('sales.index') }}"
                           class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                            <i class="fas fa-arrow-left mr-2"></i>
                            Volver
                        </a>
                    @endif

                    <button type="submit"
                            x-bind:disabled="submitting"
                            class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border-2 border-green-600 bg-green-600 text-white text-sm font-semibold hover:bg-green-700 hover:border-green-700 transition-all duration-200 shadow-sm hover:shadow-md disabled:opacity-50 disabled:cursor-not-allowed">
                        <i class="fas fa-save mr-2" x-show="!submitting"></i>
                        <i class="fas fa-spinner fa-spin mr-2" x-show="submitting"></i>
                        <span x-show="!submitting">Registrar Venta</span>
                        <span x-show="submitting">Procesando...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>

    <!-- Hidden inputs for items -->
    @foreach($items as $index => $item)
        <input type="hidden" name="items[{{ $index }}][product_id]" value="{{ $item['product_id'] }}">
        <input type="hidden" name="items[{{ $index }}][quantity]" value="{{ $item['quantity'] }}">
    @endforeach
    </form>
</div>
