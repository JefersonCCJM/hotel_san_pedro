<?php

namespace App\Livewire;

use Livewire\Component;
use Livewire\WithPagination;
use App\Models\Product;
use App\Models\Category;
use Illuminate\Support\Facades\DB;

class ProductList extends Component
{
    use WithPagination;

    public $search = '';
    public $category_id = '';
    public $status = '';

    protected $queryString = [
        'search' => ['except' => ''],
        'category_id' => ['except' => ''],
        'status' => ['except' => ''],
    ];

    public function updatingSearch() { $this->resetPage(); }
    public function updatingCategoryId() { $this->resetPage(); }
    public function updatingStatus() { $this->resetPage(); }

    public function deleteProduct($id)
    {
        $product = Product::findOrFail($id);
        
        // Opcional: Podríamos verificar si tiene ventas, pero por ahora seguimos la lógica del controlador
        $product->delete();
        
        // Limpiar caché del repositorio
        app(\App\Repositories\ProductRepository::class)->clearCache();
        
        session()->flash('success', 'Producto eliminado exitosamente.');
    }

    public function render()
    {
        $query = Product::with('category');

        if ($this->search) {
            $query->where('name', 'like', '%' . $this->search . '%')
                  ->orWhere('sku', 'like', '%' . $this->search . '%');
        }

        if ($this->category_id) {
            $query->where('category_id', $this->category_id);
        }

        if ($this->status) {
            $query->where('status', $this->status);
        }

        $products = $query->orderBy('name')->paginate(15);
        $categories = Category::orderBy('name')->get();

        return view('livewire.product-list', [
            'products' => $products,
            'categories' => $categories,
        ]);
    }
}
