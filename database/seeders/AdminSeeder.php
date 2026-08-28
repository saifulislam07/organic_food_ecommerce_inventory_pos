<?php

namespace Database\Seeders;

use App\Models\User;
use App\Support\AdminModules;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Makes sure an administrator exists, without touching catalogue data.
 *
 *   php artisan db:seed --class=AdminSeeder
 *
 * Credentials come from ADMIN_EMAIL / ADMIN_PASSWORD / ADMIN_NAME when those are
 * set, so a live site never has to hard-code them here.
 */
class AdminSeeder extends Seeder
{
    public function run(): void
    {
        $email = env('ADMIN_EMAIL', 'admin@gmail.com');
        $name = env('ADMIN_NAME', 'Admin');
        $password = env('ADMIN_PASSWORD', '111111');

        $admin = User::where('email', $email)->first();

        if ($admin) {
            // Re-running must not reset a password the owner has since changed;
            // the job here is only to guarantee the account can reach /admin.
            $admin->forceFill([
                'role' => 'admin',
                'email_verified_at' => $admin->email_verified_at ?? now(),
            ])->save();

            $this->grantFullAccess($admin);
            $this->command?->info("Existing user {$email} promoted to admin.");

            return;
        }

        $admin = User::create([
            'name' => $name,
            'email' => $email,
            'password' => Hash::make($password),
            // The column defaults to 'customer', and storeAdmin() rejects that.
            'role' => 'admin',
            'email_verified_at' => now(),
        ]);

        $this->grantFullAccess($admin);

        $this->command?->info("Admin {$email} created.");
    }

    /**
     * Reaching the panel needs `role`, but doing anything inside it needs the
     * Super Admin role — an administrator without it would see an empty panel.
     */
    private function grantFullAccess(User $admin): void
    {
        $role = Role::findOrCreate(AdminModules::SUPER_ADMIN, 'web');

        if (! $admin->hasRole($role)) {
            $admin->assignRole($role);
        }
    }
}
