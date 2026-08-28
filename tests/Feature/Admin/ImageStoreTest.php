<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use App\Support\ImageStore;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

/**
 * Uploads used to be scattered across storage/app/public in whatever format
 * they arrived in. They now go through one function, come out as WebP, and sit
 * under public/uploads.
 */
class ImageStoreTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('uploads');
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function isWebp(string $path): bool
    {
        $bytes = Storage::disk('uploads')->get($path);

        return str_starts_with($bytes, 'RIFF') && substr($bytes, 8, 4) === 'WEBP';
    }

    /* ------------------------------------------------------------- put() */

    public function test_an_upload_comes_out_as_webp_under_uploads(): void
    {
        $path = ImageStore::put(UploadedFile::fake()->image('Himsagar Mango.png', 400, 300), 'products');

        $this->assertStringStartsWith('uploads/products/'.date('Y/m').'/', $path);
        $this->assertStringEndsWith('.webp', $path);
        $this->assertStringContainsString('himsagar-mango', $path);

        Storage::disk('uploads')->assertExists($path);
        $this->assertTrue($this->isWebp($path), 'The stored file is not WebP.');
    }

    public function test_two_uploads_of_the_same_name_do_not_collide(): void
    {
        $first = ImageStore::put(UploadedFile::fake()->image('mango.png'), 'products');
        $second = ImageStore::put(UploadedFile::fake()->image('mango.png'), 'products');

        $this->assertNotSame($first, $second);
        Storage::disk('uploads')->assertExists($first);
        Storage::disk('uploads')->assertExists($second);
    }

    public function test_an_oversized_photo_is_scaled_down(): void
    {
        $wide = ImageStore::MAX_WIDTH + 800;

        $path = ImageStore::put(UploadedFile::fake()->image('huge.png', $wide, 600), 'products');

        $size = getimagesizefromstring(Storage::disk('uploads')->get($path));

        $this->assertSame(ImageStore::MAX_WIDTH, $size[0]);
    }

    public function test_a_photo_within_the_limit_keeps_its_size(): void
    {
        $path = ImageStore::put(UploadedFile::fake()->image('small.png', 800, 600), 'products');

        $size = getimagesizefromstring(Storage::disk('uploads')->get($path));

        $this->assertSame([800, 600], [$size[0], $size[1]]);
    }

    public function test_a_file_gd_cannot_read_is_stored_rather_than_lost(): void
    {
        $path = ImageStore::put(UploadedFile::fake()->create('brochure.pdf', 10), 'products');

        $this->assertStringEndsWith('.pdf', $path);
        Storage::disk('uploads')->assertExists($path);
    }

    /* ------------------------------------------------------------- url() */

    public function test_urls_are_built_for_each_era_of_path(): void
    {
        $this->assertSame(asset('uploads/products/2026/08/x.webp'), ImageStore::url('uploads/products/2026/08/x.webp'));
        $this->assertSame(asset('storage/products/old.png'), ImageStore::url('products/old.png'));
        $this->assertSame('https://cdn.test/x.png', ImageStore::url('https://cdn.test/x.png'));
        $this->assertSame(asset('assets/img/placeholder.png'), ImageStore::url(null));
        $this->assertSame(asset('assets/img/placeholder.png'), ImageStore::url('bare-filename.png'));
    }

    /* ---------------------------------------------------------- delete() */

    public function test_delete_removes_a_stored_file(): void
    {
        $path = ImageStore::put(UploadedFile::fake()->image('mango.png'), 'products');

        ImageStore::delete($path);

        Storage::disk('uploads')->assertMissing($path);
    }

    public function test_delete_ignores_anything_it_did_not_write(): void
    {
        Storage::disk('uploads')->put('assets/img/shipped.png', 'x');

        ImageStore::delete('assets/img/shipped.png');
        ImageStore::delete('products/legacy.png');
        ImageStore::delete(null);

        Storage::disk('uploads')->assertExists('assets/img/shipped.png');
    }

    /* ------------------------------------------------------ the admin forms */

    public function test_a_product_gallery_upload_is_converted(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name_en' => 'Himsagar Mango',
            'name_bn' => 'হিমসাগর আম',
            'category_id' => $category->id,
            'images' => [
                UploadedFile::fake()->image('one.jpg'),
                UploadedFile::fake()->image('two.png'),
            ],
            'variants' => [['name' => '1 kg', 'price' => 100, 'stock' => 5]],
        ])->assertRedirect();

        $product = Product::firstOrFail();

        $this->assertCount(2, $product->images);

        foreach ($product->images as $image) {
            $this->assertStringStartsWith('uploads/products/', $image->path);
            $this->assertTrue($this->isWebp($image->path));
            $this->assertStringContainsString('/uploads/products/', $image->url);
        }

        // The thumbnail mirrors one of them.
        $this->assertStringStartsWith('uploads/products/', $product->getRawOriginal('image'));
    }

    public function test_a_category_upload_is_converted_and_replaced_cleanly(): void
    {
        $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name_en' => 'Fruits',
            'name_bn' => 'ফল',
            'image' => UploadedFile::fake()->image('fruits.jpg'),
        ])->assertRedirect();

        $category = Category::firstOrFail();
        $first = $category->getRawOriginal('image');

        $this->assertStringStartsWith('uploads/categories/', $first);
        $this->assertTrue($this->isWebp($first));

        $this->actingAs($this->admin())->put(route('admin.categories.update', $category), [
            'name_en' => 'Fruits',
            'name_bn' => 'ফল',
            'image' => UploadedFile::fake()->image('other.jpg'),
        ])->assertRedirect();

        $second = $category->fresh()->getRawOriginal('image');

        $this->assertNotSame($first, $second);
        Storage::disk('uploads')->assertMissing($first);
        Storage::disk('uploads')->assertExists($second);
    }

    public function test_deleting_a_product_clears_its_converted_files(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name_en' => 'Himsagar Mango',
            'name_bn' => 'হিমসাগর আম',
            'category_id' => $category->id,
            'images' => [UploadedFile::fake()->image('one.jpg')],
            'variants' => [['name' => '1 kg', 'price' => 100, 'stock' => 5]],
        ]);

        $product = Product::firstOrFail();
        $paths = $product->images->pluck('path');

        $this->actingAs($this->admin())->delete(route('admin.products.destroy', $product));

        $paths->each(fn ($path) => Storage::disk('uploads')->assertMissing($path));
        $this->assertSame(0, ProductImage::count());
    }
}
