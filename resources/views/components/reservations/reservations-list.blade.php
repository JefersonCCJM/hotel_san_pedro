@props([
    'reservations',
])

<div class="hidden lg:block bg-white rounded-xl border border-gray-100 overflow-hidden shadow-sm">
    <div class="overflow-x-auto -mx-6 lg:mx-0">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Cliente</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Habitaciones</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Entrada / Salida</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">Total / Abono</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">Acciones</th>
                </tr>
            </thead>
            <tbody class="bg-white divide-y divide-gray-100">
                @forelse ($reservations as $reservation)
                    @php
                        $isCancelled = method_exists($reservation, 'trashed') && $reservation->trashed();
                        $hasArrival = !$isCancelled
                            && $reservation->stays->contains(
                                static fn ($stay) => in_array((string) ($stay->status ?? ''), ['active', 'pending_checkout'], true),
                            );
                        $roomNumbers = $reservation->reservationRooms
                            ->map(fn ($reservationRoom) => $reservationRoom->room?->room_number)
                            ->filter()
                            ->unique()
                            ->values();

                        $bedsTotal = $reservation->reservationRooms
                            ->sum(fn ($reservationRoom) => (int) ($reservationRoom->room?->beds_count ?? 0));

                        $checkInRaw = $reservation->reservationRooms
                            ->pluck('check_in_date')
                            ->filter()
                            ->sort()
                            ->first();

                        $checkOutRaw = $reservation->reservationRooms
                            ->pluck('check_out_date')
                            ->filter()
                            ->sortDesc()
                            ->first();

                        $checkInLabel = $checkInRaw ? \Carbon\Carbon::parse($checkInRaw)->format('d/m/Y') : 'N/A';
                        $checkOutLabel = $checkOutRaw ? \Carbon\Carbon::parse($checkOutRaw)->format('d/m/Y') : 'N/A';

                        $stayNightsTotal = (float) ($reservation->stay_nights_total ?? 0);
                        $reservationRoomsSubtotal = (float) $reservation->reservationRooms
                            ->sum(fn ($reservationRoom) => (float) ($reservationRoom->subtotal ?? 0));
                        $enteredReservationTotal = (float) ($reservation->total_amount ?? 0);
                        $totalAmount = $enteredReservationTotal > 0
                            ? $enteredReservationTotal
                            : max(0, $reservationRoomsSubtotal, $stayNightsTotal);
                        $depositAmount = $reservation->relationLoaded('payments')
                            ? (float) $reservation->payments->sum('amount')
                            : (float) ($reservation->deposit_amount ?? 0);
                        $balance = max(0, $totalAmount - $depositAmount);
                        $paymentModalPayload = [
                            'id' => (int) $reservation->id,
                            'total_amount_raw' => $totalAmount,
                            'payments_total_raw' => $depositAmount,
                            'balance_raw' => $balance,
                            'can_pay' => true,
                            'payment_url' => route('reservations.register-payment', $reservation),
                        ];
                    @endphp

                    <tr class="transition-colors duration-150 {{ $isCancelled ? 'bg-slate-50/80 hover:bg-slate-100/80' : 'hover:bg-gray-50' }}">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div class="h-10 w-10 rounded-full {{ $isCancelled ? 'bg-slate-200 text-slate-600' : 'bg-emerald-100 text-emerald-600' }} flex items-center justify-center text-sm font-semibold">
                                    {{ $reservation->customer ? strtoupper(substr($reservation->customer->name, 0, 1)) : '?' }}
                                </div>
                                <div class="ml-3 min-w-0">
                                    <div class="text-sm font-semibold {{ $isCancelled ? 'text-slate-700' : 'text-gray-900' }} truncate">
                                        {{ $reservation->customer ? $reservation->customer->name : 'Cliente eliminado' }}
                                    </div>
                                    <div class="flex items-center gap-2 mt-1">
                                        <span class="text-[10px] font-bold uppercase tracking-wider {{ $isCancelled ? 'text-slate-500' : 'text-gray-400' }}">
                                            {{ $reservation->reservation_code ?? 'RES-' . $reservation->id }}
                                        </span>
                                        @if ($isCancelled)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-slate-200 text-slate-700">
                                                <i class="fas fa-ban mr-1"></i> Cancelada
                                            </span>
                                        @elseif ($hasArrival)
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-emerald-100 text-emerald-700">
                                                <i class="fas fa-check-circle mr-1"></i> Llego
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase bg-indigo-100 text-indigo-700">
                                                Reservada
                                            </span>
                                        @endif
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm text-gray-700">
                            @if ($roomNumbers->isNotEmpty())
                                <span class="font-semibold {{ $isCancelled ? 'text-slate-700 line-through decoration-slate-400' : '' }}">
                                    {{ $roomNumbers->implode(', ') }}
                                </span>
                                <span class="text-xs text-gray-500 block">
                                    {{ $roomNumbers->count() }} {{ $roomNumbers->count() === 1 ? 'habitación' : 'habitaciones' }}
                                    @if ($bedsTotal > 0)
                                        · {{ $bedsTotal }} {{ $bedsTotal === 1 ? 'cama' : 'camas' }}
                                    @endif
                                </span>
                            @else
                                <span class="text-gray-400 italic">Sin habitaciones</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm {{ $isCancelled ? 'text-slate-600' : 'text-gray-700' }}">
                            <div><i class="fas fa-sign-in-alt text-emerald-500 mr-2"></i>{{ $checkInLabel }}</div>
                            <div><i class="fas fa-sign-out-alt text-red-500 mr-2"></i>{{ $checkOutLabel }}</div>
                            @if ($isCancelled && $reservation->deleted_at)
                                <div class="text-[10px] text-slate-500 mt-1">
                                    Cancelada: {{ $reservation->deleted_at->format('d/m/Y H:i') }}
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-sm">
                            <div class="flex flex-col space-y-1 min-w-[120px]">
                                <div class="flex items-center justify-between">
                                    <span class="text-gray-500 text-xs uppercase font-bold tracking-wider">Total:</span>
                                    <span class="font-bold {{ $isCancelled ? 'text-slate-700' : 'text-gray-900' }}">${{ number_format($totalAmount, 0, ',', '.') }}</span>
                                </div>
                                <div class="flex items-center justify-between text-xs">
                                    <span class="text-gray-400">Abono:</span>
                                    <span class="{{ $isCancelled ? 'text-slate-600' : 'text-emerald-600' }} font-semibold">${{ number_format($depositAmount, 0, ',', '.') }}</span>
                                </div>
                                <div class="pt-1 mt-1 border-t border-gray-100 flex items-center justify-between">
                                    <span class="text-gray-500 text-[10px] uppercase font-bold">Saldo:</span>
                                    @if ($balance > 0)
                                        <span class="text-xs font-bold px-1.5 py-0.5 rounded {{ $isCancelled ? 'text-slate-700 bg-slate-200' : 'text-red-600 bg-red-50' }}">
                                            ${{ number_format($balance, 0, ',', '.') }}
                                        </span>
                                    @else
                                        <span class="inline-flex items-center px-1.5 py-0.5 rounded text-[10px] font-bold uppercase {{ $isCancelled ? 'bg-slate-200 text-slate-700' : 'bg-emerald-100 text-emerald-700' }}">
                                            <i class="fas fa-check-circle mr-1"></i> Pagado
                                        </span>
                                    @endif
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-2">
                                @if (!$isCancelled)
                                    @if ($hasArrival && $balance > 0)
                                        <button type="button"
                                            onclick='openReservationPaymentModal({{ \Illuminate\Support\Js::from($paymentModalPayload) }})'
                                            class="p-2 text-emerald-600 hover:bg-emerald-50 rounded-lg transition-colors"
                                            title="Registrar pago o abono">
                                            <i class="fas fa-money-bill-wave"></i>
                                        </button>
                                    @endif
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
                                @else
                                    <span class="inline-flex items-center text-[11px] font-semibold text-slate-500 bg-slate-100 px-2 py-1 rounded-md">
                                        <i class="fas fa-lock mr-1"></i> Solo lectura
                                    </span>
                                @endif
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

    @if ($reservations->hasPages())
        <div class="bg-white px-6 py-4 border-t border-gray-100">
            {{ $reservations->appends(['view' => 'list', 'month' => $date->format('Y-m')])->links() }}
        </div>
    @endif
</div>
