<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * A slide is often just a picture — the headline is baked into the artwork.
 * Requiring an English title made the admin invent one nobody wanted shown.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('title_en')->nullable()->change();
        });
    }

    public function down(): void
    {
        // A titleless row cannot go back into a NOT NULL column.
        DB::table('hero_slides')->whereNull('title_en')->update(['title_en' => '']);

        Schema::table('hero_slides', function (Blueprint $table) {
            $table->string('title_en')->nullable(false)->change();
        });
    }
};
