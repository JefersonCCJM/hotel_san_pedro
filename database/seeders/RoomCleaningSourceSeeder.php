<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class RoomCleaningSourceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['code' => 'stay', 'name' => 'Aseo en estadía'],
            ['code' => 'manual', 'name' => 'Registro manual'],
            ['code' => 'checkout', 'name' => 'Al hacer checkout'],
        ];

        foreach ($data as $row) {
            DB::table('room_cleaning_sources')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
