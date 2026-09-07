<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A shop with something in every corner.
 *
 * DashboardTest checks the arithmetic on a nearly empty shop, which sends every
 * list down its "nothing here yet" branch. This one fills each corner so the
 * campaign table, the low-stock list and the best-seller rows are really
 * rendered — the markup a working panel shows, and the one place a typo in a
 * column name turns into something visible.
 */
class DashboardFullRenderTest extends TestCase
{
    use RefreshDatabase;

    public function test_every_block_renders_with_content(): void
    {
        $admin = User::factory()->superAdmin()->create();
        User::factory()->create(['role' => 'customer']);

        $category = Category::create(['name' => 'Mangoes', 'slug' => 'mangoes', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Himsagar',
            'slug' => 'himsagar', 'is_active' => true,
        ]);

        $good = ProductVariant::create([
            'product_id' => $product->id, 'name' => '1 kg',
            'price' => 1000, 'cost_price' => 600, 'stock' => 40,
        ]);
        ProductVariant::create([
            'product_id' => $product->id, 'name' => '5 kg',
            'price' => 4500, 'cost_price' => 2800, 'stock' => 2,
        ]);
        ProductVariant::create([
            'product_id' => $product->id, 'name' => '10 kg',
            'price' => 8500, 'cost_price' => 5200, 'stock' => 0,
        ]);

        $page = LandingPage::create([
            'slug' => 'eid-mango', 'internal_name' => 'Eid mango combo',
            'headline' => 'খাঁটি হিমসাগর', 'selection_mode' => 'single',
            'delivery_mode' => 'global', 'is_active' => true,
        ]);

        // views is a counter the page increments, not a fillable column.
        $page->forceFill(['views' => 420])->save();

        foreach ([
            ['delivered', 'website', 'cash'],
            ['pending', 'website', 'cod'],
            ['confirmed', 'pos', 'bkash'],
            ['delivered', 'landing', 'cod'],
            ['cancelled', 'website', 'cod'],
        ] as $i => [$status, $source, $method]) {
            $order = Order::create([
                'customer_name' => "Customer {$i}",
                'customer_phone' => '0170000000'.$i,
                'customer_address' => 'Dhaka',
                'subtotal' => 1000, 'delivery_charge' => 60, 'total' => 1060,
                'status' => $status, 'payment_method' => $method, 'source' => $source,
                'landing_page_id' => $source === 'landing' ? $page->id : null,
            ]);

            OrderItem::create([
                'order_id' => $order->id, 'product_id' => $product->id,
                'product_variant_id' => $good->id, 'product_name' => 'Himsagar',
                'variant_name' => '1 kg', 'quantity' => 2, 'unit_price' => 500, 'total' => 1000,
            ]);
        }

        Expense::create([
            'title' => 'Carton boxes', 'category' => 'Packing', 'amount' => 3200,
            'expense_date' => now()->toDateString(), 'paid_from' => 'cash',
        ]);

        $investor = Investor::create(['name' => 'Saiful Islam', 'is_active' => true]);
        Investment::create([
            'investor_id' => $investor->id, 'amount' => 500000,
            'invested_at' => now()->toDateString(), 'received_in' => 'bank',
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 20000,
            'withdrawn_at' => now()->toDateString(), 'paid_from' => 'bank',
        ]);

        $html = $this->actingAs($admin)->get('/admin')->assertOk()->getContent();

        $blocks = [
            'today tile' => 'Today ·',
            'money block' => 'Money — ',
            'net profit' => 'Net profit',
            'accounts table' => 'Where the money sits',
            'capital note' => 'of investor capital in',
            'shop holdings' => 'What the shop holds',
            'investor capital' => 'Investor capital',
            'investor row' => 'Saiful Islam',
            'pipeline' => 'Orders by status',
            'channels' => "'s orders came from",
            'low stock' => 'Running out',
            'low stock row' => '5 kg',
            'best sellers' => 'Best sellers',
            'best seller row' => 'Himsagar',
            'campaigns' => 'Campaign pages',
            'campaign row' => 'Eid mango combo',
            'recent orders' => 'Recent Orders',
            'recent expenses' => 'Recent Expenses',
            'expense row' => 'Carton boxes',
            'quick actions' => 'Record Investment',
        ];

        foreach ($blocks as $label => $needle) {
            $this->assertStringContainsString($needle, $html, "Dashboard is missing: {$label}");
        }

        // The numbers, so this is not merely a page that rendered.
        //
        // Three of the five orders are earned — delivered twice and confirmed
        // once. Pending and cancelled are not; "earned" here is the profit
        // report's own list, which is the point of borrowing it rather than
        // counting delivered orders and drifting apart from it later.
        $this->assertStringContainsString('3,180', $html, 'Revenue is not the earned total.');
        $this->assertStringContainsString('3,200', $html, 'The expense is missing.');
        $this->assertStringContainsString('480,000', $html, 'Capital in the business is missing.');
        $this->assertStringContainsString('420', $html, 'Campaign views are missing.');
    }
}
