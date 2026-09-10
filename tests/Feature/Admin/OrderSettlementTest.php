<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Services\ProfitLossReport;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * What a delivery actually settled for.
 *
 * On cash on delivery the courier keeps its fee out of what it collects, so the
 * money that reaches the shop is less than the order total. Before this existed
 * the books took the total at face value, which overstated the cash by the
 * courier's fee on every single order — and put it in a notional "cod" account
 * rather than wherever the remittance actually landed.
 */
class OrderSettlementTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Mango', 'slug' => 'mango',
        ]);

        // Bought at 60, sold at 100.
        $this->variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => '1 kg', 'price' => 100, 'cost_price' => 60, 'stock' => 100,
        ]);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    /** One order: 10 kg at 100, plus 60 delivery. Total 1060. */
    private function order(array $overrides = []): Order
    {
        $order = Order::create(array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'subtotal' => 1000,
            'discount_amount' => 0,
            'delivery_charge' => 60,
            'total' => 1060,
            'status' => 'shipped',
            'payment_method' => 'cod',
            'source' => 'website',
        ], $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->variant->product_id,
            'product_variant_id' => $this->variant->id,
            'product_name' => 'Mango',
            'variant_name' => '1 kg',
            'quantity' => 10,
            'unit_price' => 100,
            'total' => 1000,
        ]);

        return $order->fresh();
    }

    private function settle(Order $order, array $overrides = [])
    {
        return $this->actingAs($this->admin())->post(route('admin.orders.settle', $order), array_merge([
            'collected_amount' => 960,
            'collected_in' => 'bkash',
            'courier_charge' => 100,
        ], $overrides));
    }

    private function report(): array
    {
        return ProfitLossReport::between(
            now()->startOfMonth()->toDateString(),
            now()->endOfMonth()->toDateString(),
        )->summary();
    }

    /* --------------------------------------------------------- recording */

    public function test_settling_records_the_figures_and_marks_it_delivered(): void
    {
        $order = $this->order();

        $this->settle($order)->assertRedirect();

        $order->refresh();

        $this->assertEquals(960, (float) $order->collected_amount);
        $this->assertSame('bkash', $order->collected_in);
        $this->assertEquals(100, (float) $order->courier_charge);
        $this->assertSame('delivered', $order->status);
        $this->assertNotNull($order->delivered_at);
        $this->assertTrue($order->isSettled());
    }

    public function test_an_order_with_no_settlement_is_not_settled(): void
    {
        $this->assertFalse($this->order(['status' => 'delivered'])->isSettled());
    }

    public function test_settling_zero_still_counts_as_recorded(): void
    {
        // A parcel returned undelivered collects nothing, and that is a fact
        // about the order rather than an absence of one.
        $order = $this->order();

        $this->settle($order, ['collected_amount' => 0, 'courier_charge' => 60])->assertRedirect();

        $this->assertTrue($order->fresh()->isSettled());
        $this->assertEquals(0, (float) $order->fresh()->collected_amount);
    }

    public function test_an_order_a_courier_already_marked_delivered_can_still_be_settled(): void
    {
        $order = $this->order(['status' => 'delivered', 'delivered_at' => now()->subDay()]);
        $stamp = $order->delivered_at;

        $this->settle($order)->assertRedirect();

        $order->refresh();

        $this->assertTrue($order->isSettled());
        // The delivery date is when it was delivered, not when it was typed in.
        $this->assertTrue($stamp->equalTo($order->delivered_at));
    }

    public function test_a_settlement_can_be_corrected(): void
    {
        $order = $this->order();

        $this->settle($order)->assertRedirect();
        $this->settle($order, ['collected_amount' => 900, 'collected_in' => 'cash'])->assertRedirect();

        $order->refresh();

        $this->assertEquals(900, (float) $order->collected_amount);
        $this->assertSame('cash', $order->collected_in);
    }

    public function test_the_account_must_be_a_real_head(): void
    {
        $this->settle($this->order(), ['collected_in' => 'bitcoin'])
            ->assertSessionHasErrors('collected_in');
    }

    public function test_a_shortfall_is_called_out(): void
    {
        $order = $this->order();

        // Expected 1060 − 100 = 960; only 900 arrived.
        $this->settle($order, ['collected_amount' => 900])
            ->assertSessionHas('success', fn ($message) => str_contains($message, 'Short by'));
    }

    /* ------------------------------------------------------------ figures */

    public function test_the_expected_collection_is_the_balance_less_the_courier_fee(): void
    {
        $order = $this->order();
        $order->courier_charge = 100;

        $this->assertEquals(1060, $order->amount_due);
        $this->assertEquals(960, $order->expected_collection);
    }

    public function test_a_part_payment_comes_off_what_is_still_due(): void
    {
        $order = $this->order(['paid_amount' => 500]);

        $this->assertEquals(560, $order->amount_due);
    }

    public function test_an_overpaid_counter_sale_is_never_owed_money_back(): void
    {
        // paid_amount at the counter is the note handed over, not the price.
        $order = $this->order(['paid_amount' => 2000]);

        $this->assertEquals(0, $order->amount_due);
    }

    public function test_variance_is_null_until_something_is_recorded(): void
    {
        $this->assertNull($this->order()->settlement_variance);
    }

    /* ----------------------------------------------------------- accounts */

    public function test_the_courier_fee_comes_off_the_profit(): void
    {
        $order = $this->order();
        $before = $this->report()['net_profit'];

        $this->settle($order, ['courier_charge' => 100])->assertRedirect();

        $after = $this->report();

        $this->assertEquals(100, $after['courier_charges']);
        $this->assertEquals($before - 100, $after['net_profit']);
    }

    public function test_the_money_lands_in_the_account_it_was_collected_into(): void
    {
        $order = $this->order();

        $this->settle($order, ['collected_amount' => 960, 'collected_in' => 'bkash'])->assertRedirect();

        $accounts = $this->report()['accounts'];

        // What actually arrived, in the account it arrived in — not the total
        // in the notional "cash on delivery" head.
        $this->assertEquals(960, $accounts['bkash']['in']);
        $this->assertEquals(0, $accounts['cod']['in']);
    }

    public function test_an_unsettled_order_falls_back_to_its_face_value(): void
    {
        $this->order(['status' => 'delivered']);

        $report = $this->report();

        $this->assertEquals(1060, $report['accounts']['cod']['in']);
        $this->assertSame(1, $report['unsettled_orders']);
        $this->assertEquals(1060, $report['unsettled_value']);
    }

    public function test_settling_moves_an_order_out_of_the_assumed_column(): void
    {
        $order = $this->order(['status' => 'delivered']);

        $this->settle($order)->assertRedirect();

        $report = $this->report();

        $this->assertSame(0, $report['unsettled_orders']);
        $this->assertEquals(0, $report['accounts']['cod']['in']);
    }

    public function test_settled_and_unsettled_orders_are_added_together(): void
    {
        $this->settle($this->order(), ['collected_amount' => 960, 'collected_in' => 'cash'])->assertRedirect();
        $this->order(['status' => 'delivered', 'payment_method' => 'cash']);

        // 960 recorded plus 1060 assumed, both in the cash head.
        $this->assertEquals(2020, $this->report()['accounts']['cash']['in']);
    }

    public function test_revenue_is_still_what_was_sold_not_what_was_collected(): void
    {
        $order = $this->order();

        $this->settle($order)->assertRedirect();

        // The fee is a cost, not a discount: the customer was charged 1060 and
        // the shop earned 1060. What it kept is a different question.
        $this->assertEquals(1060, $this->report()['revenue']);
    }

    /* --------------------------------------------------------- the screen */

    public function test_the_order_page_prompts_for_an_unrecorded_settlement(): void
    {
        $order = $this->order(['status' => 'delivered']);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('has not been settled', false);
    }

    public function test_the_list_can_filter_down_to_what_needs_settling(): void
    {
        $needs = $this->order(['status' => 'delivered']);
        $done = $this->order(['status' => 'delivered']);
        $this->settle($done);

        // The settle redirect flashes a message naming that order, and the flash
        // would satisfy assertDontSee's search of the whole page.
        $this->flushSession();

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index', ['settlement' => 'pending']))
            ->assertOk()
            ->assertSee($needs->order_number)
            ->assertDontSee($done->order_number);
    }
}
