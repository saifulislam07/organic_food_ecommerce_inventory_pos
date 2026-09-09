<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Coupon;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Services\CartService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Discount codes, and the one rule that governs them: a coupon and a product's
 * own offer never stack. Per unit, the larger cut wins outright.
 */
class CouponTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Delivery would otherwise ride on every total and hide the arithmetic.
        Setting::put('free_delivery_threshold', 0);
    }

    private function variant(string $name, float $price, ?float $sale = null, ?Category $category = null): ProductVariant
    {
        $category ??= Category::create(['name' => 'Fruits', 'slug' => 'fruits-'.uniqid(), 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $name,
            'slug' => \Illuminate\Support\Str::slug($name).'-'.uniqid(),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 কেজি',
            'price' => $price,
            'sale_price' => $sale,
            'stock' => 100,
        ]);
    }

    private function coupon(array $overrides = []): Coupon
    {
        return Coupon::create(array_merge([
            'code' => 'SAVE',
            'type' => Coupon::TYPE_PERCENT,
            'value' => 20,
            'applies_to' => 'all',
            'is_active' => true,
        ], $overrides));
    }

    private function addToCart(ProductVariant $variant, int $quantity = 1): void
    {
        $this->postJson(route('cart.add'), [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => $quantity,
        ])->assertOk();
    }

    private function cart(): CartService
    {
        // A fresh instance each time: the service memoises its pricing for the
        // life of a request, and a test spans several.
        return app()->make(CartService::class);
    }

    /* --------------------------------------------------------- the rule */

    public function test_a_coupon_that_beats_the_offer_replaces_it(): void
    {
        // Listed at 500, already reduced to 450. A 40% code takes 200 off, so
        // the shop's own 50 is dropped rather than added to.
        $variant = $this->variant('Mango', 500, 450);
        $this->coupon(['code' => 'BIG', 'value' => 40]);

        $this->addToCart($variant, 2);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'BIG'])
            ->assertOk()
            ->assertJson(['success' => true]);

        $cart = $this->cart();

        $this->assertSame(900.0, $cart->getSubtotal(), 'Subtotal stays at the offer price.');
        $this->assertSame(300.0, $cart->getDiscount(), 'The 150/unit the coupon adds beyond the offer.');
        $this->assertSame(600.0, $cart->getPayableSubtotal(), '2 × (500 − 200).');
    }

    public function test_a_weaker_coupon_leaves_the_offer_alone(): void
    {
        // 10% of 500 is 50, and the product is already 100 off. Nothing changes.
        $variant = $this->variant('Mango', 500, 400);
        $this->coupon(['code' => 'SMALL', 'value' => 10]);

        $this->addToCart($variant, 2);

        $this->postJson(route('cart.coupon.apply'), ['code' => 'SMALL'])
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'no_saving']);

        $cart = $this->cart();

        $this->assertSame(800.0, $cart->getSubtotal());
        $this->assertSame(0.0, $cart->getDiscount());
        $this->assertNull($cart->coupon(), 'A code that saves nothing is not left sitting on the cart.');
    }

    public function test_the_two_discounts_never_add_up(): void
    {
        $variant = $this->variant('Mango', 1000, 900);
        $this->coupon(['code' => 'HALF', 'value' => 50]);

        $this->addToCart($variant);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'HALF'])->assertOk();

        // Stacked would be 1000 − 100 − 500 = 400. The rule says 500.
        $this->assertSame(500.0, $this->cart()->getPayableSubtotal());
    }

    public function test_a_fixed_coupon_is_taken_off_each_unit(): void
    {
        $variant = $this->variant('Ghee', 800, 750);
        $this->coupon(['code' => 'FLAT', 'type' => Coupon::TYPE_FIXED, 'value' => 120]);

        $this->addToCart($variant, 3);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'FLAT'])->assertOk();

        // 120 a unit beats the 50 already off, so each unit costs 680.
        $this->assertSame(2040.0, $this->cart()->getPayableSubtotal());
    }

    public function test_a_fixed_coupon_never_drives_a_price_below_zero(): void
    {
        $variant = $this->variant('Honey', 150);
        $this->coupon(['code' => 'HUGE', 'type' => Coupon::TYPE_FIXED, 'value' => 200]);

        $this->addToCart($variant);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'HUGE'])->assertOk();

        $this->assertSame(0.0, $this->cart()->getPayableSubtotal());
    }

    public function test_a_percentage_cap_limits_what_comes_off_a_unit(): void
    {
        $variant = $this->variant('Mango', 1000);
        $this->coupon(['code' => 'CAPPED', 'value' => 50, 'max_discount' => 300]);

        $this->addToCart($variant, 2);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'CAPPED'])->assertOk();

        // 50% would be 500 a unit; the cap holds it to 300.
        $this->assertSame(1400.0, $this->cart()->getPayableSubtotal());
    }

    /* -------------------------------------------------------- the scope */

    public function test_a_category_coupon_only_touches_that_category(): void
    {
        $honeyCategory = Category::create(['name' => 'Honey', 'slug' => 'honey', 'is_active' => true]);
        $honey = $this->variant('Sundarban Honey', 500, null, $honeyCategory);
        $mango = $this->variant('Himsagar', 1000);

        $coupon = $this->coupon(['code' => 'HONEY20', 'applies_to' => 'categories', 'value' => 20]);
        $coupon->categories()->sync([$honeyCategory->id]);

        $this->addToCart($honey);
        $this->addToCart($mango);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'HONEY20'])->assertOk();

        // 20% of the honey only: 100 off, the mango untouched.
        $this->assertSame(100.0, $this->cart()->getDiscount());
    }

    public function test_a_product_coupon_only_touches_the_products_it_names(): void
    {
        $picked = $this->variant('Picked', 500);
        $other = $this->variant('Other', 500);

        $coupon = $this->coupon(['code' => 'ONEITEM', 'applies_to' => 'products', 'value' => 20]);
        $coupon->products()->sync([$picked->product_id]);

        $this->addToCart($picked);
        $this->addToCart($other);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'ONEITEM'])->assertOk();

        $this->assertSame(100.0, $this->cart()->getDiscount());
    }

    /* --------------------------------------------------- redeemability */

    public function test_an_unknown_code_is_refused(): void
    {
        $this->addToCart($this->variant('Mango', 500));

        $this->postJson(route('cart.coupon.apply'), ['code' => 'NOPE'])
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'not_found']);
    }

    public function test_the_code_is_matched_whatever_case_it_is_typed_in(): void
    {
        $this->variant('Mango', 500);
        $this->coupon(['code' => 'MixedCase']);

        $this->addToCart($this->variant('Ghee', 500));

        $this->postJson(route('cart.coupon.apply'), ['code' => ' mixedcase '])
            ->assertOk()
            ->assertJson(['success' => true]);
    }

    public function test_an_expired_code_is_refused(): void
    {
        $this->addToCart($this->variant('Mango', 500));
        $this->coupon(['code' => 'OLD', 'ends_at' => now()->subDay()]);

        $this->postJson(route('cart.coupon.apply'), ['code' => 'OLD'])
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'expired']);
    }

    public function test_a_code_that_has_not_started_is_refused(): void
    {
        $this->addToCart($this->variant('Mango', 500));
        $this->coupon(['code' => 'SOON', 'starts_at' => now()->addDay()]);

        $this->postJson(route('cart.coupon.apply'), ['code' => 'SOON'])
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'inactive']);
    }

    public function test_a_used_up_code_is_refused(): void
    {
        $this->addToCart($this->variant('Mango', 500));
        $this->coupon(['code' => 'GONE', 'usage_limit' => 2, 'used_count' => 2]);

        $this->postJson(route('cart.coupon.apply'), ['code' => 'GONE'])
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'exhausted']);
    }

    public function test_a_code_below_its_minimum_order_is_refused(): void
    {
        $this->addToCart($this->variant('Mango', 500));
        $this->coupon(['code' => 'BIGONLY', 'min_order_amount' => 2000]);

        $this->postJson(route('cart.coupon.apply'), ['code' => 'BIGONLY'])
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'min_order']);
    }

    public function test_a_code_that_expires_while_the_cart_sits_open_stops_counting(): void
    {
        $variant = $this->variant('Mango', 500);
        $coupon = $this->coupon(['code' => 'TICKING', 'value' => 20]);

        $this->addToCart($variant);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'TICKING'])->assertOk();
        $this->assertSame(100.0, $this->cart()->getDiscount());

        $coupon->update(['ends_at' => now()->subMinute()]);

        $cart = $this->cart();
        $this->assertNull($cart->coupon());
        $this->assertSame(0.0, $cart->getDiscount(), 'An expired code must not keep discounting.');
    }

    public function test_a_code_can_be_taken_off_again(): void
    {
        $variant = $this->variant('Mango', 500);
        $this->coupon(['code' => 'OFF', 'value' => 20]);

        $this->addToCart($variant);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'OFF'])->assertOk();
        $this->postJson(route('cart.coupon.remove'))->assertOk();

        $this->assertSame(0.0, $this->cart()->getDiscount());
    }

    /* ---------------------------------------------------------- the order */

    public function test_the_order_records_the_code_and_the_saving(): void
    {
        $variant = $this->variant('Mango', 500, 450);
        $coupon = $this->coupon(['code' => 'ORDER40', 'value' => 40]);

        $this->addToCart($variant, 2);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'ORDER40'])->assertOk();

        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        $order = Order::latest('id')->first();

        $this->assertSame('900.00', $order->subtotal, 'Line items stay at their offer price.');
        $this->assertSame('300.00', $order->discount_amount);
        $this->assertSame('600.00', $order->total);
        $this->assertSame('ORDER40', $order->coupon_code);
        $this->assertSame($coupon->id, $order->coupon_id);
        $this->assertSame(1, $coupon->fresh()->used_count);
    }

    public function test_a_deleted_coupon_leaves_its_orders_readable(): void
    {
        $variant = $this->variant('Mango', 500);
        $coupon = $this->coupon(['code' => 'TEMP', 'value' => 20]);

        $this->addToCart($variant);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'TEMP'])->assertOk();
        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        $coupon->delete();
        $order = Order::latest('id')->first()->fresh();

        $this->assertNull($order->coupon_id);
        $this->assertSame('TEMP', $order->coupon_code, 'The invoice still has to name the code.');
        $this->assertSame('100.00', $order->discount_amount);
    }

    public function test_the_cart_is_left_without_a_code_after_checkout(): void
    {
        $variant = $this->variant('Mango', 500);
        $this->coupon(['code' => 'ONCE', 'value' => 20]);

        $this->addToCart($variant);
        $this->postJson(route('cart.coupon.apply'), ['code' => 'ONCE'])->assertOk();
        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        $this->assertNull($this->cart()->coupon());
    }

    public function test_a_per_customer_limit_counts_that_customer_s_orders(): void
    {
        $user = User::factory()->create();
        $variant = $this->variant('Mango', 500);
        $coupon = $this->coupon(['code' => 'ONEPER', 'value' => 20, 'usage_limit_per_user' => 1]);

        Order::create([
            'user_id' => $user->id,
            'coupon_id' => $coupon->id,
            'coupon_code' => $coupon->code,
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'subtotal' => 500,
            'discount_amount' => 100,
            'delivery_charge' => 0,
            'total' => 400,
            'status' => 'pending',
            'payment_method' => 'cod',
            'source' => 'website',
        ]);

        $this->actingAs($user);
        $this->addToCart($variant);

        $this->postJson(route('cart.coupon.apply'), ['code' => 'ONEPER'])
            ->assertOk()
            ->assertJson(['success' => false, 'reason' => 'per_user']);
    }

    /* ---------------------------------------------------------- the panel */

    public function test_the_admin_can_create_a_coupon(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.coupons.store'), [
                'code' => 'eid25',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 25,
                'applies_to' => 'all',
                'is_active' => '1',
            ])->assertRedirect(route('admin.coupons.index'));

        $this->assertDatabaseHas('coupons', ['code' => 'EID25', 'value' => 25]);
    }

    public function test_a_duplicate_code_is_rejected_whatever_its_case(): void
    {
        $this->coupon(['code' => 'TAKEN']);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.coupons.store'), [
                'code' => 'taken',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 10,
                'applies_to' => 'all',
            ])->assertSessionHasErrors('code');
    }

    public function test_a_percentage_above_a_hundred_is_clamped(): void
    {
        $this->actingAs(User::factory()->superAdmin()->create())
            ->post(route('admin.coupons.store'), [
                'code' => 'MAD',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 250,
                'applies_to' => 'all',
            ])->assertRedirect();

        $this->assertSame('100.00', Coupon::where('code', 'MAD')->value('value'));
    }

    public function test_switching_a_coupon_back_to_every_product_clears_its_lists(): void
    {
        $category = Category::create(['name' => 'Honey', 'slug' => 'honey', 'is_active' => true]);
        $coupon = $this->coupon(['code' => 'SCOPED', 'applies_to' => 'categories']);
        $coupon->categories()->sync([$category->id]);

        $this->actingAs(User::factory()->superAdmin()->create())
            ->put(route('admin.coupons.update', $coupon), [
                'code' => 'SCOPED',
                'type' => Coupon::TYPE_PERCENT,
                'value' => 20,
                'applies_to' => 'all',
                'category_ids' => [$category->id],
            ])->assertRedirect();

        $this->assertCount(0, $coupon->fresh()->categories, 'A stale list must not survive the switch.');
    }

    public function test_the_admin_screens_render(): void
    {
        $coupon = $this->coupon(['code' => 'RENDER']);
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.coupons.index'))->assertOk()->assertSee('RENDER');
        $this->actingAs($admin)->get(route('admin.coupons.create'))->assertOk();
        $this->actingAs($admin)->get(route('admin.coupons.edit', $coupon))->assertOk();
    }

    public function test_a_guest_cannot_reach_the_panel(): void
    {
        $this->get(route('admin.coupons.index'))->assertRedirect(route('login'));
    }
}
