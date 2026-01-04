<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de fuentes de estadías
        if (!Schema::hasTable('stay_sources')) {
            Schema::create('stay_sources', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Agregar source_id a stays
        if (Schema::hasTable('stays') && !Schema::hasColumn('stays', 'source_id')) {
            Schema::table('stays', function (Blueprint $table) {
                $table->foreignId('source_id')->nullable()->after('status_id')->constrained('stay_sources');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('stays') && Schema::hasColumn('stays', 'source_id')) {
            Schema::table('stays', function (Blueprint $table) {
                $table->dropForeign(['source_id']);
                $table->dropColumn('source_id');
            });
        }

        if (Schema::hasTable('stay_sources')) {
            Schema::drop('stay_sources');
        }
    }
};
