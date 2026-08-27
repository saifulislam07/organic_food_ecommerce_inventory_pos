<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Product;
use App\Models\Supplier;
use App\Models\User;
use Database\Seeders\AdminSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class AdminSeederTest extends TestCase
{
    use RefreshDatabase;

    public function test_it_creates_an_admin_that_can_sign_in(): void
    {
        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('111111', $admin->password));
        $this->assertNotNull($admin->email_verified_at);

        $this->post(route('admin.login'), [
            'login_id' => 'admin@gmail.com',
            'password' => '111111',
        ])->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_running_it_twice_does_not_duplicate_the_account(): void
    {
        $this->seed(AdminSeeder::class);
        $this->seed(AdminSeeder::class);

        $this->assertSame(1, User::where('email', 'admin@gmail.com')->count());
    }

    public function test_it_promotes_an_existing_user_without_resetting_their_password(): void
    {
        User::create([
            'name' => 'Saiful',
            'email' => 'admin@gmail.com',
            'password' => Hash::make('my-own-password'),
            'role' => 'customer',
        ]);

        $this->seed(AdminSeeder::class);

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue(Hash::check('my-own-password', $admin->password), 'A chosen password must survive a re-seed.');
        $this->assertFalse(Hash::check('111111', $admin->password));
        $this->assertSame('Saiful', $admin->name);
    }

    public function test_credentials_can_come_from_the_environment(): void
    {
        config()->set('app.env', 'testing');
        putenv('ADMIN_EMAIL=owner@mangohut.test');
        putenv('ADMIN_NAME=Owner');
        putenv('ADMIN_PASSWORD=s3cret-pass');

        try {
            $this->seed(AdminSeeder::class);

            $admin = User::where('email', 'owner@mangohut.test')->firstOrFail();

            $this->assertSame('Owner', $admin->name);
            $this->assertSame('admin', $admin->role);
            $this->assertTrue(Hash::check('s3cret-pass', $admin->password));
            $this->assertDatabaseMissing('users', ['email' => 'admin@gmail.com']);
        } finally {
            putenv('ADMIN_EMAIL');
            putenv('ADMIN_NAME');
            putenv('ADMIN_PASSWORD');
        }
    }

    public function test_it_leaves_catalogue_data_alone(): void
    {
        $this->seed(AdminSeeder::class);

        $this->assertSame(0, Product::count());
        $this->assertSame(0, Category::count());
        $this->assertSame(0, Supplier::count());
    }
}
