<div class="max-w-6xl mx-auto">
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
                <!-- Fecha de Venta -->
                <div>
                    <label for="sale_date" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Fecha de Venta <span class="text-red-500">*</span>
                    </label>
                    <input type="date" 
                           id="sale_date" 
                           name="sale_date"
                           wire:model="sale_date"
                           class="block w-full px-3 sm:px-4 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent transition-all @error('sale_date') border-red-300 focus:ring-red-500 @enderror"
                           required>
                    @error('sale_date')
                        <p class="mt-1.5 text-xs text-red-600 flex items-center">
                            <i class="fas fa-exclamation-circle mr-1.5"></i>
                            {{ $message }}
                        </p>
                    @enderror
                </div>

                <!-- Habitación (Opcional) -->
                <div>
                    <label for="room_id" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Habitación (Opcional)
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
                                    Titular: {{ $customerName }}
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

                <!-- Turno -->
                <div>
                    <label for="shift" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                        Turno <span class="text-red-500">*</span>
                    </label>
                    <div class="relative">
                        <select id="shift" 
                                name="shift"
                                wire:model="shift"
                                class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent appearance-none bg-white @error('shift') border-red-300 focus:ring-red-500 @enderror"
                                required>
                            <option value="dia">Día</option>
                            <option value="noche">Noche</option>
                        </select>
                        <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                            <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                        </div>
                    </div>
                    <p class="mt-1.5 text-xs text-gray-500">
                        @if(Auth::user()->hasRole('Recepcionista Día'))
                            Turno automático: Día
                        @elseif(Auth::user()->hasRole('Recepcionista Noche'))
                            Turno automático: Noche
                        @else
                            Turno determinado por hora
                        @endif
                    </p>
                    @error('shift')
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
                            @if($room_id)
                                <option value="pendiente">Pendiente</option>
                            @endif
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
                                    <input type="number" 
                                           id="cash_amount"
                                           name="cash_amount"
                                           wire:model.live="cash_amount"
                                           step="0.01"
                                           min="0"
                                           class="block w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('cash_amount') border-red-300 focus:ring-red-500 @enderror"
                                           placeholder="0.00">
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
                                    <input type="number" 
                                           id="transfer_amount"
                                           name="transfer_amount"
                                           wire:model.live="transfer_amount"
                                           step="0.01"
                                           min="0"
                                           class="block w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent @error('transfer_amount') border-red-300 focus:ring-red-500 @enderror"
                                           placeholder="0.00">
                                </div>
                                @error('transfer_amount')
                                    <p class="mt-1.5 text-xs text-red-600 flex items-center">
                                        <i class="fas fa-exclamation-circle mr-1.5"></i>
                                        {{ $message }}
                                    </p>
                                @enderror
                            </div>
                            
                            <div class="sm:col-span-2">
                                <p class="text-xs text-gray-500">
                                    <span class="font-semibold">Total:</span> 
                                    ${{ number_format(($cash_amount ?? 0) + ($transfer_amount ?? 0), 2, ',', '.') }}
                                    @if(abs((($cash_amount ?? 0) + ($transfer_amount ?? 0)) - $this->total) > 0.01 && $this->total > 0)
                                        <span class="text-red-600">
                                            (Debe ser igual al total de la venta: ${{ number_format($this->total, 2, ',', '.') }})
                                        </span>
                                    @endif
                                </p>
                            </div>
                        </div>
                    @endif
                </div>

                <!-- Estado de Deuda (Solo para habitaciones) -->
                @if($room_id)
                    <div class="sm:col-span-2">
                        <label for="debt_status" class="block text-xs sm:text-sm font-semibold text-gray-700 mb-2">
                            Estado de Deuda <span class="text-red-500">*</span>
                        </label>
                        <div class="relative">
                            <select id="debt_status" 
                                    name="debt_status"
                                    wire:model="debt_status"
                                    @if($payment_method !== 'pendiente') disabled @endif
                                    class="block w-full pl-3 sm:pl-4 pr-10 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent appearance-none bg-white disabled:bg-gray-100 disabled:cursor-not-allowed disabled:text-gray-500 @error('debt_status') border-red-300 focus:ring-red-500 @enderror">
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
            <div class="flex items-center justify-between mb-4 sm:mb-6">
                <div class="flex items-center space-x-2">
                    <div class="p-2 rounded-lg bg-amber-50 text-amber-600">
                        <i class="fas fa-box text-sm"></i>
                    </div>
                    <h2 class="text-base sm:text-lg font-semibold text-gray-900">Productos</h2>
                </div>
                <div class="flex items-center space-x-3">
                    <!-- Filtro de Categoría -->
                    <div class="flex items-center space-x-2">
                        <label for="category_filter" class="text-xs font-semibold text-gray-700">Filtrar por:</label>
                        <select id="category_filter"
                                wire:model.live="productCategoryFilter"
                                class="px-3 py-2 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                            <option value="">Todos</option>
                            @foreach($categories as $category)
                                <option value="{{ $category->name }}">{{ $category->name }}</option>
                            @endforeach
                        </select>
                    </div>
                </div>
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
                            @foreach($filteredProducts as $product)
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
                        <input type="number" 
                               wire:model="selectedQuantity"
                               min="1"
                               step="1"
                               class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent"
                               required>
                        @error('selectedQuantity')
                            <p class="mt-1 text-xs text-red-600">{{ $message }}</p>
                        @enderror
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
                        <div class="border border-gray-200 rounded-xl p-4">
                            <div class="grid grid-cols-1 sm:grid-cols-4 gap-4">
                                <div class="sm:col-span-2">
                                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                                        Producto
                                    </label>
                                    <p class="text-sm text-gray-900">{{ $item['product_name'] ?? 'N/A' }}</p>
                                    <p class="text-xs text-gray-500 mt-1">{{ $item['product_category'] ?? '' }}</p>
                                </div>
                                <div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                                        Cantidad
                                    </label>
                                    <input type="number" 
                                           wire:model.live="items.{{ $index }}.quantity"
                                           wire:change="calculateTotal"
                                           min="1"
                                           class="block w-full px-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-green-500 focus:border-transparent">
                                </div>
