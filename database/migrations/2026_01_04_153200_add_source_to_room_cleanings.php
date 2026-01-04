<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de fuentes de limpieza
        if (!Schema::hasTable('room_cleaning_sources')) {
            Schema::create('room_cleaning_sources', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Agregar source_id a room_cleanings
        if (Schema::hasTable('room_cleanings') && !Schema::hasColumn('room_cleanings', 'source_id')) {
            Schema::table('room_cleanings', function (Blueprint $table) {
                // Verificar si existe type_room_cleaning_id para colocar después, si no después de cleaned_at
                if (Schema::hasColumn('room_cleanings', 'type_room_cleaning_id')) {
                    $table->foreignId('source_id')->nullable()->after('type_room_cleaning_id')->constrained('room_cleaning_sources');
                } else {
                    $table->foreignId('source_id')->nullable()->after('cleaned_at')->constrained('room_cleaning_sources');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('room_cleanings') && Schema::hasColumn('room_cleanings', 'source_id')) {
            Schema::table('room_cleanings', function (Blueprint $table) {
                $table->dropForeign(['source_id']);
                $table->dropColumn('source_id');
            });
        }

        if (Schema::hasTable('room_cleaning_sources')) {
            Schema::drop('room_cleaning_sources');
        }
    }
};
