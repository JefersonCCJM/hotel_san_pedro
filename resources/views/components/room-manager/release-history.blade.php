<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-900">Historial de Habitaciones Liberadas</h3>
        <p class="text-sm text-gray-500 mt-1">Registro completo de todas las habitaciones que han sido liberadas</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Fecha Liberación</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Habitación</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-3 text-left text-xs font-bold text-gray-500 uppercase tracking-wider">Check In / Out</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Total</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Abono</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Consumos</th>
                    <th class="px-6 py-3 text-right text-xs font-bold text-gray-500 uppercase tracking-wider">Pendiente</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Estado</th>
                    <th class="px-6 py-3 text-center text-xs font-bold text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($releaseHistory as $history)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($history->release_date)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($history->created_at)->format('H:i') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">#{{ $history->room_number }}</div>
                        </td>
                        <td class="px-6 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $history->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $history->customer_identification ?? 'N/A' }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($history->check_in_date)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">Hasta: {{ \Carbon\Carbon::parse($history->check_out_date)->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-bold text-gray-900">${{ number_format($history->total_amount, 0, ',', '.') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-bold text-green-600">${{ number_format($history->deposit, 0, ',', '.') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            <div class="text-sm font-bold text-gray-900">${{ number_format($history->consumptions_total, 0, ',', '.') }}</div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-right">
                            @php
                                $pending = (float) $history->pending_amount;
                                $isCredit = $pending < 0;
                            @endphp
                            <div class="text-sm font-bold {{ $isCredit ? 'text-blue-600' : 'text-red-600' }}">
                                ${{ number_format(abs($pending), 0, ',', '.') }}
                            </div>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php
                                $statusLabels = [
                                    'libre' => 'Libre',
                                    'pendiente_aseo' => 'Pendiente Aseo',
                                    'limpia' => 'Limpia'
                                ];
                                $statusColors = [
                                    'libre' => 'bg-emerald-100 text-emerald-800',
                                    'pendiente_aseo' => 'bg-amber-100 text-amber-800',
                                    'limpia' => 'bg-blue-100 text-blue-800'
                                ];
                            @endphp
                            <span class="px-2 py-1 text-xs font-bold rounded-full {{ $statusColors[$history->target_status] ?? 'bg-gray-100 text-gray-800' }}">
                                {{ $statusLabels[$history->target_status] ?? $history->target_status }}
                            </span>
                        </td>
                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <button 
                                wire:click="viewReleaseHistoryDetail({{ $history->id }})"
                                class="text-blue-600 hover:text-blue-800 font-bold text-sm">
                                <i class="fas fa-eye mr-1"></i>
                                Ver Detalle
                            </button>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="10" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-history text-4xl text-gray-300 mb-4"></i>
                                <p class="text-base font-semibold text-gray-500 mb-1">No hay historial de liberaciones</p>
                                <p class="text-sm text-gray-400">Las habitaciones liberadas aparecerán aquí</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginación -->
    @if($releaseHistory->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $releaseHistory->links() }}
        </div>
    @endif
</div>

