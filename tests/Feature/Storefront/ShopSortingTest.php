<?php

namespace Tests\Feature\Storefront;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * Prices live on the variants and names are locale dependent, so neither can be
 * sorted with a plain column on products.
 */
class ShopSortingTest extends TestCase
{
    use RefreshDatabase;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
    }

    private function product(string $nameEn, string $nameBn, float $price, ?float $salePrice = null): Product
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => $nameEn,
            'name_en' => $nameEn,
            'name_bn' => $nameBn,
            'slug' => str($nameEn)->slug()->value(),
            'is_active' => true,
        ]);

        ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 কেজি',
            'price' => $price,
            'sale_price' => $salePrice,
            'stock' => 5,
        ]);

        return $product;
    }

    private function slugsFor(string $sort, array $query = []): array
    {
        $response = $this->get(route('shop', array_merge(['sort' => $sort], $query)));

        $response->assertOk();

        return $response->viewData('products')->pluck('slug')->all();
    }

    public function test_price_low_orders_by_the_cheapest_variant(): void
    {
        $this->product('Cheap', 'সস্তা', 300);
        $this->product('Expensive', 'দামি', 2500);
        $this->product('Middle', 'মাঝারি', 1200);

        $this->assertSame(['cheap', 'middle', 'expensive'], $this->slugsFor('price_low'));
    }

    public function test_price_high_orders_the_other_way(): void
    {
        $this->product('Cheap', 'সস্তা', 300);
        $this->product('Expensive', 'দামি', 2500);
        $this->product('Middle', 'মাঝারি', 1200);

        $this->assertSame(['expensive', 'middle', 'cheap'], $this->slugsFor('price_high'));
    }

    public function test_price_sorting_uses_the_sale_price_when_there_is_one(): void
    {
        // Lists at 3000 but sells for 200, so it is the cheapest thing in the shop.
        $this->product('Discounted', 'ছাড়', 3000, 200);
        $this->product('Plain', 'সাধারণ', 900);

        $this->assertSame(['discounted', 'plain'], $this->slugsFor('price_low'));
    }

    public function test_name_sorting_follows_the_active_locale(): void
    {
        // English order: Apple, Banana. Bengali order: আম (Banana), কলা (Apple).
        $this->product('Apple', 'কলা', 100);
        $this->product('Banana', 'আম', 200);

        $this->assertSame(['apple', 'banana'], $this->slugsFor('name_asc'));

        session(['locale' => 'bn']);

        $this->assertSame(['banana', 'apple'], $this->slugsFor('name_asc'));
    }

    public function test_sorting_survives_a_category_filter(): void
    {
        $this->product('Cheap', 'সস্তা', 300);
        $this->product('Expensive', 'দামি', 2500);

        $this->assertSame(
            ['expensive', 'cheap'],
            $this->slugsFor('price_high', ['category' => 'fruits'])
        );
    }
}
