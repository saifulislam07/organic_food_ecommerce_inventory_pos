<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use App\Support\AdminModules;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

class RoleTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    /* ------------------------------------------------------------- create */

    public function test_a_role_can_be_created_with_chosen_permissions(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.roles.store'), [
                'name' => 'Cashier',
                'permissions' => ['dashboard.view', 'pos.view', 'pos.create'],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHasNoErrors();

        $role = Role::findByName('Cashier');

        $this->assertEqualsCanonicalizing(
            ['dashboard.view', 'pos.view', 'pos.create'],
            $role->permissions->pluck('name')->all()
        );
    }

    public function test_a_role_grants_its_permissions_to_whoever_holds_it(): void
    {
        $this->actingAs($this->admin())->post(route('admin.roles.store'), [
            'name' => 'Cashier',
            'permissions' => ['dashboard.view', 'pos.view'],
        ]);

        $staff = User::factory()->admin()->create();
        $staff->assignRole('Cashier');

        $this->actingAs($staff->fresh())->get('/admin/pos')->assertOk();
        $this->actingAs($staff->fresh())->get('/admin/products')->assertForbidden();
    }

    public function test_role_names_must_be_unique(): void
    {
        Role::create(['name' => 'Cashier', 'guard_name' => 'web']);

        $this->actingAs($this->admin())
            ->post(route('admin.roles.store'), ['name' => 'Cashier'])
            ->assertSessionHasErrors('name');

        $this->assertSame(1, Role::where('name', 'Cashier')->count());
    }

    public function test_the_super_admin_name_cannot_be_reused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.roles.store'), ['name' => AdminModules::SUPER_ADMIN])
            ->assertSessionHasErrors('name');
    }

    public function test_an_unknown_permission_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.roles.store'), [
                'name' => 'Sneaky',
                'permissions' => ['products.view', 'everything.always'],
            ])
            ->assertSessionHasErrors('permissions.1');

        $this->assertNull(Role::where('name', 'Sneaky')->first());
    }

    /* --------------------------------------------------------------- edit */

    public function test_editing_a_role_replaces_its_permissions(): void
    {
        $role = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        $role->syncPermissions(['pos.view', 'pos.create']);

        $this->actingAs($this->admin())
            ->put(route('admin.roles.update', $role), [
                'name' => 'Front Desk',
                'permissions' => ['orders.view'],
            ])
            ->assertRedirect(route('admin.roles.index'))
            ->assertSessionHasNoErrors();

        $role->refresh();

        $this->assertSame('Front Desk', $role->name);
        $this->assertSame(['orders.view'], $role->permissions->pluck('name')->all());
    }

    public function test_clearing_every_box_leaves_a_role_with_nothing(): void
    {
        $role = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        $role->syncPermissions(['pos.view']);

        $this->actingAs($this->admin())
            ->put(route('admin.roles.update', $role), ['name' => 'Cashier'])
            ->assertRedirect();

        $this->assertCount(0, $role->fresh()->permissions);
    }

    public function test_the_edit_form_comes_back_prefilled(): void
    {
        $role = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        $role->syncPermissions(['pos.view', 'orders.view']);

        $response = $this->actingAs($this->admin())->get(route('admin.roles.edit', $role));

        $response->assertOk();
        $this->assertEqualsCanonicalizing(['pos.view', 'orders.view'], $response->viewData('assigned'));
    }

    /* -------------------------------------------------------- super admin */

    public function test_the_super_admin_role_cannot_be_edited(): void
    {
        $role = Role::findByName(AdminModules::SUPER_ADMIN);

        $this->actingAs($this->admin())->get(route('admin.roles.edit', $role))->assertForbidden();
        $this->actingAs($this->admin())
            ->put(route('admin.roles.update', $role), ['name' => 'Nerfed'])
            ->assertForbidden();

        $this->assertSame(AdminModules::SUPER_ADMIN, $role->fresh()->name);
    }

    public function test_the_super_admin_role_cannot_be_deleted(): void
    {
        $role = Role::findByName(AdminModules::SUPER_ADMIN);

        $this->actingAs($this->admin())
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertModelExists($role);
    }

    /* ------------------------------------------------------------- delete */

    public function test_an_unused_role_can_be_deleted(): void
    {
        $role = Role::create(['name' => 'Temp', 'guard_name' => 'web']);

        $this->actingAs($this->admin())
            ->delete(route('admin.roles.destroy', $role))
            ->assertRedirect(route('admin.roles.index'));

        $this->assertModelMissing($role);
    }

    public function test_a_role_in_use_cannot_be_deleted(): void
    {
        $role = Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        User::factory()->admin()->create()->assignRole($role);

        $this->actingAs($this->admin())
            ->delete(route('admin.roles.destroy', $role))
            ->assertSessionHasErrors('role');

        $this->assertModelExists($role);
    }

    /* -------------------------------------------------------- permissions */

    public function test_managing_roles_needs_its_own_permission(): void
    {
        $staff = User::factory()->admin()->create();

        // Managing users is not the same as handing out permissions.
        $staff->syncPermissions(['users.view', 'users.create']);
        $this->actingAs($staff->fresh())->get(route('admin.roles.index'))->assertForbidden();

        $staff->syncPermissions(['roles.view']);
        $this->actingAs($staff->fresh())->get(route('admin.roles.index'))->assertOk();
        $this->actingAs($staff->fresh())->get(route('admin.roles.create'))->assertForbidden();
    }

    public function test_the_sidebar_lists_roles_only_with_the_permission(): void
    {
        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['dashboard.view', 'users.view']);

        $html = $this->actingAs($staff->fresh())->get('/admin')->getContent();

        $this->assertStringContainsString(route('admin.users.index'), $html);
        $this->assertStringNotContainsString(route('admin.roles.index'), $html);
    }
}
