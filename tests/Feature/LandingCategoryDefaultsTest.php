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
 * The starting draft a category hands a new promotion, and the spec table a
 * gadget page argues with.
 *
 * The one rule worth guarding: a draft is copied on an explicit click, never
 * read at render time — editing a category must not rewrite the copy of a
 * campaign that has been running for a week.
 */
class LandingCategoryDefaultsTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function category(?array $defaults = null): Category
    {
        return Category::create([
            'name' => 'Gadgets',
            'slug' => 'gadgets-'.uniqid(),
            'theme' => 'gadget',
            'landing_defaults' => $defaults,
            'is_active' => true,
        ]);
    }

    private function page(array $attributes = []): LandingPage
    {
        $page = LandingPage::create(array_merge([
            'slug' => 'defaults-test',
            'internal_name' => 'Defaults test',
            'headline' => 'পাওয়ার ব্যাংক ২০০০০mAh',
            'selection_mode' => LandingPage::MODE_SINGLE,
            'delivery_mode' => 'global',
            'is_active' => true,
        ], $attributes));

        $product = Product::create([
            'category_id' => $this->category()->id,
            'name' => 'Power bank',
            'slug' => 'power-bank-'.uniqid(),
            'is_active' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '20000mAh',
            'price' => 2000,
            'stock' => 20,
        ]);

        LandingPageItem::create([
            'landing_page_id' => $page->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'offer_price' => 1800,
            'is_default' => true,
            'min_qty' => 1,
            'max_qty' => 5,
        ]);

        return $page->fresh('items');
    }

    /** The smallest body the campaign form accepts, plus what is under test. */
    private function formPayload(LandingPage $page, array $overrides = []): array
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

    /* --------------------------------------------------------------- specs */

    public function test_a_spec_table_renders_when_the_block_is_on(): void
    {
        $page = $this->page([
            'specs' => [
                ['label' => 'ওয়ারেন্টি', 'value' => '১ বছর'],
                ['label' => 'ক্যাপাসিটি', 'value' => '20000mAh'],
            ],
            'sections' => ['specs', 'delivery'],
        ]);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('স্পেসিফিকেশন')
            ->assertSee('ওয়ারেন্টি')
            ->assertSee('20000mAh');
    }

    public function test_the_spec_block_stays_off_unless_it_is_switched_on(): void
    {
        $page = $this->page([
            'specs' => [['label' => 'ওয়ারেন্টি', 'value' => '১ বছর']],
            'sections' => ['delivery'],
        ]);

        $this->get($page->url())->assertOk()->assertDontSee('ওয়ারেন্টি');
    }

    /** A row with no label is an untouched repeater line, not a spec. */
    public function test_blank_spec_rows_are_dropped_on_save(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())
            ->put('/admin/landing-pages/'.$page->id, $this->formPayload($page, [
                'specs' => [
                    ['label' => 'ওয়ারেন্টি', 'value' => '১ বছর'],
                    ['label' => '', 'value' => ''],
                    ['label' => '', 'value' => 'orphaned'],
                ],
            ]))
            ->assertRedirect();

        $this->assertSame(
            [['label' => 'ওয়ারেন্টি', 'value' => '১ বছর']],
            $page->refresh()->specs
        );
    }

    public function test_a_spec_may_have_a_label_and_no_value_yet(): void
    {
        $page = $this->page();

        $this->actingAs($this->admin())
            ->put('/admin/landing-pages/'.$page->id, $this->formPayload($page, [
                'specs' => [['label' => 'মডেল', 'value' => '']],
            ]))
            ->assertRedirect();

        $this->assertSame([['label' => 'মডেল', 'value' => '']], $page->refresh()->specs);
    }

    /* ------------------------------------------------------------ the draft */

    public function test_a_category_stores_the_draft_its_campaigns_open_with(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->put('/admin/categories/'.$category->id, [
                'name_en' => 'Gadgets',
                'name_bn' => 'গেজেট',
                'theme' => 'gadget',
                'sections' => ['features', 'specs', 'delivery'],
                'features' => ['১ বছর ওয়ারেন্টি', '', '৭ দিনে রিপ্লেসমেন্ট'],
                'faqs' => [['q' => 'ওয়ারেন্টি কীভাবে পাব?', 'a' => 'বক্সের কার্ড দিয়ে।']],
                'specs' => [['label' => 'ব্র্যান্ড', 'value' => '']],
                'cta_text' => 'এখনই অর্ডার করুন',
            ])
            ->assertRedirect();

        $draft = $category->fresh()->landingDefaults();

        $this->assertSame(['features', 'specs', 'delivery'], $draft['sections']);
        $this->assertSame(['১ বছর ওয়ারেন্টি', '৭ দিনে রিপ্লেসমেন্ট'], $draft['features']);
        $this->assertSame('ওয়ারেন্টি কীভাবে পাব?', $draft['faqs'][0]['q']);
        $this->assertSame('ব্র্যান্ড', $draft['specs'][0]['label']);
        $this->assertSame('এখনই অর্ডার করুন', $draft['cta_text']);
    }

    /**
     * The repeaters post flat field names because they are the campaign form's
     * component. None of those names may reach the categories table.
     */
    public function test_the_repeater_fields_never_land_as_columns(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->put('/admin/categories/'.$category->id, [
                'name_en' => 'Gadgets',
                'name_bn' => 'গেজেট',
                'features' => ['কিছু একটা'],
            ])
            ->assertRedirect();

        $row = (array) $category->fresh()->getAttributes();

        foreach (['sections', 'features', 'faqs', 'specs', 'cta_text'] as $field) {
            $this->assertArrayNotHasKey($field, $row);
        }
    }

    public function test_an_empty_draft_is_stored_as_null_not_an_empty_shell(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->put('/admin/categories/'.$category->id, [
                'name_en' => 'Gadgets',
                'name_bn' => 'গেজেট',
                'features' => ['', ''],
                'faqs' => [['q' => '', 'a' => '']],
            ])
            ->assertRedirect();

        $category->refresh();

        $this->assertNull($category->landing_defaults);
        $this->assertFalse($category->hasLandingDefaults());
    }

    public function test_a_block_that_is_not_a_block_is_rejected(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->put('/admin/categories/'.$category->id, [
                'name_en' => 'Gadgets',
                'name_bn' => 'গেজেট',
                'sections' => ['features', 'not-a-block'],
            ])
            ->assertSessionHasErrors('sections.1');
    }

    /* ---------------------------------------------------------- the handoff */

    public function test_the_campaign_form_carries_every_categorys_draft(): void
    {
        $this->category([
            'features' => ['১ বছর ওয়ারেন্টি'],
            'sections' => ['features', 'specs'],
        ]);

        $this->actingAs($this->admin())
            ->get('/admin/landing-pages/create')
            ->assertOk()
            ->assertSee('data-lp-apply-defaults', false)
            ->assertSee('১ বছর ওয়ারেন্টি', false);
    }

    /**
     * The three payloads the prefill button depends on must agree.
     *
     * The button reads the id list, the island reads the drafts and the CTA
     * comes from a third map — all three rendered by hand into data attributes,
     * which is exactly where a typo goes unnoticed until an admin clicks and
     * nothing happens.
     */
    public function test_the_prefill_payloads_agree_with_each_other(): void
    {
        $category = $this->category([
            'sections' => ['features', 'specs'],
            'features' => ['১ বছর ওয়ারেন্টি'],
            'specs' => [['label' => 'ব্র্যান্ড', 'value' => '']],
            'cta_text' => 'এখনই অর্ডার করুন',
        ]);

        $html = $this->actingAs($this->admin())
            ->get('/admin/landing-pages/create')
            ->assertOk()
            ->getContent();

        $attribute = function (string $pattern, string $what) use ($html) {
            preg_match($pattern, $html, $matches);

            $this->assertNotEmpty($matches, "No {$what} on the form.");

            return json_decode(html_entity_decode($matches[1], ENT_QUOTES), true, 512, JSON_THROW_ON_ERROR);
        };

        // The button's list of categories worth offering.
        $this->assertContains(
            $category->id,
            $attribute('/data-has-defaults="([^"]*)"/', 'data-has-defaults attribute')
        );

        // The CTA, which is a plain input outside the island.
        $this->assertSame(
            'এখনই অর্ডার করুন',
            $attribute('/data-ctas="([^"]*)"/', 'data-ctas attribute')[$category->id]
        );

        // And the draft the island itself will apply. Scoped to the content
        // island: the items repeater above it carries a data-props of its own.
        $draft = $attribute(
            '/data-vue="LandingContentBlocks"\s+data-props="([^"]*)"/',
            'content island'
        )['defaults'][$category->id];

        $this->assertSame(['features', 'specs'], $draft['sections']);
        $this->assertSame(['১ বছর ওয়ারেন্টি'], $draft['features']);
        $this->assertSame('ব্র্যান্ড', $draft['specs'][0]['label']);
    }

    /** A category with nothing filled in must not offer to fill anything in. */
    public function test_a_category_with_no_draft_is_not_offered(): void
    {
        $bare = $this->category();

        $html = $this->actingAs($this->admin())
            ->get('/admin/landing-pages/create')
            ->assertOk()
            ->getContent();

        preg_match('/data-has-defaults="([^"]*)"/', $html, $matches);

        $this->assertNotEmpty($matches, 'The prefill button has no list to work from.');
        $this->assertStringNotContainsString((string) $bare->id, html_entity_decode($matches[1]));
    }

    /**
     * The point of copying rather than referencing: a campaign that is already
     * running keeps the copy it was published with.
     */
    public function test_editing_a_category_leaves_a_running_campaign_alone(): void
    {
        $category = $this->category(['features' => ['পুরোনো পয়েন্ট']]);
        $page = $this->page([
            'category_id' => $category->id,
            'features' => ['পুরোনো পয়েন্ট'],
        ]);

        $category->update(['landing_defaults' => ['features' => ['নতুন পয়েন্ট']]]);

        $this->assertSame(['পুরোনো পয়েন্ট'], $page->fresh()->featureList());

        $this->get($page->url())->assertOk()->assertDontSee('নতুন পয়েন্ট');
    }

    public function test_the_category_form_offers_the_draft_editor(): void
    {
        $category = $this->category(['features' => ['১ বছর ওয়ারেন্টি']]);

        $this->actingAs($this->admin())
            ->get('/admin/categories/'.$category->id.'/edit')
            ->assertOk()
            ->assertSee('LandingContentBlocks', false)
            ->assertSee('withReviews', false)
            ->assertSee('১ বছর ওয়ারেন্টি', false);
    }

    public function test_every_block_the_admin_offers_has_a_view_behind_it(): void
    {
        foreach (array_keys(LandingPage::BLOCKS) as $key) {
            // The video block renders inline in the hero, not as its own file.
            if ($key === 'video') {
                continue;
            }

            $this->assertFileExists(
                resource_path("views/landing/blocks/{$key}.blade.php"),
                "Block '{$key}' is offered in the admin but has no view behind it."
            );
        }
    }
}
