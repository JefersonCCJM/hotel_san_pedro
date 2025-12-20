<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Room;
use App\Models\Reservation;
use App\Models\ReservationSale;
use App\Models\Product;
use App\Enums\RoomStatus;
use Carbon\Carbon;
use Livewire\WithPagination;
use Illuminate\Support\Facades\DB;

class RoomManager extends Component
{
    use WithPagination;

    public $date;
    public $search = '';
    public $status = '';

    // Selection and Modals
    public $selectedRoomId = null;
    public $detailData = null;
    public $roomDetailModal = false;
    public $showAddSale = false;
    public $quickRentModal = false;
    public $rentForm = [
        'room_id' => '',
        'room_number' => '',
        'customer_id' => '',
        'people' => 1,
        'max_capacity' => 1,
        'prices' => [],
        'total' => 0,
        'deposit' => 0,
        'check_out' => '',
        'payment_method' => 'efectivo'
    ];

    // Add consumption form
    public $newSale = [
        'product_id' => '',
        'quantity' => 1,
        'payment_method' => 'pendiente'
    ];

    protected $queryString = [
        'search' => ['except' => ''],
        'status' => ['except' => ''],
        'date' => ['except' => ''],
    ];

    public function mount()
    {
        $this->date = $this->date ?: now()->format('Y-m-d');
        $this->rentForm['check_out'] = Carbon::parse($this->date)->addDay()->format('Y-m-d');
    }

    public function updatedSearch() { $this->resetPage(); }
    public function updatedStatus() { $this->resetPage(); }

    public function changeDate($newDate)
    {
        $this->date = $newDate;
        $this->rentForm['check_out'] = Carbon::parse($newDate)->addDay()->format('Y-m-d');
        if ($this->roomDetailModal) {
            $this->openRoomDetail($this->selectedRoomId);
        }
    }

    public function openRoomDetail($roomId)
    {
        $this->selectedRoomId = $roomId;
        $this->loadRoomDetail();
        $this->roomDetailModal = true;
        $this->showAddSale = false;
    }

    private function loadRoomDetail()
    {
        $room = Room::find($this->selectedRoomId);
        $date = Carbon::parse($this->date);

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->with(['customer.taxProfile', 'sales.product'])
            ->first();

        if (!$reservation) {
            $this->detailData = [
                'room' => $room->toArray(),
                'reservation' => null,
                'sales' => [],
                'stay_history' => [],
                'status_label' => $room->status->label(),
                'status_color' => $room->status->color()
            ];
        } else {
            $total_hospedaje = (float) $reservation->total_amount;
            $abono = (float) $reservation->deposit;
            $consumos_pagados = (float) $reservation->sales->where('is_paid', true)->sum('total');
            $consumos_pendientes = (float) $reservation->sales->where('is_paid', false)->sum('total');
            $total_debt = ($total_hospedaje - $abono) + $consumos_pendientes;

            $stay_history = [];
            $checkIn = Carbon::parse($reservation->check_in_date);
            $checkOut = Carbon::parse($reservation->check_out_date);
            $daysTotal = max(1, $checkIn->diffInDays($checkOut));
            $dailyPrice = $total_hospedaje_completo / $daysTotal;

            // Calcular hospedaje consumido hasta hoy (o la fecha seleccionada)
            $selectedDate = Carbon::parse($this->date);
            $daysConsumed = $checkIn->diffInDays($selectedDate) + 1;
            $daysConsumed = max(1, min($daysTotal, $daysConsumed));
            $total_hospedaje = $dailyPrice * $daysConsumed;

            $total_debt = ($total_hospedaje - $abono) + $consumos_pendientes;

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

            $this->detailData = [
                'room' => $room->toArray(),
                'reservation' => $reservation->toArray(),
                'customer' => $reservation->customer->toArray(),
                'identification' => $reservation->customer->taxProfile?->identification ?? 'N/A',
                'total_hospedaje' => $total_hospedaje,
                'abono_realizado' => $abono,
                'sales_total' => $consumos_pagados + $consumos_pendientes,
                'consumos_pendientes' => $consumos_pendientes,
                'total_debt' => $total_debt,
                'sales' => $reservation->sales->toArray(),
                'stay_history' => $stay_history,
                'status_label' => RoomStatus::OCUPADA->label(),
                'status_color' => RoomStatus::OCUPADA->color()
            ];
        }
    }

