<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Carbon;

class PaymentSourceSeeder extends Seeder
{
    public function run(): void
    {
        $now = Carbon::now();

        $data = [
            ['code' => 'reservation', 'name' => 'Pago de alojamiento'],
            ['code' => 'sale', 'name' => 'Pago de consumos'],
            ['code' => 'deposit', 'name' => 'Anticipo'],
            ['code' => 'checkout', 'name' => 'cierre de cuenta'],
            ['code'=> 'refund', 'name'=> 'Devolución']
        ];

        foreach ($data as $row) {
            DB::table('payment_sources')->updateOrInsert(
                ['code' => $row['code']],
                array_merge($row, ['created_at' => $now, 'updated_at' => $now])
            );
        }
    }
}
