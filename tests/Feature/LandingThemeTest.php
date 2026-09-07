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
 * properties, so what is worth testing is the three-step fallback behind that
 * class name — and that an unknown theme lands on the brand rather than on a
 * page with no colours at all.
 */
class LandingThemeTest extends TestCase
{
    use RefreshDatabase;

    private function category(?string $theme): Category
    {
        return Category::create([
            'name' => 'Mangoes',
            'slug' => 'mangoes-'.uniqid(),
            'theme' => $theme,
            'is_active' => true,
        ]);
    }

    private function page(array $attributes = []): LandingPage
    {
        $page = LandingPage::create(array_merge([
            'slug' => 'theme-test',
            'internal_name' => 'Theme test',
            'headline' => 'খাঁটি হিমসাগর আম',
            'selection_mode' => LandingPage::MODE_SINGLE,
            'delivery_mode' => 'global',
            'is_active' => true,
        ], $attributes));

        $product = Product::create([
            'category_id' => $this->category('mango')->id,
            'name' => 'Himsagar',
            'slug' => 'himsagar-'.uniqid(),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
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

    public function test_a_page_with_no_category_wears_the_brand(): void
    {
        $this->assertSame('default', $this->page()->themeKey());
    }

    public function test_a_page_inherits_the_theme_of_its_category(): void
    {
        $page = $this->page(['category_id' => $this->category('honey')->id]);

        $this->assertSame('honey', $page->load('category')->themeKey());
    }

    public function test_the_pages_own_theme_overrides_the_inherited_one(): void
    {
        $page = $this->page([
            'category_id' => $this->category('honey')->id,
            'theme' => 'gadget',
        ]);

        $this->assertSame('gadget', $page->load('category')->themeKey());
    }

    public function test_a_category_nobody_themed_leaves_the_page_on_the_brand(): void
    {
        $page = $this->page(['category_id' => $this->category(null)->id]);

        $this->assertSame('default', $page->load('category')->themeKey());
    }

    /**
     * A theme deleted from the stylesheet, or a row edited by hand, must not
     * leave a live campaign rendering with no palette at all.
     */
    public function test_a_theme_that_no_longer_exists_falls_back_to_the_brand(): void
    {
        $page = $this->page();
        LandingPage::whereKey($page->id)->update(['theme' => 'no-such-theme']);

        $this->assertSame('default', $page->fresh()->themeKey());
    }

    /* ---------------------------------------------------------- rendering */

    public function test_the_theme_reaches_the_page_as_a_body_class(): void
    {
        $page = $this->page(['category_id' => $this->category('kids')->id]);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('lp-theme-kids', false)
            ->assertSee('css/landing-themes.css', false);
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

    /* -------------------------------------------------------------- admin */

    public function test_the_campaign_form_offers_a_category_and_a_theme(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->get('/admin/landing-pages/create')
            ->assertOk()
            ->assertSee('data-lp-category', false)
            ->assertSee('data-lp-theme', false);
    }

    public function test_a_campaign_saves_its_category_and_theme(): void
    {
        $page = $this->page();
        $category = $this->category('spice');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put('/admin/landing-pages/'.$page->id, $this->formPayload($page, [
                'category_id' => $category->id,
                'theme' => 'gadget',
            ]))
            ->assertRedirect();

        $page->refresh();

        $this->assertSame($category->id, $page->category_id);
        $this->assertSame('gadget', $page->theme);
    }

    /** Blank means "ask the category", and has to store as null to do that. */
    public function test_leaving_the_theme_blank_stores_null_not_an_empty_string(): void
    {
        $page = $this->page(['theme' => 'gadget']);
        $category = $this->category('spice');

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put('/admin/landing-pages/'.$page->id, $this->formPayload($page, [
                'category_id' => $category->id,
                'theme' => '',
            ]))
            ->assertRedirect();

        $page->refresh()->load('category');

        $this->assertNull($page->theme);
        $this->assertSame('spice', $page->themeKey());
    }

    public function test_a_category_saves_the_theme_its_campaigns_inherit(): void
    {
        $category = $this->category(null);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put('/admin/categories/'.$category->id, [
                'name_en' => 'Gadgets',
                'name_bn' => 'গেজেট',
                'theme' => 'gadget',
            ])
            ->assertRedirect();

        $this->assertSame('gadget', $category->fresh()->theme);
    }

    public function test_a_theme_the_admin_never_offered_is_rejected(): void
    {
        $category = $this->category(null);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put('/admin/categories/'.$category->id, [
                'name_en' => 'Gadgets',
                'name_bn' => 'গেজেট',
                'theme' => 'neon-hellscape',
            ])
            ->assertSessionHasErrors('theme');
    }

    /**
     * Deleting a category is a catalogue decision. It may cost a campaign its
     * colours; it must never cost it its orders.
     */
    public function test_deleting_a_category_leaves_the_campaign_standing(): void
    {
        $category = $this->category('honey');
        $page = $this->page(['category_id' => $category->id]);

        $category->delete();
        $page->refresh()->load('category');

        $this->assertNull($page->category_id);
        $this->assertSame('default', $page->themeKey());
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
