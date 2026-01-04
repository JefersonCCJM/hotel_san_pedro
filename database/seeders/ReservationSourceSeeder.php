<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class ReservationSourceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['code' => 'reception', 'name' => 'Creada por recepcionista'],
            ['code' => 'web', 'name' => 'Reserva online'],
            ['code' => 'whatsapp', 'name' => 'Contacto externo'],
            ['code' => 'ota', 'name' => 'Booking, Airbnb, etc.'],
            ['code' => 'walk_in', 'name' => 'Arriendo directo (sin reserva previa)'],
            ['code' => 'system', 'name' => 'Creada automáticamente'],
        ];

        foreach ($data as $row) {
            DB::table('reservation_sources')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
