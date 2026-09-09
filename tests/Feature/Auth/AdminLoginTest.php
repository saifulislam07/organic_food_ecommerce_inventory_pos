<?php

namespace Tests\Feature\Auth;

use App\Models\User;
use Database\Seeders\AdminSeeder;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The seeded admin account once landed on the column default role of
 * 'customer', so the correct password still bounced off /admin/login.
 */
class AdminLoginTest extends TestCase
{
    use RefreshDatabase;

    public function test_the_seeder_produces_an_account_that_can_reach_the_admin_panel(): void
    {
        $this->seed(DatabaseSeeder::class);

        $admin = User::where('email', 'admin@gmail.com')->firstOrFail();

        $this->assertSame('admin', $admin->role);
        $this->assertTrue($admin->isAdmin());

        $this->post(route('admin.login'), [
            'login_id' => 'admin@gmail.com',
            'password' => '111111',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
        $this->actingAs($admin)->get(route('admin.dashboard'))->assertOk();
    }

    public function test_an_admin_can_sign_in_through_the_admin_form(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->post(route('admin.login'), [
            'login_id' => $admin->email,
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }

    public function test_a_customer_is_signed_back_out_at_the_admin_form(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->post(route('admin.login'), [
            'login_id' => $customer->email,
            'password' => 'password',
        ])
            ->assertRedirect(route('admin.login'))
            ->assertSessionHasErrors('login_id');

        $this->assertGuest();
    }

    public function test_a_wrong_password_is_rejected(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->from(route('admin.login'))
            ->post(route('admin.login'), [
                'login_id' => $admin->email,
                'password' => 'not-the-password',
            ])
            ->assertSessionHasErrors('login_id');

        $this->assertGuest();
    }

    /* ------------------------------------------- the local-only prefill */

    public function test_the_gateway_arrives_filled_in_on_a_development_machine(): void
    {
        config()->set('admin.prefill_login', true);

        $this->seed(AdminSeeder::class);

        $html = $this->get(route('admin.login'))->assertOk()->getContent();

        $this->assertStringContainsString('value="'.config('admin.email').'"', $html);
        $this->assertStringContainsString('value="'.config('admin.password').'"', $html);

        // What is typed in must be what actually opens the panel.
        $this->post(route('admin.login'), [
            'login_id' => config('admin.email'),
            'password' => config('admin.password'),
        ])->assertRedirect(route('admin.dashboard', absolute: false));
    }

    public function test_no_password_reaches_the_page_anywhere_but_local(): void
    {
        // What every environment except a developer's own machine gets.
        config()->set('admin.prefill_login', false);

        $html = $this->get(route('admin.login'))->assertOk()->getContent();

        $this->assertStringNotContainsString(config('admin.password'), $html);
        $this->assertStringNotContainsString(config('admin.email'), $html);
        $this->assertStringContainsString('value=""', $html);
    }

    public function test_an_admin_can_also_sign_in_with_a_mobile_number(): void
    {
        $admin = User::factory()->superAdmin()->create(['mobile' => '01799999999']);

        $this->post(route('admin.login'), [
            'login_id' => '01799999999',
            'password' => 'password',
        ])->assertRedirect(route('admin.dashboard', absolute: false));

        $this->assertAuthenticatedAs($admin);
    }
}
