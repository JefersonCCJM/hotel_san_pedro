<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Services\FactusNumberingRangeService;
use Illuminate\Support\Facades\Log;

class FactusNumberingRangesSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        try {
            $service = app(FactusNumberingRangeService::class);
            
            Log::info('Iniciando sincronización de rangos de numeración desde Factus API...');
            
            $synced = $service->sync();
            
            if ($synced > 0) {
                $this->command->info("✅ Se sincronizaron {$synced} rangos de numeración desde Factus API");
                
                // Mostrar información de los rangos sincronizados
                $ranges = \App\Models\FactusNumberingRange::all();
                
                $this->command->info("\n📋 Rangos de numeración disponibles:");
                $this->command->info("ID | Documento | Prefijo | Rango | Actual | Estado");
                $this->command->info("---|-----------|--------|-------|--------|--------");
                
                foreach ($ranges as $range) {
                    $status = $range->is_active ? '✅ Activo' : '❌ Inactivo';
                    
                    $this->command->info(sprintf(
                        "%-2d | %-12s | %-7s | %s | %d | %s",
                        $range->id,
                        $range->document,
                        $range->prefix ?? 'N/A',
                        $range->range_from . ' - ' . $range->range_to,
                        $range->current,
                        $status
                    ));
                }
                
                // Verificar rangos para facturas específicamente
                $invoiceRanges = \App\Models\FactusNumberingRange::where('document', 'Factura de Venta')
                    ->where('is_active', true)
                    ->where('is_expired', false)
                    ->get();
                
                $this->command->info("\n🧾 Rangos para Factura de Venta:");
                foreach ($invoiceRanges as $range) {
                    $this->command->info("  - ID: {$range->id}, Prefijo: {$range->prefix}, Rango: {$range->range_from}-{$range->range_to}");
                }
                
            } else {
                $this->command->warn('⚠️ No se encontraron rangos de numeración en Factus API');
                $this->command->warn('   Verifica que la API esté disponible y que tengas rangos activos');
            }
            
        } catch (\Exception $e) {
            Log::error('Error en FactusNumberingRangesSeeder', [
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString()
            ]);
            
            $this->command->error('❌ Error al sincronizar rangos de numeración: ' . $e->getMessage());
            
            // No crear rangos de ejemplo si la API falla
            $this->command->warn('⚠️ No se crearon rangos de ejemplo debido al error de la API');
        }
    }
}
