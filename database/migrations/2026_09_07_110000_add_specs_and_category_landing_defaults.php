<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * The two things that make a second campaign faster than the first.
 *
 * `specs` is a label/value table — warranty, model, weight — which is what a
 * gadget page argues with and a mango page has no use for. It is a block like
 * any other, off unless switched on.
 *
 * `landing_defaults` is the category's starting draft: the selling points, the
 * questions and the block arrangement that every promotion in that category
 * opens with. Copied into the page at creation rather than read at render time,
 * so editing a category never rewrites a campaign that is already running.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->json('specs')->nullable()->after('reviews');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->json('landing_defaults')->nullable()->after('theme');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('specs');
        });

        Schema::table('categories', function (Blueprint $table) {
            $table->dropColumn('landing_defaults');
        });
    }
};
