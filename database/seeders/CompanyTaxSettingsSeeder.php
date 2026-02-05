<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FactusApiService;
use App\Models\CompanyTaxSetting;
use App\Models\DianMunicipality;
use Illuminate\Support\Facades\Log;

class CompanyTaxSettingsSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $this->command->info('🏢 Obteniendo información de la empresa desde Factus API...');
            
            $factusApi = app(FactusApiService::class);
            
            // Obtener información de la empresa desde Factus API
            $response = $factusApi->get('/v1/company');
            
            if (!isset($response['data'])) {
                throw new \Exception('No se encontraron datos de la empresa en la respuesta de la API');
            }
            
            $companyData = $response['data'];
            
            // Buscar el municipio por factus_id
            $municipality = null;
            if (isset($companyData['municipality']['code'])) {
                $municipality = DianMunicipality::where('factus_id', $companyData['municipality']['code'])->first();
                
                // Log para depuración
                $this->command->info('🔍 Buscando municipio con factus_id: ' . $companyData['municipality']['code']);
                if ($municipality) {
                    $this->command->info('✅ Municipio encontrado: ID ' . $municipality->id . ' - ' . $municipality->name);
                } else {
                    $this->command->warn('❌ Municipio no encontrado con factus_id: ' . $companyData['municipality']['code']);
                    // Intentar buscar por nombre como fallback
                    $municipality = DianMunicipality::where('name', 'like', '%' . $companyData['municipality']['name'] . '%')->first();
                    if ($municipality) {
                        $this->command->info('✅ Municipio encontrado por nombre: ID ' . $municipality->id . ' - ' . $municipality->name);
                    }
                }
            }
            
            if (!$municipality) {
                $this->command->warn('⚠️ Municipio no encontrado. Se usará municipality_id = 1 como valor por defecto');
                $municipalityId = 1;
            } else {
                $municipalityId = $municipality->id;
            }
            
            // Preparar datos para guardar
            $companyName = !empty($companyData['company']) 
                ? $companyData['company'] 
                : trim(($companyData['names'] ?? '') . ' ' . ($companyData['surnames'] ?? ''));
            
            $companyDataToSave = [
                'company_name' => $companyName,
                'nit' => $companyData['nit'] ?? '',
                'dv' => $companyData['dv'] ?? '',
                'email' => $companyData['email'] ?? '',
                'municipality_id' => $municipalityId,
                'economic_activity' => $companyData['economic_activity'] ?? null,
                'logo_url' => $companyData['url_logo'] ?? null,
                'factus_company_id' => null, // No viene en la respuesta de /v1/company
            ];
            
            // Eliminar configuración existente si hay
            CompanyTaxSetting::truncate();
            
            // Crear nueva configuración
            $companySetting = CompanyTaxSetting::create($companyDataToSave);
            
            $this->command->info('✅ Configuración fiscal de la empresa creada exitosamente:');
            $this->command->info("   📛 Nombre: {$companySetting->company_name}");
            $this->command->info("   🆔 NIT: {$companySetting->nit}-{$companySetting->dv}");
            $this->command->info("   📧 Email: {$companySetting->email}");
            $this->command->info("   🏙️ Municipio ID: {$companySetting->municipality_id}");
            $this->command->info("   🏭 Actividad económica: {$companySetting->economic_activity}");
            
            // Mostrar información adicional de la API
            $this->command->info("\n📋 Información adicional de Factus:");
            $this->command->info("   📍 Dirección: " . ($companyData['address'] ?? 'N/A'));
            $this->command->info("   📞 Teléfono: " . ($companyData['phone'] ?? 'N/A'));
            $this->command->info("   🏛️ Municipio: " . ($companyData['municipality']['name'] ?? 'N/A'));
            $this->command->info("   🗂️ Departamento: " . ($companyData['municipality']['department']['name'] ?? 'N/A'));
            $this->command->info("   📊 Tributo: " . ($companyData['tribute']['name'] ?? 'N/A'));
            $this->command->info("   👤 Organización: " . ($companyData['legal_organization']['name'] ?? 'N/A'));
            
            if (isset($companyData['responsibilities']) && is_array($companyData['responsibilities'])) {
                $this->command->info("   🏷️ Responsabilidades:");
                foreach ($companyData['responsibilities'] as $responsibility) {
                    $this->command->info("      • {$responsibility['name']} ({$responsibility['code']})");
                }
            }
            
            // Verificar si la configuración está completa
            $missingFields = $companySetting->getMissingFields();
            if (empty($missingFields)) {
                $this->command->info("\n✅ La configuración fiscal está COMPLETA y lista para usar.");
            } else {
                $this->command->warn("\n⚠️ La configuración fiscal tiene campos faltantes:");
                foreach ($missingFields as $field) {
                    $this->command->warn("   • {$field}");
                }
            }
            
        } catch (\Exception $e) {
            Log::error('Error en CompanyTaxSettingsSeeder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->command->error('❌ Error al obtener configuración fiscal de la empresa: ' . $e->getMessage());
            
            // Crear configuración de ejemplo si la API no está disponible
            $this->createExampleCompany();
        }
    }
    
    /**
     * Crear configuración de ejemplo para desarrollo/pruebas
     */
    private function createExampleCompany(): void
    {
        $this->command->info('📝 Creando configuración fiscal de ejemplo para desarrollo...');
        
        $exampleData = [
            'company_name' => 'HOTEL SAN PEDRO S.A.S.',
            'nit' => '900123456',
            'dv' => '7',
            'email' => 'contacto@hotelsanpedro.com',
            'municipality_id' => 1, // Bogotá por defecto
            'economic_activity' => 5510, // Hoteles
            'logo_url' => null,
            'factus_company_id' => null,
        ];
        
        // Eliminar configuración existente si hay
        CompanyTaxSetting::truncate();
        
        // Crear nueva configuración
        $companySetting = CompanyTaxSetting::create($exampleData);
        
        $this->command->info('✅ Configuración fiscal de ejemplo creada:');
        $this->command->info("   📛 Nombre: {$companySetting->company_name}");
        $this->command->info("   🆔 NIT: {$companySetting->nit}-{$companySetting->dv}");
        $this->command->info("   📧 Email: {$companySetting->email}");
        $this->command->info("   🏙️ Municipio ID: {$companySetting->municipality_id}");
        $this->command->info("   🏭 Actividad económica: {$companySetting->economic_activity}");
        
        $this->command->warn('⚠️ Esta es una configuración de ejemplo. Para producción, ejecuta el seeder con acceso a la API de Factus.');
    }
}
