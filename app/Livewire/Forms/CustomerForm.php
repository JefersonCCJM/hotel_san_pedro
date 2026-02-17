<?php

namespace App\Livewire\Forms;

use App\Models\Customer;
use App\Models\CompanyTaxSetting;
use App\Models\DianMunicipality;
use App\Services\CustomerService;
use Illuminate\Validation\ValidationException;
use Livewire\Attributes\Validate;
use Livewire\Form;

class CustomerForm extends Form
{
    #[Validate('required|string|max:255')]
    public string $name = '';

    #[Validate('required|exists:dian_identification_documents,id')]
    public string $identification_type_id = '';

    #[Validate('required|string|max:15')]
    public string $identification = '';

    #[Validate('required|string|max:20')]
    public string $phone = '';

    #[Validate('nullable|email|max:255')]
    public string $email = '';

    #[Validate('nullable|string|max:500')]
    public string $address = '';

    // DIAN fields
    public bool $requiresElectronicInvoice = false;

    #[Validate('nullable|exists:dian_identification_documents,id')]
    public string $identificationDocumentId = '';

    #[Validate('nullable|string|max:1')]
    public string $dv = '';

    #[Validate('nullable|string|max:255')]
    public string $company = '';

    #[Validate('nullable|string|max:255')]
    public string $tradeName = '';

    #[Validate('nullable|exists:dian_municipalities,factus_id')]
    public string $municipalityId = '';

    #[Validate('nullable|exists:dian_legal_organizations,id')]
    public string $legalOrganizationId = '';

    #[Validate('nullable|exists:dian_tributes,id')]
    public string $tributeId = '';

    // Auxiliary properties (not persisted)
    public bool $requiresDV = false;
    public bool $isJuridicalPerson = false;
    public bool $identificationExists = false;
    public string $identificationMessage = '';

    private CustomerService $customerService;

    public function mount(CustomerService $customerService)
    {
        $this->customerService = $customerService;
    }

    public function rules(): array
    {
        $rules = [
            'name' => 'required|string|max:255',
            'identification_type_id' => 'required|exists:dian_identification_documents,id',
            'identification' => 'required|string|max:15',
            'phone' => 'required|string|max:20',
            'email' => 'nullable|email|max:255',
            'address' => 'nullable|string|max:500',
        ];

        // Add DIAN validation if electronic invoice is required
        if ($this->requiresElectronicInvoice) {
            $rules['identificationDocumentId'] = 'required|exists:dian_identification_documents,id';
            $rules['municipalityId'] = 'required|exists:dian_municipalities,factus_id';

            // If juridical person, company is required
            if ($this->isJuridicalPerson) {
                $rules['company'] = 'required|string|max:255';
            }
        }

        return $rules;
    }

    public function messages(): array
    {
        return [
            'name.required' => 'El nombre es obligatorio.',
            'name.max' => 'El nombre no puede exceder 255 caracteres.',
            'identification_type_id.required' => 'El tipo de documento es obligatorio.',
            'identification_type_id.exists' => 'El tipo de documento seleccionado no es válido.',
            'identification.required' => 'La identificación es obligatoria.',
            'identification.max' => 'La identificación no puede exceder 15 dígitos.',
            'phone.required' => 'El teléfono es obligatorio.',
            'phone.max' => 'El teléfono no puede exceder 20 caracteres.',
            'email.email' => 'El email debe tener un formato válido.',
            'email.max' => 'El email no puede exceder 255 caracteres.',
            'address.max' => 'La dirección no puede exceder 500 caracteres.',
            'identificationDocumentId.required' => 'El tipo de documento es obligatorio para facturación electrónica.',
            'identificationDocumentId.exists' => 'El tipo de documento seleccionado no es válido.',
            'municipalityId.required' => 'El municipio es obligatorio para facturación electrónica.',
            'municipalityId.exists' => 'El municipio seleccionado no es válido.',
            'company.required' => 'La razón social es obligatoria para personas jurídicas (NIT).',
            'company.max' => 'La razón social no puede exceder 255 caracteres.',
        ];
    }

