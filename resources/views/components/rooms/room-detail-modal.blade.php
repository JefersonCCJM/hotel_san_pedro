{{-- Room Detail Modal Component --}}
<div x-show="roomDetailModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="roomDetailModal = false" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div
            class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all">
            @if ($detailData)
                <div class="px-8 py-6 border-b border-gray-100 flex items-center justify-between">
                    <div class="flex items-center space-x-3">
                        <div
                            class="w-10 h-10 rounded-xl bg-blue-50 text-blue-600 flex items-center justify-center">
                            <i class="fas fa-door-open"></i>
                        </div>
                        <h3 class="text-xl font-bold text-gray-900">Habitación
                            {{ $detailData['room']['room_number'] }}</h3>
                    </div>
                    <button @click="roomDetailModal = false" class="text-gray-400 hover:text-gray-900"><i
                            class="fas fa-times text-xl"></i></button>
                </div>

                <div class="p-8 space-y-8">
                    @if ($detailData['reservation'])
                        <div class="space-y-8">
                            <!-- Información de Huéspedes -->
                            @if(isset($detailData['guests']) && count($detailData['guests']) > 0)
                                <div class="space-y-4">
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Huéspedes en la Habitación</h4>
                                    <div class="grid grid-cols-1 md:grid-cols-2 gap-3">
                                        @foreach($detailData['guests'] as $guest)
                                            <div class="p-4 bg-gray-50 rounded-xl border border-gray-100">
                                                <div class="flex items-center space-x-3 mb-2">
                                                    <div class="w-10 h-10 rounded-full bg-blue-100 text-blue-600 flex items-center justify-center">
                                                        <i class="fas fa-user"></i>
                                                    </div>
                                                    <div class="flex-1">
                                                        <p class="text-sm font-bold text-gray-900">{{ $guest['name'] ?? 'N/A' }}</p>
                                                        <p class="text-xs text-gray-500 mt-0.5">
                                                            <i class="fas fa-id-card mr-1"></i>{{ $guest['identification'] ?? 'S/N' }}
                                                        </p>
                                                    </div>
                                                </div>
                                                @if(isset($guest['phone']) && $guest['phone'] !== 'S/N')
                                                    <div class="mt-2 text-xs text-gray-600">
                                                        <i class="fas fa-phone mr-1"></i>{{ $guest['phone'] }}
                                                    </div>
                                                @endif
                                            </div>
                                        @endforeach
                                    </div>
                                </div>
                            @endif

                            <!-- Cards de Resumen -->
                            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                                <div class="p-4 bg-gray-50 rounded-xl text-center">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Hospedaje</p>
                                    <p class="text-sm font-bold text-gray-900">
                                        ${{ number_format($detailData['total_hospedaje'], 0, ',', '.') }}</p>
                                </div>
                                <div class="p-4 bg-green-50 rounded-xl text-center relative group">
                                    <p class="text-[9px] font-bold text-green-600 uppercase mb-1">Abono</p>
                                    <p class="text-sm font-bold text-green-700">
                                        ${{ number_format($detailData['abono_realizado'], 0, ',', '.') }}</p>
                                    <button
                                        @click="addDeposit({{ $detailData['reservation']['id'] }})"
                                        class="absolute top-1 right-1 opacity-0 group-hover:opacity-100 transition-opacity text-green-600 hover:text-green-800"
                                        title="Agregar Abono">
                                        <i class="fas fa-plus-circle text-[10px]"></i>
                                    </button>
                                </div>
                                <div class="p-4 bg-gray-50 rounded-xl text-center">
                                    <p class="text-[9px] font-bold text-gray-400 uppercase mb-1">Consumos</p>
                                    <p class="text-sm font-bold text-gray-900">
                                        ${{ number_format($detailData['sales_total'], 0, ',', '.') }}</p>
                                </div>
                                <div class="p-4 bg-red-50 rounded-xl text-center">
                                    <p class="text-[9px] font-bold text-red-600 uppercase mb-1">Pendiente</p>
                                    <p class="text-sm font-black text-red-700">
                                        ${{ number_format($detailData['total_debt'], 0, ',', '.') }}</p>
                                </div>
                            </div>

                            <!-- Sección de Consumos -->
                            <div class="space-y-4">
                                <div class="flex items-center justify-between pb-2 border-b border-gray-100">
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Detalle
                                        de Consumos</h4>
                                    <button wire:click="toggleAddSale"
                                        class="text-[10px] font-bold text-blue-600 uppercase">+ Agregar
                                        Consumo</button>
                                </div>

                                @if ($showAddSale)
                                    <div class="p-6 bg-gray-50 rounded-xl border border-gray-100 space-y-4">
                                        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
                                            <div class="md:col-span-2" wire:ignore>
                                                <label
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Producto</label>
                                                <select wire:model="newSale.product_id" id="detail_product_id"
                                                    class="w-full"></select>
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Cantidad</label>
                                                <input type="number" wire:model="newSale.quantity"
                                                    min="1"
                                                    class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                            </div>
                                            <div>
                                                <label
                                                    class="text-[10px] font-bold text-gray-400 uppercase tracking-widest ml-1">Método
                                                    de Pago</label>
                                                <select wire:model="newSale.payment_method"
                                                    class="w-full px-4 py-2.5 bg-white border border-gray-200 rounded-xl text-sm font-bold focus:ring-2 focus:ring-blue-500 outline-none">
                                                    <option value="efectivo">Efectivo</option>
                                                    <option value="transferencia">Transferencia</option>
                                                    <option value="pendiente">Pendiente (Cargar a cuenta)</option>
                                                </select>
                                            </div>
                                        </div>
                                        <button wire:click="addSale"
                                            class="w-full bg-blue-600 text-white py-3 rounded-xl text-[10px] font-bold uppercase tracking-widest hover:bg-blue-700 transition-all shadow-sm">Cargar
                                            Consumo</button>
                                    </div>
                                @endif

                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                    <table class="min-w-full divide-y divide-gray-50">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-4 py-2 text-left text-[9px] font-bold text-gray-400 uppercase">
                                                    Producto</th>
                                                <th
                                                    class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">
                                                    Cant</th>
                                                <th
                                                    class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">
                                                    Pago</th>
                                                <th
                                                    class="px-4 py-2 text-right text-[9px] font-bold text-gray-400 uppercase">
                                                    Total</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach ($detailData['sales'] as $sale)
                                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                                    <td class="px-4 py-3 text-xs font-bold text-gray-900">
                                                        {{ $sale['product']['name'] }}</td>
                                                    <td
                                                        class="px-4 py-3 text-xs text-center font-bold text-gray-500">
                                                        {{ $sale['quantity'] }}</td>
                                                    <td class="px-4 py-3 text-xs text-center">
                                                        @if ($sale['is_paid'])
                                                            <div class="flex flex-col items-center space-y-1">
                                                                <span
                                                                    class="text-[9px] font-bold uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">{{ $sale['payment_method'] }}</span>
                                                                <button
                                                                    @click="confirmRevertSale({{ $sale['id'] }})"
                                                                    class="text-[8px] font-bold text-gray-400 underline uppercase tracking-tighter hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">Anular
                                                                    Pago</button>
                                                            </div>
                                                        @else
                                                            <div class="flex flex-col items-center space-y-1">
                                                                <span
                                                                    class="text-[9px] font-bold uppercase text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Pendiente</span>
                                                                <button
                                                                    @click="confirmPaySale({{ $sale['id'] }})"
                                                                    class="text-[8px] font-bold text-blue-600 underline uppercase tracking-tighter hover:text-blue-800">Marcar
                                                                    Pago</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                    <td
                                                        class="px-4 py-3 text-xs text-right font-black text-gray-900">
                                                        ${{ number_format($sale['total'], 0, ',', '.') }}</td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>

                            <!-- Historial de Abonos -->
                            @if(isset($detailData['deposit_history']) && count($detailData['deposit_history']) > 0)
                                <div class="space-y-4 pt-4 border-t border-gray-100">
                                    <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Historial de Abonos</h4>
                                    <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                        <table class="min-w-full divide-y divide-gray-50">
                                            <thead class="bg-gray-50">
                                                <tr>
                                                    <th class="px-4 py-2 text-left text-[9px] font-bold text-gray-400 uppercase">Fecha</th>
                                                    <th class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">Monto</th>
                                                    <th class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">Método</th>
                                                    <th class="px-4 py-2 text-left text-[9px] font-bold text-gray-400 uppercase">Notas</th>
                                                    <th class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">Acciones</th>
                                                </tr>
                                            </thead>
                                            <tbody class="divide-y divide-gray-50">
                                                @foreach($detailData['deposit_history'] as $deposit)
                                                    <tr class="hover:bg-gray-50/50 transition-colors group">
                                                        <td class="px-4 py-3 text-xs font-bold text-gray-900">
                                                            {{ $deposit['created_at'] }}
                                                        </td>
                                                        <td class="px-4 py-3 text-xs text-center font-bold text-green-700">
                                                            ${{ number_format($deposit['amount'], 0, ',', '.') }}
                                                        </td>
                                                        <td class="px-4 py-3 text-xs text-center">
                                                            <span class="text-[9px] font-bold uppercase px-2 py-0.5 rounded-full
                                                                {{ $deposit['payment_method'] === 'efectivo' ? 'bg-green-100 text-green-700' : 'bg-blue-100 text-blue-700' }}">
                                                                {{ ucfirst($deposit['payment_method']) }}
                                                            </span>
                                                        </td>
                                                        <td class="px-4 py-3 text-xs text-gray-600">
                                                            {{ $deposit['notes'] ?? '-' }}
                                                        </td>
                                                        <td class="px-4 py-3 text-xs text-center">
                                                            <div class="flex items-center justify-center space-x-2">
                                                                <button
                                                                    onclick="editDepositRecord({{ $deposit['id'] }}, {{ $deposit['amount'] }}, {{ json_encode($deposit['payment_method']) }}, {{ json_encode($deposit['notes'] ?? '') }})"
                                                                    class="text-blue-600 hover:text-blue-800 transition-colors"
                                                                    title="Editar">
                                                                    <i class="fas fa-edit text-xs"></i>
                                                                </button>
                                                                <button
                                                                    onclick="confirmDeleteDeposit({{ $deposit['id'] }})"
                                                                    class="text-red-600 hover:text-red-800 transition-colors"
                                                                    title="Eliminar">
                                                                    <i class="fas fa-trash text-xs"></i>
                                                                </button>
                                                            </div>
                                                        </td>
                                                    </tr>
                                                @endforeach
                                            </tbody>
                                        </table>
                                    </div>
                                </div>
                            @endif

                            <!-- Historial de Estadía -->
                            <div class="space-y-4 pt-4 border-t border-gray-100">
                                <h4 class="text-xs font-bold text-gray-900 uppercase tracking-widest">Estado de
                                    Pago por Noches</h4>
                                <div class="max-h-48 overflow-y-auto custom-scrollbar">
                                    <table class="min-w-full divide-y divide-gray-50">
                                        <thead class="bg-gray-50">
                                            <tr>
                                                <th
                                                    class="px-4 py-2 text-left text-[9px] font-bold text-gray-400 uppercase">
                                                    Fecha</th>
                                                <th
                                                    class="px-4 py-2 text-center text-[9px] font-bold text-gray-400 uppercase">
                                                    Valor Noche</th>
                                                <th
                                                    class="px-4 py-2 text-right text-[9px] font-bold text-gray-400 uppercase">
                                                    Estado</th>
                                            </tr>
                                        </thead>
                                        <tbody class="divide-y divide-gray-50">
                                            @foreach ($detailData['stay_history'] as $stay)
                                                <tr class="hover:bg-gray-50/50 transition-colors group">
                                                    <td class="px-4 py-3 text-xs font-bold text-gray-900">
                                                        {{ $stay['date'] }}</td>
                                                    <td
                                                        class="px-4 py-3 text-xs text-center font-bold text-gray-500">
                                                        ${{ number_format($stay['price'], 0, ',', '.') }}</td>
                                                    <td class="px-4 py-3 text-xs text-right">
                                                        @if ($stay['is_paid'])
                                                            <div class="flex flex-col items-end space-y-1">
                                                                <span
                                                                    class="text-[9px] font-bold uppercase text-emerald-600 bg-emerald-50 px-2 py-0.5 rounded-full">Pagado</span>
                                                                <button
                                                                    @click="confirmRevertNight({{ $detailData['reservation']['id'] }}, {{ $stay['price'] }})"
                                                                    class="text-[8px] font-bold text-gray-400 underline uppercase tracking-tighter hover:text-red-600 opacity-0 group-hover:opacity-100 transition-opacity">Anular
                                                                    Pago</button>
                                                            </div>
                                                        @else
                                                            <div class="flex flex-col items-end space-y-1">
                                                                <span
                                                                    class="text-[9px] font-bold uppercase text-red-600 bg-red-50 px-2 py-0.5 rounded-full">Pendiente</span>
                                                                <button
                                                                    @click="confirmPayStay({{ $detailData['reservation']['id'] }}, {{ $stay['price'] }})"
                                                                    class="text-[8px] font-bold text-blue-600 underline uppercase tracking-tighter hover:text-blue-800">Pagar
                                                                    Noche</button>
                                                            </div>
                                                        @endif
                                                    </td>
                                                </tr>
                                            @endforeach
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    @else
                        <div class="text-center py-12">
                            <i class="fas fa-calendar-times text-4xl text-gray-200 mb-4"></i>
                            <p class="text-gray-500 font-medium">No hay reserva activa para esta fecha</p>
                        </div>
                    @endif
                </div>
            @endif
        </div>
    </div>
</div>

