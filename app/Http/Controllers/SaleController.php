<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreSaleRequest;
use App\Http\Requests\UpdateSaleRequest;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Room;
use App\Models\Category;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;
use Carbon\Carbon;

class SaleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): View
    {
        $query = Sale::with(['user', 'room', 'items.product.category']);

        // Filtros
        // Por defecto, mostrar ventas del día actual si no se especifica fecha
        $selectedDate = $request->filled('date') ? $request->date : now()->format('Y-m-d');
        $currentDate = \Carbon\Carbon::parse($selectedDate);
        
        // Preparar días para la barra de calendario (mes actual de la fecha seleccionada)
        $startOfMonth = $currentDate->copy()->startOfMonth();
        $endOfMonth = $currentDate->copy()->endOfMonth();
        $daysInMonth = [];
        $tempDate = $startOfMonth->copy();
        while ($tempDate <= $endOfMonth) {
            $daysInMonth[] = $tempDate->copy();
            $tempDate->addDay();
        }
        
        $query->byDate($selectedDate);

        if ($request->filled('receptionist_id')) {
            $receptionist = \App\Models\User::with('roles')->find($request->receptionist_id);
            if ($receptionist) {
            $query->byReceptionist($request->receptionist_id);
                
                // Auto-filter by shift based on receptionist role
                $roleName = $receptionist->roles->first()?->name;
                if ($roleName === 'Recepcionista Día') {
                    $query->byShift('dia');
                } elseif ($roleName === 'Recepcionista Noche') {
                    $query->byShift('noche');
                }
            }
        } elseif ($request->filled('shift')) {
            // Only apply shift filter if no receptionist is selected
            $query->byShift($request->shift);
        }

        if ($request->filled('payment_method')) {
            $query->byPaymentMethod($request->payment_method);
        }

        if ($request->filled('debt_status')) {
            $query->where('debt_status', $request->debt_status);
        }

        if ($request->filled('room_id')) {
            if ($request->room_id === 'normal') {
                // Filter for sales without room (personas corrientes)
                $query->whereNull('room_id');
            } else {
            $query->where('room_id', $request->room_id);
            }
        }

        // Filter by category
        if ($request->filled('category_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
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

        $receptionists = \App\Models\User::whereHas('roles', function($q) {
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

        return view('sales.index', compact('sales', 'receptionists', 'rooms', 'categories', 'totalSales', 'paidSales', 'pendingSales', 'totalCollected', 'selectedDate', 'currentDate', 'daysInMonth', 'salesByDay'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $rooms = Room::where('status', 'ocupada')
            ->with(['reservations' => function($q) {
                $q->where('check_in_date', '<=', now())
                  ->where('check_out_date', '>=', now())
                  ->with('customer')
                  ->latest();
            }])
            ->get()
            ->map(function($room) {
                // Obtener la reservación activa más reciente
                $room->current_reservation = $room->reservations->first();
                return $room;
            });
        
        $categories = Category::where('is_active', true)->get();
        
        // Solo productos de categorías Bebidas y Mecato
        $products = Product::where('status', 'active')
            ->where('quantity', '>', 0)
            ->whereHas('category', function($q) {
                $q->whereIn('name', ['Bebidas', 'Mecato']);
            })
            ->with('category')
            ->get();

        // Determinar turno automáticamente basado en el rol del usuario
        $user = Auth::user();
        $userRole = $user->roles->first()?->name;
        $autoShift = 'dia';
        
        if ($userRole === 'Recepcionista Día') {
            $autoShift = 'dia';
        } elseif ($userRole === 'Recepcionista Noche') {
            $autoShift = 'noche';
        } else {
            // Si es Administrador o no tiene rol específico, determinar por hora
        $currentHour = (int) Carbon::now()->format('H');
            $autoShift = $currentHour < 14 ? 'dia' : 'noche';
        }

        $defaultShift = $autoShift;

        return view('sales.create', compact('rooms', 'categories', 'products', 'defaultShift', 'autoShift'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreSaleRequest $request): RedirectResponse
    {
        DB::beginTransaction();

        try {
            $user = Auth::user();
            $saleDate = Carbon::parse($request->sale_date);
            $currentHour = (int) $saleDate->format('H');
            
            // Determinar turno automáticamente basado en el rol del usuario
            $userRole = $user->roles->first()?->name;
            if ($userRole === 'Recepcionista Día') {
                $shift = 'dia';
            } elseif ($userRole === 'Recepcionista Noche') {
                $shift = 'noche';
            } else {
                // Si es Administrador o no tiene rol específico, usar el turno del request o determinar por hora
            $shift = $request->shift ?? ($currentHour < 14 ? 'dia' : 'noche');
            }

            // Validar stock de todos los productos
            $items = $request->items;
            $total = 0;

            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                
                if ($product->quantity < $item['quantity']) {
                    return back()
                        ->withInput()
                        ->withErrors(['items' => "Stock insuficiente para {$product->name}. Disponible: {$product->quantity}"]);
                }

                $itemTotal = $product->price * $item['quantity'];
                $total += $itemTotal;
            }

            // Validar y calcular montos de pago
            $cashAmount = null;
            $transferAmount = null;
            
            if ($request->payment_method === 'efectivo') {
                $cashAmount = $total;
            } elseif ($request->payment_method === 'transferencia') {
                $transferAmount = $total;
            } elseif ($request->payment_method === 'ambos') {
                $cashAmount = $request->cash_amount ?? 0;
                $transferAmount = $request->transfer_amount ?? 0;
                
                // Validar que la suma sea igual al total
                if (($cashAmount + $transferAmount) != $total) {
                    return back()
                        ->withInput()
                        ->withErrors(['payment_method' => "La suma de efectivo y transferencia debe ser igual al total: $" . number_format($total, 2, ',', '.')]);
                }
            } elseif ($request->payment_method === 'pendiente') {
                // Si es pendiente, no hay montos de pago
                $cashAmount = null;
                $transferAmount = null;
            }

            // Determinar estado de deuda
            // Si el método de pago es 'pendiente', el estado debe ser 'pendiente'
            // Si hay habitación, usar el valor del request (puede ser pendiente)
            // Si no hay habitación, siempre debe ser pagado
            if ($request->payment_method === 'pendiente') {
                $debtStatus = 'pendiente';
            } elseif ($request->room_id) {
                $debtStatus = $request->debt_status ?? 'pagado';
            } else {
                $debtStatus = 'pagado'; // Venta normal siempre pagada
            }

            // Crear la venta
            $sale = Sale::create([
                'user_id' => $user->id,
                'room_id' => $request->room_id,
                'shift' => $shift,
                'payment_method' => $request->payment_method,
                'cash_amount' => $cashAmount,
                'transfer_amount' => $transferAmount,
                'debt_status' => $debtStatus,
                'sale_date' => $saleDate,
                'total' => $total,
                'notes' => $request->notes,
            ]);

            // Crear items y descontar stock
            foreach ($items as $item) {
                $product = Product::findOrFail($item['product_id']);
                $itemTotal = $product->price * $item['quantity'];

                SaleItem::create([
                    'sale_id' => $sale->id,
                    'product_id' => $product->id,
                    'quantity' => $item['quantity'],
                    'unit_price' => $product->price,
                    'total' => $itemTotal,
                ]);

                // Descontar del inventario
                $product->decrement('quantity', $item['quantity']);
            }

            DB::commit();

            return redirect()
                ->route('sales.index')
                ->with('success', 'Venta registrada exitosamente.');
        } catch (\Exception $e) {
            DB::rollBack();
            
            return back()
                ->withInput()
                ->withErrors(['error' => 'Error al registrar la venta: ' . $e->getMessage()]);
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Sale $sale): View
    {
        $sale->load(['user', 'room.reservations.customer', 'items.product.category']);

        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Sale $sale): View
    {
        $sale->load(['user', 'room', 'items.product']);

        return view('sales.edit', compact('sale'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateSaleRequest $request, Sale $sale): RedirectResponse
    {
        // Validar y calcular montos de pago
        $cashAmount = null;
        $transferAmount = null;
        $total = $sale->total;
        
        if ($request->payment_method === 'efectivo') {
            $cashAmount = $total;
        } elseif ($request->payment_method === 'transferencia') {
            $transferAmount = $total;
        } elseif ($request->payment_method === 'ambos') {
            $cashAmount = $request->cash_amount ?? 0;
            $transferAmount = $request->transfer_amount ?? 0;
            
            // Validar que la suma sea igual al total
            if (($cashAmount + $transferAmount) != $total) {
                return back()
                    ->withInput()
                    ->withErrors(['payment_method' => "La suma de efectivo y transferencia debe ser igual al total: $" . number_format($total, 2, ',', '.')]);
            }
        } elseif ($request->payment_method === 'pendiente') {
            // Si es pendiente, no hay montos de pago
            $cashAmount = null;
            $transferAmount = null;
        }

        // Actualizar estado de deuda solo si hay habitación
        $updateData = [
            'payment_method' => $request->payment_method,
            'cash_amount' => $cashAmount,
            'transfer_amount' => $transferAmount,
            'notes' => $request->notes,
        ];
        
        // Determinar estado de deuda basado en el método de pago
        if ($request->payment_method === 'pendiente') {
            // Si el método de pago es pendiente, el estado debe ser pendiente
            $updateData['debt_status'] = 'pendiente';
        } elseif ($sale->room_id && $request->filled('debt_status')) {
            // Si hay habitación y se especifica un estado, usar ese
            $updateData['debt_status'] = $request->debt_status;
        } elseif ($sale->debt_status === 'pendiente' && $request->payment_method !== 'pendiente') {
            // Si estaba pendiente y se cambia a un método de pago, significa que se está pagando
            $updateData['debt_status'] = 'pagado';
        } else {
            // Si no hay habitación, siempre debe ser pagado
            $updateData['debt_status'] = 'pagado';
        }

        $sale->update($updateData);

        return redirect()
            ->route('sales.show', $sale)
            ->with('success', 'Venta actualizada exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Sale $sale): RedirectResponse
    {
        DB::beginTransaction();

        try {
            // Restaurar stock de productos
            foreach ($sale->items as $item) {
                $product = $item->product;
                $product->increment('quantity', $item->quantity);
            }

            $sale->delete();

            DB::commit();

            return redirect()
                ->route('sales.index')
                ->with('success', 'Venta eliminada exitosamente. El stock ha sido restaurado.');
        } catch (\Exception $e) {
            DB::rollBack();

            return back()
                ->withErrors(['error' => 'Error al eliminar la venta: ' . $e->getMessage()]);
        }
    }

    /**
     * Show sales grouped by room and category.
     */
    public function byRoom(Request $request): View
    {
        $query = Sale::with(['user', 'room.reservations.customer', 'items.product.category'])
            ->whereNotNull('room_id');

        // Filters
        if ($request->filled('date')) {
            $query->byDate($request->date);
        }

        if ($request->filled('room_id')) {
            $query->where('room_id', $request->room_id);
        }

        if ($request->filled('category_id')) {
            $query->whereHas('items.product', function($q) use ($request) {
                $q->where('category_id', $request->category_id);
            });
        }

        if ($request->filled('shift')) {
            $query->byShift($request->shift);
        }

        $sales = $query->orderBy('sale_date', 'desc')
            ->orderBy('created_at', 'desc')
            ->get();

        // Group by room
        $salesByRoom = $sales->groupBy('room_id');

        // For each room, group by category
        $roomsData = $salesByRoom->map(function($roomSales, $roomId) {
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
        });

        $rooms = Room::all();
        $categories = Category::whereIn('name', ['Bebidas', 'Mecato'])->get();

        return view('sales.by-room', compact('roomsData', 'rooms', 'categories'));
    }

    /**
     * Show daily sales report.
     */
    public function dailyReport(Request $request): View
    {
        $date = $request->filled('date') 
            ? Carbon::parse($request->date) 
            : Carbon::today();

        $sales = Sale::with(['user', 'room', 'items.product'])
            ->byDate($date->format('Y-m-d'))
            ->orderBy('shift')
            ->orderBy('created_at')
            ->get();

        // Agrupar por recepcionista
        $byReceptionist = $sales->groupBy('user_id');
        
        // Agrupar por turno
        $byShift = $sales->groupBy('shift');

        // Totales
        $totalSales = $sales->sum('total');
        $totalByPaymentMethod = $sales->groupBy('payment_method')
            ->map(function($group) {
                return $group->sum('total');
            });

        $totalByShift = $byShift->map(function($group) {
            return $group->sum('total');
        });

        return view('sales.reports', compact(
            'date',
            'sales',
            'byReceptionist',
            'byShift',
            'totalSales',
            'totalByPaymentMethod',
            'totalByShift'
        ));
    }
}
