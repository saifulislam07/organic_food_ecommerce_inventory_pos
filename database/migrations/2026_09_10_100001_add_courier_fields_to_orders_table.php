<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Which courier is carrying this order, and what that courier last said about it.
 *
 * The courier's own word is kept verbatim in courier_status rather than being
 * folded straight into the order status: providers use their own vocabulary
 * ("in_review", "delivery_pending", "partial_delivered"), they add to it without
 * warning, and when a sync maps something wrongly the raw value is the only way
 * to find out what actually arrived.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Driver key — steadfast, pathao, redx, manual. Not a foreign key:
            // couriers are configuration, not records.
            $table->string('courier', 32)->nullable()->after('delivered_at');

            // What the provider calls this parcel. Two ids because most of them
            // issue an internal one and a customer-facing tracking code, and
            // support asks for whichever one you do not have.
            $table->string('courier_consignment_id', 64)->nullable()->after('courier');
            $table->string('courier_tracking_code', 64)->nullable()->after('courier_consignment_id');

            // The provider's own status word, exactly as it came back.
            $table->string('courier_status', 64)->nullable()->after('courier_tracking_code');
            $table->string('courier_status_note')->nullable()->after('courier_status');
            $table->timestamp('courier_synced_at')->nullable()->after('courier_status_note');

            // The sync command sweeps "booked with someone, not finished yet",
            // so those are the two columns it filters on.
            $table->index(['courier', 'status'], 'orders_courier_status_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_courier_status_index');
            $table->dropColumn([
                'courier', 'courier_consignment_id', 'courier_tracking_code',
                'courier_status', 'courier_status_note', 'courier_synced_at',
            ]);
        });
    }
};
