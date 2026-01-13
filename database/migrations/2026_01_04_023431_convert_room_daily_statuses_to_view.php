<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        // Si room_daily_statuses existe como tabla, renombrarla
        if (Schema::hasTable('room_daily_statuses')) {
            Schema::rename('room_daily_statuses', 'room_daily_statuses_data');
        } else {
            // Si no existe como tabla, intentar eliminar como vista (si existe)
            $this->dropViewIfExists('room_daily_statuses');
            
            // Crear la tabla de datos si no existe
            if (!Schema::hasTable('room_daily_statuses_data')) {
                Schema::create('room_daily_statuses_data', function (Blueprint $table) {
                    $table->id();
                    $table->foreignId('room_id')->constrained()->cascadeOnDelete();
                    $table->date('date');
                    $table->string('status');
                    $table->string('cleaning_status');
                    $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
                    $table->string('guest_name')->nullable();
                    $table->date('check_out_date')->nullable();
                    $table->json('guests_data')->nullable();
                    $table->decimal('total_amount', 12, 2)->default(0);
                    $table->timestamps();
                    $table->unique(['room_id', 'date']);
                    $table->index(['date', 'status']);
                });
            }
        }

        // Crear la vista con el mismo nombre apuntando a la tabla renombrada
        DB::statement(<<<SQL
CREATE VIEW room_daily_statuses AS
SELECT
    id,
    room_id,
    date,
    status,
    cleaning_status,
    reservation_id,
    guest_name,
    check_out_date,
    guests_data,
    total_amount,
    created_at,
    updated_at
FROM room_daily_statuses_data
SQL);
    }

    /**
     * Elimina una vista de forma segura (solo si es una vista)
     */
    private function dropViewIfExists(string $name): void
    {
        try {
            // Verificar si es una vista consultando information_schema
            $viewExists = DB::selectOne(
                "SELECT TABLE_NAME 
                 FROM information_schema.VIEWS 
                 WHERE TABLE_SCHEMA = DATABASE() 
                 AND TABLE_NAME = ?",
                [$name]
            );

            if ($viewExists) {
                DB::statement("DROP VIEW IF EXISTS `{$name}`");
            }
        } catch (\Exception $e) {
            // Si hay algún error, simplemente continuar
            // Puede que la base de datos no soporte information_schema o haya otro problema
        }
    }

    public function down(): void
    {
        // Eliminar vista si existe
        $this->dropViewIfExists('room_daily_statuses');

        // Si la tabla origen existe, restaurarla al nombre original
        if (Schema::hasTable('room_daily_statuses_data')) {
            Schema::rename('room_daily_statuses_data', 'room_daily_statuses');
            return;
        }

        // Fallback: recrear la tabla con el esquema original
        Schema::create('room_daily_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status');
            $table->string('cleaning_status');
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->date('check_out_date')->nullable();
            $table->json('guests_data')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();
            $table->unique(['room_id', 'date']);
            $table->index(['date', 'status']);
        });
    }
};