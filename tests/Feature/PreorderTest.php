<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Notifications\PreorderStockArrived;
use App\Support\Preorder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Taking money for something that is not on the shelf.
 *
 * The dangerous parts are all invisible from a page that renders: a pre-order
 * that deducts stock drives the count negative and misreports every figure in
 * the panel; a pre-order button on a product the admin never marked sells a
 * delivery date nobody promised; and terms that are not recorded at the time
 * are no terms at all once the note is edited.
 */
class PreorderTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flush();
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function shopWideNote(string $note = 'Delivery takes 7-15 days.'): void
    {
        Setting::updateOrCreate(
            ['key' => Preorder::SETTING_KEY],
            ['value_en' => $note, 'value_bn' => $note, 'type' => 'textarea']
        );

        Setting::flush();
    }

    /** @param array<string, mixed> $attributes */
    private function product(int $stock, array $attributes = []): Product
    {
        $category = Category::firstOrCreate(
            ['slug' => 'baby-care'],
            ['name' => 'Baby Care', 'is_active' => true]
        );

        $product = Product::create(array_merge([
            'category_id' => $category->id,
            'name' => 'Cotton Onesie',
            'slug' => 'cotton-onesie-'.uniqid(),
            'is_active' => true,
        ], $attributes));

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Standard',
            'sku' => 'SKU-'.uniqid(),
            'price' => 500,
            'cost_price' => 300,
            'stock' => $stock,
        ]);

        return $product->fresh('variants');
    }

    private function addToCart(Product $product): void
    {
        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $product->variants->first()->id,
            'quantity' => 2,
        ])->assertOk();
    }

    /** @param array<string, mixed> $extra */
    private function checkout(array $extra = [])
    {
        return $this->post(route('checkout.store'), array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Mirpur, Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ], $extra));
    }

    /* ------------------------------------------------ what may be pre-ordered */

    public function test_running_out_of_stock_does_not_open_pre_orders_on_its_own(): void
    {
        $this->shopWideNote();
        $product = $this->product(0);

        $this->assertFalse($product->is_preorderable, 'The admin never marked this product');
        $this->assertFalse($product->allowsPreorderOf($product->variants->first()));
    }

    public function test_a_marked_product_still_sells_normally_while_it_has_stock(): void
    {
        $this->shopWideNote();
        $product = $this->product(5, ['is_preorder' => true]);

        $this->assertFalse(
            $product->allowsPreorderOf($product->variants->first()),
            'There is stock, so this is an ordinary sale'
        );
    }

    public function test_a_marked_product_that_is_out_of_stock_may_be_pre_ordered(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);

        $this->assertTrue($product->is_preorderable);
        $this->assertTrue($product->allowsPreorderOf($product->variants->first()));
    }

    /** With nothing to agree to, the button would open an empty dialog. */
    public function test_with_no_terms_written_anywhere_there_is_no_pre_order(): void
    {
        $product = $this->product(0, ['is_preorder' => true]);

        $this->assertFalse($product->is_preorderable);
        $this->assertNull($product->preorderNote());
    }

    public function test_a_products_own_note_beats_the_shop_wide_one(): void
    {
        $this->shopWideNote('Shop-wide terms.');
        $product = $this->product(0, [
            'is_preorder' => true,
            'preorder_note_en' => 'This one takes a month.',
        ]);

        $this->assertSame('This one takes a month.', $product->preorderNote());
    }

    public function test_a_product_with_no_note_of_its_own_falls_back_to_the_shop(): void
    {
        $this->shopWideNote('Shop-wide terms.');
        $product = $this->product(0, ['is_preorder' => true]);

        $this->assertSame('Shop-wide terms.', $product->preorderNote());
    }

    /* ------------------------------------------------------------- storefront */

    public function test_the_product_page_offers_the_pre_order_and_shows_its_terms(): void
    {
        $this->shopWideNote('Delivery takes 7-15 days.');
        $product = $this->product(0, ['is_preorder' => true]);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('&quot;preorderEnabled&quot;:true', false)
            ->assertSee('Delivery takes 7-15 days.');
    }

    public function test_a_sold_out_product_nobody_marked_offers_nothing(): void
    {
        $this->shopWideNote();
        $product = $this->product(0);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('&quot;preorderEnabled&quot;:false', false);
    }

    /* ------------------------------------------------------------------ cart */

    public function test_a_sold_out_line_is_marked_as_a_pre_order_in_the_cart(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);

        $items = $this->get(route('cart.index'))->viewData('items');

        $this->assertTrue(reset($items)['is_preorder']);
    }

    public function test_an_in_stock_line_is_not(): void
    {
        $this->shopWideNote();
        $product = $this->product(5, ['is_preorder' => true]);

        $this->addToCart($product);

        $items = $this->get(route('cart.index'))->viewData('items');

        $this->assertFalse(reset($items)['is_preorder']);
    }

    /**
     * Carts sit open for days. Something that was a pre-order on Monday is an
     * ordinary line once the delivery lands, and the cart has to notice.
     */
    public function test_a_line_stops_being_a_pre_order_once_the_stock_arrives(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);
        $product->variants->first()->update(['stock' => 10]);

        $items = $this->get(route('cart.index'))->viewData('items');

        $this->assertFalse(reset($items)['is_preorder']);
    }

    /* -------------------------------------------------------------- checkout */

    public function test_the_order_is_refused_until_the_conditions_are_accepted(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);

        $this->checkout()->assertSessionHasErrors('preorder_accept');
        $this->assertSame(0, Order::count());
    }

    public function test_accepting_the_conditions_places_the_order(): void
    {
        $this->shopWideNote('Delivery takes 7-15 days.');
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        $order = Order::firstOrFail();

        $this->assertTrue($order->has_preorder);
        $this->assertSame('Delivery takes 7-15 days.', $order->preorder_terms);
        $this->assertNotNull($order->preorder_accepted_at);
        $this->assertTrue($order->items->first()->is_preorder);
    }

    /**
     * The one that matters: nothing was on the shelf, so nothing may come off
     * it. A negative count would misreport stock everywhere in the panel.
     */
    public function test_a_pre_order_takes_no_stock(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);
        $variant = $product->variants->first();

        $this->addToCart($product);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        $this->assertSame(0, (int) $variant->fresh()->stock, 'Stock must not go negative');
    }

    /** An ordinary order is untouched by any of this. */
    public function test_an_in_stock_order_still_deducts_as_before(): void
    {
        $this->shopWideNote();
        $product = $this->product(5, ['is_preorder' => true]);
        $variant = $product->variants->first();

        $this->addToCart($product);
        $this->checkout()->assertRedirect();

        $this->assertSame(3, (int) $variant->fresh()->stock);
        $this->assertFalse(Order::firstOrFail()->has_preorder);
    }

    /** One order, one line off the shelf and one still coming. */
    public function test_a_mixed_cart_deducts_only_the_line_it_can(): void
    {
        $this->shopWideNote();
        $onShelf = $this->product(5, ['name' => 'In stock']);
        $coming = $this->product(0, ['is_preorder' => true, 'name' => 'Sold out']);

        $this->addToCart($onShelf);
        $this->addToCart($coming);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        $this->assertSame(3, (int) $onShelf->variants->first()->fresh()->stock);
        $this->assertSame(0, (int) $coming->variants->first()->fresh()->stock);

        $order = Order::firstOrFail();

        $this->assertTrue($order->has_preorder);
        $this->assertCount(1, $order->preorderItems()->get());
    }

    /**
     * The terms are copied, not referenced: editing the note afterwards must
     * not rewrite what this customer agreed to.
     */
    public function test_editing_the_note_later_does_not_change_a_placed_order(): void
    {
        $this->shopWideNote('Original terms.');
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        $this->shopWideNote('Rewritten terms.');

        $this->assertSame('Original terms.', Order::firstOrFail()->preorder_terms);
    }

    /* --------------------------------------------------------- stock arrival */

    public function test_the_shop_is_told_when_pre_ordered_stock_lands(): void
    {
        Notification::fake();

        $this->admin();
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);
        $variant = $product->variants->first();

        $this->addToCart($product);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        $variant->update(['stock' => 10]);

        Notification::assertSentTo($this->admin(), PreorderStockArrived::class);
    }

    /** Restocking a shelf that was never empty helps nobody who is waiting. */
    public function test_topping_up_a_shelf_that_was_not_empty_says_nothing(): void
    {
        Notification::fake();

        $this->admin();
        $this->shopWideNote();
        $product = $this->product(4, ['is_preorder' => true]);

        $product->variants->first()->update(['stock' => 9]);

        Notification::assertNothingSentTo($this->admin());
    }

    public function test_stock_arriving_with_nobody_waiting_says_nothing(): void
    {
        Notification::fake();

        $this->admin();
        $product = $this->product(0);

        $product->variants->first()->update(['stock' => 10]);

        Notification::assertNothingSentTo($this->admin());
    }

    /* ------------------------------------------------------------ admin list */

    public function test_the_admin_can_filter_down_to_orders_still_waiting(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        $waiting = Order::firstOrFail();

        // A second, ordinary order that has nothing to wait for.
        $inStock = $this->product(5);
        $this->addToCart($inStock);
        $this->checkout()->assertRedirect();

        $listed = $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['preorder' => 'waiting']))
            ->viewData('orders');

        $this->assertCount(1, $listed);
        $this->assertSame($waiting->id, $listed->first()->id);
    }

    /** Delivered or cancelled is not something to chase any more. */
    public function test_a_closed_pre_order_drops_out_of_the_waiting_queue(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        Order::firstOrFail()->update(['status' => 'delivered']);

        $listed = $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['preorder' => 'waiting']))
            ->viewData('orders');

        $this->assertCount(0, $listed);
    }

    public function test_the_orders_page_counts_what_is_waiting(): void
    {
        $this->shopWideNote();
        $product = $this->product(0, ['is_preorder' => true]);

        $this->addToCart($product);
        $this->checkout(['preorder_accept' => '1'])->assertRedirect();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index'))
            ->assertOk()
            ->assertViewHas('awaitingPreorder', 1)
            ->assertSee('waiting on pre-ordered stock', false);
    }
}
