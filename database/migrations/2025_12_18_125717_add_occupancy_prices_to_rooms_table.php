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
            $table->decimal('price_1_person', 12, 2)->default(0)->after('beds_count');
            $table->decimal('price_2_persons', 12, 2)->default(0)->after('price_1_person');
            $table->decimal('price_additional_person', 12, 2)->default(0)->after('price_2_persons');
        });

        // Opcional: Llenar datos iniciales basados en el precio actual
        \Illuminate\Support\Facades\DB::table('rooms')->update([
            'price_1_person' => \Illuminate\Support\Facades\DB::raw('price_per_night'),
            'price_2_persons' => \Illuminate\Support\Facades\DB::raw('price_per_night'),
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn(['price_1_person', 'price_2_persons', 'price_additional_person']);
        });
    }
};
