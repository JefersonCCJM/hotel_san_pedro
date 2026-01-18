@props(['releaseHistoryDetail', 'releaseHistoryDetailModal'])

{{-- Modal para ver el detalle completo del historial de liberación --}}
<div 
    x-data="{ open: @entangle('releaseHistoryDetailModal') }"
    x-show="open"
    x-cloak
    @keydown.escape.window="open = false"
    class="fixed inset-0 z-50 overflow-y-auto"
    style="display: none;"
    aria-labelledby="modal-title"
    role="dialog"
    aria-modal="true">
    
    <div class="flex items-end justify-center min-h-screen pt-4 px-4 pb-20 text-center sm:block sm:p-0">
        {{-- Backdrop --}}
        <div 
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="open = false"
            class="fixed inset-0 bg-gray-500 bg-opacity-75 transition-opacity"
            aria-hidden="true">
        </div>

        <span class="hidden sm:inline-block sm:align-middle sm:h-screen" aria-hidden="true">&#8203;</span>

        {{-- Modal --}}
        <div 
            x-show="open"
            x-transition:enter="ease-out duration-300"
            x-transition:enter-start="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            x-transition:enter-end="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave="ease-in duration-200"
            x-transition:leave-start="opacity-100 translate-y-0 sm:scale-100"
            x-transition:leave-end="opacity-0 translate-y-4 sm:translate-y-0 sm:scale-95"
            class="inline-block align-bottom bg-white rounded-2xl text-left overflow-hidden shadow-xl transform transition-all sm:my-8 sm:align-middle sm:max-w-4xl sm:w-full">
            
            @if($releaseHistoryDetail)
            <div class="bg-white">
                {{-- Header --}}
                <div class="px-6 py-4 border-b border-gray-200 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <i class="fas fa-door-open text-2xl text-blue-600"></i>
                        <div>
                            <h3 class="text-xl font-bold text-gray-900">Habitación #{{ $releaseHistoryDetail['room_number'] ?? 'N/A' }}</h3>
                            <p class="text-sm text-gray-500">Detalle de liberación - {{ \Carbon\Carbon::parse($releaseHistoryDetail['release_date'] ?? now())->format('d/m/Y') }}</p>
                        </div>
                    </div>
                    <button 
                        @click="$wire.closeReleaseHistoryDetail()"
                        class="text-gray-400 hover:text-gray-600 transition-colors">
                        <i class="fas fa-times text-xl"></i>
                    </button>
                </div>

                <div class="px-6 py-6 max-h-[calc(100vh-200px)] overflow-y-auto custom-scrollbar">
                    {{-- Información del Cliente --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Información del Cliente</h4>
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Nombre</p>
                                <p class="text-sm font-bold text-gray-900">{{ $releaseHistoryDetail['customer_name'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Identificación</p>
                                <p class="text-sm font-bold text-gray-900">{{ $releaseHistoryDetail['customer_identification'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Teléfono</p>
                                <p class="text-sm font-bold text-gray-900">{{ $releaseHistoryDetail['customer_phone'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Email</p>
                                <p class="text-sm font-bold text-gray-900">{{ $releaseHistoryDetail['customer_email'] ?? 'N/A' }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Información de la Reserva --}}
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Información de la Reserva</h4>
                        <div class="grid grid-cols-3 gap-4 mb-4">
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs text-gray-500 uppercase mb-1">Check In</p>
                                <p class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($releaseHistoryDetail['check_in_date'] ?? now())->format('d/m/Y') }}</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs text-gray-500 uppercase mb-1">Check Out</p>
                                <p class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($releaseHistoryDetail['check_out_date'] ?? now())->format('d/m/Y') }}</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl">
                                <p class="text-xs text-gray-500 uppercase mb-1">Huéspedes</p>
                                <p class="text-sm font-bold text-gray-900">{{ $releaseHistoryDetail['guests_count'] ?? 0 }}</p>
                            </div>
                        </div>
                        <div class="grid grid-cols-4 gap-4">
                            <div class="p-4 bg-blue-50 rounded-xl text-center">
                                <p class="text-xs text-blue-600 uppercase mb-1">Total Hospedaje</p>
                                <p class="text-lg font-bold text-blue-900">${{ number_format($releaseHistoryDetail['total_amount'] ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-4 bg-green-50 rounded-xl text-center">
                                <p class="text-xs text-green-600 uppercase mb-1">Abono</p>
                                <p class="text-lg font-bold text-green-900">${{ number_format($releaseHistoryDetail['deposit'] ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-4 bg-gray-50 rounded-xl text-center">
                                <p class="text-xs text-gray-600 uppercase mb-1">Consumos</p>
                                <p class="text-lg font-bold text-gray-900">${{ number_format($releaseHistoryDetail['consumptions_total'] ?? 0, 0, ',', '.') }}</p>
                            </div>
                            <div class="p-4 {{ (float)($releaseHistoryDetail['pending_amount'] ?? 0) < 0 ? 'bg-blue-50' : 'bg-red-50' }} rounded-xl text-center">
                                <p class="text-xs {{ (float)($releaseHistoryDetail['pending_amount'] ?? 0) < 0 ? 'text-blue-600' : 'text-red-600' }} uppercase mb-1">Pendiente</p>
                                <p class="text-lg font-bold {{ (float)($releaseHistoryDetail['pending_amount'] ?? 0) < 0 ? 'text-blue-900' : 'text-red-900' }}">${{ number_format(abs((float)($releaseHistoryDetail['pending_amount'] ?? 0)), 0, ',', '.') }}</p>
                            </div>
                        </div>
                    </div>

                    {{-- Consumos --}}
                    @if(!empty($releaseHistoryDetail['sales_data']))
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Consumos</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Producto</th>
                                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Cantidad</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Precio Unitario</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Total</th>
                                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Estado</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($releaseHistoryDetail['sales_data'] ?? [] as $sale)
                                        @php
                                            $sale = is_array($sale) ? $sale : (array) $sale;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ $sale['product_name'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-center text-gray-900">{{ $sale['quantity'] ?? 0 }}</td>
                                            <td class="px-4 py-3 text-sm text-right text-gray-900">${{ number_format($sale['unit_price'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">${{ number_format($sale['total'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-center">
                                                @if($sale['is_paid'] ?? false)
                                                    <span class="px-2 py-1 text-xs font-bold rounded-full bg-emerald-100 text-emerald-800">Pagado</span>
                                                @else
                                                    <span class="px-2 py-1 text-xs font-bold rounded-full bg-red-100 text-red-800">Pendiente</span>
                                                @endif
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Abonos --}}
                    @if(!empty($releaseHistoryDetail['deposits_data']))
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Historial de Abonos</h4>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Monto</th>
                                        <th class="px-4 py-2 text-center text-xs font-bold text-gray-500 uppercase">Método</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Notas</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($releaseHistoryDetail['deposits_data'] ?? [] as $deposit)
                                        @php
                                            $deposit = is_array($deposit) ? $deposit : (array) $deposit;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm text-gray-900">{{ \Carbon\Carbon::parse($deposit['created_at'])->format('d/m/Y H:i') }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-gray-900">${{ number_format($deposit['amount'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-center">
                                                <span class="px-2 py-1 text-xs font-bold rounded-full {{ $deposit['payment_method'] === 'efectivo' ? 'bg-emerald-100 text-emerald-800' : 'bg-blue-100 text-blue-800' }}">
                                                    {{ ucfirst($deposit['payment_method'] ?? 'N/A') }}
                                                </span>
                                            </td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $deposit['notes'] ?? '-' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Historial de Devoluciones --}}
                    @php
                        $refundsHistory = $releaseHistoryDetail['refunds_history'] ?? [];
                        $totalRefunds = $releaseHistoryDetail['total_refunds'] ?? 0;
                    @endphp
                    @if(!empty($refundsHistory) && count($refundsHistory) > 0)
                    <div class="mb-6">
                        <div class="flex items-center justify-between mb-4">
                            <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider">Historial de Devoluciones</h4>
                            <span class="text-xs text-gray-500 font-medium">Total: <strong class="text-blue-600">${{ number_format($totalRefunds, 0, ',', '.') }}</strong></span>
                        </div>
                        <div class="overflow-x-auto">
                            <table class="min-w-full divide-y divide-gray-200">
                                <thead class="bg-gray-50">
                                    <tr>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Fecha</th>
                                        <th class="px-4 py-2 text-right text-xs font-bold text-gray-500 uppercase">Monto</th>
                                        <th class="px-4 py-2 text-left text-xs font-bold text-gray-500 uppercase">Registrado Por</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-gray-200">
                                    @foreach($refundsHistory as $refund)
                                        @php
                                            $refund = is_array($refund) ? $refund : (array) $refund;
                                        @endphp
                                        <tr>
                                            <td class="px-4 py-3 text-sm font-bold text-gray-900">{{ $refund['created_at'] ?? 'N/A' }}</td>
                                            <td class="px-4 py-3 text-sm text-right font-bold text-blue-600">${{ number_format($refund['amount'] ?? 0, 0, ',', '.') }}</td>
                                            <td class="px-4 py-3 text-sm text-gray-500">{{ $refund['created_by'] ?? 'N/A' }}</td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                    </div>
                    @endif

                    {{-- Huéspedes --}}
                    @if(!empty($releaseHistoryDetail['guests_data']))
                    <div class="mb-6">
                        <h4 class="text-sm font-bold text-gray-900 uppercase tracking-wider mb-4">Huéspedes</h4>
                        <div class="space-y-3">
                            @foreach($releaseHistoryDetail['guests_data'] ?? [] as $guest)
                                @php
                                    $guest = is_array($guest) ? $guest : (array) $guest;
                                @endphp
                                <div class="p-4 bg-gray-50 rounded-xl">
                                    <div class="grid grid-cols-2 gap-4">
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase mb-1">Nombre</p>
                                            <p class="text-sm font-bold text-gray-900">{{ $guest['name'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase mb-1">Identificación</p>
                                            <p class="text-sm font-bold text-gray-900">{{ $guest['identification'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase mb-1">Teléfono</p>
                                            <p class="text-sm font-bold text-gray-900">{{ $guest['phone'] ?? 'N/A' }}</p>
                                        </div>
                                        <div>
                                            <p class="text-xs text-gray-500 uppercase mb-1">Email</p>
                                            <p class="text-sm font-bold text-gray-900">{{ $guest['email'] ?? 'N/A' }}</p>
                                        </div>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                    @endif

                    {{-- Información de Liberación --}}
                    <div class="border-t border-gray-200 pt-4">
                        <div class="grid grid-cols-2 gap-4">
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Liberado Por</p>
                                <p class="text-sm font-bold text-gray-900">{{ $releaseHistoryDetail['released_by_name'] ?? 'N/A' }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Estado Final</p>
                                <span class="inline-block px-3 py-1 text-xs font-bold rounded-full 
                                    {{ ($releaseHistoryDetail['target_status'] ?? '') === 'libre' ? 'bg-emerald-100 text-emerald-800' : 
                                       (($releaseHistoryDetail['target_status'] ?? '') === 'limpia' ? 'bg-blue-100 text-blue-800' : 'bg-amber-100 text-amber-800') }}">
                                    {{ ucfirst(str_replace('_', ' ', $releaseHistoryDetail['target_status'] ?? 'N/A')) }}
                                </span>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Fecha de Liberación</p>
                                <p class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($releaseHistoryDetail['release_date'] ?? now())->format('d/m/Y') }}</p>
                            </div>
                            <div>
                                <p class="text-xs text-gray-500 uppercase mb-1">Hora de Registro</p>
                                <p class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($releaseHistoryDetail['created_at'] ?? now())->format('H:i:s') }}</p>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Footer --}}
                <div class="px-6 py-4 bg-gray-50 border-t border-gray-200 flex justify-end">
                    <button 
                        @click="$wire.closeReleaseHistoryDetail()"
                        class="px-4 py-2 bg-gray-600 text-white rounded-xl text-sm font-bold hover:bg-gray-700 transition-colors">
                        Cerrar
                    </button>
                </div>
            </div>
            @endif
        </div>
    </div>
</div>

