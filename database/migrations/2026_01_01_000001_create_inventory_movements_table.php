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
        Schema::create('inventory_movements', function (Blueprint $table) {
            $table->id();
            $table->foreignId('product_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained()->onDelete('restrict');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null');
            
            // Cantidad del movimiento (positiva para entradas, negativa para salidas)
            $table->integer('quantity');
            
            // Tipo de movimiento
            $table->enum('type', [
                'input',            // Entrada (compra, reposición)
                'output',           // Salida general (daño, vencimiento)
                'sale',             // Venta (desde el módulo de ventas)
                'adjustment',       // Ajuste manual (corrección de stock)
                'room_consumption'  // Consumo en habitación (amenidades, minibar)
            ]);
            
            $table->string('reason')->nullable()->comment('Razón detallada del movimiento');
            
            // Trazabilidad de stock
            $table->integer('previous_stock')->comment('Stock antes del movimiento');
            $table->integer('current_stock')->comment('Stock después del movimiento');
            
            $table->timestamps();
            
            $table->index(['product_id', 'type']);
            $table->index('created_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_movements');
    }
};

