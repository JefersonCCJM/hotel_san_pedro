<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Repositories\ProductRepository;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ProductController extends Controller
{
    public function __construct(
        private readonly ProductRepository $productRepository
    ) {}

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
        $data['status'] = 'active'; // Set status as active by default

        \App\Models\Product::create($data);
        $this->productRepository->clearCache();

        return redirect()->route('products.index')
            ->with('success', 'Producto creado exitosamente.');
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
        $product->update($request->validated());
        $this->productRepository->clearCache();

        return redirect()->route('products.index')
            ->with('success', 'Producto actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(\App\Models\Product $product): RedirectResponse
    {
        $product->delete();
        $this->productRepository->clearCache();

        return redirect()->route('products.index')
            ->with('success', 'Producto eliminado exitosamente.');
    }
}
