<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Crear catálogo de fuentes de reserva
        if (!Schema::hasTable('reservation_sources')) {
            Schema::create('reservation_sources', function (Blueprint $table) {
                $table->id();
                $table->string('code')->unique();
                $table->string('name');
                $table->timestamps();
            });
        }

        // Convertir columna source de enum a FK
        if (Schema::hasTable('reservations') && Schema::hasColumn('reservations', 'source')) {
            // Agregar nueva columna source_id
            Schema::table('reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('reservations', 'source_id')) {
                    $table->foreignId('source_id')->nullable()->after('payment_status_id')->constrained('reservation_sources');
                }
            });

            // Migrar datos del enum al FK (después de que se ejecute el seeder)
            // Se mapean los valores del enum a los códigos de la tabla
            DB::statement(<<<SQL
UPDATE reservations r
LEFT JOIN reservation_sources rs ON rs.code = r.source
SET r.source_id = rs.id
WHERE r.source_id IS NULL AND r.source IS NOT NULL
SQL);

            // Eliminar columna source antigua
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'source')) {
                    $table->dropColumn('source');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('reservations')) {
            // Restaurar columna enum
            Schema::table('reservations', function (Blueprint $table) {
                if (!Schema::hasColumn('reservations', 'source')) {
                    $table->enum('source', ['reception', 'web', 'whatsapp', 'ota'])->default('reception')->after('payment_status_id');
                }
            });

            // Migrar datos de vuelta
            DB::statement(<<<SQL
UPDATE reservations r
LEFT JOIN reservation_sources rs ON rs.id = r.source_id
SET r.source = rs.code
WHERE r.source IS NULL AND r.source_id IS NOT NULL
SQL);

            // Eliminar FK y columna
            Schema::table('reservations', function (Blueprint $table) {
                if (Schema::hasColumn('reservations', 'source_id')) {
                    $table->dropForeign(['source_id']);
                    $table->dropColumn('source_id');
                }
            });
        }

        if (Schema::hasTable('reservation_sources')) {
            Schema::drop('reservation_sources');
        }
    }
};
