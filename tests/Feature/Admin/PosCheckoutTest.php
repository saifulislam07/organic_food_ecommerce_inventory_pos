<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What the counter can record beyond the basket itself: who bought, what was
 * knocked off, a note, which channel the sale came in by, and what was handed
 * over — the figure the change is worked out from.
 */
class PosCheckoutTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

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

        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 কেজি',
            'price' => 500,
            'stock' => 50,
        ]);
    }

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function sell(array $overrides = [])
    {
        return $this->actingAs($this->admin())->postJson(route('admin.pos.store'), array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 2]],
            'delivery_charge' => 0,
            'discount_amount' => 0,
        ], $overrides));
    }

    /* ------------------------------------------------------- the customer */

    public function test_a_sale_can_be_attached_to_a_registered_customer(): void
    {
        $customer = User::factory()->create(['role' => 'customer', 'name' => 'Karim']);

        $this->sell(['customer_id' => $customer->id])->assertOk();

        $this->assertSame($customer->id, Order::firstOrFail()->user_id);
    }

    public function test_a_walk_in_sale_belongs_to_nobody(): void
    {
        $this->sell()->assertOk();

        $this->assertNull(Order::firstOrFail()->user_id);
    }

    public function test_an_admin_account_is_never_attached_as_the_customer(): void
    {
        // Picking a staff login would file the sale in that person's own order
        // history, which is not what the cashier meant.
        $staff = User::factory()->superAdmin()->create();

        $this->sell(['customer_id' => $staff->id])->assertOk();

        $this->assertNull(Order::firstOrFail()->user_id);
    }

    public function test_the_lookup_finds_a_customer_by_phone_or_name(): void
    {
        User::factory()->create(['role' => 'customer', 'name' => 'Karim Uddin', 'mobile' => '01755566677']);
        User::factory()->superAdmin()->create(['name' => 'Karim Admin']);

        $byPhone = $this->actingAs($this->admin())
            ->getJson(route('admin.pos.customers', ['q' => '555666']))
            ->assertOk()
            ->json();

        $byName = $this->actingAs($this->admin())
            ->getJson(route('admin.pos.customers', ['q' => 'Karim']))
            ->assertOk()
            ->json();

        $this->assertCount(1, $byPhone);
        $this->assertSame('Karim Uddin', $byPhone[0]['name']);
        $this->assertCount(1, $byName, 'Staff accounts are not customers.');
    }

    public function test_the_lookup_stays_quiet_until_there_is_something_to_go_on(): void
    {
        User::factory()->create(['role' => 'customer', 'name' => 'Karim']);

        $this->actingAs($this->admin())
            ->getJson(route('admin.pos.customers', ['q' => 'K']))
            ->assertOk()
            ->assertExactJson([]);
    }

    /* ------------------------------------------------------- the discount */

    public function test_a_flat_discount_comes_off_in_taka(): void
    {
        $this->sell(['discount_amount' => 150])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('150.00', $order->discount_amount);
        $this->assertSame('850.00', $order->total);
    }

    public function test_a_percentage_discount_is_worked_out_by_the_server(): void
    {
        $this->sell(['discount_amount' => 10, 'discount_type' => 'percent'])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('100.00', $order->discount_amount, '10% of 1000.');
        $this->assertSame('900.00', $order->total);
    }

    public function test_a_discount_can_never_exceed_the_subtotal(): void
    {
        $this->sell(['discount_amount' => 5000])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('1000.00', $order->discount_amount);
        $this->assertSame('0.00', $order->total, 'A sale can be given away, never come out owing money.');
    }

    public function test_a_percentage_above_a_hundred_is_clamped(): void
    {
        $this->sell(['discount_amount' => 250, 'discount_type' => 'percent'])->assertOk();

        $this->assertSame('1000.00', Order::firstOrFail()->discount_amount);
    }

    public function test_an_unknown_discount_type_is_rejected(): void
    {
        $this->sell(['discount_amount' => 10, 'discount_type' => 'nonsense'])
            ->assertJsonValidationErrors('discount_type');
    }

    /* ------------------------------------------------- offers on the line */

    private function saleVariant(float $price, float $sale): ProductVariant
    {
        $this->variant->update(['price' => $price, 'sale_price' => $sale]);

        return $this->variant->fresh();
    }

    public function test_a_product_on_offer_rings_up_at_its_shelf_price(): void
    {
        // 1,400 listed, 1,250 on offer, two of them: the slip shows 2,800 with
        // 300 taken off rather than hiding the reduction inside the line.
        $this->saleVariant(1400, 1250);

        $this->sell()->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('2800.00', $order->subtotal);
        $this->assertSame('300.00', $order->discount_amount);
        $this->assertSame('2500.00', $order->total);
        $this->assertSame('1400.00', $order->items()->first()->unit_price);
    }

    public function test_the_offer_is_a_floor_the_browser_cannot_undercut(): void
    {
        // A client that forgets the offer, or is tampered with, must not end up
        // charging the customer the full shelf price.
        $this->saleVariant(1400, 1250);

        $this->sell(['discount_amount' => 0])->assertOk();

        $this->assertSame('300.00', Order::firstOrFail()->discount_amount);
    }

    public function test_the_cashier_can_knock_off_more_than_the_offer(): void
    {
        $this->saleVariant(1400, 1250);

        $this->sell(['discount_amount' => 500])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('500.00', $order->discount_amount);
        $this->assertSame('2300.00', $order->total);
    }

    public function test_a_product_with_no_offer_is_unaffected(): void
    {
        $this->sell(['discount_amount' => 0])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('1000.00', $order->subtotal);
        $this->assertSame('0.00', $order->discount_amount);
    }

    /* ----------------------------------------------------------- the note */

    public function test_a_note_is_kept_with_the_sale(): void
    {
        $this->sell(['notes' => 'Customer will collect after 5pm.'])->assertOk();

        $this->assertSame('Customer will collect after 5pm.', Order::firstOrFail()->notes);
    }

    /* --------------------------------------------------------- the source */

    public function test_the_channel_the_sale_came_in_by_is_recorded(): void
    {
        $this->sell(['source' => 'whatsapp'])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('whatsapp', $order->source);
        $this->assertTrue($order->isCounterSale(), 'It is still a sale rung up at the counter.');
    }

    public function test_a_sale_with_no_channel_chosen_is_a_counter_sale(): void
    {
        $this->sell()->assertOk();

        $this->assertSame('pos', Order::firstOrFail()->source);
    }

    public function test_a_channel_the_counter_does_not_offer_is_rejected(): void
    {
        // 'website' is how an order reaches the system, not something a cashier
        // may claim a counter sale arrived by.
        $this->sell(['source' => 'website'])->assertJsonValidationErrors('source');
    }

    /* --------------------------------------------------------- the change */

    public function test_the_change_is_worked_out_from_what_was_handed_over(): void
    {
        $this->sell(['paid_amount' => 1200])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('1200.00', $order->paid_amount);
        $this->assertSame(200.0, $order->change_due);
    }

    public function test_a_part_payment_is_kept_as_given_and_owes_no_change(): void
    {
        $this->sell(['paid_amount' => 600])->assertOk();

        $order = Order::firstOrFail();

        $this->assertSame('600.00', $order->paid_amount, 'The short figure is recorded, not rounded up.');
        $this->assertSame(0.0, $order->change_due);
    }

    public function test_no_payment_figure_leaves_the_change_unknown(): void
    {
        $this->sell()->assertOk();

        $order = Order::firstOrFail();

        $this->assertNull($order->paid_amount);
        $this->assertNull($order->change_due, 'Null is "not recorded", which is not the same as no change.');
    }

    /* ---------------------------------------------------------- the views */

    public function test_a_channel_sale_reads_correctly_on_the_order_screens(): void
    {
        $this->sell([
            'source' => 'facebook',
            'notes' => 'Gift wrap it.',
            'paid_amount' => 1000,
        ])->assertOk();

        $order = Order::firstOrFail();
        $admin = $this->admin();

        $this->actingAs($admin)->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Facebook')
            ->assertSee('Gift wrap it.')
            ->assertDontSee('Website Order');

        $this->actingAs($admin)->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->assertSee('Gift wrap it.');

        $this->actingAs($admin)->get(route('admin.orders.index'))
            ->assertOk()
            ->assertSee('Facebook');
    }
}
