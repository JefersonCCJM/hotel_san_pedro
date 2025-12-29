@section('title', 'Historial de Inventario')
@section('header', 'Historial de Inventario')

<div class="space-y-6" wire:poll.5s>
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6 shadow-sm">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-indigo-50 text-indigo-600">
                    <i class="fas fa-history text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Historial de Inventario</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Seguimiento detallado de entradas y salidas de productos</p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('products.index') }}" 
                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                    <i class="fas fa-box mr-2"></i>
                    Ver Productos
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 shadow-sm">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Grupo</label>
                <select wire:model.live="group" class="block w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos los grupos</option>
                    <option value="ventas">Productos de Venta</option>
                    <option value="aseo">Insumos de Aseo</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Tipo de Movimiento</label>
                <select wire:model.live="type" class="block w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
                    <option value="">Todos los tipos</option>
                    <option value="input">Reposición (Entrada)</option>
                    <option value="output">Baja / Daño (Salida)</option>
                    <option value="sale">Venta Directa</option>
                    <option value="adjustment">Ajuste de Inventario</option>
                    <option value="room_consumption">Consumo en Habitación</option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Categoría</label>
                <select wire:model.live="category_id" class="block w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
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
            </div>

<div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Fecha</label>
                <input type="date" wire:model.live="start_date" 
                       max="{{ date('Y-m-d') }}"
                       class="block w-full px-3 py-2.5 bg-gray-50 border border-gray-200 rounded-xl text-sm focus:outline-none focus:ring-2 focus:ring-indigo-500">
            </div>
        </div>

        <div class="mt-4 flex justify-end">
            <button wire:click="clearFilters" class="text-xs text-gray-500 hover:text-indigo-600 font-medium flex items-center">
                <i class="fas fa-times-circle mr-1"></i> Limpiar Filtros
            </button>
        </div>
    </div>

    <!-- Tabla -->
    <div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
        <div class="overflow-x-auto">
            <table class="min-w-full divide-y divide-gray-100">
                <thead class="bg-gray-50/50 font-bold uppercase text-[10px] text-gray-500 tracking-widest">
                    <tr>
                        <th class="px-6 py-4 text-left">Fecha</th>
                        <th class="px-6 py-4 text-left">Producto</th>
                        <th class="px-6 py-4 text-left">Tipo</th>
                        <th class="px-6 py-4 text-center">Cantidad</th>
                        <th class="px-6 py-4 text-center">Stock Prev.</th>
                        <th class="px-6 py-4 text-center">Stock Nuevo</th>
                        <th class="px-6 py-4 text-left">Responsable / Ref</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-gray-50">
                    @forelse($movements as $movement)
                        <tr class="hover:bg-gray-50/50 transition-colors">
                            <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-600">
                                <span class="font-medium text-gray-900 block">{{ $movement->created_at->format('d/m/Y') }}</span>
                                <span class="text-[10px]">{{ $movement->created_at->format('H:i') }}</span>
                            </td>
                            <td class="px-6 py-4">
                                <div class="flex items-center">
                                    <div class="ml-0">
                                        <div class="text-sm font-bold text-gray-900">{{ $movement->product->name }}</div>
                                        <div class="text-[10px] text-gray-500 uppercase tracking-tighter">{{ $movement->product->category->name ?? 'Sin categoría' }}</div>
                                    </div>
                                </div>
                            </td>
                            <td class="px-6 py-4">
                                @php
                                    $badgeClass = match($movement->type) {
                                        'input' => 'bg-emerald-50 text-emerald-700 border-emerald-100',
                                        'sale' => 'bg-indigo-50 text-indigo-700 border-indigo-100',
                                        'output' => 'bg-rose-50 text-rose-700 border-rose-100',
                                        'adjustment' => 'bg-amber-50 text-amber-700 border-amber-100',
                                        'room_consumption' => 'bg-blue-50 text-blue-700 border-blue-100',
                                        default => 'bg-gray-50 text-gray-700 border-gray-100'
                                    };
                                @endphp
                                <span class="inline-flex items-center px-2.5 py-1 rounded-lg text-[10px] font-bold uppercase tracking-wider border {{ $badgeClass }}">
                                    {{ $movement->translated_type }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center whitespace-nowrap">
                                <span class="text-sm font-bold {{ $movement->quantity > 0 ? 'text-emerald-600' : 'text-rose-600' }}">
                                    {{ $movement->quantity > 0 ? '+' : '' }}{{ $movement->quantity }}
                                </span>
                            </td>
                            <td class="px-6 py-4 text-center text-sm text-gray-500">
                                {{ $movement->previous_stock }}
                            </td>
                            <td class="px-6 py-4 text-center text-sm font-bold text-gray-900">
                                {{ $movement->current_stock }}
                            </td>
                            <td class="px-6 py-4 text-sm text-gray-600">
                                <div class="flex flex-col">
                                    <span class="font-medium text-gray-900">{{ $movement->user->name }}</span>
                                    @if($movement->room)
                                        <span class="text-[10px] text-blue-600 font-bold uppercase">Hab. {{ $movement->room->room_number }}</span>
                                    @endif
                                    @if($movement->reason)
                                        <span class="text-[10px] text-gray-400 italic">"{{ $movement->reason }}"</span>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @empty
                        <tr>
                            <td colspan="7" class="px-6 py-12 text-center text-gray-500">
                                <i class="fas fa-search text-3xl mb-3 block"></i>
                                No se encontraron movimientos en el historial
                            </td>
                        </tr>
                    @endforelse
                </tbody>
            </table>
        </div>
        
        @if($movements->hasPages())
            <div class="px-6 py-4 border-t border-gray-50">
                {{ $movements->links() }}
            </div>
        @endif
    </div>
</div>
