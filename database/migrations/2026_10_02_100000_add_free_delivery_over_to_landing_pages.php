<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * A free-delivery bar per campaign.
 *
 * One page sells a ৳500 toy and wants free delivery from ৳1,000; another sells
 * a ৳3,000 pram and wants it on every order. Null keeps the old behaviour — a
 * page on the shop's delivery charge follows the shop's bar — and 0 switches
 * free delivery off for that page alone.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->decimal('free_delivery_over', 10, 2)->nullable()->after('delivery_outside');
        });
    }

    public function down(): void
    {
        Schema::table('landing_pages', function (Blueprint $table) {
            $table->dropColumn('free_delivery_over');
        });
    }
};
