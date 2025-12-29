<div class="space-y-4 sm:space-y-6" wire:poll.5s>
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-boxes text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Gestión de Inventario</h1>
                    <div class="flex items-center space-x-2 mt-1">
                        <span class="text-xs sm:text-sm text-gray-500">
                            <span class="font-semibold text-gray-900">{{ $products->total() }}</span> productos registrados
                        </span>
                        <span class="text-gray-300 hidden sm:inline">•</span>
                        <span class="text-xs sm:text-sm text-gray-500 hidden sm:inline">
                            <i class="fas fa-chart-line mr-1"></i> Panel de control
                        </span>
                    </div>
                </div>
            </div>
            
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('products.history') }}" 
                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-indigo-200 bg-indigo-50 text-indigo-700 text-sm font-semibold hover:bg-indigo-100 transition-all duration-200 shadow-sm">
                    <i class="fas fa-history mr-2"></i>
                    <span>Historial</span>
                </a>
                <a href="{{ route('products.adjustments') }}" 
                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-amber-200 bg-amber-50 text-amber-700 text-sm font-semibold hover:bg-amber-100 transition-all duration-200 shadow-sm">
                    <i class="fas fa-exchange-alt mr-2"></i>
                    <span>Movimientos</span>
                </a>
                @can('create_products')
                <a href="{{ route('products.create') }}" 
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-blue-600 bg-blue-600 text-white text-sm font-semibold hover:bg-blue-700 hover:border-blue-700 transition-all duration-200 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-blue-500 shadow-sm hover:shadow-md">
                    <i class="fas fa-plus mr-2"></i>
                    <span>Nuevo Producto</span>
                </a>
                @endcan
            </div>
        </div>
    </div>
    
    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Buscar</label>
                <div class="relative">
                    <div class="absolute inset-y-0 left-0 pl-3 flex items-center pointer-events-none">
                        <i class="fas fa-search text-gray-400 text-sm"></i>
                    </div>
                    <input type="text" wire:model.live.debounce.300ms="search" 
                           class="block w-full pl-10 pr-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 transition-all"
                           placeholder="Nombre o SKU...">
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Categoría</label>
                <div class="relative">
                    <select wire:model.live="category_id"
                            class="block w-full pl-3 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none">
                        <option value="">Todas las categorías</option>
                        @php
                            $aseoKeywords = ['aseo', 'limpieza', 'amenities', 'insumo', 'papel', 'jabon', 'cloro', 'mantenimiento'];
                            $aseoCats = $categories->filter(function($cat) use ($aseoKeywords) {
                                $name = strtolower($cat->name);
                                foreach ($aseoKeywords as $kw) if (str_contains($name, $kw)) return true;
                                return false;
                            });
                            $ventaCats = $categories->diff($aseoCats);
                        @endphp

                        @if($ventaCats->isNotEmpty())
                            <optgroup label="PRODUCTOS DE VENTA">
                                @foreach($ventaCats as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif

                        @if($aseoCats->isNotEmpty())
                            <optgroup label="INSUMOS DE ASEO">
                                @foreach($aseoCats as $category)
                                    <option value="{{ $category->id }}">{{ $category->name }}</option>
                                @endforeach
                            </optgroup>
                        @endif
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Estado</label>
                <div class="relative">
                    <select wire:model.live="status"
                            class="block w-full pl-3 pr-10 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-blue-500 appearance-none">
                        <option value="">Todos los estados</option>
                        <option value="active">Activo</option>
                        <option value="inactive">Inactivo</option>
                        <option value="discontinued">Descontinuado</option>
                    </select>
                    <div class="absolute inset-y-0 right-0 pr-3 flex items-center pointer-events-none">
                        <i class="fas fa-chevron-down text-gray-400 text-xs"></i>
                    </div>
                </div>
            </div>
            
            <div class="flex items-end">
                <button wire:click="$set('search', ''); $set('category_id', ''); $set('status', '');"
                        class="w-full inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-200 bg-white text-gray-500 text-sm font-semibold hover:bg-gray-50 transition-all">
                    <i class="fas fa-times mr-2"></i> Limpiar
                </button>
            </div>
        </div>
    </div>
    
    <!-- Tabla de productos -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50 font-bold uppercase text-[10px] text-gray-500 tracking-widest">
                    <tr>
                        <th class="px-6 py-4 text-left">Producto</th>
                        <th class="px-6 py-4 text-left">Categoría</th>
                        <th class="px-6 py-4 text-left">Stock</th>
                        <th class="px-6 py-4 text-left">Precio</th>
                        <th class="px-6 py-4 text-left">Estado</th>
                        <th class="px-6 py-4 text-right">Acciones</th>
                    </tr>
                </thead>
                <tbody class="bg-white divide-y divide-gray-50">
                    @forelse($products as $product)
                    <tr class="hover:bg-gray-50/50 transition-colors duration-150">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-xl bg-gray-100 flex items-center justify-center mr-3 border border-gray-200/50">
                                    <i class="fas fa-box text-gray-400 text-sm"></i>
                                </div>
                                <div class="text-sm font-bold text-gray-900">{{ $product->name }}</div>
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider bg-gray-100 text-gray-600 border border-gray-200"
                                  style="background-color: {{ ($product->category->color ?? '#6B7280') }}15; color: {{ $product->category->color ?? '#6B7280' }}; border-color: {{ ($product->category->color ?? '#6B7280') }}30;">
                                {{ $product->category->name ?? 'Sin categoría' }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-black {{ $product->quantity <= 5 ? 'text-rose-600' : 'text-gray-900' }}">
                                {{ $product->quantity }} unidades
                            </div>
                            @if($product->quantity <= 5 && $product->quantity > 0)
                                <div class="text-[10px] text-rose-500 font-bold uppercase tracking-tighter mt-0.5">Stock bajo</div>
                            @elseif($product->quantity == 0)
                                <div class="text-[10px] text-rose-600 font-black uppercase tracking-tighter mt-0.5">Agotado</div>
                            @endif
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-sm font-bold text-gray-900">
                            ${{ number_format($product->price, 2) }}
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            @php
                                $statusClasses = match($product->status) {
                                    'active' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                    'inactive' => 'bg-gray-100 text-gray-600 border-gray-200',
                                    default => 'bg-rose-50 text-rose-700 border-rose-100'
                                };
                                $statusLabels = match($product->status) {
                                    'active' => 'Activo',
                                    'inactive' => 'Inactivo',
                                    default => 'Descontinuado'
                                };
                            @endphp
                            <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border {{ $statusClasses }}">
                                {{ $statusLabels }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="flex items-center justify-end space-x-2">
                                <a href="{{ route('products.show', $product) }}" class="p-2 text-indigo-400 hover:text-indigo-600 transition-colors"><i class="fas fa-eye"></i></a>
                                <a href="{{ route('products.edit', $product) }}" class="p-2 text-blue-400 hover:text-blue-600 transition-colors"><i class="fas fa-edit"></i></a>
                                <button type="button" 
                                        @click="$dispatch('confirm-delete', { 
                                            id: {{ $product->id }}, 
                                            name: '{{ addslashes($product->name) }}' 
                                        })"
                                        class="p-2 text-rose-400 hover:text-rose-600 hover:bg-rose-50 rounded-lg transition-all duration-200"
                                        title="Eliminar producto">
                                    <i class="fas fa-trash"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                    @empty
                    <tr>
                        <td colspan="6" class="px-6 py-12 text-center text-gray-500">
                            <i class="fas fa-boxes text-4xl mb-4 block text-gray-300"></i>
                            No se encontraron productos
                        </td>
                    </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        @if($products->hasPages())
            <div class="px-6 py-4 border-t border-gray-50">
                {{ $products->links() }}
            </div>
        @endif
    </div>

    <!-- Modal de Confirmación Estilizado -->
    <div x-data="{ 
            show: false, 
            productId: null, 
            productName: '',
            init() {
                window.addEventListener('confirm-delete', (e) => {
                    this.productId = e.detail.id;
                    this.productName = e.detail.name;
                    this.show = true;
                });
            },
            confirm() {
                @this.call('deleteProduct', this.productId);
                this.show = false;
            }
         }" 
         x-show="show" 
         x-cloak
         class="fixed inset-0 z-[100] overflow-y-auto" 
         aria-labelledby="modal-title" role="dialog" aria-modal="true">
        
        <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
            <!-- Overlay -->
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0" 
                 x-transition:enter-end="opacity-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100" 
                 x-transition:leave-end="opacity-0" 
                 class="fixed inset-0 bg-gray-500/75 backdrop-blur-sm transition-opacity" 
                 @click="show = false"></div>

            <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

            <!-- Modal Content -->
            <div x-show="show" 
                 x-transition:enter="ease-out duration-300" 
                 x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave="ease-in duration-200" 
                 x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100" 
                 x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95" 
                 class="inline-block align-bottom bg-white rounded-3xl text-left overflow-hidden shadow-2xl transform transition-all sm:my-8 sm:align-middle sm:max-w-lg sm:w-full">
                
                <div class="bg-white px-6 pt-6 pb-4 sm:p-8">
                    <div class="sm:flex sm:items-start">
                        <div class="mx-auto flex-shrink-0 flex items-center justify-center h-14 w-14 rounded-2xl bg-rose-50 sm:mx-0 sm:h-12 sm:w-12">
                            <i class="fas fa-exclamation-triangle text-rose-600 text-xl"></i>
                        </div>
                        <div class="mt-4 text-center sm:mt-0 sm:ml-6 sm:text-left">
                            <h3 class="text-xl leading-6 font-black text-gray-900" id="modal-title">
                                Eliminar Producto
                            </h3>
                            <div class="mt-3">
                                <p class="text-sm text-gray-500 leading-relaxed">
                                    ¿Estás seguro de que deseas eliminar <span class="font-bold text-gray-900" x-text="productName"></span>? 
                                    Esta acción eliminará el registro del inventario y no se podrá deshacer.
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="bg-gray-50/50 px-6 py-4 sm:px-8 sm:flex sm:flex-row-reverse gap-3">
                    <button type="button" 
                            @click="confirm()"
                            class="w-full inline-flex justify-center items-center px-6 py-3 rounded-xl border border-transparent shadow-sm text-sm font-bold text-white bg-rose-600 hover:bg-rose-700 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-rose-500 transition-all duration-200">
                        <i class="fas fa-trash-alt mr-2"></i>
                        Confirmar Eliminación
                    </button>
                    <button type="button" 
                            @click="show = false"
                            class="mt-3 sm:mt-0 w-full inline-flex justify-center items-center px-6 py-3 rounded-xl border border-gray-200 shadow-sm text-sm font-bold text-gray-700 bg-white hover:bg-gray-50 focus:outline-none focus:ring-2 focus:ring-offset-2 focus:ring-indigo-500 transition-all duration-200">
                        Cancelar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>