    public function checkIdentification(): void
    {
        if (empty($this->identification)) {
            $this->identificationMessage = '';
            $this->identificationExists = false;
            return;
        }

        $validation = $this->customerService->getIdentificationValidationMessage($this->identification);
        $this->identificationExists = $validation['exists'];
        $this->identificationMessage = $validation['message'];

        // Recalculate DV if required
        if ($this->requiresDV && !empty($this->identification)) {
            $this->dv = $this->customerService->calculateVerificationDigit($this->identification);
        }
    }

    public function updateRequiredFields(array $identificationDocuments): void
    {
        if (empty($this->identificationDocumentId)) {
            $this->requiresDV = false;
            $this->isJuridicalPerson = false;
            $this->dv = '';
            return;
        }

        // Find document in identificationDocuments array
        $document = null;
        foreach ($identificationDocuments as $doc) {
            if (isset($doc['id']) && (string)$doc['id'] === (string)$this->identificationDocumentId) {
                $document = $doc;
                break;
            }
        }

        if ($document) {
            $this->requiresDV = (bool)($document['requires_dv'] ?? false);
            $this->isJuridicalPerson = in_array($document['code'] ?? '', ['NI', 'NIT'], true);

            // Calculate DV if required
            if ($this->requiresDV && !empty($this->identification)) {
                $this->dv = $this->customerService->calculateVerificationDigit($this->identification);
            } else {
                $this->dv = '';
            }
        } else {
            $this->requiresDV = false;
            $this->isJuridicalPerson = false;
            $this->dv = '';
        }
    }

    public function resetCustomer(): void
    {
        $this->name = '';
        $this->identification_type_id = '';
        $this->identification = '';
        $this->phone = '';
        $this->email = '';
        $this->address = '';
        $this->requiresElectronicInvoice = false;
        $this->identificationDocumentId = '';
        $this->dv = '';
        $this->company = '';
        $this->tradeName = '';
        $this->municipalityId = '';
        $this->legalOrganizationId = '';
        $this->tributeId = '';
        $this->requiresDV = false;
        $this->isJuridicalPerson = false;
        $this->identificationExists = false;
        $this->identificationMessage = '';
    }

    public function create(): Customer
    {
        $this->validate();

        if ($this->identificationExists) {
            throw ValidationException::withMessages([
                'identification' => 'Esta identificación ya está registrada.',
            ]);
        }

        $customer = Customer::create([
            'name' => mb_strtoupper($this->name),
            'phone' => $this->phone,
            'email' => $this->email ?: null,
            'address' => $this->address ?: null,
            'identification_number' => $this->identification,
            'identification_type_id' => $this->identification_type_id,
            'is_active' => true,
            'requires_electronic_invoice' => $this->requiresElectronicInvoice,
        ]);

        // Create tax profile
        $municipalityId = $this->requiresElectronicInvoice
            ? $this->municipalityId
            : (CompanyTaxSetting::first()?->municipality_id
                ?? DianMunicipality::first()?->factus_id
                ?? 149); // Bogotá Factus ID as fallback

        $taxProfileData = [
            'identification' => $this->identification,
            'identification_document_id' => $this->identificationDocumentId ?: $this->identification_type_id,
            'dv' => $this->dv ?: null,
            'municipality_id' => $municipalityId,
            'legal_organization_id' => $this->legalOrganizationId ?: null,
            'tribute_id' => $this->tributeId ?: null,
            'company' => $this->requiresElectronicInvoice ? ($this->company ?: null) : null,
            'trade_name' => $this->requiresElectronicInvoice ? ($this->tradeName ?: null) : null,
        ];

        $customer->taxProfile()->create($taxProfileData);

        return $customer;
    }
}
