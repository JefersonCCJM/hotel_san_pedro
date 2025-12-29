<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\ProductRepository;
use App\Models\AuditLog;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {}

    /**
     * Search products for TomSelect in sales modal.
     */
    public function search(\Illuminate\Http\Request $request): \Illuminate\Http\JsonResponse
    {
        $query = $request->query('q');
        
        $products = \App\Models\Product::where('status', 'active')
            ->when($query, function($q) use ($query) {
                $q->where(function($sub) use ($query) {
                    $sub->where('name', 'like', "%{$query}%")
                        ->orWhere('sku', 'like', "%{$query}%");
                });
            })
            ->where('quantity', '>', 0) // Usar 'quantity' en lugar de 'stock'
            ->limit(20)
            ->get();

        $results = $products->map(function($product) {
            return [
                'id' => $product->id,
                'name' => $product->name,
                'price' => (float)$product->price,
                'quantity' => $product->quantity, // Mapear 'quantity' explícitamente
                'stock' => $product->quantity, // Mantener stock para compatibilidad
                'sku' => $product->sku ?? 'N/A'
            ];
        });

        return response()->json(['results' => $results]);
    }

    /**
     * Display a listing of the resource.
     */
    public function index(): View
    {
        $filters = [
            'search' => request('search'),
            'category_id' => request('category_id'),
            'status' => request('status'),
            'order_by' => request('order_by', 'name'),
            'order_direction' => request('order_direction', 'asc'),
            'per_page' => request('per_page', 15),
        ];

        $products = $this->productRepository->searchWithFilters($filters);
        $categories = $this->productRepository->getActiveCategories();

        return view('products.index', compact('products', 'categories'));
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create(): View
    {
        $categories = $this->productRepository->getActiveCategories();

        return view('products.create', compact('categories'));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request): RedirectResponse
    {
        $data = $request->validated();
        
        // Sanitize price (remove thousand separators)
        if (isset($data['price'])) {
            $data['price'] = str_replace('.', '', $data['price']);
            $data['price'] = (float) str_replace(',', '.', $data['price']);
        }

        $data['status'] = 'active'; // Set status as active by default

        // Guardar la cantidad inicial para el movimiento, pero crear el producto con 0
        $initialQuantity = $data['quantity'] ?? 0;
        $data['quantity'] = 0;

        // Si no viene categoría (caso Aseo), asignar una por defecto
        if (empty($data['category_id'])) {
            $data['category_id'] = $this->getDefaultAseoCategoryId();
        }

        $product = \App\Models\Product::create($data);
        
        // Registrar movimiento inicial si hay stock (esto sumará al 0 inicial)
        if ($initialQuantity > 0) {
            $product->recordMovement($initialQuantity, 'input', 'Carga inicial de inventario');
        }

        $this->productRepository->clearCache();

        return redirect()->route('products.index')
            ->with('success', 'Producto creado exitosamente.');
    }

    /**
     * Get default Aseo category ID.
     */
    private function getDefaultAseoCategoryId(): int
    {
        $keywords = ['aseo', 'limpieza', 'insumo'];
        
        $category = \App\Models\Category::where(function($q) use ($keywords) {
            foreach ($keywords as $kw) {
                $q->orWhere('name', 'like', "%{$kw}%");
            }
        })->first();

        // Si no existe, crear una categoría por defecto
        if (!$category) {
            $category = \App\Models\Category::create([
                'name' => 'Insumos de Aseo',
                'description' => 'Categoría automática para productos de limpieza',
                'color' => '#6366f1',
                'is_active' => true
            ]);
        }

        return $category->id;
    }

    /**
     * Display the specified resource.
     */
    public function show(\App\Models\Product $product): View
    {
        $product = $this->productRepository->findWithCategory($product->id) ?? $product;
        $product->load(['category']);

        return view('products.show', compact('product'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(\App\Models\Product $product): View
    {
        $categories = $this->productRepository->getActiveCategories();

        return view('products.edit', compact('product', 'categories'));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateProductRequest $request, \App\Models\Product $product): RedirectResponse
    {
        $data = $request->validated();
        $newQuantity = $data['quantity'];
        
        // Sanitize price (remove thousand separators)
        if (isset($data['price'])) {
            $data['price'] = str_replace('.', '', $data['price']);
            $data['price'] = (float) str_replace(',', '.', $data['price']);
        }

        // Quitar quantity del array para que update() no lo guarde directamente
        unset($data['quantity']);

        // Si no viene categoría (caso Aseo), asignar una por defecto
        if (empty($data['category_id'])) {
            $data['category_id'] = $this->getDefaultAseoCategoryId();
        }

        $oldQuantity = $product->quantity;
        $product->update($data);
        
        // Registrar ajuste si la cantidad cambió manualmente
        if ($oldQuantity != $newQuantity) {
            $diff = $newQuantity - $oldQuantity;
            $product->recordMovement($diff, 'adjustment', 'Actualización manual desde edición de producto');
        }

        $this->productRepository->clearCache();

        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Product $product): RedirectResponse
    {
        $this->auditLog('inventory_delete', "Producto eliminado: {$product->name} (SKU: {$product->sku})", ['product_id' => $product->id]);
        
        $product->delete();
        $this->productRepository->clearCache();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }

    private function auditLog($event, $description, $metadata = [])
    {
        AuditLog::create([
            'user_id' => auth()->id(),
            'event' => $event,
            'description' => $description,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'metadata' => $metadata
        ]);
    }
}
