<?php

namespace App\Console\Commands;

use App\Services\RoomDailyStatusService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class RegenerateRoomDailyStatuses extends Command
{
    /**
     * 🔥 COMANDO CRÍTICO: Regenerar room_daily_statuses_data
     * 
     * Uso: php artisan room:regenerate-statuses [--month=2026-02]
     */
    protected $signature = 'room:regenerate-statuses {--month= : Mes específico (YYYY-MM)}';
    
    protected $description = 'Regenerar estados diarios de habitaciones para el calendario';
    
    public function handle(): int
    {
        $month = $this->option('month');
        
        if ($month) {
            $date = Carbon::createFromFormat('Y-m', $month);
            $this->info("Regenerando estados para: {$date->format('F Y')}");
        } else {
            $date = Carbon::now();
            $this->info("Regenerando estados para: {$date->format('F Y')} (mes actual)");
        }
        
        try {
            $service = app(RoomDailyStatusService::class);
            $service->regenerateForMonth($date);
            
            $this->info("✅ Estados regenerados exitosamente");
            return Command::SUCCESS;
            
        } catch (\Exception $e) {
            $this->error("❌ Error al regenerar estados: " . $e->getMessage());
            return Command::FAILURE;
        }
    }
}
