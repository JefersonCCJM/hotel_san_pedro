<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Remove legacy sale relationship from electronic invoices.
     */
    public function up(): void
    {
        if (Schema::hasTable('electronic_invoices') && Schema::hasColumn('electronic_invoices', 'sale_id')) {
            Schema::table('electronic_invoices', function (Blueprint $table) {
                $table->dropColumn('sale_id');
            });
        }
    }

    /**
     * Restore sale_id column if rollback is requested.
     * Note: No foreign key is recreated to avoid coupling to removed sales module.
     */
    public function down(): void
    {
        if (Schema::hasTable('electronic_invoices') && !Schema::hasColumn('electronic_invoices', 'sale_id')) {
            Schema::table('electronic_invoices', function (Blueprint $table) {
                $table->unsignedBigInteger('sale_id')->nullable()->after('id');
            });
        }
    }
};

