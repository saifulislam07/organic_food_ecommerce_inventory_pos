<?php

namespace Tests\Feature\Storefront;

use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The footer icons used to be hard-coded handles, so the Facebook and YouTube
 * fields in Site Settings did nothing at all.
 */
class FooterSocialsTest extends TestCase
{
    use RefreshDatabase;

    private function footer(): string
    {
        return $this->get(route('home'))->getContent();
    }

    public function test_each_configured_network_gets_an_icon(): void
    {
        Setting::put('facebook', 'https://facebook.com/mangohut');
        Setting::put('instagram', 'https://instagram.com/mangohut');
        Setting::put('tiktok', 'https://tiktok.com/@mangohut');
        Setting::put('youtube', 'https://youtube.com/@mangohut');

        $html = $this->footer();

        foreach (['facebook', 'instagram', 'tiktok', 'youtube'] as $network) {
            $this->assertStringContainsString("https://{$network}.com", $html, "{$network} link missing");
            $this->assertStringContainsString("bi-{$network}", $html, "{$network} icon missing");
        }
    }

    public function test_an_unset_network_shows_no_icon(): void
    {
        Setting::put('facebook', 'https://facebook.com/mangohut');

        $html = $this->footer();

        $this->assertStringContainsString('bi-facebook', $html);
        $this->assertStringNotContainsString('bi-tiktok', $html);
        $this->assertStringNotContainsString('bi-instagram', $html);
    }

    public function test_whatsapp_is_built_from_the_contact_number(): void
    {
        Setting::put('whatsapp', '01716-952365');

        $this->assertStringContainsString('https://wa.me/8801716952365', $this->footer());
    }

    public function test_a_locally_typed_number_still_produces_a_working_link(): void
    {
        // wa.me needs 8801..., but the shop owner types 01716-952365.
        Setting::put('whatsapp', '01716-952365');

        $this->assertStringContainsString('https://wa.me/8801716952365', $this->footer());
    }

    public function test_no_number_means_no_whatsapp_link(): void
    {
        $html = $this->footer();

        $this->assertStringNotContainsString('wa.me', $html);
    }

    public function test_the_old_hard_coded_handles_are_gone(): void
    {
        $html = $this->footer();

        $this->assertStringNotContainsString('facebook.com/mangohutt', $html);
        $this->assertStringNotContainsString('@mangohut7818', $html);
    }

    public function test_an_admin_can_save_the_new_networks(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'instagram' => ['value_en' => 'https://instagram.com/mangohut', 'value_bn' => 'https://instagram.com/mangohut'],
                'tiktok' => ['value_en' => 'https://tiktok.com/@mangohut', 'value_bn' => 'https://tiktok.com/@mangohut'],
            ])
            ->assertRedirect()
            ->assertSessionHasNoErrors();

        $this->assertSame('https://instagram.com/mangohut', Setting::get('instagram'));
        $this->assertSame('https://tiktok.com/@mangohut', Setting::get('tiktok'));
    }

    public function test_a_social_field_must_be_a_url(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)
            ->post(route('admin.settings.update'), [
                'tiktok' => ['value_en' => 'javascript:alert(1)', 'value_bn' => 'javascript:alert(1)'],
            ])
            ->assertSessionHasErrors('tiktok.value_en');
    }
}
