<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Sale;
use App\Models\SaleItem;
use App\Models\Product;
use App\Models\Room;
use App\Models\Category;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class CreateSale extends Component
{
    // Form data
    public $sale_date;
    public $room_id = '';
    public $shift = 'dia';
    public $payment_method = 'efectivo';
    public $cash_amount = null;
    public $transfer_amount = null;
    public $debt_status = 'pagado';
    public $notes = '';
    public $productCategoryFilter = '';
    
    // Items
    public $items = [];
    
    // Computed properties
    public $rooms = [];
    public $products = [];
    public $categories = [];
    public $selectedProduct = null;
    public $selectedQuantity = 1;
    public $autoShift = 'dia';
    
    public function getTotalProperty()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                $total += $product->price * $item['quantity'];
            }
        }
        return $total;
    }

    protected $rules = [
        'sale_date' => 'required|date',
        'room_id' => 'nullable|exists:rooms,id',
        'shift' => 'required|string|in:dia,noche',
        'payment_method' => 'required|string|in:efectivo,transferencia,ambos,pendiente',
        'cash_amount' => 'nullable|numeric|min:0',
        'transfer_amount' => 'nullable|numeric|min:0',
        'debt_status' => 'nullable|string|in:pagado,pendiente',
        'notes' => 'nullable|string|max:1000',
        'items' => 'required|array|min:1',
        'items.*.product_id' => 'required|exists:products,id',
        'items.*.quantity' => 'required|integer|min:1',
    ];

    protected $messages = [
        'sale_date.required' => 'La fecha de venta es obligatoria.',
        'sale_date.date' => 'La fecha de venta debe ser una fecha válida.',
        'shift.required' => 'El turno es obligatorio.',
        'shift.in' => 'El turno debe ser día o noche.',
        'payment_method.required' => 'El método de pago es obligatorio.',
        'payment_method.in' => 'El método de pago debe ser efectivo, transferencia, ambos o pendiente.',
        'items.required' => 'Debe agregar al menos un producto.',
        'items.min' => 'Debe agregar al menos un producto.',
        'items.*.product_id.required' => 'El producto es obligatorio.',
        'items.*.product_id.exists' => 'El producto seleccionado no existe.',
        'items.*.quantity.required' => 'La cantidad es obligatoria.',
        'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
    ];

    public function mount()
    {
        $this->sale_date = now()->format('Y-m-d');
        
        // Load rooms with active reservations
        $this->rooms = Room::where('status', 'ocupada')
            ->with(['reservations' => function($q) {
                $q->where('check_in_date', '<=', now())
                  ->where('check_out_date', '>=', now())
                  ->with('customer')
                  ->latest();
            }])
            ->get()
            ->map(function($room) {
                $room->current_reservation = $room->reservations->first();
                return $room;
            });

        // Load products (only Bebidas and Mecato)
        $this->products = Product::where('status', 'active')
            ->where('quantity', '>', 0)
            ->whereHas('category', function($q) {
                $q->whereIn('name', ['Bebidas', 'Mecato']);
            })
            ->with('category')
            ->get();

        // Load categories
        $this->categories = Category::whereIn('name', ['Bebidas', 'Mecato'])->get();

        // Determine auto shift based on user role
        $user = Auth::user();
        $userRole = $user->roles->first()?->name;
        
        if ($userRole === 'Recepcionista Día') {
            $this->autoShift = 'dia';
            $this->shift = 'dia';
        } elseif ($userRole === 'Recepcionista Noche') {
            $this->autoShift = 'noche';
            $this->shift = 'noche';
        } else {
            $currentHour = (int) Carbon::now()->format('H');
            $this->autoShift = $currentHour < 14 ? 'dia' : 'noche';
            $this->shift = $this->autoShift;
        }
    }

    public function updatedPaymentMethod()
    {
        if ($this->payment_method !== 'pendiente' && $this->room_id) {
            $this->debt_status = 'pagado';
        }
        
        if ($this->payment_method === 'pendiente') {
            $this->cash_amount = null;
            $this->transfer_amount = null;
            if ($this->room_id) {
                $this->debt_status = 'pendiente';
            }
        } elseif ($this->payment_method === 'efectivo') {
            $this->cash_amount = $this->total;
            $this->transfer_amount = null;
        } elseif ($this->payment_method === 'transferencia') {
            $this->cash_amount = null;
            $this->transfer_amount = $this->total;
        } elseif ($this->payment_method === 'ambos') {
            // Keep current values or set defaults
            if (!$this->cash_amount && !$this->transfer_amount) {
                $this->cash_amount = $this->total / 2;
                $this->transfer_amount = $this->total / 2;
            }
        }
    }

    public function updatedRoomId()
    {
        if (!$this->room_id) {
            $this->debt_status = 'pagado';
        } elseif ($this->payment_method !== 'pendiente') {
            $this->debt_status = 'pagado';
        }
    }

    public function addItem()
    {
        if (!$this->selectedProduct) {
            $this->addError('selectedProduct', 'Debe seleccionar un producto.');
            return;
        }

        if ($this->selectedQuantity < 1) {
            $this->addError('selectedQuantity', 'La cantidad debe ser mayor a 0.');
            return;
        }

        $product = Product::find($this->selectedProduct);
        if (!$product) {
            $this->addError('selectedProduct', 'El producto seleccionado no existe.');
            return;
        }

        // Check if product already exists in items
        $existingIndex = collect($this->items)->search(function($item) use ($product) {
            return $item['product_id'] == $product->id;
        });

        if ($existingIndex !== false) {
            // Update quantity
            $this->items[$existingIndex]['quantity'] += $this->selectedQuantity;
        } else {
            // Add new item
            $this->items[] = [
                'product_id' => $product->id,
                'quantity' => $this->selectedQuantity,
                'product_name' => $product->name,
                'product_price' => $product->price,
                'product_category' => $product->category->name ?? 'Sin categoría',
            ];
        }

        $this->selectedProduct = null;
        $this->selectedQuantity = 1;
        $this->updatePaymentFields();
    }

    public function removeItem($index)
    {
        unset($this->items[$index]);
        $this->items = array_values($this->items);
        $this->updatePaymentFields();
    }

    public function updatedItems()
    {
        $this->updatePaymentFields();
    }

    public function updatePaymentFields()
    {
        $total = $this->total;
        
        if ($this->payment_method === 'efectivo') {
            $this->cash_amount = $total;
            $this->transfer_amount = null;
        } elseif ($this->payment_method === 'transferencia') {
            $this->cash_amount = null;
            $this->transfer_amount = $total;
        } elseif ($this->payment_method === 'ambos') {
            // Validate that sum equals total
            if ($this->cash_amount && $this->transfer_amount) {
                $sum = $this->cash_amount + $this->transfer_amount;
                if (abs($sum - $total) > 0.01 && $total > 0) {
                    // Sum doesn't match, adjust proportionally
                    $ratio = $total / $sum;
                    $this->cash_amount = round($this->cash_amount * $ratio, 2);
                    $this->transfer_amount = round($this->transfer_amount * $ratio, 2);
                }
            } elseif ($total > 0) {
                // Set defaults if not set
                $this->cash_amount = round($total / 2, 2);
                $this->transfer_amount = round($total / 2, 2);
            }
        }
    }

    public function validateBeforeSubmit()
    {
        // Basic validation in Livewire (UI level)
        if (empty($this->items)) {
            $this->addError('items', 'Debe agregar al menos un producto.');
            return false;
        }

        // Validate payment amounts for "ambos" method
        if ($this->payment_method === 'ambos') {
            $this->validate([
                'cash_amount' => 'required|numeric|min:0',
                'transfer_amount' => 'required|numeric|min:0',
            ], [
                'cash_amount.required' => 'El monto en efectivo es obligatorio cuando el método de pago es "Ambos".',
                'transfer_amount.required' => 'El monto por transferencia es obligatorio cuando el método de pago es "Ambos".',
            ]);

            $sum = $this->cash_amount + $this->transfer_amount;
            if (abs($sum - $this->total) > 0.01) {
                $this->addError('payment_method', "La suma de efectivo y transferencia debe ser igual al total: $" . number_format($this->total, 2, ',', '.'));
                return false;
            }
        }

        // Validate debt_status if room is selected
        if ($this->room_id && !$this->debt_status) {
            $this->addError('debt_status', 'El estado de deuda es obligatorio cuando se selecciona una habitación.');
            return false;
        }

        return true;
    }

    public function getFilteredProductsProperty()
    {
        if (!$this->productCategoryFilter) {
            return $this->products;
        }

        return $this->products->filter(function($product) {
            return $product->category && $product->category->name === $this->productCategoryFilter;
        });
    }

    public function render()
    {
        return view('livewire.create-sale', [
            'filteredProducts' => $this->filteredProducts,
        ]);
    }
}