    public function payEverything($reservationId, $method)
    {
        $reservation = Reservation::with('sales')->findOrFail($reservationId);
        $this->selectedRoomId = $reservation->room_id;

        // 1. Marcar todos los consumos como pagados
        $reservation->sales()->where('is_paid', false)->update([
            'is_paid' => true,
            'payment_method' => $method
        ]);

        // 2. Actualizar el abono para cubrir todo el hospedaje
        $reservation->update([
            'deposit' => $reservation->total_amount,
            'is_paid' => true
        ]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Todo marcado como pagado.');
    }

    public function toggleAddSale()
    {
        $this->showAddSale = !$this->showAddSale;
        if ($this->showAddSale) {
            $this->dispatch('initAddSaleSelect');
        }
    }

    public function addSale()
    {
        $this->validate([
            'newSale.product_id' => 'required|exists:products,id',
            'newSale.quantity' => 'required|integer|min:1',
            'newSale.payment_method' => 'required|in:efectivo,transferencia,pendiente',
        ]);

        $room = Room::find($this->selectedRoomId);
        $date = Carbon::parse($this->date);

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>', $date)
            ->first();

        if (!$reservation) {
            $this->dispatch('notify', type: 'error', message: 'No hay un arrendamiento activo.');
            return;
        }

        $product = Product::find($this->newSale['product_id']);
        if ($product->quantity < $this->newSale['quantity']) {
            $this->dispatch('notify', type: 'error', message: "Stock insuficiente ({$product->quantity})");
            return;
        }

        $reservation->sales()->create([
            'product_id' => $product->id,
            'quantity' => $this->newSale['quantity'],
            'unit_price' => $product->price,
            'total' => $product->price * $this->newSale['quantity'],
            'payment_method' => $this->newSale['payment_method'],
            'is_paid' => $this->newSale['payment_method'] !== 'pendiente',
        ]);

        $product->decrement('quantity', $this->newSale['quantity']);
        $this->newSale = ['product_id' => '', 'quantity' => 1, 'payment_method' => 'pendiente'];
        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Consumo cargado.');
    }

    public function paySale($saleId, $method)
    {
        $sale = ReservationSale::findOrFail($saleId);

        if ($method === 'pendiente') {
            $sale->update(['is_paid' => false, 'payment_method' => 'pendiente']);
        } else {
            $sale->update(['is_paid' => true, 'payment_method' => $method]);
        }

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Pago actualizado.');
    }

    public function payNight($reservationId, $amount, $method)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->increment('deposit', $amount);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Pago de noche registrado (' . ucfirst($method) . ').');
    }

    public function revertNightPayment($reservationId, $amount)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $newDeposit = max(0, $reservation->deposit - $amount);
        $reservation->update(['deposit' => $newDeposit]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Pago de noche anulado.');
    }

    public function updateDeposit($reservationId, $newAmount)
    {
        $reservation = Reservation::findOrFail($reservationId);
        $reservation->update(['deposit' => $newAmount]);

        $this->loadRoomDetail();
        $this->dispatch('notify', type: 'success', message: 'Abono actualizado.');
    }

    public function releaseRoom($roomId, $targetStatus)
    {
        $room = Room::findOrFail($roomId);
        $date = Carbon::parse($this->date);

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>=', $date)
            ->orderBy('check_in_date', 'asc')
            ->first();

        if ($reservation) {
            $start = $reservation->check_in_date;
            $end = $reservation->check_out_date;

            if ($end->isSameDay($date) || ($start->isSameDay($date) && $end->copy()->subDay()->isSameDay($date))) {
                // Termina hoy
            } elseif ($start->isSameDay($date)) {
                $reservation->update(['check_in_date' => $date->copy()->addDay()]);
            } elseif ($end->copy()->subDay()->isSameDay($date)) {
                $reservation->update(['check_out_date' => $date]);
            } else {
                $originalEnd = $reservation->check_out_date;
                $reservation->update(['check_out_date' => $date]);
                $newRes = $reservation->replicate();
                $newRes->check_in_date = $date->copy()->addDay();
                $newRes->check_out_date = $originalEnd;
                $newRes->save();
            }
        }

        if ($date->isToday()) {
            $room->update(['status' => $targetStatus]);
        }

        $this->dispatch('notify', type: 'success', message: "Habitación #{$room->room_number} liberada.");
    }

    public function continueStay($roomId)
    {
        $room = Room::findOrFail($roomId);
        $date = Carbon::parse($this->date);

        $reservation = $room->reservations()
            ->where('check_in_date', '<=', $date)
            ->where('check_out_date', '>=', $date)
            ->first();

        if (!$reservation) return;

        $newCheckOut = $reservation->check_out_date->copy()->addDay();
        $prices = $room->getPricesForDate($reservation->check_out_date);
        $additionalPrice = $prices[$reservation->guests_count] ?? ($prices[1] ?? 0);

        $reservation->update([
            'check_out_date' => $newCheckOut,
            'total_amount' => $reservation->total_amount + $additionalPrice
        ]);

        $this->dispatch('notify', type: 'success', message: "Estancia extendida.");
    }

    public function openQuickRent($roomId)
    {
        $room = Room::find($roomId);
        $prices = $room->getPricesForDate(Carbon::parse($this->date));

        $this->rentForm = [
            'room_id' => $room->id,
            'room_number' => $room->room_number,
            'max_capacity' => $room->max_capacity,
            'prices' => $prices,
            'people' => 1,
            'total' => $prices[1] ?? 0,
            'deposit' => 0,
            'check_out' => Carbon::parse($this->date)->addDay()->format('Y-m-d'),
            'payment_method' => 'efectivo',
            'customer_id' => ''
        ];

        $this->quickRentModal = true;
        $this->dispatch('quickRentOpened');
    }

