@props([
    'reservations'
])

<div class="hidden lg:block bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
    <div class="overflow-x-auto -mx-6 lg:mx-0">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Habitación</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Entrada / Salida</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total / Abono</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse($reservations as $reservation)
                <tr class="hover:bg-gray-50 transition-colors duration-150">
                    <td class="px-6 py-4 whitespace-nowrap">
                        <div class="flex items-center">
                            <div class="h-10 w-10 rounded-full bg-emerald-100 text-emerald-600 flex items-center justify-center text-sm font-semibold">
                                {{ $reservation->customer ? strtoupper(substr($reservation->customer->name, 0, 1)) : '?' }}
                            </div>
                            <div class="ml-3">
                                <div class="text-sm font-semibold text-gray-900">{{ $reservation->customer ? $reservation->customer->name : 'Cliente eliminado' }}</div>
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        @if($reservation->room)
                            <span class="font-semibold">{{ $reservation->room->room_number }}</span>
                            <span class="text-xs text-gray-500 block">{{ $reservation->room->beds_count }} {{ $reservation->room->beds_count == 1 ? 'Cama' : 'Camas' }}</span>
                        @else
                            <span class="text-gray-400 italic">Habitación eliminada</span>
                        @endif
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                        <div><i class="fas fa-sign-in-alt text-emerald-500 mr-2"></i>{{ $reservation->check_in_date ? $reservation->check_in_date->format('d/m/Y') : 'N/A' }}</div>
                        <div><i class="fas fa-sign-out-alt text-red-500 mr-2"></i>{{ $reservation->check_out_date ? $reservation->check_out_date->format('d/m/Y') : 'N/A' }}</div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-sm">
                        <div class="flex flex-col space-y-1 min-w-[120px]">
                            <div class="flex items-center justify-between">
                                <span class="text-gray-500 text-xs uppercase font-bold tracking-wider">Total:</span>
                                <span class="font-bold text-gray-900">${{ number_format($reservation->total_amount, 0, ',', '.') }}</span>
                            </div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-gray-400">Abono:</span>
                                <span class="text-emerald-600 font-semibold">${{ number_format($reservation->deposit, 0, ',', '.') }}</span>
                            </div>
                            <div class="pt-1 mt-1 border-t border-gray-100 flex items-center justify-between">
                                <span class="text-gray-500 text-[10px] uppercase font-bold">Saldo:</span>
                                @php
                                    $balance = $reservation->total_amount - $reservation->deposit;
                                @endphp
                                @if($balance > 0)
                                    <span class="text-xs text-red-600 font-bold bg-red-50 px-1.5 py-0.5 rounded">${{ number_format($balance, 0, ',', '.') }}</span>
                                @else
                                    <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold bg-emerald-100 text-emerald-700 uppercase">
                                        <i class="fas fa-check-circle mr-1"></i> Pagado
                                    </span>
                                @endif
                            </div>
                        </div>
                    </td>
                    <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                        <div class="flex items-center justify-end space-x-2">
                            <a href="{{ route('reservations.download', $reservation) }}"
                               class="p-2 text-red-600 hover:bg-red-50 rounded-lg transition-colors"
                               title="Descargar PDF">
                                <i class="fas fa-file-pdf"></i>
                            </a>
                            <a href="{{ route('reservations.edit', $reservation) }}"
                               class="p-2 text-indigo-600 hover:bg-indigo-50 rounded-lg transition-colors"
                               title="Editar">
                                <i class="fas fa-edit"></i>
                            </a>
                            <button type="button"
                                    onclick="openDeleteModal({{ $reservation->id }})"
                                    class="p-2 text-orange-600 hover:bg-orange-50 rounded-lg transition-colors"
                                    title="Cancelar">
                                <i class="fas fa-ban"></i>
                            </button>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="5" class="px-6 py-16 text-center text-gray-500">No hay reservas registradas.</td>
                </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    @if($reservations->hasPages())
    <div class="bg-white px-6 py-4 border-t border-gray-100">
        {{ $reservations->links() }}
    </div>
    @endif
</div>

