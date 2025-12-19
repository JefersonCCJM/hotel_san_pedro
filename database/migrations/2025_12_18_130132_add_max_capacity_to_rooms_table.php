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
            $table->integer('max_capacity')->default(2)->after('beds_count');
        });

        // Lógica inicial: 2 personas por cama
        \Illuminate\Support\Facades\DB::table('rooms')->update([
            'max_capacity' => \Illuminate\Support\Facades\DB::raw('beds_count * 2')
        ]);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->dropColumn('max_capacity');
        });
    }
};
