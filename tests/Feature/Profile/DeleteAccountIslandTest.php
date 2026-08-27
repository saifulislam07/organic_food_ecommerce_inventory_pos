<?php

namespace Tests\Feature\Profile;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * The delete-account dialog used to be an Alpine component, but /profile renders
 * through layouts.frontend, which never loaded Alpine — so the button did nothing.
 * It is a Vue island now; these cover the props that drive it.
 */
class DeleteAccountIslandTest extends TestCase
{
    use RefreshDatabase;

    public function test_profile_page_mounts_the_dialog_closed(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('profile.edit'));

        $response->assertOk();

        $props = $this->propsFrom($response->getContent(), 'ConfirmDeleteAccount');

        $this->assertSame(route('profile.destroy'), $props['action']);
        $this->assertFalse($props['open']);
        $this->assertNull($props['error']);
        $this->assertArrayHasKey('confirm', $props['labels']);
    }

    public function test_a_wrong_password_reopens_the_dialog_with_its_error(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->from(route('profile.edit'))
            ->delete(route('profile.destroy'), ['password' => 'wrong-password'])
            ->assertRedirect(route('profile.edit'));

        $response = $this->actingAs($user)->get(route('profile.edit'));

        $props = $this->propsFrom($response->getContent(), 'ConfirmDeleteAccount');

        $this->assertTrue($props['open'], 'The dialog must reopen so the shopper sees why it failed.');
        $this->assertNotEmpty($props['error']);
        $this->assertNotNull($user->fresh());
    }

    public function test_the_dialog_still_posts_a_plain_form_that_deletes_the_account(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->delete(route('profile.destroy'), ['password' => 'password'])
            ->assertRedirect('/');

        $this->assertGuest();
        $this->assertNull($user->fresh());
    }

    public function test_the_page_no_longer_ships_alpine_directives(): void
    {
        $response = $this->actingAs(User::factory()->create())->get(route('profile.edit'));

        $response->assertOk();
        $response->assertDontSee('x-data', false);
        $response->assertDontSee('$dispatch', false);
    }

    /** Pull and decode the data-props blob for a given island out of the HTML. */
    private function propsFrom(string $html, string $component): array
    {
        $pattern = '/data-vue="'.preg_quote($component, '/').'"\s+data-props="([^"]*)"/';

        $this->assertMatchesRegularExpression($pattern, $html, "No {$component} island found.");

        preg_match($pattern, $html, $matches);

        return json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true, 512, JSON_THROW_ON_ERROR);
    }
}
