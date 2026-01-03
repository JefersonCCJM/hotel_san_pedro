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
        Schema::create('room_release_history', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->onDelete('cascade');
            $table->foreignId('reservation_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->foreignId('released_by')->nullable()->constrained('users')->onDelete('set null');
            
            // Información de la habitación al momento de liberación
            $table->string('room_number');
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->decimal('deposit', 12, 2)->default(0);
            $table->decimal('consumptions_total', 12, 2)->default(0);
            $table->decimal('pending_amount', 12, 2)->default(0);
            $table->integer('guests_count')->default(1);
            
            // Fechas
            $table->date('check_in_date');
            $table->date('check_out_date');
            $table->date('release_date');
            $table->string('target_status'); // libre, pendiente_aseo, limpia
            
            // Información del cliente
            $table->string('customer_name');
            $table->string('customer_identification')->nullable();
            $table->string('customer_phone')->nullable();
            $table->string('customer_email')->nullable();
            
            // Datos completos en JSON para preservar toda la información
            $table->json('reservation_data')->nullable(); // Datos completos de la reserva
            $table->json('sales_data')->nullable(); // Todos los consumos
            $table->json('deposits_data')->nullable(); // Todos los abonos
            $table->json('guests_data')->nullable(); // Todos los huéspedes
            
            $table->timestamps();
            
            // Índices para búsquedas rápidas
            $table->index('room_id');
            $table->index('release_date');
            $table->index('customer_id');
            $table->index('released_by');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_release_history');
    }
};

