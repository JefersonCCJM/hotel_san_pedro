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
            // Add fields for mixed payment
            $table->decimal('cash_amount', 12, 2)->nullable()->after('payment_method')->comment('Monto pagado en efectivo');
            $table->decimal('transfer_amount', 12, 2)->nullable()->after('cash_amount')->comment('Monto pagado por transferencia');
        });

        // Update payment_method enum to include 'ambos' and 'pendiente'
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('efectivo', 'transferencia', 'ambos', 'pendiente') NOT NULL DEFAULT 'efectivo'");
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('sales', function (Blueprint $table) {
            $table->dropColumn(['cash_amount', 'transfer_amount']);
        });

        // Revert payment_method enum
        DB::statement("ALTER TABLE sales MODIFY COLUMN payment_method ENUM('efectivo', 'transferencia', 'ambos') NOT NULL DEFAULT 'efectivo'");
    }
};
