<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The printed invoice.
 *
 * Almost none of this is visible from a passing page render, which is why it is
 * tested: paper size, the brand mark and the total in words are the three
 * things that make it a document rather than a web page, and all three fail
 * silently — the page still returns 200 with the logo missing, the words gone,
 * and the sheet quietly reflowed to A4.
 */
class InvoiceTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flush();
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function order(array $overrides = []): Order
    {
        $category = Category::create(['name' => 'Kids', 'slug' => 'kids', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Cotton Romper', 'slug' => 'cotton-romper',
        ]);
        $variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => '0-3 Months', 'price' => 650, 'stock' => 10,
        ]);

        $order = Order::create(array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'subtotal' => 1300,
            'discount_amount' => 0,
            'delivery_charge' => 60,
            'total' => 1360,
            'status' => 'confirmed',
            'payment_method' => 'cod',
            'source' => 'website',
        ], $overrides));

        OrderItem::create([
            'order_id' => $order->id,
            'product_id' => $product->id,
            'product_variant_id' => $variant->id,
            'product_name' => 'Cotton Romper',
            'variant_name' => '0-3 Months',
            'quantity' => 2,
            'unit_price' => 650,
            'total' => 1300,
        ]);

        return $order->fresh();
    }

    private function render(Order $order): string
    {
        return $this->actingAs($this->admin())
            ->get(route('admin.orders.invoice', $order))
            ->assertOk()
            ->getContent();
    }

    /* --------------------------------------------------------------- paper */

    public function test_it_is_laid_out_for_a5_paper(): void
    {
        $html = $this->render($this->order());

        $this->assertStringContainsString('size: A5 portrait', $html);
        // The on-screen sheet is drawn at the same width it prints at, so the
        // preview is not quietly a different document from the print.
        $this->assertStringContainsString('width: 148mm', $html);
    }

    public function test_a_long_order_keeps_its_headings_and_totals_intact_across_pages(): void
    {
        $html = $this->render($this->order());

        $this->assertStringContainsString('display: table-header-group', $html);
        $this->assertStringContainsString('page-break-inside: avoid', $html);
    }

    /* ---------------------------------------------------------------- logo */

    public function test_it_falls_back_to_the_packaged_wordmark(): void
    {
        $html = $this->render($this->order());

        $this->assertStringContainsString('assets/img/logo.webp', $html);
        $this->assertStringContainsString('assets/img/logo.png', $html);
    }

    public function test_an_uploaded_logo_wins(): void
    {
        Setting::put('logo', 'uploads/brand/shop-logo.webp', 'image');
        Setting::flush();

        $html = $this->render($this->order());

        $this->assertStringContainsString('shop-logo.webp', $html);
        $this->assertStringNotContainsString('assets/img/logo.png', $html);
    }

    /* --------------------------------------------------------------- money */

    public function test_the_total_is_written_out_in_words(): void
    {
        $html = $this->render($this->order());

        $this->assertStringContainsString('One Thousand Three Hundred Sixty Taka Only', $html);
    }

    public function test_the_words_follow_the_locale(): void
    {
        app()->setLocale('bn');

        $html = $this->render($this->order());

        $this->assertStringContainsString('এক হাজার তিনশত ষাট টাকা মাত্র', $html);
        $this->assertStringContainsString('ইনভয়েস', $html);
    }

    public function test_a_discount_and_its_coupon_are_shown(): void
    {
        $html = $this->render($this->order([
            'discount_amount' => 100,
            'coupon_code' => 'EID100',
            'total' => 1260,
        ]));

        $this->assertStringContainsString('EID100', $html);
        $this->assertStringContainsString('Discount', $html);
    }

    public function test_a_counter_sale_shows_what_was_handed_over_and_given_back(): void
    {
        $html = $this->render($this->order(['paid_amount' => 1500, 'source' => 'pos']));

        $this->assertStringContainsString('Paid', $html);
        $this->assertStringContainsString('Change', $html);
    }

    public function test_a_part_payment_states_the_balance_still_owed(): void
    {
        $html = $this->render($this->order(['paid_amount' => 1000]));

        $this->assertStringContainsString('Balance due', $html);
        $this->assertStringContainsString('৳360', $html);
    }

    public function test_a_fully_unpaid_order_does_not_restate_its_total_as_a_balance(): void
    {
        // paid_amount is null, so there is no settlement block at all — a
        // "balance due" equal to the total would just be the total twice.
        $html = $this->render($this->order());

        $this->assertStringNotContainsString('Balance due', $html);
    }

    /* -------------------------------------------------------------- typing */

    public function test_the_bangla_font_is_loaded_and_ranked_above_the_generic(): void
    {
        $html = $this->render($this->order());

        $this->assertStringContainsString('Hind+Siliguri', $html);

        // sans-serif ahead of Hind Siliguri would win outright and drop the
        // Bangla to whatever the machine happened to have installed.
        $stack = "'Hind Siliguri', sans-serif";
        $this->assertStringContainsString($stack, $html);
    }

    /* -------------------------------------------------------------- access */

    public function test_a_customer_can_print_their_own_invoice(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);
        $order = $this->order(['user_id' => $customer->id]);

        $this->actingAs($customer)
            ->get(route('customer.orders.invoice', $order->order_number))
            ->assertOk()
            ->assertSee('size: A5 portrait', false);
    }
}
