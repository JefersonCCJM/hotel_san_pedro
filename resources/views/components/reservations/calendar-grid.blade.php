@props([
    'rooms',
    'daysInMonth'
])

<div class="bg-white rounded-xl border border-gray-200 shadow-lg overflow-hidden">
    <div class="overflow-x-auto overflow-y-auto max-h-[calc(100vh-120px)] relative">
        @php
            $todayIndex = null;
            foreach ($daysInMonth as $index => $day) {
                if ($day->isToday()) {
                    $todayIndex = $index;
                    break;
                }
            }
        @endphp
        @if($todayIndex !== null)
        <div class="absolute top-0 bottom-0 w-0.5 bg-orange-500 z-25 pointer-events-none"
             style="left: {{ 120 + ($todayIndex * 45) + 22.5 }}px; margin-top: 40px;">
            <div class="absolute -top-2 left-1/2 transform -translate-x-1/2 w-0 h-0 border-l-[5px] border-r-[5px] border-t-[8px] border-transparent border-t-orange-500"></div>
        </div>
        @endif
        
        <table class="min-w-full border-collapse">
            <thead class="sticky top-0 z-30 bg-white border-b-2 border-gray-300">
                <tr>
                    <th class="sticky left-0 z-40 bg-white px-4 py-3 text-left text-[10px] font-bold text-gray-500 uppercase tracking-wider border-r-2 border-gray-300 min-w-[120px] shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)]">
                        <div class="flex items-center">
                            <i class="fas fa-door-open mr-2 text-gray-400"></i>
                            Habitación
                        </div>
                    </th>
                    @foreach($daysInMonth as $day)
                    <th class="px-0 py-2.5 text-center border-r border-gray-200 w-[45px] min-w-[45px] bg-gray-50/50 {{ $day->isToday() ? 'bg-orange-50' : '' }}">
                        <span class="block text-[9px] font-semibold text-gray-500 uppercase tracking-tight mb-0.5">{{ substr($day->translatedFormat('D'), 0, 1) }}</span>
                        <span class="text-xs font-bold {{ $day->isToday() ? 'text-orange-600' : 'text-gray-700' }}">{{ $day->day }}</span>
                    </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white">
                @foreach($rooms as $roomIndex => $room)
                @php
                    $statusRanges = [];
                    $currentStatus = null;
                    $rangeStart = null;
                    $rangeReservation = null;
                    $today = \Carbon\Carbon::today()->startOfDay();
                    $roomStatusValue = $room->status->value ?? null;
                    $isRoomOccupied = in_array($roomStatusValue, ['ocupada', 'pendiente_checkout']);
                    
                    foreach ($daysInMonth as $dayIndex => $day) {
                        $dayStatus = 'free';
                        $dayNormalized = $day->copy()->startOfDay();
                        $isPastDate = $dayNormalized->lt($today);
                        $dayReservation = null;
                        
                        // For past dates, use immutable snapshots (RoomDailyStatus)
                        if ($isPastDate && isset($room->dailyStatuses)) {
                            $snapshot = $room->dailyStatuses->first(function($snapshot) use ($dayNormalized) {
                                return \Carbon\Carbon::parse($snapshot->date)->startOfDay()->equalTo($dayNormalized);
                            });
                            
                            if ($snapshot) {
                                // Use snapshot status (immutable historical data)
                                $snapshotStatus = $snapshot->status->value ?? null;
                                if ($snapshotStatus === 'ocupada') {
                                    $dayStatus = 'occupied';
                                    // Try to load reservation from snapshot if exists
                                    if ($snapshot->reservation_id) {
                                        $dayReservation = $room->reservations->first(function($res) use ($snapshot) {
                                            return $res->id === $snapshot->reservation_id;
                                        });
                                    }
                                } elseif ($snapshotStatus === 'mantenimiento') {
                                    $dayStatus = 'maintenance';
                                } elseif ($snapshotStatus === 'limpieza') {
                                    $dayStatus = 'cleaning';
                                } else {
                                    $dayStatus = 'free';
                                }
                            } else {
                                // No snapshot for this past date - fallback to free
                                $dayStatus = 'free';
                            }
                        } else {
                            // For today and future dates, use active reservations
                            $dayReservation = $room->reservations->first(function($res) use ($dayNormalized) {
                                if (!$res->check_in_date || !$res->check_out_date) {
                                    return false;
                                }
                                $checkIn = \Carbon\Carbon::parse($res->check_in_date)->startOfDay();
                                $checkOut = \Carbon\Carbon::parse($res->check_out_date)->startOfDay();
                                return $dayNormalized->gte($checkIn) && $dayNormalized->lte($checkOut);
                            });

                            if ($dayReservation) {
                                $checkInTime = $dayReservation->check_in_time ?? config('hotel.check_in_time', '15:00');
                                $checkOutTime = config('hotel.check_out_time', '12:00');

                                $checkInDateTime = \Carbon\Carbon::parse($dayReservation->check_in_date)->setTimeFromTimeString($checkInTime);
                                $checkOutDateTime = \Carbon\Carbon::parse($dayReservation->check_out_date)->setTimeFromTimeString($checkOutTime);

                                $checkInDate = $checkInDateTime->copy()->startOfDay();
                                $checkOutDate = $checkOutDateTime->copy()->startOfDay();
                                $now = \Carbon\Carbon::now();

                                // Check-in is represented by room status occupied/pending_checkout
                                $isCheckedIn = $isRoomOccupied;

                                if ($dayNormalized->gt($checkOutDate)) {
                                    $dayStatus = 'free';
                                } elseif (!$isCheckedIn) {
                                    // No check-in yet: keep whole range as reserved
                                    $dayStatus = 'reserved';
                                } elseif ($dayNormalized->lt($checkOutDate)) {
                                    // Checked-in and before checkout day
                                    $dayStatus = 'occupied';
                                } else { // $dayNormalized == $checkOutDate
                                    $dayStatus = $now->lt($checkOutDateTime) ? 'pending_checkout' : 'free';
                                }
                            } elseif ($room->status->value === 'mantenimiento') {
                                $dayStatus = 'maintenance';
                            } elseif ($room->status->value === 'limpieza') {
                                $dayStatus = 'cleaning';
                            }
                        }

                        if ($dayStatus !== $currentStatus) {
                            if ($currentStatus !== null && $rangeStart !== null) {
                                $statusRanges[] = [
                                    'status' => $currentStatus,
                                    'start' => $rangeStart,
                                    'end' => $dayIndex - 1,
                                    'reservation' => $rangeReservation
                                ];
                            }
                            $currentStatus = $dayStatus;
                            $rangeStart = $dayIndex;
                            $rangeReservation = $dayReservation;
                        }
                    }
                    
                    if ($currentStatus !== null && $rangeStart !== null) {
                        $lastDayIndex = count($daysInMonth) - 1;
                        $statusRanges[] = [
                            'status' => $currentStatus,
                            'start' => $rangeStart,
                            'end' => $lastDayIndex,
                            'reservation' => $rangeReservation
                        ];
                    }
                @endphp
                <tr class="group/row hover:bg-gray-50/30 transition-colors border-b border-gray-100 {{ $roomIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50/30' }}">
                    <td class="sticky left-0 z-30 bg-white group-hover/row:bg-gray-50/30 px-4 py-4 border-r-2 border-gray-300 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)] min-w-[120px] transition-colors {{ $roomIndex % 2 === 0 ? '' : 'bg-gray-50/30' }}">
                        <div class="flex flex-col leading-tight">
                            <span class="text-sm font-semibold text-gray-900">{{ $room->room_number }}</span>
                            <span class="text-[10px] font-medium text-gray-500 mt-0.5">{{ $room->beds_count }} {{ $room->beds_count == 1 ? 'Cama' : 'Camas' }}</span>
                        </div>
                    </td>
                    @foreach($daysInMonth as $dayIndex => $day)
                        @php
                            $status = 'free';
                            $dayNormalized = $day->copy()->startOfDay();
                            $today = \Carbon\Carbon::today()->startOfDay();
                            $isPastDate = $dayNormalized->lt($today);
                            $reservation = null;
                            $roomStatusValue = $room->status->value ?? null;
                            $isRoomOccupied = in_array($roomStatusValue, ['ocupada', 'pendiente_checkout']);
                            
                            // For past dates, use immutable snapshots (RoomDailyStatus)
                            if ($isPastDate && isset($room->dailyStatuses)) {
                                $snapshot = $room->dailyStatuses->first(function($snapshot) use ($dayNormalized) {
                                    return \Carbon\Carbon::parse($snapshot->date)->startOfDay()->equalTo($dayNormalized);
                                });
                                
                                if ($snapshot) {
                                    // Use snapshot status (immutable historical data)
                                    $snapshotStatus = $snapshot->status->value ?? null;
                                    if ($snapshotStatus === 'ocupada') {
                                        $status = 'occupied';
                                        // Try to load reservation from snapshot if exists
                                        if ($snapshot->reservation_id) {
                                            $reservation = $room->reservations->first(function($res) use ($snapshot) {
                                                return $res->id === $snapshot->reservation_id;
                                            });
                                        }
                                    } elseif ($snapshotStatus === 'mantenimiento') {
                                        $status = 'maintenance';
                                    } elseif ($snapshotStatus === 'limpieza') {
                                        $status = 'cleaning';
                                    } else {
                                        $status = 'free';
                                    }
                                } else {
                                    // No snapshot for this past date - fallback to free
                                    $status = 'free';
                                }
                            } else {
                                // For today and future dates, use active reservations
                                $reservation = $room->reservations->first(function($res) use ($dayNormalized) {
                                    if (!$res->check_in_date || !$res->check_out_date) {
                                        return false;
                                    }
                                    $checkIn = \Carbon\Carbon::parse($res->check_in_date)->startOfDay();
                                    $checkOut = \Carbon\Carbon::parse($res->check_out_date)->startOfDay();
                                    return $dayNormalized->gte($checkIn) && $dayNormalized->lte($checkOut);
                                });

                                if ($reservation) {
                                    $checkInTime = $reservation->check_in_time ?? config('hotel.check_in_time', '15:00');
                                    $checkOutTime = config('hotel.check_out_time', '12:00');

                                    $checkInDateTime = \Carbon\Carbon::parse($reservation->check_in_date)->setTimeFromTimeString($checkInTime);
                                    $checkOutDateTime = \Carbon\Carbon::parse($reservation->check_out_date)->setTimeFromTimeString($checkOutTime);

                                    $checkInDate = $checkInDateTime->copy()->startOfDay();
                                    $checkOutDate = $checkOutDateTime->copy()->startOfDay();
                                    $now = \Carbon\Carbon::now();

                                    // Check-in is represented by room status occupied/pending_checkout
                                    $isCheckedIn = $isRoomOccupied;

                                    if ($dayNormalized->gt($checkOutDate)) {
                                        $status = 'free';
                                    } elseif (!$isCheckedIn) {
                                        $status = 'reserved';
                                    } elseif ($dayNormalized->lt($checkOutDate)) {
                                        $status = 'occupied';
                                    } else { // $dayNormalized == $checkOutDate
                                        $status = $now->lt($checkOutDateTime) ? 'pending_checkout' : 'free';
                                    }
                                } elseif ($room->status->value === 'mantenimiento') {
                                    $status = 'maintenance';
                                } elseif ($room->status->value === 'limpieza') {
                                    $status = 'cleaning';
                                }
                            }

                            $rangeInfo = null;
                            foreach ($statusRanges as $range) {
                                if ($dayIndex >= $range['start'] && $dayIndex <= $range['end']) {
                                    $rangeInfo = $range;
                                    break;
                                }
                            }

                            $isRangeStart = $rangeInfo && $dayIndex === $rangeInfo['start'];
                            $isRangeEnd = $rangeInfo && $dayIndex === $rangeInfo['end'];
                            $isRangeMiddle = $rangeInfo && !$isRangeStart && !$isRangeEnd;
                            $isSingleDay = $rangeInfo && $rangeInfo['start'] === $rangeInfo['end'];

                            $isCheckIn = $reservation && $reservation->check_in_date && $day->isSameDay(\Carbon\Carbon::parse($reservation->check_in_date));
                            $isCheckOut = $reservation && $reservation->check_out_date && $day->isSameDay(\Carbon\Carbon::parse($reservation->check_out_date));

                            $colorClasses = [
                                'free' => 'bg-emerald-100 hover:bg-emerald-200',
                                'reserved' => 'bg-indigo-500 hover:bg-indigo-600',
                                'occupied' => 'bg-red-500 hover:bg-red-600',
                                'pending_checkout' => 'bg-orange-400 hover:bg-orange-500',
                                'maintenance' => 'bg-yellow-400 hover:bg-yellow-500',
                                'cleaning' => 'bg-purple-400 hover:bg-purple-500'
                            ];

                            $roundedClass = '';
                            if ($isSingleDay) {
                                $roundedClass = 'rounded-md';
                            } elseif ($isRangeStart) {
                                $roundedClass = 'rounded-l-md';
                            } elseif ($isRangeEnd) {
                                $roundedClass = 'rounded-r-md';
                            }

                            $cellBorderClass = 'border-b border-gray-100';
                            
                            if ($isRangeEnd || $isSingleDay) {
                                $cellBorderClass .= ' border-r border-gray-200';
                            }

                            $tooltipData = [
                                'room' => $room->room_number,
                                'beds' => $room->beds_count . ($room->beds_count == 1 ? ' Cama' : ' Camas'),
                                'date' => $day->format('d/m/Y'),
                                'status' => match($status) {
                                    'free' => 'Libre',
                                    'reserved' => 'Reservada',
                                    'occupied' => 'Ocupada',
                                    'pending_checkout' => 'Pendiente de Checkout',
                                    'maintenance' => 'Mantenimiento',
                                    'cleaning' => 'Limpieza',
                                    default => 'Desconocido'
                                }
                            ];

                            if ($reservation && $reservation->customer) {
                                $tooltipData['customer'] = $reservation->customer->name;
                                $tooltipData['check_in'] = $reservation->check_in_date ? \Carbon\Carbon::parse($reservation->check_in_date)->format('d/m/Y') : 'N/A';
                                $tooltipData['check_out'] = $reservation->check_out_date ? \Carbon\Carbon::parse($reservation->check_out_date)->format('d/m/Y') : 'N/A';
                            }
                        @endphp
                        <td class="p-0.5 {{ $cellBorderClass }} relative group w-[45px] min-w-[45px] max-w-[45px] align-middle">
                            <div class="w-full h-10 {{ $roundedClass }} {{ $colorClasses[$status] }} cursor-pointer transition-all duration-200 flex items-center justify-center overflow-hidden relative shadow-sm hover:shadow-md"
                                 data-tooltip="{{ htmlspecialchars(json_encode($tooltipData), ENT_QUOTES, 'UTF-8') }}"
                                 style="min-height: 40px; width: calc(100% - 2px);"
                                 @if($reservation && $reservation->customer)
                                 onclick="openReservationDetail({{ json_encode([
                                     'id' => $reservation->id,
                                     'customer_name' => $reservation->customer ? $reservation->customer->name : 'Cliente eliminado',
                                     'room_number' => $room->room_number,
                                     'beds_count' => $room->beds_count . ($room->beds_count == 1 ? ' Cama' : ' Camas'),
                                     'check_in' => $reservation->check_in_date ? $reservation->check_in_date->format('d/m/Y') : 'N/A',
                                     'check_out' => $reservation->check_out_date ? $reservation->check_out_date->format('d/m/Y') : 'N/A',
                                     'check_in_time' => $reservation->check_in_time ? substr((string) $reservation->check_in_time, 0, 5) : 'N/A',
                                     'guests_count' => (int) ($reservation->guests_count ?? 0),
                                     'payment_method' => $reservation->payment_method ? (string) $reservation->payment_method : 'N/A',
                                     'total' => number_format($reservation->total_amount, 0, ',', '.'),
                                     'deposit' => number_format($reservation->deposit, 0, ',', '.'),
                                     'balance' => number_format($reservation->total_amount - $reservation->deposit, 0, ',', '.'),
                                     'edit_url' => route('reservations.edit', $reservation),
                                     'pdf_url' => route('reservations.download', $reservation),
                                     'notes' => $reservation->notes ?? 'Sin notas adicionales'
                                 ]) }})"
                                 @endif>
                                @if($reservation)
                                    @if($isCheckIn)
                                        <span class="absolute left-1.5 top-0.5 text-white text-[10px] font-bold drop-shadow-md" title="Check-in">▶</span>
                                    @endif
                                    @if($isCheckOut)
                                        <span class="absolute right-1.5 top-0.5 text-white text-[10px] font-bold drop-shadow-md" title="Check-out">⏹</span>
                                    @endif
                                    <i class="fas fa-eye text-white/70 text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200"></i>
                                @endif
                            </div>
                        </td>
                    @endforeach
                </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>

