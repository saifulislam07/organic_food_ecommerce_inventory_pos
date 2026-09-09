<?php

namespace Tests\Feature;

use App\Models\HeroSlide;
use App\Models\Setting;
use App\Models\User;
use App\Support\ImageStore;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * The front page hero is a carousel over hero_slides. A shop with no slides
 * still has to get the settings-driven panel it had before the table existed,
 * and a shop with one slide must not sprout arrows it cannot use.
 */
class HeroSliderTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function slide(array $overrides = []): HeroSlide
    {
        return HeroSlide::create(array_merge([
            'title_en' => 'Fresh Mango Season',
        ], $overrides));
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'title_en' => 'Pure Honey',
            'title_bn' => 'খাঁটি মধু',
            'sort_order' => 2,
        ], $overrides);
    }

    /* ------------------------------------------------------- the storefront */

    public function test_an_empty_table_still_renders_the_hero_from_the_settings(): void
    {
        Setting::put('hero_title', 'Settings <span>Headline</span>');
        Setting::put('hero_desc', 'Straight from the orchard.');

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertStringContainsString('Settings <span>Headline</span>', $html);
        $this->assertStringContainsString('Straight from the orchard.', $html);
        // One panel is not a slider: nothing to press, nothing to jump to.
        $this->assertStringNotContainsString('data-bs-slide="prev"', $html);
        $this->assertStringNotContainsString('data-bs-slide-to', $html);
    }

    public function test_every_active_slide_becomes_a_panel_with_arrows_and_dots(): void
    {
        $this->slide(['title_en' => 'Mango Season', 'sort_order' => 1]);
        $this->slide(['title_en' => 'Pure Honey', 'sort_order' => 0]);

        $html = $this->get('/')->assertOk()->getContent();

        $this->assertSame(2, substr_count($html, 'carousel-item '));
        $this->assertSame(2, substr_count($html, 'data-bs-slide-to'));
        $this->assertStringContainsString('data-bs-slide="prev"', $html);

        // sort_order decides the running order, not insertion order.
        $this->assertLessThan(
            strpos($html, 'Mango Season'),
            strpos($html, 'Pure Honey')
        );
    }

    public function test_an_inactive_slide_never_reaches_the_front_page(): void
    {
        $this->slide(['title_en' => 'Live One']);
        $this->slide(['title_en' => 'Hidden One', 'is_active' => false]);

        $this->get('/')
            ->assertOk()
            ->assertSee('Live One')
            ->assertDontSee('Hidden One');
    }

    public function test_a_slide_falls_back_to_the_shop_link_and_the_shipped_picture(): void
    {
        $this->slide();

        $this->get('/')
            ->assertOk()
            ->assertSee(route('shop'), false)
            ->assertSee(HeroSlide::DEFAULT_IMAGE, false);
    }

    public function test_bangla_falls_back_to_english_field_by_field(): void
    {
        app()->setLocale('bn');

        $slide = $this->slide([
            'title_en' => 'Pure Honey',
            'title_bn' => 'খাঁটি মধু',
            'subtitle_en' => 'Raw and unfiltered.',
        ]);

        $this->assertSame('খাঁটি মধু', $slide->title);
        $this->assertSame('Raw and unfiltered.', $slide->subtitle);
        $this->assertSame('শপ করুন', $slide->button_text);
    }

    /* ------------------------------------------------------------ the admin */

    public function test_admin_can_create_a_slide_with_a_picture(): void
    {
        Storage::fake('uploads');

        $this->actingAs($this->admin())
            ->post(route('admin.sliders.store'), $this->payload([
                'image' => UploadedFile::fake()->image('honey.jpg'),
            ]))
            ->assertRedirect(route('admin.sliders.index'));

        $slide = HeroSlide::sole();

        $this->assertSame('খাঁটি মধু', $slide->title_bn);
        $this->assertTrue($slide->is_active);
        $this->assertTrue(ImageStore::exists($slide->image));
    }

    public function test_replacing_the_picture_removes_the_old_file(): void
    {
        Storage::fake('uploads');

        $slide = $this->slide([
            'image' => ImageStore::put(UploadedFile::fake()->image('old.jpg'), 'sliders'),
        ]);
        $old = $slide->image;

        $this->actingAs($this->admin())
            ->put(route('admin.sliders.update', $slide), $this->payload([
                'image' => UploadedFile::fake()->image('new.jpg'),
            ]))
            ->assertRedirect(route('admin.sliders.index'));

        $this->assertFalse(ImageStore::exists($old));
        $this->assertTrue(ImageStore::exists($slide->fresh()->image));
    }

    public function test_deleting_a_slide_takes_its_picture_with_it(): void
    {
        Storage::fake('uploads');

        $slide = $this->slide([
            'image' => ImageStore::put(UploadedFile::fake()->image('gone.jpg'), 'sliders'),
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.sliders.destroy', $slide))
            ->assertRedirect(route('admin.sliders.index'));

        $this->assertDatabaseCount('hero_slides', 0);
        $this->assertFalse(ImageStore::exists($slide->image));
    }

    public function test_a_slide_needs_an_english_title(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.sliders.store'), $this->payload(['title_en' => '']))
            ->assertSessionHasErrors('title_en');

        $this->assertDatabaseCount('hero_slides', 0);
    }

    public function test_the_admin_screens_render(): void
    {
        $slide = $this->slide();

        foreach ([
            route('admin.sliders.index'),
            route('admin.sliders.create'),
            route('admin.sliders.edit', $slide),
        ] as $url) {
            $this->actingAs($this->admin())->get($url)->assertOk();
        }
    }

    public function test_the_module_is_permissioned_like_every_other(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();

        $this->actingAs($staff)
            ->get(route('admin.sliders.index'))
            ->assertForbidden();

        $staff->syncPermissions(['sliders.view']);

        $this->actingAs($staff->fresh())
            ->get(route('admin.sliders.index'))
            ->assertOk();
    }
}
