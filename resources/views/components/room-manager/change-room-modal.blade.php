@props(['changeRoomModal', 'changeRoomData', 'availableRoomsForChange'])

@if ($changeRoomModal)
    <div
        x-data="{ selectedRoom: null }"
        x-show="$wire.changeRoomModal"
        x-transition:enter="transition ease-out duration-300"
        x-transition:enter-start="opacity-0"
        x-transition:enter-end="opacity-100"
        x-transition:leave="transition ease-in duration-200"
        x-transition:leave-start="opacity-100"
        x-transition:leave-end="opacity-0"
        class="fixed inset-0 z-50 overflow-y-auto"
        style="display: none;">
        <div class="flex min-h-screen items-center justify-center p-4">
            <!-- Backdrop -->
            <div class="fixed inset-0 bg-black bg-opacity-50" @click="$wire.cancelChangeRoom()"></div>

            <!-- Modal -->
            <div class="relative bg-white rounded-xl shadow-xl max-w-lg w-full max-h-[90vh] overflow-hidden">
                <!-- Header -->
                <div class="bg-gradient-to-r from-purple-600 to-purple-700 px-6 py-4">
                    <div class="flex items-center justify-between">
                        <div class="flex items-center space-x-3">
                            <div class="w-10 h-10 bg-white/20 rounded-lg flex items-center justify-center">
                                <i class="fas fa-exchange-alt text-white"></i>
                            </div>
                            <div>
                                <h3 class="text-lg font-semibold text-white">Cambiar Habitacion</h3>
                                <p class="text-purple-100 text-sm">
                                    Hab. {{ $changeRoomData['room_number'] ?? '—' }}
                                    &nbsp;&mdash;&nbsp;
                                    {{ $changeRoomData['customer_name'] ?? '' }}
                                    <span class="font-mono text-purple-200">{{ $changeRoomData['reservation_code'] ?? '' }}</span>
                                </p>
                            </div>
                        </div>
                        <button type="button" @click="$wire.cancelChangeRoom()"
                            class="text-white/80 hover:text-white transition-colors">
                            <i class="fas fa-times text-xl"></i>
                        </button>
                    </div>
                </div>

                <!-- Body -->
                <div class="p-6 max-h-[55vh] overflow-y-auto">
                    <p class="text-xs text-gray-500 mb-4">
                        Selecciona la habitacion a la que deseas mover esta reserva.
                        Los pagos existentes no se veran afectados.
                    </p>

                    @if (count($availableRoomsForChange) === 0)
                        <p class="text-center text-sm text-gray-500 py-4">No hay habitaciones libres disponibles.</p>
                    @else
                        <div class="grid grid-cols-2 gap-3">
                            @foreach ($availableRoomsForChange as $avRoom)
                                <button
                                    type="button"
                                    @click="selectedRoom = {{ $avRoom['id'] }}"
                                    :class="selectedRoom === {{ $avRoom['id'] }}
                                        ? 'border-purple-500 bg-purple-50 ring-2 ring-purple-400'
                                        : 'border-gray-200 bg-white hover:border-purple-300 hover:bg-purple-50'"
                                    class="flex flex-col items-center justify-center p-4 rounded-lg border-2 transition-all cursor-pointer">
                                    <span class="text-2xl font-black text-gray-800">{{ $avRoom['room_number'] }}</span>
                                    @if ($avRoom['type_name'])
                                        <span class="text-[11px] text-gray-500 mt-0.5">{{ $avRoom['type_name'] }}</span>
                                    @endif
                                    <span class="mt-1.5 inline-flex items-center gap-1 text-[10px] font-semibold text-emerald-600">
                                        <i class="fas fa-check-circle text-[9px]"></i> Libre
                                    </span>
                                </button>
                            @endforeach
                        </div>
                    @endif
                </div>

                <!-- Footer -->
                <div class="bg-gray-50 px-6 py-4 border-t border-gray-200">
                    <div class="flex items-center justify-end space-x-3">
                        <button type="button" @click="$wire.cancelChangeRoom()"
                            class="px-4 py-2 text-sm font-medium text-gray-700 bg-white border border-gray-300 rounded-lg hover:bg-gray-50 transition-colors">
                            Cancelar
                        </button>
                        <button
                            type="button"
                            :disabled="!selectedRoom"
                            @click="selectedRoom && $wire.submitChangeRoom(selectedRoom)"
                            :class="selectedRoom
                                ? 'bg-purple-600 border-purple-600 hover:bg-purple-700 cursor-pointer'
                                : 'bg-purple-300 border-purple-300 cursor-not-allowed'"
                            class="px-4 py-2 text-sm font-medium text-white border rounded-lg transition-colors">
                            <i class="fas fa-exchange-alt mr-2"></i> Confirmar Cambio
                        </button>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endif
