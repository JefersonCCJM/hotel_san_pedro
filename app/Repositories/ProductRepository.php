<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\Category;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\Cache;

class ProductRepository
{
    /**
     * Cache TTL in seconds (5 minutes).
     */
    private const CACHE_TTL = 300;

    /**
     * Search products with filters.
     *
     * @param array<string, mixed> $filters
     * @return LengthAwarePaginator
     */
    public function searchWithFilters(array $filters): LengthAwarePaginator
    {
        $query = Product::with(['category']);

        // Search filter
        if (isset($filters['search']) && $filters['search']) {
            $search = $filters['search'];
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('sku', 'like', "%{$search}%");
            });
        }

        // Category filter
        if (isset($filters['category_id']) && $filters['category_id']) {
            $query->where('category_id', $filters['category_id']);
        }

        // Status filter
        if (isset($filters['status']) && $filters['status']) {
            $query->where('status', $filters['status']);
        }

        // Ordering
        $orderBy = $filters['order_by'] ?? 'name';
        $orderDirection = $filters['order_direction'] ?? 'asc';
        $query->orderBy($orderBy, $orderDirection);

        // Pagination
        $perPage = $filters['per_page'] ?? 15;

        return $query->paginate($perPage);
    }

    /**
     * Get all active products with cache.
     *
     * @return Collection
     */
    public function getActiveProducts(): Collection
    {
        return Cache::remember('products.active', self::CACHE_TTL, function () {
            return Product::active()
                ->inStock()
                ->with('category')
                ->orderBy('name')
                ->get();
        });
    }

    /**
     * Get all active categories with cache.
     *
     * @return Collection
     */
    public function getActiveCategories(): Collection
    {
        return Cache::remember('categories.active', self::CACHE_TTL, function () {
            return Category::active()->get();
        });
    }

    /**
     * Find product by ID with relationships.
     *
     * @param int $id
     * @return Product|null
     */
    public function findWithCategory(int $id): ?Product
    {
        return Product::with('category')->find($id);
    }

    /**
     * Clear products cache.
     *
     * @return void
     */
    public function clearCache(): void
    {
        Cache::forget('products.active');
        Cache::forget('categories.active');
    }
}
