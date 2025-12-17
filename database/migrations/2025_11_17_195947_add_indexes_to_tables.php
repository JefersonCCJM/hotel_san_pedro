<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        $this->addIndexIfMissing('products', 'category_id');
        $this->addIndexIfMissing('products', 'status');
        $this->addIndexIfMissing('products', 'quantity');
        $this->addIndexIfMissing('products', ['status', 'category_id']);

        // Add indexes to sale_items table
        if (Schema::hasTable('sale_items')) {
            $this->addIndexIfMissing('sale_items', 'sale_id');
            $this->addIndexIfMissing('sale_items', 'product_id');
            $this->addIndexIfMissing('sale_items', ['sale_id', 'product_id']);
        }

        // Add indexes to repairs table
        if (Schema::hasTable('repairs')) {
            $this->addIndexIfMissing('repairs', 'customer_id');
            $this->addIndexIfMissing('repairs', 'repair_status');
            $this->addIndexIfMissing('repairs', 'repair_date');
            $this->addIndexIfMissing('repairs', ['customer_id', 'repair_status']);
        }

        $this->addIndexIfMissing('customers', 'is_active');
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        $this->dropIndexIfExists('products', 'category_id');
        $this->dropIndexIfExists('products', 'status');
        $this->dropIndexIfExists('products', 'quantity');
        $this->dropIndexIfExists('products', ['status', 'category_id']);

        // Remove indexes from sale_items table
        if (Schema::hasTable('sale_items')) {
            $this->dropIndexIfExists('sale_items', 'sale_id');
            $this->dropIndexIfExists('sale_items', 'product_id');
            $this->dropIndexIfExists('sale_items', ['sale_id', 'product_id']);
        }

        // Remove indexes from repairs table
        if (Schema::hasTable('repairs')) {
            $this->dropIndexIfExists('repairs', 'customer_id');
            $this->dropIndexIfExists('repairs', 'repair_status');
            $this->dropIndexIfExists('repairs', 'repair_date');
            $this->dropIndexIfExists('repairs', ['customer_id', 'repair_status']);
        }

        $this->dropIndexIfExists('customers', 'is_active');
    }

    /**
     * Add an index only if it does not exist.
     */
    private function addIndexIfMissing(string $table, string|array $columns): void
    {
        if (!$this->indexExists($table, $this->makeIndexName($table, $columns))) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                $table->index($columns);
            });
        }
    }

    /**
     * Drop an index only if it exists.
     */
    private function dropIndexIfExists(string $table, string|array $columns): void
    {
        if ($this->indexExists($table, $this->makeIndexName($table, $columns))) {
            Schema::table($table, function (Blueprint $table) use ($columns) {
                $table->dropIndex($columns);
            });
        }
    }

    /**
     * Check if an index exists in the database.
     */
    private function indexExists(string $table, string $indexName): bool
    {
        if (!Schema::hasTable($table)) {
            return false;
        }

        $result = DB::select(
            'SHOW INDEX FROM `' . $table . '` WHERE Key_name = ?',
            [$indexName]
        );

        return !empty($result);
    }

    /**
     * Build the default index name Laravel would generate.
     */
    private function makeIndexName(string $table, string|array $columns): string
    {
        $columns = (array) $columns;

        return $table . '_' . implode('_', $columns) . '_index';
    }
};
