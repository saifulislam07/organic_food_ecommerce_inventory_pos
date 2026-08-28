<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Setting;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Guards the work done in the query pass.
 *
 * The home page ran 69 queries: twenty-two for settings alone, and one more
 * per variant to work out a combo's stock. The budgets below are generous —
 * they are here to catch an N+1 creeping back in, not to police one query.
 */
class QueryBudgetTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        $this->seedCatalogue();
    }

    /** Enough products, variants and combos that an N+1 shows up clearly. */
    private function seedCatalogue(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $components = [];

        for ($i = 1; $i <= 12; $i++) {
            $product = Product::create([
                'category_id' => $category->id,
                'name' => "Product {$i}",
                'slug' => "product-{$i}",
                'is_active' => true,
                'is_featured' => $i % 2 === 0,
                'is_bestseller' => $i % 3 === 0,
                'is_trending' => $i % 4 === 0,
            ]);

            foreach (['1 kg', '2 kg'] as $n => $name) {
                $components[] = ProductVariant::create([
                    'product_id' => $product->id,
                    'name' => $name,
                    'price' => 100 * ($n + 1),
                    'stock' => 20,
                    'sort_order' => $n,
                ]);
            }
        }

        // Four combos, each drawing on two components.
        for ($i = 1; $i <= 4; $i++) {
            $combo = Product::create([
                'category_id' => $category->id,
                'name' => "Combo {$i}",
                'slug' => "combo-{$i}",
                'is_active' => true,
                'is_featured' => true,
                'is_combo' => true,
            ]);

            $variant = ProductVariant::create([
                'product_id' => $combo->id,
                'name' => 'Box',
                'price' => 500,
                'stock' => 0,
            ]);

            foreach (array_slice($components, $i * 2, 2) as $component) {
                ComboItem::create([
                    'combo_variant_id' => $variant->id,
                    'component_variant_id' => $component->id,
                    'quantity' => 1,
                ]);
            }
        }

        // The layout reads a dozen of these on every page.
        foreach (['site_title', 'phone', 'whatsapp', 'facebook', 'instagram', 'tiktok', 'youtube', 'address'] as $key) {
            Setting::put($key, 'x');
        }
    }

    private function countQueries(callable $work): int
    {
        DB::flushQueryLog();
        DB::enableQueryLog();

        $work();

        $count = count(DB::getQueryLog());
        DB::disableQueryLog();

        return $count;
    }

    public static function storefrontPages(): array
    {
        return [
            'home' => ['/', 18],
            'shop' => ['/shop', 12],
            'cart' => ['/cart', 8],
        ];
    }

    #[DataProvider('storefrontPages')]
    public function test_a_storefront_page_stays_within_its_budget(string $url, int $budget): void
    {
        // Warm anything cached on first use, then measure a normal request.
        $this->get($url)->assertOk();

        $count = $this->countQueries(fn () => $this->get($url)->assertOk());

        $this->assertLessThanOrEqual(
            $budget,
            $count,
            "{$url} ran {$count} queries, over its budget of {$budget}. Look for a missing eager load."
        );
    }

    public function test_the_product_page_does_not_query_per_variant(): void
    {
        $this->get('/product/combo-1')->assertOk();

        $count = $this->countQueries(fn () => $this->get('/product/combo-1')->assertOk());

        $this->assertLessThanOrEqual(14, $count, "The product page ran {$count} queries.");
    }

    public function test_the_pos_screen_loads_every_variant_in_a_handful_of_queries(): void
    {
        $admin = User::factory()->superAdmin()->create();

        $this->actingAs($admin)->get('/admin/pos')->assertOk();

        $count = $this->countQueries(fn () => $this->actingAs($admin)->get('/admin/pos')->assertOk());

        // 28 variants, four of them combos: without the eager load this was 30+.
        $this->assertLessThanOrEqual(10, $count, "The POS screen ran {$count} queries.");
    }

    public function test_settings_are_read_once_however_many_are_asked_for(): void
    {
        Setting::flush();

        $count = $this->countQueries(function () {
            for ($i = 0; $i < 30; $i++) {
                Setting::get('site_title');
                Setting::get('phone');
                Setting::value('address', 'bn');
            }
        });

        // One read of the whole table, plus whatever the cache store costs.
        $this->assertLessThanOrEqual(3, $count, "Reading settings took {$count} queries.");
    }

    public function test_writing_a_setting_is_visible_immediately(): void
    {
        Setting::put('site_title', 'Before');
        $this->assertSame('Before', Setting::get('site_title'));

        Setting::put('site_title', 'After');
        $this->assertSame('After', Setting::get('site_title'));

        // Including a write that goes around the helper.
        Setting::where('key', 'site_title')->first()->update(['value_en' => 'Direct', 'value_bn' => 'Direct']);
        $this->assertSame('Direct', Setting::get('site_title'));
    }

    public function test_deleting_a_setting_clears_it_from_the_cache(): void
    {
        Setting::put('phone', '01700000000');
        $this->assertSame('01700000000', Setting::get('phone'));

        Setting::where('key', 'phone')->first()->delete();

        $this->assertNull(Setting::get('phone'));
    }
}
