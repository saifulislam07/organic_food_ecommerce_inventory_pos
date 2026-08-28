<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Support\SeoSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class SeoTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        SeoSettings::forget();
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function product(): Product
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango',
            'is_active' => true,
            'meta_title' => 'Buy Himsagar Mango Online',
            'meta_description' => 'Garden fresh Himsagar mangoes from Chapainawabganj.',
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => '3 কেজি',
            'price' => 1200,
            'stock' => 5,
        ]);

        return $product;
    }

    /* -------------------------------------------------------- meta tags */

    public function test_site_defaults_fill_the_head_when_a_page_sets_nothing(): void
    {
        SeoSettings::save([
            'seo_meta_title' => 'Mango Hut — Organic Bazaar',
            'seo_meta_description' => 'Pure and organic products delivered nationwide.',
            'seo_meta_keywords' => 'mango, ghee, honey',
        ]);

        // The cart page sets its own title but no description or keywords,
        // so those two fall through to the site defaults.
        $html = $this->get(route('cart.index'))->getContent();

        $this->assertStringContainsString('Pure and organic products delivered nationwide.', $html);
        $this->assertStringContainsString('name="keywords" content="mango, ghee, honey"', $html);
        $this->assertStringContainsString('og:site_name', $html);
    }

    public function test_a_page_overrides_the_site_default(): void
    {
        SeoSettings::save(['seo_meta_title' => 'Site Wide Title']);

        $product = $this->product();
        $html = $this->get(route('product.show', $product->slug))->getContent();

        $this->assertStringContainsString('Buy Himsagar Mango Online', $html);
        $this->assertStringNotContainsString('<title>Site Wide Title</title>', $html);
    }

    public function test_every_page_carries_a_canonical_url(): void
    {
        $html = $this->get(route('shop'))->getContent();

        $this->assertStringContainsString('<link rel="canonical" href="'.route('shop').'"', $html);
    }

    public function test_open_graph_and_twitter_tags_are_present(): void
    {
        SeoSettings::save(['seo_meta_description' => 'Organic bazaar']);

        $html = $this->get(route('shop'))->getContent();

        foreach (['og:type', 'og:site_name', 'og:url', 'og:title', 'og:description', 'twitter:card', 'twitter:title'] as $tag) {
            $this->assertStringContainsString($tag, $html, "{$tag} missing");
        }
    }

    public function test_the_share_image_switches_the_twitter_card_to_large(): void
    {
        $withoutImage = $this->get(route('shop'))->getContent();
        $this->assertStringContainsString('name="twitter:card" content="summary"', $withoutImage);

        SeoSettings::save(['seo_og_image' => 'seo/share.png']);

        $withImage = $this->get(route('shop'))->getContent();
        $this->assertStringContainsString('name="twitter:card" content="summary_large_image"', $withImage);
        $this->assertStringContainsString('storage/seo/share.png', $withImage);
    }

    /* ----------------------------------------------------------- robots */

    public function test_indexing_is_allowed_by_default(): void
    {
        $this->assertStringContainsString(
            'name="robots" content="index, follow"',
            $this->get(route('shop'))->getContent()
        );
    }

    public function test_the_shop_can_ask_search_engines_to_stay_away(): void
    {
        SeoSettings::save(['seo_robots' => 'noindex, nofollow']);

        $this->assertStringContainsString(
            'name="robots" content="noindex, nofollow"',
            $this->get(route('shop'))->getContent()
        );
    }

    /* -------------------------------------------------------- analytics */

    public function test_no_tracking_script_loads_until_an_id_is_saved(): void
    {
        $html = $this->get(route('shop'))->getContent();

        $this->assertStringNotContainsString('googletagmanager.com', $html);
    }

    public function test_the_analytics_script_loads_once_configured(): void
    {
        SeoSettings::save(['seo_google_analytics' => 'G-ABC1234567']);

        $html = $this->get(route('shop'))->getContent();

        $this->assertStringContainsString('googletagmanager.com/gtag/js?id=G-ABC1234567', $html);
        $this->assertStringContainsString("gtag('config', \"G-ABC1234567\")", $html);
    }

    public function test_the_verification_tag_is_rendered_when_set(): void
    {
        SeoSettings::save(['seo_google_site_verification' => 'abc123token']);

        $this->assertStringContainsString(
            'name="google-site-verification" content="abc123token"',
            $this->get(route('shop'))->getContent()
        );
    }

    /* ------------------------------------------------------------ admin */

    public function test_an_admin_can_save_seo_settings(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.seo.update'), [
                'seo_meta_title' => 'Mango Hut',
                'seo_meta_description' => 'Organic bazaar',
                'seo_google_analytics' => 'G-ABC1234567',
                'seo_robots' => 'index, follow',
            ])
            ->assertRedirect(route('admin.settings.seo.edit'))
            ->assertSessionHasNoErrors();

        SeoSettings::forget();
        $this->assertSame('G-ABC1234567', SeoSettings::analyticsId());
    }

    public function test_a_malformed_analytics_id_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.seo.update'), ['seo_google_analytics' => 'not-an-id'])
            ->assertSessionHasErrors('seo_google_analytics');
    }

    public function test_an_over_long_meta_title_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.seo.update'), ['seo_meta_title' => str_repeat('a', 80)])
            ->assertSessionHasErrors('seo_meta_title');
    }

    public function test_uploading_a_share_image_replaces_the_previous_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.settings.seo.update'), [
            'og_image' => UploadedFile::fake()->image('first.png'),
        ])->assertRedirect();

        SeoSettings::forget();
        $first = SeoSettings::get('seo_og_image');
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->admin())->post(route('admin.settings.seo.update'), [
            'og_image' => UploadedFile::fake()->image('second.png'),
        ])->assertRedirect();

        SeoSettings::forget();
        $second = SeoSettings::get('seo_og_image');

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
    }

    public function test_a_customer_cannot_change_seo_settings(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.settings.seo.edit'))
            ->assertForbidden();
    }

    /* ---------------------------------------------------------- sitemap */

    public function test_the_sitemap_lists_products_categories_and_pages(): void
    {
        $product = $this->product();

        Page::create([
            'slug' => 'about-us',
            'title_en' => 'About Us',
            'title_bn' => 'আমাদের সম্পর্কে',
            'content_en' => 'Hello',
            'content_bn' => 'হ্যালো',
            'is_active' => true,
        ]);

        $xml = $this->get('/sitemap.xml')
            ->assertOk()
            ->assertHeader('Content-Type', 'text/xml; charset=UTF-8')
            ->getContent();

        $this->assertStringContainsString(route('product.show', $product->slug), $xml);
        $this->assertStringContainsString(route('shop', ['category' => 'fruits']), $xml);
        $this->assertStringContainsString(route('pages.show', 'about-us'), $xml, 'CMS pages were missing from the sitemap.');
    }

    public function test_the_sitemap_leaves_out_inactive_pages(): void
    {
        Page::create([
            'slug' => 'draft-page',
            'title_en' => 'Draft',
            'title_bn' => 'খসড়া',
            'content_en' => 'x',
            'content_bn' => 'x',
            'is_active' => false,
        ]);

        $this->assertStringNotContainsString('draft-page', $this->get('/sitemap.xml')->getContent());
    }
}
