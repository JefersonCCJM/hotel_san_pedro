<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\InventoryMovement;
use App\Models\Product;
use App\Models\Category;
use App\Models\Room;

class InventoryHistory extends Component
{
    use WithPagination;

    public $type = '';
    public $product_id = '';
    public $category_id = '';
    public $group = ''; // Para filtrar por Ventas o Aseo
    public $start_date = '';
    public $end_date = '';

    protected $queryString = [
        'type' => ['except' => ''],
        'product_id' => ['except' => ''],
        'category_id' => ['except' => ''],
        'group' => ['except' => ''],
        'start_date' => ['except' => ''],
        'end_date' => ['except' => ''],
    ];

    public function clearFilters()
    {
        $this->reset(['type', 'product_id', 'category_id', 'group', 'start_date', 'end_date']);
        $this->resetPage();
    }

    public function render()
    {
        $query = InventoryMovement::with(['product.category', 'user', 'room'])
            ->latest();

        if ($this->type) {
            $query->where('type', $this->type);
        }

        if ($this->group) {
            $aseoKeywords = ['aseo', 'limpieza', 'amenities', 'insumo', 'papel', 'jabon', 'cloro', 'mantenimiento'];
            
            $query->whereHas('product.category', function($q) use ($aseoKeywords) {
                $q->where(function($sub) use ($aseoKeywords) {
                    if ($this->group === 'aseo') {
                        foreach ($aseoKeywords as $keyword) {
                            $sub->orWhere('name', 'like', '%' . $keyword . '%');
                        }
                    } else {
                        foreach ($aseoKeywords as $keyword) {
                            $sub->where('name', 'not like', '%' . $keyword . '%');
                        }
                    }
                });
            });
        }

        if ($this->product_id) {
            $query->where('product_id', $this->product_id);
        }

        if ($this->category_id) {
            $query->whereHas('product', function($q) {
                $q->where('category_id', $this->category_id);
            });
        }

        if ($this->start_date) {
            $query->whereDate('created_at', '>=', $this->start_date);
        }

        if ($this->end_date) {
            $query->whereDate('created_at', '<=', $this->end_date);
        }

        $movements = $query->paginate(15);
        $products = Product::active()->orderBy('name')->get();
        $categories = Category::orderBy('name')->get();

        return view('livewire.inventory-history', [
            'movements' => $movements,
            'products' => $products,
            'categories' => $categories,
        ])->extends('layouts.app')
          ->section('content');
    }
}
