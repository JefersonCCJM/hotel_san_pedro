<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Room;
use App\Http\Requests\StoreReservationRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        Carbon::setLocale('es');
        $view = $request->get('view', 'calendar');
        $dateStr = $request->get('month', now()->format('Y-m'));
        $date = Carbon::createFromFormat('Y-m', $dateStr);

        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        while ($tempDate <= $endOfMonth) {
            $daysInMonth[] = $tempDate->copy();
            $tempDate->addDay();
        }

        $rooms = Room::with(['reservations' => function($query) use ($startOfMonth, $endOfMonth) {
            $query->where(function($q) use ($startOfMonth, $endOfMonth) {
                $q->where('check_in_date', '<=', $endOfMonth)
                  ->where('check_out_date', '>=', $startOfMonth);
            });
        }, 'reservations.customer'])->orderBy('room_number')->get();

        $reservations = Reservation::with(['customer', 'room'])->latest()->paginate(10);

        return view('reservations.index', compact(
            'reservations',
            'rooms',
            'daysInMonth',
            'view',
            'date'
        ));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::with('taxProfile')->orderBy('name')->get();
        $rooms = Room::where('status', '!=', 'maintenance')->get();

        // Preparar datos de habitaciones para Alpine.js
        $roomsData = $rooms->map(function($room) {
            return [
                'id' => $room->id,
                'number' => $room->room_number,
                'type' => $room->room_type,
                'price' => (float)$room->price_per_night,
                'capacity' => 2, // Asumiendo capacidad por defecto si no existe en BD
                'status' => $room->status
            ];
        });

        return view('reservations.create', compact('customers', 'rooms', 'roomsData'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreReservationRequest $request)
    {
        $exists = Reservation::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<', $request->check_out_date)
                      ->where('check_out_date', '>', $request->check_in_date);
            })
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['room_id' => 'La habitación ya está reservada para las fechas seleccionadas.']);
        }

        Reservation::create($request->validated());
        return redirect()->route('reservations.index')->with('success', 'Reserva creada correctamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Reservation $reservation)
    {
        return view('reservations.show', compact('reservation'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Reservation $reservation)
    {
        $customers = Customer::with('taxProfile')->orderBy('name')->get();
        $rooms = Room::all(); // Show all rooms for edit
        return view('reservations.edit', compact('reservation', 'customers', 'rooms'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(StoreReservationRequest $request, Reservation $reservation)
    {
        $exists = Reservation::where('room_id', $request->room_id)
            ->where('id', '!=', $reservation->id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<', $request->check_out_date)
                      ->where('check_out_date', '>', $request->check_in_date);
            })
            ->exists();

        if ($exists) {
            return back()->withInput()->withErrors(['room_id' => 'La habitación ya está reservada para las fechas seleccionadas.']);
        }

        $reservation->update($request->validated());
        return redirect()->route('reservations.index')->with('success', 'Reserva actualizada correctamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Reservation $reservation)
    {
        $reservationId = $reservation->id;
        $customerName = $reservation->customer->name;

        $reservation->delete();

        \App\Models\AuditLog::create([
            'user_id' => auth()->id(),
            'event' => 'reservation_deleted',
            'description' => "Eliminó la reserva #{$reservationId} del cliente {$customerName}",
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
        ]);

        return redirect()->route('reservations.index')->with('success', 'Reserva eliminada correctamente.');
    }

    /**
     * Download the reservation support in PDF format.
     */
    public function download(Reservation $reservation)
    {
        $reservation->load(['customer', 'room']);
        $pdf = Pdf::loadView('reservations.pdf', compact('reservation'));
        return $pdf->download("Soporte_Reserva_{$reservation->id}.pdf");
    }

    /**
     * Check if a room is available for a given date range.
     */
    public function checkAvailability(Request $request)
    {
        $exists = Reservation::where('room_id', $request->room_id)
            ->where(function ($query) use ($request) {
                $query->where('check_in_date', '<', $request->check_out_date)
                      ->where('check_out_date', '>', $request->check_in_date);
            })
            ->when($request->reservation_id, function($q) use ($request) {
                $q->where('id', '!=', $request->reservation_id);
            })
            ->exists();

        return response()->json(['available' => !$exists]);
    }
}
