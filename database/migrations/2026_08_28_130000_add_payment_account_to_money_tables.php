<?php

use App\Support\PaymentAccounts;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Money leaving the shop had nowhere to record which account it left from, so
 * the profit and loss report could not say where anything went.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->string('paid_from', 20)->default(PaymentAccounts::DEFAULT_PAYOUT)->after('amount');
            $table->index(['paid_from'], 'expenses_paid_from_index');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->string('paid_from', 20)->default(PaymentAccounts::DEFAULT_PAYOUT)->after('quantity');
            $table->index(['paid_from'], 'purchases_paid_from_index');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->index('payment_method', 'orders_payment_method_index');
        });
    }

    public function down(): void
    {
        Schema::table('expenses', function (Blueprint $table) {
            $table->dropIndex('expenses_paid_from_index');
            $table->dropColumn('paid_from');
        });

        Schema::table('purchases', function (Blueprint $table) {
            $table->dropIndex('purchases_paid_from_index');
            $table->dropColumn('paid_from');
        });

        Schema::table('orders', function (Blueprint $table) {
            $table->dropIndex('orders_payment_method_index');
        });
    }
};
