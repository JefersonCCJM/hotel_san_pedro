@props(['product'])

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
        <div class="flex items-center gap-3">
            <div class="text-sm font-black {{ $product->quantity <= 5 ? 'text-rose-600' : 'text-gray-900' }}">
                {{ $product->quantity }} unidades
            </div>
            @can('edit_products')
                <div class="inline-flex items-center rounded-xl border border-gray-200 bg-white overflow-hidden shadow-sm">
                    <button
                        type="button"
                        wire:click="decreaseStock({{ $product->id }})"
                        wire:loading.attr="disabled"
                        class="px-2.5 py-1.5 text-xs font-black text-gray-700 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                        title="Disminuir stock"
                        aria-label="Disminuir stock"
                    >
                        <i class="fas fa-minus"></i>
                    </button>
                    <div class="w-px h-7 bg-gray-200"></div>
                    <button
                        type="button"
                        wire:click="increaseStock({{ $product->id }})"
                        wire:loading.attr="disabled"
                        class="px-2.5 py-1.5 text-xs font-black text-gray-700 hover:bg-gray-50 disabled:opacity-60 disabled:cursor-not-allowed"
                        title="Aumentar stock"
                        aria-label="Aumentar stock"
                    >
                        <i class="fas fa-plus"></i>
                    </button>
                </div>
            @endcan
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

