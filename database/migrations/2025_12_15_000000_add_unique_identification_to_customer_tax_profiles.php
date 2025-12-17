<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('customer_tax_profiles', function (Blueprint $table) {
            if (!Schema::hasColumn('customer_tax_profiles', 'identification_document_id')) {
                return;
            }

            $table->unique(
                ['identification_document_id', 'identification'],
                'ctp_identification_document_identification_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('customer_tax_profiles', function (Blueprint $table) {
            $table->dropUnique('ctp_identification_document_identification_unique');
        });
    }
};

