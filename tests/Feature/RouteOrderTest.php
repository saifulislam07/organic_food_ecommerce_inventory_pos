<?php

namespace Tests\Feature;

use App\Models\Page;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Request;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * pages.show is a root-level catch-all, so it has to stay the very last route.
 * Registered any earlier it matched /cart, /checkout and everything else first
 * and handed them to PageController, which 404s on the missing slug.
 */
class RouteOrderTest extends TestCase
{
    use RefreshDatabase;

    public static function reservedPaths(): array
    {
        return [
            'cart' => ['/cart', 'CartController'],
            'checkout' => ['/checkout', 'CheckoutController'],
            'shop' => ['/shop', 'ShopController'],
            'contact' => ['/contact', null],
            'login' => ['/login', 'AuthenticatedSessionController'],
            'register' => ['/register', 'RegisteredUserController'],
            'admin login' => ['/admin/login', 'AuthenticatedSessionController'],
            'sitemap' => ['/sitemap.xml', 'SitemapController'],
        ];
    }

    #[DataProvider('reservedPaths')]
    public function test_a_reserved_path_is_not_swallowed_by_the_page_catch_all(string $path, ?string $controller): void
    {
        $route = app('router')->getRoutes()->match(
            Request::create($path, 'GET')
        );

        $this->assertNotSame(
            'pages.show',
            $route->getName(),
            "{$path} is being matched by the page catch-all."
        );

        if ($controller) {
            $this->assertStringContainsString($controller, $route->getActionName());
        }
    }

    public function test_a_real_page_slug_still_resolves(): void
    {
        Page::create([
            'slug' => 'about-us',
            'title_en' => 'About Us',
            'title_bn' => 'আমাদের সম্পর্কে',
            'content_en' => 'Hello',
            'content_bn' => 'হ্যালো',
        ]);

        $this->get('/about-us')
            ->assertOk()
            ->assertSee('About Us');
    }

    public function test_an_unknown_slug_is_a_normal_404(): void
    {
        $this->get('/no-such-page')->assertNotFound();
    }

    public function test_the_catch_all_only_accepts_slug_shaped_paths(): void
    {
        // Uppercase and underscores are not slugs, so they must not reach PageController.
        $this->get('/Some_Path')->assertNotFound();
    }
}
