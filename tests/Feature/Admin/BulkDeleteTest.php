<?php

namespace Tests\Feature\Admin;

use App\Models\Adjustment;
use App\Models\Category;
use App\Models\Expense;
use App\Models\Page;
use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductVariant;
use App\Models\Purchase;
use App\Models\Supplier;
use App\Models\Unit;
use App\Models\User;
use App\Support\AdminModules;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Models\Role;
use Tests\TestCase;

/**
 * Deleting a product used to leave every gallery photo but the thumbnail
 * orphaned on disk, and rows could only be removed one at a time.
 */
class BulkDeleteTest extends TestCase
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

    private function productWithImages(int $count = 3, string $name = 'Himsagar Mango'): Product
    {
        $category = Category::firstOrCreate(['slug' => 'fruits'], ['name' => 'Fruits']);

        $files = [];
        for ($i = 1; $i <= $count; $i++) {
            $files[] = UploadedFile::fake()->image("photo-{$i}.png");
        }

        $this->actingAs($this->admin())->post(route('admin.products.store'), [
            'name_en' => $name,
            'name_bn' => 'হিমসাগর আম',
            'category_id' => $category->id,
            'images' => $files,
            'variants' => [['name' => '1 kg', 'price' => 100, 'stock' => 5]],
        ]);

        return Product::where('name_en', $name)->firstOrFail();
    }

    /* ------------------------------------------------------ image cleanup */

    public function test_deleting_a_product_removes_every_gallery_file(): void
    {
        $product = $this->productWithImages(3);
        $paths = $product->images->pluck('path');

        $this->assertCount(3, $paths);
        $paths->each(fn ($path) => Storage::disk('uploads')->assertExists($path));

        $this->actingAs($this->admin())
            ->delete(route('admin.products.destroy', $product))
            ->assertRedirect();

        $paths->each(fn ($path) => Storage::disk('uploads')->assertMissing($path));
        $this->assertSame(0, ProductImage::count());
    }

    public function test_deleting_a_category_removes_its_image(): void
    {
        $this->actingAs($this->admin())->post(route('admin.categories.store'), [
            'name_en' => 'Fruits',
            'name_bn' => 'ফল',
            'image' => UploadedFile::fake()->image('fruits.png'),
        ]);

        $category = Category::firstOrFail();
        $path = $category->getRawOriginal('image');

        Storage::disk('uploads')->assertExists($path);

        $this->actingAs($this->admin())->delete(route('admin.categories.destroy', $category));

        Storage::disk('uploads')->assertMissing($path);
    }

    public function test_a_shipped_asset_is_never_deleted_from_disk(): void
    {
        $category = Category::create([
            'name' => 'Fruits',
            'slug' => 'fruits',
            // Bare filename: a file in public/assets, not an upload.
            'image' => 'mangoes.png',
        ]);

        $this->actingAs($this->admin())->delete(route('admin.categories.destroy', $category));

        // Nothing was on the fake disk to begin with; the point is it did not throw
        // and did not try to delete outside the uploads folder.
        $this->assertModelMissing($category);
        $this->assertSame([], Storage::disk('uploads')->allFiles());
    }

    /* -------------------------------------------------------- bulk delete */

    public function test_several_rows_go_at_once(): void
    {
        foreach (['Kilogram' => 'kg', 'Litre' => 'L', 'Piece' => 'pc'] as $name => $code) {
            Unit::create(['name' => $name, 'short_code' => $code]);
        }

        $ids = Unit::whereIn('short_code', ['kg', 'L'])->pluck('id')->all();

        $this->actingAs($this->admin())
            ->delete(route('admin.units.bulkDestroy'), ['ids' => $ids])
            ->assertRedirect(route('admin.units.index'))
            ->assertSessionHas('success');

        $this->assertSame(1, Unit::count());
        $this->assertSame('pc', Unit::first()->short_code);
    }

    public function test_a_row_that_is_still_in_use_is_kept_and_reported(): void
    {
        $used = Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);
        $free = Unit::create(['name' => 'Litre', 'short_code' => 'L']);

        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits']);
        $product = Product::create([
            'category_id' => $category->id,
            'name' => 'Mango',
            'slug' => 'mango',
        ]);
        ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'unit_id' => $used->id,
            'price' => 100,
            'stock' => 1,
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.units.bulkDestroy'), ['ids' => [$used->id, $free->id]])
            ->assertSessionHasErrors('bulk');

        $this->assertModelExists($used);
        $this->assertModelMissing($free);
    }

    public function test_bulk_delete_also_clears_the_image_files(): void
    {
        $first = $this->productWithImages(2, 'Himsagar Mango');
        $second = $this->productWithImages(2, 'Cow Ghee');

        $paths = $first->images->pluck('path')->merge($second->images->pluck('path'));
        $this->assertCount(4, $paths);

        $this->actingAs($this->admin())
            ->delete(route('admin.products.bulkDestroy'), ['ids' => [$first->id, $second->id]])
            ->assertRedirect();

        $this->assertSame(0, Product::count());
        $paths->each(fn ($path) => Storage::disk('uploads')->assertMissing($path));
    }

    public function test_selecting_nothing_says_so_rather_than_failing(): void
    {
        Supplier::create(['name' => 'Chapai Traders']);

        $this->actingAs($this->admin())
            ->delete(route('admin.suppliers.bulkDestroy'), ['ids' => []])
            ->assertRedirect()
            ->assertSessionHas('error');

        $this->assertSame(1, Supplier::count());
    }

    public function test_rubbish_ids_are_ignored(): void
    {
        $expense = Expense::create([
            'title' => 'Packaging',
            'category' => 'supplies',
            'amount' => 100,
            'expense_date' => date('Y-m-d'),
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.expenses.bulkDestroy'), ['ids' => ['abc', '99999']])
            ->assertRedirect();

        $this->assertModelExists($expense);
    }

    /* --------------------------------------------------------- permission */

    public function test_bulk_delete_needs_the_delete_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $unit = Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['units.view', 'units.edit']);

        $this->actingAs($staff->fresh())
            ->delete(route('admin.units.bulkDestroy'), ['ids' => [$unit->id]])
            ->assertForbidden();

        $this->assertModelExists($unit);

        $staff->syncPermissions(['units.view', 'units.delete']);

        $this->actingAs($staff->fresh())
            ->delete(route('admin.units.bulkDestroy'), ['ids' => [$unit->id]])
            ->assertRedirect();

        $this->assertModelMissing($unit);
    }

    public function test_the_table_offers_checkboxes_and_an_action_bar(): void
    {
        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);

        $html = $this->actingAs($this->admin())->get('/admin/units')->getContent();

        $this->assertStringContainsString('data-bulk-all', $html);
        $this->assertStringContainsString('data-bulk-bar', $html);
        $this->assertStringContainsString('name="ids[]"', $html);
    }

    public function test_someone_without_delete_rights_sees_no_checkboxes(): void
    {
        $this->seed(PermissionSeeder::class);

        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['units.view']);

        $html = $this->actingAs($staff->fresh())->get('/admin/units')->getContent();

        $this->assertStringNotContainsString('name="ids[]"', $html);
        $this->assertStringNotContainsString('data-bulk-bar', $html);
    }

    public function test_the_bulk_form_does_not_swallow_the_row_delete_forms(): void
    {
        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);

        $html = $this->actingAs($this->admin())->get('/admin/units')->getContent();

        // A form nested inside another form is dropped by the parser, and the
        // row's trash button would then submit the bulk delete instead. The
        // rows join the bulk form by id, so it has to close before the table.
        $bulkForm = substr($html, strpos($html, 'id="bulk-units"'));
        $bulkForm = substr($bulkForm, 0, strpos($bulkForm, '</form>'));

        $this->assertStringNotContainsString('<table', $bulkForm);
        $this->assertStringNotContainsString('<form', $bulkForm);
        $this->assertStringContainsString('form="bulk-units"', $html);
    }

    public function test_a_page_can_be_deleted_one_at_a_time_too(): void
    {
        $page = Page::create([
            'slug' => 'return-policy',
            'title_en' => 'Return Policy',
            'title_bn' => 'রিটার্ন নীতি',
            'content_en' => 'Send it back within 7 days.',
            'content_bn' => '৭ দিনের মধ্যে ফেরত দিন।',
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.pages.destroy', $page))
            ->assertRedirect(route('admin.pages.index'));

        $this->assertModelMissing($page);
    }

    /* ------------------------------------------- stock-bearing records */

    private function variant(int $stock = 10): ProductVariant
    {
        $category = Category::firstOrCreate(['slug' => 'fruits'], ['name' => 'Fruits']);
        $product = Product::firstOrCreate(
            ['slug' => 'mango'],
            ['category_id' => $category->id, 'name' => 'Mango']
        );

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 kg',
            'price' => 100,
            'stock' => $stock,
        ]);
    }

    public function test_bulk_deleting_purchases_puts_the_stock_back(): void
    {
        $variant = $this->variant(10);
        $supplier = Supplier::create(['name' => 'Chapai Traders']);

        $purchases = collect([4, 3])->map(fn ($qty) => Purchase::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'purchase_price' => 80,
            'quantity' => $qty,
            'purchase_date' => date('Y-m-d'),
        ]));

        $this->actingAs($this->admin())
            ->delete(route('admin.purchases.bulkDestroy'), ['ids' => $purchases->pluck('id')->all()])
            ->assertRedirect(route('admin.purchases.index'));

        $this->assertSame(0, Purchase::count());
        $this->assertSame(3, $variant->fresh()->stock);
    }

    public function test_bulk_deleting_adjustments_reverses_them_by_type(): void
    {
        $variant = $this->variant(10);

        $damage = Adjustment::create([
            'product_variant_id' => $variant->id,
            'quantity' => 2,
            'type' => 'damage',
            'adjustment_date' => date('Y-m-d'),
        ]);
        $returned = Adjustment::create([
            'product_variant_id' => $variant->id,
            'quantity' => 5,
            'type' => Adjustment::RETURNED,
            'adjustment_date' => date('Y-m-d'),
        ]);

        $this->actingAs($this->admin())
            ->delete(route('admin.adjustments.bulkDestroy'), ['ids' => [$damage->id, $returned->id]])
            ->assertRedirect(route('admin.adjustments.index'));

        // The damage is given back (+2) and the return is taken away (-5).
        $this->assertSame(7, $variant->fresh()->stock);
    }

    public function test_deleting_one_purchase_still_reverts_its_stock(): void
    {
        $variant = $this->variant(10);
        $supplier = Supplier::create(['name' => 'Chapai Traders']);

        $purchase = Purchase::create([
            'supplier_id' => $supplier->id,
            'product_variant_id' => $variant->id,
            'purchase_price' => 80,
            'quantity' => 6,
            'purchase_date' => date('Y-m-d'),
        ]);

        $this->actingAs($this->admin())->delete(route('admin.purchases.destroy', $purchase));

        $this->assertSame(4, $variant->fresh()->stock);
    }

    /* -------------------------------------------------- guarded records */

    public function test_the_super_admin_role_and_roles_in_use_survive_a_bulk_delete(): void
    {
        $this->seed(PermissionSeeder::class);

        $super = Role::findByName(AdminModules::SUPER_ADMIN);
        $held = Role::findOrCreate('Cashier', 'web');
        $spare = Role::findOrCreate('Packer', 'web');

        User::factory()->admin()->create()->assignRole($held);

        $this->actingAs($this->admin())
            ->delete(route('admin.roles.bulkDestroy'), ['ids' => [$super->id, $held->id, $spare->id]])
            ->assertSessionHasErrors('bulk');

        $this->assertModelExists($super);
        $this->assertModelExists($held);
        $this->assertModelMissing($spare);
    }

    public function test_you_cannot_bulk_delete_yourself(): void
    {
        $me = $this->admin();
        $other = User::factory()->admin()->create();

        $this->actingAs($me)
            ->delete(route('admin.users.bulkDestroy'), ['ids' => [$me->id, $other->id]])
            ->assertSessionHasErrors('bulk');

        $this->assertModelExists($me);
        $this->assertModelMissing($other);
    }

    public function test_an_ordinary_product_cannot_be_removed_through_the_combo_route(): void
    {
        $product = $this->productWithImages(1, 'Himsagar Mango');

        $this->actingAs($this->admin())
            ->delete(route('admin.combos.bulkDestroy'), ['ids' => [$product->id]])
            ->assertSessionHasErrors('bulk');

        $this->assertModelExists($product);
    }
}
