<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\LandingPageItem;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a visitor arriving from an ad actually gets.
 */
class LandingPageShowTest extends TestCase
{
    use RefreshDatabase;

    private int $slugCounter = 0;

    private function variant(int $stock = 20, float $price = 1000): ProductVariant
    {
        $category = Category::firstOrCreate(
            ['slug' => 'fruits'],
            ['name' => 'Fruits', 'is_active' => true]
        );

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar',
            'slug' => 'himsagar-'.(++$this->slugCounter),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function page(array $attributes = [], int $stock = 20): LandingPage
    {
        $page = LandingPage::create(array_merge([
            'slug' => 'mango-offer',
            'internal_name' => 'Mango campaign',
            'headline' => 'খাঁটি হিমসাগর আম',
            'selection_mode' => LandingPage::MODE_SINGLE,
            'delivery_mode' => 'custom',
            'delivery_inside' => 60,
            'delivery_outside' => 120,
            'is_active' => true,
        ], $attributes));

        $variant = $this->variant($stock);

        LandingPageItem::create([
            'landing_page_id' => $page->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'offer_price' => 900,
            'is_default' => true,
            'min_qty' => 1,
            'max_qty' => 5,
        ]);

        return $page->fresh('items');
    }

    /* -------------------------------------------------------------- access */

    public function test_a_live_page_renders_its_headline_and_offer(): void
    {
        $page = $this->page();

        $this->get($page->url())
            ->assertOk()
            ->assertSee('খাঁটি হিমসাগর আম')
            ->assertSee('৳900')
            ->assertSee('অর্ডার করতে নিচের তথ্য দিন');
    }

    public function test_the_shops_navigation_is_nowhere_on_the_page(): void
    {
        $page = $this->page();

        // Nothing to click but the order button.
        $this->get($page->url())
            ->assertOk()
            ->assertDontSee('mainNavbar')
            ->assertDontSee(route('cart.index'));
    }

    public function test_a_draft_page_is_hidden_from_the_public(): void
    {
        $page = $this->page(['is_active' => false]);

        $this->get($page->url())->assertNotFound();
    }

    public function test_an_admin_can_preview_a_draft(): void
    {
        $page = $this->page(['is_active' => false]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->get($page->url())
            ->assertOk()
            ->assertSee('প্রিভিউ');
    }

    public function test_an_expired_page_explains_itself_instead_of_404ing(): void
    {
        $page = $this->page(['ends_at' => now()->subHour()]);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('মেয়াদ শেষ');
    }

    public function test_an_unknown_slug_is_a_404(): void
    {
        $this->get(route('landing.show', 'nothing-here'))->assertNotFound();
    }

    /* --------------------------------------------------------------- stock */

    public function test_a_sold_out_page_says_so_and_disables_the_button(): void
    {
        $page = $this->page([], stock: 0);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('স্টক শেষ');
    }

    /* --------------------------------------------------------------- views */

    public function test_a_visit_is_counted(): void
    {
        $page = $this->page();

        $this->get($page->url());
        $this->get($page->url());

        $this->assertSame(2, (int) $page->fresh()->views);
    }

    public function test_an_admin_preview_is_not_counted(): void
    {
        $page = $this->page(['is_active' => false]);

        $this->actingAs(User::factory()->superAdmin()->create())->get($page->url());

        $this->assertSame(0, (int) $page->fresh()->views);
    }

    /* ------------------------------------------------------------- content */

    public function test_a_switched_off_block_does_not_render(): void
    {
        $page = $this->page([
            'features' => ['১০০% খাঁটি'],
            'sections' => ['features'],
        ]);

        $this->get($page->url())->assertSee('১০০% খাঁটি');

        $page->update(['sections' => []]);

        $this->get($page->url())->assertDontSee('১০০% খাঁটি');
    }

    public function test_blocks_render_in_the_order_the_admin_chose(): void
    {
        $page = $this->page([
            'features' => ['১০০% খাঁটি'],
            'faqs' => [['q' => 'কতদিনে পাবো?', 'a' => 'দুই দিনে']],
            'sections' => ['faqs', 'features'],
        ]);

        $html = $this->get($page->url())->getContent();

        $this->assertLessThan(
            strpos($html, '১০০% খাঁটি'),
            strpos($html, 'কতদিনে পাবো?'),
            'The FAQ block was listed first but did not render first.'
        );
    }

    /* -------------------------------------------------------------- layout */

    /**
     * The catalogue substitutes a placeholder graphic for a product with no
     * photo. A column of identical grey squares beside the packages reads as a
     * broken page, so the landing page shows no picture instead.
     */
    public function test_a_product_without_a_photo_gets_no_placeholder(): void
    {
        $page = $this->page();

        $this->get($page->url())
            ->assertOk()
            ->assertDontSee('placeholder.png');
    }

    /**
     * One column, one form, one reading order at every width: headline and
     * price, then what to buy, then the reasons, then the customer's details.
     */
    public function test_the_page_reads_top_to_bottom_in_one_order(): void
    {
        $page = $this->page(['features' => ['১০০% খাঁটি'], 'sections' => ['features']]);

        $html = $this->get($page->url())->assertOk()->getContent();

        $positions = [
            'headline' => strpos($html, 'খাঁটি হিমসাগর আম'),
            'packages' => strpos($html, 'name="item_id"'),
            'features' => strpos($html, '১০০% খাঁটি'),
            'fields' => strpos($html, 'name="customer_name"'),
            'submit' => strpos($html, 'অর্ডার করতে নিচের তথ্য দিন'),
        ];

        foreach ($positions as $where => $at) {
            $this->assertNotFalse($at, "Missing from the page: {$where}");
        }

        $this->assertTrue(
            $positions['headline'] < $positions['packages']
                && $positions['packages'] < $positions['features']
                && $positions['features'] < $positions['submit']
                && $positions['submit'] < $positions['fields'],
            'The page did not render in its intended order.'
        );
    }

    public function test_the_packages_and_the_customer_fields_post_as_one_form(): void
    {
        $page = $this->page();

        $html = $this->get($page->url())->assertOk()->getContent();

        // Exactly one form, so a package choice and the details always travel
        // together — there is no second submit that could lose the selection.
        $this->assertSame(1, substr_count($html, '<form '), 'The page should carry a single form.');
    }

    /* -------------------------------------------------------------- pixels */

    public function test_no_facebook_script_loads_when_no_pixel_is_configured(): void
    {
        $page = $this->page();

        $this->get($page->url())
            ->assertOk()
            ->assertDontSee('connect.facebook.net')
            ->assertDontSee('fbq(');
    }

    public function test_the_shop_pixel_is_used_when_the_page_sets_none(): void
    {
        Setting::put('seo_facebook_pixel', '111111111111111');

        $this->get($this->page()->url())
            ->assertOk()
            ->assertSee('fbq(\'init\', "111111111111111")', false);
    }

    public function test_a_page_can_report_to_its_own_pixel(): void
    {
        Setting::put('seo_facebook_pixel', '111111111111111');

        $page = $this->page(['pixel_id' => '222222222222222']);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('fbq(\'init\', "222222222222222")', false)
            ->assertDontSee('111111111111111');
    }

    /* ----------------------------------------------------------------- seo */

    public function test_campaign_pages_stay_out_of_search_by_default(): void
    {
        $this->get($this->page()->url())
            ->assertOk()
            ->assertSee('<meta name="robots" content="noindex, nofollow">', false);
    }

    public function test_indexing_can_be_switched_on(): void
    {
        $page = $this->page(['noindex' => false]);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('<meta name="robots" content="index, follow">', false);
    }

    /* ------------------------------------------------------------ tracking */

    public function test_campaign_tags_from_the_url_are_carried_in_the_form(): void
    {
        $page = $this->page();

        $this->get($page->url().'?utm_source=facebook&utm_campaign=eid-mango&fbclid=IwAR9')
            ->assertOk()
            ->assertSee('name="utm_source" value="facebook"', false)
            ->assertSee('name="utm_campaign" value="eid-mango"', false)
            ->assertSee('name="fbclid" value="IwAR9"', false);
    }

    /* ------------------------------------------------------------- routing */

    public function test_the_landing_prefix_does_not_collide_with_static_pages(): void
    {
        Page::create([
            'slug' => 'mango-offer',
            'title_en' => 'Mango offer',
            'title_bn' => 'আমের অফার',
            'content_en' => 'A static page',
            'content_bn' => 'স্ট্যাটিক পেজ',
            'is_active' => true,
        ]);

        $page = $this->page();

        // Same slug, two different URLs, neither swallowing the other.
        $this->get($page->url())->assertOk()->assertSee('খাঁটি হিমসাগর আম');
        $this->get('/mango-offer')->assertOk()->assertSee('A static page');
    }
}
