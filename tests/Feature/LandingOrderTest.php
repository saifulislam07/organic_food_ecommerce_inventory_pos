<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\LandingPageItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A landing page is a public URL handed to strangers on Facebook. The order
 * form behind it takes money, moves stock, and trusts nothing it is sent.
 */
class LandingOrderTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
    }

    private int $slugCounter = 0;

    private function variant(string $name, int $stock, float $price = 1000): ProductVariant
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            // The same product name is used by several tests; the column is unique.
            'slug' => str($name)->slug()->value().'-'.(++$this->slugCounter),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function page(array $attributes = [], array $items = []): LandingPage
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

        foreach ($items ?: [['variant' => $this->variant('Himsagar', 50), 'offer_price' => 900]] as $row) {
            LandingPageItem::create([
                'landing_page_id' => $page->id,
                'product_id' => $row['variant']->product_id,
                'product_variant_id' => $row['variant']->id,
                'offer_price' => $row['offer_price'] ?? null,
                'is_default' => $row['is_default'] ?? true,
                'min_qty' => $row['min_qty'] ?? 1,
                'max_qty' => $row['max_qty'] ?? 5,
            ]);
        }

        return $page->fresh('items');
    }

    private function customer(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'করিম মিয়া',
            'customer_phone' => '01712345678',
            'customer_address' => 'মিরপুর ১০, ঢাকা',
            'customer_area' => 'dhaka_inside',
        ], $overrides);
    }

    /* ------------------------------------------------------------- placing */

    public function test_an_order_is_placed_from_the_page_without_a_cart(): void
    {
        $page = $this->page();
        $item = $page->items->first();

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $item->id,
            'quantity' => 2,
        ]))->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertSame('landing', $order->source);
        $this->assertSame($page->id, $order->landing_page_id);
        $this->assertSame('cod', $order->payment_method);
        // 2 x 900 + 60 delivery
        $this->assertEquals(1800, (float) $order->subtotal);
        $this->assertEquals(60, (float) $order->delivery_charge);
        $this->assertEquals(1860, (float) $order->total);
        $this->assertSame(2, $order->items->first()->quantity);
    }

    public function test_stock_is_taken_when_the_order_is_placed(): void
    {
        $variant = $this->variant('Himsagar', 10);
        $page = $this->page([], [['variant' => $variant, 'offer_price' => 900]]);

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
            'quantity' => 3,
        ]));

        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_an_order_larger_than_the_stock_is_refused(): void
    {
        $variant = $this->variant('Himsagar', 1);
        $page = $this->page([], [['variant' => $variant, 'offer_price' => 900, 'max_qty' => 5]]);

        $this->from($page->url())
            ->post(route('landing.order', $page->slug), $this->customer([
                'item_id' => $page->items->first()->id,
                'quantity' => 4,
            ]))
            ->assertRedirect($page->url())
            ->assertSessionHasErrors('order');

        $this->assertSame(0, Order::count());
        $this->assertSame(1, $variant->fresh()->stock);
    }

    /* ------------------------------------------------------------- pricing */

    public function test_a_price_posted_by_the_visitor_is_ignored(): void
    {
        $page = $this->page();

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
            'quantity' => 1,
            // Everything a tampered form could try to name its own price with.
            'price' => 1,
            'offer_price' => 1,
            'unit_price' => 1,
            'subtotal' => 1,
            'total' => 1,
            'delivery_charge' => 0,
        ]));

        $order = Order::firstOrFail();

        $this->assertEquals(900, (float) $order->subtotal);
        $this->assertEquals(960, (float) $order->total);
        $this->assertEquals(900, (float) $order->items->first()->unit_price);
    }

    public function test_a_quantity_above_the_pages_maximum_is_clamped(): void
    {
        $page = $this->page([], [[
            'variant' => $this->variant('Himsagar', 100),
            'offer_price' => 900,
            'max_qty' => 3,
        ]]);

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
            'quantity' => 99,
        ]));

        $this->assertSame(3, Order::firstOrFail()->items->first()->quantity);
    }

    public function test_an_item_from_another_page_cannot_be_ordered(): void
    {
        $page = $this->page();
        $other = $this->page(['slug' => 'other-offer', 'internal_name' => 'Other'], [[
            'variant' => $this->variant('Langra', 20),
            'offer_price' => 5,
        ]]);

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $other->items->first()->id,
            'quantity' => 1,
        ]));

        // Falls back to this page's own default rather than honouring the
        // cheaper item from somewhere else.
        $this->assertEquals(900, (float) Order::firstOrFail()->items->first()->unit_price);
    }

    public function test_a_bundle_charges_its_own_price_and_records_the_difference(): void
    {
        $page = $this->page([
            'selection_mode' => LandingPage::MODE_BUNDLE,
            'bundle_price' => 1500,
        ], [
            ['variant' => $this->variant('Himsagar', 20), 'offer_price' => 900],
            ['variant' => $this->variant('Langra', 20), 'offer_price' => 800, 'is_default' => false],
        ]);

        $this->post(route('landing.order', $page->slug), $this->customer());

        $order = Order::firstOrFail();

        $this->assertEquals(1700, (float) $order->subtotal);
        $this->assertEquals(200, (float) $order->discount_amount);
        $this->assertEquals(1560, (float) $order->total);
        $this->assertCount(2, $order->items);
    }

    public function test_several_items_can_be_ordered_with_their_own_quantities(): void
    {
        $page = $this->page(['selection_mode' => LandingPage::MODE_MULTI], [
            ['variant' => $this->variant('Himsagar', 20), 'offer_price' => 900],
            ['variant' => $this->variant('Langra', 20), 'offer_price' => 800, 'is_default' => false],
        ]);

        [$first, $second] = $page->items->all();

        $this->post(route('landing.order', $page->slug), $this->customer([
            'items' => [
                $first->id => ['qty' => 2],
                $second->id => ['qty' => 0],
            ],
        ]));

        $order = Order::firstOrFail();

        $this->assertCount(1, $order->items);
        $this->assertEquals(1800, (float) $order->subtotal);
    }

    public function test_delivery_follows_the_area_the_customer_picked(): void
    {
        $page = $this->page();

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
            'customer_area' => 'dhaka_outside',
        ]));

        $this->assertEquals(120, (float) Order::firstOrFail()->delivery_charge);
    }

    public function test_the_shops_free_delivery_threshold_applies_on_global_pages(): void
    {
        Setting::put('free_delivery_threshold', 1000);
        Setting::put('shipping_fee_inside', 60);

        $page = $this->page(['delivery_mode' => 'global', 'delivery_inside' => null]);

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
            'quantity' => 2,
        ]));

        $this->assertEquals(0, (float) Order::firstOrFail()->delivery_charge);
    }

    /* ---------------------------------------------------------- validation */

    public function test_the_form_complains_in_bengali_about_a_bad_phone_number(): void
    {
        $page = $this->page();

        $this->from($page->url())
            ->post(route('landing.order', $page->slug), $this->customer([
                'customer_phone' => '12345',
                'item_id' => $page->items->first()->id,
            ]))
            ->assertSessionHasErrors('customer_phone');

        $this->assertSame(0, Order::count());
    }

    public function test_a_phone_number_with_punctuation_is_accepted(): void
    {
        $page = $this->page();

        $this->post(route('landing.order', $page->slug), $this->customer([
            'customer_phone' => '+880 1712-345678',
            'item_id' => $page->items->first()->id,
        ]));

        $this->assertSame(1, Order::count());
    }

    public function test_a_filled_honeypot_is_turned_away(): void
    {
        $page = $this->page();

        $this->from($page->url())
            ->post(route('landing.order', $page->slug), $this->customer([
                'item_id' => $page->items->first()->id,
                'website' => 'http://spam.example',
            ]))
            ->assertSessionHasErrors('order');

        $this->assertSame(0, Order::count());
    }

    public function test_a_page_whose_offer_has_ended_takes_no_orders(): void
    {
        $page = $this->page(['ends_at' => now()->subDay()]);

        $this->from($page->url())
            ->post(route('landing.order', $page->slug), $this->customer([
                'item_id' => $page->items->first()->id,
            ]))
            ->assertSessionHasErrors('order');

        $this->assertSame(0, Order::count());
    }

    /* ------------------------------------------------------------ tracking */

    public function test_campaign_tags_ride_the_form_onto_the_order(): void
    {
        $page = $this->page();

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
            'utm_source' => 'facebook',
            'utm_medium' => 'cpc',
            'utm_campaign' => 'eid-mango',
            'fbclid' => 'IwAR123',
        ]));

        $order = Order::firstOrFail();

        $this->assertSame('facebook', $order->utm_source);
        $this->assertSame('cpc', $order->utm_medium);
        $this->assertSame('eid-mango', $order->utm_campaign);
        $this->assertSame('IwAR123', $order->fbclid);
    }

    /* ----------------------------------------------------------- thank you */

    public function test_the_thank_you_page_shows_the_order_and_fires_purchase(): void
    {
        Setting::put('seo_facebook_pixel', '123456789012345');

        $page = $this->page();

        $response = $this->followingRedirects()->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
        ]));

        $order = Order::firstOrFail();

        $response->assertOk()
            ->assertSee($order->order_number)
            ->assertSee("fbq('track', \"Purchase\"", false);
    }

    public function test_a_thank_you_page_cannot_show_another_pages_order(): void
    {
        $page = $this->page();
        $other = $this->page(['slug' => 'other-offer', 'internal_name' => 'Other']);

        $this->post(route('landing.order', $page->slug), $this->customer([
            'item_id' => $page->items->first()->id,
        ]));

        $order = Order::firstOrFail();

        $this->get(route('landing.thankyou', [$other->slug, $order->order_number]))->assertNotFound();
    }
}
