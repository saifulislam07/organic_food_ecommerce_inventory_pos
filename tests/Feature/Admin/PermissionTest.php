<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Supplier;
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

    /**
     * Every admin list, with the module whose permissions govern its buttons.
     * The lists render with no rows, which is enough: the Add button and the
     * bulk-delete bar are drawn either way.
     */
    public static function listPages(): array
    {
        $pages = [
            'products', 'categories', 'combos', 'units', 'landing-pages',
            'purchases', 'suppliers', 'adjustments',
            'expenses', 'investors', 'investments', 'withdrawals',
            'coupons', 'reviews', 'sliders', 'blocks', 'pages',
            'users', 'roles',
        ];

        return array_combine(
            $pages,
            array_map(fn (string $module) => [$module], $pages)
        );
    }

    /**
     * A button the route will only answer with a 403 is worse than no button:
     * the staff member finds out by pressing it. Every list is asked the same
     * question, so a new one cannot quietly skip its @can.
     */
    #[DataProvider('listPages')]
    public function test_a_list_shows_no_write_button_to_a_viewer(string $module): void
    {
        $viewer = $this->staff(['dashboard.view', "{$module}.view"]);

        $html = $this->actingAs($viewer)
            ->get(route("admin.{$module}.index"))
            ->assertOk()
            ->getContent();

        if (in_array(AdminModules::CREATE, AdminModules::abilities($module), true)) {
            $this->assertStringNotContainsString(
                route("admin.{$module}.create"),
                $html,
                "The {$module} list offers Add to someone who cannot create."
            );
        }

        // Every delete control on these pages is a form carrying @method('DELETE'),
        // the bulk bar included — so a viewer should see not one of them.
        $this->assertStringNotContainsString(
            'value="DELETE"',
            $html,
            "The {$module} list offers a delete to someone who cannot delete."
        );
    }

    #[DataProvider('listPages')]
    public function test_the_same_list_shows_the_buttons_once_granted(string $module): void
    {
        $abilities = AdminModules::abilities($module);
        $granted = ['dashboard.view'];

        foreach ($abilities as $ability) {
            $granted[] = "{$module}.{$ability}";
        }

        $html = $this->actingAs($this->staff($granted))
            ->get(route("admin.{$module}.index"))
            ->assertOk()
            ->getContent();

        if (in_array(AdminModules::CREATE, $abilities, true)) {
            $this->assertStringContainsString(route("admin.{$module}.create"), $html);
        }

        if (in_array(AdminModules::DELETE, $abilities, true)) {
            $this->assertStringContainsString('value="DELETE"', $html);
        }
    }

    /**
     * The pages that are not lists get the same treatment: a detail screen or
     * a dashboard panel opens on a .view permission, but the buttons on it
     * still need their own.
     */
    public function test_an_order_screen_offers_no_edit_to_a_viewer(): void
    {
        $order = Order::create([
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'subtotal' => 500,
            'delivery_charge' => 60,
            'total' => 560,
            'status' => 'confirmed',
            'payment_method' => 'cod',
            'source' => 'website',
        ]);

        $viewer = $this->staff(['dashboard.view', 'orders.view']);
        $html = $this->actingAs($viewer)->get(route('admin.orders.show', $order))->assertOk()->getContent();

        $this->assertStringNotContainsString(route('admin.orders.edit', $order), $html);
        // The status control posts to updateStatus, which needs orders.edit.
        $this->assertStringNotContainsString('OrderStatusControl', $html);
        $this->assertStringNotContainsString(route('admin.orders.settle', $order), $html);

        $editor = $this->staff(['dashboard.view', 'orders.view', 'orders.edit']);
        $html = $this->actingAs($editor)->get(route('admin.orders.show', $order))->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.orders.edit', $order), $html);
        $this->assertStringContainsString('OrderStatusControl', $html);
    }

    public function test_the_dashboard_links_only_where_the_viewer_may_go(): void
    {
        $viewer = $this->staff(['dashboard.view', 'investors.view', 'landing-pages.view']);

        $html = $this->actingAs($viewer)->get('/admin')->assertOk()->getContent();

        $this->assertStringNotContainsString(route('admin.investors.create'), $html);
        $this->assertStringNotContainsString('/admin/landing-pages/', $html);
    }

    /** Row-level buttons need a row, so one list stands for the shape of them all. */
    public function test_a_row_offers_no_action_the_route_would_refuse(): void
    {
        Supplier::create(['name' => 'Green Valley Farms']);

        $viewer = $this->staff(['dashboard.view', 'suppliers.view']);
        $html = $this->actingAs($viewer)->get('/admin/suppliers')->assertOk()->getContent();

        $this->assertStringContainsString('Green Valley Farms', $html);
        $this->assertStringNotContainsString(route('admin.suppliers.create'), $html);
        $this->assertStringNotContainsString('/edit', $html);

        $full = $this->staff([
            'dashboard.view', 'suppliers.view', 'suppliers.create',
            'suppliers.edit', 'suppliers.delete',
        ]);
        $html = $this->actingAs($full)->get('/admin/suppliers')->assertOk()->getContent();

        $this->assertStringContainsString(route('admin.suppliers.create'), $html);
        $this->assertStringContainsString('/edit', $html);
    }

    /** The delete control for one row of the users list, markup and all. */
    private function deleteControl(string $html, User $user): string
    {
        preg_match(
            '/<form action="'.preg_quote(route('admin.users.destroy', $user), '/').'".*?<\/form>/s',
            $html,
            $found
        );

        $this->assertNotEmpty($found, "No delete control for {$user->name} in the users list.");

        return $found[0];
    }

    /**
     * Deleting refuses your own account and the last super admin. The list has
     * to refuse them too — otherwise the only way to learn you are the last
     * super admin is to press Delete and read the error.
     */
    public function test_the_users_list_disables_a_delete_the_route_would_refuse(): void
    {
        $super = User::factory()->superAdmin()->create(['name' => 'Only Super']);
        $manager = $this->staff(['dashboard.view', 'users.view', 'users.edit', 'users.delete']);

        $html = $this->actingAs($manager)->get('/admin/users')->assertOk()->getContent();

        $lastSuper = $this->deleteControl($html, $super);
        $this->assertStringContainsString('disabled', $lastSuper);
        $this->assertStringContainsString('last super admin', $lastSuper);

        // And the route really does refuse it, so the two agree.
        $this->actingAs($manager)->delete(route('admin.users.destroy', $super))->assertSessionHasErrors('user');
        $this->assertModelExists($super);

        $this->assertStringContainsString('disabled', $this->deleteControl($html, $manager));
    }

    public function test_a_row_that_can_go_keeps_its_delete_button(): void
    {
        User::factory()->superAdmin()->create(['name' => 'Only Super']);
        $spare = User::factory()->superAdmin()->create(['name' => 'Spare Super']);
        $manager = $this->staff(['dashboard.view', 'users.view', 'users.delete']);

        $html = $this->actingAs($manager)->get('/admin/users')->assertOk()->getContent();

        // Two super admins, so neither is the last one.
        $this->assertStringNotContainsString('disabled', $this->deleteControl($html, $spare));

        $this->actingAs($manager)->delete(route('admin.users.destroy', $spare))->assertRedirect();
        $this->assertModelMissing($spare);
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
