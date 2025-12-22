<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use App\Models\Room;
use App\Models\Category;
use Carbon\Carbon;

class SalesByRoom extends Component
{
    public $date;
    public $room_id = '';
    public $category_id = '';
    public $shift = '';

    public $rooms = [];
    public $categories = [];
    public $roomsData = [];

    protected $queryString = [
        'date' => ['except' => ''],
        'room_id' => ['except' => ''],
        'category_id' => ['except' => ''],
        'shift' => ['except' => ''],
    ];

    public function mount($date = null)
    {
        $this->date = $date ?: request('date') ?: now()->format('Y-m-d');
        $this->loadData();
    }

    public function updatedDate()
    {
        $this->loadData();
    }

    public function updatedRoomId()
    {
        $this->loadData();
    }

    public function updatedCategoryId()
    {
        $this->loadData();
    }

    public function updatedShift()
    {
        $this->loadData();
    }

    private function loadData()
    {
        $this->rooms = Room::all();
        $this->categories = Category::whereIn('name', ['Bebidas', 'Mecato'])->get();

        $query = Sale::with(['user', 'room.reservations.customer', 'items.product.category'])
            ->whereNotNull('room_id');

        if ($this->date) {
            $query->byDate($this->date);
        }

        if ($this->room_id) {
            $query->where('room_id', $this->room_id);
        }

        if ($this->category_id) {
            $query->whereHas('items.product', function($q) {
                $q->where('category_id', $this->category_id);
            });
        }

        if ($this->shift) {
            $query->byShift($this->shift);
        }

        $sales = $query->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by room
        $salesByRoom = $sales->groupBy('room_id');

        // For each room, group by category
        $this->roomsData = $salesByRoom->map(function($roomSales, $roomId) {
            $room = $roomSales->first()->room;
            $totalByCategory = $roomSales->flatMap->items->groupBy('product.category.name')
                ->map(function($items) {
                    return [
                        'count' => $items->count(),
                        'total' => $items->sum('total')
                    ];
                });

            return [
                'room' => $room,
                'sales' => $roomSales,
                'total' => $roomSales->sum('total'),
                'byCategory' => $totalByCategory,
                'customer' => $room->reservations->first()->customer ?? null,
            ];
        })->values();
    }

    public function render()
    {
        return view('livewire.sales-by-room', [
            'currentDate' => Carbon::parse($this->date),
        ]);
    }
}
