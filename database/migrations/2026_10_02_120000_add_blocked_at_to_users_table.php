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
        Schema::table('users', function (Blueprint $table) {
            // When the account was blocked; null means it can sign in.
            $table->timestamp('blocked_at')->nullable()->after('role');
        });

        // Customers gained an edit ability (edit details, block). Created here
        // so existing installs can grant it without re-running the seeder.
        if (Schema::hasTable('permissions')) {
            Permission::findOrCreate('customers.edit', 'web');
            app(PermissionRegistrar::class)->forgetCachedPermissions();
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn('blocked_at');
        });
    }
};
