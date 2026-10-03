<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('incomplete_orders', function (Blueprint $table) {
            $table->id();
            // One per visitor and source, so every keystroke updates a single
            // row instead of piling up new ones.
            $table->string('visitor_key', 64);
            $table->string('source', 20)->default('website');
            $table->foreignId('landing_page_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();

            $table->string('customer_name')->nullable();
            $table->string('customer_phone', 20);
            $table->string('customer_email')->nullable();
            $table->text('customer_address')->nullable();
            $table->string('customer_area')->nullable();
            $table->string('delivery_type', 20)->nullable();
            $table->string('pickup_point')->nullable();
            $table->text('notes')->nullable();

            // What they were about to buy, priced when they last touched the form.
            $table->json('items')->nullable();
            $table->decimal('subtotal', 12, 2)->default(0);
            $table->decimal('discount_amount', 12, 2)->default(0);
            $table->string('coupon_code')->nullable();
            $table->decimal('delivery_charge', 12, 2)->default(0);
            $table->decimal('total', 12, 2)->default(0);

            // Follow-up by the shop.
            $table->string('status', 20)->default('new');
            $table->text('admin_note')->nullable();
            $table->foreignId('handled_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('called_at')->nullable();
            $table->foreignId('order_id')->nullable()->constrained()->nullOnDelete();

            $table->string('ip_address', 45)->nullable();
            $table->string('user_agent', 500)->nullable();
            $table->timestamps();

            $table->index(['visitor_key', 'source']);
            $table->index(['status', 'updated_at']);
            $table->index('customer_phone');
        });

        // Created here so existing installs can grant it without re-running the seeder.
        if (Schema::hasTable('permissions')) {
            foreach (['view', 'edit', 'delete'] as $ability) {
                Permission::findOrCreate("incomplete-orders.{$ability}", 'web');
            }
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('incomplete_orders');

        if (Schema::hasTable('permissions')) {
            Permission::where('name', 'like', 'incomplete-orders.%')->where('guard_name', 'web')->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
