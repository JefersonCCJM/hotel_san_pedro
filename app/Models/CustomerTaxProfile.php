<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * @property int $id
 * @property int $customer_id
 * @property int|null $identification_document_id
 * @property string|null $identification
 * @property string|null $dv
 * @property int|null $legal_organization_id
 * @property string|null $company
 * @property string|null $trade_name
 * @property string|null $names
 * @property string|null $address
 * @property string|null $email
 * @property string|null $phone
 * @property int|null $tribute_id
 * @property int|null $municipality_id
 * @property-read Customer $customer
 * @property-read DianIdentificationDocument|null $identificationDocument
 * @property-read DianLegalOrganization|null $legalOrganization
 * @property-read DianCustomerTribute|null $tribute
 * @property-read DianMunicipality|null $municipality
 *
 * @mixin Builder
 */
class CustomerTaxProfile extends Model
{
    protected $fillable = [
        'customer_id',
        'identification_document_id',
        'identification',
        'dv',
        'legal_organization_id',
        'company',
        'trade_name',
        'names',
        'address',
        'email',
        'phone',
        'tribute_id',
        'municipality_id',
    ];

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function identificationDocument(): BelongsTo
    {
        return $this->belongsTo(DianIdentificationDocument::class, 'identification_document_id');
    }

    public function legalOrganization(): BelongsTo
    {
        return $this->belongsTo(DianLegalOrganization::class, 'legal_organization_id');
    }

    public function tribute(): BelongsTo
    {
        return $this->belongsTo(DianCustomerTribute::class, 'tribute_id');
    }

    public function municipality(): BelongsTo
    {
        return $this->belongsTo(DianMunicipality::class, 'municipality_id', 'factus_id');
    }

    public function requiresDV(): bool
    {
        return $this->identificationDocument?->requires_dv ?? false;
    }

    public function isJuridicalPerson(): bool
    {
        return $this->identificationDocument?->code === 'NIT';
    }
}
