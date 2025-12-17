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
        // Add indexes to products table
        Schema::table('products', function (Blueprint $table) {
            $table->index('category_id');
            $table->index('status');
            $table->index('quantity');
            $table->index(['status', 'category_id']);
        });

        // Add indexes to customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->index('is_active');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Remove indexes from products table
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex(['category_id']);
            $table->dropIndex(['status']);
            $table->dropIndex(['quantity']);
            $table->dropIndex(['status', 'category_id']);
        });

        // Remove indexes from customers table
        Schema::table('customers', function (Blueprint $table) {
            $table->dropIndex(['is_active']);
        });
    }
};
