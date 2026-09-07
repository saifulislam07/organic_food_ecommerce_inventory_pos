<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Withdrawal;
use App\Services\DashboardSummary;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

/**
 * The panel's front page.
 *
 * Two things are worth guarding here. The numbers have to agree with the
 * screens they summarise — a dashboard that quietly disagrees with the profit
 * report is worse than no dashboard. And a section has to be invisible to
 * anyone who could not have reached it by clicking, because the owner's capital
 * is now on this page.
 */
class DashboardTest extends TestCase
{
    use RefreshDatabase;

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    private function variant(float $price = 1000, float $cost = 600, int $stock = 20): ProductVariant
    {
        $category = Category::firstOrCreate(['slug' => 'fruits'], ['name' => 'Fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar',
            'slug' => 'himsagar-'.uniqid(),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'price' => $price,
            'cost_price' => $cost,
            'stock' => $stock,
        ]);
    }

    private function order(array $attributes = [], ?ProductVariant $variant = null): Order
    {
        $variant ??= $this->variant();

        $order = Order::create(array_merge([
            'customer_name' => 'Karim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'subtotal' => 1000,
            'delivery_charge' => 60,
            'total' => 1060,
            'status' => 'delivered',
            'payment_method' => 'cash',
            'source' => 'website',
        ], $attributes));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $variant->product_id,
            'product_variant_id' => $variant->id,
            'product_name' => $variant->product->name,
            'variant_name' => $variant->name,
            'quantity' => 1,
            'unit_price' => 1000,
            'total' => 1000,
        ]);

        return $order;
    }

    /** Backdating needs forceFill: created_at is not a fillable column. */
    private function orderOn(string $when, array $attributes = [], ?ProductVariant $variant = null): Order
    {
        $order = $this->order($attributes, $variant);

        $order->forceFill(['created_at' => $when])->save();

        return $order;
    }

    /* -------------------------------------------------------- the numbers */

    public function test_todays_sales_are_separated_from_the_months(): void
    {
        $this->order(['total' => 1000]);
        $this->orderOn(now()->subDays(3)->toDateTimeString(), ['total' => 500]);
        // Last month is neither today nor this month.
        $this->orderOn(now()->subMonthNoOverflow()->startOfMonth()->toDateTimeString(), ['total' => 9999]);

        $sales = app(DashboardSummary::class)->sales();

        $this->assertSame(1000.0, $sales['today']['revenue']);
        $this->assertSame(1, $sales['today']['orders']);

        // Today plus three days ago, both inside this month.
        $this->assertSame(1500.0, $sales['month']['revenue']);
        $this->assertSame(2, $sales['month']['orders']);

        $this->assertSame(3, $sales['all']['orders']);
        $this->assertSame(11499.0, $sales['all']['revenue']);
    }

    /** A cancelled order is not money, on any of the three ranges. */
    public function test_cancelled_orders_are_counted_but_not_earned(): void
    {
        $this->order(['total' => 1000, 'status' => 'cancelled']);

        $sales = app(DashboardSummary::class)->sales();

        $this->assertSame(1, $sales['today']['orders']);
        $this->assertSame(0.0, $sales['today']['revenue']);
    }

    /**
     * The dashboard hands the profit question to the report rather than
     * answering it again, so the two can never drift apart.
     */
    public function test_the_profit_figure_matches_the_profit_report(): void
    {
        $this->order(['total' => 1000]);
        Expense::create([
            'title' => 'Packing', 'category' => 'Packing', 'amount' => 200,
            'expense_date' => now()->toDateString(), 'paid_from' => 'cash',
        ]);

        $dashboard = app(DashboardSummary::class)->money();
        $report = \App\Services\ProfitLossReport::between(
            now()->startOfMonth()->toDateString(),
            now()->toDateString(),
        )->summary();

        $this->assertSame($report['net_profit'], $dashboard['net_profit']);
        $this->assertSame($report['gross_profit'], $dashboard['gross_profit']);
        $this->assertSame($report['expenses'], $dashboard['expenses']);
    }

    public function test_investor_capital_stays_out_of_the_profit_figure(): void
    {
        $investor = Investor::create(['name' => 'Saiful', 'is_active' => true]);

        Investment::create([
            'investor_id' => $investor->id, 'amount' => 500000,
            'invested_at' => now()->toDateString(), 'received_in' => 'bank',
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 20000,
            'withdrawn_at' => now()->toDateString(), 'paid_from' => 'bank',
        ]);

        $summary = app(DashboardSummary::class);

        $this->assertSame(0.0, $summary->money()['net_profit']);

        $capital = $summary->capital();
        $this->assertSame(500000.0, $capital['invested']);
        $this->assertSame(20000.0, $capital['withdrawn']);
        $this->assertSame(480000.0, $capital['in_business']);
    }

    public function test_attention_counts_split_low_stock_from_none_at_all(): void
    {
        $this->variant(stock: 0);
        $this->variant(stock: 2);
        $this->variant(stock: 50);
        $this->order(['status' => 'pending']);

        $attention = app(DashboardSummary::class)->attention();

        $this->assertSame(1, $attention['out_of_stock']);
        $this->assertSame(1, $attention['low_stock']);
        $this->assertSame(1, $attention['pending_orders']);
    }

    public function test_the_pipeline_shows_every_status_even_at_zero(): void
    {
        $this->order(['status' => 'pending']);

        $pipeline = app(DashboardSummary::class)->pipeline();

        foreach (['pending', 'confirmed', 'processing', 'shipped', 'delivered', 'cancelled'] as $status) {
            $this->assertArrayHasKey($status, $pipeline);
        }

        $this->assertSame(1, $pipeline['pending']);
        $this->assertSame(0, $pipeline['delivered']);
    }

    public function test_best_sellers_come_from_this_months_earned_orders(): void
    {
        $variant = $this->variant();

        $this->order(['total' => 1000], $variant);
        $this->order(['total' => 1000, 'status' => 'cancelled'], $variant);
        $this->orderOn(
            now()->subMonthNoOverflow()->startOfMonth()->toDateTimeString(),
            ['total' => 1000],
            $variant
        );

        $top = app(DashboardSummary::class)->topProducts();

        $this->assertCount(1, $top);
        // Only the one delivered order inside this month.
        $this->assertSame(1, (int) $top->first()->units);
    }

    public function test_stock_is_valued_at_both_cost_and_retail(): void
    {
        $this->variant(price: 1000, cost: 600, stock: 10);

        $standing = app(DashboardSummary::class)->standing();

        $this->assertSame(6000.0, $standing['stock_cost']);
        $this->assertSame(10000.0, $standing['stock_retail']);
    }

    /* --------------------------------------------------------- permissions */

    public function test_a_super_admin_sees_every_section(): void
    {
        Investor::create(['name' => 'Saiful', 'is_active' => true]);

        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('Net profit')
            ->assertSee('Investor capital')
            ->assertSee('Orders by status')
            ->assertSee('What the shop holds');
    }

    /**
     * The important one. Capital and profit are the owner's business, and a
     * packer with dashboard access must not read them off the front page.
     */
    public function test_staff_without_the_permission_see_no_money(): void
    {
        $this->seed(PermissionSeeder::class);

        $investor = Investor::create(['name' => 'Saiful', 'is_active' => true]);
        Investment::create([
            'investor_id' => $investor->id, 'amount' => 500000,
            'invested_at' => now()->toDateString(),
        ]);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['dashboard.view', 'orders.view']);

        $html = $this->actingAs($staff->fresh())->get('/admin')->assertOk()->getContent();

        $this->assertStringNotContainsString('Investor capital', $html);
        $this->assertStringNotContainsString('Net profit', $html);
        $this->assertStringNotContainsString('500,000', $html);

        // What they are allowed to do is still there.
        $this->assertStringContainsString('Orders by status', $html);
    }

    public function test_a_section_the_viewer_may_not_see_is_never_queried(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['dashboard.view']);

        DB::enableQueryLog();
        $this->actingAs($staff->fresh())->get('/admin')->assertOk();
        $queries = collect(DB::getQueryLog())->pluck('query')->implode(' ');
        DB::disableQueryLog();

        // Not merely hidden in the view — the work is skipped entirely.
        $this->assertStringNotContainsString('investments', $queries);
        $this->assertStringNotContainsString('withdrawals', $queries);
    }

    public function test_a_quick_action_the_viewer_cannot_use_is_not_offered(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['dashboard.view']);

        $html = $this->actingAs($staff->fresh())->get('/admin')->assertOk()->getContent();

        $this->assertStringNotContainsString('Record Investment', $html);
        $this->assertStringNotContainsString('New Purchase', $html);
    }

    /* --------------------------------------------------------------- cost */

    /**
     * Generous, and here to catch an N+1 rather than to police one query. The
     * dashboard is the most-opened page in the panel, so it is the one where a
     * loop over results costs the most.
     */
    public function test_the_dashboard_stays_within_its_query_budget(): void
    {
        $investor = Investor::create(['name' => 'Saiful', 'is_active' => true]);

        for ($i = 0; $i < 6; $i++) {
            $this->order(['total' => 1000]);
            Investment::create([
                'investor_id' => $investor->id, 'amount' => 1000,
                'invested_at' => now()->toDateString(),
            ]);
            Expense::create([
                'title' => "Expense {$i}", 'category' => 'Other', 'amount' => 50,
                'expense_date' => now()->toDateString(), 'paid_from' => 'cash',
            ]);
        }

        $admin = $this->admin();

        // Warm whatever the layout caches on a first hit.
        $this->actingAs($admin)->get('/admin')->assertOk();

        DB::enableQueryLog();
        $this->actingAs($admin)->get('/admin')->assertOk();
        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        $this->assertLessThanOrEqual(
            40,
            $count,
            "The dashboard ran {$count} queries. Look for a loop that asks a question per row."
        );
    }

    public function test_an_empty_shop_still_renders(): void
    {
        $this->actingAs($this->admin())
            ->get('/admin')
            ->assertOk()
            ->assertSee('No orders yet');
    }
}
