<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Models\VentilationType as VentilationTypeModel;
use App\Enums\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Schema;

class RoomController extends Controller
{
    private function ensureAdmin(): void
    {
        abort_unless(Auth::user()?->hasRole('Administrador'), 403);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        return view('rooms.index');
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $this->ensureAdmin();

        $statuses = RoomStatus::cases();
        return view('rooms.create', compact('statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'beds_count' => 'required|integer|min:1|max:15',
            'max_capacity' => 'required|integer|min:1',
            'ventilation_type' => 'required|string|in:' . implode(',', array_column(\App\Enums\VentilationType::cases(), 'value')),
            'occupancy_prices' => 'required|array',
            'status' => 'nullable|string',
        ], [
            'beds_count.max' => 'El número de camas no puede ser mayor a 15.',
        ]);

        // Asegurar que los precios sean numéricos
        $validated['occupancy_prices'] = array_map('intval', $validated['occupancy_prices']);
        
        // Mantener compatibilidad con columnas antiguas (opcional)
        $validated['price_1_person'] = $validated['occupancy_prices'][1] ?? 0;
        $validated['price_2_persons'] = $validated['occupancy_prices'][2] ?? 0;
        $validated['price_per_night'] = $validated['price_2_persons'];

        // Si no se proporciona status, usar LIBRE por defecto
        if (!isset($validated['status'])) {
            $validated['status'] = RoomStatus::LIBRE->value;
        }

        // New rooms start as "Limpia" (clean), not "Pendiente por Aseo"
        // Set last_cleaned_at to now() so cleaningStatus() returns 'limpia'
        $validated['last_cleaned_at'] = now();

        Room::create($validated);

        return redirect()->route('rooms.index')
            ->with('success', 'Habitación creada exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Room $room): View
    {
        $room->load(['reservations.customer']);
        return view('rooms.show', compact('room'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Room $room): View
    {
        $this->ensureAdmin();

        $statuses = RoomStatus::cases();
        $room->load('rates');
        
        // Check if room is occupied (from reservations, not from status)
        $isOccupied = $room->isOccupied();
        
        return view('rooms.edit', compact('room', 'statuses', 'isOccupied'));
    }

    /**
     * Store a special rate for a date range.
     */
    public function storeRate(Request $request, Room $room): RedirectResponse
    {
        $this->ensureAdmin();

        $validated = $request->validate([
            'start_date' => 'required|date',
            'end_date' => 'required|date|after_or_equal:start_date',
            'occupancy_prices' => 'required|array',
            'event_name' => 'nullable|string|max:255',
        ]);

        $room->rates()->create($validated);

        return back()->with('success', 'Tarifa especial añadida correctamente.');
    }

    /**
     * Delete a special rate.
     */
    public function destroyRate(Room $room, \App\Models\RoomRate $rate): RedirectResponse
    {
        $this->ensureAdmin();

        $rate->delete();
        return back()->with('success', 'Tarifa especial eliminada.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $this->ensureAdmin();

        $rules = [
            'room_number' => 'required|string|unique:rooms,room_number,' . $room->id,
            'beds_count' => 'required|integer|min:1|max:15',
            'max_capacity' => 'required|integer|min:1',
            'occupancy_prices' => 'required|array',
        ];

        // Compatibilidad: aceptar esquema nuevo (ventilation_type_id) y legado (ventilation_type).
        if ($request->filled('ventilation_type_id')) {
            $rules['ventilation_type_id'] = 'required|integer|exists:ventilation_types,id';
        } else {
            $rules['ventilation_type'] = 'required|string';
        }

        if (Schema::hasColumn('rooms', 'status') && $request->filled('status')) {
            $rules['status'] = 'nullable|string';
        }

        $validated = $request->validate($rules, [
            'beds_count.max' => 'El numero de camas no puede ser mayor a 15.',
        ]);

        $ventilationTypeId = null;
        if (!empty($validated['ventilation_type_id'])) {
            $ventilationTypeId = (int) $validated['ventilation_type_id'];
        } else {
            $rawType = strtolower(trim((string) ($validated['ventilation_type'] ?? '')));
            $legacyMap = [
                'ventilador' => 'fan',
                'aire_acondicionado' => 'ac',
            ];
            $normalizedType = $legacyMap[$rawType] ?? $rawType;

            if (is_numeric($normalizedType)) {
                $ventilationTypeId = VentilationTypeModel::query()
                    ->whereKey((int) $normalizedType)
                    ->value('id');
            } else {
                $ventilationTypeId = VentilationTypeModel::query()
                    ->where('code', $normalizedType)
                    ->value('id');
            }
        }

        if (empty($ventilationTypeId)) {
            return back()
                ->withInput()
                ->with('error', 'El tipo de ventilacion seleccionado no es valido.');
        }

        if (Schema::hasColumn('rooms', 'status') && !empty($validated['status'])) {
            $newStatus = RoomStatus::from($validated['status']);
            $isOccupied = $room->isOccupied();

            // Restriccion: no permitir cambiar a Ocupada/Libre si hay reserva activa.
            if ($isOccupied && ($newStatus === RoomStatus::OCUPADA || $newStatus === RoomStatus::LIBRE)) {
                return back()->with('error', 'No se puede cambiar el estado a "Ocupada" o "Libre" cuando hay una reserva activa. La ocupacion se calcula automaticamente desde las reservas.');
            }
        }

        $validatedPrices = collect($validated['occupancy_prices'] ?? [])
            ->mapWithKeys(function ($price, $guests) {
                $guestCount = (int) $guests;
                if ($guestCount <= 0) {
                    return [];
                }

                return [$guestCount => max(0, (int) $price)];
            })
            ->sortKeys();

        if ($validatedPrices->isEmpty()) {
            return back()
                ->withInput()
                ->with('error', 'Debe definir al menos un precio de ocupacion valido.');
        }

        DB::transaction(function () use ($room, $validated, $validatedPrices, $ventilationTypeId): void {
            $room->room_number = (string) $validated['room_number'];
            $room->beds_count = (int) $validated['beds_count'];
            $room->max_capacity = (int) $validated['max_capacity'];
            $room->ventilation_type_id = $ventilationTypeId;

            if (Schema::hasColumn('rooms', 'status') && !empty($validated['status'])) {
                $room->status = $validated['status'];
            }

            if (Schema::hasColumn('rooms', 'base_price_per_night')) {
                $basePrice = (float) ($validatedPrices->get(1, $validatedPrices->first() ?? 0));
                $room->base_price_per_night = $basePrice;
            }

            $room->save();

            foreach ($validatedPrices as $guestCount => $price) {
                $room->rates()->updateOrCreate(
                    [
                        'min_guests' => (int) $guestCount,
                        'max_guests' => (int) $guestCount,
                    ],
                    [
                        'price_per_night' => (float) $price,
                    ]
                );
            }

            // Limpiar tarifas estandar que ya no aplican para la nueva capacidad.
            $room->rates()
                ->whereColumn('min_guests', 'max_guests')
                ->whereNotIn('min_guests', $validatedPrices->keys()->all())
                ->delete();
        });

        return redirect()->route('rooms.index')
            ->with('success', 'Habitacion actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room): RedirectResponse
    {
        $this->ensureAdmin();

        if ($room->reservations()->exists()) {
            return back()->with('error', 'No se puede eliminar la habitación porque tiene reservas asociadas.');
        }

        $room->delete();

        return redirect()->route('rooms.index')
            ->with('success', 'Habitación eliminada exitosamente.');
    }

    /**
     * Get detailed information about a room and its current reservation/sales.
     */
    public function getDetail(Request $request, Room $room): \Illuminate\Http\JsonResponse
    {
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : now();

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->with(['customer.taxProfile', 'sales.product'])
            ->first();

        if (!$reservation) {
            return response()->json([
                'room' => $room,
                'reservation' => null,
                'sales' => [],
                'stay_history' => [],
                'status_label' => $room->status->label(),
                'status_color' => $room->status->color()
            ]);
        }

        // Asegurar valores numéricos para evitar errores en JS
        $total_hospedaje = (float) $reservation->total_amount;
        $abono = (float) $reservation->deposit;
        $consumos_pagados = (float) $reservation->sales->where('is_paid', true)->sum('total');
        $consumos_pendientes = (float) $reservation->sales->where('is_paid', false)->sum('total');
        
        // Deuda = (Hospedaje - Abono) + Consumos Pendientes
        $total_debt = ($total_hospedaje - $abono) + $consumos_pendientes;

        // Calcular historial de días (Stay History)
        $stay_history = [];
        $checkIn = \Carbon\Carbon::parse($reservation->check_in_date);
        $checkOut = \Carbon\Carbon::parse($reservation->check_out_date);
        $daysTotal = $checkIn->diffInDays($checkOut);
        $dailyPrice = $daysTotal > 0 ? ($total_hospedaje / $daysTotal) : $total_hospedaje;
        
        $paidAmount = $abono;
        for ($i = 0; $i < $daysTotal; $i++) {
            $currentDate = $checkIn->copy()->addDays($i);
            $isPaid = $paidAmount >= $dailyPrice;
            $stay_history[] = [
                'date' => $currentDate->format('d/m/Y'),
                'is_paid' => $isPaid,
                'price' => $dailyPrice
            ];
            if ($isPaid) $paidAmount -= $dailyPrice;
        }

        return response()->json([
            'room' => $room,
            'reservation' => $reservation,
            'customer' => $reservation->customer,
            'identification' => $reservation->customer->taxProfile?->identification ?? 'N/A',
            'total_hospedaje' => $total_hospedaje,
            'abono_realizado' => $abono,
            'sales_total' => $consumos_pagados + $consumos_pendientes, // Total de consumos para la caja visual
            'consumos_pendientes' => $consumos_pendientes,
            'total_debt' => $total_debt,
            'sales' => $reservation->sales,
            'stay_history' => $stay_history,
            'status_label' => RoomStatus::OCUPADA->label(),
            'status_color' => RoomStatus::OCUPADA->color()
        ]);
    }

    /**
     * Add a sale/consumption to the room's current reservation.
     */
    public function addSale(Request $request, Room $room): RedirectResponse
    {
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : now();
        
        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->first();

        if (!$reservation) {
            return back()->with('error', 'No hay un arrendamiento activo para cargar ventas.');
        }

        $validated = $request->validate([
            'product_id' => 'required|exists:products,id',
            'quantity' => 'required|integer|min:1',
            'payment_method' => 'required|string|in:efectivo,transferencia,pendiente',
        ]);

        $product = \App\Models\Product::find($validated['product_id']);
        
        if ($product->quantity < $validated['quantity']) {
            return back()->with('error', "Stock insuficiente. Disponible: {$product->quantity}");
        }

        $total = $product->price * $validated['quantity'];

        $reservation->sales()->create([
            'product_id' => $product->id,
            'quantity' => $validated['quantity'],
            'unit_price' => $product->price,
            'total' => $total,
            'payment_method' => $validated['payment_method'],
            'is_paid' => $validated['payment_method'] !== 'pendiente',
        ]);

        // Descontar del inventario
        $product->decrement('quantity', $validated['quantity']);

        return back()->with('success', 'Venta cargada a la habitación correctamente.');
    }

    /**
     * Update room status via AJAX.
     * RESTRINGIDO: Solo permite cambiar estados de limpieza cuando NO hay reservas activas.
     * Ocupación se calcula desde reservas, NO se puede cambiar manualmente.
     */
    public function updateStatus(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'status' => 'required|string',
        ]);

        $newStatus = RoomStatus::from($validated['status']);
        $today = \Carbon\Carbon::today();

        // Verificar si hay reserva activa
        $hasActiveReservation = $room->reservations()
            ->where('check_in_date', '<=', $today)
            ->where('check_out_date', '>=', $today)
            ->exists();

        // Si hay reserva activa, NO permitir cambiar estado (ocupación se calcula desde reservas)
        if ($hasActiveReservation) {
            return back()->with('error', 'No se puede cambiar el estado de una habitación ocupada. La ocupación se calcula automáticamente desde las reservas.');
        }

        // Solo permitir cambiar a estados de limpieza (SUCIA, LIMPIEZA) o LIBRE cuando NO hay reservas
        $allowedStatuses = [RoomStatus::LIBRE, RoomStatus::SUCIA, RoomStatus::LIMPIEZA];
        if (!in_array($newStatus, $allowedStatuses)) {
            return back()->with('error', 'Solo se pueden establecer estados de limpieza (Libre, Sucia, Limpieza) cuando no hay reservas activas.');
        }

        $room->update(['status' => $newStatus]);

        return back()->with('success', 'Estado de limpieza actualizado correctamente.');
    }

    /**
     * Mark a consumable sale as paid or pending.
     */
    public function paySale(Request $request, \App\Models\ReservationSale $sale): RedirectResponse
    {
        $validated = $request->validate([
            'payment_method' => 'required|string|in:efectivo,transferencia,pendiente',
        ]);

        if ($validated['payment_method'] === 'pendiente') {
            $sale->update([
                'is_paid' => false,
                'payment_method' => 'pendiente'
            ]);
            return back()->with('success', 'Consumo devuelto a estado pendiente.');
        }

        $sale->update([
            'is_paid' => true,
            'payment_method' => $validated['payment_method']
        ]);

        return back()->with('success', 'Consumo marcado como pagado.');
    }

    /**
     * Update the reservation deposit (total abono).
     */
    public function updateDeposit(Request $request, \App\Models\Reservation $reservation): RedirectResponse
    {
        $validated = $request->validate([
            'deposit' => 'required|numeric|min:0',
        ]);

        $reservation->update([
            'deposit' => $validated['deposit']
        ]);

        return back()->with('success', 'Abono total actualizado correctamente.');
    }

    /**
     * Add a payment to the reservation deposit (effectively paying for nights).
     */
    public function payNight(Request $request, \App\Models\Reservation $reservation): RedirectResponse
    {
        $validated = $request->validate([
            'amount' => 'required|numeric|min:0',
            'payment_method' => 'required|string|in:efectivo,transferencia',
        ]);

        $reservation->increment('deposit', $validated['amount']);
        
        // Opcional: Podríamos registrar este pago en una tabla de auditoría o historial de pagos
        // Por ahora, solo aumentamos el abono para que el cálculo de noches se actualice

        return back()->with('success', 'Pago de hospedaje registrado correctamente.');
    }

    /**
     * Release the room: surgically free only the selected date.
     * Ocupación se calcula desde reservas, NO se guarda en rooms.status.
     */
    public function release(Request $request, Room $room): RedirectResponse
    {
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : now();
        $targetStatus = $request->get('status', RoomStatus::LIBRE->value);

        // Buscar la reserva relevante: la que viene de antes o la que está en curso
        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>=', $date)
            ->orderBy('check_in_date', 'asc')
            ->first();

        if ($reservation) {
            $start = $reservation->check_in_date;
            $end = $reservation->check_out_date;

            // Si la reserva termina hoy o es de un solo día (el actual)
            if ($end->isSameDay($date) || ($start->isSameDay($date) && $end->copy()->subDay()->isSameDay($date))) {
                // No hacemos nada a la reserva, ya termina hoy. 
            }
            // CASO 2: Es el día de entrada de una estancia larga -> Mover entrada a mañana
            elseif ($start->isSameDay($date)) {
                $reservation->update(['check_in_date' => $date->copy()->addDay()]);
            }
            // CASO 3: Es el último día ocupado (mañana saldría) -> Adelantar salida a hoy
            elseif ($end->copy()->subDay()->isSameDay($date)) {
                $reservation->update(['check_out_date' => $date]);
            }
            // CASO 4: El día está en la mitad -> Dividir la reserva en dos
            else {
                $originalEnd = $reservation->check_out_date;
                $reservation->update(['check_out_date' => $date]);

                $newRes = $reservation->replicate();
                $newRes->check_in_date = $date->copy()->addDay();
                $newRes->check_out_date = $originalEnd;
                $newRes->save();
            }
        }

        // Si estamos viendo el día de HOY y se marca como SUCIA, actualizar estado físico
        // (solo para limpieza, NO para ocupación - ocupación se calcula desde reservas)
        if ($date->isToday() && $targetStatus === RoomStatus::SUCIA->value) {
            $room->update(['status' => RoomStatus::SUCIA]);
        } elseif ($date->isToday() && $targetStatus === RoomStatus::LIBRE->value) {
            // Solo marcar como LIBRE si NO hay reservas activas
            $hasActiveReservation = $room->reservations()
                ->where('check_in_date', '<=', $date)
                ->where('check_out_date', '>=', $date)
                ->exists();
            
            if (!$hasActiveReservation) {
                $room->update(['status' => RoomStatus::LIBRE]);
            }
        }

        $statusLabel = $targetStatus === RoomStatus::LIBRE->value ? 'Libre' : 'Sucia';
        return back()->with('success', "Habitación #{$room->room_number} gestionada como {$statusLabel} para el día {$date->format('d/m/Y')}.");
    }

    /**
     * Extend the reservation for one more night.
     */
    public function continueStay(Request $request, Room $room): RedirectResponse
    {
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : now();

        // Buscar la reserva relevante (la que termina hoy o está en curso)
        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>=', $date)
            ->orderBy('check_in_date', 'asc')
            ->first();

        if (!$reservation) {
            return back()->with('error', 'No hay un arrendamiento activo para continuar.');
        }

        // Extender la fecha de salida por un día
        $newCheckOut = $reservation->check_out_date->copy()->addDay();
        
        // Calcular el precio de la noche adicional
        $prices = $room->getPricesForDate($reservation->check_out_date);
        $additionalPrice = $prices[$reservation->guests_count] ?? ($prices[1] ?? 0);

        $reservation->update([
            'check_out_date' => $newCheckOut,
            'total_amount' => $reservation->total_amount + $additionalPrice
        ]);

        // Dispatch Livewire event globally for cleaning panel
        \Livewire\Livewire::dispatch('reservation-extended', [
            'roomId' => $room->id,
            'roomNumber' => $room->room_number
        ]);

        return back()->with('success', "Arrendamiento de la Habitación #{$room->room_number} extendido hasta el {$newCheckOut->format('d/m/Y')}.");
    }
}
