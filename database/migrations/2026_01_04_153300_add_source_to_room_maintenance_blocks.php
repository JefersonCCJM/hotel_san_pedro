<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Catálogo de fuentes de bloqueos de mantenimiento
        if (!Schema::hasTable('room_maintenance_block_sources')) {
            Schema::create('room_maintenance_block_sources', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Agregar source_id a room_maintenance_blocks
        if (Schema::hasTable('room_maintenance_blocks') && !Schema::hasColumn('room_maintenance_blocks', 'source_id')) {
            Schema::table('room_maintenance_blocks', function (Blueprint $table) {
                $table->foreignId('source_id')->nullable()->after('status_id')->constrained('room_maintenance_block_sources');
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('room_maintenance_blocks') && Schema::hasColumn('room_maintenance_blocks', 'source_id')) {
            Schema::table('room_maintenance_blocks', function (Blueprint $table) {
                $table->dropForeign(['source_id']);
                $table->dropColumn('source_id');
            });
        }

        if (Schema::hasTable('room_maintenance_block_sources')) {
            Schema::drop('room_maintenance_block_sources');
        }
    }
};