    public function updatedRentForm($value, $key)
    {
        if (in_array($key, ['people', 'check_out'])) {
            $p = (int)$this->rentForm['people'];
            $basePrice = $this->rentForm['prices'][$p] ?? ($this->rentForm['prices'][$this.rentForm['max_capacity']] ?? 0);
            $start = Carbon::parse($this->date);
            $end = Carbon::parse($this->rentForm['check_out']);
            $diffDays = max(1, $start->diffInDays($end));
            $this->rentForm['total'] = $basePrice * $diffDays;
        }
    }

    public function storeQuickRent()
    {
        $this->validate([
            'rentForm.customer_id' => 'required|exists:customers,id',
            'rentForm.people' => 'required|integer|min:1|max:'.$this->rentForm['max_capacity'],
            'rentForm.check_out' => 'required|date|after:'.$this->date,
            'rentForm.total' => 'required|numeric|min:0',
            'rentForm.deposit' => 'required|numeric|min:0',
            'rentForm.payment_method' => 'required|in:efectivo,transferencia',
        ]);

        Reservation::create([
            'room_id' => $this->rentForm['room_id'],
            'customer_id' => $this->rentForm['customer_id'],
            'check_in_date' => $this->date,
            'check_out_date' => $this->rentForm['check_out'],
            'reservation_date' => now(),
            'guests_count' => $this->rentForm['people'],
            'total_amount' => $this->rentForm['total'],
            'deposit' => $this->rentForm['deposit'],
            'payment_method' => $this->rentForm['payment_method'],
            'is_paid' => $this->rentForm['deposit'] >= $this->rentForm['total'],
            'status' => 'confirmed'
        ]);

        $this->quickRentModal = false;
        $this->dispatch('notify', type: 'success', message: 'Reserva creada exitosamente.');
    }

    public function render()
    {
        $date = Carbon::parse($this->date);
        $startOfMonth = $date->copy()->startOfMonth();
        $endOfMonth = $date->copy()->endOfMonth();

        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        while ($tempDate <= $endOfMonth) {
            $daysInMonth[] = $tempDate->copy();
            $tempDate->addDay();
        }

        $query = Room::query();
        if ($this->search) {
            $query->where(function($q) {
                $q->where('room_number', 'like', '%' . $this->search . '%')
                  ->orWhere('beds_count', 'like', '%' . $this->search . '%');
            });
        }
        if ($this->status) {
            $query->where('status', $this->status);
        }

        $rooms = $query->with([
            'reservations' => function($q) use ($startOfMonth, $endOfMonth) {
                $q->where('check_in_date', '<=', $endOfMonth)
                  ->where('check_out_date', '>=', $startOfMonth)
                  ->with('customer');
            },
            'reservations.sales',
            'rates'
        ])->orderBy('room_number')->paginate(30);

        $rooms->getCollection()->transform(function($room) use ($date) {
            $isFuture = $date->isAfter(now()->endOfDay());
            $reservation = null;

            // Solo buscamos reservas si NO es una fecha futura
            if (!$isFuture) {
                $reservation = $room->reservations->first(function($res) use ($date) {
                    $yesterday = $date->copy()->subDay();
                    $occupiedYesterday = $yesterday->between($res->check_in_date, $res->check_out_date->copy()->subDay());
                    $occupiedToday = $date->between($res->check_in_date, $res->check_out_date->copy()->subDay());
                    return $occupiedYesterday || $occupiedToday;
                });
            }

            if ($reservation) {
                $room->display_status = RoomStatus::OCUPADA;
                $room->current_reservation = $reservation;

                $checkIn = Carbon::parse($reservation->check_in_date);
                $checkOut = Carbon::parse($reservation->check_out_date);
                $daysTotal = max(1, $checkIn->diffInDays($checkOut));
                $dailyPrice = (float)$reservation->total_amount / $daysTotal;

                $daysUntilSelected = $checkIn->diffInDays($date);
                $costUntilSelected = $dailyPrice * ($daysUntilSelected + 1);
                $costUntilSelected = min((float)$reservation->total_amount, $costUntilSelected);

                $room->is_night_paid = ($reservation->deposit >= $costUntilSelected);

                $stay_debt = (float)($reservation->total_amount - $reservation->deposit);
                $sales_debt = (float)$reservation->sales->where('is_paid', false)->sum('total');
                $room->total_debt = $stay_debt + $sales_debt;
            } else {
                $room->display_status = $date->isToday() ? (($room->status === RoomStatus::OCUPADA) ? RoomStatus::LIBRE : $room->status) : RoomStatus::LIBRE;
                $room->current_reservation = null;
                $room->total_debt = 0;
            }
            $room->active_prices = $room->getPricesForDate($date);
            return $room;
        });

        return view('livewire.room-manager', [
            'rooms' => $rooms,
            'statuses' => RoomStatus::cases(),
            'daysInMonth' => $daysInMonth,
            'currentDate' => $date
        ]);
    }
}
