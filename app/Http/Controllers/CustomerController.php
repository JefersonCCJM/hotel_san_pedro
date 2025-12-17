<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Customer;
use App\Models\CustomerTaxProfile;
use App\Http\Requests\StoreCustomerRequest;
use App\Http\Requests\UpdateCustomerRequest;

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
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

        return view('customers.index', compact('customers'));
    }

    /**
     * Show the form for creating a new resource.
     * Loads all necessary catalogs for electronic invoice configuration.
     */
    public function create()
    {
        // Load DIAN catalogs required for electronic invoice setup
        $identificationDocuments = \App\Models\DianIdentificationDocument::orderBy('id')->get();
        $legalOrganizations = \App\Models\DianLegalOrganization::orderBy('id')->get();
        $tributes = \App\Models\DianCustomerTribute::orderBy('id')->get();
        $municipalities = \App\Models\DianMunicipality::orderBy('department')->orderBy('name')->get();
        
        return view('customers.create', compact(
            'identificationDocuments',
            'legalOrganizations',
            'tributes',
            'municipalities'
        ));
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreCustomerRequest $request)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['requires_electronic_invoice'] = $request->boolean('requires_electronic_invoice');
        $requiresElectronicInvoice = $data['requires_electronic_invoice'];

        $customer = Customer::create($data);

        // Handle tax profile creation for electronic invoicing
        if ($requiresElectronicInvoice) {
            CustomerTaxProfile::create([
                'customer_id' => $customer->id,
                'identification_document_id' => $request->input('identification_document_id'),
                'identification' => $request->input('identification'),
                'municipality_id' => $request->input('municipality_id'),
                'dv' => $request->input('dv'),
                'legal_organization_id' => $request->input('legal_organization_id'),
                'company' => $request->input('company'),
                'trade_name' => $request->input('trade_name'),
                'names' => $request->input('names'),
                'address' => $request->input('tax_address') ?: $request->input('address'),
                'email' => $request->input('tax_email') ?: $request->input('email'),
                'phone' => $request->input('tax_phone') ?: $request->input('phone'),
                'tribute_id' => $request->input('tribute_id'),
            ]);
        }

        // Si es una petición AJAX, devolver JSON
        if ($request->ajax()) {
            $customer->load('taxProfile.identificationDocument');
            $customerData = [
                'id' => $customer->id,
                'name' => $customer->name,
                'email' => $customer->email,
            ];
            
            // Include tax profile data if exists
            if ($customer->taxProfile) {
                $customerData['tax_profile'] = [
                    'identification' => $customer->taxProfile->identification,
                    'dv' => $customer->taxProfile->dv,
                    'document_type' => $customer->taxProfile->identificationDocument?->code,
                ];
            }
            
            return response()->json([
                'success' => true,
                'customer' => $customerData,
                'message' => 'Cliente creado exitosamente.'
            ]);
        }

        return redirect()->route('customers.index')
            ->with('success', 'Cliente creado exitosamente.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Customer $customer)
    {
        $customer->load('taxProfile');

        return view('customers.show', compact('customer'));
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Customer $customer)
    {
        $customer->load(['taxProfile.municipality', 'taxProfile.identificationDocument']);
        
        $identificationDocuments = \App\Models\DianIdentificationDocument::orderBy('id')->get();
        $legalOrganizations = \App\Models\DianLegalOrganization::orderBy('id')->get();
        $tributes = \App\Models\DianCustomerTribute::orderBy('id')->get();
        $municipalities = \App\Models\DianMunicipality::orderBy('department')->orderBy('name')->get();
        
        return view('customers.edit', compact(
            'customer',
            'identificationDocuments',
            'legalOrganizations',
            'tributes',
            'municipalities'
        ));
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UpdateCustomerRequest $request, Customer $customer)
    {
        $data = $request->validated();
        $data['is_active'] = $request->boolean('is_active');
        $data['requires_electronic_invoice'] = $request->boolean('requires_electronic_invoice');
        $requiresElectronicInvoice = $data['requires_electronic_invoice'];

        // Update customer
        $customer->update($data);

        // Handle tax profile
        if ($requiresElectronicInvoice) {
            if ($customer->taxProfile) {
                // Update existing profile
                $customer->taxProfile->update([
                    'identification_document_id' => $request->input('identification_document_id'),
                    'identification' => $request->input('identification'),
                    'dv' => $request->input('dv'),
                    'legal_organization_id' => $request->input('legal_organization_id'),
                    'company' => $request->input('company'),
                    'trade_name' => $request->input('trade_name'),
                    'names' => $request->input('names'),
                    'address' => $request->input('tax_address') ?: $request->input('address'),
                    'email' => $request->input('tax_email') ?: $request->input('email'),
                    'phone' => $request->input('tax_phone') ?: $request->input('phone'),
                    'tribute_id' => $request->input('tribute_id'),
                    'municipality_id' => $request->input('municipality_id'),
                ]);
            } else {
                // Create new profile
                CustomerTaxProfile::create([
                    'customer_id' => $customer->id,
                    'identification_document_id' => $request->input('identification_document_id'),
                    'identification' => $request->input('identification'),
                    'dv' => $request->input('dv'),
                    'legal_organization_id' => $request->input('legal_organization_id'),
                    'company' => $request->input('company'),
                    'trade_name' => $request->input('trade_name'),
                    'names' => $request->input('names'),
                    'address' => $request->input('tax_address') ?: $request->input('address'),
                    'email' => $request->input('tax_email') ?: $request->input('email'),
                    'phone' => $request->input('tax_phone') ?: $request->input('phone'),
                    'tribute_id' => $request->input('tribute_id'),
                    'municipality_id' => $request->input('municipality_id'),
                ]);
            }
        } else {
            // Remove tax profile if exists
            if ($customer->taxProfile) {
                $customer->taxProfile->delete();
            }
        }

        return redirect()->route('customers.index')
            ->with('success', 'Cliente actualizado exitosamente.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()->route('customers.index')
            ->with('success', 'Cliente eliminado exitosamente.');
    }

    /**
     * Get tax profile data for a customer (API endpoint)
     */
    public function getTaxProfile(Customer $customer)
    {
        try {
            $customer->load('taxProfile.identificationDocument');
            
            // Load catalogs needed for the form
            $identificationDocuments = \App\Models\DianIdentificationDocument::orderBy('id')->get();
            $legalOrganizations = \App\Models\DianLegalOrganization::orderBy('id')->get();
            $tributes = \App\Models\DianCustomerTribute::orderBy('id')->get();
            $municipalities = \App\Models\DianMunicipality::orderBy('department')->orderBy('name')->get();

            return response()->json([
                'customer' => [
                    'id' => $customer->id,
                    'name' => $customer->name,
                    'requires_electronic_invoice' => (bool) $customer->requires_electronic_invoice,
                    'tax_profile' => $customer->taxProfile ? [
                        'identification_document_id' => $customer->taxProfile->identification_document_id,
                        'identification' => $customer->taxProfile->identification,
                        'dv' => $customer->taxProfile->dv,
                        'legal_organization_id' => $customer->taxProfile->legal_organization_id,
                        'company' => $customer->taxProfile->company,
                        'trade_name' => $customer->taxProfile->trade_name,
                        'names' => $customer->taxProfile->names,
                        'address' => $customer->taxProfile->address,
                        'email' => $customer->taxProfile->email,
                        'phone' => $customer->taxProfile->phone,
                        'tribute_id' => $customer->taxProfile->tribute_id,
                        'municipality_id' => $customer->taxProfile->municipality_id,
                    ] : null,
                ],
                'catalogs' => [
                    'identification_documents' => $identificationDocuments->map(fn($doc) => [
                        'id' => $doc->id,
                        'code' => $doc->code,
                        'name' => $doc->name,
                        'requires_dv' => (bool) $doc->requires_dv,
                    ])->values(),
                    'legal_organizations' => $legalOrganizations->map(fn($org) => [
                        'id' => $org->id,
                        'name' => $org->name,
                    ])->values(),
                    'tributes' => $tributes->map(fn($t) => [
                        'id' => $t->id,
                        'code' => $t->code,
                        'name' => $t->name,
                    ])->values(),
                    'municipalities' => $municipalities->groupBy('department')->map(function($municipalities) {
                        return $municipalities->map(fn($m) => [
                            'factus_id' => $m->factus_id,
                            'name' => $m->name,
                            'department' => $m->department,
                        ])->values();
                    }),
                ],
            ], 200, [], JSON_UNESCAPED_UNICODE);
        } catch (\Exception $e) {
            \Illuminate\Support\Facades\Log::error('Error al obtener perfil fiscal del cliente', [
                'customer_id' => $customer->id,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            return response()->json([
                'error' => true,
                'message' => 'Error al cargar los datos del cliente: ' . $e->getMessage(),
            ], 500, [], JSON_UNESCAPED_UNICODE);
        }
    }

    /**
     * Save tax profile for a customer (API endpoint)
     */
    public function saveTaxProfile(Request $request, Customer $customer)
    {
        $validated = $request->validate([
            'requires_electronic_invoice' => 'required|boolean',
            'identification_document_id' => 'required_if:requires_electronic_invoice,true|exists:dian_identification_documents,id',
            'identification' => 'required_if:requires_electronic_invoice,true|string|max:20',
            'dv' => 'nullable|string|max:1',
            'legal_organization_id' => 'nullable|exists:dian_legal_organizations,id',
            'company' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'names' => 'nullable|string|max:255',
            'address' => 'nullable|string|max:500',
            'email' => 'nullable|email|max:255',
            'phone' => 'nullable|string|max:20',
            'tribute_id' => 'nullable|exists:dian_customer_tributes,id',
            'municipality_id' => [
                'required_if:requires_electronic_invoice,true',
                function ($attribute, $value, $fail) {
                    if ($value && !\App\Models\DianMunicipality::where('factus_id', $value)->exists()) {
                        $fail('El municipio seleccionado no es válido.');
                    }
                },
            ],
        ]);

        // Update customer
        $customer->update([
            'requires_electronic_invoice' => $validated['requires_electronic_invoice'],
        ]);

        // Handle tax profile
        if ($validated['requires_electronic_invoice']) {
            if ($customer->taxProfile) {
                $customer->taxProfile->update([
                    'identification_document_id' => $validated['identification_document_id'],
                    'identification' => $validated['identification'],
                    'dv' => $validated['dv'] ?? null,
                    'legal_organization_id' => $validated['legal_organization_id'] ?? null,
                    'company' => $validated['company'] ?? null,
                    'trade_name' => $validated['trade_name'] ?? null,
                    'names' => $validated['names'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'tribute_id' => $validated['tribute_id'] ?? null,
                    'municipality_id' => $validated['municipality_id'],
                ]);
            } else {
                CustomerTaxProfile::create([
                    'customer_id' => $customer->id,
                    'identification_document_id' => $validated['identification_document_id'],
                    'identification' => $validated['identification'],
                    'dv' => $validated['dv'] ?? null,
                    'legal_organization_id' => $validated['legal_organization_id'] ?? null,
                    'company' => $validated['company'] ?? null,
                    'trade_name' => $validated['trade_name'] ?? null,
                    'names' => $validated['names'] ?? null,
                    'address' => $validated['address'] ?? null,
                    'email' => $validated['email'] ?? null,
                    'phone' => $validated['phone'] ?? null,
                    'tribute_id' => $validated['tribute_id'] ?? null,
                    'municipality_id' => $validated['municipality_id'],
                ]);
            }
        } else {
            if ($customer->taxProfile) {
                $customer->taxProfile->delete();
            }
        }

            $customer->load('taxProfile');

        return response()->json([
            'success' => true,
            'message' => 'Configuración fiscal actualizada correctamente',
            'customer' => [
                'requires_electronic_invoice' => $customer->requires_electronic_invoice,
                'has_complete_tax_profile' => $customer->hasCompleteTaxProfileData(),
            ],
        ], 200, [], JSON_UNESCAPED_UNICODE);
    }
}
