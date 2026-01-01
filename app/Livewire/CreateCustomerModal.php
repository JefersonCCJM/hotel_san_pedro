<?php

namespace App\Livewire;

use Livewire\Component;
use App\Models\Customer;
use App\Models\CustomerTaxProfile;
use App\Models\DianIdentificationDocument;
use App\Models\DianLegalOrganization;
use App\Models\DianCustomerTribute;
use App\Models\DianMunicipality;
use App\Models\CompanyTaxSetting;
use Livewire\Attributes\On;
use Illuminate\Support\Facades\Log;

class CreateCustomerModal extends Component
{
    public bool $isOpen = false;
    public array $formData = [
        'name' => '',
        'identification' => '',
        'phone' => '',
        'email' => '',
        'address' => '',
        'requires_electronic_invoice' => false,
        'identification_document_id' => '',
        'dv' => '',
        'company' => '',
        'trade_name' => '',
        'municipality_id' => '',
        'legal_organization_id' => '',
        'tribute_id' => '',
    ];

    public array $errors = [];
    public bool $isCreating = false;
    public string $identificationMessage = '';
    public bool $identificationExists = false;
    public bool $requiresDV = false;
    public bool $isJuridicalPerson = false;

    public function mount(): void
    {
        $this->loadCatalogs();
    }

    #[On('open-create-customer-modal')]
    public function open(): void
    {
        $this->resetForm();
        $this->isOpen = true;
    }

    public function close(): void
    {
        $this->isOpen = false;
        $this->resetForm();
    }

    private function resetForm(): void
    {
        $this->formData = [
            'name' => '',
            'identification' => '',
            'phone' => '',
            'email' => '',
            'address' => '',
            'requires_electronic_invoice' => false,
            'identification_document_id' => '',
            'dv' => '',
            'company' => '',
            'trade_name' => '',
            'municipality_id' => '',
            'legal_organization_id' => '',
            'tribute_id' => '',
        ];
        $this->errors = [];
        $this->identificationMessage = '';
        $this->identificationExists = false;
        $this->requiresDV = false;
        $this->isJuridicalPerson = false;
    }

    public function updatedFormDataIdentificationDocumentId(): void
    {
        $this->updateRequiredFields();
    }

    public function updatedFormDataIdentification(): void
    {
        $this->validateIdentification();
        if ($this->isJuridicalPerson) {
            $this->calculateDV();
        }
        // No llamar checkIdentification() automáticamente para evitar timeouts
        // Se verificará al crear el cliente
    }

    // Método deshabilitado para evitar timeouts - usar checkIdentificationSync() en su lugar
    // public function checkIdentification(): void
    // {
    //     // Este método causaba timeouts, ahora se usa checkIdentificationSync() directamente
    // }

    private function checkIdentificationSync(): void
    {
        if (empty($this->formData['identification']) || strlen($this->formData['identification']) < 6) {
            $this->identificationExists = false;
            return;
        }

        try {
            $profile = \App\Models\CustomerTaxProfile::where('identification', $this->formData['identification'])->first();
            
            if ($profile) {
                $customer = \App\Models\Customer::withTrashed()->find($profile->customer_id);
                if ($customer) {
                    $this->identificationExists = true;
                    $this->identificationMessage = "Este cliente ya está registrado como: {$customer->name}";
                    return;
                }
            }
            
            $this->identificationExists = false;
        } catch (\Exception $e) {
            Log::error('Error checking identification sync: ' . $e->getMessage());
            $this->identificationExists = false;
        }
    }

    private function updateRequiredFields(): void
    {
        if (empty($this->formData['identification_document_id'])) {
            $this->requiresDV = false;
            $this->isJuridicalPerson = false;
            return;
        }

        $document = DianIdentificationDocument::find($this->formData['identification_document_id']);
        if ($document) {
            $this->requiresDV = $document->requires_dv;
            $this->isJuridicalPerson = $document->code === 'NIT';
            
            if ($this->isJuridicalPerson) {
                $this->calculateDV();
            } else {
                $this->formData['dv'] = '';
            }
        }
    }

    private function calculateDV(): void
    {
        if (!$this->isJuridicalPerson || empty($this->formData['identification'])) {
            $this->formData['dv'] = '';
            return;
        }

        $nit = preg_replace('/\D/', '', $this->formData['identification']);
        if (empty($nit)) {
            $this->formData['dv'] = '';
            return;
        }

        $weights = [3, 7, 13, 17, 19, 23, 29, 37, 41, 43, 47, 53, 59, 67, 71];
        $sum = 0;
        
        for ($i = 0; $i < strlen($nit); $i++) {
            $sum += intval($nit[strlen($nit) - 1 - $i]) * $weights[$i];
        }
        
        $remainder = $sum % 11;
        $this->formData['dv'] = $remainder < 2 ? (string)$remainder : (string)(11 - $remainder);
    }

