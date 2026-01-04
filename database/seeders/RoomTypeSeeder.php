<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RoomTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $types = [
            ['code' => 'single',    'name' => 'Sencilla'],
            ['code' => 'double',    'name' => 'Doble'],
            ['code' => 'triple',    'name' => 'Triple'],
            ['code' => 'quad',      'name' => 'Cuádruple'],
            ['code' => 'suite',     'name' => 'Suite'],
            ['code' => 'family',    'name' => 'Familiar'],
        ];

        foreach ($types as $type) {
            DB::table('room_types')->updateOrInsert(
                ['code' => $type['code']],
                ['name' => $type['name'], 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
