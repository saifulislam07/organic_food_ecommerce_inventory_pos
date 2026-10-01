<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

/**
 * Staff can correct a customer's details and block an account outright.
 */
class CustomerManageTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function customer(array $attributes = []): User
    {
        return User::factory()->create(array_merge([
            'role' => 'customer',
            'mobile' => '01711111111',
            'password' => Hash::make('secret123'),
        ], $attributes));
    }

    /* ---------------------------------------------------------------- edit */

    public function test_an_admin_can_edit_a_customers_details(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->admin())
            ->get(route('admin.customers.edit', $customer))
            ->assertOk();

        $this->actingAs($this->admin())
            ->put(route('admin.customers.update', $customer), [
                'name' => 'Karim Mia',
                'mobile' => '01822222222',
                'email' => 'karim@example.com',
                'password' => '',
            ])
            ->assertRedirect(route('admin.customers.show', $customer));

        $customer->refresh();
        $this->assertSame('Karim Mia', $customer->name);
        $this->assertSame('01822222222', $customer->mobile);
        $this->assertSame('karim@example.com', $customer->email);
        $this->assertTrue(Hash::check('secret123', $customer->password), 'A blank password leaves it alone.');
    }

    public function test_a_new_password_can_be_set(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->admin())->put(route('admin.customers.update', $customer), [
            'name' => $customer->name,
            'mobile' => $customer->mobile,
            'password' => 'newpass99',
        ]);

        $this->assertTrue(Hash::check('newpass99', $customer->fresh()->password));
    }

    public function test_the_mobile_number_must_stay_unique(): void
    {
        $this->customer(['mobile' => '01822222222']);
        $customer = $this->customer();

        $this->actingAs($this->admin())
            ->put(route('admin.customers.update', $customer), [
                'name' => $customer->name,
                'mobile' => '01822222222',
            ])
            ->assertSessionHasErrors('mobile');
    }

    public function test_an_admin_account_cannot_be_edited_from_here(): void
    {
        $other = User::factory()->superAdmin()->create();

        $this->actingAs($this->admin())
            ->get(route('admin.customers.edit', $other))
            ->assertNotFound();
    }

    /* --------------------------------------------------------------- block */

    public function test_blocking_and_unblocking_a_customer(): void
    {
        $customer = $this->customer();

        $this->actingAs($this->admin())->post(route('admin.customers.toggleBlock', $customer));
        $this->assertTrue($customer->fresh()->isBlocked());

        $this->actingAs($this->admin())->post(route('admin.customers.toggleBlock', $customer));
        $this->assertFalse($customer->fresh()->isBlocked());
    }

    public function test_a_blocked_customer_cannot_sign_in(): void
    {
        $this->customer(['blocked_at' => now()]);

        $this->post('/login', ['login_id' => '01711111111', 'password' => 'secret123'])
            ->assertSessionHasErrors('login_id');

        $this->assertGuest();
    }

    public function test_a_blocked_customer_who_is_already_signed_in_is_signed_out(): void
    {
        $customer = $this->customer();

        $this->actingAs($customer)->get(route('customer.dashboard'))->assertOk();

        $customer->forceFill(['blocked_at' => now()])->save();

        $this->actingAs($customer)
            ->get(route('customer.dashboard'))
            ->assertRedirect(route('login'));

        $this->assertGuest();
    }

    public function test_an_unblocked_customer_can_sign_in_again(): void
    {
        $this->customer();

        $this->post('/login', ['login_id' => '01711111111', 'password' => 'secret123'])
            ->assertRedirect(route('customer.dashboard', absolute: false));

        $this->assertAuthenticated();
    }

    /* --------------------------------------------------------- permissions */

    public function test_staff_with_view_only_cannot_edit_or_block(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['customers.view']);
        $customer = $this->customer();

        $this->actingAs($staff)->get(route('admin.customers.edit', $customer))->assertForbidden();
        $this->actingAs($staff)->post(route('admin.customers.toggleBlock', $customer))->assertForbidden();
        $this->assertFalse($customer->fresh()->isBlocked());

        $this->actingAs($staff)
            ->get(route('admin.customers.show', $customer))
            ->assertOk()
            ->assertDontSee(route('admin.customers.edit', $customer));
    }

    public function test_staff_with_edit_can_block(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['customers.view', 'customers.edit']);
        $customer = $this->customer();

        $this->actingAs($staff)->post(route('admin.customers.toggleBlock', $customer));

        $this->assertTrue($customer->fresh()->isBlocked());
    }
}
