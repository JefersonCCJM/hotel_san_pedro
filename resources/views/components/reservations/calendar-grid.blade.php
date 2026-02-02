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

            /**
             * Get formatted rooms information for a reservation
             */
            function getReservationRoomsInfo($reservation): string
            {
                if (
                    !$reservation ||
                    !isset($reservation->reservationRooms) ||
                    $reservation->reservationRooms->isEmpty()
                ) {
                    return 'Sin habitaciones asignadas';
                }

                $rooms = [];
                foreach ($reservation->reservationRooms as $roomReservation) {
                    if ($roomReservation->room) {
                        $rooms[] = $roomReservation->room->room_number;
                    }
                }

                return empty($rooms) ? 'Sin habitaciones asignadas' : implode(', ', $rooms);
            }
        @endphp
        @if ($todayIndex !== null)
            <div class="absolute top-0 bottom-0 w-0.5 bg-orange-500 z-25 pointer-events-none"
                style="left: {{ 120 + $todayIndex * 45 + 22.5 }}px; margin-top: 40px;">
            </div>
        @endif

        <table class="w-full border-collapse">
            <thead class="sticky top-0 z-20 bg-white border-b-2 border-gray-300">
                <tr class="bg-gray-50/50">
                    <th
                        class="sticky left-0 z-30 bg-gray-50/50 px-4 py-2.5 text-left border-r-2 border-gray-300 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)] min-w-[120px]">
                        <div class="flex flex-col leading-tight">
                            <span class="text-xs font-bold text-gray-700 uppercase tracking-tight">Habitación</span>
                            <span class="text-[10px] font-medium text-gray-500 mt-0.5">Capacidad</span>
                        </div>
                    </th>
                    @foreach ($daysInMonth as $day)
                        <th
                            class="px-0 py-2.5 text-center border-r border-gray-200 w-[45px] min-w-[45px] bg-gray-50/50 {{ $day->isToday() ? 'bg-orange-50' : '' }}">
                            <span
                                class="block text-[9px] font-semibold text-gray-500 uppercase tracking-tight mb-0.5">{{ substr($day->translatedFormat('D'), 0, 1) }}</span>
                            <span
                                class="text-xs font-bold {{ $day->isToday() ? 'text-orange-600' : 'text-gray-700' }}">{{ $day->day }}</span>
                        </th>
                    @endforeach
                </tr>
            </thead>
            <tbody class="bg-white">
                @foreach ($rooms as $roomIndex => $room)
                    @php
                        // 🔥 DEBUG: Logs para identificar el problema
                        \Log::error("=== PROCESANDO HABITACIÓN {$room->room_number} ===");
                        \Log::error(
                            'Reservation rooms cargadas: ' .
                                ($room->reservationRooms ? $room->reservationRooms->count() : 'null'),
                        );
                        \Log::error(
                            'Reservations cargadas: ' . ($room->reservations ? $room->reservations->count() : 'null'),
                        );

                        // 🔥 CORRECCIÓN CRÍTICA: Generar statusRanges desde reservation_rooms
                        $statusRanges = [];
                        $currentStatus = null;
                        $currentReservationId = null;
                        $rangeStart = null;
                        $rangeReservation = null;

                        \Log::error('🔥 INICIANDO BUCLE DE DÍAS - Total días: ' . count($daysInMonth));

                        foreach ($daysInMonth as $dayIndex => $day) {
                            $dayStatus = 'free';
                            $dayNormalized = $day->copy()->startOfDay();
                            $isPastDate = $dayNormalized->lt(\Carbon\Carbon::today()->startOfDay());
                            $dayReservation = null;

                            \Log::error(
                                "Procesando día {$day->format('Y-m-d')} (index: {$dayIndex}) - isPast: " .
                                    ($isPastDate ? 'YES' : 'NO'),
                            );

                            // For past dates, use immutable snapshots (RoomDailyStatus)
                            if ($isPastDate && isset($room->dailyStatuses)) {
                                $snapshot = $room->dailyStatuses->first(function ($snapshot) use ($dayNormalized) {
                                    return \Carbon\Carbon::parse($snapshot->date)
                                        ->startOfDay()
                                        ->equalTo($dayNormalized);
                                });

                                if ($snapshot) {
                                    $dayStatus = $snapshot->status;
                                    if ($snapshot->reservation_id) {
                                        $dayReservation = $room->reservations->first(function ($r) use ($snapshot) {
                                            return $r->id === $snapshot->reservation_id;
                                        });
                                        
                                        // 🔥 VERIFICAR SI LA RESERVATION TIENE STAY FINISHED
                                        if ($dayReservation) {
                                            $hasFinishedStay = $dayReservation->stays()
                                                ->whereIn('status', ['active', 'pending_checkout', 'finished'])
                                                ->exists();
                                            
                                            if ($hasFinishedStay) {
                                                // Si tiene stay, marcar como ocupado (rojo) y ignorar reservation
                                                $dayStatus = 'occupied';
                                                $dayReservation = null;
                                                \Log::error("  🏠 RESERVATION CON STAY ENCONTRADA - MARCANDO COMO OCUPADO - Reservation ID: {$snapshot->reservation_id}");
                                            }
                                        }
                                    }
                                } else {
                                    $dayStatus = 'free';
                                }
                            } else {
                                // For today and future dates: PRIORIZAR STAYS sobre reservation_rooms
                                \Log::error(
                                    "🔥 Buscando stays y reservas para habitación {$room->id} en día {$day->format('Y-m-d')}",
                                );
                                \Log::error(
                                    '    Stays disponibles: ' . ($room->stays ? $room->stays->count() : 'null'),
                                );
                                \Log::error(
                                    '    ReservationRooms disponibles: ' .
                                        ($room->reservationRooms ? $room->reservationRooms->count() : 'null'),
                                );

                                $dayReservation = null;
                                
                                /*
                                |--------------------------------------------------------------------------
                                | 1. VERIFICAR STAY ACTIVO → OCUPADO (si hay stay, ignorar reservation)
                                |--------------------------------------------------------------------------
                                */
                                $activeStay = $room->stays->first(function ($stay) use ($dayNormalized) {
                                    // Si hay cualquier stay (active, pending_checkout, finished), ignorar reservation
                                    if (!in_array($stay->status, ['active', 'pending_checkout', 'finished'])) {
                                        return false;
                                    }
                                    
                                    $checkIn = \Carbon\Carbon::parse($stay->check_in_at)->startOfDay();
                                    $checkOut = $stay->check_out_at
                                        ? \Carbon\Carbon::parse($stay->check_out_at)->startOfDay()
                                        : null;

                                    \Log::error("    🏠 Analizando Stay ID: {$stay->id}");
                                    \Log::error("    📅 Stay Status: {$stay->status}");
                                    \Log::error("    📅 Stay Check-in: {$checkIn->format('Y-m-d')}");
                                    \Log::error("    📅 Stay Check-out: " . ($checkOut ? $checkOut->format('Y-m-d') : 'NULL'));
                                    \Log::error("    📅 Día evaluado: {$dayNormalized->format('Y-m-d')}");

                                    // Stay activo: ocupa desde check-in hasta checkout (con límite de 1 día si no hay checkout)
                                    if ($stay->status === 'active') {
                                        if ($checkOut) {
                                            $affectsDay = $dayNormalized->gte($checkIn) && $dayNormalized->lt($checkOut);
                                        } else {
                                            // Si no hay checkout, solo afectar el día del check-in
                                            $affectsDay = $dayNormalized->equalTo($checkIn);
                                            \Log::error("    ⚠️ Stay sin checkout - solo afecta el día: {$checkIn->format('Y-m-d')}");
                                        }
                                        \Log::error("    🎯 ¿Stay afecta este día? " . ($affectsDay ? 'SÍ' : 'NO'));
                                        return $affectsDay;
                                    } elseif ($stay->status === 'pending_checkout') {
                                        // Pendiente checkout: ocupa el día de checkout
                                        $affectsDay = $checkOut && $dayNormalized->equalTo($checkOut);
                                        \Log::error("    🎯 ¿Pending checkout afecta este día? " . ($affectsDay ? 'SÍ' : 'NO'));
                                        return $affectsDay;
                                    } elseif ($stay->status === 'finished') {
                                        // Finished: solo afecta los días que realmente ocupó (con límite de 1 día si no hay checkout)
                                        if ($checkOut) {
                                            $affectsDay = $dayNormalized->gte($checkIn) && $dayNormalized->lt($checkOut);
                                        } else {
                                            // Si no hay checkout, solo afectar el día del check-in
                                            $affectsDay = $dayNormalized->equalTo($checkIn);
                                            \Log::error("    ⚠️ Stay finished sin checkout - solo afecta el día: {$checkIn->format('Y-m-d')}");
                                        }
                                        \Log::error("    🎯 ¿Finished stay afecta este día? " . ($affectsDay ? 'SÍ' : 'NO'));
                                        return $affectsDay;
                                    }
                                    
                                    return false;
                                });

                                if ($activeStay) {
                                    // 🔥 Si hay stay activo, marcar como ocupado (rojo)
                                    $dayStatus = 'occupied';
                                    $dayReservation = null;
                                    \Log::error("  🏠 STAY ACTIVO encontrado - MARCANDO COMO OCUPADO - Status: {$activeStay->status}");
                                }
                                /*
                                |--------------------------------------------------------------------------
                                | 2. RESERVATION ROOMS → RESERVADA (solo si NO hay stay activo)
                                |--------------------------------------------------------------------------
                                */
                                else {
                                    \Log::error("  🔥 PROCESANDO RESERVATION ROOMS - Total: " . ($room->reservationRooms ? $room->reservationRooms->count() : 0));
                                    
                                    $reservationRoom = $room->reservationRooms->first(function ($rr) use ($dayNormalized) {
                                        \Log::error("    🔍 Analizando ReservationRoom ID: {$rr->id}");
                                        \Log::error('    📅 check_in_date: ' . ($rr->check_in_date ?? 'NULL'));
                                        \Log::error('    📅 check_out_date: ' . ($rr->check_out_date ?? 'NULL'));

                                        if (!$rr->check_in_date || !$rr->check_out_date) {
                                            \Log::error('    ❌ Fechas nulas - skip');
                                            return false;
                                        }
                                        $checkIn = \Carbon\Carbon::parse($rr->check_in_date)->startOfDay();
                                        $checkOut = \Carbon\Carbon::parse($rr->check_out_date)->startOfDay();

                                        \Log::error("    📊 Rango reservation: {$checkIn->format('Y-m-d')} - {$checkOut->format('Y-m-d')}");
                                        \Log::error("    📊 Día evaluado: {$dayNormalized->format('Y-m-d')}");

                                        $isInRange = $dayNormalized->gte($checkIn) && $dayNormalized->lt($checkOut);
                                        \Log::error("    🎯 ¿Día en rango? " . ($isInRange ? 'SÍ' : 'NO'));

                                        return $isInRange;
                                    });

                                    if ($reservationRoom) {
                                        $dayStatus = 'reserved';
                                        $dayReservation = $reservationRoom->reservation;
                                        \Log::error(
                                            "  📋 ReservationRoom encontrado - Reserva: " .
                                                ($dayReservation ? $dayReservation->id : 'null'),
                                        );
                                        \Log::error("  🔵 MARCANDO DÍA COMO 'RESERVED' (AZUL)");
                                    } else {
                                        // 2. LIBRE (sino hay reservation)
                                        $dayStatus = 'free';
                                        \Log::error("  🟢 No hay reservation - LIBRE");
                                    }
                                }

                                \Log::error(
                                    "🎯 Resultado final para día {$day->format('Y-m-d')}: " .
                                        ($dayReservation ? "OCUPADA/RESERVADA (ID: {$dayReservation->id})" : 'LIBRE'),
                                );
                            }

                            // 🔥 FIX: Cortar rango si cambia status O reserva
                            if ($dayStatus !== $currentStatus || $dayReservation?->id !== $currentReservationId) {
                                if ($currentStatus !== null && $rangeStart !== null) {
                                    $statusRanges[] = [
                                        'status' => $currentStatus,
                                        'start' => $rangeStart,
                                        'end' => $dayIndex - 1,
                                        'reservation' => $rangeReservation,
                                    ];
                                }
                                $currentStatus = $dayStatus;
                                $currentReservationId = $dayReservation?->id;
                                $rangeStart = $dayIndex;
                                $rangeReservation = $dayReservation;
                            }
                        }

                        // 🔥 Cerrar el último rango si existe
                        if ($currentStatus !== null && $rangeStart !== null) {
                            $lastDayIndex = count($daysInMonth) - 1;
                            $statusRanges[] = [
                                'status' => $currentStatus,
                                'start' => $rangeStart,
                                'end' => $lastDayIndex,
                                'reservation' => $rangeReservation,
                            ];
                        }

                        \Log::error("📊 StatusRanges generados para habitación {$room->room_number}:");
                        \Log::error('  Total statusRanges: ' . count($statusRanges));
                        foreach ($statusRanges as $i => $range) {
                            \Log::error(
                                "  [{$i}] {$range['status']} ({$range['start']} - {$range['end']}) - Reserva: " .
                                    ($range['reservation'] ? $range['reservation']->id : 'null'),
                            );
                        }
                        \Log::error("🔥 FIN PROCESAMIENTO HABITACIÓN {$room->room_number} ===");
                    @endphp
                    <tr
                        class="group/row hover:bg-gray-50/30 transition-colors border-b border-gray-100 {{ $roomIndex % 2 === 0 ? 'bg-white' : 'bg-gray-50/30' }}">
                        <td
                            class="sticky left-0 z-30 bg-white group-hover/row:bg-gray-50/30 px-4 py-4 border-r-2 border-gray-300 shadow-[2px_0_4px_-2px_rgba(0,0,0,0.1)] min-w-[120px] transition-colors {{ $roomIndex % 2 === 0 ? '' : 'bg-gray-50/30' }}">
                            <div class="flex flex-col leading-tight">
                                <span class="text-sm font-semibold text-gray-900">{{ $room->room_number }}</span>
                                <span class="text-[10px] font-medium text-gray-500 mt-0.5">{{ $room->beds_count }}
                                    {{ $room->beds_count == 1 ? 'Cama' : 'Camas' }}</span>
                            </div>
                        </td>
                        @foreach ($daysInMonth as $dayIndex => $day)
                            @php
                                // ✅ MVP: Usar solo statusRanges pre-calculados
                                $rangeInfo = null;
                                foreach ($statusRanges as $range) {
                                    if ($dayIndex >= $range['start'] && $dayIndex <= $range['end']) {
                                        $rangeInfo = $range;
                                        break;
                                    }
                                }

                                $status = $rangeInfo['status'] ?? 'free';
                                $reservation = $rangeInfo['reservation'] ?? null;

                                \Log::error(
                                    "🎨 RENDER día {$day->format(
        'Y-m-d',
    )} (index: {$dayIndex}) - Status: {$status} - Reservation: " .
                                        ($reservation ? $reservation->id : 'null'),
                                );

                                $isRangeStart = $rangeInfo && $dayIndex === $rangeInfo['start'];
                                $isRangeEnd = $rangeInfo && $dayIndex === $rangeInfo['end'];
                                $isSingleDay = $rangeInfo && $rangeInfo['start'] === $rangeInfo['end'];

                                // ✅ MVP: Obtener fechas de reservation_rooms
                                $isCheckIn = false;
                                $isCheckOut = false;
                                if ($reservation && isset($reservation->reservationRooms)) {
                                    foreach ($reservation->reservationRooms as $rr) {
                                        if (
                                            $rr->check_in_date &&
                                            $day->isSameDay(\Carbon\Carbon::parse($rr->check_in_date))
                                        ) {
                                            $isCheckIn = true;
                                        }
                                        if (
                                            $rr->check_out_date &&
                                            $day->isSameDay(\Carbon\Carbon::parse($rr->check_out_date))
                                        ) {
                                            $isCheckOut = true;
                                        }
                                    }
                                }

                                $colorClasses = [
                                    'free' => 'bg-emerald-100 hover:bg-emerald-200',
                                    'reserved' => 'bg-indigo-500 hover:bg-indigo-600',
                                    'occupied' => 'bg-red-500 hover:bg-red-600',
                                ];

                                $roundedClass = '';
                                if ($isSingleDay) {
                                    $roundedClass = 'rounded-md';
                                } elseif ($isRangeStart) {
                                    $roundedClass = 'rounded-l-md';
                                } elseif ($isRangeEnd) {
                                    $roundedClass = 'rounded-r-md';
                                }

                                // Bordes para celdas
                                $cellBorderClass = 'border-t border-b border-l border-gray-200';
                                if ($isRangeStart || $isSingleDay) {
                                    $cellBorderClass .= ' border-l border-gray-200';
                                }
                                if ($isRangeEnd || $isSingleDay) {
                                    $cellBorderClass .= ' border-r border-gray-200';
                                }

                                $tooltipData = [
                                    'room' => $room->room_number,
                                    'beds' => $room->beds_count . ($room->beds_count == 1 ? ' Cama' : ' Camas'),
                                    'date' => $day->format('d/m/Y'),
                                    'status' => match ($status) {
                                        'free' => 'Libre',
                                        'reserved' => 'Reservada',
                                        'occupied' => 'Ocupada',
                                        default => 'Desconocido',
                                    },
                                ];

                                if ($reservation && $reservation->customer) {
                                    $tooltipData['customer'] = $reservation->customer->name;
                                    // ✅ MVP: Obtener fechas de reservation_rooms
                                    $checkInDate = null;
                                    $checkOutDate = null;
                                    if (
                                        isset($reservation->reservationRooms) &&
                                        $reservation->reservationRooms->isNotEmpty()
                                    ) {
                                        $firstRoom = $reservation->reservationRooms->first();
                                        $checkInDate = $firstRoom->check_in_date;
                                        $checkOutDate = $firstRoom->check_out_date;
                                    }
                                    $tooltipData['check_in'] = $checkInDate
                                        ? \Carbon\Carbon::parse($checkInDate)->format('d/m/Y')
                                        : 'N/A';
                                    $tooltipData['check_out'] = $checkOutDate
                                        ? \Carbon\Carbon::parse($checkOutDate)->format('d/m/Y')
                                        : 'N/A';
                                }

                                // 🔥 DEBUG: Log del tooltip data
                                \Log::error(
                                    "🔍 Tooltip data para día {$day->format('Y-m-d')}: " . json_encode($tooltipData),
                                );
                            @endphp
                            <td
                                class="p-0.5 {{ $cellBorderClass }} relative group w-[45px] min-w-[45px] max-w-[45px] align-middle">
                                @if ($status === 'checkout_day')
                                    <div class="w-full h-full border-2 border-dashed border-blue-400 bg-blue-50 cursor-pointer transition-all duration-200 flex items-center justify-center overflow-hidden relative shadow-sm hover:shadow-md"
                                        data-tooltip="{{ json_encode($tooltipData) }}"
                                        @if ($reservation) onclick="console.log('🔥 Click detected on reservation'); openReservationDetail({{ json_encode([
                                            'id' => $reservation->id,
                                            'customer_name' => $reservation->customer ? $reservation->customer->name : 'Sin cliente asignado',
                                            'customer_identification' => $reservation->customer
                                                ? ($reservation->customer->identification_number
                                                    ? ($reservation->customer->identificationType
                                                        ? $reservation->customer->identificationType->name .
                                                            ': ' .
                                                            $reservation->customer->identification_number
                                                        : $reservation->customer->identification_number)
                                                    : '-')
                                                : '-',
                                            'customer_phone' => $reservation->customer ? $reservation->customer->phone ?? '-' : '-',
                                            'rooms' => getReservationRoomsInfo($reservation),
                                            'check_in' =>
                                                isset($reservation->reservationRooms) && $reservation->reservationRooms->isNotEmpty()
                                                    ? \Carbon\Carbon::parse($reservation->reservationRooms->first()->check_in_date)->format('d/m/Y')
                                                    : 'N/A',
                                            'check_out' =>
                                                isset($reservation->reservationRooms) && $reservation->reservationRooms->isNotEmpty()
                                                    ? \Carbon\Carbon::parse($reservation->reservationRooms->first()->check_out_date)->format('d/m/Y')
                                                    : 'N/A',
                                            'check_in_time' => $reservation->check_in_time ? substr((string) $reservation->check_in_time, 0, 5) : 'N/A',
                                            'guests_count' => (int) ($reservation->total_guests ?? 0),
                                            'payment_method' => $reservation->payment_method ? (string) $reservation->payment_method : 'N/A',
                                            'total' => number_format($reservation->total_amount, 0, ',', '.'),
                                            'deposit' => number_format($reservation->deposit_amount, 0, ',', '.'),
                                            'balance' => number_format($reservation->total_amount - $reservation->deposit_amount, 0, ',', '.'),
                                            'edit_url' => route('reservations.edit', $reservation),
                                            'pdf_url' => route('reservations.download', $reservation),
                                            'notes' => $reservation->notes ?? 'Sin notas adicionales',
                                            'status' => $reservation->status ?? 'Activa',
                                        ]) }})" @endif>
                                        @if ($isRangeStart && $reservation)
                                            <div class="absolute left-0 top-0 h-full w-[3px] bg-white/70"></div>
                                        @endif
                                        @if ($isRangeStart && $reservation && $reservation->customer)
                                            <span class="text-[10px] font-bold text-blue-600 truncate px-1">
                                                {{ \Illuminate\Support\Str::limit($reservation->customer->name ?? 'Reserva', 8) }}
                                            </span>
                                        @endif
                                        @if ($isCheckOut)
                                            <span
                                                class="absolute right-1.5 top-0.5 text-blue-600 text-[10px] font-bold drop-shadow-md"
                                                title="Check-out">⏹</span>
                                        @endif
                                        <i
                                            class="fas fa-eye text-blue-600/70 text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200"></i>
                                    </div>
                                @else
                                    <div class="w-full h-10 {{ $roundedClass }} {{ $colorClasses[$status] }} cursor-pointer transition-all duration-200 flex items-center justify-center overflow-hidden relative shadow-sm hover:shadow-md"
                                        data-tooltip="{{ json_encode($tooltipData) }}"
                                        style="min-height: 40px; width: calc(100% - 2px);"
                                        @if ($reservation) onclick="console.log('🔥 Click detected on reservation'); openReservationDetail({{ json_encode([
                                            'id' => $reservation->id,
                                            'customer_name' => $reservation->customer ? $reservation->customer->name : 'Sin cliente asignado',
                                            'customer_identification' => $reservation->customer
                                                ? ($reservation->customer->identification_number
                                                    ? ($reservation->customer->identificationType
                                                        ? $reservation->customer->identificationType->name .
                                                            ': ' .
                                                            $reservation->customer->identification_number
                                                        : $reservation->customer->identification_number)
                                                    : '-')
                                                : '-',
                                            'customer_phone' => $reservation->customer ? $reservation->customer->phone ?? '-' : '-',
                                            'rooms' => getReservationRoomsInfo($reservation),
                                            'check_in' =>
                                                isset($reservation->reservationRooms) && $reservation->reservationRooms->isNotEmpty()
                                                    ? \Carbon\Carbon::parse($reservation->reservationRooms->first()->check_in_date)->format('d/m/Y')
                                                    : 'N/A',
                                            'check_out' =>
                                                isset($reservation->reservationRooms) && $reservation->reservationRooms->isNotEmpty()
                                                    ? \Carbon\Carbon::parse($reservation->reservationRooms->first()->check_out_date)->format('d/m/Y')
                                                    : 'N/A',
                                            'check_in_time' => $reservation->check_in_time ? substr((string) $reservation->check_in_time, 0, 5) : 'N/A',
                                            'guests_count' => (int) ($reservation->total_guests ?? 0),
                                            'payment_method' => $reservation->payment_method ? (string) $reservation->payment_method : 'N/A',
                                            'total' => number_format($reservation->total_amount, 0, ',', '.'),
                                            'deposit' => number_format($reservation->deposit_amount, 0, ',', '.'),
                                            'balance' => number_format($reservation->total_amount - $reservation->deposit_amount, 0, ',', '.'),
                                            'edit_url' => route('reservations.edit', $reservation),
                                            'pdf_url' => route('reservations.download', $reservation),
                                            'notes' => $reservation->notes ?? 'Sin notas adicionales',
                                            'status' => $reservation->status ?? 'Activa',
                                        ]) }})" @endif>
                                        @if ($isRangeStart && $reservation)
                                            <div class="absolute left-0 top-0 h-full w-[3px] bg-white/70"></div>
                                        @endif
                                        @if ($isRangeStart && $reservation && $reservation->customer)
                                            <span class="text-[10px] font-bold text-white truncate px-1">
                                                {{ \Illuminate\Support\Str::limit($reservation->customer->name ?? 'Reserva', 8) }}
                                            </span>
                                        @endif
                                        @if ($reservation)
                                            @if ($isCheckIn)
                                                <span
                                                    class="absolute left-1.5 top-0.5 text-white text-[10px] font-bold drop-shadow-md"
                                                    title="Check-in">▶</span>
                                            @endif
                                            @if ($isCheckOut)
                                                <span
                                                    class="absolute right-1.5 top-0.5 text-white text-[10px] font-bold drop-shadow-md"
                                                    title="Check-out">⏹</span>
                                            @endif
                                            <i
                                                class="fas fa-eye text-white/70 text-xs opacity-0 group-hover:opacity-100 transition-opacity duration-200"></i>
                                        @endif
                                    </div>
                                @endif
                            </td>
                        @endforeach
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
</div>
