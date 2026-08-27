<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The POS and Inventory screens are Blade shells that hand a JSON payload to a
 * Vue island. These cover the contract between the two: the mount point, the
 * prop names, and the shape of each row.
 */
class VueIslandTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
            'password' => 'password',
            'role' => 'admin',
        ]);
    }

    private function variant(array $overrides = []): ProductVariant
    {
        $category = Category::create([
            'name' => 'Fruits',
            'slug' => 'fruits',
        ]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango',
            'is_active' => true,
        ]);

        return ProductVariant::create(array_merge([
            'product_id' => $product->id,
            'name' => '3 কেজি',
            'price' => 1200,
            'stock' => 8,
            'sku' => 'MNG-3KG',
        ], $overrides));
    }

    public function test_pos_screen_renders_a_vue_mount_point_with_product_props(): void
    {
        $variant = $this->variant();

        $response = $this->actingAs($this->admin())->get(route('admin.pos.index'));

        $response->assertOk();
        $response->assertSee('data-vue="PosApp"', false);

        $props = $this->propsFrom($response->getContent(), 'PosApp');

        $this->assertSame(route('admin.pos.search'), $props['searchUrl']);
        $this->assertSame(route('admin.pos.store'), $props['storeUrl']);
        $this->assertCount(1, $props['items']);

        $item = $props['items'][0];
        $this->assertSame($variant->id, $item['id']);
        $this->assertSame('3 কেজি', $item['name']);
        $this->assertSame('Himsagar Mango', $item['product_name']);
        $this->assertEquals(1200, $item['price']);
        $this->assertSame(8, $item['stock']);
        $this->assertNotEmpty($item['image'], 'image_url must be serialised for the Vue grid');
    }

    public function test_pos_search_returns_the_same_row_shape_as_the_grid(): void
    {
        $variant = $this->variant(['sale_price' => 999]);

        $response = $this->actingAs($this->admin())
            ->getJson(route('admin.pos.search', ['q' => 'Himsagar']));

        $response->assertOk();
        $response->assertJsonCount(1);
        $response->assertJsonPath('0.id', $variant->id);
        $response->assertJsonPath('0.product_name', 'Himsagar Mango');
        $this->assertEquals(999, $response->json('0.price'));

        $this->assertNotEmpty($response->json('0.image'));
    }

    public function test_pos_search_also_matches_on_sku(): void
    {
        $this->variant();

        $this->actingAs($this->admin())
            ->getJson(route('admin.pos.search', ['q' => 'MNG-3KG']))
            ->assertOk()
            ->assertJsonCount(1);
    }

    public function test_inventory_screen_renders_a_vue_mount_point_with_row_props(): void
    {
        $variant = $this->variant(['stock' => 2]);

        $response = $this->actingAs($this->admin())->get(route('admin.inventory.index'));

        $response->assertOk();
        $response->assertSee('data-vue="InventoryTable"', false);
        $response->assertSee('1 Items', false); // low stock counter, rendered by Blade

        $props = $this->propsFrom($response->getContent(), 'InventoryTable');

        $this->assertSame(5, $props['lowStockThreshold']);
        $this->assertCount(1, $props['rows']);

        $row = $props['rows'][0];
        $this->assertSame($variant->id, $row['id']);
        $this->assertSame('Himsagar Mango', $row['product_name']);
        $this->assertSame('3 কেজি', $row['variant_name']);
        $this->assertSame(2, $row['stock']);
        $this->assertSame(route('admin.inventory.update', $variant), $row['update_url']);
    }

    public function test_stock_update_answers_json_for_the_vue_component(): void
    {
        $variant = $this->variant(['stock' => 2]);

        $this->actingAs($this->admin())
            ->patchJson(route('admin.inventory.update', $variant), ['stock' => 25])
            ->assertOk()
            ->assertJson(['success' => true, 'new_stock' => 25]);

        $this->assertSame(25, $variant->fresh()->stock);
    }

    public function test_product_form_renders_the_variant_repeater_with_existing_rows(): void
    {
        $variant = $this->variant(['weight_kg' => 3, 'sale_price' => 1100]);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.products.edit', $variant->product));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'VariantRepeater');

        $this->assertCount(1, $props['rows']);
        $this->assertSame('3 কেজি', $props['rows'][0]['name']);
        $this->assertEquals(1200, $props['rows'][0]['price']);
        $this->assertEquals(1100, $props['rows'][0]['sale_price']);
        $this->assertEquals(8, $props['rows'][0]['stock']);
    }

    public function test_product_form_repopulates_variant_rows_after_a_validation_error(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.products.create'))
            ->post(route('admin.products.store'), [
                'name_en' => '',  // trips validation
                'name_bn' => 'আম',
                'variants' => [
                    ['name' => '5 কেজি', 'price' => 500, 'stock' => 3],
                ],
            ])
            ->assertRedirect(route('admin.products.create'));

        $followUp = $this->actingAs($this->admin())->get(route('admin.products.create'));
        $props = $this->propsFrom($followUp->getContent(), 'VariantRepeater');

        $this->assertCount(1, $props['rows'], 'Old variant input should survive a failed submit.');
        $this->assertSame('5 কেজি', $props['rows'][0]['name']);
        $this->assertArrayHasKey('name_en', $props['errors']);
    }

    public function test_purchase_form_receives_suppliers_and_variant_options(): void
    {
        $variant = $this->variant();
        Supplier::create(['name' => 'Chapai Traders', 'phone' => '01700000000']);

        $response = $this->actingAs($this->admin())->get(route('admin.purchases.create'));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'PurchaseForm');

        $this->assertSame('Chapai Traders', $props['suppliers'][0]['name']);
        $this->assertSame($variant->id, $props['variants'][0]['id']);
        $this->assertSame('Himsagar Mango', $props['variants'][0]['product_name']);
        $this->assertSame(8, $props['variants'][0]['stock']);
        $this->assertArrayHasKey('cost_price', $props['variants'][0]);
        $this->assertSame(date('Y-m-d'), $props['today']);
    }

    public function test_adjustment_form_receives_variant_options(): void
    {
        $variant = $this->variant();

        $response = $this->actingAs($this->admin())->get(route('admin.adjustments.create'));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'AdjustmentForm');

        $this->assertSame($variant->id, $props['variants'][0]['id']);
        $this->assertSame('3 কেজি', $props['variants'][0]['variant_name']);
    }

    public function test_order_screen_renders_the_status_control(): void
    {
        $order = Order::create([
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'subtotal' => 1200,
            'discount_amount' => 0,
            'delivery_charge' => 60,
            'total' => 1260,
            'status' => 'pending',
            'payment_method' => 'cod',
            'source' => 'web',
        ]);

        $response = $this->actingAs($this->admin())->get(route('admin.orders.show', $order));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'OrderStatusControl');

        $this->assertSame('pending', $props['current']);
        $this->assertSame(route('admin.orders.updateStatus', $order), $props['updateUrl']);

        $this->actingAs($this->admin())
            ->patchJson($props['updateUrl'], ['status' => 'shipped'])
            ->assertOk()
            ->assertJson(['success' => true, 'status' => 'shipped']);

        $this->assertSame('shipped', $order->fresh()->status);
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
