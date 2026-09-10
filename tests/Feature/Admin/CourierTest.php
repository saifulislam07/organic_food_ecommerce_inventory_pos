<?php

namespace Tests\Feature\Admin;

use App\Courier\CourierManager;
use App\Courier\Drivers\SteadfastDriver;
use App\Models\Category;
use App\Models\Order;
use App\Models\OrderItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use App\Services\CourierDispatch;
use App\Support\CourierSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

/**
 * Handing a parcel to a courier, and letting that courier drive the status.
 *
 * The behaviour worth guarding is the refusal to guess. A provider can return a
 * status word nobody has mapped — they add to their vocabulary without telling
 * anyone — and the one thing that must never happen is a shop's orders being
 * marked delivered on the strength of a string we do not understand.
 */
class CourierTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        Setting::flush();

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Mango', 'slug' => 'mango',
        ]);
        $this->variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => '1 kg', 'price' => 100, 'stock' => 100,
        ]);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

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
            'status' => 'confirmed',
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

    private function configureSteadfast(): void
    {
        CourierSettings::save('steadfast', [
            'api_key' => 'key-123',
            'secret_key' => 'secret-456',
            'base_url' => 'https://portal.packzy.test/api/v1',
        ], true);

        CourierSettings::setDefault('steadfast');
        Setting::flush();
        app(CourierManager::class)->flush();
    }

    /* ----------------------------------------------------------- settings */

    public function test_the_settings_page_lists_every_installed_courier(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.settings.couriers.edit'))
            ->assertOk()
            ->assertSee('Steadfast Courier')
            ->assertSee('Pathao Courier')
            ->assertSee('RedX')
            ->assertSee('Manual / other courier');
    }

    public function test_credentials_can_be_saved_and_a_default_chosen(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.couriers.update'), [
                'couriers' => [
                    'steadfast' => [
                        'enabled' => '1',
                        'api_key' => 'key-123',
                        'secret_key' => 'secret-456',
                        'base_url' => 'https://portal.packzy.test/api/v1',
                    ],
                ],
                'courier_default' => 'steadfast',
            ])
            ->assertRedirect(route('admin.settings.couriers.edit'));

        Setting::flush();

        $this->assertTrue(CourierSettings::isEnabled('steadfast'));
        $this->assertSame('steadfast', CourierSettings::default());
        $this->assertSame('key-123', CourierSettings::credentials('steadfast')['api_key']);
    }

    public function test_a_secret_is_stored_encrypted(): void
    {
        $this->configureSteadfast();

        $raw = Setting::where('key', CourierSettings::key('steadfast', 'api_key'))->value('value_en');

        $this->assertNotSame('key-123', $raw);
        $this->assertSame('key-123', CourierSettings::credentials('steadfast')['api_key']);
    }

    public function test_a_blank_secret_keeps_the_stored_one(): void
    {
        $this->configureSteadfast();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.couriers.update'), [
                'couriers' => ['steadfast' => ['enabled' => '1', 'api_key' => '', 'secret_key' => '']],
            ])->assertRedirect();

        Setting::flush();

        $this->assertSame('key-123', CourierSettings::credentials('steadfast')['api_key']);
    }

    public function test_switching_a_courier_on_requires_its_credentials(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.couriers.update'), [
                'couriers' => ['steadfast' => ['enabled' => '1', 'api_key' => '', 'secret_key' => '']],
            ])
            ->assertSessionHasErrors('couriers.steadfast.api_key');
    }

    public function test_a_courier_left_switched_off_needs_nothing(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.couriers.update'), [
                'couriers' => ['steadfast' => ['enabled' => '0', 'api_key' => '']],
            ])
            ->assertSessionHasNoErrors();
    }

    public function test_a_default_that_was_switched_off_falls_back_to_another(): void
    {
        $this->configureSteadfast();
        CourierSettings::save('manual', ['display_name' => 'Local rider'], true);
        CourierSettings::setDefault('steadfast');

        CourierSettings::save('steadfast', [], false);
        Setting::flush();

        // Never a courier that cannot be used — degrade to one that can.
        $this->assertSame('manual', CourierSettings::default());
    }

    public function test_a_courier_without_credentials_is_not_offered(): void
    {
        CourierSettings::save('steadfast', [], true);
        Setting::flush();

        $keys = array_column(app(CourierManager::class)->usable(), 'key');

        $this->assertNotContains('steadfast', $keys);
    }

    /* ------------------------------------------------------------ booking */

    public function test_booking_stores_the_ids_the_courier_issued(): void
    {
        $this->configureSteadfast();

        Http::fake(['*/create_order' => Http::response([
            'status' => 200,
            'consignment' => [
                'consignment_id' => 1234567,
                'tracking_code' => '15BAAQ12',
                'status' => 'in_review',
            ],
        ])]);

        $order = $this->order();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.sendToCourier', $order), ['courier' => 'steadfast'])
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('steadfast', $order->courier);
        $this->assertSame('1234567', $order->courier_consignment_id);
        $this->assertSame('15BAAQ12', $order->courier_tracking_code);
        $this->assertSame('in_review', $order->courier_status);
        $this->assertNotNull($order->courier_synced_at);
    }

    public function test_the_courier_is_asked_to_collect_what_is_still_owed(): void
    {
        $this->configureSteadfast();

        Http::fake(['*' => Http::response(['status' => 200, 'consignment' => ['consignment_id' => 1]])]);

        // 500 already paid on a 1060 order leaves 560 to collect.
        $order = $this->order(['paid_amount' => 500]);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.sendToCourier', $order), ['courier' => 'steadfast']);

        Http::assertSent(fn ($request) => (float) $request['cod_amount'] === 560.0
            && $request['invoice'] === $order->order_number);
    }

    public function test_booking_moves_a_confirmed_order_to_processing(): void
    {
        $this->configureSteadfast();

        Http::fake(['*' => Http::response(['status' => 200, 'consignment' => ['consignment_id' => 1]])]);

        $order = $this->order(['status' => 'confirmed']);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.sendToCourier', $order), ['courier' => 'steadfast']);

        $this->assertSame('processing', $order->fresh()->status);
    }

    public function test_a_rejected_booking_reports_why_and_changes_no_status(): void
    {
        $this->configureSteadfast();

        Http::fake(['*' => Http::response([
            'status' => 400,
            'errors' => ['recipient_phone' => ['The recipient phone must be 11 digits.']],
        ], 400)]);

        $order = $this->order(['status' => 'confirmed']);

        $this->actingAs($this->admin())
            ->post(route('admin.orders.sendToCourier', $order), ['courier' => 'steadfast'])
            ->assertSessionHasErrors('courier');

        $order->refresh();

        $this->assertSame('confirmed', $order->status);
        $this->assertNull($order->courier_consignment_id);
        $this->assertStringContainsString('11 digits', $order->courier_status_note);
        // Nobody accepted the parcel, so the order is not with a courier.
        $this->assertNull($order->courier);
        $this->assertFalse($order->hasCourier());
    }

    public function test_a_refused_booking_leaves_the_send_button_available_to_try_again(): void
    {
        $this->configureSteadfast();

        Http::fake(['*' => Http::response(['status' => 500, 'message' => 'Server error'], 500)]);

        $order = $this->order();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.sendToCourier', $order), ['courier' => 'steadfast'])
            ->assertSessionHasErrors('courier');

        $this->actingAs($this->admin())
            ->get(route('admin.orders.show', $order))
            ->assertOk()
            ->assertSee('Send to courier')
            ->assertDontSee('Check with courier');
    }

    public function test_a_courier_that_cannot_be_reached_is_reported_not_thrown(): void
    {
        $this->configureSteadfast();

        Http::fake(fn () => throw new ConnectionException('Connection timed out'));

        $order = $this->order();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.sendToCourier', $order), ['courier' => 'steadfast'])
            ->assertSessionHasErrors('courier');
    }

    public function test_a_manual_courier_records_a_consignment_typed_in_by_hand(): void
    {
        CourierSettings::save('manual', ['display_name' => 'Local rider'], true);
        Setting::flush();

        $order = $this->order();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.sendToCourier', $order), [
                'courier' => 'manual',
                'consignment_id' => 'HAND-001',
            ])->assertRedirect();

        $this->assertSame('manual', $order->fresh()->courier);
        $this->assertSame('HAND-001', $order->fresh()->courier_consignment_id);
    }

    /* --------------------------------------------------------------- sync */

    private function booked(string $status = 'shipped'): Order
    {
        $order = $this->order(['status' => $status]);

        $order->forceFill([
            'courier' => 'steadfast',
            'courier_consignment_id' => '1234567',
        ])->save();

        return $order->fresh();
    }

    private function fakeStatus(string $deliveryStatus): void
    {
        Http::fake(['*status_by_cid*' => Http::response([
            'status' => 200,
            'delivery_status' => $deliveryStatus,
        ])]);
    }

    public function test_a_delivered_parcel_moves_the_order_to_delivered(): void
    {
        $this->configureSteadfast();
        $this->fakeStatus('delivered');

        $order = $this->booked();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.syncCourier', $order))
            ->assertRedirect();

        $order->refresh();

        $this->assertSame('delivered', $order->status);
        $this->assertSame('delivered', $order->courier_status);
        $this->assertNotNull($order->delivered_at);
    }

    public function test_a_cancelled_parcel_cancels_the_order(): void
    {
        $this->configureSteadfast();
        $this->fakeStatus('cancelled');

        $order = $this->booked();

        $this->actingAs($this->admin())->post(route('admin.orders.syncCourier', $order));

        $this->assertSame('cancelled', $order->fresh()->status);
    }

    /**
     * Steadfast reports an outcome before it confirms it, and the money is not
     * remitted until it does. Booking revenue on the unconfirmed word would pay
     * the shop for a parcel that may yet come back.
     */
    public function test_an_outcome_still_awaiting_the_couriers_approval_is_not_delivered(): void
    {
        $this->configureSteadfast();
        $this->fakeStatus('delivered_approval_pending');

        $order = $this->booked();

        $this->actingAs($this->admin())->post(route('admin.orders.syncCourier', $order));

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertSame('delivered_approval_pending', $order->courier_status);
    }

    public function test_a_status_word_nobody_has_mapped_leaves_the_order_alone(): void
    {
        $this->configureSteadfast();
        $this->fakeStatus('teleported_to_mars');

        $order = $this->booked('shipped');

        $this->actingAs($this->admin())->post(route('admin.orders.syncCourier', $order));

        $order->refresh();

        // Recorded verbatim so somebody can find out what it means; acted on by
        // nobody until they have.
        $this->assertSame('shipped', $order->status);
        $this->assertSame('teleported_to_mars', $order->courier_status);
    }

    public function test_status_words_are_matched_however_the_provider_punctuates_them(): void
    {
        $this->configureSteadfast();
        $this->fakeStatus('Partial Delivered');

        $order = $this->booked();

        $this->actingAs($this->admin())->post(route('admin.orders.syncCourier', $order));

        $this->assertSame('delivered', $order->fresh()->status);
    }

    public function test_syncing_an_order_with_no_courier_says_so(): void
    {
        $this->configureSteadfast();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.syncCourier', $this->order()))
            ->assertSessionHasErrors('courier');
    }

    public function test_a_failed_read_is_recorded_against_the_order(): void
    {
        $this->configureSteadfast();

        Http::fake(['*' => Http::response(['status' => 401, 'message' => 'Unauthorized'], 401)]);

        $order = $this->booked();

        $this->actingAs($this->admin())
            ->post(route('admin.orders.syncCourier', $order))
            ->assertSessionHasErrors('courier');

        $order->refresh();

        $this->assertSame('shipped', $order->status);
        $this->assertNotNull($order->courier_synced_at);
        $this->assertStringContainsString('Unauthorized', $order->courier_status_note);
    }

    /* ------------------------------------------------------------ command */

    public function test_the_sync_command_sweeps_parcels_still_in_flight(): void
    {
        $this->configureSteadfast();
        $this->fakeStatus('delivered');

        $inFlight = $this->booked('shipped');

        $this->artisan('couriers:sync')
            ->expectsOutputToContain('1 parcel(s) checked, 1 moved')
            ->assertSuccessful();

        $this->assertSame('delivered', $inFlight->fresh()->status);
    }

    public function test_the_sync_command_leaves_finished_orders_alone(): void
    {
        $this->configureSteadfast();
        $this->fakeStatus('delivered');

        $done = $this->booked('cancelled');

        $this->artisan('couriers:sync')->assertSuccessful();

        $this->assertSame('cancelled', $done->fresh()->status);
        Http::assertNothingSent();
    }

    public function test_the_sync_command_is_quiet_when_no_courier_is_set_up(): void
    {
        $this->artisan('couriers:sync')
            ->expectsOutputToContain('No courier is switched on')
            ->assertSuccessful();
    }

    public function test_the_sync_command_rejects_a_courier_that_does_not_exist(): void
    {
        $this->configureSteadfast();

        $this->artisan('couriers:sync --courier=fedex')->assertFailed();
    }

    /* ------------------------------------------------------------- driver */

    public function test_an_incomplete_driver_reports_itself_unconfigured(): void
    {
        $this->assertFalse((new SteadfastDriver(['api_key' => 'only-one']))->isConfigured());
        $this->assertTrue((new SteadfastDriver([
            'api_key' => 'a', 'secret_key' => 'b',
        ]))->isConfigured());
    }

    public function test_dispatch_refuses_when_nothing_is_set_up(): void
    {
        $result = app(CourierDispatch::class)->book($this->order());

        $this->assertFalse($result->booked);
        $this->assertStringContainsString('No courier is set up', $result->error);
    }
}
