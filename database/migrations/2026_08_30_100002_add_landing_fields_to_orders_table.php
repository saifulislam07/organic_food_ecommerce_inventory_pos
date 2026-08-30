<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * An order placed from a landing page has to remember where it came from, or
 * there is no way to tell which campaign paid for itself.
 *
 * Everything is nullable: existing orders predate all of it.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->foreignId('landing_page_id')->nullable()->after('source')
                ->constrained()->nullOnDelete();

            $table->string('utm_source')->nullable()->after('landing_page_id');
            $table->string('utm_medium')->nullable()->after('utm_source');
            $table->string('utm_campaign')->nullable()->after('utm_medium');
            $table->string('utm_content')->nullable()->after('utm_campaign');
            $table->string('fbclid')->nullable()->after('utm_content');

            // The orders list filters by channel now that there is more than
            // one to filter by.
            $table->index('source', 'orders_source_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_source_index');
            $table->dropConstrainedForeignId('landing_page_id');
            $table->dropColumn([
                'utm_source', 'utm_medium', 'utm_campaign', 'utm_content', 'fbclid',
            ]);
        });
    }
};
