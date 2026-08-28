<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Expense;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Only three admin tables used to be searchable, and the pages list had no
 * pagination at all.
 */
class TableSearchTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function variant(string $productName, string $sku = 'SKU-1'): ProductVariant
    {
        $category = Category::firstOrCreate(['slug' => 'fruits'], ['name' => 'Fruits']);

        $product = Product::create([
            'category_id' => $category->id,
            'name' => $productName,
            'slug' => str($productName)->slug()->value(),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'sku' => $sku,
            'price' => 100,
            'stock' => 5,
        ]);
    }

    /** Build two rows, then check searching narrows to the first. */
    private function assertSearchNarrows(string $url, string $key, string $term, string $keep, string $drop): void
    {
        $all = $this->actingAs($this->admin())->get($url)->viewData($key);
        $this->assertCount(2, $all, "Both rows should be listed before searching {$url}");

        $found = $this->actingAs($this->admin())->get($url.'?search='.urlencode($term))->viewData($key);

        $this->assertCount(1, $found, "Search on {$url} should leave one row");

        $html = $this->actingAs($this->admin())->get($url.'?search='.urlencode($term))->getContent();
        $this->assertStringContainsString($keep, $html);
        $this->assertStringNotContainsString($drop, $html);
    }

    public function test_categories_can_be_searched(): void
    {
        Category::create(['name' => 'Fresh Mangoes', 'slug' => 'fresh-mangoes']);
        Category::create(['name' => 'Pure Honey', 'slug' => 'pure-honey']);

        $this->assertSearchNarrows('/admin/categories', 'categories', 'Mango', 'Fresh Mangoes', 'Pure Honey');
    }

    public function test_units_can_be_searched_by_name_or_code(): void
    {
        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);
        Unit::create(['name' => 'Litre', 'short_code' => 'L']);

        $this->assertSearchNarrows('/admin/units', 'units', 'Kilo', 'Kilogram', 'Litre');

        $byCode = $this->actingAs($this->admin())->get('/admin/units?search=kg')->viewData('units');
        $this->assertCount(1, $byCode);
    }

    public function test_suppliers_can_be_searched_by_phone(): void
    {
        Supplier::create(['name' => 'Chapai Traders', 'phone' => '01711111111']);
        Supplier::create(['name' => 'Rajshahi Farms', 'phone' => '01822222222']);

        $found = $this->actingAs($this->admin())
            ->get('/admin/suppliers?search=01822222222')
            ->viewData('suppliers');

        $this->assertCount(1, $found);
        $this->assertSame('Rajshahi Farms', $found->first()->name);
    }

    public function test_expenses_can_be_searched(): void
    {
        foreach ([['Packaging', 'supplies'], ['Rent', 'office']] as [$title, $category]) {
            Expense::create([
                'title' => $title,
                'category' => $category,
                'amount' => 100,
                'expense_date' => date('Y-m-d'),
            ]);
        }

        $this->assertSearchNarrows('/admin/expenses', 'expenses', 'Packag', 'Packaging', 'Rent');
    }

    public function test_inventory_can_be_searched_through_the_product_name(): void
    {
        $this->variant('Himsagar Mango', 'MNG-1');
        $this->variant('Cow Ghee', 'GHE-1');

        $rows = $this->actingAs($this->admin())
            ->get('/admin/inventory?search=Himsagar')
            ->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertSame('Himsagar Mango', $rows->first()['product_name']);
    }

    public function test_inventory_can_be_searched_by_sku(): void
    {
        $this->variant('Himsagar Mango', 'MNG-1');
        $this->variant('Cow Ghee', 'GHE-1');

        $rows = $this->actingAs($this->admin())
            ->get('/admin/inventory?search=GHE-1')
            ->viewData('rows');

        $this->assertCount(1, $rows);
        $this->assertSame('Cow Ghee', $rows->first()['product_name']);
    }

    public function test_roles_can_be_searched(): void
    {
        Role::create(['name' => 'Cashier', 'guard_name' => 'web']);
        Role::create(['name' => 'Stock Manager', 'guard_name' => 'web']);

        $found = $this->actingAs($this->admin())->get('/admin/roles?search=Cash')->viewData('roles');

        $this->assertCount(1, $found);
        $this->assertSame('Cashier', $found->first()->name);
    }

    public function test_staff_can_be_searched_by_email(): void
    {
        User::factory()->admin()->create(['name' => 'Rahim', 'email' => 'rahim@shop.test']);
        User::factory()->admin()->create(['name' => 'Karim', 'email' => 'karim@shop.test']);

        $found = $this->actingAs($this->admin())
            ->get('/admin/users?search=karim@shop.test')
            ->viewData('users');

        $this->assertCount(1, $found);
        $this->assertSame('Karim', $found->first()->name);
    }

    /* --------------------------------------------------------- pagination */

    public function test_the_pages_list_is_paginated(): void
    {
        foreach (range(1, 25) as $i) {
            Page::create([
                'slug' => "page-{$i}",
                'title_en' => "Page {$i}",
                'title_bn' => "পাতা {$i}",
                'content_en' => 'x',
                'content_bn' => 'x',
            ]);
        }

        $pages = $this->actingAs($this->admin())->get('/admin/pages')->viewData('pages');

        $this->assertCount(20, $pages, 'The pages list used to render every row at once.');
        $this->assertTrue($pages->hasPages());
    }

    public function test_a_search_survives_paging(): void
    {
        foreach (range(1, 25) as $i) {
            Page::create([
                'slug' => "policy-{$i}",
                'title_en' => "Policy {$i}",
                'title_bn' => "নীতি {$i}",
                'content_en' => 'x',
                'content_bn' => 'x',
            ]);
        }

        Page::create([
            'slug' => 'about-us',
            'title_en' => 'About',
            'title_bn' => 'পরিচিতি',
            'content_en' => 'x',
            'content_bn' => 'x',
        ]);

        $html = $this->actingAs($this->admin())->get('/admin/pages?search=policy')->getContent();

        // withQueryString keeps the term on the page links.
        $this->assertStringContainsString('search=policy', $html);
        $this->assertStringNotContainsString('about-us', $html);
    }

    /* -------------------------------------------------------------- shape */

    public static function searchableTables(): array
    {
        return [
            'categories' => ['/admin/categories'],
            'units' => ['/admin/units'],
            'suppliers' => ['/admin/suppliers'],
            'expenses' => ['/admin/expenses'],
            'purchases' => ['/admin/purchases'],
            'adjustments' => ['/admin/adjustments'],
            'pages' => ['/admin/pages'],
            'roles' => ['/admin/roles'],
            'users' => ['/admin/users'],
            'combos' => ['/admin/combos'],
            'inventory' => ['/admin/inventory'],
        ];
    }

    #[DataProvider('searchableTables')]
    public function test_every_table_offers_a_search_box(string $url): void
    {
        $html = $this->actingAs($this->admin())->get($url)->getContent();

        $this->assertStringContainsString('name="search"', $html, "No search box on {$url}");
    }

    #[DataProvider('searchableTables')]
    public function test_a_search_that_matches_nothing_is_not_an_error(string $url): void
    {
        $this->actingAs($this->admin())->get($url.'?search=zzzz-no-such-row')->assertOk();
    }
}
