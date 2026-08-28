<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class ProductGalleryTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private ?Category $category = null;

    protected function setUp(): void
    {
        parent::setUp();

        Storage::fake('public');
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function category(): Category
    {
        return $this->category ??= Category::create(['name' => 'Fruits', 'slug' => 'fruits']);
    }

    private function payload(array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Himsagar Mango',
            'name_bn' => 'হিমসাগর আম',
            'category_id' => $this->category()->id,
            'variants' => [['name' => '3 কেজি', 'price' => 1200, 'stock' => 5]],
        ], $overrides);
    }

    private function createWithImages(int $count): Product
    {
        $files = [];

        for ($i = 1; $i <= $count; $i++) {
            $files[] = UploadedFile::fake()->image("photo-{$i}.png");
        }

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['images' => $files]))
            ->assertRedirect(route('admin.products.index'));

        return Product::firstOrFail();
    }

    /* ------------------------------------------------------------ upload */

    public function test_a_product_can_be_created_with_several_images(): void
    {
        $product = $this->createWithImages(3);

        $this->assertSame(3, $product->images()->count());

        foreach ($product->images as $image) {
            Storage::disk('public')->assertExists($image->path);
        }
    }

    public function test_the_first_upload_becomes_the_thumbnail(): void
    {
        $product = $this->createWithImages(3);

        $this->assertSame($product->images->first()->path, $product->getRawOriginal('image'));
    }

    public function test_more_than_the_limit_is_refused(): void
    {
        $files = [];

        for ($i = 1; $i <= Product::MAX_IMAGES + 1; $i++) {
            $files[] = UploadedFile::fake()->image("photo-{$i}.png");
        }

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload(['images' => $files]))
            ->assertSessionHasErrors('images');

        $this->assertSame(0, ProductImage::count());
    }

    public function test_adding_past_the_limit_on_edit_is_refused(): void
    {
        $product = $this->createWithImages(Product::MAX_IMAGES);

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'images' => [UploadedFile::fake()->image('one-too-many.png')],
            ]))
            ->assertSessionHasErrors('images');

        $this->assertSame(Product::MAX_IMAGES, $product->images()->count());
    }

    public function test_a_non_image_upload_is_refused(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload([
                'images' => [UploadedFile::fake()->create('payload.php', 20, 'application/x-php')],
            ]))
            ->assertSessionHasErrors('images.0');
    }

    /* --------------------------------------------------------- thumbnail */

    public function test_the_admin_can_choose_which_image_is_the_thumbnail(): void
    {
        $product = $this->createWithImages(3);
        $second = $product->images[1];

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'thumbnail_id' => $second->id,
            ]))
            ->assertRedirect(route('admin.products.index'));

        $this->assertSame($second->path, $product->fresh()->getRawOriginal('image'));
    }

    public function test_deleting_the_thumbnail_promotes_another_image(): void
    {
        $product = $this->createWithImages(3);
        $thumbnail = $product->images->first();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'remove_images' => [$thumbnail->id],
            ]))
            ->assertRedirect();

        $product->refresh()->load('images');

        $this->assertSame(2, $product->images->count());
        $this->assertNotSame($thumbnail->path, $product->getRawOriginal('image'));
        $this->assertContains($product->getRawOriginal('image'), $product->images->pluck('path')->all());
    }

    /* ------------------------------------------------------------ delete */

    public function test_removing_an_image_deletes_the_file_too(): void
    {
        $product = $this->createWithImages(3);
        $doomed = $product->images[2];

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->payload([
                'remove_images' => [$doomed->id],
            ]))
            ->assertRedirect();

        $this->assertModelMissing($doomed);
        Storage::disk('public')->assertMissing($doomed->path);
    }

    public function test_deleting_a_product_takes_its_images_with_it(): void
    {
        $product = $this->createWithImages(2);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect();

        $this->assertSame(0, ProductImage::count());
    }

    /* --------------------------------------------------------- storefront */

    public function test_the_product_page_shows_every_image_thumbnail_first(): void
    {
        $product = $this->createWithImages(3);
        $second = $product->images[1];

        $this->actingAs($this->admin())->put(route('admin.products.update', $product), $this->payload([
            'thumbnail_id' => $second->id,
        ]));

        $response = $this->get(route('product.show', $product->fresh()->slug));

        $response->assertOk();

        $pattern = '/data-vue="ProductGalleryViewer"\s+data-props="([^"]*)"/';
        preg_match($pattern, $response->getContent(), $matches);
        $props = json_decode(html_entity_decode($matches[1], ENT_QUOTES, 'UTF-8'), true);

        $this->assertCount(3, $props['images']);
        $this->assertStringContainsString($second->path, $props['images'][0], 'The thumbnail leads the gallery.');
    }

    public function test_a_product_with_no_images_still_renders(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->payload())
            ->assertRedirect();

        $product = Product::firstOrFail();

        $this->assertSame(0, $product->images()->count());
        $this->get(route('product.show', $product->slug))->assertOk();
    }
}
