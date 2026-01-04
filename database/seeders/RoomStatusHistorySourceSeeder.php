<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RoomStatusHistorySourceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['code' => 'reservation', 'name' => 'Desde reserva'],
            ['code' => 'stay', 'name' => 'Desde estadía'],
            ['code' => 'maintenance', 'name' => 'Desde mantenimiento'],
            ['code'=> 'clean', 'name'=> 'Desde limpieza'],
            ['code' => 'manual', 'name' => 'Cambio manual'],
            ['code' => 'system', 'name' => 'Cambio automático del sistema'],
        ];

        foreach ($data as $row) {
            DB::table('room_status_history_sources')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
