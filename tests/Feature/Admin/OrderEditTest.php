<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\OrderStatusChanged;
use App\Support\SmsSettings;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

/**
 * Editing an order after the fact.
 *
 * The whole risk here is stock. An order's lines are a claim on the shelves,
 * so rewriting them has to give back what the old lines were holding before it
 * takes what the new ones need — and if the new ones cannot be covered, nothing
 * may change at all. A half-applied edit leaves phantom stock that can never be
 * sold, and nobody would notice until a count came up short.
 */
class OrderEditTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $mango;

    private ProductVariant $lychee;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango',
            'is_active' => true,
        ]);

        $this->mango = ProductVariant::create([
            'product_id' => $product->id, 'name' => '1 kg', 'price' => 100, 'stock' => 50,
        ]);

        $other = Product::create([
            'category_id' => $category->id,
            'name' => 'Lychee',
            'slug' => 'lychee',
            'is_active' => true,
        ]);

        $this->lychee = ProductVariant::create([
            'product_id' => $other->id, 'name' => '100 pcs', 'price' => 250, 'stock' => 10,
        ]);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    /** An order for 5 mangoes, with the stock already taken for it. */
    private function order(array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'subtotal' => 500,
            'discount_amount' => 0,
            'delivery_charge' => 60,
            'total' => 560,
            'status' => 'confirmed',
            'payment_method' => 'cod',
            'source' => 'website',
        ], $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->mango->product_id,
            'product_variant_id' => $this->mango->id,
            'product_name' => 'Himsagar Mango',
            'variant_name' => '1 kg',
            'quantity' => 5,
            'unit_price' => 100,
            'total' => 500,
        ]);

        $this->mango->decrement('stock', 5);

        return $order->fresh();
    }

    private function payload(Order $order, array $overrides = []): array
    {
        return array_merge([
            'customer_name' => $order->customer_name,
            'customer_phone' => $order->customer_phone,
            'customer_address' => $order->customer_address,
            'payment_method' => $order->payment_method,
            'status' => $order->status,
            'delivery_charge' => 60,
            'discount_amount' => 0,
            'items' => [
                ['variant_id' => $this->mango->id, 'quantity' => 5, 'unit_price' => 100],
            ],
        ], $overrides);
    }

    private function save(Order $order, array $overrides = [])
    {
        return $this->actingAs($this->admin())
            ->put(route('admin.orders.update', $order), $this->payload($order, $overrides));
    }

    /* ------------------------------------------------------------- access */

    public function test_the_edit_screen_renders(): void
    {
        $order = $this->order();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.edit', $order))
            ->assertOk()
            ->assertSee('data-vue="OrderEditor"', false)
            ->assertSee('Himsagar Mango');
    }

    public function test_every_order_is_editable_whatever_state_it_is_in(): void
    {
        foreach (array_keys(Order::STATUSES) as $status) {
            $order = $this->order(['status' => $status]);

            $this->actingAs($this->admin())
                ->get(route('admin.orders.edit', $order))
                ->assertOk();
        }
    }

    public function test_a_user_without_edit_permission_cannot_open_it(): void
    {
        $this->seed(PermissionSeeder::class);

        $order = $this->order();
        $viewer = User::factory()->create(['role' => 'admin']);
        $viewer->givePermissionTo('orders.view');

        $this->actingAs($viewer)->get(route('admin.orders.edit', $order))->assertForbidden();
    }

    /* -------------------------------------------------------------- stock */

    public function test_raising_a_quantity_takes_the_difference_from_stock(): void
    {
        $order = $this->order();
        $before = $this->mango->fresh()->stock;

        $this->save($order, [
            'items' => [['variant_id' => $this->mango->id, 'quantity' => 8, 'unit_price' => 100]],
        ])->assertRedirect(route('admin.orders.show', $order));

        // Three more mangoes left the shelf, not eight.
        $this->assertSame($before - 3, $this->mango->fresh()->stock);
    }

    public function test_lowering_a_quantity_puts_stock_back(): void
    {
        $order = $this->order();
        $before = $this->mango->fresh()->stock;

        $this->save($order, [
            'items' => [['variant_id' => $this->mango->id, 'quantity' => 2, 'unit_price' => 100]],
        ])->assertRedirect();

        $this->assertSame($before + 3, $this->mango->fresh()->stock);
    }

    public function test_removing_a_line_returns_everything_it_held(): void
    {
        $order = $this->order();
        $before = $this->mango->fresh()->stock;

        $this->save($order, [
            'items' => [['variant_id' => $this->lychee->id, 'quantity' => 1, 'unit_price' => 250]],
        ])->assertRedirect();

        $this->assertSame($before + 5, $this->mango->fresh()->stock);
        $this->assertSame(9, $this->lychee->fresh()->stock);
    }

    public function test_an_edit_it_cannot_cover_changes_nothing_at_all(): void
    {
        $order = $this->order();
        $stockBefore = $this->mango->fresh()->stock;

        // Only 45 mangoes on the shelf plus the 5 this order holds.
        $response = $this->save($order, [
            'items' => [['variant_id' => $this->mango->id, 'quantity' => 500, 'unit_price' => 100]],
        ]);

        $response->assertSessionHasErrors('items');

        $this->assertSame($stockBefore, $this->mango->fresh()->stock);
        $this->assertSame(5, (int) $order->fresh()->items->first()->quantity);
        $this->assertEquals(560, (float) $order->fresh()->total);
    }

    public function test_an_order_may_take_back_the_stock_it_is_already_holding(): void
    {
        // 5 on the order and 45 on the shelf: asking for all 50 must be allowed,
        // because this order is the reason the other five are missing.
        $order = $this->order();
        $this->mango->update(['stock' => 45]);

        $this->save($order, [
            'items' => [['variant_id' => $this->mango->id, 'quantity' => 50, 'unit_price' => 100]],
        ])->assertRedirect();

        $this->assertSame(0, $this->mango->fresh()->stock);
    }

    public function test_two_lines_for_one_product_are_merged_rather_than_checked_apart(): void
    {
        $order = $this->order();
        $this->mango->update(['stock' => 5]);

        // 5 held by the order + 5 on the shelf = 10 available. Two lines of 6
        // would each pass a check of their own; together they cannot.
        $this->save($order, [
            'items' => [
                ['variant_id' => $this->mango->id, 'quantity' => 6, 'unit_price' => 100],
                ['variant_id' => $this->mango->id, 'quantity' => 6, 'unit_price' => 100],
            ],
        ])->assertSessionHasErrors('items');

        $this->assertSame(5, $this->mango->fresh()->stock);
    }

    public function test_duplicate_lines_that_do_fit_become_one_line(): void
    {
        $order = $this->order();

        $this->save($order, [
            'items' => [
                ['variant_id' => $this->mango->id, 'quantity' => 2, 'unit_price' => 100],
                ['variant_id' => $this->mango->id, 'quantity' => 3, 'unit_price' => 100],
            ],
        ])->assertRedirect();

        $items = $order->fresh()->items;

        $this->assertCount(1, $items);
        $this->assertSame(5, (int) $items->first()->quantity);
    }

    /* ------------------------------------------------------------- totals */

    public function test_totals_are_recomputed_from_the_lines(): void
    {
        $order = $this->order();

        $this->save($order, [
            'items' => [
                ['variant_id' => $this->mango->id, 'quantity' => 2, 'unit_price' => 100],
                ['variant_id' => $this->lychee->id, 'quantity' => 1, 'unit_price' => 250],
            ],
            'delivery_charge' => 80,
            'discount_amount' => 50,
        ])->assertRedirect();

        $order->refresh();

        $this->assertEquals(450, (float) $order->subtotal);
        $this->assertEquals(50, (float) $order->discount_amount);
        $this->assertEquals(480, (float) $order->total);
    }

    public function test_a_percentage_discount_is_worked_out_by_the_server(): void
    {
        $order = $this->order();

        $this->save($order, [
            'discount_amount' => 10,
            'discount_type' => 'percent',
        ])->assertRedirect();

        $order->refresh();

        // 10% of a 500 subtotal, not 10 taka.
        $this->assertEquals(50, (float) $order->discount_amount);
        $this->assertEquals(510, (float) $order->total);
    }

    public function test_a_discount_can_never_exceed_the_subtotal(): void
    {
        $order = $this->order();

        $this->save($order, ['discount_amount' => 9999])->assertRedirect();

        $order->refresh();

        $this->assertEquals(500, (float) $order->discount_amount);
        $this->assertEquals(60, (float) $order->total);
    }

    public function test_an_admin_can_override_the_price_on_a_line(): void
    {
        $order = $this->order();

        $this->save($order, [
            'items' => [['variant_id' => $this->mango->id, 'quantity' => 5, 'unit_price' => 90]],
        ])->assertRedirect();

        $order->refresh();

        $this->assertEquals(90, (float) $order->items->first()->unit_price);
        $this->assertEquals(450, (float) $order->subtotal);
    }

    /* ----------------------------------------------------------- the rest */

    public function test_customer_details_can_be_corrected(): void
    {
        $order = $this->order();

        $this->save($order, [
            'customer_name' => 'Karim Uddin',
            'customer_phone' => '01800000000',
            'customer_address' => 'Chattogram',
            'notes' => 'Ring the bell twice',
        ])->assertRedirect();

        $order->refresh();

        $this->assertSame('Karim Uddin', $order->customer_name);
        $this->assertSame('01800000000', $order->customer_phone);
        $this->assertSame('Chattogram', $order->customer_address);
        $this->assertSame('Ring the bell twice', $order->notes);
    }

    public function test_an_edit_that_moves_the_status_tells_the_customer(): void
    {
        Notification::fake();
        $this->configureSmsChannel();

        $order = $this->order(['status' => 'confirmed']);

        $this->save($order, ['status' => 'shipped'])->assertRedirect();

        $this->assertSame('shipped', $order->fresh()->status);
        Notification::assertSentTo(new AnonymousNotifiable, OrderStatusChanged::class);
    }

    public function test_an_edit_that_leaves_the_status_alone_says_nothing(): void
    {
        Notification::fake();
        $this->configureSmsChannel();

        $order = $this->order(['status' => 'confirmed']);

        $this->save($order, ['delivery_charge' => 120])->assertRedirect();

        Notification::assertNothingSent();
    }

    /**
     * A customer notification is only dispatched on a channel that is set up,
     * so without a gateway configured there is nothing for the fake to record.
     */
    private function configureSmsChannel(): void
    {
        SmsSettings::save([
            'sms_driver' => 'bulksmsbd',
            'sms_sender_id' => 'BaburhashiBD',
            'sms_api_key' => 'key',
        ]);

        SmsSettings::forget();
    }

    public function test_an_order_must_keep_at_least_one_line(): void
    {
        $order = $this->order();

        $this->save($order, ['items' => []])->assertSessionHasErrors('items');

        $this->assertCount(1, $order->fresh()->items);
    }

    public function test_the_editor_can_search_the_catalogue(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.orders.products', ['q' => 'Himsagar']))
            ->assertOk()
            ->assertJsonFragment(['product_name' => 'Himsagar Mango']);
    }

    public function test_a_one_letter_search_returns_nothing(): void
    {
        $this->actingAs($this->admin())
            ->getJson(route('admin.orders.products', ['q' => 'H']))
            ->assertOk()
            ->assertExactJson([]);
    }
}
