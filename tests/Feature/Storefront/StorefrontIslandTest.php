<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The storefront is Blade with Vue islands on top. These cover the handshake:
 * the runtime config block, the mount points, and the cart JSON the store reads.
 */
class StorefrontIslandTest extends TestCase
{
    use RefreshDatabase;

    private function product(int $variantCount = 1): Product
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango',
            'is_active' => true,
        ]);

        for ($i = 1; $i <= $variantCount; $i++) {
            ProductVariant::create([
                'product_id' => $product->id,
                'name' => "{$i} কেজি",
                'price' => 1000 * $i,
                'sale_price' => $i === 1 ? 900 : null,
                'stock' => 10,
                'sort_order' => $i,
            ]);
        }

        return $product->fresh('variants');
    }

    public function test_layout_publishes_the_cart_routes_the_store_needs(): void
    {
        $response = $this->get(route('shop'));

        $response->assertOk();

        $config = $this->jsonBlock($response->getContent(), 'storefront-config');

        $this->assertSame(route('cart.add'), $config['routes']['add']);
        $this->assertSame(route('cart.update'), $config['routes']['update']);
        $this->assertSame(route('cart.remove'), $config['routes']['remove']);
        $this->assertSame(route('cart.count'), $config['routes']['count']);
        $this->assertArrayHasKey('freeDeliveryThreshold', $config);
        $this->assertArrayHasKey('added', $config['strings']);
    }

    public function test_shop_page_mounts_the_cart_badge_and_toast_islands(): void
    {
        $response = $this->get(route('shop'));

        $response->assertOk();
        $response->assertSee('data-vue="CartBadge"', false);
        $response->assertSee('data-vue="CartToast"', false);
    }

    public function test_single_variant_card_mounts_an_add_to_cart_button(): void
    {
        $product = $this->product(1);

        $response = $this->get(route('shop'));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'AddToCartButton');

        $this->assertSame($product->id, $props['productId']);
        $this->assertSame($product->variants->first()->id, $props['variantId']);
    }

    public function test_product_page_mounts_the_purchase_island_with_every_variant(): void
    {
        $product = $this->product(2);

        $response = $this->get(route('product.show', $product->slug));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'ProductPurchase');

        $this->assertSame($product->id, $props['productId']);
        $this->assertCount(2, $props['variants']);
        $this->assertEquals(900, $props['variants'][0]['sale_price']);
        $this->assertNull($props['variants'][1]['sale_price']);
        $this->assertStringContainsString('{product}', $props['whatsappTemplate']);
        $this->assertArrayHasKey('addToCart', $props['labels']);
    }

    public function test_cart_page_hands_its_lines_to_the_vue_island(): void
    {
        $product = $this->product(1);
        $variant = $product->variants->first();

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk()->assertJson(['success' => true, 'cart_count' => 2]);

        $response = $this->get(route('cart.index'));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'CartPage');

        $key = "{$product->id}_{$variant->id}";
        $this->assertArrayHasKey($key, $props['items']);
        $this->assertSame(2, $props['items'][$key]['quantity']);
        $this->assertEquals(1800, $props['subtotal']);
        $this->assertSame(route('checkout'), $props['checkoutUrl']);
    }

    public function test_cart_mutations_return_the_lines_the_store_re_renders_from(): void
    {
        $product = $this->product(1);
        $variant = $product->variants->first();
        $key = "{$product->id}_{$variant->id}";

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->postJson(route('cart.update'), ['key' => $key, 'quantity' => 3])
            ->assertOk()
            ->assertJsonPath("items.{$key}.quantity", 3)
            ->assertJsonPath('cart_count', 3);

        // Removal must also return items, so the cart page can re-render without a reload.
        $removal = $this->postJson(route('cart.remove'), ['key' => $key]);

        $removal->assertOk()->assertJson(['success' => true]);
        $this->assertSame([], $removal->json('items'));
        $this->assertEquals(0, $removal->json('subtotal'));
    }

    public function test_checkout_page_mounts_the_form_island_with_fees_and_lines(): void
    {
        $product = $this->product(1);
        $variant = $product->variants->first();

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $response = $this->get(route('checkout'));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'CheckoutForm');

        $this->assertCount(1, $props['items']);
        $this->assertEquals(900, $props['subtotal']);
        $this->assertEquals(60, $props['feeInside']);
        $this->assertEquals(120, $props['feeOutside']);
        $this->assertEquals(2000, $props['freeDeliveryThreshold']);
        $this->assertCount(3, $props['pickupPoints']);
        $this->assertFalse($props['authenticated']);
        $this->assertSame([], $props['savedAddresses']);
        $this->assertArrayHasKey('placeOrder', $props['labels']);
    }

    public function test_checkout_still_posts_a_normal_form_and_creates_the_order(): void
    {
        $product = $this->product(1);
        $variant = $product->variants->first();

        $this->postJson(route('cart.add'), [
            'product_id' => $product->id,
            'variant_id' => $variant->id,
            'quantity' => 2,
        ])->assertOk();

        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Mirpur, Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', [
            'customer_name' => 'Rahim',
            'customer_area' => 'dhaka_inside',
            'status' => 'pending',
        ]);
    }

    /** Decode a <script type="application/json" id="..."> block. */
    private function jsonBlock(string $html, string $id): array
    {
        $pattern = '/<script type="application\/json" id="'.preg_quote($id, '/').'">(.*?)<\/script>/s';

        $this->assertMatchesRegularExpression($pattern, $html, "No #{$id} block found.");

        preg_match($pattern, $html, $matches);

        return json_decode($matches[1], true, 512, JSON_THROW_ON_ERROR);
    }

    /** Pull and decode the data-props blob for a given island out of the HTML. */
    private function propsFrom(string $html, string $component): array
    {
        $pattern = '/data-vue="'.preg_quote($component, '/').'"\s+data-props="([^"]*)"/';

        $this->assertMatchesRegularExpression($pattern, $html, "No {$component} island found.");

        preg_match($pattern, $html, $matches);

        return json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR);
    }
}
