<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('coupons', function (Blueprint $table) {
            $table->id();
            $table->string('code', 40)->unique();      // stored upper-cased
            $table->string('label_en')->nullable();    // what the shopper sees on the cart line
            $table->string('label_bn')->nullable();
            $table->string('type', 10);                // percent | fixed
            $table->decimal('value', 10, 2);
            // A ceiling for percentage coupons: "20% off, at most ৳300 a unit".
            $table->decimal('max_discount', 10, 2)->nullable();
            $table->decimal('min_order_amount', 10, 2)->nullable();
            $table->string('applies_to', 12)->default('all');   // all | categories | products
            $table->unsignedInteger('usage_limit')->nullable();
            $table->unsignedInteger('usage_limit_per_user')->nullable();
            // Kept as a column rather than counted from orders: the check runs on
            // every cart render, and an order can be deleted.
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamp('starts_at')->nullable();
            $table->timestamp('ends_at')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'starts_at', 'ends_at']);
        });

        Schema::create('category_coupon', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')->constrained()->cascadeOnDelete();
            $table->unique(['coupon_id', 'category_id']);
        });

        Schema::create('coupon_product', function (Blueprint $table) {
            $table->id();
            $table->foreignId('coupon_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->unique(['coupon_id', 'product_id']);
        });

        Schema::table('orders', function (Blueprint $table) {
            // Null on delete rather than cascade: deleting a spent coupon must
            // never take the orders it paid for with it. The code is snapshotted
            // beside it so an invoice still reads correctly afterwards.
            $table->foreignId('coupon_id')->nullable()->after('discount_amount')
                ->constrained()->nullOnDelete();
            $table->string('coupon_code', 40)->nullable()->after('coupon_id');
        });
    }

    public function down(): void
    {
        Schema::table('orders', function (Blueprint $table) {
            $table->dropForeign(['coupon_id']);
            $table->dropColumn(['coupon_id', 'coupon_code']);
        });

        Schema::dropIfExists('coupon_product');
        Schema::dropIfExists('category_coupon');
        Schema::dropIfExists('coupons');
    }
};
