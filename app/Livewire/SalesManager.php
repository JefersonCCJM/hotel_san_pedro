<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Sale;
use App\Models\Room;
use App\Models\Category;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class SalesManager extends Component
{
    use WithPagination;

    // Filtros
    public $date;
    public $search = '';
    public $debt_status = '';
    public $receptionist_id = '';
    public $shift = '';
    public $payment_method = '';
    public $category_id = '';
    public $room_id = '';

    // Estado para filtros avanzados
    public $filtersOpen = false;

    // Query string para mantener filtros en URL
    protected $queryString = [
        'date' => ['except' => ''],
        'search' => ['except' => ''],
        'debt_status' => ['except' => ''],
        'receptionist_id' => ['except' => ''],
        'shift' => ['except' => ''],
        'payment_method' => ['except' => ''],
        'category_id' => ['except' => ''],
        'room_id' => ['except' => ''],
    ];

    public function mount($date = null)
    {
        // Si se pasa fecha como parámetro, usarla; si no, usar la del query string o la actual
        if ($date) {
            $this->date = $date;
        } elseif (!$this->date) {
            $this->date = request('date') ?: now()->format('Y-m-d');
        }

        // Cargar filtros desde query string si existen
        if (request()->filled('search')) {
            $this->search = request('search');
        }
        if (request()->filled('debt_status')) {
            $this->debt_status = request('debt_status');
        }
        if (request()->filled('receptionist_id')) {
            $this->receptionist_id = request('receptionist_id');
        }
        if (request()->filled('shift')) {
            $this->shift = request('shift');
        }
        if (request()->filled('payment_method')) {
            $this->payment_method = request('payment_method');
        }
        if (request()->filled('category_id')) {
            $this->category_id = request('category_id');
        }
        if (request()->filled('room_id')) {
            $this->room_id = request('room_id');
        }

        // Abrir filtros si hay algún filtro activo
        $this->filtersOpen = $this->hasActiveFilters();
        
        // Auto-establecer turno si hay recepcionista
        if ($this->receptionist_id) {
            $this->autoSetShift();
        }
    }

    public function updatedSearch()
    {
        $this->resetPage();
    }

    public function updatedDebtStatus()
    {
        $this->resetPage();
    }

    public function updatedReceptionistId()
    {
        $this->resetPage();
        $this->autoSetShift();
    }

    public function updatedDate()
    {
        $this->resetPage();
    }

    public function updatedShift()
    {
        $this->resetPage();
    }

    public function updatedPaymentMethod()
    {
        $this->resetPage();
    }

    public function updatedCategoryId()
    {
        $this->resetPage();
    }

    public function updatedRoomId()
    {
        $this->resetPage();
    }

    public function changeDate($newDate)
    {
        $this->date = $newDate;
        $this->resetPage();
    }

    public function clearFilters()
    {
        $this->search = '';
        $this->debt_status = '';
        $this->receptionist_id = '';
        $this->shift = '';
        $this->payment_method = '';
        $this->category_id = '';
        $this->room_id = '';
        $this->date = now()->format('Y-m-d');
        $this->resetPage();
    }

    private function autoSetShift()
    {
        if (!$this->receptionist_id) {
            return;
        }

        $receptionist = User::with('roles')->find($this->receptionist_id);
        if (!$receptionist) {
            return;
        }

        $roleName = $receptionist->roles->first()?->name;
        $isAdmin = Auth::user()->hasRole('Administrador');

        // Solo auto-establecer si no es admin
        if (!$isAdmin) {
            if ($roleName === 'Recepcionista Día') {
                $this->shift = 'dia';
            } elseif ($roleName === 'Recepcionista Noche') {
                $this->shift = 'noche';
            } else {
                $this->shift = '';
            }
        }
    }

    private function hasActiveFilters(): bool
    {
        return !empty($this->search) ||
               !empty($this->debt_status) ||
               !empty($this->receptionist_id) ||
               !empty($this->shift) ||
               !empty($this->payment_method) ||
               !empty($this->category_id) ||
               !empty($this->room_id);
    }

    public function render()
    {
        $query = Sale::with(['user', 'room', 'items.product.category']);

        // Filtro por fecha (por defecto día actual)
        $selectedDate = $this->date ?: now()->format('Y-m-d');
        $currentDate = Carbon::parse($selectedDate);
        $query->byDate($selectedDate);

        // Preparar días para la barra de calendario
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();
        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        while ($tempDate <= $endOfMonth) {
            $daysInMonth[] = $tempDate->copy();
            $tempDate->addDay();
        }

        // Filtro por búsqueda
        if ($this->search) {
            $query->where(function($q) {
                $q->whereHas('user', function($userQuery) {
                    $userQuery->where('name', 'like', '%' . $this->search . '%');
                })
                ->orWhereHas('room', function($roomQuery) {
                    $roomQuery->where('room_number', 'like', '%' . $this->search . '%');
                });
            });
        }

        // Filtro por recepcionista
        if ($this->receptionist_id) {
            $receptionist = User::with('roles')->find($this->receptionist_id);
            if ($receptionist) {
                $query->byReceptionist($this->receptionist_id);
                
                // Auto-filter by shift based on receptionist role (solo si no es admin y no hay turno seleccionado manualmente)
                $isAdmin = Auth::user()->hasRole('Administrador');
                if (!$isAdmin) {
                    $roleName = $receptionist->roles->first()?->name;
                    if ($roleName === 'Recepcionista Día') {
                        if (!$this->shift || $this->shift === '') {
                            $this->shift = 'dia';
                        }
                        $query->byShift($this->shift);
                    } elseif ($roleName === 'Recepcionista Noche') {
                        if (!$this->shift || $this->shift === '') {
                            $this->shift = 'noche';
                        }
                        $query->byShift($this->shift);
                    } else {
                        // Si el recepcionista no tiene turno específico, aplicar el filtro de turno si existe
                        if ($this->shift) {
                            $query->byShift($this->shift);
                        }
                    }
                } else {
                    // Si es admin, aplicar el filtro de turno si existe
                    if ($this->shift) {
                        $query->byShift($this->shift);
                    }
                }
            }
        } elseif ($this->shift) {
            // Solo aplicar filtro de turno si no hay recepcionista seleccionado
            $query->byShift($this->shift);
        }

        // Filtro por método de pago
        if ($this->payment_method) {
            $query->byPaymentMethod($this->payment_method);
        }

        // Filtro por estado de deuda
        if ($this->debt_status) {
            $query->where('debt_status', $this->debt_status);
        }

        // Filtro por habitación
        if ($this->room_id) {
            if ($this->room_id === 'normal') {
                $query->whereNull('room_id');
            } else {
                $query->where('room_id', $this->room_id);
            }
        }

        // Filtro por categoría
        if ($this->category_id) {
            $query->whereHas('items.product', function($q) {
                $q->where('category_id', $this->category_id);
            });
        }

        $sales = $query->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        // Calcular estadísticas del día actual (o fecha seleccionada)
        $statsQuery = Sale::whereDate('sale_date', $selectedDate);
        $totalSales = $statsQuery->count();
        $paidSales = (clone $statsQuery)->where('debt_status', 'pagado')->count();
        $pendingSales = (clone $statsQuery)->where('debt_status', 'pendiente')->count();
        $totalCollected = (clone $statsQuery)->where('debt_status', 'pagado')->sum('total');

        // Obtener datos para filtros
        $receptionists = User::whereHas('roles', function($q) {
            $q->whereIn('name', ['Administrador', 'Recepcionista Día', 'Recepcionista Noche']);
        })->with('roles')->get();

        $rooms = Room::all();
        $categories = Category::whereIn('name', ['Bebidas', 'Mecato'])->get();

        // Obtener conteo de ventas por día para el mes actual (para indicadores en el calendario)
        $salesByDay = Sale::whereBetween('sale_date', [$startOfMonth->format('Y-m-d'), $endOfMonth->format('Y-m-d')])
            ->selectRaw('DATE(sale_date) as date, COUNT(*) as count')
            ->groupBy('date')
            ->pluck('count', 'date')
            ->toArray();

        // Preparar datos de recepcionistas con turnos automáticos
        $receptionistsWithShifts = $receptionists->mapWithKeys(function($receptionist) {
            $roleName = $receptionist->roles->first()?->name;
            $autoShift = null;
            if ($roleName === 'Recepcionista Día') {
                $autoShift = 'dia';
            } elseif ($roleName === 'Recepcionista Noche') {
                $autoShift = 'noche';
            }
            return [$receptionist->id => $autoShift];
        })->filter();

        return view('livewire.sales-manager', [
            'sales' => $sales,
            'receptionists' => $receptionists,
            'receptionistsWithShifts' => $receptionistsWithShifts,
            'rooms' => $rooms,
            'categories' => $categories,
            'totalSales' => $totalSales,
            'paidSales' => $paidSales,
            'pendingSales' => $pendingSales,
            'totalCollected' => $totalCollected,
            'selectedDate' => $selectedDate,
            'currentDate' => $currentDate,
            'daysInMonth' => $daysInMonth,
            'salesByDay' => $salesByDay,
        ]);
    }
}
