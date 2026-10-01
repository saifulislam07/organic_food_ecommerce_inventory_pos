<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\LandingPageItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Where a campaign page gets its colours from.
 *
 * The whole mechanism is one class on <body> plus a stylesheet of custom
 * properties. The shop has one theme — kids — so what is worth testing is that
 * every page wears it, whatever a page or category row still says from the
 * days there were more.
 */
class LandingThemeTest extends TestCase
{
    use RefreshDatabase;

    private function category(?string $theme): Category
    {
        return Category::create([
            'name' => 'Toys',
            'slug' => 'toys-'.uniqid(),
            'theme' => $theme,
            'is_active' => true,
        ]);
    }

    private function page(array $attributes = []): LandingPage
    {
        $page = LandingPage::create(array_merge([
            'slug' => 'theme-test',
            'internal_name' => 'Theme test',
            'headline' => 'বাচ্চাদের রঙিন বিল্ডিং ব্লক',
            'selection_mode' => LandingPage::MODE_SINGLE,
            'delivery_mode' => 'global',
            'is_active' => true,
        ], $attributes));

        $product = Product::create([
            'category_id' => $this->category(null)->id,
            'name' => 'Building blocks',
            'slug' => 'blocks-'.uniqid(),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '100 pcs',
            'price' => 1000,
            'stock' => 20,
        ]);

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

    /* --------------------------------------------------------- resolution */

    public function test_kids_is_the_only_theme_on_offer(): void
    {
        $this->assertSame(['kids'], array_keys(LandingPage::THEMES));
        $this->assertSame('kids', LandingPage::DEFAULT_THEME);
    }

    public function test_a_page_with_no_category_wears_the_kids_theme(): void
    {
        $this->assertSame('kids', $this->page()->themeKey());
    }

    public function test_a_category_nobody_themed_leaves_the_page_on_the_kids_theme(): void
    {
        $page = $this->page(['category_id' => $this->category(null)->id]);

        $this->assertSame('kids', $page->load('category')->themeKey());
    }

    /**
     * Pages and categories saved while there were mango, honey and gadget
     * themes must not render with no palette now those are gone.
     */
    public function test_a_retired_theme_on_the_page_falls_back_to_kids(): void
    {
        $page = $this->page();
        LandingPage::whereKey($page->id)->update(['theme' => 'gadget']);

        $this->assertSame('kids', $page->fresh()->themeKey());
    }

    public function test_a_retired_theme_on_the_category_falls_back_to_kids(): void
    {
        $page = $this->page(['category_id' => $this->category('honey')->id]);

        $this->assertSame('kids', $page->load('category')->themeKey());
    }

    /* ---------------------------------------------------------- rendering */

    public function test_the_theme_reaches_the_page_as_a_body_class(): void
    {
        $page = $this->page(['category_id' => $this->category('mango')->id]);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('lp-theme-kids', false)
            ->assertSee('css/landing-themes.css', false)
            ->assertDontSee('lp-theme-mango', false);
    }

    public function test_every_theme_offered_has_a_stylesheet_behind_it(): void
    {
        $css = file_get_contents(public_path('css/landing-themes.css'));

        foreach (array_keys(LandingPage::THEMES) as $key) {
            $this->assertStringContainsString(
                ".lp-theme-{$key} {",
                $css,
                "Theme '{$key}' is offered in the admin but has no CSS behind it."
            );
        }
    }

    /** The perks under the price are read off the page, never promised blind. */
    public function test_the_hero_perks_follow_the_pages_own_settings(): void
    {
        $page = $this->page(['payment_mode' => 'cod', 'delivery_mode' => 'free']);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('ফ্রি হোম ডেলিভারি')
            ->assertSee('ক্যাশ অন ডেলিভারি');

        // 0 switches the free-delivery bar off, so there is no offer to quote.
        $page->update(['payment_mode' => 'advance', 'delivery_mode' => 'global', 'free_delivery_over' => 0]);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('সারা দেশে হোম ডেলিভারি')
            ->assertDontSee('ফ্রি হোম ডেলিভারি')
            ->assertDontSee('💵 ক্যাশ অন ডেলিভারি');
    }

    /** Several items are picked with "এটা নিতে চাই", not a dropdown of numbers. */
    public function test_a_several_items_page_offers_an_add_button_per_product(): void
    {
        $page = $this->page(['selection_mode' => LandingPage::MODE_MULTI]);
        $item = $page->items->first();

        $this->get($page->url())
            ->assertOk()
            ->assertSee('এটা নিতে চাই')
            ->assertSee('data-pick-summary', false)
            ->assertSee('name="items['.$item->id.'][qty]" value="1"', false)
            ->assertDontSee('<select name="items[', false);
    }

    /**
     * Choose, see delivery, fill in details: one run, nothing in between,
     * wherever the admin put the delivery block in the list.
     */
    public function test_packages_delivery_and_form_come_one_after_another(): void
    {
        $page = $this->page([
            'selection_mode' => LandingPage::MODE_MULTI,
            'sections' => ['delivery', 'features', 'faqs'],
            'features' => ['নরম কাপড়'],
            'faqs' => [['q' => 'কতদিনে পাবো?', 'a' => '২-৩ দিনে।']],
        ]);

        $this->get($page->url())
            ->assertOk()
            ->assertSeeInOrder([
                'নরম কাপড়',
                'কতদিনে পাবো?',
                'যা যা নিতে চান বেছে নিন',
                'ডেলিভারি ও পেমেন্ট',
                'অর্ডার করতে নিচের তথ্য দিন',
            ])
            ->assertSee('href="#lp-buy"', false);
    }

    /* -------------------------------------------------------------- admin */

    public function test_the_campaign_form_saves_the_pages_own_free_delivery_bar(): void
    {
        $page = $this->page();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->get('/admin/landing-pages/create')
            ->assertSee('name="free_delivery_over"', false);

        $this->actingAs($admin)
            ->put('/admin/landing-pages/'.$page->id, $this->formPayload($page, ['free_delivery_over' => 1000]))
            ->assertSessionHasNoErrors();

        $this->assertEquals(1000, (float) $page->refresh()->free_delivery_over);

        // Emptied again, it goes back to following the shop.
        $this->actingAs($admin)
            ->put('/admin/landing-pages/'.$page->id, $this->formPayload($page, ['free_delivery_over' => '']))
            ->assertSessionHasNoErrors();

        $this->assertNull($page->refresh()->free_delivery_over);
    }

    /** One theme and no urgency tricks: none of these are on the form any more. */
    public function test_the_campaign_form_has_no_category_theme_or_urgency_fields(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/admin/landing-pages/create')
            ->assertOk()
            ->assertDontSee('ক্যাটাগরি ও থিম')
            ->assertDontSee('তাড়া তৈরি')
            ->assertDontSee('name="category_id"', false)
            ->assertDontSee('name="theme"', false)
            ->assertDontSee('name="countdown_ends_at"', false)
            ->assertDontSee('name="stock_note"', false);
    }

    public function test_the_category_form_no_longer_offers_a_theme(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/admin/categories/'.$this->category(null)->id.'/edit')
            ->assertOk()
            ->assertDontSee('data-cat-theme', false);
    }

    /** A hand-built request cannot set what the form no longer offers. */
    public function test_saving_ignores_a_posted_theme_category_or_urgency(): void
    {
        $page = $this->page();

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put('/admin/landing-pages/'.$page->id, $this->formPayload($page, [
                'category_id' => $this->category(null)->id,
                'theme' => 'gadget',
                'countdown_ends_at' => now()->addDay()->format('Y-m-d\TH:i'),
                'stock_note' => 'মাত্র ২ পিস বাকি',
            ]))
            ->assertSessionHasNoErrors()
            ->assertRedirect();

        $page->refresh();

        $this->assertNull($page->category_id);
        $this->assertNull($page->theme);
        $this->assertNull($page->countdown_ends_at);
        $this->assertNull($page->stock_note);
        $this->assertSame('kids', $page->themeKey());
    }

    /** Pages saved before the fields went must not keep showing them. */
    public function test_an_old_countdown_or_stock_note_is_not_shown(): void
    {
        $page = $this->page([
            'countdown_ends_at' => now()->addDay(),
            'stock_note' => 'মাত্র ২ পিস বাকি',
        ]);

        $this->get($page->url())
            ->assertOk()
            ->assertDontSee('data-countdown', false)
            ->assertDontSee('মাত্র ২ পিস বাকি');
    }

    public function test_a_category_still_rejects_a_retired_theme(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->put('/admin/categories/'.$this->category(null)->id, [
                'name_en' => 'Gadgets',
                'name_bn' => 'গেজেট',
                'theme' => 'gadget',
            ])
            ->assertSessionHasErrors('theme');
    }

    /**
     * Deleting a category is a catalogue decision. It may cost a campaign its
     * colours; it must never cost it its orders.
     */
    public function test_deleting_a_category_leaves_the_campaign_standing(): void
    {
        $category = $this->category('kids');
        $page = $this->page(['category_id' => $category->id]);

        $category->delete();
        $page->refresh()->load('category');

        $this->assertNull($page->category_id);
        $this->assertSame('kids', $page->themeKey());
        $this->get($page->url())->assertOk();
    }

    /** The smallest body the campaign form accepts, plus what is under test. */
    private function formPayload(LandingPage $page, array $overrides): array
    {
        $item = $page->items->first();

        return array_merge([
            'internal_name' => $page->internal_name,
            'headline' => $page->headline,
            'selection_mode' => LandingPage::MODE_SINGLE,
            'delivery_mode' => 'global',
            'payment_mode' => 'cod',
            'items' => [[
                'id' => $item->id,
                'product_variant_id' => $item->product_variant_id,
                'min_qty' => 1,
                'max_qty' => 5,
            ]],
        ], $overrides);
    }
}
