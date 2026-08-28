<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\Unit;
use App\Models\User;
use Database\Seeders\UnitSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class UnitTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    public function test_the_seeder_installs_the_units_this_shop_sells_in(): void
    {
        $this->seed(UnitSeeder::class);

        $this->assertDatabaseHas('units', ['short_code' => 'kg', 'name_bn' => 'কেজি']);
        $this->assertSame(7, Unit::count());

        // Re-running must not duplicate.
        $this->seed(UnitSeeder::class);
        $this->assertSame(7, Unit::count());
    }

    public function test_an_admin_can_create_a_unit(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.units.store'), [
                'name' => 'Kilogram',
                'name_bn' => 'কেজি',
                'short_code' => 'kg',
            ])
            ->assertRedirect(route('admin.units.index'));

        $this->assertDatabaseHas('units', ['short_code' => 'kg', 'is_active' => true]);
    }

    public function test_short_codes_must_be_unique(): void
    {
        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);

        $this->actingAs($this->admin())
            ->post(route('admin.units.store'), ['name' => 'Kilo', 'short_code' => 'kg'])
            ->assertSessionHasErrors('short_code');

        $this->assertSame(1, Unit::count());
    }

    public function test_editing_a_unit_keeps_its_own_short_code(): void
    {
        $unit = Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);

        $this->actingAs($this->admin())
            ->put(route('admin.units.update', $unit), [
                'name' => 'Kilogramme',
                'short_code' => 'kg',
            ])
            ->assertRedirect(route('admin.units.index'))
            ->assertSessionHasNoErrors();

        $this->assertSame('Kilogramme', $unit->fresh()->name);
    }

    public function test_a_unit_in_use_cannot_be_deleted(): void
    {
        $unit = Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);
        $this->variantUsing($unit);

        $this->actingAs($this->admin())
            ->delete(route('admin.units.destroy', $unit))
            ->assertSessionHasErrors('unit');

        $this->assertModelExists($unit);
    }

    public function test_an_unused_unit_can_be_deleted(): void
    {
        $unit = Unit::create(['name' => 'Dozen', 'short_code' => 'dz']);

        $this->actingAs($this->admin())
            ->delete(route('admin.units.destroy', $unit))
            ->assertRedirect(route('admin.units.index'));

        $this->assertModelMissing($unit);
    }

    public function test_a_product_variant_can_be_saved_with_a_unit(): void
    {
        $unit = Unit::create(['name' => 'Kilogram', 'short_code' => 'kg']);
        $category = Category::create(['name' => 'Fruits', 'slug' => 'fruits']);

        $this->actingAs($this->admin())
            ->post(route('admin.products.store'), [
                'name_en' => 'Himsagar Mango',
                'name_bn' => 'হিমসাগর আম',
                'category_id' => $category->id,
                'variants' => [
                    ['name' => '3 কেজি', 'unit_id' => $unit->id, 'unit_value' => 3, 'price' => 1200, 'stock' => 5],
                ],
            ])
            ->assertRedirect(route('admin.products.index'));

        $variant = ProductVariant::firstOrFail();

        $this->assertSame($unit->id, $variant->unit_id);
        $this->assertEquals(3, $variant->unit_value);
        $this->assertSame('3 kg', $variant->measure);
    }

    public function test_a_variant_without_a_unit_falls_back_to_its_name(): void
    {
        $variant = $this->variantUsing(null);

        $this->assertNull($variant->unit_id);
        $this->assertSame($variant->name, $variant->measure);
    }

    public function test_the_measure_trims_pointless_decimals(): void
    {
        $unit = Unit::create(['name' => 'Litre', 'short_code' => 'L']);

        $this->assertSame('1.5 L', $this->variantUsing($unit, 1.5)->measure);
        $this->assertSame('2 L', $this->variantUsing($unit, 2.000)->measure);
        $this->assertSame('0.25 L', $this->variantUsing($unit, 0.25)->measure);
    }

    public function test_the_product_form_offers_only_active_units(): void
    {
        Unit::create(['name' => 'Kilogram', 'short_code' => 'kg', 'is_active' => true]);
        Unit::create(['name' => 'Retired', 'short_code' => 'old', 'is_active' => false]);

        $offered = $this->actingAs($this->admin())
            ->get(route('admin.products.create'))
            ->viewData('units');

        $this->assertCount(1, $offered);
        $this->assertSame('kg', $offered->first()->short_code);
    }

    public function test_a_customer_cannot_manage_units(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.units.index'))
            ->assertForbidden();
    }

    private function variantUsing(?Unit $unit, float $value = 3): ProductVariant
    {
        static $counter = 0;
        $counter++;

        $category = Category::firstOrCreate(['slug' => 'fruits'], ['name' => 'Fruits']);
        $product = Product::firstOrCreate(
            ['slug' => 'himsagar-mango'],
            ['category_id' => $category->id, 'name' => 'Himsagar Mango', 'is_active' => true]
        );

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => "Variant {$counter}",
            'unit_id' => $unit?->id,
            'unit_value' => $unit ? $value : null,
            'price' => 1200,
            'stock' => 5,
        ]);
    }
}
