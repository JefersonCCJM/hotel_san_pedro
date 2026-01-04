<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de fuentes de pagos
        if (!Schema::hasTable('payment_sources')) {
            Schema::create('payment_sources', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Agregar source_id a payments
        if (Schema::hasTable('payments') && !Schema::hasColumn('payments', 'source_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->foreignId('source_id')->nullable()->after('payment_type_id')->constrained('payment_sources');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('payments') && Schema::hasColumn('payments', 'source_id')) {
            Schema::table('payments', function (Blueprint $table) {
                $table->dropForeign(['source_id']);
                $table->dropColumn('source_id');
            });
        }

        if (Schema::hasTable('payment_sources')) {
            Schema::drop('payment_sources');
        }
    }
};
