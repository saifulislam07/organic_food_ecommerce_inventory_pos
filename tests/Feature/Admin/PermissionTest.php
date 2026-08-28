<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use App\Support\AdminModules;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * `role` decides who may reach the admin panel; Spatie permissions decide what
 * they can do once inside. Super Admin bypasses the second half entirely.
 */
class PermissionTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    /** Staff who can enter the panel but hold only the permissions given. */
    private function staff(array $permissions = []): User
    {
        $user = User::factory()->admin()->create();
        $user->syncPermissions($permissions);

        return $user->fresh();
    }

    /* -------------------------------------------------------- the basics */

    public function test_every_module_ability_exists_as_a_permission(): void
    {
        foreach (AdminModules::permissions() as $name) {
            $this->assertDatabaseHas('permissions', ['name' => $name, 'guard_name' => 'web']);
        }
    }

    public function test_a_super_admin_reaches_everything(): void
    {
        $admin = User::factory()->superAdmin()->create();

        foreach (['/admin', '/admin/products', '/admin/settings', '/admin/users'] as $url) {
            $this->actingAs($admin)->get($url)->assertOk();
        }
    }

    public function test_staff_with_no_permissions_are_shut_out_of_every_section(): void
    {
        $staff = $this->staff();

        foreach (['/admin', '/admin/products', '/admin/settings'] as $url) {
            $this->actingAs($staff)->get($url)->assertForbidden();
        }
    }

    public function test_a_customer_still_cannot_reach_the_panel_at_all(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $customer->syncPermissions(['products.view']);

        $this->actingAs($customer->fresh())->get('/admin/products')->assertForbidden();
    }

    /* ------------------------------------------------------ per-ability */

    public function test_view_alone_opens_the_list_but_not_the_create_form(): void
    {
        $staff = $this->staff(['dashboard.view', 'products.view']);

        $this->actingAs($staff)->get('/admin/products')->assertOk();
        $this->actingAs($staff)->get('/admin/products/create')->assertForbidden();
    }

    public function test_creating_needs_the_create_permission(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits']);

        $payload = [
            'name_en' => 'Mango',
            'name_bn' => 'আম',
            'category_id' => $category->id,
            'variants' => [['name' => '1 kg', 'price' => 100, 'stock' => 1]],
        ];

        $viewer = $this->staff(['products.view']);
        $this->actingAs($viewer)->post(route('admin.products.store'), $payload)->assertForbidden();
        $this->assertDatabaseCount('products', 0);

        $creator = $this->staff(['products.view', 'products.create']);
        $this->actingAs($creator)->post(route('admin.products.store'), $payload)->assertRedirect();
        $this->assertDatabaseCount('products', 1);
    }

    public function test_deleting_needs_the_delete_permission(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits']);

        $editor = $this->staff(['categories.view', 'categories.edit']);
        $this->actingAs($editor)->delete(route('admin.categories.destroy', $category))->assertForbidden();
        $this->assertModelExists($category);

        $remover = $this->staff(['categories.view', 'categories.delete']);
        $this->actingAs($remover)->delete(route('admin.categories.destroy', $category))->assertRedirect();
        $this->assertModelMissing($category);
    }

    public static function guardedSections(): array
    {
        return [
            'orders' => ['/admin/orders', 'orders.view'],
            'customers' => ['/admin/customers', 'customers.view'],
            'inventory' => ['/admin/inventory', 'inventory.view'],
            'suppliers' => ['/admin/suppliers', 'suppliers.view'],
            'expenses' => ['/admin/expenses', 'expenses.view'],
            'pages' => ['/admin/pages', 'pages.view'],
            'settings' => ['/admin/settings', 'settings.view'],
            'users' => ['/admin/users', 'users.view'],
        ];
    }

    #[DataProvider('guardedSections')]
    public function test_a_section_opens_only_with_its_view_permission(string $url, string $permission): void
    {
        $this->actingAs($this->staff())->get($url)->assertForbidden();
        $this->actingAs($this->staff([$permission]))->get($url)->assertOk();
    }

    /* ---------------------------------------------------------- sidebar */

    public function test_the_sidebar_only_lists_what_the_user_can_open(): void
    {
        $staff = $this->staff(['dashboard.view', 'products.view']);

        $html = $this->actingAs($staff)->get('/admin')->getContent();

        $this->assertStringContainsString(route('admin.products.index'), $html);
        $this->assertStringNotContainsString(route('admin.expenses.index'), $html);
        $this->assertStringNotContainsString(route('admin.settings.index'), $html);
    }

    public function test_a_group_with_nothing_visible_is_dropped_entirely(): void
    {
        $staff = $this->staff(['dashboard.view']);

        $html = $this->actingAs($staff)->get('/admin')->getContent();

        // Nothing in Catalogue is permitted, so the group heading goes too.
        $this->assertStringNotContainsString('id="nav-catalogue"', $html);
        $this->assertStringNotContainsString('id="nav-stock"', $html);
    }

    /* ------------------------------------------------------ user admin */

    public function test_an_admin_can_create_staff_with_specific_access(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->post(route('admin.users.store'), [
            'name' => 'Cashier',
            'email' => 'cashier@example.test',
            'password' => 'secret123',
            'permissions' => ['dashboard.view', 'pos.view', 'pos.create'],
        ])->assertRedirect(route('admin.users.index'));

        $staff = User::where('email', 'cashier@example.test')->firstOrFail();

        $this->assertSame('admin', $staff->role, 'Staff need role=admin to pass the panel gate.');
        $this->assertTrue($staff->can('pos.create'));
        $this->assertFalse($staff->can('products.view'));

        $this->actingAs($staff)->get('/admin/pos')->assertOk();
        $this->actingAs($staff)->get('/admin/products')->assertForbidden();
    }

    public function test_the_last_super_admin_keeps_the_role_even_if_unchecked(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->put(route('admin.users.update', $admin), [
            'name' => $admin->name,
            'email' => $admin->email,
            'roles' => [],
        ])->assertRedirect();

        $this->assertTrue($admin->fresh()->hasRole(AdminModules::SUPER_ADMIN));
    }

    public function test_the_last_super_admin_cannot_be_deleted(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $other = User::factory()->superAdmin()->create();

        // With two of them, one can go.
        $this->actingAs($admin)->delete(route('admin.users.destroy', $other))->assertRedirect();
        $this->assertModelMissing($other);

        // The survivor cannot delete themselves either way.
        $this->actingAs($admin)
            ->delete(route('admin.users.destroy', $admin))
            ->assertSessionHasErrors('user');

        $this->assertModelExists($admin);
    }

    public function test_a_customer_account_is_not_editable_as_staff(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($admin)->get(route('admin.users.edit', $customer))->assertNotFound();
    }

    public function test_managing_users_needs_the_users_permission(): void
    {
        $staff = $this->staff(['users.view']);

        $this->actingAs($staff)->get('/admin/users')->assertOk();
        $this->actingAs($staff)->get('/admin/users/create')->assertForbidden();
    }
}
