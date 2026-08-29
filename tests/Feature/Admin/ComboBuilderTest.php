<?php

namespace Tests\Feature\Admin;

use App\Models\Category;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Models\User;
use Database\Seeders\PermissionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

/**
 * A combo is composed on one screen: pick the products, name it, and set the
 * price against what the parts would cost separately.
 */
class ComboBuilderTest extends TestCase
{
    use RefreshDatabase;

    private ?User $admin = null;

    private Category $category;

    protected function setUp(): void
    {
        parent::setUp();

        $this->category = Category::create(['name' => 'Fruits', 'slug' => 'fruits', 'is_active' => true]);
    }

    private function admin(): User
    {
        return $this->admin ??= User::factory()->superAdmin()->create();
    }

    private function variant(string $name, int $stock = 10, float $price = 500): ProductVariant
    {
        $product = Product::create([
            'category_id' => $this->category->id,
            'name' => $name,
            'slug' => str($name)->slug()->value(),
            'is_active' => true,
        ]);

        return ProductVariant::create([
            'product_id' => $product->id,
            'name' => '1 unit',
            'price' => $price,
            'stock' => $stock,
        ]);
    }

    private function payload(array $components, array $overrides = []): array
    {
        return array_merge([
            'name_en' => 'Eid Gift Box',
            'name_bn' => 'ঈদ গিফট বক্স',
            'category_id' => $this->category->id,
            'short_description_bn' => 'উৎসবের জন্য',
            'compare_price' => 1500,
            'price' => 1200,
            'components' => $components,
        ], $overrides);
    }

    /* ------------------------------------------------------------- create */

    public function test_a_combo_is_created_in_one_go(): void
    {
        $mango = $this->variant('Mango', 20, 500);
        $ghee = $this->variant('Ghee', 10, 500);

        $this->actingAs($this->admin())
            ->post(route('admin.combos.store'), $this->payload([
                ['variant_id' => $mango->id, 'quantity' => 2],
                ['variant_id' => $ghee->id, 'quantity' => 1],
            ]))
            ->assertRedirect(route('admin.combos.index'))
            ->assertSessionHasNoErrors();

        $combo = Product::where('is_combo', true)->firstOrFail();

        $this->assertSame('Eid Gift Box', $combo->name_en);
        $this->assertSame('ঈদ গিফট বক্স', $combo->name_bn);
        $this->assertSame('উৎসবের জন্য', $combo->short_description_bn);
        $this->assertSame($this->category->id, $combo->category_id);
        $this->assertSame(1, $combo->variants()->count(), 'A combo is one bundle.');
        $this->assertSame(2, ComboItem::count());
    }

