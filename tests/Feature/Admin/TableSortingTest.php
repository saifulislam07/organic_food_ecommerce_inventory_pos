<?php

namespace Tests\Feature\Admin;

use App\Models\Adjustment;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Expense;
use App\Models\HeroSlide;
use App\Models\Investment;
use App\Models\Investor;
use App\Models\LandingPage;
use App\Models\Order;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Review;
use App\Models\SiteBlock;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Models\Withdrawal;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Schema;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

/**
 * Click-to-sort column headers on the admin tables.
 *
 * The three things that go wrong quietly: the ?sort= key reaching SQL unchecked,
 * the chosen column being forgotten the moment you page, and rows that tie on
 * the sort column shuffling between pages so records vanish out of the middle.
 */
class TableSortingTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function order(string $number, float $total, string $customer = 'Rahim'): Order
    {
        return Order::create([
            'order_number' => $number,
            'customer_name' => $customer,
            'customer_phone' => '01700000000',
            'customer_address' => 'Dhaka',
            'subtotal' => $total,
            'discount_amount' => 0,
            'delivery_charge' => 0,
            'total' => $total,
            'status' => 'pending',
            'payment_method' => 'cod',
            'source' => 'website',
        ]);
    }

    /** @return list<string> the order numbers, in the order the page listed them */
    private function listedOrders(string $query = ''): array
    {
        return $this->actingAs($this->admin())
            ->get(route('admin.orders.index').$query)
            ->viewData('orders')
            ->pluck('order_number')
            ->all();
    }

    public function test_a_column_sorts_both_ways(): void
    {
        $this->order('A-1', 500);
        $this->order('A-2', 1500);
        $this->order('A-3', 1000);

        $this->assertSame(['A-2', 'A-3', 'A-1'], $this->listedOrders('?sort=total&dir=desc'));
        $this->assertSame(['A-1', 'A-3', 'A-2'], $this->listedOrders('?sort=total&dir=asc'));
    }

    public function test_the_default_is_newest_first(): void
    {
        $this->order('A-1', 100)->forceFill(['created_at' => now()->subDay()])->save();
        $this->order('A-2', 100)->forceFill(['created_at' => now()])->save();

        $this->assertSame(['A-2', 'A-1'], $this->listedOrders());
    }

    /**
     * A key that is not on the whitelist must fall back to the default rather
     * than reach the database.
     */
    public function test_an_unknown_sort_key_is_ignored(): void
    {
        $this->order('A-1', 100);

        $response = $this->actingAs($this->admin())
            ->get(route('admin.orders.index').'?sort='.urlencode('total, (select 1)').'&dir=asc');

        $response->assertOk();
        $this->assertSame(['A-1'], $response->viewData('orders')->pluck('order_number')->all());
    }

    public function test_a_junk_direction_falls_back_instead_of_reaching_sql(): void
    {
        $this->order('A-1', 500);
        $this->order('A-2', 1500);

        // Not 'asc' or 'desc', so the default direction stands.
        $this->assertSame(['A-2', 'A-1'], $this->listedOrders('?sort=total&dir=asc;drop'));
    }

    /**
     * Every row here has the same status, so without a tie-breaker the database
     * is free to return them in a different order on each page — which silently
     * drops some records and repeats others.
     */
    public function test_rows_that_tie_on_the_sort_column_still_page_cleanly(): void
    {
        foreach (range(1, 20) as $n) {
            $this->order(sprintf('A-%02d', $n), 100);
        }

        $first = $this->listedOrders('?sort=status&dir=asc');
        $second = $this->listedOrders('?sort=status&dir=asc&page=2');

        $this->assertCount(15, $first);
        $this->assertCount(5, $second);
        $this->assertSame([], array_intersect($first, $second), 'No order should appear on both pages');
        $this->assertCount(20, array_unique(array_merge($first, $second)));
    }

    public function test_sorting_survives_paging(): void
    {
        foreach (range(1, 20) as $n) {
            $this->order(sprintf('A-%02d', $n), $n * 100);
        }

        $page2 = $this->actingAs($this->admin())
            ->get(route('admin.orders.index').'?sort=total&dir=asc&page=2');

        // Cheapest first, so page two is the five dearest, still ascending.
        $this->assertSame(
            ['A-16', 'A-17', 'A-18', 'A-19', 'A-20'],
            $page2->viewData('orders')->pluck('order_number')->all()
        );
    }

    public function test_sorting_and_filtering_combine(): void
    {
        $this->order('A-1', 500)->update(['status' => 'delivered']);
        $this->order('A-2', 1500);
        $this->order('A-3', 900)->update(['status' => 'delivered']);

        $this->assertSame(['A-3', 'A-1'], $this->listedOrders('?status=delivered&sort=total&dir=desc'));
    }

    /** The header links have to carry the filter, or clicking one clears it. */
    public function test_a_sort_link_keeps_the_current_filter(): void
    {
        $this->order('A-1', 500)->update(['status' => 'delivered']);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index').'?status=delivered')
            ->assertSee('status=delivered&amp;sort=total', false);
    }

    /** The arrow has to land on the column actually in use. */
    public function test_the_active_column_is_marked_for_the_reader(): void
    {
        $this->order('A-1', 500);

        $this->actingAs($this->admin())
            ->get(route('admin.orders.index').'?sort=total&dir=asc')
            ->assertSee('aria-sort="ascending"', false)
            ->assertSee('bi-sort-up', false);
    }

    /** Products lost its search on page two, having no withQueryString(). */
    public function test_the_product_search_survives_paging(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        foreach (range(1, 20) as $n) {
            Product::create([
                'category_id' => $category->id,
                'name' => 'Mango '.$n,
                'slug' => 'mango-'.$n,
            ]);
        }

        Product::create(['category_id' => $category->id, 'name' => 'Guava', 'slug' => 'guava']);

        $page2 = $this->actingAs($this->admin())
            ->get(route('admin.products.index').'?search=Mango&page=2')
            ->viewData('products');

        $this->assertSame(20, $page2->total(), 'The Guava should still be filtered out on page two');
    }

    /** Bootstrap markup, not the framework's Tailwind default. */
    public function test_pagination_renders_as_bootstrap(): void
    {
        foreach (range(1, 25) as $n) {
            Supplier::create(['name' => 'Supplier '.$n]);
        }

        $this->actingAs($this->admin())
            ->get(route('admin.suppliers.index'))
            ->assertSee('class="pagination"', false)
            ->assertSee('page-link', false)
            ->assertDontSee('relative inline-flex items-center', false);
    }

    /* ------------------------------------------------- every column, every list */

    /**
     * The whitelists name database columns, and a name that does not exist
     * only fails when somebody clicks that particular header — which is how a
     * mistyped column reaches production. Every key gets clicked here instead.
     *
     * @return array<string, array{string, string}>
     */
    public static function sortableColumns(): array
    {
        $lists = [
            'admin.adjustments.index' => ['adjustment_date', 'product', 'type', 'quantity', 'reason'],
            'admin.blocks.index' => ['title', 'status', 'sort_order'],
            'admin.categories.index' => ['name', 'products', 'status', 'sort_order'],
            'admin.combos.index' => ['name', 'created_at'],
            'admin.coupons.index' => ['code', 'discount', 'window', 'used', 'status', 'created_at'],
            'admin.customers.index' => ['name', 'mobile', 'email', 'orders', 'lifetime', 'created_at'],
            'admin.expenses.index' => ['expense_date', 'title', 'category', 'amount'],
            'admin.investments.index' => ['invested_at', 'investor', 'account', 'amount'],
            'admin.investors.index' => ['name', 'phone', 'invested', 'withdrawn'],
            'admin.landing-pages.index' => ['page', 'status', 'views', 'orders', 'revenue', 'created_at'],
            'admin.orders.index' => [
                'order_number', 'created_at', 'customer_name', 'total',
                'collected_amount', 'courier', 'source', 'status',
            ],
            'admin.pages.index' => ['title', 'slug', 'status'],
            'admin.products.index' => ['name', 'category', 'status', 'created_at'],
            'admin.purchases.index' => ['purchase_date', 'supplier', 'product', 'unit_price', 'quantity'],
            'admin.reviews.index' => ['product', 'customer', 'rating', 'status', 'created_at'],
            'admin.roles.index' => ['name', 'permissions', 'staff'],
            'admin.sliders.index' => ['title', 'status', 'sort_order'],
            'admin.suppliers.index' => ['name', 'contact_person', 'phone', 'email'],
            'admin.units.index' => ['name', 'short_code', 'used_by', 'status', 'sort_order'],
            'admin.users.index' => ['name', 'email', 'created_at'],
            'admin.withdrawals.index' => ['withdrawn_at', 'investor', 'account', 'amount'],
        ];

        $cases = [];

        foreach ($lists as $route => $keys) {
            foreach ($keys as $key) {
                $cases[$route.' by '.$key] = [$route, $key];
            }
        }

        return $cases;
    }

    /**
     * One row in every list. An empty table never reaches the ordered query at
     * all — the paginator answers straight from its count — so without this the
     * check below would happily pass on a column that does not exist.
     */
    private function seedOneOfEach(): void
    {
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);

        $product = Product::create([
            'category_id' => $category->id, 'name' => 'Mango', 'slug' => 'mango',
        ]);
        Product::create([
            'category_id' => $category->id, 'name' => 'Basket', 'slug' => 'basket', 'is_combo' => true,
        ]);

        $variant = ProductVariant::create([
            'product_id' => $product->id, 'name' => '1 kg', 'sku' => 'MNG-1', 'price' => 100, 'stock' => 10,
        ]);

        $supplier = Supplier::create(['name' => 'Karwan Bazar']);
        $investor = Investor::create(['name' => 'Karim']);

        Purchase::create([
            'supplier_id' => $supplier->id, 'product_variant_id' => $variant->id,
            'purchase_price' => 60, 'quantity' => 10, 'purchase_date' => now()->toDateString(),
        ]);
        Adjustment::create([
            'product_variant_id' => $variant->id, 'quantity' => 1,
            'type' => 'damage', 'reason' => 'Bruised', 'adjustment_date' => now()->toDateString(),
        ]);
        Investment::create([
            'investor_id' => $investor->id, 'amount' => 5000, 'invested_at' => now()->toDateString(),
        ]);
        Withdrawal::create([
            'investor_id' => $investor->id, 'amount' => 500, 'withdrawn_at' => now()->toDateString(),
        ]);
        Expense::create([
            'title' => 'Packing tape', 'category' => 'Packing',
            'amount' => 250, 'expense_date' => now()->toDateString(),
        ]);
        Coupon::create(['code' => 'EID10', 'type' => 'percent', 'value' => 10]);
        Review::create([
            'product_id' => $product->id, 'customer_name' => 'Rahim', 'rating' => 5, 'body' => 'Good',
        ]);
        Page::create(['slug' => 'about', 'title_en' => 'About us']);
        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);
        HeroSlide::create(['title_en' => 'Eid sale']);
        SiteBlock::create(['group' => 'header_menu', 'title_en' => 'Offers']);
        LandingPage::create([
            'slug' => 'eid-offer', 'internal_name' => 'Eid offer', 'headline' => 'Eid offer',
        ]);

        User::factory()->create(['role' => 'customer']);

        $this->order('A-1', 500);
    }

    #[DataProvider('sortableColumns')]
    public function test_every_sortable_column_actually_exists(string $route, string $key): void
    {
        $this->seedOneOfEach();

        foreach (['asc', 'desc'] as $direction) {
            $response = $this->actingAs($this->admin())
                ->get(route($route).'?sort='.$key.'&dir='.$direction);

            $response->assertOk();

            $listed = collect($response->original->getData())
                ->first(fn ($value) => $value instanceof LengthAwarePaginator);

            $this->assertNotNull($listed, "No paginator on {$route}");
            $this->assertGreaterThan(
                0,
                $listed->total(),
                "{$route} listed nothing, so sorting by {$key} was never actually run"
            );
        }
    }

    /* ----------------------------------------- the whitelists against the schema */

    /**
     * Every controller that sorts, and the model whose table its columns must
     * belong to.
     *
     * @return array<class-string<Model>, string>  model => controller file
     */
    private static function sortingControllers(): array
    {
        return [
            \App\Models\Adjustment::class => 'AdminAdjustmentController',
            \App\Models\Category::class => 'AdminCategoryController',
            \App\Models\Product::class => 'AdminComboController',
            \App\Models\Coupon::class => 'AdminCouponController',
            \App\Models\User::class => 'AdminCustomerController',
            \App\Models\Expense::class => 'AdminExpenseController',
            \App\Models\HeroSlide::class => 'AdminHeroSlideController',
            \App\Models\Investment::class => 'AdminInvestmentController',
            \App\Models\Investor::class => 'AdminInvestorController',
            \App\Models\LandingPage::class => 'AdminLandingPageController',
            \App\Models\Order::class => 'AdminOrderController',
            \App\Models\Page::class => 'AdminPageController',
            \App\Models\Purchase::class => 'AdminPurchaseController',
            \App\Models\Review::class => 'AdminReviewController',
            \Spatie\Permission\Models\Role::class => 'AdminRoleController',
            \App\Models\SiteBlock::class => 'AdminSiteBlockController',
            \App\Models\Supplier::class => 'AdminSupplierController',
            \App\Models\Unit::class => 'AdminUnitController',
            \App\Models\Withdrawal::class => 'AdminWithdrawalController',
        ];
    }

    /**
     * Names that are computed by withCount()/withSum() on the query rather than
     * stored, so they will not be found in the table.
     *
     * @return list<string>
     */
    private static function aggregateAliases(): array
    {
        return [
            'products_count', 'orders_count', 'variants_count',
            'permissions_count', 'users_count',
            'investments_sum_amount', 'withdrawals_sum_amount',
            'orders_total', 'revenue',
        ];
    }

    /**
     * Reads the whitelist straight out of each controller so this cannot drift
     * from what is actually wired up.
     *
     * @return list<string>
     */
    private function mappedColumns(string $controller): array
    {
        $source = file_get_contents(app_path("Http/Controllers/Admin/{$controller}.php"));

        $this->assertMatchesRegularExpression(
            '/\$this->applySort\(/',
            $source,
            "{$controller} does not call applySort()"
        );

        // The whitelist literal: from applySort( up to the closing "], '".
        preg_match('/\$this->applySort\(.*?\[(.*?)\n\s*\],\s*\'/s', $source, $matches);

        $this->assertNotEmpty($matches, "Could not read the whitelist out of {$controller}");

        // Right-hand sides only: 'key' => 'column', or 'key' => ['a', 'b'].
        preg_match_all("/=>\s*(\[[^\]]*\]|'[^']*')/", $matches[1], $values);

        $columns = [];

        foreach ($values[1] as $value) {
            preg_match_all("/'([^']+)'/", $value, $names);
            $columns = array_merge($columns, $names[1]);
        }

        $this->assertNotEmpty($columns, "No sortable columns found in {$controller}");

        return $columns;
    }

    /**
     * SQLite quietly accepts an unknown name in ORDER BY — it falls back to
     * reading the quoted identifier as a string literal, so the sort is a no-op
     * and the page still renders. MySQL, which is what production runs, throws.
     * That is why this checks the schema rather than the rendered page.
     */
    public function test_every_sorted_column_exists_on_its_table(): void
    {
        foreach (self::sortingControllers() as $modelClass => $controller) {
            /** @var Model $model */
            $model = new $modelClass;
            $table = $model->getTable();

            foreach ($this->mappedColumns($controller) as $column) {
                if (in_array($column, self::aggregateAliases(), true)) {
                    continue;
                }

                $this->assertTrue(
                    Schema::hasColumn($table, $column),
                    "{$controller} sorts by {$table}.{$column}, which does not exist"
                );
            }
        }
    }
}
