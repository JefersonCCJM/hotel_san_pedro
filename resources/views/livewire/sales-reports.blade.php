<div class="space-y-4 sm:space-y-6">
    <!-- Header -->
    <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
        <div class="flex flex-col sm:flex-row sm:items-center sm:justify-between gap-4">
            <div class="flex items-center space-x-3 sm:space-x-4">
                <div class="p-2.5 sm:p-3 rounded-xl bg-blue-50 text-blue-600">
                    <i class="fas fa-chart-bar text-lg sm:text-xl"></i>
                </div>
                <div>
                    <h1 class="text-xl sm:text-2xl font-bold text-gray-900">Reportes de Ventas</h1>
                    <p class="text-xs sm:text-sm text-gray-500 mt-1">Ventas del día {{ $currentDate->format('d/m/Y') }}</p>
                </div>
            </div>
            
            <div class="flex gap-2">
                <form wire:submit.prevent="loadData" class="flex gap-2">
                    <input type="date" 
                           wire:model.live="date"
                           class="px-3 py-2.5 border border-gray-300 rounded-xl text-sm text-gray-900 focus:outline-none focus:ring-2 focus:ring-blue-500">
                </form>
                <a href="{{ route('sales.index') }}" 
                   class="inline-flex items-center justify-center px-4 py-2.5 rounded-xl border border-gray-300 text-sm font-semibold text-gray-700 bg-white hover:bg-gray-50 transition-all">
                    <i class="fas fa-arrow-left mr-2"></i>
                    Volver
                </a>
            </div>
        </div>
    </div>

    <!-- Estadísticas Generales -->
    <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">Total Ventas</p>
                    <p class="text-2xl font-bold text-gray-900 mt-1">{{ count($sales) }}</p>
                </div>
                <div class="p-3 rounded-lg bg-blue-50 text-blue-600">
                    <i class="fas fa-shopping-cart text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">Total Recaudado</p>
                    <p class="text-2xl font-bold text-green-600 mt-1">${{ number_format($totalSales, 2, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-lg bg-green-50 text-green-600">
                    <i class="fas fa-dollar-sign text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">Turno Día</p>
                    <p class="text-2xl font-bold text-yellow-600 mt-1">${{ number_format($totalByShift['dia'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-lg bg-yellow-50 text-yellow-600">
                    <i class="fas fa-sun text-xl"></i>
                </div>
            </div>
        </div>

        <div class="bg-white rounded-xl border border-gray-100 p-4">
            <div class="flex items-center justify-between">
                <div>
                    <p class="text-xs font-semibold text-gray-500 uppercase">Turno Noche</p>
                    <p class="text-2xl font-bold text-indigo-600 mt-1">${{ number_format($totalByShift['noche'] ?? 0, 2, ',', '.') }}</p>
                </div>
                <div class="p-3 rounded-lg bg-indigo-50 text-indigo-600">
                    <i class="fas fa-moon text-xl"></i>
                </div>
            </div>
        </div>
    </div>

    <!-- Por Recepcionista -->
    @if(count($byReceptionist) > 0)
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Ventas por Recepcionista</h2>
            <div class="space-y-3">
                @foreach($byReceptionist as $userId => $userSales)
                    @php
                        $user = $userSales->first()->user;
                        $userTotal = $userSales->sum('total');
                    @endphp
                    <div class="border border-gray-200 rounded-lg p-4">
                        <div class="flex items-center justify-between">
<div>
                                <p class="text-sm font-semibold text-gray-900">{{ $user->name }}</p>
                                <p class="text-xs text-gray-500">{{ count($userSales) }} venta(s)</p>
                            </div>
                            <p class="text-lg font-bold text-gray-900">${{ number_format($userTotal, 2, ',', '.') }}</p>
                        </div>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Por Método de Pago -->
    @if(count($totalByPaymentMethod) > 0)
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Ventas por Método de Pago</h2>
            <div class="grid grid-cols-1 sm:grid-cols-2 lg:grid-cols-4 gap-4">
                @foreach($totalByPaymentMethod as $method => $total)
                    <div class="border border-gray-200 rounded-lg p-4">
                        <p class="text-xs font-semibold text-gray-500 uppercase mb-1">{{ ucfirst($method) }}</p>
                        <p class="text-xl font-bold text-gray-900">${{ number_format($total, 2, ',', '.') }}</p>
                    </div>
                @endforeach
            </div>
        </div>
    @endif

    <!-- Lista de Ventas -->
    @if(count($sales) > 0)
        <div class="bg-white rounded-xl border border-gray-100 p-4 sm:p-6">
            <h2 class="text-lg font-bold text-gray-900 mb-4">Detalle de Ventas</h2>
            <div class="overflow-x-auto">
                <table class="min-w-full divide-y divide-gray-200">
                    <thead class="bg-gray-50">
                        <tr>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Fecha</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Recepcionista</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Habitación</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Turno</th>
                            <th class="px-4 py-3 text-left text-xs font-semibold text-gray-700 uppercase">Método</th>
                            <th class="px-4 py-3 text-right text-xs font-semibold text-gray-700 uppercase">Total</th>
                        </tr>
                    </thead>
                    <tbody class="bg-white divide-y divide-gray-200">
                        @foreach($sales as $sale)
                            <tr>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $sale->sale_date->format('d/m/Y H:i') }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">{{ $sale->user->name }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900">
                                    @if($sale->room)
                                        Hab. {{ $sale->room->room_number }}
                                    @else
                                        Normal
                                    @endif
                                </td>
                                <td class="px-4 py-3 text-sm text-gray-900 capitalize">{{ $sale->shift }}</td>
                                <td class="px-4 py-3 text-sm text-gray-900 capitalize">{{ $sale->payment_method }}</td>
                                <td class="px-4 py-3 text-sm font-medium text-gray-900 text-right">${{ number_format($sale->total, 2, ',', '.') }}</td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>
    @else
        <div class="bg-white rounded-xl border border-gray-100 p-16 text-center">
            <div class="inline-flex items-center justify-center w-16 h-16 rounded-full bg-gray-100 mb-4">
                <i class="fas fa-chart-bar text-2xl text-gray-400"></i>
            </div>
            <h3 class="text-sm font-semibold text-gray-900 mb-1">No hay ventas para esta fecha</h3>
            <p class="text-xs text-gray-500">Seleccione otra fecha para ver los reportes</p>
        </div>
    @endif
</div>
