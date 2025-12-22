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
        Schema::create('sales', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('restrict')->comment('Recepcionista que realizó la venta');
            $table->foreignId('room_id')->nullable()->constrained()->onDelete('set null')->comment('Habitación asociada (null si es venta normal)');
            $table->enum('shift', ['dia', 'noche'])->default('dia')->comment('Turno: día o noche');
            $table->enum('payment_method', ['efectivo', 'transferencia', 'pendiente'])->default('efectivo');
            $table->enum('debt_status', ['pagado', 'pendiente'])->default('pagado');
            $table->date('sale_date')->comment('Fecha de la venta');
            $table->decimal('total', 12, 2)->default(0);
            $table->text('notes')->nullable()->comment('Notas adicionales');
            $table->timestamps();
            
            $table->index(['sale_date', 'user_id']);
            $table->index(['sale_date', 'shift']);
            $table->index('debt_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sales');
    }
};
