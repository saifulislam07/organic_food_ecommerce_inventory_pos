<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\LandingPageItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LandingPageCrudTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private int $slugCounter = 0;

    protected function setUp(): void
    {
        parent::setUp();

        // The rows the module permission guard checks against.
        $this->seed(PermissionSeeder::class);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function variant(float $price = 1000, int $stock = 20): ProductVariant
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

    /** The shape the form posts, with room to override any of it. */
    private function payload(array $overrides = [], ?array $items = null): array
    {
        return array_merge([
            'internal_name' => 'ঈদ আম ক্যাম্পেইন',
            'headline' => 'খাঁটি হিমসাগর আম',
            'selection_mode' => LandingPage::MODE_SINGLE,
            'delivery_mode' => 'global',
            'payment_mode' => 'cod',
            'is_active' => '1',
            'items' => $items ?? [
                ['product_variant_id' => $this->variant()->id, 'offer_price' => 900, 'min_qty' => 1, 'max_qty' => 5],
            ],
        ], $overrides);
    }

    /* -------------------------------------------------------------- create */

    public function test_a_page_is_created_with_its_items_and_a_generated_url(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.landing-pages.store'), $this->payload())
            ->assertRedirect();

        $page = LandingPage::firstOrFail();

        $this->assertSame('eed-am-kzampein', $page->slug);
        $this->assertTrue($page->is_active);
        $this->assertCount(1, $page->items);
        $this->assertEquals(900, (float) $page->items->first()->offer_price);
        // The first item is the one that opens selected.
        $this->assertTrue($page->items->first()->is_default);
    }

    public function test_a_chosen_url_is_kept(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.landing-pages.store'), $this->payload(['slug' => 'eid-mango']));

        $this->assertSame('eid-mango', LandingPage::firstOrFail()->slug);
    }

    public function test_a_page_needs_at_least_one_product(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.landing-pages.create'))
            ->post(route('admin.landing-pages.store'), $this->payload(items: []))
            ->assertSessionHasErrors('items');

        $this->assertSame(0, LandingPage::count());
    }

    public function test_the_same_product_cannot_be_added_twice(): void
    {
        $variant = $this->variant();

        $this->actingAs($this->admin())
            ->from(route('admin.landing-pages.create'))
            ->post(route('admin.landing-pages.store'), $this->payload(items: [
                ['product_variant_id' => $variant->id],
                ['product_variant_id' => $variant->id],
            ]))
            ->assertSessionHasErrors('items.1.product_variant_id');
    }

    public function test_a_bundle_cannot_cost_more_than_its_parts(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.landing-pages.create'))
            ->post(route('admin.landing-pages.store'), $this->payload([
                'selection_mode' => LandingPage::MODE_BUNDLE,
                'bundle_price' => 5000,
            ], items: [
                ['product_variant_id' => $this->variant()->id, 'offer_price' => 900, 'min_qty' => 1],
            ]))
            ->assertSessionHasErrors('bundle_price');
    }

    public function test_the_content_blocks_are_stored_the_way_they_are_read_back(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload([
            'sections' => ['faqs', 'features'],
            'features' => ['১০০% খাঁটি', '', '  '],
            'faqs' => [['q' => 'কতদিনে?', 'a' => 'দুই দিনে'], ['q' => '', 'a' => 'ignored']],
            'reviews' => [['name' => 'রাকিব', 'text' => 'দারুণ', 'rating' => 5], ['name' => 'x', 'text' => '']],
            'form_fields' => ['address', 'area'],
        ]));

        $page = LandingPage::firstOrFail();

        // Blank repeater rows are dropped rather than stored as empties.
        $this->assertSame(['১০০% খাঁটি'], $page->featureList());
        $this->assertCount(1, $page->faqList());
        $this->assertCount(1, $page->reviewList());
        $this->assertSame(['faqs', 'features'], $page->enabledSections());
        $this->assertTrue($page->asksFor('address'));
        $this->assertFalse($page->asksFor('note'));
    }

    /* -------------------------------------------------------------- update */

    public function test_editing_replaces_the_items_and_keeps_the_ids_of_the_ones_that_stay(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload());

        $page = LandingPage::firstOrFail();
        $kept = $page->items->first();
        $added = $this->variant(1200);

        $this->actingAs($this->admin())->put(route('admin.landing-pages.update', $page), $this->payload([
            'slug' => $page->slug,
        ], items: [
            ['id' => $kept->id, 'product_variant_id' => $kept->product_variant_id, 'offer_price' => 850],
            ['product_variant_id' => $added->id, 'offer_price' => 1100],
        ]));

        $page->refresh()->load('items');

        $this->assertCount(2, $page->items);
        $this->assertSame($kept->id, $page->items->first()->id);
        $this->assertEquals(850, (float) $page->items->first()->offer_price);
    }

    public function test_an_item_dropped_from_the_form_is_removed(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload(items: [
            ['product_variant_id' => $this->variant()->id],
            ['product_variant_id' => $this->variant()->id],
        ]));

        $page = LandingPage::firstOrFail();
        $keep = $page->items->first();

        $this->actingAs($this->admin())->put(route('admin.landing-pages.update', $page), $this->payload([
            'slug' => $page->slug,
        ], items: [
            ['id' => $keep->id, 'product_variant_id' => $keep->product_variant_id],
        ]));

        $this->assertSame(1, LandingPageItem::where('landing_page_id', $page->id)->count());
    }

    /* ----------------------------------------------------------- duplicate */

    public function test_duplicating_makes_a_draft_on_a_new_url_with_its_own_counters(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload([
            'slug' => 'eid-mango',
        ]));

        $original = LandingPage::firstOrFail();
        // Not fillable: a view counter has no business arriving from a form.
        $original->forceFill(['views' => 500])->save();

        $this->actingAs($this->admin())
            ->post(route('admin.landing-pages.duplicate', $original))
            ->assertRedirect();

        $copy = LandingPage::where('id', '!=', $original->id)->firstOrFail();

        $this->assertSame('eid-mango-copy', $copy->slug);
        $this->assertFalse($copy->is_active);
        $this->assertSame(0, (int) $copy->views);
        $this->assertCount(1, $copy->items);
        $this->assertSame(
            $original->items->first()->product_variant_id,
            $copy->items->first()->product_variant_id
        );
    }

    /* -------------------------------------------------------------- delete */

    public function test_a_page_with_no_orders_can_be_deleted(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload());

        $page = LandingPage::firstOrFail();

        $this->actingAs($this->admin())->delete(route('admin.landing-pages.destroy', $page));

        $this->assertSame(0, LandingPage::count());
    }

    public function test_a_page_that_took_orders_is_kept(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload());

        $page = LandingPage::firstOrFail();

        Order::create([
            'customer_name' => 'করিম',
            'customer_phone' => '01712345678',
            'customer_address' => 'ঢাকা',
            'subtotal' => 900,
            'total' => 960,
            'delivery_charge' => 60,
            'source' => 'landing',
            'landing_page_id' => $page->id,
        ]);

        $this->actingAs($this->admin())
            ->from(route('admin.landing-pages.index'))
            ->delete(route('admin.landing-pages.destroy', $page))
            ->assertSessionHasErrors('delete');

        $this->assertSame(1, LandingPage::count());
    }

    /* --------------------------------------------------------- permissions */

    public function test_the_screen_is_behind_its_own_permission(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);

        $this->actingAs($staff)->get(route('admin.landing-pages.index'))->assertForbidden();

        $staff->givePermissionTo('landing-pages.view');

        $this->actingAs($staff)->get(route('admin.landing-pages.index'))->assertOk();
    }

    public function test_viewing_does_not_allow_editing(): void
    {
        $staff = User::factory()->create(['role' => 'admin']);
        $staff->givePermissionTo('landing-pages.view');

        $this->actingAs($staff)->get(route('admin.landing-pages.create'))->assertForbidden();
    }

    /* -------------------------------------------------------------- screens */

    public function test_the_list_shows_the_url_and_the_conversion_rate(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload([
            'slug' => 'eid-mango',
        ]));

        LandingPage::firstOrFail()->forceFill(['views' => 100])->save();

        $this->actingAs($this->admin())
            ->get(route('admin.landing-pages.index'))
            ->assertOk()
            ->assertSee('ঈদ আম ক্যাম্পেইন')
            ->assertSee('/lp/eid-mango')
            ->assertSee('0.0%');
    }

    public function test_the_edit_screen_renders_with_its_items(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload());

        $page = LandingPage::firstOrFail();

        $this->actingAs($this->admin())
            ->get(route('admin.landing-pages.edit', $page))
            ->assertOk()
            ->assertSee('data-vue="LandingItems"', false)
            ->assertSee('data-vue="LandingContentBlocks"', false);
    }
}
