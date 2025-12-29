@section('title', 'Movimientos de Inventario')
@section('header', 'Movimientos de Inventario')

<div class="max-w-4xl mx-auto space-y-6" wire:poll.5s>
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
        <div class="flex items-center space-x-3 sm:space-x-4">
            <div class="p-2.5 sm:p-3 rounded-xl bg-amber-50 text-amber-600">
                <i class="fas fa-exchange-alt text-lg sm:text-xl"></i>
            </div>
            <div>
                <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Movimientos de Inventario</h1>
                <p class="text-xs sm:text-sm text-gray-500 mt-1">Registre entradas, salidas o consumos de productos fuera de ventas</p>
            </div>
        </div>
    </div>

    @if (session()->has('success'))
        <div class="bg-emerald-50 border border-emerald-200 text-emerald-700 px-4 py-3 rounded-xl flex items-center shadow-sm">
            <i class="fas fa-check-circle mr-2"></i>
            <span class="text-sm font-medium">{{ session('success') }}</span>
        </div>
    @endif

            <div class="grid grid-cols-1 md:grid-cols-3 gap-6">
                <!-- Formulario -->
                <div class="md:col-span-2 space-y-6">
                    <div class="bg-white rounded-xl border border-gray-100 p-6 shadow-sm">
                        <form wire:submit.prevent="save" class="space-y-5" x-data="{ selectionMode: 'search' }">
                            <!-- Selección de Producto -->
                            <div class="relative">
                                <div class="flex items-center justify-between mb-2 ml-1">
                                    <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest">Producto <span class="text-rose-500">*</span></label>
                                    
                                    @if(!$product_id)
                                    <div class="flex bg-gray-100 p-1 rounded-lg">
                                        <button type="button" @click="selectionMode = 'search'" 
                                                :class="selectionMode === 'search' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500'"
                                                class="px-3 py-1 text-[10px] font-bold uppercase rounded-md transition-all">
                                            <i class="fas fa-search mr-1"></i> Buscar
                                        </button>
                                        <button type="button" @click="selectionMode = 'list'" 
                                                :class="selectionMode === 'list' ? 'bg-white text-indigo-600 shadow-sm' : 'text-gray-500'"
                                                class="px-3 py-1 text-[10px] font-bold uppercase rounded-md transition-all">
                                            <i class="fas fa-list mr-1"></i> Filtrado
                                        </button>
                                    </div>
                                    @endif
                                </div>

                                @if($product_id)
                                    <div class="flex items-center justify-between p-3 bg-indigo-50 border border-indigo-100 rounded-xl">
                                        <div class="flex items-center">
                                            <i class="fas fa-box text-indigo-500 mr-3"></i>
                                            <span class="text-sm font-bold text-indigo-900">{{ $selected_product_name }}</span>
                                        </div>
                                        <button type="button" wire:click="$set('product_id', '')" class="text-indigo-400 hover:text-indigo-600 transition-colors">
                                            <i class="fas fa-times-circle"></i>
                                        </button>
                                    </div>
                                @else
                                    <!-- Modo Búsqueda -->
                                    <div x-show="selectionMode === 'search'" class="relative group">
                                        <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none text-gray-400 group-focus-within:text-indigo-500 transition-colors">
                                            <i class="fas fa-search"></i>
                                        </div>
                                        <input type="text" wire:model.live="search_product" 
                                               class="block w-full pl-10 pr-3 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                                               placeholder="Escriba nombre o SKU del producto...">
                                        
                                        @if(count($search_results) > 0)
                                            <div class="absolute z-50 w-full mt-1 bg-white border border-gray-100 rounded-xl shadow-xl overflow-hidden animate-in fade-in slide-in-from-top-2">
                                                @foreach($search_results as $product)
                                                    <button type="button" wire:click="selectProduct({{ $product->id }}, '{{ $product->name }}')" 
                                                            class="w-full px-4 py-3 text-left hover:bg-indigo-50 flex items-center justify-between group transition-colors border-b border-gray-50 last:border-0">
                                                        <div>
                                                            <span class="block text-sm font-bold text-gray-900 group-hover:text-indigo-700">{{ $product->name }}</span>
                                                            <span class="block text-[10px] text-gray-500 uppercase tracking-tighter">{{ $product->sku ?? 'Sin SKU' }}</span>
                                                        </div>
                                                        <span class="text-xs font-bold px-2 py-1 bg-gray-100 rounded-lg text-gray-600">Stock: {{ $product->quantity }}</span>
                                                    </button>
                                                @endforeach
                                            </div>
                                        @endif
                                    </div>

                                    <!-- Modo Desglose (Filtrado por Grupo > Categoría) -->
                                    <div x-show="selectionMode === 'list'" class="space-y-3">
                                        @if(!$active_group)
                                            <!-- Nivel 1: Selección de Grupo (Ventas vs Aseo) -->
                                            <div class="grid grid-cols-2 gap-4">
                                                <button type="button" wire:click="selectGroup('ventas')"
                                                        class="relative overflow-hidden group p-6 rounded-3xl bg-emerald-50 border-2 border-emerald-100 hover:border-emerald-200 transition-all text-left">
                                                    <div class="relative z-10">
                                                        <div class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center mb-4 text-emerald-600 group-hover:scale-110 transition-transform">
                                                            <i class="fas fa-shopping-cart text-xl"></i>
                                                        </div>
                                                        <h3 class="text-sm font-black text-emerald-900 uppercase tracking-widest">PRODUCTOS DE VENTA</h3>
                                                        <p class="text-[10px] text-emerald-600 font-bold mt-1 uppercase tracking-tighter">Bebidas, snacks y más</p>
                                                    </div>
                                                    <i class="fas fa-arrow-right absolute right-6 bottom-6 text-emerald-200 group-hover:text-emerald-400 group-hover:translate-x-1 transition-all"></i>
                                                </button>

                                                <button type="button" wire:click="selectGroup('aseo')"
                                                        class="relative overflow-hidden group p-6 rounded-3xl bg-blue-50 border-2 border-blue-100 hover:border-blue-200 transition-all text-left">
                                                    <div class="relative z-10">
                                                        <div class="w-12 h-12 rounded-2xl bg-white shadow-sm flex items-center justify-center mb-4 text-blue-600 group-hover:scale-110 transition-transform">
                                                            <i class="fas fa-broom text-xl"></i>
                                                        </div>
                                                        <h3 class="text-sm font-black text-blue-900 uppercase tracking-widest">INSUMOS DE ASEO</h3>
                                                        <p class="text-[10px] text-blue-600 font-bold mt-1 uppercase tracking-tighter">Jabón, papel, limpieza</p>
                                                    </div>
                                                    <i class="fas fa-arrow-right absolute right-6 bottom-6 text-blue-200 group-hover:text-blue-400 group-hover:translate-x-1 transition-all"></i>
                                                </button>
                                            </div>
                                        @elseif(!$active_category)
                                            <!-- Nivel 2: Selección de Categoría dentro del Grupo -->
                                            <div class="bg-gray-50 rounded-3xl border border-gray-200 p-4">
                                                <div class="flex items-center justify-between mb-4 px-2">
                                                    <button type="button" wire:click="$set('active_group', null)" 
                                                            class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 flex items-center bg-white px-3 py-1.5 rounded-full shadow-sm border border-indigo-50">
                                                        <i class="fas fa-chevron-left mr-1.5"></i> VOLVER A GRUPOS
                                                    </button>
                                                    <span class="text-[10px] font-black text-gray-400 uppercase tracking-widest">
                                                        {{ $active_group === 'ventas' ? 'Categorías de Venta' : 'Categorías de Aseo' }}
                                                    </span>
                                                </div>

                                                <div class="grid grid-cols-2 gap-3 max-h-64 overflow-y-auto pr-1">
                                                    @forelse($categories as $cat)
                                                        <button type="button" wire:click="selectCategory({{ $cat->id }})"
                                                                class="flex flex-col items-center justify-center p-4 bg-white border border-gray-100 rounded-2xl hover:bg-indigo-50 hover:border-indigo-200 transition-all group shadow-sm">
                                                            <div class="w-10 h-10 rounded-xl bg-gray-50 flex items-center justify-center mb-2 text-indigo-500 group-hover:scale-110 transition-transform">
                                                                <i class="fas fa-folder-open"></i>
                                                            </div>
                                                            <span class="text-[10px] font-bold uppercase tracking-widest text-gray-600 group-hover:text-indigo-700 text-center">
                                                                {{ $cat->name }}
                                                            </span>
                                                        </button>
                                                    @empty
                                                        <div class="col-span-2 py-8 text-center bg-white rounded-2xl border border-dashed border-gray-300">
                                                            <i class="fas fa-folder-minus text-gray-300 text-3xl mb-3 block"></i>
                                                            <p class="text-xs text-gray-500 italic">No hay categorías configuradas para este grupo</p>
                                                        </div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @else
                                            <!-- Nivel 3: Selección de Producto dentro de la Categoría -->
                                            <div class="bg-gray-50 rounded-3xl border border-gray-200 overflow-hidden shadow-sm">
                                                <div class="px-4 py-3 bg-white border-b border-gray-100 flex items-center justify-between">
                                                    <button type="button" wire:click="$set('active_category', null)" 
                                                            class="text-[10px] font-black text-indigo-600 hover:text-indigo-800 flex items-center bg-gray-50 px-3 py-1.5 rounded-full border border-indigo-50">
                                                        <i class="fas fa-chevron-left mr-1.5"></i> VOLVER
                                                    </button>
                                                    <div class="text-right">
                                                        <span class="block text-[10px] font-black text-indigo-900 uppercase tracking-widest">
                                                            {{ $categories->firstWhere('id', $active_category)->name }}
                                                        </span>
                                                        <span class="block text-[8px] text-gray-400 font-bold uppercase tracking-tighter">
                                                            {{ $active_group === 'ventas' ? 'Venta' : 'Insumos' }}
                                                        </span>
                                                    </div>
                                                </div>
                                                <div class="max-h-48 overflow-y-auto p-2 grid grid-cols-1 gap-1">
                                                    @forelse($products_in_category as $prod)
                                                        <button type="button" wire:click="selectProduct({{ $prod->id }}, '{{ $prod->name }}')"
                                                                class="flex items-center justify-between px-4 py-3 rounded-2xl hover:bg-white hover:shadow-md transition-all group border border-transparent hover:border-indigo-100">
                                                            <div class="flex items-center">
                                                                <i class="fas fa-box text-gray-300 group-hover:text-indigo-400 mr-3 transition-colors"></i>
                                                                <span class="text-xs font-bold text-gray-700 group-hover:text-indigo-900">{{ $prod->name }}</span>
                                                            </div>
                                                            <span class="text-[10px] font-bold px-2.5 py-1 bg-gray-100 rounded-lg text-gray-500 group-hover:text-indigo-600 group-hover:bg-indigo-50 border border-gray-200 transition-all">
                                                                Stock: {{ $prod->quantity }}
                                                            </span>
                                                        </button>
                                                    @empty
                                                        <div class="text-center py-8 text-xs text-gray-500 italic">No hay productos en esta categoría</div>
                                                    @endforelse
                                                </div>
                                            </div>
                                        @endif
                                    </div>
                                @endif
                                @error('product_id') <p class="mt-1.5 text-[10px] text-rose-500 font-bold uppercase ml-1">{{ $message }}</p> @enderror
                            </div>

                            <div class="grid grid-cols-1 sm:grid-cols-2 gap-5">
                        <!-- Tipo de Movimiento -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2 ml-1">Tipo <span class="text-rose-500">*</span></label>
                            <div class="grid grid-cols-2 gap-2">
                                <button type="button" wire:click="$set('type', 'input')" 
                                        class="px-3 py-2.5 rounded-xl text-xs font-bold uppercase transition-all border {{ $type === 'input' ? 'bg-emerald-600 border-emerald-600 text-white shadow-lg' : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-emerald-200' }}">
                                    <i class="fas fa-plus-circle mr-1"></i> Entrada
                                </button>
                                <button type="button" wire:click="$set('type', 'output')" 
                                        class="px-3 py-2.5 rounded-xl text-xs font-bold uppercase transition-all border {{ $type === 'output' ? 'bg-rose-600 border-rose-600 text-white shadow-lg' : 'bg-gray-50 border-gray-200 text-gray-500 hover:border-rose-200' }}">
                                    <i class="fas fa-minus-circle mr-1"></i> Salida
                                </button>
                            </div>
                        </div>

                        <!-- Cantidad -->
                        <div>
                            <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2 ml-1">Cantidad <span class="text-rose-500">*</span></label>
                            <input type="number" wire:model="quantity" min="1"
                                   class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all">
                            @error('quantity') <p class="mt-1.5 text-[10px] text-rose-500 font-bold uppercase ml-1">{{ $message }}</p> @enderror
                        </div>
                            </div>

                            <!-- Razón -->
                            <div>
                                <label class="block text-xs font-bold text-gray-700 uppercase tracking-widest mb-2 ml-1">Motivo / Razón <span class="text-rose-500">*</span></label>
                                <textarea wire:model="reason" rows="3"
                                          class="block w-full px-4 py-3 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:bg-white transition-all"
                                          placeholder="Ej: Reposición de insumos de aseo, producto dañado, limpieza general..."></textarea>
                                @error('reason') <p class="mt-1.5 text-[10px] text-rose-500 font-bold uppercase ml-1">{{ $message }}</p> @enderror
                            </div>

                    <div class="pt-2">
                        <button type="submit" 
                                class="w-full flex items-center justify-center py-4 px-6 rounded-2xl bg-slate-900 text-white font-bold uppercase tracking-widest text-xs hover:bg-slate-800 transition-all shadow-xl shadow-slate-900/20 active:scale-[0.98]">
                            <i class="fas fa-save mr-2"></i> Registrar Movimiento
                        </button>
                    </div>
                </form>
            </div>
        </div>

        <!-- Ayuda/Información -->
        <div class="space-y-6">
            <div class="bg-indigo-900 rounded-2xl p-6 text-white shadow-xl">
                <h3 class="text-lg font-bold mb-4 flex items-center">
                    <i class="fas fa-info-circle mr-2 text-indigo-300"></i> Tipos de Salidas
                </h3>
                <ul class="space-y-4 text-xs text-indigo-100 leading-relaxed">
                    <li class="flex items-start">
                        <span class="w-1.5 h-1.5 rounded-full bg-rose-400 mt-1 mr-2 flex-shrink-0"></span>
                        <p><span class="font-bold text-white">Dañado / Vencido:</span> Usa el tipo "Salida" para productos que ya no sirven.</p>
                    </li>
                    <li class="flex items-start">
                        <span class="w-1.5 h-1.5 rounded-full bg-blue-400 mt-1 mr-2 flex-shrink-0"></span>
                        <p><span class="font-bold text-white">Consumo Hab.:</span> Para amenidades (jabón, champú) o reposición sin costo de minibar.</p>
                    </li>
                    <li class="flex items-start">
                        <span class="w-1.5 h-1.5 rounded-full bg-emerald-400 mt-1 mr-2 flex-shrink-0"></span>
                        <p><span class="font-bold text-white">Entrada:</span> Reposición manual si no usas el módulo de compras.</p>
                    </li>
                </ul>
            </div>

            <a href="{{ route('products.history') }}" class="block group">
                <div class="bg-white rounded-2xl p-6 border border-gray-100 shadow-sm group-hover:shadow-md transition-all border-l-4 border-l-indigo-500">
                    <div class="flex items-center justify-between">
<div>
                            <h4 class="font-bold text-gray-900">Ver Historial</h4>
                            <p class="text-[10px] text-gray-500 uppercase tracking-widest mt-1 font-medium">Consultar movimientos previos</p>
                        </div>
                        <i class="fas fa-chevron-right text-indigo-500 group-hover:translate-x-1 transition-transform"></i>
                    </div>
                </div>
            </a>
        </div>
    </div>
</div>
