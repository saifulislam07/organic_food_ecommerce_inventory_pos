<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\LandingPage;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

/**
 * The hero picture is the first thing an ad click sees, so it has to survive
 * the upload, the next save, and the trip back out to the page.
 */
class LandingImageTest extends TestCase
{
    use RefreshDatabase;

    private ProductVariant $variant;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seed(PermissionSeeder::class);

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Mango', 'slug' => 'mango', 'is_active' => true,
        ]);

        $this->variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => '1kg', 'price' => 1000, 'stock' => 10,
        ]);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'internal_name' => 'Test',
            'headline' => 'Head',
            'slug' => 'test-lp',
            'selection_mode' => 'single',
            'delivery_mode' => 'global',
            'payment_mode' => 'cod',
            'is_active' => '1',
            'items' => [['product_variant_id' => $this->variant->id, 'offer_price' => 900]],
        ], $overrides);
    }

    private function admin(): User
    {
        return User::factory()->superAdmin()->create();
    }

    public function test_a_hero_image_is_stored_and_shown_on_the_page(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.landing-pages.store'), $this->payload([
                'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 600),
            ]))
            ->assertSessionHasNoErrors();

        $page = LandingPage::firstOrFail();

        $this->assertNotNull($page->hero_image, 'The upload never reached the column.');

        $this->get($page->url())
            ->assertOk()
            ->assertSee($page->heroImageUrl(), false)
            // Never lazy: it is the largest thing above the fold.
            ->assertSee('fetchpriority="high"', false);
    }

    public function test_the_edit_screen_shows_the_picture_already_uploaded(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.landing-pages.store'), $this->payload([
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 600),
        ]));

        $page = LandingPage::firstOrFail();

        $this->actingAs($admin)
            ->get(route('admin.landing-pages.edit', $page))
            ->assertOk()
            ->assertSee($page->heroImageUrl(), false);
    }

    public function test_saving_again_without_choosing_a_file_keeps_the_picture(): void
    {
        $admin = $this->admin();

        $this->actingAs($admin)->post(route('admin.landing-pages.store'), $this->payload([
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 600),
        ]));

        $page = LandingPage::firstOrFail();
        $original = $page->hero_image;

        $this->actingAs($admin)
            ->put(route('admin.landing-pages.update', $page), $this->payload())
            ->assertSessionHasNoErrors();

        $this->assertSame($original, $page->fresh()->hero_image);
    }

    /** An uploaded hero never disappears behind a video link: both are shown. */
    public function test_the_picture_leads_and_the_video_gets_its_own_block(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload([
            'hero_image' => UploadedFile::fake()->image('hero.jpg', 1200, 600),
            'video_url' => 'https://www.youtube.com/watch?v=abc123XYZ',
            'sections' => ['video'],
        ]));

        $page = LandingPage::firstOrFail();

        $this->get($page->url())
            ->assertOk()
            ->assertSee('fetchpriority="high"', false)
            ->assertSee('ভিডিওতে দেখুন')
            ->assertSeeInOrder([$page->heroImageUrl(), 'youtube.com/embed/abc123XYZ'], false)
            // An order button above the video, straight to the items.
            ->assertSeeInOrder(['lp-btn lp-btn-jump" href="#lp-buy"', 'ভিডিওতে দেখুন', 'id="lp-buy"'], false);

        // Switch the video block off and only the picture is left.
        $page->update(['sections' => []]);

        $this->get($page->url())
            ->assertOk()
            ->assertSee('fetchpriority="high"', false)
            ->assertDontSee('youtube.com/embed', false);
    }

    /** With no picture to show, the video takes the top of the page. */
    public function test_without_a_picture_the_video_takes_the_hero_slot(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload([
            'video_url' => 'https://www.youtube.com/watch?v=abc123XYZ',
            'sections' => ['video'],
        ]));

        $this->get(LandingPage::firstOrFail()->url())
            ->assertOk()
            ->assertSee('youtube.com/embed/abc123XYZ', false)
            ->assertDontSee('fetchpriority="high"', false)
            // Played once, at the top — not again as a block.
            ->assertDontSee('ভিডিওতে দেখুন');
    }

    public function test_a_reel_is_framed_upright(): void
    {
        $this->actingAs($this->admin())->post(route('admin.landing-pages.store'), $this->payload([
            'video_url' => 'https://www.facebook.com/reel/1087944020605175',
            'sections' => ['video'],
        ]));

        $this->get(LandingPage::firstOrFail()->url())
            ->assertOk()
            ->assertSee('lp-hero-media is-upright', false);
    }

    public function test_an_image_bigger_than_the_limit_is_reported_rather_than_dropped(): void
    {
        $this->actingAs($this->admin())
            ->from(route('admin.landing-pages.create'))
            ->post(route('admin.landing-pages.store'), $this->payload([
                'hero_image' => UploadedFile::fake()->create('hero.jpg', 9000, 'image/jpeg'),
            ]))
            ->assertSessionHasErrors('hero_image');

        $this->assertSame(0, LandingPage::count());
    }

    /**
     * A 3 MB hero photo is ordinary — anything under the limit has to go
     * through, or the page ends up with no picture and no explanation.
     */
    public function test_a_three_megabyte_photo_is_accepted(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.landing-pages.store'), $this->payload([
                'hero_image' => UploadedFile::fake()->create('hero.jpg', 3072, 'image/jpeg'),
            ]))
            ->assertSessionHasNoErrors();

        $this->assertNotNull(LandingPage::firstOrFail()->hero_image);
    }
}
