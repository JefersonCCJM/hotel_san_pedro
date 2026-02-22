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
    public bool $isModal = false;

    // Form data
    public $sale_date;
    public $room_id = '';
    public $payment_method = 'efectivo';
    public $cash_amount = null;
    public $transfer_amount = null;
    public $debt_status = 'pagado';
    public $notes = '';

    // Items
    public $items = [];

    // Computed properties
    public $rooms = [];
    public $products = [];
    public $selectedProduct = null;
    public $selectedQuantity = 1;

    public function getTotalProperty()
    {
        $total = 0;
        foreach ($this->items as $item) {
            $total += $item['product_price'] * $item['quantity'];
        }

        // Sumar también el producto que está seleccionado actualmente en el buscador pero no agregado
        if ($this->selectedProduct) {
            $prod = collect($this->products)->firstWhere('id', $this->selectedProduct);
            if ($prod) {
                $total += $prod->price * ($this->selectedQuantity ?: 0);
            }
        }

        return $total;
    }

    protected $rules = [
        'sale_date' => 'required|date',
        'room_id' => 'nullable|exists:rooms,id',
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
        'payment_method.required' => 'El método de pago es obligatorio.',
        'payment_method.in' => 'El método de pago debe ser efectivo, transferencia, ambos o pendiente.',
        'items.required' => 'Debe agregar al menos un producto.',
        'items.min' => 'Debe agregar al menos un producto.',
        'items.*.product_id.required' => 'El producto es obligatorio.',
        'items.*.product_id.exists' => 'El producto seleccionado no existe.',
        'items.*.quantity.required' => 'La cantidad es obligatoria.',
        'items.*.quantity.min' => 'La cantidad debe ser mayor a 0.',
    ];

    public function mount(bool $isModal = false)
    {
        $this->isModal = $isModal;
        $this->sale_date = now()->format('Y-m-d');

        // Cargar habitaciones ocupadas usando estado operacional (rooms.status ya no existe)
        $today = Carbon::today();

        $this->rooms = Room::query()
            ->where('is_active', true)
            ->with(['reservationRooms.reservation.customer'])
            ->get()
            ->map(function ($room) use ($today) {
                $activeReservationRoom = $room->reservationRooms->first(function ($reservationRoom) use ($today) {
                    if (empty($reservationRoom->check_in_date) || empty($reservationRoom->check_out_date)) {
                        return false;
                    }

                    $checkIn = Carbon::parse($reservationRoom->check_in_date)->startOfDay();
                    $checkOut = Carbon::parse($reservationRoom->check_out_date)->startOfDay();

                    return $today->betweenIncluded($checkIn, $checkOut);
                });

                $room->current_reservation = $activeReservationRoom?->reservation;

                return $room;
            })
            ->filter(function ($room) use ($today) {
                $operationalStatus = $room->getOperationalStatus($today);

                return in_array($operationalStatus, ['occupied', 'pending_checkout'], true)
                    && $room->current_reservation !== null;
            })
            ->values();

        // Load only active products with stock that ARE NOT cleaning products
        $this->products = Product::where('status', 'active')
            ->where('quantity', '>', 0)
            ->whereHas('category', function($q) {
                $aseoKeywords = ['aseo', 'limpieza', 'amenities', 'insumo', 'papel', 'jabon', 'cloro', 'mantenimiento'];
                foreach ($aseoKeywords as $keyword) {
                    $q->where('name', 'not like', '%' . $keyword . '%');
                }
            })
            ->with('category')
            ->get();
    }

    public function updatedPaymentMethod()
    {
        if ($this->payment_method === 'pendiente') {
            $this->debt_status = 'pendiente';
            $this->cash_amount = null;
            $this->transfer_amount = null;
        } else {
            $this->debt_status = 'pagado';

            if ($this->payment_method === 'efectivo') {
                $this->cash_amount = $this->total;
                $this->transfer_amount = null;
            } elseif ($this->payment_method === 'transferencia') {
                $this->cash_amount = null;
                $this->transfer_amount = $this->total;
            } elseif ($this->payment_method === 'ambos') {
                // Para "Ambos", dejamos que el usuario ingrese los montos manualmente
                $this->cash_amount = null;
                $this->transfer_amount = null;
            }
        }
    }

    public function updatedRoomId()
    {
        // Se mantiene disponible "pendiente" también para ventas sin habitación.
    }

    public function updatedSelectedProduct()
    {
        $this->updatePaymentFields();
    }

    public function updatedSelectedQuantity()
    {
        // Validar stock en tiempo real
        if ($this->selectedProduct && $this->selectedQuantity > 0) {
            $product = Product::find($this->selectedProduct);
            if ($product && $this->selectedQuantity > $product->quantity) {
                $this->addError('selectedQuantity', "No hay suficiente stock disponible. Stock actual: {$product->quantity} unidades.");
            } else {
                $this->resetErrorBag('selectedQuantity');
            }
        }

        $this->updatePaymentFields();
    }

    public function addItem()
    {
        if (!$this->selectedProduct) {
            $this->addError('selectedProduct', 'Debe seleccionar un producto.');
            return;
        }

        $product = Product::find($this->selectedProduct);
        if (!$product) {
            $this->addError('selectedProduct', 'El producto seleccionado no existe.');
            return;
        }

        if ($this->selectedQuantity < 1) {
            $this->addError('selectedQuantity', 'La cantidad debe ser al menos 1.');
            return;
        }

        if ($this->selectedQuantity > $product->quantity) {
            $this->addError('selectedQuantity', "Stock insuficiente. Máximo disponible: {$product->quantity}");
            return;
        }

        // Check if product already exists in items
        $existingIndex = collect($this->items)->search(function($item) use ($product) {
            return $item['product_id'] == $product->id;
        });

        if ($existingIndex !== false) {
            $newQuantity = $this->items[$existingIndex]['quantity'] + $this->selectedQuantity;

            if ($newQuantity > $product->quantity) {
                $this->addError('selectedQuantity', "No se puede agregar más. El total en la lista ({$newQuantity}) superaría el stock ({$product->quantity}).");
                return;
            }

            // Update quantity
            $this->items[$existingIndex]['quantity'] = $newQuantity;
        } else {
            // Add new item
            $this->items[] = [
                'product_id' => $product->id,
                'quantity' => $this->selectedQuantity,
                'product_name' => $product->name,
                'product_price' => $product->price,
                'product_category' => $product->category->name ?? 'Sin categoría',
                'stock_available' => $product->quantity, // Store stock for validation in list
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

    public function updatedCashAmount()
    {
        $this->cash_amount = $this->sanitizeNumber($this->cash_amount);
        if ($this->payment_method !== 'ambos') {
            $this->updatePaymentFields();
        }
    }

    public function updatedTransferAmount()
    {
        $this->transfer_amount = $this->sanitizeNumber($this->transfer_amount);
        if ($this->payment_method !== 'ambos') {
            $this->updatePaymentFields();
        }
    }

    private function sanitizeNumber($value)
    {
        if (empty($value)) return 0;

        // Si ya es un número (int o float), lo devolvemos tal cual
        if (is_int($value) || is_float($value)) return (float)$value;

        // Si es un string, quitamos puntos de miles y cambiamos coma por punto decimal
        // Esto es necesario porque PHP interpreta "3.000" como 3.0
        $clean = str_replace('.', '', (string)$value);
        $clean = str_replace(',', '.', $clean);

        return is_numeric($clean) ? (float)$clean : 0;
    }

    public function calculateTotal()
    {
        // Validate each item in the list against its stock
        foreach ($this->items as $index => $item) {
            $product = Product::find($item['product_id']);
            if ($product) {
                // Ensure quantity is at least 1
                if ($item['quantity'] < 1) {
                    $this->items[$index]['quantity'] = 1;
                }
                // Ensure quantity doesn't exceed stock
                if ($item['quantity'] > $product->quantity) {
                    $this->items[$index]['quantity'] = $product->quantity;
                    $this->addError("items.{$index}.quantity", "No hay suficiente stock disponible. Stock actual: {$product->quantity} unidades.");
                } else {
                    // Clear error if quantity is valid
                    $this->resetErrorBag("items.{$index}.quantity");
                }
            }
        }

        $this->updatePaymentFields();
    }

    public function updatedItems($value, $key)
    {
        // Validar stock cuando se actualiza la cantidad de un item
        // El key viene como "items.0.quantity" cuando se actualiza desde wire:model
        if (preg_match('/^items\.(\d+)\.quantity$/', $key, $matches)) {
            $index = (int) $matches[1];
            if (isset($this->items[$index])) {
                $item = $this->items[$index];
                $product = Product::find($item['product_id']);
                if ($product && isset($item['quantity'])) {
                    // Validar que la cantidad no exceda el stock
                    if ($item['quantity'] > $product->quantity) {
                        $this->addError("items.{$index}.quantity", "No hay suficiente stock disponible. Stock actual: {$product->quantity} unidades.");
                    } else {
                        $this->resetErrorBag("items.{$index}.quantity");
                    }
                }
            }
        }

        $this->updatePaymentFields();
    }

    public function updatePaymentFields()
    {
        // Solo automatizar si NO es el método "Ambos"
        if ($this->payment_method === 'ambos') {
            return;
        }

        $total = $this->total;

        if ($this->payment_method === 'efectivo') {
            $this->cash_amount = $total;
            $this->transfer_amount = null;
        } elseif ($this->payment_method === 'transferencia') {
            $this->cash_amount = null;
            $this->transfer_amount = $total;
        }
    }

    public function validateBeforeSubmit()
    {
        // Si la lista está vacía pero hay un producto seleccionado en el desplegable, agregarlo automáticamente
        if (empty($this->items) && $this->selectedProduct) {
            $this->addItem();
        }

        // Basic validation in Livewire (UI level)
        if (empty($this->items)) {
            $this->addError('items', 'Debe agregar al menos un producto a la lista.');
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

            $cash = (float) $this->sanitizeNumber($this->cash_amount);
            $transfer = (float) $this->sanitizeNumber($this->transfer_amount);
            $sum = $cash + $transfer;
            if (abs($sum - (float)$this->total) > 0.01) {
                $this->addError('payment_method', "La suma de efectivo y transferencia debe ser igual al total: $" . number_format($this->total, 2, ',', '.'));
                return false;
            }
        }

        // Asegurar un estado de deuda por defecto si no hay uno
        if (!$this->debt_status) {
            $this->debt_status = ($this->payment_method === 'pendiente') ? 'pendiente' : 'pagado';
        }

        return true;
    }

    public function getSumaPagosProperty()
    {
        $cash = (float) $this->sanitizeNumber($this->cash_amount);
        $transfer = (float) $this->sanitizeNumber($this->transfer_amount);
        return $cash + $transfer;
    }

    public function getDiferenciaPagosProperty()
    {
        return $this->suma_pagos - (float)$this->total;
    }

    public function render()
    {
        return view('livewire.create-sale');
    }
}
