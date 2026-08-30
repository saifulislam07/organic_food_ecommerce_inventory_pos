<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a landing page sells. One row per variant on offer, so the same page can
 * carry a single product, a list of packages to choose between, or several
 * items sold together as one bundle.
 *
 * The price lives here rather than being read from the variant: a campaign
 * price is meant to differ from the catalogue price, and changing the shop's
 * price should not silently change what a running ad promised.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('landing_page_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('landing_page_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->cascadeOnDelete();

            // Blank falls back to the product and variant names.
            $table->string('label')->nullable();
            // Blank falls back to the variant's own selling price.
            $table->decimal('offer_price', 10, 2)->nullable();
            // The struck-through number beside it.
            $table->decimal('compare_at_price', 10, 2)->nullable();
            $table->string('image')->nullable();

            // Which package is pre-selected when the page opens.
            $table->boolean('is_default')->default(false);
            $table->unsignedInteger('min_qty')->default(1);
            $table->unsignedInteger('max_qty')->default(10);
            $table->integer('sort_order')->default(0);
            $table->timestamps();

            $table->index(['landing_page_id', 'sort_order'], 'landing_page_items_order_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('landing_page_items');
    }
};