    private function validateIdentification(): void
    {
        $this->errors['identification'] = null;
        $identification = trim($this->formData['identification'] ?? '');
        
        if (empty($identification)) {
            $this->errors['identification'] = 'La identificación es obligatoria.';
            return;
        }

        $digitCount = strlen(preg_replace('/\D/', '', $identification));

        if ($digitCount < 6) {
            $this->errors['identification'] = 'El número de documento debe tener mínimo 6 dígitos.';
            return;
        }

        if ($digitCount > 10) {
            $this->errors['identification'] = 'El número de documento debe tener máximo 10 dígitos.';
            return;
        }

        // Permitir solo dígitos (ya se limpia en el frontend, pero validamos por seguridad)
        if (!preg_match('/^\d+$/', $identification)) {
            $this->errors['identification'] = 'El número de documento solo puede contener dígitos.';
            return;
        }
    }

    public function test(): void
    {
        Log::info('CreateCustomerModal: test() called');
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Test method called successfully'
        ]);
    }

    public function create(): void
    {
        // Forzar escritura inmediata del log
        \Illuminate\Support\Facades\Log::channel('daily')->info('=== CREATE METHOD CALLED ===', [
            'formData' => $this->formData,
            'isOpen' => $this->isOpen,
            'isCreating' => $this->isCreating,
            'timestamp' => now()->toDateTimeString()
        ]);
        
        // Debug visible
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Iniciando creación de cliente...'
        ]);
        
        $this->errors = [];
        $this->validateIdentification();
        
        \Illuminate\Support\Facades\Log::channel('daily')->info('After validateIdentification', ['errors' => $this->errors]);

        if (!empty($this->errors['identification'])) {
            Log::info('CreateCustomerModal: Validation failed - identification error', ['errors' => $this->errors]);
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error en identificación: ' . ($this->errors['identification'] ?? 'Error desconocido')
            ]);
            return;
        }

        if (empty($this->formData['name'])) {
            $this->errors['name'] = 'El nombre es obligatorio.';
            Log::info('CreateCustomerModal: Validation failed - name empty');
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El nombre es obligatorio.'
            ]);
            return;
        }

        if (empty($this->formData['phone'])) {
            $this->errors['phone'] = 'El teléfono es obligatorio.';
            Log::info('CreateCustomerModal: Validation failed - phone empty');
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'El teléfono es obligatorio.'
            ]);
            return;
        }

        // Check identification synchronously before creating
        Log::info('CreateCustomerModal: Before checkIdentificationSync');
        $this->checkIdentificationSync();
        Log::info('CreateCustomerModal: After checkIdentificationSync', [
            'identificationExists' => $this->identificationExists,
            'identificationMessage' => $this->identificationMessage
        ]);

        if ($this->identificationExists) {
            $this->errors['identification'] = 'Este cliente ya está registrado.';
            Log::info('CreateCustomerModal: Validation failed - identification exists');
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Este cliente ya está registrado: ' . $this->identificationMessage
            ]);
            return;
        }

        // Solo validar campos de facturación electrónica si está activada
        if (!empty($this->formData['requires_electronic_invoice']) && $this->formData['requires_electronic_invoice'] === true) {
            if (empty($this->formData['identification_document_id'])) {
                $this->errors['identification_document_id'] = 'El tipo de documento es obligatorio para facturación electrónica.';
            }
            if (empty($this->formData['municipality_id'])) {
                $this->errors['municipality_id'] = 'El municipio es obligatorio para facturación electrónica.';
            }
            // Solo validar razón social si es persona jurídica
            if ($this->isJuridicalPerson && empty($this->formData['company'])) {
                $this->errors['company'] = 'La razón social es obligatoria para NIT.';
            }
        }

        // Debug: mostrar todos los errores en consola y notificación
        $allErrors = array_filter($this->errors, fn($v) => !empty($v));
        
        if (!empty($allErrors)) {
            $errorMessages = [];
            foreach ($allErrors as $key => $value) {
                $fieldName = match($key) {
                    'name' => 'Nombre',
                    'phone' => 'Teléfono',
                    'identification' => 'Identificación',
                    'identification_document_id' => 'Tipo de documento',
                    'municipality_id' => 'Municipio',
                    'company' => 'Razón social',
                    default => ucfirst(str_replace('_', ' ', $key))
                };
                $errorMessages[] = "$fieldName: " . (is_array($value) ? implode(', ', $value) : $value);
            }
            
            $errorText = implode(' | ', $errorMessages);
            
            // Log detallado
            Log::info('CreateCustomerModal: Validation failed', [
                'errors' => $allErrors,
                'formData' => $this->formData,
                'requires_electronic_invoice' => $this->formData['requires_electronic_invoice'] ?? false,
                'isJuridicalPerson' => $this->isJuridicalPerson
            ]);
            
            // Dispatch con mensaje detallado
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => $errorText
            ]);
            
            // También dispatch un evento para debug en consola
            $this->dispatch('validation-errors', errors: $allErrors);
            
            return;
        }

        Log::info('CreateCustomerModal: All validations passed, starting customer creation');
        $this->dispatch('notify', [
            'type' => 'info',
            'message' => 'Creando cliente...'
        ]);
        $this->isCreating = true;

        try {
            $requiresElectronicInvoice = $this->formData['requires_electronic_invoice'] ?? false;
            
            Log::info('CreateCustomerModal: Creating customer', [
                'name' => $this->formData['name'],
                'phone' => $this->formData['phone'],
                'requires_electronic_invoice' => $requiresElectronicInvoice
            ]);
            
            $customer = Customer::create([
                'name' => mb_strtoupper($this->formData['name']),
                'phone' => $this->formData['phone'],
                'email' => !empty($this->formData['email']) ? mb_strtolower($this->formData['email']) : null,
                'address' => $this->formData['address'] ?? null,
                'is_active' => true,
                'requires_electronic_invoice' => $requiresElectronicInvoice,
            ]);

            $municipalityId = $requiresElectronicInvoice
                ? ($this->formData['municipality_id'] ?? null)
                : (CompanyTaxSetting::first()?->municipality_id
                    ?? DianMunicipality::first()?->factus_id
                    ?? 149);

            $taxProfileData = [
                'customer_id' => $customer->id,
                'identification' => $this->formData['identification'],
                'dv' => $this->formData['dv'] ?? null,
                'identification_document_id' => $requiresElectronicInvoice
                    ? ($this->formData['identification_document_id'] ?? null)
                    : 3,
                'legal_organization_id' => $requiresElectronicInvoice
                    ? ($this->formData['legal_organization_id'] ?? null)
                    : 2,
                'tribute_id' => $requiresElectronicInvoice
                    ? ($this->formData['tribute_id'] ?? null)
                    : 21,
                'municipality_id' => $municipalityId,
                'company' => $requiresElectronicInvoice && $this->isJuridicalPerson
                    ? ($this->formData['company'] ?? null)
                    : null,
                'trade_name' => $requiresElectronicInvoice
                    ? ($this->formData['trade_name'] ?? null)
                    : null,
            ];

            Log::info('CreateCustomerModal: About to create tax profile', ['taxProfileData' => $taxProfileData]);
            CustomerTaxProfile::create($taxProfileData);
            Log::info('CreateCustomerModal: Tax profile created successfully');

            Log::info('CreateCustomerModal: Customer created successfully', ['customer_id' => $customer->id]);

            $this->dispatch('customer-created', customerId: $customer->id, customer: [
                'id' => $customer->id,
                'name' => $customer->name,
                'identification' => $this->formData['identification'],
            ]);

            Log::info('CreateCustomerModal: Events dispatched, closing modal');
            $this->close();
            
            $this->dispatch('notify', [
                'type' => 'success',
                'message' => 'Cliente creado exitosamente.'
            ]);
            
            Log::info('CreateCustomerModal: All done!');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::error('Validation error creating customer', ['errors' => $e->errors()]);
            $this->errors = array_merge($this->errors, $e->errors());
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error de validación: ' . implode(', ', array_map(fn($err) => is_array($err) ? implode(', ', $err) : $err, $e->errors()))
            ]);
        } catch (\Exception $e) {
            Log::error('Error creating customer: ' . $e->getMessage(), [
                'trace' => $e->getTraceAsString(),
                'formData' => $this->formData,
                'file' => $e->getFile(),
                'line' => $e->getLine()
            ]);
            $this->addError('general', 'Error al crear el cliente: ' . $e->getMessage());
            $this->dispatch('notify', [
                'type' => 'error',
                'message' => 'Error al crear el cliente: ' . $e->getMessage()
            ]);
        } finally {
            $this->isCreating = false;
        }
    }

    private function loadCatalogs(): void
    {
        // Catalogs are loaded in render method
    }

    public function render()
    {
        return view('livewire.create-customer-modal', [
            'identificationDocuments' => DianIdentificationDocument::query()->orderBy('id')->get(),
            'legalOrganizations' => DianLegalOrganization::query()->orderBy('id')->get(),
            'tributes' => DianCustomerTribute::query()->orderBy('id')->get(),
            'municipalities' => DianMunicipality::query()
                ->orderBy('department')
                ->orderBy('name')
                ->get(),
        ]);
    }
}

