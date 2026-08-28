<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Support\MailSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Tests\TestCase;

class MailSettingsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        MailSettings::forget();
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function validPayload(array $overrides = []): array
    {
        return array_merge([
            'mail_host' => 'smtp.gmail.com',
            'mail_port' => 587,
            'mail_username' => 'shop@mangohut.test',
            'mail_password' => 'app-password',
            'mail_encryption' => 'tls',
            'mail_from_address' => 'orders@mangohut.test',
            'mail_from_name' => 'Mango Hut',
        ], $overrides);
    }

    public function test_an_admin_can_save_smtp_settings(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload())
            ->assertRedirect(route('admin.settings.mail.edit'))
            ->assertSessionHasNoErrors();

        $this->assertSame('smtp.gmail.com', Setting::get('mail_host'));
        $this->assertSame('Mango Hut', Setting::get('mail_from_name'));
    }

    public function test_the_password_is_encrypted_at_rest(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload());

        $stored = Setting::where('key', 'mail_password')->firstOrFail();

        $this->assertSame(Setting::TYPE_SECRET, $stored->type);
        $this->assertNotSame('app-password', $stored->value_en, 'The raw password must not sit in the table.');
        $this->assertStringNotContainsString('app-password', $stored->value_en);

        // ...but it round-trips for the code that needs it.
        $this->assertSame('app-password', Setting::get('mail_password'));
    }

    public function test_leaving_the_password_blank_keeps_the_saved_one(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload());

        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload([
                'mail_password' => '',
                'mail_host' => 'smtp.office365.com',
            ]))
            ->assertSessionHasNoErrors();

        MailSettings::forget();

        $this->assertSame('smtp.office365.com', Setting::get('mail_host'));
        $this->assertSame('app-password', Setting::get('mail_password'));
    }

    public function test_host_port_and_sender_are_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), [])
            ->assertSessionHasErrors(['mail_host', 'mail_port', 'mail_from_address', 'mail_from_name']);
    }

    public function test_the_port_must_be_a_real_port(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload(['mail_port' => 99999]))
            ->assertSessionHasErrors('mail_port');
    }

    public function test_saved_settings_are_pushed_into_the_mail_config(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload());

        MailSettings::forget();
        MailSettings::apply();

        $this->assertSame('smtp', config('mail.default'));
        $this->assertSame('smtp.gmail.com', config('mail.mailers.smtp.host'));
        $this->assertSame(587, config('mail.mailers.smtp.port'));
        $this->assertSame('app-password', config('mail.mailers.smtp.password'));
        $this->assertSame('orders@mangohut.test', config('mail.from.address'));
    }

    public function test_ssl_selects_the_smtps_scheme(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload([
                'mail_encryption' => 'ssl',
                'mail_port' => 465,
            ]));

        MailSettings::forget();
        MailSettings::apply();

        $this->assertSame('smtps', config('mail.mailers.smtp.scheme'));
    }

    public function test_nothing_is_overridden_while_no_host_is_configured(): void
    {
        config(['mail.default' => 'log']);

        MailSettings::apply();

        $this->assertSame('log', config('mail.default'), 'A blank setup must leave .env in charge.');
        $this->assertFalse(MailSettings::isConfigured());
    }

    public function test_a_test_email_can_be_sent_once_configured(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.update'), $this->validPayload());

        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.test'), ['test_email' => 'owner@mangohut.test'])
            ->assertSessionHas('success');

        Mail::assertSentCount(1);
    }

    public function test_the_test_button_refuses_before_anything_is_configured(): void
    {
        Mail::fake();

        $this->actingAs($this->admin())
            ->post(route('admin.settings.mail.test'), ['test_email' => 'owner@mangohut.test'])
            ->assertSessionHasErrors('test_email');

        Mail::assertNothingSent();
    }

    public function test_a_customer_cannot_read_or_change_mail_settings(): void
    {
        $customer = User::factory()->create(['role' => 'customer']);

        $this->actingAs($customer)->get(route('admin.settings.mail.edit'))->assertForbidden();
        $this->actingAs($customer)->post(route('admin.settings.mail.update'), $this->validPayload())->assertForbidden();

        $this->assertDatabaseMissing('settings', ['key' => 'mail_host']);
    }
}