<div>
                                    <label class="block text-xs font-semibold text-gray-700 mb-2">
                                        Total
                                    </label>
                                    <div class="relative">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                                            <span class="text-gray-500 text-sm">$</span>
                                        </div>
                                        <input type="text" 
                                               value="${{ number_format($itemTotal, 2, ',', '.') }}"
                                               readonly
                                               class="block w-full pl-8 pr-3 py-2.5 border border-gray-300 rounded-lg text-sm text-gray-900 bg-gray-50">
                                    </div>
                                </div>
                            </div>
                            <div class="mt-3 flex justify-end">
                                <button type="button" 
                                        wire:click="removeItem({{ $index }})"
                                        class="text-red-600 hover:text-red-800 text-sm">
                                    <i class="fas fa-trash mr-1"></i>
                                    Eliminar
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

            <!-- Total General -->
            @if(count($items) > 0)
                <div class="mt-6 pt-6 border-t border-gray-200">
                    <div class="flex justify-between items-center">
                        <span class="text-lg font-semibold text-gray-900">Total de la Venta:</span>
                        <span class="text-2xl font-bold text-green-600">${{ number_format($this->total, 2, ',', '.') }}</span>
                    </div>
                </div>
            @endif

            @error('items')
                <p class="mt-2 text-xs text-red-600 flex items-center">
                    <i class="fas fa-exclamation-circle mr-1.5"></i>
                    {{ $message }}
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
                    <a href="{{ route('sales.index') }}" 
                       class="inline-flex items-center justify-center px-4 sm:px-6 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-colors duration-200">
                        <i class="fas fa-arrow-left mr-2"></i>
                        Volver
                    </a>
                    
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
