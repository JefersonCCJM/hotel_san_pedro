<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-bed text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Ventas por Habitación</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Ventas agrupadas por habitación y categoría</p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <a href="{{ route('sales.index') }}" 
                   class="inline-flex items-center justify-center px-4 sm:px-5 py-2.5 rounded-xl border-2 border-gray-200 bg-white text-gray-700 text-sm font-semibold hover:bg-gray-50 hover:border-gray-300 transition-all duration-200">
                    <i class="fas fa-arrow-left mr-2"></i>
                    <span>Volver</span>
                </a>
            </div>
        </div>
    </div>

    <!-- Filtros -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Fecha</label>
                <input type="date" 
                       wire:model.live="date"
                       class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent">
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Habitación</label>
                <select wire:model.live="room_id"
                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white">
                    <option value="">Todas</option>
                    @foreach($rooms as $room)
                        <option value="{{ $room->id }}">Habitación {{ $room->room_number }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Categoría</label>
                <select wire:model.live="category_id"
                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white">
                    <option value="">Todas</option>
                    @foreach($categories as $category)
                        <option value="{{ $category->id }}">{{ $category->name }}</option>
                    @endforeach
                </select>
            </div>
            
            <div>
                <label class="block text-xs font-semibold text-gray-700 uppercase tracking-wider mb-2">Turno</label>
                <select wire:model.live="shift"
                        class="block w-full px-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500 focus:border-transparent appearance-none bg-white">
                    <option value="">Todos</option>
                    <option value="dia">Día</option>
                    <option value="noche">Noche</option>
                </select>
            </div>
        </div>
    </div>

    <!-- Ventas por Habitación -->
    @if(count($roomsData) > 0)
        <div class="space-y-4">
            @foreach($roomsData as $roomData)
                <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
                    <div class="flex items-center justify-between mb-4">
<div>
                            <h3 class="text-lg font-bold text-gray-900">
                                Habitación {{ $roomData['room']->room_number }}
                            </h3>
                            @if($roomData['customer'])
                                <p class="text-sm text-gray-600 mt-1">
                                    Titular: {{ $roomData['customer']->name }}
                                </p>
                            @endif
                        </div>
                        <div class="text-right">
                            <p class="text-xs text-gray-500">Total Consumo</p>
                            <p class="text-xl font-bold text-blue-600">
                                ${{ number_format($roomData['total'], 2, ',', '.') }}
                            </p>
                        </div>
                    </div>

                    <!-- Totales por Categoría -->
                    @if(count($roomData['byCategory']) > 0)
                        <div class="mb-4 flex gap-2">
                            @foreach($roomData['byCategory'] as $categoryName => $categoryData)
                                <span class="inline-flex items-center px-3 py-1.5 rounded-lg text-xs font-medium 
                                    {{ $categoryName === 'Bebidas' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                    {{ $categoryName }}: ${{ number_format($categoryData['total'], 2, ',', '.') }}
                                </span>
                            @endforeach
                        </div>
                    @endif

                    <!-- Lista de Ventas -->
                    <div class="space-y-2">
                        @foreach($roomData['sales'] as $sale)
                            <div class="border border-gray-200 rounded-lg p-3">
                                <div class="flex items-center justify-between">
                                    <div class="flex-1">
                                        <p class="text-sm font-medium text-gray-900">
                                            {{ $sale->sale_date->format('d/m/Y H:i') }}
                                        </p>
                                        <p class="text-xs text-gray-500">
                                            {{ $sale->user->name }} - 
                                            <span class="capitalize">{{ $sale->shift }}</span>
                                        </p>
                                        <div class="mt-1 flex flex-wrap gap-1">
                                            @foreach($sale->items->groupBy('product.category.name') as $catName => $items)
                                                <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium 
                                                    {{ $catName === 'Bebidas' ? 'bg-blue-100 text-blue-700' : 'bg-amber-100 text-amber-700' }}">
                                                    {{ $catName }} ({{ $items->sum('quantity') }})
                                                </span>
                                            @endforeach
                                        </div>
                                    </div>
                                    <div class="text-right ml-4">
                                        <p class="text-sm font-bold text-gray-900">
                                            ${{ number_format($sale->total, 2, ',', '.') }}
                                        </p>
                                        @if($sale->debt_status === 'pendiente')
                                            <span class="inline-flex items-center px-2 py-0.5 rounded-md text-xs font-medium bg-red-100 text-red-700">
                                                Pendiente
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            @endforeach
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 p-16 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <i class="fas fa-bed text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 mb-1">No se encontraron ventas</h3>
            <p class="text-xs text-gray-500">Intente ajustar los filtros de búsqueda</p>
        </div>
    @endif
</div>
