<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Indexes for the queries the shop actually runs.
 *
 * With a few hundred products these are barely measurable; with a few thousand
 * the home page and the date-ranged reports would be scanning whole tables.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // The three home page shelves.
            $table->index(['is_active', 'is_featured'], 'products_active_featured_index');
            $table->index(['is_active', 'is_bestseller'], 'products_active_bestseller_index');
            $table->index(['is_active', 'is_trending'], 'products_active_trending_index');

            // Browsing a category, and the combo list.
            $table->index(['category_id', 'is_active'], 'products_category_active_index');
            $table->index('is_combo', 'products_is_combo_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->index(['product_id', 'sort_order'], 'variants_product_sort_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            // Admin order list and the dashboard's daily figures.
            $table->index('status', 'orders_status_index');
            $table->index('created_at', 'orders_created_at_index');
        });

        // The profit and loss report reads all three by date.
        Schema::table('expenses', function (Blueprint $table) {
            $table->index('expense_date', 'expenses_date_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->index('purchase_date', 'purchases_date_index');
        });

        Schema::table('adjustments', function (Blueprint $table) {
            $table->index(['adjustment_date', 'type'], 'adjustments_date_type_index');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropIndex('products_active_featured_index');
            $table->dropIndex('products_active_bestseller_index');
            $table->dropIndex('products_active_trending_index');
            $table->dropIndex('products_category_active_index');
            $table->dropIndex('products_is_combo_index');
        });

        Schema::table('product_variants', function (Blueprint $table) {
            $table->dropIndex('variants_product_sort_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_status_index');
            $table->dropIndex('orders_created_at_index');
        });

        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_date_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_date_index');
        });

        Schema::table('adjustments', function (Blueprint $table) {
            $table->dropIndex('adjustments_date_type_index');
        });
    }
};
