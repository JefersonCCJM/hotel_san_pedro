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
        // Actualizar valores existentes
        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'available')->update(['status' => 'libre']);
        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'occupied')->update(['status' => 'ocupada']);
        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'maintenance')->update(['status' => 'mantenimiento']);
        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'cleaning')->update(['status' => 'limpieza']);

        Schema::table('rooms', function (Blueprint $table) {
            $table->string('status')->default('libre')->change();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            $table->string('status')->default('available')->change();
        });

        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'libre')->update(['status' => 'available']);
        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'ocupada')->update(['status' => 'occupied']);
        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'mantenimiento')->update(['status' => 'maintenance']);
        \Illuminate\Support\Facades\DB::table('rooms')->where('status', 'limpieza')->update(['status' => 'cleaning']);
    }
};
