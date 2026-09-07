<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Where a campaign page gets its look from.
 *
 * A theme is a skin — colours, corners, a hero band — and nothing else; which
 * blocks render and in what order is still `template`'s job. Keeping them apart
 * means a mango page and a gadget page can share one layout and look nothing
 * alike, which is the whole point.
 *
 * The category owns the theme so that picking "আম" on a new promotion is the
 * only decision anyone has to make. The page keeps its own nullable column on
 * top: null means "whatever the category says", and a value overrides it for
 * one campaign without disturbing every other page in that category.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('categories', function (Blueprint $table) {
            // Null is the brand's own green — a category nobody has themed
            // still gets a page that looks like the shop.
            $table->string('theme', 32)->nullable()->after('slug');
        });

        Schema::table('landing_pages', function (Blueprint $table) {
            // Nulled rather than cascaded: deleting a category should cost a
            // running campaign its colours, never its orders.
            $table->foreignId('category_id')->nullable()->after('internal_name')
                ->constrained()->nullOnDelete();
            $table->string('theme', 32)->nullable()->after('template');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropConstrainedForeignId('category_id');
            $table->dropColumn('theme');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('theme');
        });
    }
};
