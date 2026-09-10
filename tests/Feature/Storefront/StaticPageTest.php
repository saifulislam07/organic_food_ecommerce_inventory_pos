<?php

namespace Tests\Feature\Storefront;

use App\Models\Page;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Terms, privacy, returns, shipping and the FAQ.
 *
 * These pages carried the old organic-food theme's leaf across the text and had
 * no prose styling at all, so an admin writing headings and lists in the editor
 * got one undifferentiated block. What is checked here is that the markup the
 * stylesheet needs is actually emitted, and that the set stays navigable —
 * neither shows up in a page that merely returns 200.
 */
class StaticPageTest extends TestCase
{
    use RefreshDatabase;

    private function page(array $attributes = []): Page
    {
        return Page::create(array_merge([
            'slug' => 'return-policy',
            'title_en' => 'Return Policy',
            'title_bn' => 'রিটার্ন পলিসি',
            'content_en' => '<h2>Our promise</h2><p>We check every order.</p><ul><li>Damaged</li></ul>',
            'content_bn' => '<h2>আমাদের প্রতিশ্রুতি</h2><p>প্রতিটি অর্ডার যাচাই করি।</p>',
            'is_active' => true,
        ], $attributes));
    }

    public function test_a_page_renders_its_content_in_the_prose_wrapper(): void
    {
        $this->page();

        $this->get('/return-policy')
            ->assertOk()
            ->assertSee('legal-prose', false)
            ->assertSee('<h2>Our promise</h2>', false)
            ->assertSee('We check every order.');
    }

    /** The old theme's leaf sat at full opacity across the first paragraph. */
    public function test_the_organic_theme_decoration_is_gone(): void
    {
        $this->page();

        $this->get('/return-policy')
            ->assertOk()
            ->assertDontSee('🍃', false)
            ->assertDontSee('opacity-05', false);
    }

    public function test_the_other_policies_are_listed_alongside(): void
    {
        $this->page();
        $this->page(['slug' => 'privacy-policy', 'title_en' => 'Privacy Policy']);
        $this->page(['slug' => 'faq', 'title_en' => 'Frequently Asked Questions']);

        $response = $this->get('/return-policy')->assertOk();

        $response->assertSee('legal-nav', false)
            ->assertSee('Privacy Policy')
            ->assertSee('Frequently Asked Questions')
            ->assertSee('/privacy-policy', false);
    }

    /** The page you are on is marked, not repeated as a link to nowhere. */
    public function test_the_current_page_is_marked_in_the_sidebar(): void
    {
        $this->page();
        $this->page(['slug' => 'privacy-policy', 'title_en' => 'Privacy Policy']);

        $this->get('/return-policy')
            ->assertOk()
            ->assertSee('is-current', false)
            ->assertSee('aria-current="page"', false);
    }

    /** A page an admin has switched off must not be advertised on its siblings. */
    public function test_an_inactive_page_is_neither_shown_nor_listed(): void
    {
        $this->page();
        $this->page([
            'slug' => 'draft-policy',
            'title_en' => 'Draft Policy',
            'is_active' => false,
        ]);

        $this->get('/return-policy')
            ->assertOk()
            ->assertDontSee('Draft Policy');

        $this->get('/draft-policy')->assertNotFound();
    }

    /** With only one page there is no set to navigate, so no sidebar. */
    public function test_a_lone_page_gets_the_full_width(): void
    {
        $this->page();

        $this->get('/return-policy')
            ->assertOk()
            ->assertDontSee('legal-nav', false);
    }

    public function test_a_page_follows_the_reading_locale(): void
    {
        $this->page();

        $this->get('/lang/bn');

        $this->get('/return-policy')
            ->assertOk()
            ->assertSee('রিটার্ন পলিসি')
            ->assertSee('প্রতিটি অর্ডার যাচাই করি।');
    }

    /* ------------------------------------------------------------- the seeder */

    /** Every page the footer and the policy sidebar expect to exist. */
    public function test_the_seeder_provides_the_whole_set(): void
    {
        $this->seed(PageSeeder::class);

        foreach (['about-us', 'faq', 'shipping-policy', 'return-policy', 'terms-and-conditions', 'privacy-policy'] as $slug) {
            $this->get('/'.$slug)->assertOk();
        }
    }

    /**
     * firstOrCreate, not updateOrCreate: re-running the seeder after an admin
     * has rewritten a policy must not throw their words away.
     */
    public function test_re_seeding_leaves_an_edited_page_alone(): void
    {
        $this->seed(PageSeeder::class);

        Page::where('slug', 'privacy-policy')->update(['content_en' => '<p>Our own wording.</p>']);

        $this->seed(PageSeeder::class);

        $this->assertSame(
            '<p>Our own wording.</p>',
            Page::where('slug', 'privacy-policy')->value('content_en')
        );
    }
}
