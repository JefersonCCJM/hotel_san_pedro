<?php

namespace App\Http\Controllers;

use App\Models\Customer;
use App\Models\CustomerTaxProfile;
use App\Http\Requests\SaveCustomerTaxProfileRequest;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;
use App\Models\DianCustomerTribute;
use App\Models\DianIdentificationDocument;
use App\Models\DianLegalOrganization;
use App\Models\DianMunicipality;
use Illuminate\Contracts\View\View as ViewContract;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Response;
use Illuminate\Support\Facades\View;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request): ViewContract
    {
        $query = Customer::query();

        // Filtros
        if ($request->filled('search')) {
            $query->where('name', 'like', '%' . $request->search . '%')
                  ->orWhere('email', 'like', '%' . $request->search . '%')
                  ->orWhere('phone', 'like', '%' . $request->search . '%');
        }

        if ($request->filled('status')) {
            $query->where('is_active', $request->status === 'active');
        }

        $customers = $query->orderBy('name')->paginate(15);

        return View::make('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     * Loads all necessary catalogs for electronic invoice configuration.
     */
    public function create(): ViewContract
    {
        return View::make('customers.create', $this->getTaxCatalogs());
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request): RedirectResponse|JsonResponse
    {
        $data = $request->validated();
        $data['name'] = mb_strtoupper($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['requires_electronic_invoice'] = $request->boolean('requires_electronic_invoice');

        $customer = Customer::create($data);

        $this->syncTaxProfile($customer, $data, $data['requires_electronic_invoice']);

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax()) {
            $customer->load('taxProfile.identificationDocument');
            /** @var \App\Models\CustomerTaxProfile|null $taxProfile */
            $taxProfile = $customer->taxProfile;
            $customerData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ];

            // Include tax profile data if exists
            if ($taxProfile) {
                $customerData['tax_profile'] = [
                    'identification' => $taxProfile->identification,
                    'dv' => $taxProfile->dv,
                    'document_type' => $taxProfile->identificationDocument?->code,
                ];
            }

            return Response::json([
                'success' => true,
                'customer' => $customerData,
                'message' => 'Cliente creado exitosamente.'
            ]);
        }

        return Redirect::route('customers.show', $customer)
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer): ViewContract
    {
        $customer->load([
            'taxProfile.identificationDocument',
            'taxProfile.legalOrganization',
            'taxProfile.tribute',
            'taxProfile.municipality',
        ]);

        return View::make('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer): ViewContract
    {
        $customer->load(['taxProfile.municipality', 'taxProfile.identificationDocument']);

        return View::make('customers.edit', array_merge(
            ['customer' => $customer],
            $this->getTaxCatalogs()
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer): RedirectResponse
    {
        $data = $request->validated();
        $data['name'] = mb_strtoupper($data['name']);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['requires_electronic_invoice'] = $request->boolean('requires_electronic_invoice');

        // Update customer
        $customer->update($data);

        $this->syncTaxProfile($customer, $data, $data['requires_electronic_invoice']);

        return Redirect::route('customers.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Toggle customer status (active/inactive).
     */
    public function toggleStatus(Customer $customer): RedirectResponse
    {
        $customer->update([
            'is_active' => !$customer->is_active
        ]);

        $status = $customer->is_active ? 'activado' : 'desactivado';

        return Redirect::back()
            ->with('success', "Cliente {$status} correctamente.");
    }

    /**
     * Get tax profile data for a customer (API endpoint)
     */
    public function getTaxProfile(Customer $customer): JsonResponse
    {
        $customer->load('taxProfile.identificationDocument');
        // ...
    }

    /**
     * Search customers for TomSelect.
     */
    public function search(Request $request): JsonResponse
    {
        $query = $request->query('q', '');

        $customersQuery = Customer::where('is_active', true);

        if (!empty($query) && strlen($query) >= 1) {
            $customersQuery->where(function($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('email', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhereHas('taxProfile', function($subQ) use ($query) {
                      $subQ->where('identification', 'like', "%{$query}%");
                  });
            });
        }

        // If no query, return last 5 customers by creation date
        if (empty($query) || strlen($query) < 1) {
            $customers = $customersQuery
                ->orderBy('created_at', 'desc')
                ->orderBy('id', 'desc')
                ->limit(5)
                ->get();
        } else {
            // If there's a query, search and order by name
            $customers = $customersQuery
                ->orderBy('name')
                ->limit(5)
                ->get();
        }

        $results = $customers->map(function($customer) {
            return [
                'id' => $customer->id,
                'name' => $customer->name,
                'identification' => $customer->taxProfile?->identification ?? 'S/N',
                'phone' => $customer->phone ?? 'S/N'
            ];
        });

        return Response::json(['results' => $results]);
    }

    /**
     * Check if a document number already exists.
     */
    public function checkIdentification(Request $request): JsonResponse
    {
        $identification = $request->query('identification');
        $excludeId = $request->query('exclude_id');

        if (!$identification) {
            return Response::json(['exists' => false]);
        }

        // Buscamos el perfil fiscal por identificación
        $profile = CustomerTaxProfile::where('identification', $identification)
            ->when($excludeId, function ($q) use ($excludeId) {
                $q->where('customer_id', '!=', $excludeId);
            })
            ->first();

        if ($profile) {
            $customer = Customer::withTrashed()->find($profile->customer_id);
            if ($customer) {
                return Response::json([
                    'exists' => true,
                    'name' => $customer->name,
                    'id' => $customer->id,
                ]);
            }
        }

        return Response::json(['exists' => false]);
    }

    /**
     * Save tax profile for a customer (API endpoint)
     */
    public function saveTaxProfile(SaveCustomerTaxProfileRequest $request, Customer $customer): JsonResponse
    {
        $validated = $request->validated();

        $customer->update([
            'requires_electronic_invoice' => (bool) $validated['requires_electronic_invoice'],
        ]);

        $this->syncTaxProfile(
            $customer,
            $validated,
            (bool) $validated['requires_electronic_invoice']
        );

        $customer->load('taxProfile');

        return Response::json([
            'success' => true,
            'message' => 'Configuración fiscal actualizada correctamente',
            'customer' => [
                'requires_electronic_invoice' => $customer->requires_electronic_invoice,
                'has_complete_tax_profile' => $customer->hasCompleteTaxProfileData(),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }

    private function getTaxCatalogs(): array
    {
        return [
            'identificationDocuments' => DianIdentificationDocument::query()->orderBy('id')->get(),
            'legalOrganizations' => DianLegalOrganization::query()->orderBy('id')->get(),
            'tributes' => DianCustomerTribute::query()->orderBy('id')->get(),
            'municipalities' => DianMunicipality::query()
                ->orderBy('department')
                ->orderBy('name')
                ->get(),
        ];
    }

    private function syncTaxProfile(Customer $customer, array $input, bool $requiresElectronicInvoice): void
    {
        // Siempre sincronizamos el perfil fiscal porque ahí reside la identificación del cliente,
        // independientemente de si requiere factura electrónica o no.
        $attributes = $this->buildTaxProfileData($input);

        if ($customer->taxProfile) {
            $customer->taxProfile->update($attributes);

            return;
        }

        CustomerTaxProfile::create(array_merge(
            ['customer_id' => $customer->id],
            $attributes
        ));
    }

    private function buildTaxProfileData(array $input): array
    {
        // Fallback for municipality: use company setting, first available, or Bogotá (149) as last resort
        $municipalityId = $input['municipality_id'] ?? null;
        if (!$municipalityId) {
            $municipalityId = \App\Models\CompanyTaxSetting::first()?->municipality_id
                ?? \App\Models\DianMunicipality::first()?->factus_id
                ?? 149; // Bogotá Factus ID
        }

        return [
            'identification_document_id' => $input['identification_document_id'] ?? 3, // Default to CC
            'identification' => $input['identification'] ?? null,
            'municipality_id' => $municipalityId,
            'dv' => $input['dv'] ?? null,
            'legal_organization_id' => $input['legal_organization_id'] ?? 2, // Default to Persona Natural
            'company' => $input['company'] ?? null,
            'trade_name' => $input['trade_name'] ?? null,
            'names' => $input['names'] ?? $input['name'] ?? null,
            'address' => $input['tax_address'] ?? $input['address'] ?? 'Calle 1 #1-1', // Default address if missing
            'email' => $input['tax_email'] ?? $input['email'] ?? 'cliente@hotel.com', // Default email if missing
            'phone' => $input['tax_phone'] ?? $input['phone'] ?? null,
            'tribute_id' => $input['tribute_id'] ?? 21, // Default to No responsable de IVA
        ];
    }
}
