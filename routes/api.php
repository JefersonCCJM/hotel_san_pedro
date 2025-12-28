<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/municipalities/search', function (Request $request) {
    $term = $request->query('q', '');

    if (strlen($term) < 2) {
        return response()->json([]);
    }

    $municipalities = \App\Models\DianMunicipality::search($term)
        ->limit(20)
        ->get()
        ->map(function ($municipality) {
            return [
                'factus_id' => $municipality->factus_id,
                'name' => $municipality->name,
                'department' => $municipality->department,
                'code' => $municipality->code,
                'display' => "{$municipality->name} – {$municipality->department}",
            ];
        });

    return response()->json($municipalities);
});

Route::get('/measurement-units/search', function (Request $request) {
    $term = $request->query('q', '');

    if (strlen($term) < 2) {
        return response()->json([]);
    }

    $units = \App\Models\DianMeasurementUnit::search($term)
        ->limit(20)
        ->get()
        ->map(function ($unit) {
            return [
                'factus_id' => $unit->factus_id,
                'name' => $unit->name,
                'code' => $unit->code,
                'display' => "{$unit->name} ({$unit->code})",
            ];
        });

    return response()->json($units);
});

Route::get('/customers/search', function (Request $request) {
    $term = $request->query('q', '');
    $excludeOccupied = $request->query('exclude_occupied', false);

    $query = \App\Models\Customer::active()->with('taxProfile.identificationDocument');

    // Filter out customers with active reservations if requested
    if ($excludeOccupied) {
        $today = now()->startOfDay();
        $query->whereDoesntHave('reservations', function($q) use ($today) {
            $q->where('check_in_date', '<=', $today)
              ->where('check_out_date', '>', $today);
        });
    }

    if (strlen($term) >= 2) {
        $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('email', 'like', "%{$term}%")
              ->orWhere('phone', 'like', "%{$term}%")
              ->orWhereHas('taxProfile', function($subQ) use ($term) {
                  $subQ->where('identification', 'like', "%{$term}%");
              });
        });
    }

    $customers = $query->orderBy('name')->limit(50)->get()->map(function ($customer) {
        $text = $customer->name;

        // Add document number if available
        if ($customer->taxProfile && $customer->taxProfile->identification) {
            $documentType = $customer->taxProfile->identificationDocument ?
                $customer->taxProfile->identificationDocument->code . ': ' : '';
            $documentNumber = $customer->taxProfile->identification;
            $dv = $customer->taxProfile->dv ? '-' . $customer->taxProfile->dv : '';
            $text .= ' - ' . $documentType . $documentNumber . $dv;
        }

        return [
            'id' => $customer->id,
            'text' => $text,
            'name' => $customer->name,
            'email' => $customer->email,
            'phone' => $customer->phone,
            'identification' => $customer->taxProfile?->identification,
            'document_type' => $customer->taxProfile?->identificationDocument?->code,
        ];
    });

    return response()->json(['results' => $customers]);
});

Route::get('/products/search', function (Request $request) {
    $term = $request->query('q', '');

    $query = \App\Models\Product::where('status', 'active');

    if (strlen($term) >= 2) {
        $query->where(function ($q) use ($term) {
            $q->where('name', 'like', "%{$term}%")
              ->orWhere('sku', 'like', "%{$term}%");
        });
    }

    $products = $query->orderBy('name')->limit(50)->get()->map(function ($product) {
        return [
            'id' => $product->id,
            'text' => $product->name . ' - Stock: ' . $product->quantity,
            'name' => $product->name,
            'sku' => $product->sku,
            'price' => $product->price,
            'stock' => $product->quantity,
        ];
    });

    return response()->json(['results' => $products]);
});

/*
|--------------------------------------------------------------------------
| PUBLIC CLEANING MODULE API - REMOVED
|--------------------------------------------------------------------------
|
| This endpoint has been replaced by Livewire component CleaningPanel.
| All cleaning operations are now handled via Livewire in the web routes.
|
*/
