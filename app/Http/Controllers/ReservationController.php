<?php

namespace App\Http\Controllers;

use App\Models\Reservation;
use App\Models\Customer;
use App\Models\Room;
use App\Http\Requests\StoreReservationRequest;
use Illuminate\Http\Request;
use Barryvdh\DomPDF\Facade\Pdf;

class ReservationController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $reservations = Reservation::with(['customer', 'room'])->latest()->paginate(10);
        return view('reservations.index', compact('reservations'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $customers = Customer::all();
        $rooms = Room::where('status', '!=', 'maintenance')->get();
        return view('reservations.create', compact('customers', 'rooms'));
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
        $customers = Customer::all();
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
        $reservation->delete();
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
