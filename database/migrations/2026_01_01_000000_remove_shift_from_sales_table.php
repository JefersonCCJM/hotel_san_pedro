<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            // Eliminar el índice antes de la columna si existe
            $table->dropColumn('shift');
        });

        // Eliminar el índice si existe
        $indexes = DB::select("SHOW INDEXES FROM sales WHERE key_name = 'sales_sale_date_shift_index'");
        if (count($indexes) > 0) {
            DB::statement("ALTER TABLE sales DROP INDEX sales_sale_date_shift_index");
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->enum('shift', ['dia', 'noche'])->default('dia')->after('room_id');
            $table->index(['sale_date', 'shift']);
        });
    }
};

