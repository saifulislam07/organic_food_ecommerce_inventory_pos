<?php

namespace Tests\Feature\Admin;

use App\Models\Setting;
use App\Models\User;
use App\Support\ChatSettings;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The floating WhatsApp button used to be hard-coded, and rendered a dead link
 * when no number was configured. Both buttons now come from settings.
 */
class ChatSettingsTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        ChatSettings::forget();
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function configure(array $values): void
    {
        ChatSettings::save($values);
    }

    /* --------------------------------------------------------- the links */

    public function test_nothing_floats_on_a_fresh_install(): void
    {
        $this->assertFalse(ChatSettings::anyEnabled());

        $this->get('/')->assertOk()->assertDontSee('chat-float', false);
    }

    public function test_a_whatsapp_number_produces_a_dialable_link(): void
    {
        $this->configure([
            'chat_whatsapp_enabled' => '1',
            'chat_whatsapp_number' => '01716-952365',
            'chat_whatsapp_message_en' => 'Hello!',
        ]);

        $url = ChatSettings::whatsappUrl();

        $this->assertStringStartsWith('https://wa.me/8801716952365', $url);
        $this->assertStringContainsString('text=Hello%21', $url);
    }

    public function test_the_button_falls_back_to_the_shop_number(): void
    {
        Setting::put('whatsapp', '01716-952365');
        $this->configure(['chat_whatsapp_enabled' => '1', 'chat_whatsapp_number' => '']);

        $this->assertStringContainsString('8801716952365', (string) ChatSettings::whatsappUrl());
    }

    public function test_turning_whatsapp_off_hides_it_even_with_a_number(): void
    {
        $this->configure([
            'chat_whatsapp_enabled' => '',
            'chat_whatsapp_number' => '01716-952365',
        ]);

        $this->assertNull(ChatSettings::whatsappUrl());
    }

    public static function messengerHandles(): array
    {
        return [
            'plain username' => ['mangohut.bd'],
            'numeric id' => ['100064123456789'],
            'full page url' => ['https://www.facebook.com/mangohut.bd'],
            'm.me url' => ['m.me/mangohut.bd'],
            'url with tracking' => ['https://facebook.com/mangohut.bd?ref=bookmarks'],
        ];
    }

    #[DataProvider('messengerHandles')]
    public function test_any_way_of_naming_the_page_works(string $input): void
    {
        $this->configure(['chat_messenger_enabled' => '1', 'chat_messenger_id' => $input]);

        $expected = str_contains($input, '100064') ? '100064123456789' : 'mangohut.bd';

        $this->assertSame('https://m.me/'.$expected, ChatSettings::messengerUrl());
    }

    public function test_messenger_stays_hidden_without_a_page(): void
    {
        $this->configure(['chat_messenger_enabled' => '1', 'chat_messenger_id' => '']);

        $this->assertNull(ChatSettings::messengerUrl());
    }

    /* -------------------------------------------------------- the render */

    public function test_both_buttons_reach_the_storefront(): void
    {
        $this->configure([
            'chat_whatsapp_enabled' => '1',
            'chat_whatsapp_number' => '01716-952365',
            'chat_messenger_enabled' => '1',
            'chat_messenger_id' => 'mangohut.bd',
        ]);

        $this->get('/')
            ->assertOk()
            ->assertSee('chat-float--right', false)
            ->assertSee('https://wa.me/8801716952365', false)
            ->assertSee('https://m.me/mangohut.bd', false);
    }

    public function test_the_buttons_can_be_moved_to_the_left(): void
    {
        $this->configure([
            'chat_whatsapp_enabled' => '1',
            'chat_whatsapp_number' => '01716-952365',
            'chat_position' => 'left',
        ]);

        $this->get('/')->assertOk()->assertSee('chat-float--left', false);
    }

    public function test_the_greeting_follows_the_language(): void
    {
        $this->configure([
            'chat_whatsapp_enabled' => '1',
            'chat_whatsapp_number' => '01716-952365',
            'chat_whatsapp_message_en' => 'Hello!',
            'chat_whatsapp_message_bn' => 'হ্যালো!',
        ]);

        app()->setLocale('bn');
        $this->assertSame('হ্যালো!', ChatSettings::whatsappMessage());

        app()->setLocale('en');
        $this->assertSame('Hello!', ChatSettings::whatsappMessage());
    }

    public function test_a_missing_translation_falls_back_to_english(): void
    {
        $this->configure(['chat_whatsapp_message_en' => 'Hello!', 'chat_whatsapp_message_bn' => '']);

        app()->setLocale('bn');

        $this->assertSame('Hello!', ChatSettings::whatsappMessage());
    }

    /* ---------------------------------------------------------- the form */

    public function test_an_admin_can_save_both_channels(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.chat.update'), [
                'chat_whatsapp_enabled' => '1',
                'chat_whatsapp_number' => '01716-952365',
                'chat_whatsapp_message_en' => 'Hello!',
                'chat_messenger_enabled' => '1',
                'chat_messenger_id' => 'mangohut.bd',
                'chat_position' => 'left',
            ])
            ->assertRedirect(route('admin.settings.chat.edit'))
            ->assertSessionHas('success');

        ChatSettings::forget();

        $this->assertStringContainsString('8801716952365', (string) ChatSettings::whatsappUrl());
        $this->assertSame('https://m.me/mangohut.bd', ChatSettings::messengerUrl());
        $this->assertSame('left', ChatSettings::position());
    }

    public function test_a_number_wa_me_cannot_dial_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.settings.chat.update'), [
                'chat_whatsapp_enabled' => '1',
                'chat_whatsapp_number' => 'call me maybe',
            ])
            ->assertSessionHasErrors('chat_whatsapp_number');

        ChatSettings::forget();
        $this->assertNull(ChatSettings::whatsappUrl());
    }

    public function test_unticking_the_switch_turns_the_button_off(): void
    {
        $this->configure(['chat_whatsapp_enabled' => '1', 'chat_whatsapp_number' => '01716-952365']);

        $this->actingAs($this->admin())->post(route('admin.settings.chat.update'), [
            'chat_whatsapp_number' => '01716-952365',
        ]);

        ChatSettings::forget();
        $this->assertNull(ChatSettings::whatsappUrl());
    }

    public function test_the_page_is_in_the_sidebar_and_behind_the_settings_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $editor = User::factory()->admin()->create();
        $editor->syncPermissions(['dashboard.view', 'settings.view', 'settings.edit']);

        $html = $this->actingAs($editor->fresh())->get(route('admin.settings.chat.edit'))
            ->assertOk()
            ->getContent();

        $this->assertStringContainsString(route('admin.settings.chat.edit'), $html);

        $viewer = User::factory()->admin()->create();
        $viewer->syncPermissions(['dashboard.view', 'settings.view']);

        $this->actingAs($viewer->fresh())->get(route('admin.settings.chat.edit'))->assertForbidden();
    }

    public function test_the_sidebar_only_offers_settings_pages_the_staff_can_open(): void
    {
        $this->seed(PermissionSeeder::class);

        $viewer = User::factory()->admin()->create();
        $viewer->syncPermissions(['dashboard.view', 'settings.view']);

        $html = $this->actingAs($viewer->fresh())->get('/admin')->getContent();

        // A link that 403s the moment it is clicked should not be shown at all.
        foreach (['chat', 'mail', 'sms', 'seo'] as $page) {
            $this->assertStringNotContainsString(route("admin.settings.{$page}.edit"), $html);
        }

        $this->assertStringContainsString(route('admin.settings.index'), $html);
    }

    public function test_no_number_leaves_no_dead_links_anywhere(): void
    {
        // Every one of these pages used to render href="" or href="?text=..."
        // when the shop number was blank, which just reloads the page.
        foreach (['/', '/contact', '/no-such-page'] as $url) {
            $html = $this->get($url)->getContent();

            $this->assertStringNotContainsString('href="?text=', $html);
            $this->assertStringNotContainsString('href="" target="_blank"', $html);
        }
    }

    public function test_a_configured_number_reaches_the_order_button_on_the_home_page(): void
    {
        $this->configure(['chat_whatsapp_number' => '01716-952365']);

        $this->get('/')->assertOk()->assertSee('wa.me/8801716952365', false);
    }
}
