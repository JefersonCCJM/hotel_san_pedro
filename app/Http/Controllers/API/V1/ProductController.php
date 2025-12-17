<?php

namespace App\Http\Controllers\API\V1;

use App\Http\Requests\API\V1\ProductIndexRequest;
use App\Models\Product;

class ProductController
{
    public function index(ProductIndexRequest $request){

        $filters = $request->validated();
        $products = Product::query();

        if (!empty($filters['category_id'] ?? null)) {
            $products->where('category_id', $filters['category_id']);
        }

        return response()->json($products->get());
    }
}
