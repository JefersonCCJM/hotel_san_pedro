<?php

namespace App\Services;

// TODO: Adaptar cuando se implementen las reservas
// use App\Models\Reservation;
use App\Models\Customer;
use App\Models\ElectronicInvoice;
use App\Models\ElectronicInvoiceItem;
use App\Models\CompanyTaxSetting;
use App\Models\DianDocumentType;
use App\Models\DianOperationType;
use App\Models\DianPaymentMethod;
use App\Models\DianPaymentForm;
use App\Models\Service;
use App\Services\FactusNumberingRangeService;
use App\Exceptions\FactusApiException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ElectronicInvoiceService
{
    public function __construct(
        private FactusApiService $apiService,
        private FactusNumberingRangeService $numberingRangeService
    ) {}

    // TODO: Adaptar este método cuando se implementen las reservas
    // La facturación electrónica se generará desde las reservas, no desde ventas
    // public function createFromReservation(Reservation $reservation): ElectronicInvoice
    // {
    //     ...
    // }

    /**
     * Create electronic invoice from form data.
     *
     * @param array<string, mixed> $data
     * @return ElectronicInvoice
     * @throws \Exception
     */
    public function createFromForm(array $data): ElectronicInvoice
    {
        DB::beginTransaction();
        
        try {
            $customer = Customer::with('taxProfile')->findOrFail($data['customer_id']);
            
            // Agregar logs detallados para depurar el perfil fiscal
            Log::info('Customer data:', [
                'customer_id' => $customer->id,
                'customer_name' => $customer->name,
                'requires_electronic_invoice' => $customer->requires_electronic_invoice,
                'has_tax_profile' => $customer->taxProfile ? true : false,
            ]);
            
            if ($customer->taxProfile) {
                Log::info('Tax profile data:', [
                    'identification_document_id' => $customer->taxProfile->identification_document_id,
                    'identification' => $customer->taxProfile->identification,
                    'legal_organization_id' => $customer->taxProfile->legal_organization_id,
                    'tribute_id' => $customer->taxProfile->tribute_id,
                    'municipality_id' => $customer->taxProfile->municipality_id,
                    'dv' => $customer->taxProfile->dv,
                ]);
            }
            
            if (!$customer->hasCompleteTaxProfileData()) {
                $missingFields = [];
                $taxProfile = $customer->taxProfile;
                
                if (!$taxProfile) {
                    $missingFields[] = 'tax_profile (no existe)';
                } else {
                    // Verificar cada campo requerido por el método hasCompleteTaxProfileData()
                    if (empty($taxProfile->identification_document_id)) $missingFields[] = 'identification_document_id';
                    if (empty($taxProfile->identification)) $missingFields[] = 'identification';
                    if (empty($taxProfile->municipality_id)) $missingFields[] = 'municipality_id';
                    
                    // Verificar DV si es requerido
                    if ($taxProfile->requiresDV() && empty($taxProfile->dv)) $missingFields[] = 'dv';
                    
                    // Verificar company si es persona jurídica
                    if ($taxProfile->isJuridicalPerson() && empty($taxProfile->company)) $missingFields[] = 'company (requerido para persona jurídica)';
                    
                    // Agregar logs específicos para depuración
                    Log::info('Tax profile validation details:', [
                        'requiresDV' => $taxProfile->requiresDV(),
                        'hasDV' => !empty($taxProfile->dv),
                        'isJuridicalPerson' => $taxProfile->isJuridicalPerson(),
                        'hasCompany' => !empty($taxProfile->company),
                        'identification_document_id' => $taxProfile->identification_document_id,
                        'identification' => $taxProfile->identification,
                        'municipality_id' => $taxProfile->municipality_id,
                    ]);
                }
                
                Log::error('Customer missing tax profile fields:', [
                    'customer_id' => $customer->id,
                    'customer_name' => $customer->name,
                    'missing_fields' => $missingFields,
                    'tax_profile_data' => $taxProfile ? $taxProfile->toArray() : null,
                ]);
                
                throw new \Exception('El cliente no tiene perfil fiscal completo. Campos faltantes: ' . implode(', ', $missingFields));
            }
            
            // Get document type code
            $documentType = DianDocumentType::findOrFail($data['document_type_id']);
            
            // Get numbering range directly by ID (not by document type)
            $numberingRange = \App\Models\FactusNumberingRange::findOrFail($data['numbering_range_id']);
            if (!$numberingRange || !$numberingRange->is_active || $numberingRange->is_expired) {
                throw new \Exception('El rango de numeración seleccionado no está activo o está vencido.');
            }
            
            Log::info('Using numbering range:', [
                'range_id' => $numberingRange->id,
                'document' => $numberingRange->document,
                'prefix' => $numberingRange->prefix,
                'is_active' => $numberingRange->is_active,
                'is_expired' => $numberingRange->is_expired,
            ]);
            
            // Crear la factura en la base de datos local primero
            $invoice = ElectronicInvoice::create([
                'customer_id' => $customer->id,
                'factus_numbering_range_id' => $numberingRange->factus_id,
                'document_type_id' => $data['document_type_id'],
                'operation_type_id' => $data['operation_type_id'],
                'payment_method_code' => $data['payment_method_code'],
                'payment_form_code' => $data['payment_form_code'],
                'reference_code' => $data['reference_code'] ?? $this->generateReferenceCode(),
                'document' => $this->generateDocumentNumber($numberingRange),
                'status' => 'pending',
                'gross_value' => $data['totals']['subtotal'],
                'tax_amount' => $data['totals']['tax'],
                'discount_amount' => 0,
                'total' => $data['totals']['total'],
            ]);
            
            // Crear los items con datos del formulario (no desde servicios)
            foreach ($data['items'] as $itemData) {
                ElectronicInvoiceItem::create([
                    'electronic_invoice_id' => $invoice->id,
                    'name' => $itemData['name'],
                    'quantity' => $itemData['quantity'],
                    'price' => $itemData['price'],
                    'tax_rate' => $itemData['tax_rate'],
                    'tax_amount' => $itemData['tax'],
                    'total' => $itemData['total'],
                    'discount_rate' => 0,
                    'is_excluded' => false,
                    // Valores por defecto para los campos requeridos (usando IDs correctos)
                    'tribute_id' => 18, // IVA (ID 18)
                    'standard_code_id' => 1, // Estándar por defecto
                    'unit_measure_id' => 70, // Unidad por defecto
                    'code_reference' => 'SRV-' . uniqid(), // Código de referencia generado
                ]);
            }
            
            // Enviar a Factus API
            $this->sendToFactus($invoice);
            
            DB::commit();
            
            return $invoice->fresh(['customer', 'items']);
        } catch (\Exception $e) {
            DB::rollBack();
            Log::error('Error creating electronic invoice from form', [
                'error' => $e->getMessage(),
                'data' => $data,
            ]);
            throw $e;
        }
    }

    /**
     * Send invoice to Factus API.
     *
     * @param ElectronicInvoice $invoice
     * @return void
     * @throws \Exception
     */
    private function sendToFactus(ElectronicInvoice $invoice): void
    {
        $company = CompanyTaxSetting::first();
        if (!$company) {
            throw new \Exception('La configuración fiscal de la empresa no está completa.');
        }
        
        $payload = $this->buildPayload($invoice, $company);
        
        // Agregar log del payload para depuración
        Log::info('Payload enviado a Factus API:', [
            'payload' => $payload,
            'invoice_id' => $invoice->id,
            'document_number' => $invoice->document,
        ]);
        
        try {
            // Usar el endpoint de validación según la documentación
            $response = $this->apiService->post('/v1/bills/validate', $payload);
            
            // Agregar log de la respuesta
            Log::info('Respuesta de Factus API:', [
                'response' => $response,
                'invoice_id' => $invoice->id,
            ]);
            
            $status = $this->mapStatusFromResponse($response);
            
            $updateData = [
                'status' => $status,
                'payload_sent' => $payload,
                'response_dian' => $response,
            ];
            
            // Actualizar campos según la respuesta de la API
            if (isset($response['data']['bill']['cufe']) && !empty($response['data']['bill']['cufe'])) {
                $updateData['cufe'] = $response['data']['bill']['cufe'];
            }
            
            if (isset($response['data']['bill']['qr']) && !empty($response['data']['bill']['qr'])) {
                $updateData['qr'] = $response['data']['bill']['qr'];
            }
            
            if (isset($response['data']['bill']['number']) && !empty($response['data']['bill']['number'])) {
                $updateData['document'] = $response['data']['bill']['number'];
            }
            
            if (isset($response['data']['bill']['pdf_url']) && !empty($response['data']['bill']['pdf_url'])) {
                $updateData['pdf_url'] = $response['data']['bill']['pdf_url'];
            }
            
            if (isset($response['data']['bill']['xml_url']) && !empty($response['data']['bill']['xml_url'])) {
                $updateData['xml_url'] = $response['data']['bill']['xml_url'];
            }
            
            if (isset($response['data']['bill']['validated_at']) && !empty($response['data']['bill']['validated_at'])) {
                $updateData['validated_at'] = $response['data']['bill']['validated_at'];
            }
            
            $invoice->update($updateData);
            
            Log::info('Factura enviada exitosamente a Factus', [
                'invoice_id' => $invoice->id,
                'document' => $updateData['document'] ?? null,
                'status' => $status,
                'response' => $response
            ]);
            
        } catch (FactusApiException $e) {
            // Capturar error específico de Factus API con detalles
            $errorDetails = $e->getMessage();
            $statusCode = $e->getStatusCode();
            $errorData = $e->getResponseBody();
            
            Log::error('Error sending invoice to Factus API', [
                'invoice_id' => $invoice->id,
                'status_code' => $statusCode,
                'error_message' => $errorDetails,
                'error_data' => $errorData,
                'payload' => $payload,
            ]);
            
            // Construir mensaje de error más detallado
            $detailedError = "Error en Factus API ({$statusCode}): {$errorDetails}";
            if ($errorData && isset($errorData['errors'])) {
                $detailedError .= ' | Errors: ' . json_encode($errorData['errors']);
            }
            
            throw new \Exception('Error al enviar la factura a Factus: ' . $detailedError);
        } catch (\Exception $e) {
            // Manejar otros errores
            Log::error('Error sending invoice to Factus', [
                'invoice_id' => $invoice->id,
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);
            throw new \Exception('Error al enviar la factura a Factus: ' . $e->getMessage());
        }
    }

    private function buildPayload(ElectronicInvoice $invoice, CompanyTaxSetting $company): array
    {
        $customer = $invoice->customer;
        $taxProfile = $customer->taxProfile;
        $identificationDocument = $taxProfile->identificationDocument;

        // Obtener información de la empresa directamente desde la API de Factus
        try {
            $companyApiResponse = $this->apiService->get('/v1/company');
            $companyData = $companyApiResponse['data'];
            
            Log::info('Company data from Factus API:', [
                'nit' => $companyData['nit'] ?? 'N/A',
                'name' => !empty($companyData['company']) ? $companyData['company'] : trim(($companyData['names'] ?? '') . ' ' . ($companyData['surnames'] ?? '')),
                'address' => $companyData['address'] ?? 'N/A',
                'phone' => $companyData['phone'] ?? 'N/A',
                'email' => $companyData['email'] ?? 'N/A',
                'municipality_code' => $companyData['municipality']['code'] ?? 'N/A',
                'municipality_name' => $companyData['municipality']['name'] ?? 'N/A',
            ]);
            
            // Buscar el factus_id usando el code de la API
            $municipalityCode = $companyData['municipality']['code'] ?? null;
            $municipalityFactusId = null;
            
            if ($municipalityCode) {
                $municipality = \App\Models\DianMunicipality::where('code', $municipalityCode)->first();
                if ($municipality) {
                    $municipalityFactusId = $municipality->factus_id;
                    Log::info('Municipality found:', [
                        'api_code' => $municipalityCode,
                        'local_id' => $municipality->id,
                        'factus_id' => $municipalityFactusId,
                        'name' => $municipality->name,
                    ]);
                } else {
                    Log::warning('Municipality not found with code:', ['code' => $municipalityCode]);
                    // Usar un municipio por defecto que sabemos que funciona
                    $municipalityFactusId = 980;
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error getting company data from Factus API', [
                'error' => $e->getMessage(),
            ]);
            throw new \Exception('No se pudo obtener la información de la empresa desde Factus API: ' . $e->getMessage());
        }

        // Determine names and company according to document type
        $isJuridicalPerson = $identificationDocument->code === 'NIT';
        $customerNames = $isJuridicalPerson 
            ? ($taxProfile->company ?? $customer->name)
            : ($taxProfile->names ?? $customer->name);
        
        // Construir el establecimiento con datos de la API de Factus
        $establishment = [
            'name' => !empty($companyData['company']) 
                ? $companyData['company'] 
                : trim(($companyData['names'] ?? '') . ' ' . ($companyData['surnames'] ?? '')),
            'address' => $companyData['address'] ?? 'Sin dirección',
            'phone_number' => $companyData['phone'] ?? 'Sin teléfono',
            'email' => $companyData['email'],
            'municipality_id' => $municipalityFactusId, // Usar el factus_id encontrado por code
        ];

        // Construir el cliente según la documentación de la API
        $customerData = [
            'identification_document_id' => $identificationDocument->id,
            'identification' => $taxProfile->identification,
            'dv' => (!empty($taxProfile->dv) && $identificationDocument->code === 'NIT') ? (int)$taxProfile->dv : null,
            'municipality_id' => $taxProfile->municipality->factus_id, // Usar el factus_id del municipio del cliente
        ];
        
        // Add names or company according to document type
        if ($isJuridicalPerson) {
            // Para personas jurídicas, usar company si existe, si no usar el nombre del cliente
            if (!empty($taxProfile->company)) {
                $customerData['company'] = $taxProfile->company;
            } elseif (!empty($customer->name)) {
                $customerData['company'] = $customer->name; // Usar nombre del cliente como razón social
            }
            if (!empty($taxProfile->trade_name)) {
                $customerData['trade_name'] = $taxProfile->trade_name;
            }
        } else {
            if (!empty($customerNames)) {
                $customerData['names'] = $customerNames;
            }
        }
        
        // Agregar información de contacto opcional (solo si existe) - del customer, no del taxProfile
        if (!empty($customer->address)) {
            $customerData['address'] = $customer->address;
        }
        if (!empty($customer->email)) {
            $customerData['email'] = $customer->email;
        }
        if (!empty($customer->phone)) {
            $customerData['phone'] = $customer->phone;
        }
        
        // Agregar organización legal y tributo
        if (!empty($taxProfile->legal_organization_id)) {
            $customerData['legal_organization_id'] = $taxProfile->legal_organization_id;
        }
        if (!empty($taxProfile->tribute_id)) {
            $customerData['tribute_id'] = $taxProfile->tribute_id;
        }
        
        // Construir los items del payload
        $items = $invoice->items->map(function($item) {
            // Usar IDs que sabemos que funcionan según la documentación de Factus
            $itemData = [
                'code_reference' => $item->code_reference,
                'name' => $item->name,
                'quantity' => (int) $item->quantity,
                'price' => (float) $item->price,
                'unit_measure_id' => 70, // ID correcto según documentación
                'tax_rate' => number_format($item->tax_rate, 2),
                'tax_amount' => (float) $item->tax_amount,
                'discount_rate' => (float) $item->discount_rate,
                'is_excluded' => $item->is_excluded ? 1 : 0,
                'standard_code_id' => 1, // ID correcto según documentación
                'tribute_id' => 1, // ID correcto según documentación (IVA)
                'total' => (float) $item->total,
            ];

            // Agregar retenciones si existen
            if ($item->withholding_taxes && count($item->withholding_taxes) > 0) {
                $itemData['withholding_taxes'] = $item->withholding_taxes->map(function($withholding) {
                    return [
                        'code' => $withholding->code,
                        'withholding_tax_rate' => (float) $withholding->rate,
                    ];
                })->toArray();
            }

            return $itemData;
        })->toArray();

        // Log para depuración de IDs
        Log::info('IDs usados en el payload:', [
            'establishment_municipality_id' => $company->municipality->factus_id,
            'customer_municipality_id' => $taxProfile->municipality->factus_id,
            'items_unit_measure_id' => $invoice->items->first()->unit_measure_id,
            'items_standard_code_id' => $invoice->items->first()->standard_code_id,
            'items_tribute_id' => $invoice->items->first()->tribute_id,
        ]);

        // Construir el payload final según la documentación
        $payload = [
            'document' => $invoice->documentType->code,
            'numbering_range_id' => $invoice->factus_numbering_range_id,
            'reference_code' => $invoice->reference_code,
            'observation' => $invoice->notes ?? '',
            'payment_method_code' => (int) $invoice->payment_method_code,
            'payment_form_code' => (int) $invoice->payment_form_code,
            'operation_type' => $invoice->operationType->code,
            'establishment' => $establishment,
            'customer' => $customerData,
            'items' => $items,
        ];

        // Agregar descuentos o recargos si existen
        if ($invoice->allowance_charges && count($invoice->allowance_charges) > 0) {
            $payload['allowance_charges'] = $invoice->allowance_charges->map(function($charge) {
                return [
                    'concept_type' => $charge->concept_type,
                    'is_surcharge' => $charge->is_surcharge,
                    'reason' => $charge->reason,
                    'base_amount' => (float) $charge->base_amount,
                    'amount' => (float) $charge->amount,
                ];
            })->toArray();
        }

        return $payload;
    }

    private function mapStatusFromResponse(array $response): string
    {
        if (isset($response['data']['bill']['status'])) {
            $status = strtolower($response['data']['bill']['status']);
            if (in_array($status, ['accepted', 'rejected', 'pending', 'error'])) {
                return $status;
            }
        }

        if (isset($response['data']['bill']['cufe']) && !empty($response['data']['bill']['cufe'])) {
            return 'accepted';
        }

        return 'pending';
    }

    /**
     * Generate a unique reference code for the invoice.
     */
    private function generateReferenceCode(): string
    {
        return 'INV-' . date('Ymd') . '-' . strtoupper(uniqid());
    }

    /**
     * Generate document number using the numbering range.
     */
    private function generateDocumentNumber(\App\Models\FactusNumberingRange $range): string
    {
        return $range->prefix . $range->current;
    }
}
