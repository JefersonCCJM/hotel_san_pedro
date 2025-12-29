<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Product;
use App\Models\Room;
use App\Models\InventoryMovement;
use Illuminate\Support\Facades\DB;

class InventoryAdjustment extends Component
{
    public $product_id = '';
    public $type = 'output'; // Default to output as requested (Salidas)
    public $quantity = 1;
    public $reason = '';
    public $active_category = null; // Para la navegación por desglose
    public $active_group = null;    // Para la navegación por grupo (Ventas vs Aseo)
    
    // Para la búsqueda de productos
    public $search_product = '';
    public $selected_product_name = '';

    protected $rules = [
        'product_id' => 'required|exists:products,id',
        'type' => 'required|in:input,output,adjustment',
        'quantity' => 'required|integer|min:1',
        'reason' => 'required|string|max:255',
    ];

    protected $messages = [
        'product_id.required' => 'Debe seleccionar un producto.',
        'quantity.min' => 'La cantidad debe ser al menos 1.',
        'reason.required' => 'Debe indicar una razón para el movimiento.',
    ];

    public function selectProduct($id, $name)
    {
        $this->product_id = $id;
        $this->selected_product_name = $name;
        $this->search_product = '';
        $this->active_category = null; // Reset navegación
        $this->active_group = null;    // Reset navegación
    }

    public function selectGroup($group)
    {
        $this->active_group = $group;
        $this->active_category = null;
    }

    public function selectCategory($id)
    {
        $this->active_category = $id;
    }

    public function save()
    {
        $this->validate();

        $product = Product::findOrFail($this->product_id);
        
        // Si es una salida, la cantidad debe ser negativa para el registro
        $qty = ($this->type === 'input') ? $this->quantity : -$this->quantity;

        // Validar stock suficiente para salidas
        if ($qty < 0 && $product->quantity < abs($qty)) {
            $this->addError('quantity', 'Stock insuficiente para realizar esta salida. Disponible: ' . $product->quantity);
            return;
        }

        DB::transaction(function() use ($product, $qty) {
            $product->recordMovement($qty, $this->type, $this->reason);
        });

        session()->flash('success', 'Movimiento registrado exitosamente.');
        
        $this->reset(['product_id', 'quantity', 'reason', 'selected_product_name']);
    }

    public function render()
    {
        $products = [];
        if (strlen($this->search_product) >= 2) {
            $products = Product::active()
                ->where('name', 'like', '%' . $this->search_product . '%')
                ->orWhere('sku', 'like', '%' . $this->search_product . '%')
                ->limit(5)
                ->get();
        }

        // Definir palabras clave para el grupo de Aseo
        $aseoKeywords = ['aseo', 'limpieza', 'amenities', 'insumo', 'papel', 'jabon', 'cloro', 'mantenimiento'];

        // Obtener categorías que tienen productos activos
        $allCategories = \App\Models\Category::whereHas('products', function($q) {
            $q->where('status', 'active');
        })->orderBy('name')->get();

        // Clasificar categorías en Ventas o Aseo
        $categoriesByGroup = [
            'aseo' => $allCategories->filter(function($cat) use ($aseoKeywords) {
                $name = strtolower($cat->name);
                foreach ($aseoKeywords as $keyword) {
                    if (str_contains($name, $keyword)) return true;
                }
                return false;
            }),
            'ventas' => $allCategories->filter(function($cat) use ($aseoKeywords) {
                $name = strtolower($cat->name);
                foreach ($aseoKeywords as $keyword) {
                    if (str_contains($name, $keyword)) return false;
                }
                return true;
            })
        ];

        // Categorías a mostrar según el grupo seleccionado
        $categoriesToShow = [];
        if ($this->active_group) {
            $categoriesToShow = $categoriesByGroup[$this->active_group];
        }

        $products_in_category = [];
        if ($this->active_category) {
            $products_in_category = Product::active()
                ->where('category_id', $this->active_category)
                ->orderBy('name')
                ->get();
        }

        $rooms = Room::orderBy('room_number')->get();

        return view('livewire.inventory-adjustment', [
            'search_results' => $products,
            'categories' => $categoriesToShow,
            'has_aseo' => $categoriesByGroup['aseo']->isNotEmpty(),
            'has_ventas' => $categoriesByGroup['ventas']->isNotEmpty(),
            'products_in_category' => $products_in_category,
            'rooms' => $rooms,
        ])->extends('layouts.app')
          ->section('content');
    }
}
