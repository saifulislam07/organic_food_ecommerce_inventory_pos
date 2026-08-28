<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Sms\Drivers\BulkSmsBdDriver;
use App\Sms\Drivers\LogDriver;
use App\Sms\SmsManager;
use App\Support\SmsSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class SmsSettingsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        SmsSettings::forget();
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function configureGateway(array $overrides = []): void
    {
        SmsSettings::save(array_merge([
            'sms_driver' => 'bulksmsbd',
            'sms_sender_id' => 'MangoHut',
            'sms_api_key' => 'secret-key',
            'sms_endpoint' => 'https://bulksmsbd.net/api/smsapi',
        ], $overrides));

        SmsSettings::forget();
    }

    /* ------------------------------------------------------------ numbers */

    public static function numbers(): array
    {
        return [
            'local' => ['01712345678', '8801712345678'],
            'international' => ['8801712345678', '8801712345678'],
            'plus prefixed' => ['+8801712345678', '8801712345678'],
            'spaced' => ['017 1234 5678', '8801712345678'],
            'dashed' => ['01712-345678', '8801712345678'],
            'missing zero' => ['1712345678', '8801712345678'],
        ];
    }

    #[DataProvider('numbers')]
    public function test_it_normalises_bangladeshi_numbers(string $input, string $expected): void
    {
        $this->assertSame($expected, SmsManager::normalise($input));
    }

    public function test_unusable_numbers_are_rejected_rather_than_sent(): void
    {
        $this->assertNull(SmsManager::normalise(null));
        $this->assertNull(SmsManager::normalise(''));
        $this->assertNull(SmsManager::normalise('abc'));
        $this->assertNull(SmsManager::normalise('12345'));
    }

    public function test_sending_to_a_blank_number_fails_without_touching_the_gateway(): void
    {
        Http::fake();

        $result = app(SmsManager::class)->send(null, 'Hello');

        $this->assertFalse($result->sent);
        Http::assertNothingSent();
    }

    /* ------------------------------------------------------------- driver */

    public function test_it_falls_back_to_the_log_driver_when_nothing_is_configured(): void
    {
        $this->assertInstanceOf(LogDriver::class, app(SmsManager::class)->driver());
        $this->assertFalse(SmsSettings::isConfigured());
    }

    public function test_configuring_a_gateway_switches_the_driver(): void
    {
        $this->configureGateway();

        $this->assertInstanceOf(BulkSmsBdDriver::class, app(SmsManager::class)->driver());
        $this->assertTrue(SmsSettings::isConfigured());
    }

    public function test_a_gateway_without_credentials_is_not_considered_configured(): void
    {
        SmsSettings::save(['sms_driver' => 'bulksmsbd', 'sms_sender_id' => '', 'sms_api_key' => '']);
        SmsSettings::forget();

        $this->assertFalse(SmsSettings::isConfigured());
    }

    /* ------------------------------------------------------------ gateway */

    public function test_a_successful_gateway_response_is_reported_as_sent(): void
    {
        $this->configureGateway();

        Http::fake([
            'bulksmsbd.net/*' => Http::response(['response_code' => 202, 'message_id' => '9988'], 200),
        ]);

        $result = app(SmsManager::class)->send('01712345678', 'Your order is confirmed.');

        $this->assertTrue($result->sent);
        $this->assertSame('9988', $result->reference);

        Http::assertSent(function ($request) {
            return $request['number'] === '8801712345678'
                && $request['senderid'] === 'MangoHut'
                && $request['api_key'] === 'secret-key';
        });
    }

    public function test_a_rejected_message_reports_the_gateway_reason(): void
    {
        $this->configureGateway();

        Http::fake([
            'bulksmsbd.net/*' => Http::response(['response_code' => 1006, 'error_message' => 'Invalid sender id'], 200),
        ]);

        $result = app(SmsManager::class)->send('01712345678', 'Hello');

        $this->assertFalse($result->sent);
        $this->assertStringContainsString('Invalid sender id', $result->error);
    }

    public function test_an_http_failure_does_not_throw(): void
    {
        $this->configureGateway();

        Http::fake(['bulksmsbd.net/*' => Http::response('gateway down', 500)]);

        $result = app(SmsManager::class)->send('01712345678', 'Hello');

        $this->assertFalse($result->sent);
        $this->assertStringContainsString('HTTP 500', $result->error);
    }

    /* -------------------------------------------------------------- admin */

    public function test_an_admin_can_save_gateway_settings(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.update'), [
                'sms_driver' => 'bulksmsbd',
                'sms_sender_id' => 'MangoHut',
                'sms_api_key' => 'secret-key',
            ])
            ->assertRedirect(route('admin.settings.sms.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('MangoHut', Setting::get('sms_sender_id'));
    }

    public function test_the_api_key_is_encrypted_at_rest(): void
    {
        $this->configureGateway();

        $stored = Setting::where('key', 'sms_api_key')->firstOrFail();

        $this->assertSame(Setting::TYPE_SECRET, $stored->type);
        $this->assertStringNotContainsString('secret-key', $stored->value_en);
        $this->assertSame('secret-key', Setting::get('sms_api_key'));
    }

    public function test_leaving_the_api_key_blank_keeps_the_saved_one(): void
    {
        $this->configureGateway();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.update'), [
                'sms_driver' => 'bulksmsbd',
                'sms_sender_id' => 'NewSender',
                'sms_api_key' => '',
            ])
            ->assertSessionHasNoErrors();

        SmsSettings::forget();

        $this->assertSame('NewSender', Setting::get('sms_sender_id'));
        $this->assertSame('secret-key', Setting::get('sms_api_key'));
    }

    public function test_a_live_gateway_requires_a_sender_id(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.update'), [
                'sms_driver' => 'bulksmsbd',
                'sms_sender_id' => '',
                'sms_api_key' => 'key',
            ])
            ->assertSessionHasErrors('sms_sender_id');
    }

    public function test_log_only_needs_no_credentials(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.update'), ['sms_driver' => 'log'])
            ->assertSessionHasNoErrors();
    }

    public function test_an_unknown_provider_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.update'), ['sms_driver' => 'some-other-gateway'])
            ->assertSessionHasErrors('sms_driver');
    }

    public function test_the_test_button_sends_through_the_configured_gateway(): void
    {
        $this->configureGateway();

        Http::fake(['bulksmsbd.net/*' => Http::response(['response_code' => 202, 'message_id' => '1'], 200)]);

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.test'), ['test_number' => '01712345678'])
            ->assertSessionHas('success');

        Http::assertSentCount(1);
    }

    public function test_the_test_button_rejects_a_nonsense_number(): void
    {
        Http::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.sms.test'), ['test_number' => 'abcd'])
            ->assertSessionHasErrors('test_number');

        Http::assertNothingSent();
    }

    public function test_a_customer_cannot_read_or_change_sms_settings(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.settings.sms.edit'))->assertForbidden();
        $this->actingAs($customer)
            ->post(route('admin.settings.sms.update'), ['sms_driver' => 'log'])
            ->assertForbidden();
    }
}
