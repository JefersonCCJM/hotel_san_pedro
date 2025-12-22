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
        Schema::create('room_statuses', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('code')->unique();
            $table->string('color')->default('bg-gray-50 text-gray-700');
            $table->string('icon')->default('fa-door-open');
            $table->boolean('is_visible_public')->default(true);
            $table->boolean('is_actionable')->default(false);
            $table->unsignedBigInteger('next_status_id')->nullable();
            $table->text('description')->nullable();
            $table->integer('order')->default(0);
            $table->timestamps();

            $table->foreign('next_status_id')->references('id')->on('room_statuses')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('room_statuses');
    }
};
