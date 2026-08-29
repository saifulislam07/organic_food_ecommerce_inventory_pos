<?php

namespace Tests\Feature\Admin;

use App\Models\Adjustment;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\User;
use App\Services\ProfitLossReport;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Tests\TestCase;

/**
 * Sales, purchases, expenses and damage, added up per account head.
 *
 * The trap this guards against is counting a purchase twice: as cash out and
 * again as a cost. Stock bought is not a loss until it is sold.
 */
class ProfitLossTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango',
        ]);

        // Bought at 60, sold at 100.
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'price' => 100,
            'cost_price' => 60,
            'stock' => 100,
        ]);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function sell(int $quantity, string $status = 'delivered', string $account = 'cash', ?Carbon $on = null): Order
    {
        $price = 100;

        $order = Order::create([
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'subtotal' => $price * $quantity,
            'discount_amount' => 0,
            'delivery_charge' => 0,
            'total' => $price * $quantity,
            'status' => $status,
            'payment_method' => $account,
            'source' => 'pos',
        ]);

        if ($on) {
            $order->forceFill(['created_at' => $on])->saveQuietly();
        }

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $this->variant->product_id,
            'product_variant_id' => $this->variant->id,
            'product_name' => 'Himsagar Mango',
            'variant_name' => '1 kg',
            'quantity' => $quantity,
            'unit_price' => $price,
            'total' => $price * $quantity,
        ]);

        return $order;
    }

    private function report(?string $from = null, ?string $to = null): array
    {
        return ProfitLossReport::between($from, $to)->summary();
    }

    /* -------------------------------------------------------- the sums */

    public function test_an_empty_month_reports_zeroes_rather_than_nulls(): void
    {
        $report = $this->report();

        $this->assertSame(0, $report['orders']);
        $this->assertSame(0.0, $report['revenue']);
        $this->assertSame(0.0, $report['net_profit']);
        $this->assertSame(0.0, $report['gross_margin']);
    }

    public function test_gross_profit_is_revenue_less_what_the_goods_cost(): void
    {
        $this->sell(10);

        $report = $this->report();

        $this->assertSame(1000.0, $report['revenue']);
        $this->assertSame(600.0, $report['cost_of_goods']);
        $this->assertSame(400.0, $report['gross_profit']);
        $this->assertSame(40.0, $report['gross_margin']);
    }

    public function test_expenses_and_damage_come_off_the_net(): void
    {
        $this->sell(10);

        Expense::create([
            'title' => 'Packaging',
            'category' => 'supplies',
            'amount' => 100,
            'expense_date' => now()->toDateString(),
            'paid_from' => 'cash',
        ]);

        // Three units written off, at 60 each.
        Adjustment::create([
            'product_variant_id' => $this->variant->id,
            'quantity' => 3,
            'type' => 'damage',
            'adjustment_date' => now()->toDateString(),
        ]);

        $report = $this->report();

        $this->assertSame(100.0, $report['expenses']);
        $this->assertSame(180.0, $report['damage']);
        $this->assertSame(3, $report['damage_units']);
        $this->assertSame(120.0, $report['net_profit']);  // 400 − 100 − 180
    }

    public function test_a_purchase_is_cash_out_but_not_a_loss(): void
    {
        $this->sell(10);

        Purchase::create([
            'supplier_id' => Supplier::create(['name' => 'Chapai Traders'])->id,
            'product_variant_id' => $this->variant->id,
            'purchase_price' => 60,
            'quantity' => 50,
            'purchase_date' => now()->toDateString(),
            'paid_from' => 'bank',
        ]);

        $report = $this->report();

        // 3,000 of stock bought — the profit is untouched by it.
        $this->assertSame(3000.0, $report['purchases']);
        $this->assertSame(400.0, $report['net_profit']);
        $this->assertSame(3000.0, $report['accounts']['bank']['out']);
    }

    public function test_a_returned_adjustment_is_not_treated_as_damage(): void
    {
        $this->sell(10);

        Adjustment::create([
            'product_variant_id' => $this->variant->id,
            'quantity' => 5,
            'type' => 'returned',
            'adjustment_date' => now()->toDateString(),
        ]);

        $this->assertSame(0.0, $this->report()['damage']);
    }

    public function test_a_cancelled_order_earns_nothing(): void
    {
        $this->sell(10, 'delivered');
        $this->sell(4, 'cancelled');

        $report = $this->report();

        $this->assertSame(1, $report['orders']);
        $this->assertSame(1000.0, $report['revenue']);
    }

    public function test_a_variant_with_no_cost_price_does_not_invent_one(): void
    {
        $this->variant->update(['cost_price' => null]);
        $this->sell(10);

        $report = $this->report();

        $this->assertSame(0.0, $report['cost_of_goods']);
        $this->assertSame(1000.0, $report['gross_profit']);
    }

    /* ------------------------------------------------------- the range */

    public function test_only_what_happened_inside_the_range_is_counted(): void
    {
        $this->sell(10, 'delivered', 'cash', now()->subMonths(2));
        $this->sell(3, 'delivered', 'cash', now());

        $report = $this->report(now()->startOfMonth()->toDateString(), now()->toDateString());

        $this->assertSame(1, $report['orders']);
        $this->assertSame(300.0, $report['revenue']);
    }

    public function test_the_last_day_of_the_range_is_counted(): void
    {
        // Expenses, purchases and damage are filed against date columns rather
        // than timestamps, and the last day of the range is where an off-by-one
        // in the bounds hides: everything else in the month still adds up.
        $supplier = Supplier::create(['name' => 'Chapai Traders']);

        foreach (['2026-06-20' => 100, '2026-06-21' => 999] as $date => $amount) {
            Expense::create([
                'title' => 'Packaging',
                'category' => 'supplies',
                'amount' => $amount,
                'expense_date' => $date,
                'paid_from' => 'cash',
            ]);

            Purchase::create([
                'supplier_id' => $supplier->id,
                'product_variant_id' => $this->variant->id,
                'purchase_price' => $amount,
                'quantity' => 1,
                'purchase_date' => $date,
                'paid_from' => 'bank',
            ]);

            Adjustment::create([
                'product_variant_id' => $this->variant->id,
                'quantity' => 1,
                'type' => 'damage',
                'adjustment_date' => $date,
            ]);
        }

        $report = $this->report('2026-06-10', '2026-06-20');

        // The 20th is in; the 21st is a day past the end.
        $this->assertSame(100.0, $report['expenses']);
        $this->assertSame(100.0, $report['purchases']);
        $this->assertSame(60.0, $report['damage']);
        $this->assertSame(1, $report['damage_units']);
        $this->assertSame(100.0, $report['accounts']['cash']['out']);
        $this->assertSame(100.0, $report['accounts']['bank']['out']);
    }

    public function test_a_range_typed_backwards_is_still_read_the_right_way_round(): void
    {
        $report = ProfitLossReport::between(now()->toDateString(), now()->subMonth()->toDateString());

        $this->assertTrue($report->from()->lessThan($report->to()));
    }

    /* ---------------------------------------------------- the accounts */

    public function test_money_is_split_across_the_account_heads(): void
    {
        $this->sell(5, 'delivered', 'bkash');
        $this->sell(2, 'delivered', 'cash');

        Expense::create([
            'title' => 'Rent',
            'category' => 'utilities',
            'amount' => 200,
            'expense_date' => now()->toDateString(),
            'paid_from' => 'nagad',
        ]);

        $accounts = $this->report()['accounts'];

        $this->assertSame(500.0, $accounts['bkash']['in']);
        $this->assertSame(200.0, $accounts['cash']['in']);
        $this->assertSame(200.0, $accounts['nagad']['out']);
        $this->assertSame(-200.0, $accounts['nagad']['net']);

        // Every head shows, even the quiet ones.
        foreach (['cash', 'bank', 'bkash', 'nagad', 'rocket', 'cod'] as $head) {
            $this->assertArrayHasKey($head, $accounts);
        }
    }

    /* ---------------------------------------------------- the page */

    public function test_the_page_renders_the_figures(): void
    {
        $this->sell(10);

        $this->actingAs($this->admin())
            ->get(route('admin.reports.profitLoss'))
            ->assertOk()
            ->assertSee('Net profit')
            ->assertSee('৳400')
            ->assertSee('Rocket');
    }

    public function test_the_range_can_be_narrowed_from_the_page(): void
    {
        $this->sell(10, 'delivered', 'cash', now()->subMonths(3));

        $html = $this->actingAs($this->admin())
            ->get(route('admin.reports.profitLoss', [
                'from' => now()->startOfMonth()->toDateString(),
                'to' => now()->toDateString(),
            ]))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString('0 order(s)', $html);
    }

    public function test_a_bad_date_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.reports.profitLoss', ['from' => 'yesterday-ish']))
            ->assertSessionHasErrors('from');
    }

    public function test_the_report_needs_its_own_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['dashboard.view', 'orders.view']);

        $this->actingAs($staff->fresh())->get(route('admin.reports.profitLoss'))->assertForbidden();

        $staff->syncPermissions(['dashboard.view', 'reports.view']);

        $this->actingAs($staff->fresh())->get(route('admin.reports.profitLoss'))->assertOk();
    }

    public function test_the_sidebar_shows_the_link_only_to_staff_who_can_open_it(): void
    {
        $this->seed(PermissionSeeder::class);

        $allowed = User::factory()->admin()->create();
        $allowed->syncPermissions(['dashboard.view', 'reports.view']);

        $this->actingAs($allowed->fresh())->get('/admin')
            ->assertSee(route('admin.reports.profitLoss'));

        $denied = User::factory()->admin()->create();
        $denied->syncPermissions(['dashboard.view']);

        $this->actingAs($denied->fresh())->get('/admin')
            ->assertDontSee(route('admin.reports.profitLoss'));
    }

    /* -------------------------------------------------- recording it */

    public function test_the_pos_records_which_account_took_the_money(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.pos.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 2]],
            'delivery_charge' => 0,
            'discount_amount' => 0,
            'payment_method' => 'bkash',
        ])->assertOk();

        $this->assertSame('bkash', Order::firstOrFail()->payment_method);
        $this->assertSame(200.0, $this->report()['accounts']['bkash']['in']);
    }

    public function test_the_pos_falls_back_to_cash_when_nothing_is_chosen(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.pos.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 1]],
            'delivery_charge' => 0,
            'discount_amount' => 0,
        ])->assertOk();

        $this->assertSame('cash', Order::firstOrFail()->payment_method);
    }

    public function test_the_pos_refuses_an_account_that_does_not_exist(): void
    {
        $this->actingAs($this->admin())->postJson(route('admin.pos.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'items' => [['variant_id' => $this->variant->id, 'quantity' => 1]],
            'delivery_charge' => 0,
            'discount_amount' => 0,
            'payment_method' => 'bitcoin',
        ])->assertStatus(422);
    }

    public function test_an_expense_records_the_account_it_was_paid_from(): void
    {
        $this->actingAs($this->admin())->post(route('admin.expenses.store'), [
            'title' => 'Packaging',
            'category' => 'supplies',
            'amount' => 250,
            'expense_date' => now()->toDateString(),
            'paid_from' => 'rocket',
        ])->assertRedirect();

        $this->assertSame('rocket', Expense::firstOrFail()->paid_from);
        $this->assertSame(250.0, $this->report()['accounts']['rocket']['out']);
    }

    public function test_a_purchase_records_the_account_it_was_paid_from(): void
    {
        $this->actingAs($this->admin())->post(route('admin.purchases.store'), [
            'supplier_id' => Supplier::create(['name' => 'Chapai Traders'])->id,
            'product_variant_id' => $this->variant->id,
            'purchase_price' => 60,
            'quantity' => 10,
            'purchase_date' => now()->toDateString(),
            'paid_from' => 'bank',
        ])->assertRedirect();

        $this->assertSame('bank', Purchase::firstOrFail()->paid_from);
        $this->assertSame(600.0, $this->report()['accounts']['bank']['out']);
    }

    public function test_a_form_that_omits_the_account_still_saves(): void
    {
        $this->actingAs($this->admin())->post(route('admin.expenses.store'), [
            'title' => 'Packaging',
            'category' => 'supplies',
            'amount' => 250,
            'expense_date' => now()->toDateString(),
        ])->assertRedirect();

        $this->assertSame('cash', Expense::firstOrFail()->paid_from);
    }
}
