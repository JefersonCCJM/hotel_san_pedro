<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RoomMaintenanceBlockSourceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['code' => 'manual', 'name' => 'Bloqueo manual'],
            ['code'=> 'inspection', 'name'=> 'Inspección'],
            ['code' => 'emergency', 'name' => 'Bloqueo por emergencia'],
            ['code' => 'system', 'name' => 'Bloqueo automático'],
        ];

        foreach ($data as $row) {
            DB::table('room_maintenance_block_sources')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