    public function test_the_parts_total_becomes_the_struck_through_price(): void
    {
        $mango = $this->variant('Mango', 20, 500);

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 3]],
            ['compare_price' => 1500, 'price' => 1200]
        ));

        $variant = Product::where('is_combo', true)->firstOrFail()->variants->first();

        $this->assertEquals(1500, $variant->price, 'What the parts cost separately.');
        $this->assertEquals(1200, $variant->sale_price, 'What the shop charges.');
        $this->assertEquals(1200, $variant->display_price);
        $this->assertTrue($variant->is_on_sale);
    }

    public function test_a_combo_holds_no_stock_of_its_own(): void
    {
        $mango = $this->variant('Mango', 9, 500);

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 3]]
        ));

        $variant = Product::where('is_combo', true)->firstOrFail()->variants->first();

        $this->assertSame(0, $variant->stock);
        $this->assertSame(3, $variant->available_stock, '9 mangoes / 3 per box');
    }

    /* ----------------------------------------------------------- refusals */

    public function test_at_least_one_product_is_required(): void
    {
        $this->actingAs($this->admin())
            ->post(route('admin.combos.store'), $this->payload([]))
            ->assertSessionHasErrors('components');

        $this->assertSame(0, Product::where('is_combo', true)->count());
    }

    public function test_the_same_product_cannot_be_listed_twice(): void
    {
        $mango = $this->variant('Mango');

        $this->actingAs($this->admin())
            ->post(route('admin.combos.store'), $this->payload([
                ['variant_id' => $mango->id, 'quantity' => 1],
                ['variant_id' => $mango->id, 'quantity' => 2],
            ]))
            ->assertSessionHasErrors('components');

        $this->assertSame(0, ComboItem::count());
    }

    public function test_a_combo_cannot_contain_another_combo(): void
    {
        $mango = $this->variant('Mango');

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 1]]
        ));

        $inner = Product::where('is_combo', true)->firstOrFail()->variants->first();

        $this->actingAs($this->admin())
            ->post(route('admin.combos.store'), $this->payload(
                [['variant_id' => $inner->id, 'quantity' => 1]],
                ['name_en' => 'Outer Box']
            ))
            ->assertSessionHasErrors('components');
    }

    public function test_an_existing_combo_is_never_offered_as_a_component(): void
    {
        $mango = $this->variant('Mango');

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 1]]
        ));

        $options = $this->actingAs($this->admin())
            ->get(route('admin.combos.create'))
            ->viewData('options');

        $this->assertCount(1, $options);
        $this->assertSame($mango->id, $options->first()['id']);
    }

    public function test_the_picker_carries_prices_so_the_total_can_be_worked_out(): void
    {
        $this->variant('Mango', 10, 750);

        $option = $this->actingAs($this->admin())
            ->get(route('admin.combos.create'))
            ->viewData('options')
            ->first();

        $this->assertEquals(750, $option['price']);
        $this->assertSame(10, $option['stock']);
        $this->assertArrayHasKey('image', $option);
    }

    /* --------------------------------------------------------------- edit */

    public function test_a_combo_can_be_edited_and_repriced(): void
    {
        $mango = $this->variant('Mango', 20, 500);
        $ghee = $this->variant('Ghee', 10, 500);

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 1]]
        ));

        $combo = Product::where('is_combo', true)->firstOrFail();

        $this->actingAs($this->admin())
            ->put(route('admin.combos.update', $combo), $this->payload(
                [
                    ['variant_id' => $mango->id, 'quantity' => 2],
                    ['variant_id' => $ghee->id, 'quantity' => 1],
                ],
                ['name_bn' => 'বড় গিফট বক্স', 'compare_price' => 1500, 'price' => 999]
            ))
            ->assertRedirect(route('admin.combos.index'))
            ->assertSessionHasNoErrors();

        $combo->refresh()->load('variants.comboItems');
        $variant = $combo->variants->first();

        $this->assertSame('বড় গিফট বক্স', $combo->name_bn);
        $this->assertEquals(999, $variant->sale_price);
        $this->assertSame(2, $variant->comboItems->count());
        $this->assertSame(1, $combo->variants()->count(), 'Editing must not add a second variant.');
    }

    public function test_the_edit_form_comes_back_prefilled(): void
    {
        $mango = $this->variant('Mango', 20, 500);

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 3]]
        ));

        $combo = Product::where('is_combo', true)->firstOrFail();

        $response = $this->actingAs($this->admin())->get(route('admin.combos.edit', $combo));

        $response->assertOk();
        $this->assertEquals(1200, $response->viewData('price'));
        $this->assertEquals(1500, $response->viewData('comparePrice'));

        $components = $response->viewData('components');
        $this->assertCount(1, $components);
        $this->assertSame($mango->id, $components->first()['variant_id']);
        $this->assertSame(3, $components->first()['quantity']);
    }

    public function test_a_plain_product_cannot_be_opened_as_a_combo(): void
    {
        $plain = $this->variant('Mango');

        $this->actingAs($this->admin())
            ->get(route('admin.combos.edit', $plain->product))
            ->assertNotFound();
    }

    /* -------------------------------------------------------- list/delete */

    public function test_the_menu_lists_combos_with_what_can_be_built(): void
    {
        $mango = $this->variant('Mango', 9, 500);
        $this->variant('Plain Product');

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 3]]
        ));

        $response = $this->actingAs($this->admin())->get(route('admin.combos.index'));

        $response->assertOk();
        $response->assertSee('Eid Gift Box');
        $response->assertDontSee('Plain Product');

        $combo = Product::where('is_combo', true)->firstOrFail();
        $this->assertSame(3, $response->viewData('buildable')[$combo->id]);
    }

    public function test_deleting_a_combo_leaves_its_products_alone(): void
    {
        $mango = $this->variant('Mango', 20, 500);

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            [['variant_id' => $mango->id, 'quantity' => 1]]
        ));

        $combo = Product::where('is_combo', true)->firstOrFail();

        $this->actingAs($this->admin())
            ->delete(route('admin.combos.destroy', $combo))
            ->assertRedirect(route('admin.combos.index'));

        $this->assertModelMissing($combo);
        $this->assertSame(0, ComboItem::count());
        $this->assertModelExists($mango);
    }

    /**
     * The delete button used to sit in a <form> nested inside the update form.
     * Browsers drop the inner tag and keep its inputs, so the page posted
     * _method=PUT and then _method=DELETE — PHP takes the last one, and every
     * save deleted the combo instead of updating it.
     */
    public function test_the_edit_page_posts_only_one_method_override(): void
    {
        $combo = $this->combo($this->variant('Mango', 20, 500));

        $html = $this->actingAs($this->admin())
            ->get(route('admin.combos.edit', $combo))
            ->assertOk()
            ->getContent();

        // What the browser builds: everything from the update form's action to
        // the first close, since a nested <form> tag is thrown away.
        $open = strpos($html, 'action="'.route('admin.combos.update', $combo).'"');
        $updateForm = substr($html, $open, strpos($html, '</form>', $open) - $open);

        $this->assertSame(
            1,
            preg_match_all('/name="_method"/', $updateForm),
            'A second _method would win and turn the save into a delete.'
        );
        $this->assertSame(0, substr_count($updateForm, '<form'), 'No form may be nested in the update form.');
    }

    /* ------------------------------------------- kept out of the products screen */

    private function combo(ProductVariant ...$components): Product
    {
        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload(
            array_map(fn (ProductVariant $v) => ['variant_id' => $v->id, 'quantity' => 1], $components)
        ))->assertSessionHasNoErrors();

        // Every layout prints the flash, and "Combo X created." would satisfy
        // an assertSee for the combo's own name on the next page.
        $this->flushSession();

        return Product::where('is_combo', true)->firstOrFail();
    }

    /** @param  array<string, mixed>  $overrides */
    private function productPayload(Product $product, array $overrides = []): array
    {
        return array_merge([
            'name_en' => $product->name_en ?? $product->getRawOriginal('name'),
            'name_bn' => $product->name_bn ?? $product->getRawOriginal('name'),
            'category_id' => $product->category_id,
            'variants' => $product->variants->map(fn (ProductVariant $v) => [
                'id' => $v->id,
                'name' => $v->name,
                'price' => $v->price,
                'stock' => $v->stock,
            ])->all(),
        ], $overrides);
    }

    public function test_combos_are_not_listed_among_the_products(): void
    {
        $mango = $this->variant('Mango', 20, 500);
        $combo = $this->combo($mango);

        $this->actingAs($this->admin())
            ->get(route('admin.products.index'))
            ->assertOk()
            ->assertSee('Mango')
            ->assertDontSee($combo->name_en);
    }

    public function test_opening_a_combo_from_the_products_screen_lands_on_the_combo_editor(): void
    {
        $combo = $this->combo($this->variant('Mango', 20, 500));

        $this->actingAs($this->admin())
            ->get(route('admin.products.edit', $combo))
            ->assertRedirect(route('admin.combos.edit', $combo));
    }

    /**
     * The product form used to delete every variant and write fresh rows, which
     * cascaded the combo's parts away and left the bundle unsellable.
     */
    public function test_saving_a_combo_through_the_product_form_cannot_empty_it(): void
    {
        $combo = $this->combo($this->variant('Mango', 20, 500), $this->variant('Ghee', 10, 500));

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $combo), $this->productPayload($combo->load('variants')))
            ->assertRedirect(route('admin.combos.edit', $combo));

        $this->assertSame(2, ComboItem::count());
    }

    public function test_editing_a_component_product_keeps_the_combo_intact(): void
    {
        $mango = $this->variant('Mango', 20, 500);
        $this->combo($mango);

        $product = $mango->product->load('variants');

        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->productPayload($product, [
                'variants' => [['id' => $mango->id, 'name' => '1 unit', 'price' => 650, 'stock' => 30]],
            ]))
            ->assertRedirect(route('admin.products.index'))
            ->assertSessionHasNoErrors();

        $mango->refresh();

        $this->assertSame('650.00', $mango->price, 'The row is edited in place.');
        $this->assertSame(30, $mango->stock);
        $this->assertSame(1, ComboItem::count(), 'The combo still knows what is in it.');
    }

    public function test_a_variant_inside_a_combo_cannot_be_dropped_from_the_product_form(): void
    {
        $mango = $this->variant('Mango', 20, 500);
        $product = $mango->product;

        $spare = ProductVariant::create([
            'product_id' => $product->id,
            'name' => '2 units',
            'price' => 900,
            'stock' => 5,
        ]);

        $this->combo($mango);

        // Posting only the spare row asks for the combo's component to go.
        $this->actingAs($this->admin())
            ->put(route('admin.products.update', $product), $this->productPayload($product, [
                'name_en' => 'Renamed Mango',
                'variants' => [['id' => $spare->id, 'name' => '2 units', 'price' => 900, 'stock' => 5]],
            ]))
            ->assertSessionHasErrors('variants');

        $this->assertModelExists($mango);
        $this->assertSame(1, ComboItem::count());
        $this->assertNull($product->refresh()->name_en, 'The refused save must not half-apply.');
    }

    /* -------------------------------------------------------- storefront */

    public function test_a_combo_has_its_own_section_on_the_home_page(): void
    {
        $combo = $this->combo($this->variant('Mango', 20, 500));

        $this->get('/')
            ->assertOk()
            ->assertSee('Combo Offers')
            ->assertSee($combo->name_en);
    }

    public function test_the_detail_page_lists_what_the_combo_is_made_of(): void
    {
        $mango = $this->variant('Mango', 20, 500);
        $ghee = $this->variant('Ghee', 10, 400);

        $this->actingAs($this->admin())->post(route('admin.combos.store'), $this->payload([
            ['variant_id' => $mango->id, 'quantity' => 2],
            ['variant_id' => $ghee->id, 'quantity' => 1],
        ]))->assertSessionHasNoErrors();

        $this->flushSession();

        $combo = Product::where('is_combo', true)->firstOrFail();

        $this->get(route('product.show', $combo->slug))
            ->assertOk()
            ->assertSee('Inside this combo', false)
            ->assertSee('Mango')
            ->assertSee('Ghee')
            ->assertSee('&times;2', false)
            // 2 × 500 for the mangoes, 400 for the ghee, 1,400 in total.
            ->assertSee('৳1,000')
            ->assertSee('৳400')
            ->assertSee('৳1,400')
            // The combo itself sells for 1,200, so 200 is saved.
            ->assertSee('৳1,200')
            ->assertSee('You save');
    }

    public function test_an_ordinary_product_gets_no_combo_breakdown(): void
    {
        $mango = $this->variant('Mango', 20, 500);

        $this->get(route('product.show', $mango->product->slug))
            ->assertOk()
            ->assertDontSee('Inside this combo', false);
    }

    public function test_an_inactive_combo_stays_off_the_home_page(): void
    {
        $combo = $this->combo($this->variant('Mango', 20, 500));
        $combo->update(['is_active' => false]);

        $this->get('/')->assertOk()->assertDontSee($combo->name_en);
    }

    /* -------------------------------------------------------- permissions */

    public function test_the_combos_menu_needs_its_own_permission(): void
    {
        $this->seed(PermissionSeeder::class);

        $staff = User::factory()->admin()->create();
        $staff->syncPermissions(['products.view']);

        $this->actingAs($staff->fresh())->get(route('admin.combos.index'))->assertForbidden();

        $staff->syncPermissions(['combos.view']);
        $this->actingAs($staff->fresh())->get(route('admin.combos.index'))->assertOk();
    }

    public function test_a_customer_cannot_build_combos(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'customer']))
            ->get(route('admin.combos.create'))
            ->assertForbidden();
    }
}
