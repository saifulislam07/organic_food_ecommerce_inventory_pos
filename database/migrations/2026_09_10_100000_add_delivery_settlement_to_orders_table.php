<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * What a delivery actually settled for.
 *
 * Until now an order going to "delivered" told the books it had earned its
 * total, in whatever account payment_method named. For cash on delivery that is
 * wrong twice over: the courier keeps its fee out of what it collects, so the
 * money that reaches us is less than the total, and it reaches whichever account
 * the courier remits into rather than a notional "cod" head.
 *
 * These columns record the settlement as it happened, so the profit and loss
 * report can stop guessing. All nullable: an order that has not been delivered
 * has nothing to say here, and that has to stay distinguishable from one that
 * settled at zero.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            // Money that actually reached us, net of whatever the courier kept.
            $table->decimal('collected_amount', 10, 2)->nullable()->after('paid_amount');

            // Which account head it landed in — cash, bKash, bank. Drives the
            // "in" column of the profit and loss account table.
            $table->string('collected_in', 20)->nullable()->after('collected_amount');

            // What the courier charged for the delivery. A real cost, so it
            // comes off the profit; kept apart from expenses because it belongs
            // to one order and is reported per order.
            $table->decimal('courier_charge', 10, 2)->default(0)->after('collected_in');

            $table->string('settlement_note')->nullable()->after('courier_charge');

            // When it was actually handed over, which is not when the row was
            // last touched. Reports that ask "what did we deliver in August"
            // need the delivery date, not updated_at.
            $table->timestamp('delivered_at')->nullable()->after('settlement_note');

            $table->index('delivered_at', 'orders_delivered_at_index');
            $table->index('collected_in', 'orders_collected_in_index');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_delivered_at_index');
            $table->dropIndex('orders_collected_in_index');
            $table->dropColumn([
                'collected_amount', 'collected_in', 'courier_charge',
                'settlement_note', 'delivered_at',
            ]);
        });
    }
};
