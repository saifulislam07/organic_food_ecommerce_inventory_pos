<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * All four error pages now share one Bootstrap layout — 403 and 505 used to be
 * Tailwind while 404 and 500 were standalone Bootstrap documents.
 */
class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public static function errorPages(): array
    {
        return [
            '403' => ['errors.403', '403', 'Access Denied'],
            '404' => ['errors.404', '404', "We couldn't find that page"],
            '500' => ['errors.500', '500', 'Something went wrong on our side'],
            '505' => ['errors.505', '505', 'Protocol Not Supported'],
        ];
    }

    #[DataProvider('errorPages')]
    public function test_error_page_renders_through_the_shared_layout(string $view, string $code, string $heading): void
    {
        // @yield escapes, so decode before matching copy that contains an apostrophe.
        $html = html_entity_decode(view($view)->render(), ENT_QUOTES, 'UTF-8');

        $this->assertStringContainsString($code, $html);
        $this->assertStringContainsString($heading, $html);
        $this->assertStringContainsString('error-container', $html, 'Should use the shared layout markup.');
        $this->assertStringContainsString('bootstrap@5.3.3', $html);
    }

    public static function errorViews(): array
    {
        return array_map(fn (array $case) => [$case[0]], self::errorPages());
    }

    #[DataProvider('errorViews')]
    public function test_error_page_ships_no_tailwind_or_vite_assets(string $view): void
    {
        $html = view($view)->render();

        $this->assertStringNotContainsString('resources/css/app.css', $html);
        $this->assertStringNotContainsString('build/assets/app-', $html);
        $this->assertStringNotContainsString('dark:bg-gray', $html, 'Tailwind utility classes should be gone.');
    }

    public function test_the_500_page_offers_a_retry_action(): void
    {
        $html = view('errors.500')->render();

        $this->assertStringContainsString('window.location.reload()', $html);
        $this->assertStringContainsString(route('contact'), $html);
    }

    public function test_a_customer_hitting_the_admin_panel_gets_the_403_page(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)
            ->get(route('admin.dashboard'))
            ->assertForbidden();
    }
}
