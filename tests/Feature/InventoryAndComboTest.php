<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\InventoryService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class InventoryAndComboTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
    }

    private function variant(string $name, int $stock, float $price = 1000): ProductVariant
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 unit',
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function combo(string $name, array $components, float $price = 1800): ProductVariant
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'is_active' => true,
            'is_combo' => true,
        ]);

        $combo = ProductVariant::create([
            'product_id' => $product->id,
            'name' => 'Bundle',
            'price' => $price,
            'stock' => 0,
        ]);

        foreach ($components as [$component, $quantity]) {
            ComboItem::create([
                'combo_variant_id' => $combo->id,
                'component_variant_id' => $component->id,
                'quantity' => $quantity,
            ]);
        }

        return $combo->fresh('comboItems.component');
    }

    private function inventory(): InventoryService
    {
        return app(InventoryService::class);
    }

    /* ---------------------------------------------------------- available */

    public function test_a_plain_variant_reports_its_own_stock(): void
    {
        $this->assertSame(7, $this->inventory()->available($this->variant('Mango', 7)));
    }

    public function test_a_combo_is_limited_by_its_scarcest_component(): void
    {
        $mango = $this->variant('Mango', 10);
        $ghee = $this->variant('Ghee', 3);

        // One mango and one ghee per bundle: only three bundles possible.
        $combo = $this->combo('Gift Box', [[$mango, 1], [$ghee, 1]]);

        $this->assertSame(3, $this->inventory()->available($combo));
    }

    public function test_a_component_needed_more_than_once_divides_down(): void
    {
        $mango = $this->variant('Mango', 10);

        // Four mangoes per bundle -> two bundles from ten.
        $combo = $this->combo('Four Pack', [[$mango, 4]]);

        $this->assertSame(2, $this->inventory()->available($combo));
    }

    public function test_a_combo_with_an_empty_component_cannot_be_sold(): void
    {
        $mango = $this->variant('Mango', 10);
        $ghee = $this->variant('Ghee', 0);

        $combo = $this->combo('Gift Box', [[$mango, 1], [$ghee, 1]]);

        $this->assertSame(0, $this->inventory()->available($combo));
        $this->assertFalse($this->inventory()->hasStockFor($combo, 1));
    }

    /* ------------------------------------------------------------ deduct */

    public function test_selling_a_combo_draws_down_every_component(): void
    {
        $mango = $this->variant('Mango', 10);
        $ghee = $this->variant('Ghee', 8);

        $combo = $this->combo('Gift Box', [[$mango, 2], [$ghee, 1]]);

        $this->inventory()->deduct($combo, 3);

        $this->assertSame(4, $mango->fresh()->stock, '10 - (2 x 3)');
        $this->assertSame(5, $ghee->fresh()->stock, '8 - (1 x 3)');
        $this->assertSame(0, $combo->fresh()->stock, 'The bundle itself holds no stock.');
    }

    public function test_overselling_a_combo_is_refused(): void
    {
        $mango = $this->variant('Mango', 10);
        $ghee = $this->variant('Ghee', 2);

        $combo = $this->combo('Gift Box', [[$mango, 1], [$ghee, 1]]);

        $this->expectException(\RuntimeException::class);

        $this->inventory()->deduct($combo, 3);
    }

    public function test_restoring_a_combo_puts_every_component_back(): void
    {
        $mango = $this->variant('Mango', 10);
        $ghee = $this->variant('Ghee', 8);

        $combo = $this->combo('Gift Box', [[$mango, 2], [$ghee, 1]]);

        $this->inventory()->deduct($combo, 2);
        $this->inventory()->restore($combo, 2);

        $this->assertSame(10, $mango->fresh()->stock);
        $this->assertSame(8, $ghee->fresh()->stock);
    }

    /* ---------------------------------------------------------- checkout */

    public function test_a_website_order_now_reduces_stock(): void
    {
        $variant = $this->variant('Mango', 10);

        $this->postJson(route('cart.add'), [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 3,
        ])->assertOk();

        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        $this->assertSame(7, $variant->fresh()->stock, 'Website sales used to leave stock untouched.');
    }

    public function test_a_website_order_for_a_combo_reduces_its_components(): void
    {
        $mango = $this->variant('Mango', 10);
        $ghee = $this->variant('Ghee', 8);
        $combo = $this->combo('Gift Box', [[$mango, 2], [$ghee, 1]]);

        $this->postJson(route('cart.add'), [
            'product_id' => $combo->product_id,
            'variant_id' => $combo->id,
            'quantity' => 2,
        ])->assertOk();

        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        $this->assertSame(6, $mango->fresh()->stock);
        $this->assertSame(6, $ghee->fresh()->stock);
    }

    public function test_an_order_that_cannot_be_stocked_is_not_written_at_all(): void
    {
        $variant = $this->variant('Mango', 1);

        $this->postJson(route('cart.add'), [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 5,
        ])->assertOk();

        $this->from(route('checkout'))
            ->post(route('checkout.store'), [
                'customer_name' => 'Rahim',
                'customer_phone' => '01711111111',
                'customer_address' => 'Dhaka',
                'customer_area' => 'dhaka_inside',
                'delivery_type' => 'home',
            ])
            ->assertRedirect(route('checkout'))
            ->assertSessionHasErrors('cart');

        $this->assertDatabaseCount('orders', 0);
        $this->assertDatabaseCount('order_items', 0);
        $this->assertSame(1, $variant->fresh()->stock, 'A refused order must not eat stock.');
    }

    /* ---------------------------------------------------------- display */

    public function test_a_combo_reads_as_in_stock_on_the_storefront(): void
    {
        $mango = $this->variant('Mango', 10);
        $combo = $this->combo('Gift Box', [[$mango, 1]]);

        $product = $combo->product->fresh('variants');

        $this->assertTrue($product->is_in_stock, 'The bundle itself holds 0 stock but can be sold.');
        $this->assertSame(10, $combo->fresh()->available_stock);
    }

    public function test_a_combo_reads_as_sold_out_once_a_component_runs_dry(): void
    {
        $mango = $this->variant('Mango', 0);
        $combo = $this->combo('Gift Box', [[$mango, 1]]);

        $this->assertFalse($combo->product->fresh('variants')->is_in_stock);
    }

    public function test_the_product_page_advertises_buildable_quantity(): void
    {
        $mango = $this->variant('Mango', 9);
        $combo = $this->combo('Gift Box', [[$mango, 3]]);

        $response = $this->get(route('product.show', $combo->product->slug));

        $response->assertOk();

        $pattern = '/data-vue="ProductPurchase"\s+data-props="([^"]*)"/';
        preg_match($pattern, $response->getContent(), $matches);
        $props = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);

        $this->assertSame(3, $props['variants'][0]['stock'], '9 mangoes / 3 per box = 3 boxes');
    }

    public function test_the_pos_grid_shows_buildable_quantity(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $mango = $this->variant('Mango', 8);
        $combo = $this->combo('Gift Box', [[$mango, 4]]);

        $items = $this->actingAs($admin)
            ->get(route('admin.pos.index'))
            ->viewData('items');

        $row = collect($items)->firstWhere('id', $combo->id);

        $this->assertSame(2, $row['stock']);
    }

    /* --------------------------------------------------------------- pos */

    public function test_a_pos_sale_of_a_combo_draws_down_components(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $mango = $this->variant('Mango', 10);
        $ghee = $this->variant('Ghee', 8);
        $combo = $this->combo('Gift Box', [[$mango, 2], [$ghee, 1]]);

        $this->actingAs($admin)
            ->postJson(route('admin.pos.store'), [
                'customer_name' => 'Walk-in',
                'customer_phone' => '01711111111',
                'customer_address' => 'Counter',
                'delivery_charge' => 0,
                'discount_amount' => 0,
                'items' => [['variant_id' => $combo->id, 'quantity' => 2]],
            ])
            ->assertOk();

        $this->assertSame(6, $mango->fresh()->stock);
        $this->assertSame(6, $ghee->fresh()->stock);
    }

    public function test_a_pos_sale_beyond_stock_is_still_refused(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $variant = $this->variant('Mango', 2);

        $this->actingAs($admin)
            ->postJson(route('admin.pos.store'), [
                'customer_name' => 'Walk-in',
                'customer_phone' => '01711111111',
                'customer_address' => 'Counter',
                'delivery_charge' => 0,
                'discount_amount' => 0,
                'items' => [['variant_id' => $variant->id, 'quantity' => 5]],
            ])
            ->assertStatus(500);

        $this->assertDatabaseCount('orders', 0);
        $this->assertSame(2, $variant->fresh()->stock);
    }
}
