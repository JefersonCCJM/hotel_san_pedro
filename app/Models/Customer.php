<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasOne;

/**
 * @property int $id
 * @property string $name
 * @property string|null $email
 * @property string|null $phone
 * @property string|null $address
 * @property string|null $city
 * @property string|null $state
 * @property string|null $zip_code
 * @property string|null $notes
 * @property bool $is_active
 * @property bool $requires_electronic_invoice
 * @property-read CustomerTaxProfile|null $taxProfile
 *
 * @mixin Builder
 */
class Customer extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'email',
        'phone',
        'address',
        'city',
        'state',
        'zip_code',
        'notes',
        'is_active',
        'requires_electronic_invoice',
    ];

    /**
     * Always store and retrieve the name in uppercase.
     */
    protected function name(): \Illuminate\Database\Eloquent\Casts\Attribute
    {
        return \Illuminate\Database\Eloquent\Casts\Attribute::make(
            get: fn (?string $value) => $value ? mb_strtoupper($value) : null,
            set: fn (?string $value) => $value ? mb_strtoupper($value) : null,
        );
    }

    protected $casts = [
        'is_active' => 'boolean',
        'requires_electronic_invoice' => 'boolean',
    ];

    /**
     * Get the tax profile for the customer.
     */
    public function taxProfile(): HasOne
    {
        return $this->hasOne(CustomerTaxProfile::class);
    }

    /**
     * Check if customer requires electronic invoice.
     */
    public function requiresElectronicInvoice(): bool
    {
        return $this->requires_electronic_invoice && 
               $this->taxProfile !== null;
    }

    /**
     * Check if customer has complete tax profile data.
     */
    public function hasCompleteTaxProfileData(): bool
    {
        if (!$this->requires_electronic_invoice) {
            return false;
        }
        
        $profile = $this->taxProfile;
        if (!$profile) {
            return false;
        }
        
        // Load necessary relationships
        $profile->load('identificationDocument');
        
        $required = ['identification_document_id', 'identification', 'municipality_id'];
        
        foreach ($required as $field) {
            if (empty($profile->$field)) {
                return false;
            }
        }
        
        if ($profile->requiresDV() && empty($profile->dv)) {
            return false;
        }
        
        if ($profile->isJuridicalPerson() && empty($profile->company)) {
            return false;
        }
        
        return true;
    }

    /**
     * Get missing tax profile fields.
     *
     * @return array<string>
     */
    public function getMissingTaxProfileFields(): array
    {
        $missing = [];

        if (!$this->requires_electronic_invoice) {
            return ['Facturación electrónica no está activada'];
        }

        $profile = $this->taxProfile;
        if (!$profile) {
            return ['Perfil fiscal no está configurado. Por favor, complete los datos fiscales del cliente.'];
        }

        // Load necessary relationships
        $profile->load('identificationDocument');

        $required = [
            'identification_document_id' => 'Tipo de documento',
            'identification' => 'Número de identificación',
            'municipality_id' => 'Municipio',
        ];

        foreach ($required as $field => $label) {
            if (empty($profile->$field)) {
                $missing[] = $label;
            }
        }

        if ($profile->requiresDV() && empty($profile->dv)) {
            $missing[] = 'Dígito verificador (DV)';
        }

        if ($profile->isJuridicalPerson() && empty($profile->company)) {
            $missing[] = 'Razón social / Empresa';
        }

        return $missing;
    }

    /**
     * Scope a query to only include active customers.
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

}
