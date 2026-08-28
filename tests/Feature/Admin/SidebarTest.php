<?php

namespace Tests\Feature\Admin;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The sidebar is position:fixed, so it does not scroll with the page. Once the
 * menu grew past a laptop viewport the bottom entries — Site Settings, Email /
 * SMTP, SMS Gateway, Logout — were clipped away with no way to reach them.
 * The groups below keep the list short; scrolling covers the rest.
 */
class SidebarTest extends TestCase
{
    use RefreshDatabase;

    private function sidebar(string $url = '/admin'): string
    {
        return $this->actingAs(User::factory()->superAdmin()->create())
            ->get($url)
            ->getContent();
    }

    private function groupIsOpen(string $html, string $key): bool
    {
        preg_match('/<ul class="collapse nav-children ([^"]*)" id="nav-'.$key.'"/', $html, $matches);

        $this->assertNotEmpty($matches, "No collapsible group called \"{$key}\" in the sidebar.");

        return str_contains($matches[1], 'show');
    }

    /* --------------------------------------------------------- reachable */

    public function test_the_sidebar_can_scroll_when_the_menu_outgrows_the_screen(): void
    {
        $html = $this->sidebar();

        $this->assertMatchesRegularExpression(
            '/\.admin-sidebar\s*\{[^}]*overflow-y:\s*auto/s',
            $html,
            'Without overflow-y the bottom menu items become unreachable.'
        );

        $this->assertMatchesRegularExpression(
            '/\.admin-sidebar\s*\{[^}]*height:\s*100vh/s',
            $html,
            'min-height would let the element grow instead of scrolling.'
        );
    }

    public function test_every_admin_section_is_linked_from_the_sidebar(): void
    {
        $html = $this->sidebar();

        $expected = [
            route('admin.dashboard'),
            route('admin.products.index'),
            route('admin.categories.index'),
            route('admin.units.index'),
            route('admin.orders.index'),
            route('admin.customers.index'),
            route('admin.pos.index'),
            route('admin.inventory.index'),
            route('admin.suppliers.index'),
            route('admin.purchases.index'),
            route('admin.adjustments.index'),
            route('admin.expenses.index'),
            route('admin.pages.index'),
            route('admin.settings.index'),
            route('admin.settings.mail.edit'),
            route('admin.settings.sms.edit'),
        ];

        foreach ($expected as $url) {
            $this->assertStringContainsString($url, $html, "Not linked in the sidebar: {$url}");
        }
    }

    public function test_the_settings_pages_are_separate_entries(): void
    {
        $html = $this->sidebar();

        foreach (['Site Settings', 'Email / SMTP', 'SMS Gateway'] as $label) {
            $this->assertStringContainsString($label, $html);
        }
    }

    /* ------------------------------------------------------------ groups */

    public static function groupPages(): array
    {
        return [
            'sell' => ['/admin/orders', 'sell'],
            'catalogue' => ['/admin/products', 'catalogue'],
            'stock' => ['/admin/inventory', 'stock'],
            'settings' => ['/admin/settings/sms', 'settings'],
        ];
    }

    #[DataProvider('groupPages')]
    public function test_the_group_you_are_inside_opens_by_itself(string $url, string $key): void
    {
        $this->assertTrue(
            $this->groupIsOpen($this->sidebar($url), $key),
            "Visiting {$url} should leave the \"{$key}\" group expanded."
        );
    }

    public function test_groups_you_are_not_in_stay_shut(): void
    {
        $html = $this->sidebar('/admin/orders');

        $this->assertTrue($this->groupIsOpen($html, 'sell'));
        $this->assertFalse($this->groupIsOpen($html, 'catalogue'));
        $this->assertFalse($this->groupIsOpen($html, 'stock'));
        $this->assertFalse($this->groupIsOpen($html, 'settings'));
    }

    public function test_the_dashboard_leaves_every_group_shut(): void
    {
        $html = $this->sidebar('/admin');

        foreach (['sell', 'catalogue', 'stock', 'settings'] as $key) {
            $this->assertFalse($this->groupIsOpen($html, $key), "Group {$key} should start collapsed.");
        }
    }

    public function test_a_child_page_marks_its_own_row_active(): void
    {
        $html = $this->sidebar('/admin/settings/sms');

        // The SMS row, not just its parent, should carry the active class.
        $this->assertMatchesRegularExpression(
            '/href="[^"]*\/admin\/settings\/sms"\s+class="[^"]*active/',
            $html
        );
    }
}
