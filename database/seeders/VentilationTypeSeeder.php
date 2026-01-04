<?php

namespace Database\Seeders;


use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class VentilationTypeSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $now = now();
        $types = [
            ['code' => 'fan', 'name' => 'Ventilador'],
            ['code' => 'ac', 'name' => 'Aire Acondicionado'],
        ];

        foreach ($types as $type) {
            DB::table('ventilation_types')->updateOrInsert(
                ['code' => $type['code']],
                ['name' => $type['name'], 'created_at' => $now, 'updated_at' => $now]
            );
        }
    }
}
