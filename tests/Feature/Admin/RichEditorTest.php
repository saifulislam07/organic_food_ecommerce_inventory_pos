<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use App\Support\RichText;
use Database\Seeders\PageSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * The long fields are edited as HTML now, so what an editor produces has to
 * survive the round trip while anything dangerous does not.
 */
class RichEditorTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function pagePayload(array $overrides = []): array
    {
        return array_merge([
            'title_en' => 'Return Policy',
            'title_bn' => 'রিটার্ন পলিসি',
            'content_en' => '<p>Send it back within 7 days.</p>',
            'content_bn' => '<p>৭ দিনের মধ্যে ফেরত দিন।</p>',
        ], $overrides);
    }

    /* ------------------------------------------------------- the cleaner */

    public static function dangerousMarkup(): array
    {
        return [
            'script tag' => ['<p>Hi</p><script>steal()</script>', 'steal'],
            'iframe' => ['<p>Hi</p><iframe src="//evil.test"></iframe>', 'iframe'],
            'inline handler' => ['<p onclick="steal()">Hi</p>', 'onclick'],
            'unquoted handler' => ['<img src=x onerror=steal()>', 'onerror'],
            'javascript url' => ['<a href="javascript:steal()">Hi</a>', 'javascript:'],
            'style block' => ['<style>body{display:none}</style><p>Hi</p>', 'display:none'],
        ];
    }

    #[DataProvider('dangerousMarkup')]
    public function test_the_cleaner_removes_what_does_not_belong(string $html, string $trace): void
    {
        $this->assertStringNotContainsString($trace, RichText::clean($html));
    }

    public function test_the_cleaner_keeps_ordinary_formatting(): void
    {
        $html = '<h3>Refunds</h3><p>Within <strong>7 days</strong>.</p>'
            .'<ul><li>Damaged</li></ul><a href="https://example.test/x">Read more</a>';

        $this->assertSame($html, RichText::clean($html));
    }

    public function test_blank_values_pass_through_untouched(): void
    {
        $this->assertNull(RichText::clean(null));
        $this->assertSame('', RichText::clean(''));
        $this->assertSame('', RichText::display(null));
    }

    public function test_plain_text_written_before_the_editor_keeps_its_line_breaks(): void
    {
        $rendered = RichText::display("First line\nSecond line");

        $this->assertStringContainsString('<br', $rendered);
        $this->assertStringContainsString('Second line', $rendered);
    }

    public function test_editor_html_is_rendered_as_html(): void
    {
        $this->assertSame('<p>Hello</p>', RichText::display('<p>Hello</p>'));
    }

    public function test_display_still_strips_a_script_that_reached_the_database(): void
    {
        $rendered = RichText::display('<p>Hi</p><script>steal()</script>');

        $this->assertStringNotContainsString('steal', $rendered);
    }

    /* -------------------------------------------------------- the fields */

    public function test_the_page_form_offers_an_editor_for_both_languages(): void
    {
        $html = $this->actingAs($this->admin())->get(route('admin.pages.create'))->getContent();

        $this->assertStringContainsString('name="content_en" data-editor', $html);
        $this->assertStringContainsString('name="content_bn" data-editor', $html);

        // The editor hides the textarea, and Chrome refuses to submit a form
        // holding a hidden field marked required -- with no message shown.
        // The server-side rule is what reports an empty page now.
        $this->assertStringNotContainsString('data-editor class="form-control" rows="10" required', $html);
    }

    public function test_an_empty_page_is_reported_by_the_server(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.pages.store'), $this->pagePayload(['content_en' => '']))
            ->assertSessionHasErrors('content_en');
    }

    public function test_the_product_form_offers_an_editor_for_the_descriptions(): void
    {
        Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $html = $this->actingAs($this->admin())->get(route('admin.products.create'))->getContent();

        $this->assertStringContainsString('name="description_en" data-editor', $html);
        $this->assertStringContainsString('name="description_bn" data-editor', $html);
    }

    /* --------------------------------------------------------- the saves */

    public function test_a_page_keeps_its_formatting_but_loses_a_script(): void
    {
        $this->actingAs($this->admin())->post(route('admin.pages.store'), $this->pagePayload([
            'content_en' => '<h3>Refunds</h3><p>Within 7 days.</p><script>steal()</script>',
        ]));

        $page = Page::firstOrFail();

        $this->assertStringContainsString('<h3>Refunds</h3>', $page->content_en);
        $this->assertStringNotContainsString('steal', $page->content_en);
    }

    public function test_editing_a_page_cleans_it_too(): void
    {
        $page = Page::create($this->pagePayload(['slug' => 'return-policy']));

        $this->actingAs($this->admin())->put(route('admin.pages.update', $page), $this->pagePayload([
            'slug' => 'return-policy',
            'content_bn' => '<p>ঠিক আছে</p><iframe src="//evil.test"></iframe>',
        ]));

        $this->assertStringNotContainsString('iframe', $page->fresh()->content_bn);
    }

    public function test_a_product_description_survives_as_html_and_reaches_the_shop(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name_en' => 'Himsagar Mango',
            'name_bn' => 'হিমসাগর আম',
            'category_id' => $category->id,
            'description_en' => '<p>Sweet and <strong>juicy</strong>.</p><script>steal()</script>',
            'description_bn' => '<p>মিষ্টি ও রসালো।</p>',
            'variants' => [['name' => '1 kg', 'price' => 100, 'stock' => 5]],
        ]);

        $product = Product::firstOrFail();
        $this->assertStringNotContainsString('steal', $product->description_en);

        $this->get(route('product.show', $product->slug))
            ->assertOk()
            ->assertSee('<strong>juicy</strong>', false)
            ->assertDontSee('&lt;strong&gt;', false);
    }

    /* --------------------------------------------------- the new page */

    public function test_the_seeder_creates_the_return_policy_page(): void
    {
        $this->seed(PageSeeder::class);

        $page = Page::where('slug', 'return-policy')->first();

        $this->assertNotNull($page);
        $this->assertTrue($page->is_active);
        $this->assertStringContainsString('24 hours', $page->content_en);
        $this->assertStringContainsString('২৪ ঘণ্টার', $page->content_bn);
    }

    public function test_the_seeder_leaves_an_edited_page_alone(): void
    {
        $this->seed(PageSeeder::class);

        Page::where('slug', 'return-policy')->update(['content_en' => '<p>Our own words.</p>']);

        $this->seed(PageSeeder::class);

        $this->assertSame('<p>Our own words.</p>', Page::where('slug', 'return-policy')->value('content_en'));
    }

    public function test_the_return_policy_is_reachable_and_linked_from_the_footer(): void
    {
        $this->seed(PageSeeder::class);

        $this->get('/return-policy')
            ->assertOk()
            ->assertSee('Return Policy')
            ->assertSee('<h3>How to raise a return</h3>', false);

        $this->get('/')->assertOk()->assertSee(url('/return-policy'));
    }

    public function test_every_page_the_footer_links_to_exists_after_seeding(): void
    {
        $this->seed(PageSeeder::class);

        foreach (['about-us', 'terms-and-conditions', 'privacy-policy', 'shipping-policy', 'return-policy'] as $slug) {
            $this->get("/{$slug}")->assertOk();
        }
    }
}
