<?php

namespace Tests\Feature;

use App\Models\Customer;
use App\Models\CustomerTaxProfile;
use App\Models\DianCustomerTribute;
use App\Models\DianIdentificationDocument;
use App\Models\DianMunicipality;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CustomerValidationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        // Force env before parent::setUp()
        putenv('DB_CONNECTION=sqlite');
        putenv('DB_DATABASE=:memory:');
        $_ENV['DB_CONNECTION'] = 'sqlite';
        $_ENV['DB_DATABASE'] = ':memory:';
        $_SERVER['DB_CONNECTION'] = 'sqlite';
        $_SERVER['DB_DATABASE'] = ':memory:';

        parent::setUp();
    }

    private function createIdentityDeps(): array
    {
        $doc = DianIdentificationDocument::create([
            'name' => 'CC',
            'code' => 'CC',
            'requires_dv' => false,
        ]);

        $municipality = DianMunicipality::create([
            'factus_id' => 123456,
            'name' => 'Test City',
            'department' => 'Test Dept',
        ]);

        $tribute = DianCustomerTribute::create([
            'code' => '01',
            'name' => 'Regimen Común',
        ]);

        return [$doc, $municipality, $tribute];
    }

    public function test_store_accepts_unique_document()
    {
        [$doc, $municipality] = $this->createIdentityDeps();

        $user = User::factory()->create();
        $payload = [
            'name' => 'Cliente A',
            'email' => 'a@test.com',
            'is_active' => 1,
            'requires_electronic_invoice' => 1,
            'identification_document_id' => $doc->id,
            'identification' => '900123456',
            'municipality_id' => $municipality->factus_id,
        ];

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertRedirect(route('customers.index'));
        $this->assertDatabaseHas('customers', ['email' => 'a@test.com']);
        $this->assertDatabaseHas('customer_tax_profiles', [
            'identification_document_id' => $doc->id,
            'identification' => '900123456',
        ]);
    }

    public function test_store_rejects_duplicate_document_same_type()
    {
        [$doc, $municipality] = $this->createIdentityDeps();
        $user = User::factory()->create();

        $customer = Customer::factory()->create();
        CustomerTaxProfile::create([
            'customer_id' => $customer->id,
            'identification_document_id' => $doc->id,
            'identification' => '900123456',
            'municipality_id' => $municipality->factus_id,
        ]);

        $payload = [
            'name' => 'Cliente B',
            'email' => 'b@test.com',
            'requires_electronic_invoice' => 1,
            'identification_document_id' => $doc->id,
            'identification' => '900123456',
            'municipality_id' => $municipality->factus_id,
        ];

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasErrors('identification');
        $this->assertEquals(1, CustomerTaxProfile::count());
    }

    public function test_update_rejects_duplicate_document_from_other_customer()
    {
        [$doc, $municipality] = $this->createIdentityDeps();
        $user = User::factory()->create();

        $c1 = Customer::factory()->create(['email' => 'a@test.com']);
        CustomerTaxProfile::create([
            'customer_id' => $c1->id,
            'identification_document_id' => $doc->id,
            'identification' => '900123456',
            'municipality_id' => $municipality->factus_id,
        ]);

        $c2 = Customer::factory()->create(['email' => 'b@test.com']);
        CustomerTaxProfile::create([
            'customer_id' => $c2->id,
            'identification_document_id' => $doc->id,
            'identification' => '800111222',
            'municipality_id' => $municipality->factus_id,
        ]);

        $payload = [
            'name' => $c2->name,
            'email' => $c2->email,
            'requires_electronic_invoice' => 1,
            'identification_document_id' => $doc->id,
            'identification' => '900123456', // duplicado de c1
            'municipality_id' => $municipality->factus_id,
        ];

        $response = $this->actingAs($user)->put(route('customers.update', $c2), $payload);

        $response->assertSessionHasErrors('identification');
        $this->assertDatabaseHas('customer_tax_profiles', [
            'customer_id' => $c2->id,
            'identification' => '800111222',
        ]);
    }

    public function test_requires_fields_when_einvoice_enabled()
    {
        [$doc, $municipality] = $this->createIdentityDeps();
        $user = User::factory()->create();

        $payload = [
            'name' => 'Cliente C',
            'requires_electronic_invoice' => 1,
            // falta identification_document_id e identification
            'municipality_id' => $municipality->factus_id,
        ];

        $response = $this->actingAs($user)->post(route('customers.store'), $payload);

        $response->assertSessionHasErrors(['identification_document_id', 'identification']);
        $this->assertEquals(0, Customer::count());
    }
}

