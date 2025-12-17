<?php

namespace App\Http\Requests\ElectronicInvoice;

use Illuminate\Foundation\Http\FormRequest;

class ElectronicInvoiceFilterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            // Only digits for document number
            'filter_number' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            // Letters, digits and dashes for reference code
            'filter_reference_code' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-z0-9\\-]+$/'],
            'filter_status' => 'nullable|in:pending,sent,accepted,rejected,cancelled,0,1',
            // Identification: digits only (nit/cc)
            'filter_identification' => ['nullable', 'string', 'max:50', 'regex:/^[0-9]+$/'],
            // Names: letters, spaces, apostrophes, hyphens
            'filter_names' => ['nullable', 'string', 'max:100', 'regex:/^[A-Za-zÁÉÍÓÚáéíóúÑñ\s\'-]+$/u'],
            // Prefix: alphanumeric up to 10 chars
            'filter_prefix' => ['nullable', 'string', 'max:10', 'regex:/^[A-Za-z0-9]+$/'],
        ];
    }

    public function messages(): array
    {
        return [
            'filter_number.regex' => 'El número de documento solo puede contener dígitos.',
            'filter_reference_code.regex' => 'El código de referencia solo permite letras, números y guiones.',
            'filter_identification.regex' => 'La identificación debe contener solo dígitos.',
            'filter_names.regex' => 'El nombre solo puede contener letras, espacios, apóstrofes y guiones.',
            'filter_prefix.regex' => 'El prefijo solo permite caracteres alfanuméricos.',
        ];
    }
}

