<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Page;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * products.name and categories.name are NOT NULL fallbacks that the admin forms
 * never post — only name_en / name_bn. Both slug columns are UNIQUE too, so a
 * repeated name has to keep working.
 */
class CatalogCrudTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private ?Category $category = null;

    private function category(): Category
    {
        return $this->category ??= Category::create(['name' => 'Fruits', 'slug' => 'fruits']);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create([
            'name' => 'Admin',
            'email' => 'admin@example.test',
        ]);
    }

    private function productPayload(array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Himsagar Mango',
            'name_bn' => 'হিমসাগর আম',
            'category_id' => $this->category()->id,
            'variants' => [
                ['name' => '3 কেজি', 'price' => 1200, 'stock' => 5],
            ],
        ], $overrides);
    }

    public function test_admin_can_create_a_product(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), $this->productPayload())
            ->assertRedirect(route('admin.products.index'));

        $this->assertDatabaseHas('products', [
            'name' => 'Himsagar Mango',
            'name_bn' => 'হিমসাগর আম',
            'slug' => 'himsagar-mango',
        ]);

        $this->assertDatabaseHas('product_variants', ['name' => '3 কেজি', 'stock' => 5]);
    }

    public function test_two_products_can_share_a_name_without_colliding_on_slug(): void
    {
        foreach (range(1, 3) as $ignored) {
            $this->actingAs($this->admin())
                ->post(route('admin.products.store'), $this->productPayload())
                ->assertRedirect(route('admin.products.index'));
        }

        $this->assertSame(
            ['himsagar-mango', 'himsagar-mango-2', 'himsagar-mango-3'],
            Product::orderBy('id')->pluck('slug')->all()
        );
    }

    public function test_updating_a_product_keeps_its_own_slug(): void
    {
        $this->actingAs($this->admin())->post(route('admin.products.store'), $this->productPayload());

        $product = Product::firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->productPayload([
                'name_bn' => 'আপডেটেড আম',
            ]))
            ->assertRedirect(route('admin.products.index'));

        $product->refresh();

        $this->assertSame('himsagar-mango', $product->slug, 'Updating must not bump its own slug.');
        $this->assertSame('আপডেটেড আম', $product->name_bn);
    }

    public function test_two_pages_with_the_same_title_get_distinct_auto_slugs(): void
    {
        foreach (range(1, 2) as $ignored) {
            $this->actingAs($this->admin())
                ->post(route('admin.pages.store'), [
                    'title_en' => 'Refund Policy',
                    'title_bn' => 'রিফান্ড নীতি',
                    'content_en' => 'Text',
                    'content_bn' => 'লেখা',
                ])
                ->assertRedirect(route('admin.pages.index'));
        }

        $this->assertSame(
            ['refund-policy', 'refund-policy-2'],
            Page::orderBy('id')->pluck('slug')->all()
        );
    }

    public function test_admin_can_create_a_category(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.categories.store'), [
                'name_en' => 'Seasonal Fruits',
                'name_bn' => 'মৌসুমি ফল',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $this->assertDatabaseHas('categories', [
            'name' => 'Seasonal Fruits',
            'name_bn' => 'মৌসুমি ফল',
            'slug' => 'seasonal-fruits',
        ]);
    }

    public function test_admin_can_update_a_category(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), [
                'name_en' => 'Seasonal Fruits',
                'name_bn' => 'মৌসুমি ফল',
                'sort_order' => 4,
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'));

        $category->refresh();

        $this->assertSame('Seasonal Fruits', $category->name_en);
        $this->assertSame('মৌসুমি ফল', $category->name_bn);
        $this->assertSame(4, $category->sort_order);
    }

    /**
     * categories.sort_order is NOT NULL, and an empty number box arrives as
     * null — which used to abort the whole save with a driver error.
     */
    public function test_a_category_saves_with_the_sort_order_box_left_empty(): void
    {
        $category = $this->category();

        $this->actingAs($this->admin())
            ->put(route('admin.categories.update', $category), [
                'name_en' => 'Seasonal Fruits',
                'name_bn' => 'মৌসুমি ফল',
                'sort_order' => '',
                'is_active' => '1',
            ])
            ->assertRedirect(route('admin.categories.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame(0, $category->refresh()->sort_order);
    }

    public function test_the_category_form_no_longer_asks_for_a_description(): void
    {
        $this->actingAs($this->admin())
            ->get(route('admin.categories.edit', $this->category()))
            ->assertOk()
            ->assertDontSee('name="description_en"', false)
            ->assertDontSee('name="description_bn"', false);
    }

    public function test_two_categories_can_share_a_name_without_colliding_on_slug(): void
    {
        foreach (range(1, 2) as $ignored) {
            $this->actingAs($this->admin())
                ->post(route('admin.categories.store'), [
                    'name_en' => 'Seasonal Fruits',
                    'name_bn' => 'মৌসুমি ফল',
                ])
                ->assertRedirect(route('admin.categories.index'));
        }

        $this->assertSame(
            ['seasonal-fruits', 'seasonal-fruits-2'],
            Category::orderBy('id')->pluck('slug')->all()
        );
    }
}
