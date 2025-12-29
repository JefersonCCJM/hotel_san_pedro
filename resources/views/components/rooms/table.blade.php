{{-- Room Manager Table Component --}}
<div class="bg-white rounded-xl border border-gray-100 overflow-hidden">
    <div class="overflow-x-auto">
        <table class="min-w-full divide-y divide-gray-100">
            <thead class="bg-gray-50">
                <tr>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Habitación</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Estado</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Estado de Limpieza</th>
                    <th class="px-6 py-4 text-center text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Ventilación</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Huésped Actual / Info</th>
                    <th class="px-6 py-4 text-left text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Cuenta</th>
                    <th class="px-6 py-4 text-right text-xs font-semibold text-gray-500 uppercase tracking-wider">
                        Acciones</th>
                </tr>
            </thead>
            <tbody class="divide-y divide-gray-200">
                @forelse($rooms as $room)
                    <tr class="{{ $room->display_status->cardBgColor() }} transition-colors duration-150 group">
                        <td class="px-6 py-4 whitespace-nowrap">
                            <div class="flex items-center">
                                <div
                                    class="h-10 w-10 rounded-lg bg-gray-100 flex items-center justify-center mr-3 text-gray-400 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors">
                                    <i class="fas fa-door-closed"></i>
                                </div>
                                <div wire:click="openRoomDetail({{ $room->id }})" class="cursor-pointer">
                                    <div class="text-sm font-semibold text-gray-900">Hab. {{ $room->room_number }}
                                    </div>
                                    <div class="text-xs text-gray-500">
                                        {{ $room->beds_count }} {{ $room->beds_count == 1 ? 'Cama' : 'Camas' }} •
                                        Cap. {{ $room->max_capacity }}
                                    </div>
                                </div>
                            </div>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $room->display_status->color() }}">
                                <span class="w-1.5 h-1.5 rounded-full mr-2"
                                    style="background-color: currentColor"></span>
                                {{ $room->display_status->label() }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @php($cleaning = $room->cleaning_status_for_date ?? $room->cleaningStatus($date ?? null))
                            <span
                                class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold {{ $cleaning['color'] }}">
                                <i class="fas {{ $cleaning['icon'] }} mr-1.5"></i>
                                {{ $cleaning['label'] }}
                            </span>
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-center">
                            @if ($room->ventilation_type)
                                <span
                                    class="inline-flex items-center px-2.5 py-1 rounded-full text-xs font-semibold bg-blue-50 text-blue-700">
                                    <i class="fas fa-wind mr-1.5"></i>
                                    {{ $room->ventilation_type->label() }}
                                </span>
                            @else
                                <span class="text-xs text-gray-400 italic">No asignado</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if (
                                ($room->display_status === \App\Enums\RoomStatus::OCUPADA ||
                                    $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT) &&
                                    isset($room->current_reservation) &&
                                    $room->current_reservation)
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-semibold text-gray-900">{{ $room->current_reservation->customer->name ?? 'N/A' }}</span>
                                    <span class="text-xs text-blue-600 font-medium">
                                        Salida:
                                        {{ \Carbon\Carbon::parse($room->current_reservation->check_out_date)->format('d/m/Y') }}
                                    </span>
                                </div>
                            @else
                                <span class="text-xs text-gray-400 italic">Sin arrendatario</span>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap">
                            @if (
                                ($room->display_status === \App\Enums\RoomStatus::OCUPADA ||
                                    $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT) &&
                                    isset($room->current_reservation) &&
                                    $room->current_reservation)
                                <div class="flex flex-col space-y-1">
                                    @if ($room->is_night_paid)
                                        <span
                                            class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-emerald-100 text-emerald-700 border border-emerald-200">
                                            <i class="fas fa-moon mr-1"></i> NOCHE PAGA
                                        </span>
                                    @else
                                        <span
                                            class="inline-flex items-center w-fit px-2 py-0.5 rounded-full text-[10px] font-bold bg-red-100 text-red-700 border border-red-200">
                                            <i class="fas fa-moon mr-1"></i> NOCHE PENDIENTE
                                        </span>
                                    @endif

                                    @if ($room->total_debt > 0)
                                        <div class="flex flex-col">
                                            <span
                                                class="text-[9px] font-bold text-gray-400 uppercase tracking-widest">Saldo
                                                Total</span>
                                            <span
                                                class="text-sm font-bold text-red-700">${{ number_format($room->total_debt, 0, ',', '.') }}</span>
                                        </div>
                                    @else
                                        <span
                                            class="inline-flex items-center px-2 py-1 rounded-full text-xs font-semibold bg-emerald-50 text-emerald-700 w-fit">
                                            <i class="fas fa-check-circle mr-1"></i> Al día
                                        </span>
                                    @endif
                                </div>
                            @else
                                <div class="flex flex-col">
                                    <span
                                        class="text-sm font-semibold text-gray-900">${{ number_format($room->active_prices[1] ?? 0, 0, ',', '.') }}</span>
                                    <span class="text-xs text-gray-400">precio base</span>
                                </div>
                            @endif
                        </td>

                        <td class="px-6 py-4 whitespace-nowrap text-right text-sm font-medium">
                            <div class="flex items-center justify-end space-x-3">
                                @if (
                                    $room->display_status === \App\Enums\RoomStatus::LIBRE &&
                                        (!Carbon\Carbon::parse($date)->isPast() || Carbon\Carbon::parse($date)->isToday()))
                                    <button wire:click="openQuickRent({{ $room->id }})"
                                        class="text-blue-600 hover:text-blue-700 transition-colors"
                                        title="Arrendar">
                                        <i class="fas fa-key"></i>
                                    </button>
                                @endif

                                @if (
                                    $room->display_status === \App\Enums\RoomStatus::PENDIENTE_CHECKOUT &&
                                        isset($room->current_reservation) &&
                                        $room->current_reservation)
                                    <button wire:click="continueStay({{ $room->id }})"
                                        class="text-emerald-600 hover:text-emerald-700 transition-colors"
                                        title="Continúa">
                                        <i class="fas fa-redo-alt"></i>
                                    </button>
                                    <button wire:click="cancelReservation({{ $room->id }})"
                                        class="text-red-600 hover:text-red-700 transition-colors"
                                        title="Cancelar Reserva">
                                        <i class="fas fa-times"></i>
                                    </button>
                                @endif

                                <a href="{{ route('rooms.edit', $room) }}"
                                    class="text-indigo-600 hover:text-indigo-700 transition-colors"
                                    title="Editar">
                                    <i class="fas fa-edit"></i>
                                </a>
                                @if ($room->display_status !== \App\Enums\RoomStatus::LIBRE)
                                    <button
                                        @click="confirmRelease({{ $room->id }}, '{{ $room->room_number }}', {{ $room->total_debt ?? 0 }}, {{ $room->current_reservation->id ?? 'null' }})"
                                        class="text-red-600 hover:text-red-700 transition-colors" title="Liberar">
                                        <i class="fas fa-sign-out-alt"></i>
                                    </button>
                                @endif
                            </div>
                        </td>
                    </tr>
                @empty
                    <tr>
                        <td colspan="7" class="px-6 py-12 text-center">
                            <div class="flex flex-col items-center">
                                <i class="fas fa-door-closed text-4xl text-gray-300 mb-4"></i>
                                <p class="text-base font-semibold text-gray-500 mb-1">No se encontraron
                                    habitaciones</p>
                                <p class="text-sm text-gray-400">Registra tu primera habitación para comenzar</p>
                            </div>
                        </td>
                    </tr>
                @endforelse
            </tbody>
        </table>
    </div>

    <div class="bg-white px-6 py-4 border-t border-gray-100">
        {{ $rooms->links() }}
    </div>
</div>

