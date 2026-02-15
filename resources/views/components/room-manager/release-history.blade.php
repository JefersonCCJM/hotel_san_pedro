<div class="bg-white rounded-xl border border-gray-100 shadow-sm overflow-hidden">
    <div class="px-6 py-4 bg-gray-50 border-b border-gray-200">
        <h3 class="text-lg font-bold text-gray-900">Historial de Liberaciones</h3>
        <p class="text-sm text-gray-500 mt-1">Registro completo de habitaciones liberadas y su estado financiero</p>
    </div>

    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-200">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Fecha</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Habitacion</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Huesped</th>
                    <th class="px-4 py-3 text-left text-xs font-bold text-gray-700 uppercase tracking-wider">Estancia</th>
                    <th class="px-4 py-3 text-right text-xs font-bold text-gray-700 uppercase tracking-wider">Financiero</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Estado</th>
                    <th class="px-4 py-3 text-center text-xs font-bold text-gray-700 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-200">
                @forelse($releaseHistory ?? [] as $history)
                    <tr class="hover:bg-gray-50 transition-colors">
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">{{ \Carbon\Carbon::parse($history->release_date)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500">{{ \Carbon\Carbon::parse($history->created_at)->format('H:i') }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm font-bold text-gray-900">#{{ $history->room_number }}</div>
                        </td>
                        <td class="px-4 py-4">
                            <div class="text-sm font-bold text-gray-900">{{ $history->customer_name }}</div>
                            <div class="text-xs text-gray-500">{{ $history->customer_identification ?? 'N/A' }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap">
                            <div class="text-sm text-gray-900">{{ \Carbon\Carbon::parse($history->check_in_date)->format('d/m/Y') }}</div>
                            <div class="text-xs text-gray-500"> {{ \Carbon\Carbon::parse($history->check_out_date)->format('d/m/Y') }}</div>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-right">
                            <div class="text-xs text-gray-500 mb-1">Total: ${{ number_format($history->total_amount, 0, ',', '.') }}</div>
                            <div class="text-sm font-bold text-gray-900">${{ number_format($history->total_amount, 0, ',', '.') }}</div>
                            @if($history->deposit > 0)
                                <div class="text-xs text-green-600 font-medium">+${{ number_format($history->deposit, 0, ',', '.') }}</div>
                            @endif
                            @if($history->consumptions_total > 0)
                                <div class="text-xs text-orange-600 font-medium">+${{ number_format($history->consumptions_total, 0, ',', '.') }}</div>
                            @endif
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
                            @php
                                $pending = (float) $history->pending_amount;
                                $isCredit = $pending < 0;
                                $statusLabels = [
                                    'libre' => ['text' => 'Libre', 'color' => 'bg-emerald-100 text-emerald-800'],
                                    'pendiente_aseo' => ['text' => 'Pendiente Aseo', 'color' => 'bg-amber-100 text-amber-800'],
                                    'mantenimiento' => ['text' => 'Mantenimiento', 'color' => 'bg-red-100 text-red-800'],
                                ];
                                $statusKey = $history->target_status ?? 'libre';
                                $statusConfig = $statusLabels[$statusKey] ?? $statusLabels['libre'];
                            @endphp
                            <span class="px-2 py-1 text-xs font-bold rounded-full {{ $statusConfig['color'] }}">
                                {{ $statusConfig['text'] }}
                            </span>
                        </td>
                        <td class="px-4 py-3 whitespace-nowrap text-center">
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
                                <p class="text-sm text-gray-400">Las habitaciones liberadas apareceran aqui</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <!-- Paginacion -->
    @if($releaseHistory && method_exists($releaseHistory, 'hasPages') && $releaseHistory->hasPages())
        <div class="px-6 py-4 bg-gray-50 border-t border-gray-200">
            {{ $releaseHistory->links('pagination::tailwind', ['pageName' => 'releaseHistoryPage']) }}
        </div>
    @endif
</div>


