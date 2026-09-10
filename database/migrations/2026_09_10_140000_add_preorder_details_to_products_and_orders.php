<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Pre-order, from a badge into something you can actually buy.
 *
 * products.is_preorder already existed but only painted a label on the card.
 * What was missing is the terms the shopper agrees to, the record that they
 * agreed, and a way to tell a pre-ordered line from one that was in stock —
 * without which a pre-order looks exactly like an order the shop can ship
 * today.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('products', function (Blueprint $table) {
            // Blank here falls back to the shop-wide note in Settings, so the
            // common case needs no typing per product.
            $table->text('preorder_note_en')->nullable()->after('is_preorder');
            $table->text('preorder_note_bn')->nullable()->after('preorder_note_en');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->boolean('has_preorder')->default(false)->after('source');
            // A copy of the terms as they were shown, beside the timestamp:
            // editing the note later must not rewrite what someone agreed to
            // last month.
            $table->text('preorder_terms')->nullable()->after('has_preorder');
            $table->timestamp('preorder_accepted_at')->nullable()->after('preorder_terms');
            $table->index('has_preorder');
        });

        Schema::table('order_items', function (Blueprint $table) {
            // Per line, because one order can hold both: two things off the
            // shelf and one still coming.
            $table->boolean('is_preorder')->default(false)->after('quantity');
        });
    }

    public function down(): void
    {
        Schema::table('products', function (Blueprint $table) {
            $table->dropColumn(['preorder_note_en', 'preorder_note_bn']);
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex(['has_preorder']);
            $table->dropColumn(['has_preorder', 'preorder_terms', 'preorder_accepted_at']);
        });

        Schema::table('order_items', function (Blueprint $table) {
            $table->dropColumn('is_preorder');
        });
    }
};
