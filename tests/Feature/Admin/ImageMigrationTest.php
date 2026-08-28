<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Support\ImageStore;
use App\Support\SeoSettings;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * `php artisan images:migrate` brings the pictures uploaded before ImageStore
 * existed into public/uploads as WebP, and repoints the database at them.
 */
class ImageMigrationTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads');
        Storage::fake('public');
    }

    /** Writes a real PNG on the old public disk and returns its stored path. */
    private function legacyImage(string $path, int $width = 300, int $height = 200): string
    {
        $image = imagecreatetruecolor($width, $height);
        imagefill($image, 0, 0, imagecolorallocate($image, 240, 180, 40));

        ob_start();
        imagepng($image);
        $bytes = ob_get_clean();

        Storage::disk('public')->put($path, $bytes);

        return $path;
    }

    private function product(string $image): Product
    {
        $category = Category::firstOrCreate(['slug' => 'fruits'], ['name' => 'Fruits']);

        return Product::create([
            'category_id' => $category->id,
            'name' => 'Himsagar Mango',
            'slug' => 'himsagar-mango-'.uniqid(),
            'image' => $image,
        ]);
    }

    public function test_a_dry_run_changes_nothing(): void
    {
        $product = $this->product($this->legacyImage('products/old.png'));

        $this->artisan('images:migrate --dry-run')
            ->expectsOutputToContain('would convert')
            ->assertSuccessful();

        $this->assertSame('products/old.png', $product->fresh()->getRawOriginal('image'));
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    public function test_a_product_thumbnail_is_converted_and_repointed(): void
    {
        $product = $this->product($this->legacyImage('products/old.png'));

        $this->artisan('images:migrate')->assertSuccessful();

        $path = $product->fresh()->getRawOriginal('image');

        $this->assertStringStartsWith('uploads/products/', $path);
        $this->assertStringEndsWith('.webp', $path);
        Storage::disk('uploads')->assertExists($path);
    }

    public function test_gallery_rows_are_converted_too(): void
    {
        $product = $this->product($this->legacyImage('products/thumb.png'));
        $image = ProductImage::create([
            'product_id' => $product->id,
            'path' => 'products/thumb.png',
            'sort_order' => 1,
        ]);

        $this->artisan('images:migrate')->assertSuccessful();

        $path = $image->fresh()->path;

        $this->assertStringStartsWith('uploads/products/', $path);

        // products.image mirrors the gallery photo, so it must move with it.
        $this->assertSame($path, $product->fresh()->getRawOriginal('image'));
    }

    public function test_a_category_image_is_converted(): void
    {
        $category = Category::create([
            'name' => 'Fruits',
            'slug' => 'fruits',
            'image' => $this->legacyImage('categories/fruits.png'),
        ]);

        $this->artisan('images:migrate')->assertSuccessful();

        $this->assertStringStartsWith('uploads/categories/', $category->fresh()->getRawOriginal('image'));
    }

    public function test_the_share_image_is_converted(): void
    {
        SeoSettings::save(['seo_og_image' => $this->legacyImage('seo/share.png')]);

        $this->artisan('images:migrate')->assertSuccessful();

        SeoSettings::forget();

        $this->assertStringStartsWith('uploads/seo/', (string) SeoSettings::get('seo_og_image'));
    }

    public function test_running_it_twice_converts_nothing_the_second_time(): void
    {
        $product = $this->product($this->legacyImage('products/old.png'));

        $this->artisan('images:migrate')->assertSuccessful();
        $first = $product->fresh()->getRawOriginal('image');

        $this->artisan('images:migrate')->assertSuccessful();

        $this->assertSame($first, $product->fresh()->getRawOriginal('image'));
        $this->assertCount(1, Storage::disk('uploads')->allFiles());
    }

    public function test_a_missing_source_is_reported_and_left_alone(): void
    {
        $product = $this->product('products/never-existed.png');

        $this->artisan('images:migrate')
            ->expectsOutputToContain('file not found')
            ->assertSuccessful();

        $this->assertSame('products/never-existed.png', $product->fresh()->getRawOriginal('image'));
    }

    public function test_prune_removes_the_original(): void
    {
        $this->product($this->legacyImage('products/old.png'));

        $this->artisan('images:migrate --prune')->assertSuccessful();

        Storage::disk('public')->assertMissing('products/old.png');
    }

    public function test_prune_never_touches_a_shipped_asset(): void
    {
        // The seeder points products at bare filenames in public/assets.
        $shipped = public_path('assets/img/products/cow-ghee.png');

        if (! File::exists($shipped)) {
            $this->markTestSkipped('The shipped demo images are not present.');
        }

        $product = $this->product('cow-ghee.png');

        $this->artisan('images:migrate --prune')->assertSuccessful();

        $this->assertTrue(File::exists($shipped), 'A version-controlled asset was deleted.');
        $this->assertTrue(ImageStore::isStored($product->fresh()->getRawOriginal('image')));
    }
}
