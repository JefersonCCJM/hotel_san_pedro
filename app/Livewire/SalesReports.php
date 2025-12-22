<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use Carbon\Carbon;

class SalesReports extends Component
{
    public $date;

    public $sales = [];
    public $byReceptionist = [];
    public $byShift = [];
    public $totalSales = 0;
    public $totalByPaymentMethod = [];
    public $totalByShift = [];

    protected $queryString = [
        'date' => ['except' => ''],
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

    private function loadData()
    {
        $date = Carbon::parse($this->date);

        $this->sales = Sale::with(['user', 'room', 'items.product'])
            ->byDate($date->format('Y-m-d'))
            ->orderBy('shift')
            ->orderBy('created_at')
            ->get();

        // Group by receptionist
        $this->byReceptionist = $this->sales->groupBy('user_id');
        
        // Group by shift
        $this->byShift = $this->sales->groupBy('shift');

        // Totals
        $this->totalSales = $this->sales->sum('total');
        $this->totalByPaymentMethod = $this->sales->groupBy('payment_method')
            ->map(function($group) {
                return $group->sum('total');
            });

        $this->totalByShift = $this->byShift->map(function($group) {
            return $group->sum('total');
        });
    }

    public function render()
    {
        return view('livewire.sales-reports', [
            'currentDate' => Carbon::parse($this->date),
        ]);
    }
}
