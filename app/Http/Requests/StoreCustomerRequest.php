<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreCustomerRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'name' => $this->name ? trim(mb_strtoupper($this->name)) : null,
            'identification' => $this->identification ? trim($this->identification) : null,
            'phone' => $this->phone ? trim($this->phone) : null,
            'email' => $this->email ? trim(mb_strtolower($this->email)) : null,
            'company' => $this->company ? trim(mb_strtoupper($this->company)) : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'identification' => [
                'required',
                'string',
                'max:20',
                Rule::unique('customer_tax_profiles', 'identification'),
            ],
            'phone' => ['required', 'string', 'max:20'],
            'email' => ['nullable', 'email', 'max:255', Rule::unique('customers', 'email')],
            'address' => ['nullable', 'string', 'max:500'],
            'is_active' => ['sometimes', 'boolean'],
            'requires_electronic_invoice' => ['sometimes', 'boolean'],

            // Perfil fiscal (solo cuando se habilita factura electrónica)
            'identification_document_id' => [
                'required_if:requires_electronic_invoice,1',
                'nullable',
                'exists:dian_identification_documents,id',
            ],
            'dv' => ['nullable', 'string', 'max:1'],
            'legal_organization_id' => ['nullable', 'exists:dian_legal_organizations,id'],
            'company' => ['nullable', 'string', 'max:255'],
            'trade_name' => ['nullable', 'string', 'max:255'],
            'municipality_id' => [
                'required_if:requires_electronic_invoice,1',
                'nullable',
                Rule::exists('dian_municipalities', 'factus_id'),
            ],
            'tribute_id' => ['nullable', 'exists:dian_customer_tributes,id'],
        ];
    }
}
