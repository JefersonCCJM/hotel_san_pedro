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
     * Now handled by Livewire component SalesManager
     */
    public function index(Request $request): View
    {
        return view('sales.index');
    }

    /**
     * Show the form for creating a new resource.
     * Now handled by Livewire component CreateSale
     */
    public function create(): View
    {
        return view('sales.create');
    }

    /**
     * Store a newly created resource in storage.
     * Handles business logic for creating sales.
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
     * Now handled by Livewire component ShowSale
     */
    public function show(Sale $sale): View
    {
        return view('sales.show', compact('sale'));
    }

    /**
     * Show the form for editing the specified resource.
     * Now handled by Livewire component EditSale
     */
    public function edit(Sale $sale): View
    {
        return view('sales.edit', compact('sale'));
    }

    /**
     * Update the specified resource in storage.
     * Handles business logic for updating sales.
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
     * Now handled by Livewire component SalesByRoom
     */
    public function byRoom(Request $request): View
    {
        return view('sales.by-room');
    }

    /**
     * Show daily sales report.
     * Now handled by Livewire component SalesReports
     */
    public function dailyReport(Request $request): View
    {
        return view('sales.reports');
    }
}
