<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $customer = $this->route('customer');
        $customerId = $customer->id;
        $currentTaxProfileId = $customer->taxProfile?->id;

        $rules = [
            'name' => 'required|string|max:255',
            'email' => 'nullable|email|max:255|unique:customers,email,' . $customerId,
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:255',
            'city' => 'nullable|string|max:100',
            'state' => 'nullable|string|max:100',
            'zip_code' => 'nullable|string|max:10',
            'notes' => 'nullable|string',
            'is_active' => 'boolean',
            'requires_electronic_invoice' => 'boolean',
            'identification_document_id' => 'required_if:requires_electronic_invoice,1|nullable|exists:dian_identification_documents,id',
            'identification' => [
                'required_if:requires_electronic_invoice,1',
                'nullable',
                'string',
                'max:20',
                Rule::unique('customer_tax_profiles', 'identification')
                    ->ignore($currentTaxProfileId)
                    ->where('identification_document_id', $this->input('identification_document_id')),
            ],
            'municipality_id' => [
                'required_if:requires_electronic_invoice,1',
                'nullable',
                function (string $attribute, $value, $fail): void {
                    if ($value && !\App\Models\DianMunicipality::where('factus_id', $value)->exists()) {
                        $fail('El municipio seleccionado no es válido.');
                    }
                },
            ],
            'dv' => 'nullable|string|max:1',
            'legal_organization_id' => 'nullable|exists:dian_legal_organizations,id',
            'company' => 'nullable|string|max:255',
            'trade_name' => 'nullable|string|max:255',
            'names' => 'nullable|string|max:255',
            'tribute_id' => 'nullable|exists:dian_customer_tributes,id',
            'tax_address' => 'nullable|string|max:500',
            'tax_email' => 'nullable|email|max:255',
            'tax_phone' => 'nullable|string|max:20',
        ];

        if ($this->boolean('requires_electronic_invoice') && $this->has('identification_document_id')) {
            $identificationDocument = \App\Models\DianIdentificationDocument::find(
                $this->input('identification_document_id')
            );

            if ($identificationDocument && $identificationDocument->requires_dv) {
                $rules['dv'] = 'required_if:requires_electronic_invoice,1|string|size:1';
            }

            if ($identificationDocument && $identificationDocument->code === 'NIT') {
                $rules['company'] = 'required_if:requires_electronic_invoice,1|string|max:255';
            }
        }

        return $rules;
    }
}
