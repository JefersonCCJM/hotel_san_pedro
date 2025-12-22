<?php

namespace App\Http\Controllers;

use App\Models\Room;
use App\Enums\RoomStatus;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class RoomController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Room::query();
        $date = $request->filled('date') ? \Carbon\Carbon::parse($request->date) : now();
        
        // Preparar días para la barra de calendario (mes actual de la fecha seleccionada)
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();
        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        while ($tempDate <= $endOfMonth) {
            $daysInMonth[] = $tempDate->copy();
            $tempDate->addDay();
        }

        // Filtros
        if ($request->filled('search')) {
            $query->where('room_number', 'like', '%' . $request->search . '%')
                  ->orWhere('beds_count', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        $rooms = $query->with([
            'reservations' => function($q) use ($startOfMonth, $endOfMonth) {
                $q->where('check_in_date', '<=', $endOfMonth)
                  ->where('check_out_date', '>=', $startOfMonth)
                  ->with('customer'); // Cargar el cliente para evitar N+1
            }, 
            'reservations.sales', 
            'rates'
        ])->orderBy('room_number')->paginate(30);

        // Para la fecha específica seleccionada (la que se resalta en el calendario)
        $rooms->getCollection()->transform(function($room) use ($date) {
            // 1. Buscamos si había alguien durmiendo aquí anoche (o si se queda hoy)
            $reservation = $room->reservations->first(function($res) use ($date) {
                $yesterday = $date->copy()->subDay();
                // Ocupada ayer si ayer estaba entre check-in y salida
                $occupiedYesterday = $yesterday->between($res->check_in_date, $res->check_out_date->copy()->subDay());
                // Ocupada hoy si hoy está entre check-in y salida
                $occupiedToday = $date->between($res->check_in_date, $res->check_out_date->copy()->subDay());
                
                return $occupiedYesterday || $occupiedToday;
            });
            
            // Determinar estado visual
            if ($reservation) {
                // Si hay una reserva activa (que viene de ayer o está hoy), el estado es OCUPADA
                $room->display_status = RoomStatus::OCUPADA;
                $room->current_reservation = $reservation;

                // Calcular deuda total
                $stay_debt = (float)($reservation->total_amount - $reservation->deposit);
                $sales_debt = (float)$reservation->sales->where('is_paid', false)->sum('total');
                $room->total_debt = $stay_debt + $sales_debt;
            } else {
                // Si no hay reserva que venga de ayer ni esté hoy, revisamos si hay una que EMPIEZA hoy
                $newReservation = $room->reservations->first(function($res) use ($date) {
                    return $date->isSameDay($res->check_in_date);
                });

                if ($newReservation) {
                    $room->display_status = RoomStatus::OCUPADA;
                    $room->current_reservation = $newReservation;
                    $room->total_debt = (float)($newReservation->total_amount - $newReservation->deposit) + (float)$newReservation->sales->sum('total');
                } else {
                    // Si no hay nada, mostramos el estado físico de la habitación (solo para hoy)
                    if ($date->isToday()) {
                        $room->display_status = ($room->status === RoomStatus::OCUPADA) ? RoomStatus::LIBRE : $room->status;
                    } else {
                        $room->display_status = RoomStatus::LIBRE;
                    }
                    $room->current_reservation = null;
                    $room->total_debt = 0;
                }
            }

            // Determinar precios dinámicos para esta fecha
            $room->active_prices = $room->getPricesForDate($date);
            $room->has_special_rate = $room->rates->first(function($rate) use ($date) {
                return $date->between($rate->start_date, $rate->end_date);
            }) !== null;
            
            return $room;
        });

        $statuses = RoomStatus::cases();

        return view('rooms.index', compact('rooms', 'statuses', 'date', 'daysInMonth'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $statuses = RoomStatus::cases();
        return view('rooms.create', compact('statuses'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number',
            'beds_count' => 'required|integer|min:1',
            'max_capacity' => 'required|integer|min:1',
            'occupancy_prices' => 'required|array',
            'status' => 'nullable|string',
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
        $rate->delete();
        return back()->with('success', 'Tarifa especial eliminada.');
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(Request $request, Room $room): RedirectResponse
    {
        $validated = $request->validate([
            'room_number' => 'required|string|unique:rooms,room_number,' . $room->id,
            'beds_count' => 'required|integer|min:1',
            'max_capacity' => 'required|integer|min:1',
            'occupancy_prices' => 'required|array',
            'status' => 'required|string',
        ]);

        $newStatus = RoomStatus::from($validated['status']);
        $isOccupied = $room->isOccupied();

        // RESTRICCIÓN: No permitir cambiar a Ocupada/Libre si hay reserva activa
        // Ocupación se calcula desde reservas, NO se puede cambiar manualmente
        if ($isOccupied) {
            if ($newStatus === RoomStatus::OCUPADA || $newStatus === RoomStatus::LIBRE) {
                return back()->with('error', 'No se puede cambiar el estado a "Ocupada" o "Libre" cuando hay una reserva activa. La ocupación se calcula automáticamente desde las reservas.');
            }
        }

        // Asegurar que los precios sean numéricos
        $validated['occupancy_prices'] = array_map('intval', $validated['occupancy_prices']);

        // Mantener compatibilidad con columnas antiguas
        $validated['price_1_person'] = $validated['occupancy_prices'][1] ?? 0;
        $validated['price_2_persons'] = $validated['occupancy_prices'][2] ?? 0;
        $validated['price_per_night'] = $validated['price_2_persons'];

        $room->update($validated);

        return redirect()->route('rooms.index')
            ->with('success', 'Habitación actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Room $room): RedirectResponse
    {
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
