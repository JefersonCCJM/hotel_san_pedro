<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->json('occupancy_prices')->nullable()->after('price_additional_person');
        });

        // Migrar datos existentes al nuevo formato JSON
        $rooms = \Illuminate\Support\Facades\DB::table('rooms')->get();
        foreach ($rooms as $room) {
            $prices = [];
            for ($i = 1; $i <= $room->max_capacity; $i++) {
                if ($i == 1) {
                    $prices[$i] = (int) $room->price_1_person;
                } elseif ($i == 2) {
                    $prices[$i] = (int) $room->price_2_persons;
                } else {
                    $prices[$i] = (int) $room->price_2_persons + ((int) $room->price_additional_person * ($i - 2));
                }
            }
            \Illuminate\Support\Facades\DB::table('rooms')->where('id', $room->id)->update([
                'occupancy_prices' => json_encode($prices)
            ]);
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('occupancy_prices');
        });
    }
};
