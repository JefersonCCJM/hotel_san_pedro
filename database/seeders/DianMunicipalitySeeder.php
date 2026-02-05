<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use App\Models\DianMunicipality;

class DianMunicipalitySeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // URL de la API de Factus para municipios
        $url = config('services.factus.sandbox', true) 
            ? 'https://api-sandbox.factus.com.co/v1/municipalities'
            : 'https://api.factus.com.co/v1/municipalities';

        $this->command->info('URL de la API: ' . $url);

        try {
            // Obtener token de acceso (debes implementar tu método para obtener el token)
            $accessToken = $this->getAccessToken();

            if (!$accessToken) {
                $this->command->error('No se pudo obtener el token de acceso para Factus');
                return;
            }

            $this->command->info('Token de acceso obtenido exitosamente');

            // Realizar la solicitud a la API
            $response = Http::withHeaders([
                'Content-Type' => 'application/json',
                'Authorization' => 'Bearer ' . $accessToken,
                'Accept' => 'application/json',
            ])->get($url);

            $this->command->info('Status de la respuesta: ' . $response->status());

            if (!$response->successful()) {
                $this->command->error('Error en la respuesta de la API:');
                $this->command->error('Status: ' . $response->status());
                $this->command->error('Body: ' . $response->body());
                Log::error('Error al obtener municipios de Factus: ' . $response->body());
                return;
            }

            $municipalities = $response->json('data');
            $this->command->info('Respuesta JSON obtenida');

            // Verificar si la respuesta es un array o si está anidada
            if (is_array($municipalities)) {
                $this->command->info('Se encontraron ' . count($municipalities) . ' municipios en la respuesta');
                
                // Mostrar el primer elemento para depurar estructura
                if (!empty($municipalities)) {
                    $this->command->info('Estructura del primer municipio:');
                    $this->command->info(json_encode($municipalities[0], JSON_PRETTY_PRINT));
                }
            } else {
                $this->command->error('La respuesta no es un array. Respuesta completa:');
                $this->command->error(json_encode($municipalities, JSON_PRETTY_PRINT));
                return;
            }

            if (empty($municipalities)) {
                $this->command->warn('No se encontraron municipios en la respuesta de la API');
                return;
            }

            // Limpiar la tabla antes de insertar nuevos datos
            DianMunicipality::truncate();
            $this->command->info('Tabla limpiada');

            // Insertar los municipios
            $inserted = 0;
            foreach ($municipalities as $index => $municipality) {
                try {
                    // Verificar si el municipio es un string o array
                    if (is_string($municipality)) {
                        $this->command->error("El municipio #{$index} es un string: {$municipality}");
                        continue;
                    }
                    
                    // Verificar que tenga las claves necesarias
                    if (!isset($municipality['id'])) {
                        $this->command->error("El municipio #{$index} no tiene 'id'");
                        $this->command->error('Datos: ' . json_encode($municipality, JSON_PRETTY_PRINT));
                        continue;
                    }
                    
                    DianMunicipality::create([
                        'factus_id' => $municipality['id'],
                        'code' => $municipality['code'] ?? null,
                        'name' => $municipality['name'],
                        'department' => $municipality['department'],
                    ]);
                    $inserted++;
                    
                    if ($inserted % 100 === 0) {
                        $this->command->info("Insertados {$inserted} municipios...");
                    }
                } catch (\Exception $e) {
                    $this->command->error("Error insertando municipio #{$index}: " . $e->getMessage());
                    $this->command->error('Datos del municipio: ' . json_encode($municipality, JSON_PRETTY_PRINT));
                }
            }

            $this->command->info("Se han importado {$inserted} municipios exitosamente.");

        } catch (\Exception $e) {
            $this->command->error('Error en DianMunicipalitySeeder: ' . $e->getMessage());
            Log::error('Error en DianMunicipalitySeeder: ' . $e->getMessage());
        }
    }

    /**
     * Obtener token de acceso de Factus
     * Debes implementar este método según tu configuración de autenticación
     */
    private function getAccessToken(): ?string
    {
        try {
            // URL para obtener token (CORREGIDA: sin /v1)
            $tokenUrl = config('services.factus.sandbox', true)
                ? 'https://api-sandbox.factus.com.co/oauth/token'
                : 'https://api.factus.com.co/oauth/token';

            $this->command->info('URL del token: ' . $tokenUrl);

            // Credenciales (debes configurar estas variables en tu .env)
            $clientId = config('services.factus.client_id');
            $clientSecret = config('services.factus.client_secret');
            $username = env('FACTUS_USERNAME');
            $password = env('FACTUS_PASSWORD');

            $this->command->info('Client ID: ' . ($clientId ? 'Configurado' : 'No configurado'));
            $this->command->info('Client Secret: ' . ($clientSecret ? 'Configurado' : 'No configurado'));
            $this->command->info('Username: ' . ($username ? 'Configurado' : 'No configurado'));

            if (!$clientId || !$clientSecret || !$username || !$password) {
                $this->command->error('Faltan credenciales de Factus en la configuración');
                $this->command->error('Verifica que FACTUS_CLIENT_ID, FACTUS_CLIENT_SECRET, FACTUS_USERNAME y FACTUS_PASSWORD estén en tu .env');
                return null;
            }

            $this->command->info('Intentando obtener token...');

            // Usar grant_type 'password' en lugar de 'client_credentials'
            $response = Http::asForm()->post($tokenUrl, [
                'grant_type' => 'password',
                'client_id' => $clientId,
                'client_secret' => $clientSecret,
                'username' => $username,
                'password' => $password,
            ]);

            $this->command->info('Status del token: ' . $response->status());

            if ($response->successful()) {
                $token = $response->json('access_token');
                $this->command->info('Token obtenido exitosamente');
                return $token;
            }

            $this->command->error('Error al obtener token de Factus:');
            $this->command->error('Status: ' . $response->status());
            $this->command->error('Body: ' . $response->body());
            Log::error('Error al obtener token de Factus: ' . $response->body());
            return null;

        } catch (\Exception $e) {
            $this->command->error('Error al obtener token de acceso: ' . $e->getMessage());
            Log::error('Error al obtener token de acceso: ' . $e->getMessage());
            return null;
        }
    }
}
