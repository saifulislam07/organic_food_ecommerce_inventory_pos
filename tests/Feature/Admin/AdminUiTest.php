<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Flash messages arrive as toasts and destructive forms open the panel's own
 * dialog, so no page relies on the browser's window.confirm any more.
 */
class AdminUiTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function flashFrom(string $html): array
    {
        preg_match('/<script type="application\/json" id="admin-flash">(.*?)<\/script>/s', $html, $matches);

        $this->assertNotEmpty($matches, 'No admin-flash block in the layout.');

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    /* ------------------------------------------------------------- toasts */

    public function test_the_dialog_island_is_on_every_admin_page(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('data-vue="AdminDialogs"', false);
    }

    public function test_a_success_message_is_handed_over_as_a_toast(): void
    {
        $this->actingAs($this->admin())->post(route('admin.units.store'), [
            'name' => 'Kilogram',
            'short_code' => 'kg',
        ]);

        $flash = $this->flashFrom($this->actingAs($this->admin())->get('/admin/units')->getContent());

        $this->assertSame('Unit created.', $flash['success']);
        $this->assertSame([], $flash['errors']);
    }

    public function test_validation_errors_are_handed_over_too(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.units.create'))
            ->post(route('admin.units.store'), ['name' => '', 'short_code' => '']);

        $flash = $this->flashFrom(
            $this->actingAs($this->admin())->get(route('admin.units.create'))->getContent()
        );

        $this->assertNotEmpty($flash['errors']);
    }

    public function test_a_quiet_page_carries_no_message(): void
    {
        $flash = $this->flashFrom($this->actingAs($this->admin())->get('/admin')->getContent());

        $this->assertNull($flash['success']);
        $this->assertSame([], $flash['errors']);
    }

    /* ------------------------------------------------------------ confirm */

    public function test_destructive_forms_ask_through_the_themed_dialog(): void
    {
        Category::create(['name' => 'Fruits', 'slug' => 'fruits']);

        $html = $this->actingAs($this->admin())->get('/admin/categories')->getContent();

        $this->assertStringContainsString('data-confirm="Delete this category?"', $html);
    }

    public function test_no_admin_page_falls_back_to_the_browser_dialog(): void
    {
        Category::create(['name' => 'Fruits', 'slug' => 'fruits']);

        foreach (['/admin/categories', '/admin/units', '/admin/suppliers', '/admin/users', '/admin/roles'] as $url) {
            $html = $this->actingAs($this->admin())->get($url)->getContent();

            $this->assertStringNotContainsString('return confirm(', $html, "window.confirm still used on {$url}");
        }
    }

    public function test_the_old_static_alert_bars_are_gone(): void
    {
        $this->actingAs($this->admin())->post(route('admin.units.store'), [
            'name' => 'Kilogram',
            'short_code' => 'kg',
        ]);

        $html = $this->actingAs($this->admin())->get('/admin/units')->getContent();

        $this->assertStringNotContainsString('alert alert-success alert-dismissible', $html);
    }
}
