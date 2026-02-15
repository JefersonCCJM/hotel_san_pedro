@props(['roomDailyHistoryData'])

<div x-show="roomDailyHistoryModal" class="fixed inset-0 z-50 overflow-y-auto" x-cloak>
    @if($roomDailyHistoryData)
    <div class="flex items-center justify-center min-h-screen p-4">
        <div @click="$wire.closeRoomDailyHistory()" class="fixed inset-0 bg-gray-900/60 backdrop-blur-sm"></div>
        <div class="relative bg-white rounded-2xl shadow-2xl w-full max-w-2xl overflow-hidden transform transition-all max-h-[90vh] flex flex-col">
            {{-- Header --}}
            <div class="flex items-center justify-between border-b border-gray-100 px-6 py-4 bg-gradient-to-r from-gray-50 to-white">
                <div class="flex items-center space-x-3">
                    <div class="w-10 h-10 rounded-xl bg-gray-100 text-gray-700 flex items-center justify-center">
                        <i class="fas fa-history"></i>
                    </div>
                    <div>
                        <h3 class="text-xl font-bold text-gray-900">Historial del Dia</h3>
                        <p class="text-xs text-gray-500 mt-0.5">
                            Hab. {{ $roomDailyHistoryData['room']['room_number'] }} - {{ $roomDailyHistoryData['date_formatted'] }}
                        </p>
                    </div>
                </div>
                <button @click="$wire.closeRoomDailyHistory()" class="text-gray-400 hover:text-gray-900">
                    <i class="fas fa-times text-xl"></i>
                </button>
            </div>

            {{-- Body: Scrollable content --}}
            <div class="overflow-y-auto flex-1 px-6 py-4">
                @if($roomDailyHistoryData['total_releases'] > 0)
                    <div class="mb-4 flex items-center justify-between">
                        <span class="text-sm font-semibold text-gray-700">
                            {{ $roomDailyHistoryData['total_releases'] }} 
                            {{ $roomDailyHistoryData['total_releases'] == 1 ? 'liberacion' : 'liberaciones' }}
                        </span>
                    </div>

                    {{-- Timeline de liberaciones --}}
                    <div class="space-y-4">
                        @foreach($roomDailyHistoryData['releases'] as $index => $release)
                            <div class="relative pl-8 pb-4 border-l-2 border-gray-200 last:border-l-0 last:pb-0">
                                {{-- Time marker --}}
                                <div class="absolute left-0 top-1 w-3 h-3 bg-blue-600 rounded-full -translate-x-[7px] border-2 border-white shadow-sm"></div>
                                
                                {{-- Card de liberacion --}}
                                <div class="bg-gray-50 rounded-lg border border-gray-200 p-4 hover:border-gray-300 transition-colors">
                                    {{-- Header de la liberacion --}}
                                    <div class="flex items-start justify-between mb-3">
                                        <div class="flex items-center space-x-2">
                                            <i class="fas fa-clock text-xs text-gray-400"></i>
                                            <span class="text-sm font-bold text-gray-900">{{ $release['released_at'] }}</span>
                                        </div>
                                        @if($release['is_paid'])
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200">
                                                <i class="fas fa-check-circle mr-1"></i>
                                                Pagado
                                            </span>
                                        @else
                                            <span class="inline-flex items-center px-2 py-1 rounded-md text-[10px] font-bold text-amber-700 bg-amber-50 border border-amber-200">
                                                <i class="fas fa-exclamation-triangle mr-1"></i>
                                                Pendiente: ${{ number_format($release['pending_amount'], 0, ',', '.') }}
                                            </span>
                                        @endif
                                    </div>

                                    {{-- Cliente --}}
                                    <div class="mb-3">
                                        <div class="flex items-center space-x-2 mb-1">
                                            <i class="fas fa-user text-xs text-gray-400"></i>
                                            <span class="text-sm font-semibold text-gray-900">{{ $release['customer_name'] }}</span>
                                        </div>
                                        @if($release['customer_identification'] && $release['customer_identification'] !== 'N/A')
                                            <span class="text-xs text-gray-500 ml-5">{{ $release['customer_identification'] }}</span>
                                        @endif
                                    </div>

                                    {{-- Detalles de estadia --}}
                                    @if($release['check_in_date'] && $release['check_out_date'])
                                        <div class="flex items-center space-x-4 text-xs text-gray-600 mb-3">
                                            <div class="flex items-center space-x-1">
                                                <i class="fas fa-calendar-check text-[10px]"></i>
                                                <span>Check-in: {{ $release['check_in_date'] }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <i class="fas fa-calendar-times text-[10px]"></i>
                                                <span>Check-out: {{ $release['check_out_date'] }}</span>
                                            </div>
                                            <div class="flex items-center space-x-1">
                                                <i class="fas fa-users text-[10px]"></i>
                                                <span>{{ $release['guests_count'] }} {{ $release['guests_count'] == 1 ? 'huesped' : 'huespedes' }}</span>
                                            </div>
                                        </div>
                                    @endif

                                    {{-- Informacion financiera --}}
                                    <div class="grid grid-cols-2 gap-3 mb-3">
                                        <div class="p-2 bg-white rounded border border-gray-100">
                                            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Total Hospedaje</div>
                                            <div class="text-sm font-bold text-gray-900">${{ number_format($release['total_amount'], 0, ',', '.') }}</div>
                                        </div>
                                        <div class="p-2 bg-white rounded border border-gray-100">
                                            <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Abonos</div>
                                            <div class="text-sm font-bold text-emerald-600">${{ number_format($release['deposit'], 0, ',', '.') }}</div>
                                        </div>
                                        @if($release['has_consumptions'])
                                            <div class="p-2 bg-white rounded border border-gray-100">
                                                <div class="text-[10px] font-bold text-gray-500 uppercase tracking-widest mb-1">Consumos</div>
                                                <div class="text-sm font-bold text-blue-600">${{ number_format($release['consumptions_total'], 0, ',', '.') }}</div>
                                            </div>
                                        @endif
                                        @if(!$release['is_paid'])
                                            <div class="p-2 bg-white rounded border border-amber-200 bg-amber-50">
                                                <div class="text-[10px] font-bold text-amber-700 uppercase tracking-widest mb-1">Pendiente</div>
                                                <div class="text-sm font-bold text-amber-700">${{ number_format($release['pending_amount'], 0, ',', '.') }}</div>
                                            </div>
                                        @endif
                                    </div>

                                    {{-- Footer: Operacion --}}
                                    <div class="flex items-center justify-between pt-2 border-t border-gray-200">
                                        <div class="flex items-center space-x-2 text-xs text-gray-500">
                                            <i class="fas fa-user-circle text-[10px]"></i>
                                            <span>Liberado por: <span class="font-semibold">{{ $release['released_by'] }}</span></span>
                                        </div>
                                        <div class="flex items-center space-x-2">
                                            <span class="text-[10px] font-semibold text-gray-600 uppercase">Estado posterior:</span>
                                            @if($release['target_status'] === 'free_clean')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold text-emerald-700 bg-emerald-50 border border-emerald-200">
                                                    Limpia
                                                </span>
                                            @elseif($release['target_status'] === 'pending_cleaning')
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold text-yellow-700 bg-yellow-50 border border-yellow-200">
                                                    Pendiente aseo
                                                </span>
                                            @else
                                                <span class="inline-flex items-center px-2 py-0.5 rounded text-[10px] font-bold text-gray-700 bg-gray-50 border border-gray-200">
                                                    {{ $release['target_status'] }}
                                                </span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                @else
                    {{-- Estado vacio --}}
                    <div class="flex flex-col items-center justify-center py-12 text-center">
                        <div class="w-16 h-16 rounded-full bg-gray-100 flex items-center justify-center mb-4">
                            <i class="fas fa-history text-gray-400 text-2xl"></i>
                        </div>
                        <h4 class="text-lg font-bold text-gray-900 mb-2">Sin liberaciones registradas</h4>
                        <p class="text-sm text-gray-500 max-w-sm">
                            No hay registros de liberaciones para esta habitacion el dia <strong>{{ $roomDailyHistoryData['date_formatted'] }}</strong>.
                        </p>
                    </div>
                @endif
            </div>

            {{-- Footer --}}
            <div class="border-t border-gray-100 px-6 py-3 bg-gray-50">
                <button type="button"
                        @click="$wire.closeRoomDailyHistory()"
                        class="w-full px-4 py-2 text-sm font-bold text-gray-700 bg-white border border-gray-200 rounded-lg hover:bg-gray-50 transition-colors">
                    Cerrar
                </button>
            </div>
        </div>
    </div>
    @endif
</div>

