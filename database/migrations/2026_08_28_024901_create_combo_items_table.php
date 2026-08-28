<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('combo_items', function (Blueprint $table) {
            $table->id();

            // The bundle being sold.
            $table->foreignId('combo_variant_id')->constrained('product_variants')->cascadeOnDelete();

            // What comes out of stock when the bundle sells. Restricted rather
            // than cascaded: removing a component silently would make the combo
            // sell for less than it contains.
            $table->foreignId('component_variant_id')->constrained('product_variants')->restrictOnDelete();

            $table->unsignedInteger('quantity')->default(1);
            $table->timestamps();

            $table->unique(['combo_variant_id', 'component_variant_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('combo_items');
    }
};
