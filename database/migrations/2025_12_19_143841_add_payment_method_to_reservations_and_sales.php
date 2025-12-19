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
        Schema::table('reservations', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('deposit'); // efectivo, transferencia
        });

        Schema::table('reservation_sales', function (Blueprint $table) {
            $table->string('payment_method')->nullable()->after('total'); // efectivo, transferencia
            $table->boolean('is_paid')->default(false)->after('payment_method');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('reservations', function (Blueprint $table) {
            $table->dropColumn('payment_method');
        });

        Schema::table('reservation_sales', function (Blueprint $table) {
            $table->dropColumn(['payment_method', 'is_paid']);
        });
    }
};
