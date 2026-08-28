<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Order;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use App\Notifications\NewOrderReceived;
use App\Notifications\OrderPlaced;
use App\Notifications\OrderStatusChanged;
use App\Support\MailSettings;
use App\Support\OrderNotifier;
use App\Support\SmsSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Notifications\AnonymousNotifiable;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Notification;
use Tests\TestCase;

class OrderNotificationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        MailSettings::forget();
        SmsSettings::forget();
    }

    private function configureChannels(): void
    {
        MailSettings::save([
            'mail_host' => 'smtp.test',
            'mail_port' => '587',
            'mail_from_address' => 'shop@mangohut.test',
            'mail_from_name' => 'Mango Hut',
        ]);

        SmsSettings::save([
            'sms_driver' => 'bulksmsbd',
            'sms_sender_id' => 'MangoHut',
            'sms_api_key' => 'key',
        ]);

        MailSettings::forget();
        SmsSettings::forget();
    }

    private function catalog(): ProductVariant
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango',
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '3 কেজি',
            'price' => 1200,
            'stock' => 10,
        ]);
    }

    private function order(array $attributes = []): Order
    {
        return Order::create(array_merge([
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Dhaka',
            'subtotal' => 1200,
            'discount_amount' => 0,
            'delivery_charge' => 60,
            'total' => 1260,
            'status' => 'pending',
            'payment_method' => 'cod',
            'source' => 'website',
        ], $attributes));
    }

    /* ------------------------------------------------------------- placed */

    public function test_placing_an_order_on_the_website_alerts_the_admin_and_the_shopper(): void
    {
        Notification::fake();
        $this->configureChannels();

        $admin = User::factory()->superAdmin()->create();
        $variant = $this->catalog();

        $this->postJson(route('cart.add'), [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Mirpur, Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        Notification::assertSentTo($admin, NewOrderReceived::class);
        Notification::assertSentTo(new AnonymousNotifiable, OrderPlaced::class);
    }

    public function test_the_admin_alert_uses_the_database_channel_so_it_works_with_nothing_configured(): void
    {
        $admin = User::factory()->superAdmin()->create();

        app(OrderNotifier::class)->placed($this->order());

        $this->assertSame(1, $admin->notifications()->count());
        $this->assertSame('new_order', $admin->notifications()->first()->data['type']);
    }

    public function test_a_shopper_gets_nothing_while_no_channel_is_configured(): void
    {
        Notification::fake();

        app(OrderNotifier::class)->placed($this->order());

        Notification::assertNothingSentTo(new AnonymousNotifiable);
    }

    public function test_the_sms_channel_is_used_once_a_gateway_exists(): void
    {
        $this->configureChannels();
        Http::fake(['*' => Http::response(['response_code' => 202, 'message_id' => '1'], 200)]);

        $order = $this->order();

        (new AnonymousNotifiable)
            ->route('sms', $order->customer_phone)
            ->notify(new OrderPlaced($order));

        Http::assertSent(fn ($request) => $request['number'] === '8801711111111'
            && str_contains($request['message'], $order->order_number));
    }

    /* ------------------------------------------------------------- status */

    public function test_a_meaningful_status_change_notifies_the_shopper(): void
    {
        Notification::fake();
        $this->configureChannels();

        $admin = User::factory()->superAdmin()->create();
        $order = $this->order();

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'shipped'])
            ->assertOk();

        Notification::assertSentTo(new AnonymousNotifiable, OrderStatusChanged::class);
    }

    public function test_processing_is_an_internal_step_and_stays_quiet(): void
    {
        Notification::fake();
        $this->configureChannels();

        $admin = User::factory()->superAdmin()->create();
        $order = $this->order();

        $this->actingAs($admin)
            ->patchJson(route('admin.orders.updateStatus', $order), ['status' => 'processing'])
            ->assertOk();

        Notification::assertNothingSentTo(new AnonymousNotifiable);
    }

    public function test_saving_the_same_status_again_sends_nothing(): void
    {
        Notification::fake();
        $this->configureChannels();

        $order = $this->order(['status' => 'shipped']);

        app(OrderNotifier::class)->statusChanged($order, 'shipped');

        Notification::assertNothingSentTo(new AnonymousNotifiable);
    }

    /* ------------------------------------------------------------ failure */

    public function test_a_broken_gateway_never_costs_the_shop_an_order(): void
    {
        $this->configureChannels();
        Http::fake(fn () => throw new \RuntimeException('gateway exploded'));

        $variant = $this->catalog();

        $this->postJson(route('cart.add'), [
            'product_id' => $variant->product_id,
            'variant_id' => $variant->id,
            'quantity' => 1,
        ])->assertOk();

        $this->post(route('checkout.store'), [
            'customer_name' => 'Rahim',
            'customer_phone' => '01711111111',
            'customer_address' => 'Mirpur, Dhaka',
            'customer_area' => 'dhaka_inside',
            'delivery_type' => 'home',
        ])->assertRedirect();

        $this->assertDatabaseHas('orders', ['customer_name' => 'Rahim']);
    }

    /* --------------------------------------------------------- admin page */

    public function test_the_admin_can_read_and_clear_notifications(): void
    {
        $admin = User::factory()->superAdmin()->create();
        $order = $this->order();

        app(OrderNotifier::class)->placed($order);

        $this->actingAs($admin)->get(route('admin.notifications.index'))
            ->assertOk()
            ->assertSee($order->order_number);

        $notification = $admin->notifications()->firstOrFail();

        $this->actingAs($admin)
            ->post(route('admin.notifications.read', $notification->id))
            ->assertRedirect(route('admin.orders.show', $order->id));

        $this->assertNotNull($admin->notifications()->firstOrFail()->read_at);
    }

    public function test_mark_all_read_clears_the_bell(): void
    {
        $admin = User::factory()->superAdmin()->create();

        app(OrderNotifier::class)->placed($this->order());
        app(OrderNotifier::class)->placed($this->order());

        $this->assertSame(2, $admin->unreadNotifications()->count());

        $this->actingAs($admin)->post(route('admin.notifications.readAll'))->assertRedirect();

        $this->assertSame(0, $admin->fresh()->unreadNotifications()->count());
    }

    public function test_one_admin_cannot_open_another_admins_notification(): void
    {
        $mine = User::factory()->superAdmin()->create();
        $theirs = User::factory()->superAdmin()->create();

        app(OrderNotifier::class)->placed($this->order());

        $theirNotification = $theirs->notifications()->firstOrFail();

        $this->actingAs($mine)
            ->post(route('admin.notifications.read', $theirNotification->id))
            ->assertNotFound();
    }
}
