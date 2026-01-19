<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     * 
     * Crea la tabla stay_nights para representar cada noche cobrable de una estadía.
     * Cada fila = 1 noche cobrable con fecha, precio y estado de pago.
     */
    public function up(): void
    {
        if (!Schema::hasTable('stay_nights')) {
            Schema::create('stay_nights', function (Blueprint $table) {
                $table->id();
                $table->foreignId('stay_id')->constrained('stays')->cascadeOnDelete();
                $table->foreignId('reservation_id')->constrained('reservations')->cascadeOnDelete();
                $table->foreignId('room_id')->constrained('rooms')->cascadeOnDelete();
                $table->date('date'); // Fecha de la noche (2026-01-18, 2026-01-19, etc)
                $table->decimal('price', 12, 2); // Precio de esta noche específica
                $table->boolean('is_paid')->default(false); // Si la noche está pagada
                $table->timestamps();

                // Índices para consultas frecuentes
                $table->index(['stay_id', 'date']);
                $table->index(['reservation_id', 'is_paid']);
                $table->index(['room_id', 'date']);
                
                // Evitar duplicados: una noche por stay y fecha
                $table->unique(['stay_id', 'date']);
            });
        }
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('stay_nights');
    }
};
