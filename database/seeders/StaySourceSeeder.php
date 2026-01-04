<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class StaySourceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['code' => 'reservation', 'name' => 'Check-in desde reserva'],
            ['code' => 'walk_in', 'name' => 'Check-in directo'],
            ['code'=> 'extension', 'name'=> 'Extensión de estadía'],
            ['code' => 'system', 'name' => 'Creada automáticamente por el sistema'],
        ];

        foreach ($data as $row) {
            DB::table('stay_sources')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
