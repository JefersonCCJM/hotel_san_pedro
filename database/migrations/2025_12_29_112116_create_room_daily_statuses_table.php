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
        Schema::create('room_daily_statuses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('room_id')->constrained()->cascadeOnDelete();
            $table->date('date');
            $table->string('status');
            $table->string('cleaning_status');
            $table->foreignId('reservation_id')->nullable()->constrained()->nullOnDelete();
            $table->string('guest_name')->nullable();
            $table->decimal('total_amount', 12, 2)->default(0);
            $table->timestamps();

            $table->unique(['room_id', 'date']);
            $table->index(['date', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_daily_statuses');
    }
};
