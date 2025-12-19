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
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'beds_count')) {
                $table->integer('beds_count')->default(1)->after('room_number');
            }
            if (Schema::hasColumn('rooms', 'room_type')) {
                $table->dropColumn('room_type');
            }
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('rooms', function (Blueprint $table) {
            if (!Schema::hasColumn('rooms', 'room_type')) {
                $table->string('room_type')->after('room_number')->nullable();
            }
            if (Schema::hasColumn('rooms', 'beds_count')) {
                $table->dropColumn('beds_count');
            }
        });
    }
};
