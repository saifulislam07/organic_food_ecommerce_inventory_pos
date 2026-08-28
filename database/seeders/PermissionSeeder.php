<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AdminModules;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Creates every permission and the Super Admin role.
 *
 *   php artisan db:seed --class=PermissionSeeder
 *
 * Safe to re-run: it adds what is missing and leaves existing grants alone.
 */
class PermissionSeeder extends Seeder
{
    public function run(): void
    {
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        foreach (AdminModules::permissions() as $name) {
            Permission::findOrCreate($name, 'web');
        }

        // Super Admin is granted through a Gate::before hook rather than by
        // holding every permission, so it never goes stale as modules are added.
        $superAdmin = Role::findOrCreate(AdminModules::SUPER_ADMIN, 'web');

        // Anyone already marked as an admin keeps full access.
        User::where('role', 'admin')->each(function (User $user) use ($superAdmin) {
            if (! $user->hasRole($superAdmin)) {
                $user->assignRole($superAdmin);
            }
        });

        $this->command?->info(
            count(AdminModules::permissions()).' permissions ready; '.
            User::role(AdminModules::SUPER_ADMIN)->count().' super admin(s).'
        );
    }
}
