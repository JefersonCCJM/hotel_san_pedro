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
        Schema::table('customers', function (Blueprint $table) {
            // Agregar campo de número de identificación
            $table->string('identification_number')->nullable()->after('phone');
            
            // Agregar campo de tipo de documento (FK a dian_identification_documents)
            $table->unsignedBigInteger('identification_type_id')->nullable()->after('identification_number');
            
            // Agregar clave foránea
            $table->foreign('identification_type_id')
                  ->references('id')
                  ->on('dian_identification_documents')
                  ->onDelete('set null'); // Si se elimina el tipo de documento, mantener el cliente pero sin tipo
                  
            // Agregar índices para mejor rendimiento
            $table->index('identification_number');
            $table->index('identification_type_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('customers', function (Blueprint $table) {
            // Eliminar clave foránea primero
            $table->dropForeign(['identification_type_id']);
            
            // Eliminar índices
            $table->dropIndex(['identification_number']);
            $table->dropIndex(['identification_type_id']);
            
            // Eliminar campos
            $table->dropColumn(['identification_number', 'identification_type_id']);
        });
    }
};
