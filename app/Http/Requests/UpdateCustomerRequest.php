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
        $customerId = $customer?->id;
        $taxProfileId = $customer?->taxProfile?->id;
        $identificationDocumentId = $this->input('identification_document_id');

        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => [
                'nullable',
                'email',
                'max:255',
                Rule::unique('customers', 'email')->ignore($customerId),
            ],
            'phone' => ['nullable', 'string', 'max:20'],
            'address' => ['nullable', 'string', 'max:500'],
            'city' => ['nullable', 'string', 'max:150'],
            'state' => ['nullable', 'string', 'max:150'],
            'zip_code' => ['nullable', 'string', 'max:20'],
            'notes' => ['nullable', 'string', 'max:1000'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_electronic_invoice' => ['sometimes', 'boolean'],

            // Perfil fiscal
            'identification_document_id' => [
                'required_if:requires_electronic_invoice,1',
                'nullable',
                'exists:dian_identification_documents,id',
            ],
            'identification' => [
                'required_if:requires_electronic_invoice,1',
                'nullable',
                'string',
                'max:20',
                Rule::unique('customer_tax_profiles', 'identification')
                    ->where(fn ($query) => $query->where('identification_document_id', $identificationDocumentId))
                    ->ignore($taxProfileId),
            ],
            'dv' => ['nullable', 'string', 'max:1'],
            'legal_organization_id' => ['nullable', 'exists:dian_legal_organizations,id'],
            'company' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'names' => ['nullable', 'string', 'max:255'],
            'municipality_id' => [
                'required_if:requires_electronic_invoice,1',
                'nullable',
                Rule::exists('dian_municipalities', 'factus_id'),
            ],
            'tribute_id' => ['nullable', 'exists:dian_customer_tributes,id'],
            'tax_address' => ['nullable', 'string', 'max:500'],
            'tax_email' => ['nullable', 'email', 'max:255'],
            'tax_phone' => ['nullable', 'string', 'max:20'],
        ];
    }
}