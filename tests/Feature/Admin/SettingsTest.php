<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The settings form used to write whatever it was handed — every posted key
 * landed in the table, with the row's `type` taken from a hidden input.
 */
class SettingsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->create(['role' => 'admin']);
    }

    public function test_known_settings_are_saved_in_both_languages(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'site_title' => ['value_en' => 'Mango Hut', 'value_bn' => 'ম্যাঙ্গো হাট'],
                'shipping_fee_inside' => ['value_en' => '80', 'value_bn' => '80'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertDatabaseHas('settings', [
            'key' => 'site_title',
            'value_en' => 'Mango Hut',
            'value_bn' => 'ম্যাঙ্গো হাট',
            'type' => 'text',
        ]);

        $this->assertSame('80', Setting::get('shipping_fee_inside'));
    }

    public function test_unknown_keys_are_ignored(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'site_title' => ['value_en' => 'Mango Hut', 'value_bn' => 'ম্যাঙ্গো হাট'],
                'is_admin' => ['value_en' => 'yes', 'type' => 'text'],
                'anything_at_all' => ['value_en' => 'injected'],
            ])
            ->assertRedirect();

        $this->assertDatabaseMissing('settings', ['key' => 'is_admin']);
        $this->assertDatabaseMissing('settings', ['key' => 'anything_at_all']);
        $this->assertSame(1, Setting::count());
    }

    public function test_the_row_type_comes_from_the_server_not_the_form(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'address' => ['value_en' => 'Chapainawabganj', 'value_bn' => 'চাঁপাইনবাবগঞ্জ', 'type' => 'image'],
            ])
            ->assertRedirect();

        $this->assertDatabaseHas('settings', ['key' => 'address', 'type' => 'textarea']);
    }

    public function test_fees_must_be_numeric(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'shipping_fee_inside' => ['value_en' => 'free please', 'value_bn' => 'free please'],
            ])
            ->assertSessionHasErrors('shipping_fee_inside.value_en');

        $this->assertDatabaseMissing('settings', ['key' => 'shipping_fee_inside']);
    }

    public function test_social_links_must_be_urls(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'facebook' => ['value_en' => 'javascript:alert(1)', 'value_bn' => 'javascript:alert(1)'],
            ])
            ->assertSessionHasErrors('facebook.value_en');
    }

    public function test_the_logo_must_be_an_image_within_the_size_limit(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'logo' => ['value_en' => UploadedFile::fake()->create('payload.php', 40, 'application/x-php')],
            ])
            ->assertSessionHasErrors('logo.value_en');

        $this->assertDatabaseMissing('settings', ['key' => 'logo']);
    }

    public function test_replacing_the_logo_deletes_the_previous_file(): void
    {
        Storage::fake('public');

        $this->actingAs($this->admin())->post(route('admin.settings.update'), [
            'logo' => ['value_en' => UploadedFile::fake()->image('old-logo.png')],
        ])->assertRedirect();

        $first = Setting::where('key', 'logo')->firstOrFail()->value_en;
        Storage::disk('public')->assertExists($first);

        $this->actingAs($this->admin())->post(route('admin.settings.update'), [
            'logo' => ['value_en' => UploadedFile::fake()->image('new-logo.png')],
        ])->assertRedirect();

        $second = Setting::where('key', 'logo')->firstOrFail()->value_en;

        $this->assertNotSame($first, $second);
        Storage::disk('public')->assertMissing($first);
        Storage::disk('public')->assertExists($second);
    }

    public function test_a_field_the_form_did_not_submit_is_left_alone(): void
    {
        Setting::create(['key' => 'phone', 'value_en' => '01700000000', 'value_bn' => '01700000000', 'type' => 'text']);

        $this->actingAs($this->admin())
            ->post(route('admin.settings.update'), [
                'site_title' => ['value_en' => 'Mango Hut', 'value_bn' => 'ম্যাঙ্গো হাট'],
            ])
            ->assertRedirect();

        $this->assertSame('01700000000', Setting::get('phone'));
    }

    public function test_a_customer_cannot_reach_the_settings_form(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->post(route('admin.settings.update'), [
                'site_title' => ['value_en' => 'Hacked', 'value_bn' => 'Hacked'],
            ])
            ->assertForbidden();

        $this->assertDatabaseMissing('settings', ['value_en' => 'Hacked']);
    }
}
