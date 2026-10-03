<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\IncompleteOrder;
use App\Models\LandingPage;
use App\Models\LandingPageItem;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A checkout someone starts and abandons is saved as a lead, so the shop can
 * call them; finishing the order closes it.
 */
class IncompleteOrderTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar',
            'slug' => 'himsagar',
            'is_active' => true,
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'price' => 500,
            'stock' => 20,
        ]);
    }

    private function addToCart(int $quantity = 2): void
    {
        $this->postJson(route('cart.add'), [
            'product_id' => $this->variant->product_id,
            'variant_id' => $this->variant->id,
            'quantity' => $quantity,
        ])->assertOk();
    }

    private function details(array $overrides = []): array
    {
        return array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Mirpur 10, Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ], $overrides);
    }

    private function landingPage(): LandingPage
    {
        $page = LandingPage::create([
            'slug' => 'mango-offer',
            'internal_name' => 'Mango campaign',
            'headline' => 'Mango',
            'selection_mode' => LandingPage::MODE_SINGLE,
            'delivery_mode' => 'custom',
            'delivery_inside' => 60,
            'delivery_outside' => 120,
            'is_active' => true,
        ]);

        LandingPageItem::create([
            'landing_page_id' => $page->id,
            'product_id' => $this->variant->product_id,
            'product_variant_id' => $this->variant->id,
            'offer_price' => 450,
            'is_default' => true,
            'min_qty' => 1,
            'max_qty' => 5,
        ]);

        return $page->fresh('items');
    }

    public function test_checkout_capture_saves_the_cart_and_details_once_the_phone_is_complete(): void
    {
        $this->addToCart();

        $this->post(route('checkout.capture'), $this->details(['customer_phone' => '0171']))->assertNoContent();
        $this->assertDatabaseCount('incomplete_orders', 0);

        $this->post(route('checkout.capture'), $this->details(['customer_phone' => '+880 1711-111111']))->assertNoContent();
        $this->post(route('checkout.capture'), $this->details(['customer_address' => 'Uttara']))->assertNoContent();

        $this->assertDatabaseCount('incomplete_orders', 1);
        $lead = IncompleteOrder::first();
        $this->assertSame('01711111111', $lead->customer_phone);
        $this->assertSame('Uttara', $lead->customer_address);
        $this->assertSame('new', $lead->status);
        $this->assertSame(2, $lead->items[0]['quantity']);
        $this->assertEquals(1000, $lead->subtotal);
        $this->assertEquals(1060, $lead->total);
    }

    public function test_capture_without_a_cart_saves_nothing(): void
    {
        $this->post(route('checkout.capture'), $this->details())->assertNoContent();

        $this->assertDatabaseCount('incomplete_orders', 0);
    }

    public function test_placing_the_order_marks_the_lead_converted(): void
    {
        $this->addToCart();
        $this->post(route('checkout.capture'), $this->details());

        $this->post(route('checkout.store'), $this->details())->assertRedirect();

        $order = Order::firstOrFail();
        $lead = IncompleteOrder::firstOrFail();
        $this->assertSame('converted', $lead->status);
        $this->assertSame($order->id, $lead->order_id);
    }

    public function test_landing_capture_prices_from_the_page_and_closes_on_order(): void
    {
        $page = $this->landingPage();
        $form = $this->details(['quantity' => 3, 'item_id' => $page->items->first()->id]);

        $this->post(route('landing.capture', $page->slug), $form)->assertNoContent();

        $lead = IncompleteOrder::firstOrFail();
        $this->assertSame('landing', $lead->source);
        $this->assertSame($page->id, $lead->landing_page_id);
        $this->assertEquals(1350, $lead->subtotal);

        $this->post(route('landing.order', $page->slug), $form)->assertRedirect();

        $this->assertSame('converted', $lead->fresh()->status);

        // A save still in flight after the order must not reopen it.
        $this->post(route('landing.capture', $page->slug), $form)->assertNoContent();
        $this->assertSame(0, IncompleteOrder::open()->count());
    }

    public function test_landing_capture_ignores_bots(): void
    {
        $page = $this->landingPage();

        $this->post(route('landing.capture', $page->slug), $this->details(['website' => 'spam']))->assertNoContent();

        $this->assertDatabaseCount('incomplete_orders', 0);
    }

    public function test_admin_can_note_a_call_and_convert_the_lead(): void
    {
        $this->addToCart(3);
        $this->post(route('checkout.capture'), $this->details());
        $lead = IncompleteOrder::firstOrFail();
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get(route('admin.incomplete-orders.index'))
            ->assertOk()
            ->assertSee('01711111111');

        $this->actingAs($admin)->put(route('admin.incomplete-orders.update', $lead), [
            'status' => 'called',
            'admin_note' => 'Delivery charge felt high',
        ])->assertRedirect();

        $lead->refresh();
        $this->assertSame('called', $lead->status);
        $this->assertSame($admin->id, $lead->handled_by);
        $this->assertNotNull($lead->called_at);

        $this->actingAs($admin)->post(route('admin.incomplete-orders.convert', $lead))->assertRedirect();

        $order = Order::with('items')->firstOrFail();
        $this->assertSame('website', $order->source);
        $this->assertSame(3, $order->items->first()->quantity);
        $this->assertSame(17, $this->variant->fresh()->stock);
        $this->assertSame('converted', $lead->fresh()->status);

        // Converting twice must not make a second order.
        $this->actingAs($admin)->post(route('admin.incomplete-orders.convert', $lead));
        $this->assertSame(1, Order::count());
    }

    public function test_staff_need_the_permission(): void
    {
        $this->seed(PermissionSeeder::class);
        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['orders.view']);

        $this->actingAs($staff->fresh())->get(route('admin.incomplete-orders.index'))->assertForbidden();

        $staff->givePermissionTo('incomplete-orders.view');

        $this->actingAs($staff->fresh())->get(route('admin.incomplete-orders.index'))->assertOk();
    }

    public function test_old_unconverted_leads_are_pruned(): void
    {
        $old = IncompleteOrder::create(['visitor_key' => 'a', 'customer_phone' => '01711111111']);
        $converted = IncompleteOrder::create(['visitor_key' => 'b', 'customer_phone' => '01722222222', 'status' => 'converted']);
        $recent = IncompleteOrder::create(['visitor_key' => 'c', 'customer_phone' => '01733333333']);

        IncompleteOrder::whereIn('id', [$old->id, $converted->id])->update(['updated_at' => now()->subDays(100)]);

        $this->artisan('model:prune', ['--model' => [IncompleteOrder::class]])->assertSuccessful();

        $this->assertModelMissing($old);
        $this->assertModelExists($converted);
        $this->assertModelExists($recent);
    }
}
