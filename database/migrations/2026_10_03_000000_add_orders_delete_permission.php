<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\PermissionRegistrar;

return new class extends Migration
{
    public function up(): void
    {
        // Orders gained a delete ability. Created here so existing installs can
        // grant it without re-running the seeder.
        if (Schema::hasTable('permissions')) {
            Permission::findOrCreate('orders.delete', 'web');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('permissions')) {
            Permission::where('name', 'orders.delete')->where('guard_name', 'web')->delete();
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }
};
