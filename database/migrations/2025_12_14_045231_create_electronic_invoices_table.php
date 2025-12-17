<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('electronic_invoices', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sale_id')->nullable();
            $table->foreignId('customer_id')->constrained()->onDelete('cascade');
            $table->unsignedBigInteger('factus_numbering_range_id')->nullable();
            
            // Relaciones con catálogos DIAN
            $table->foreignId('document_type_id')->constrained('dian_document_types')->onDelete('restrict');
            $table->foreignId('operation_type_id')->constrained('dian_operation_types')->onDelete('restrict');
            $table->string('payment_method_code', 10)->nullable();
            $table->string('payment_form_code', 10)->nullable();
            
            // Identificación del documento
            $table->string('reference_code')->unique();
            $table->string('document');
            
            // Estado
            $table->enum('status', [
                'pending',
                'sent',
                'accepted',
                'rejected',
                'cancelled'
            ])->default('pending');
            
            // Códigos DIAN
            $table->string('cufe')->nullable()->unique();
            $table->text('qr')->nullable();
            
            // Valores financieros
            $table->decimal('total', 15, 2);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('gross_value', 15, 2);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('surcharge_amount', 15, 2)->default(0);
            
            // Fechas
            $table->timestamp('validated_at')->nullable();
            
            // Payloads y respuestas
            $table->json('payload_sent')->nullable();
            $table->json('response_dian')->nullable();
            
            // URLs de documentos
            $table->string('pdf_url')->nullable();
            $table->string('xml_url')->nullable();
            
            $table->timestamps();
            
            // Foreign keys
            $table->foreign('payment_method_code')->references('code')->on('dian_payment_methods')->onDelete('set null');
            $table->foreign('payment_form_code')->references('code')->on('dian_payment_forms')->onDelete('set null');
            
            // Índices
            $table->index('sale_id');
            $table->index('customer_id');
            $table->index('status');
            $table->index('cufe');
            $table->index('reference_code');
            $table->index('created_at');
            $table->index('document_type_id');
            $table->index('operation_type_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('electronic_invoices');
    }
};
