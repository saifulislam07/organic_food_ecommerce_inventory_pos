<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\GeneratesUniqueSlug;
use App\Http\Controllers\Admin\Concerns\HandlesProductImages;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\ProductVariant;
use App\Services\InventoryService;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * A combo is built on one screen: pick the products that go in it, give it a
 * title, and set the selling price against the total the parts would cost
 * separately.
 *
 * Underneath it is still an ordinary product with a single variant, so the
 * storefront, cart and POS need no special cases.
 */
class AdminComboController extends Controller
{
    use BulkDeletes, GeneratesUniqueSlug, HandlesProductImages, SearchesRecords;

    public function index(Request $request)
    {
        $combos = $this->applySearch(
            Product::where('is_combo', true)->with(['variants.comboItems.component.product', 'category']),
            $request->input('search'),
            ['name', 'name_en', 'name_bn']
        )
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        $inventory = app(InventoryService::class);

        return view('admin.combos.index', [
            'combos' => $combos,
            'buildable' => $combos->getCollection()->mapWithKeys(fn (Product $product) => [
                $product->id => $product->variants->sum(fn ($variant) => $inventory->available($variant)),
            ]),
        ]);
    }

    public function create()
    {
        return view('admin.combos.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $this->guardImageCount($request);

        $combo = DB::transaction(function () use ($validated, $request) {
            $product = Product::create([
                'category_id' => $validated['category_id'],
                'name' => $validated['name_en'],
                'name_en' => $validated['name_en'],
                'name_bn' => $validated['name_bn'],
                'slug' => $this->uniqueSlug($validated['name_en'], 'products'),
                'short_description_en' => $validated['short_description_en'] ?? null,
                'short_description_bn' => $validated['short_description_bn'] ?? null,
                'description_en' => RichText::clean($validated['description_en'] ?? null),
                'description_bn' => RichText::clean($validated['description_bn'] ?? null),
                'is_active' => $request->boolean('is_active', true),
                'is_combo' => true,
            ]);

            // One variant: the bundle itself. It carries no stock of its own.
            $variant = $product->variants()->create([
                'name' => $validated['name_bn'],
                'price' => $validated['compare_price'],
                'sale_price' => $validated['price'],
                'stock' => 0,
                'sku' => strtoupper($this->uniqueSlug($validated['name_en'], 'products').'-COMBO'),
            ]);

            $this->syncComponents($variant, $validated['components']);

            return $product;
        });

        $this->syncProductImages($request, $combo);

        return redirect()->route('admin.combos.index')
            ->with('success', "Combo \"{$combo->name}\" created.");
    }

    public function edit(Product $product)
    {
        abort_unless($product->is_combo, 404);

        $product->load('images', 'variants.comboItems.component.product');

        return view('admin.combos.edit', $this->formData($product));
    }

    public function update(Request $request, Product $product)
    {
        abort_unless($product->is_combo, 404);

        $validated = $request->validate($this->rules());

        $this->guardImageCount($request, $product);

        DB::transaction(function () use ($validated, $request, $product) {
            $product->update([
                'category_id' => $validated['category_id'],
                'name' => $validated['name_en'],
                'name_en' => $validated['name_en'],
                'name_bn' => $validated['name_bn'],
                'slug' => $this->uniqueSlug($validated['name_en'], 'products', $product->id),
                'short_description_en' => $validated['short_description_en'] ?? null,
                'short_description_bn' => $validated['short_description_bn'] ?? null,
                'description_en' => RichText::clean($validated['description_en'] ?? null),
                'description_bn' => RichText::clean($validated['description_bn'] ?? null),
                'is_active' => $request->boolean('is_active', true),
            ]);

            $variant = $product->variants()->firstOrFail();

            $variant->update([
                'name' => $validated['name_bn'],
                'price' => $validated['compare_price'],
                'sale_price' => $validated['price'],
            ]);

            $this->syncComponents($variant, $validated['components']);
        });

        $this->syncProductImages($request, $product->fresh());

        return redirect()->route('admin.combos.index')
            ->with('success', "Combo \"{$product->name}\" updated.");
    }

    public function destroy(Product $product)
    {
        abort_unless($product->is_combo, 404);

        $name = $product->name;
        $product->delete();

        return redirect()->route('admin.combos.index')->with('success', "Combo \"{$name}\" removed.");
    }

    public function bulkDestroy(Request $request)
    {
        // The combo list only ever shows combos, but the ids arrive from the
        // browser, so an ordinary product must not slip through this route.
        $result = $this->bulkDelete(
            $request, Product::class,
            fn (Product $product) => $product->is_combo ? null : "\"{$product->name}\" is not a combo."
        );

        return $this->bulkResponse($result, 'combos', 'admin.combos.index');
    }

    /* ------------------------------------------------------------ helpers */

    private function rules(): array
    {
        return [
            'name_en' => ['required', 'string', 'max:255'],
            'name_bn' => ['required', 'string', 'max:255'],
            'category_id' => ['required', 'exists:categories,id'],
            'short_description_en' => ['nullable', 'string'],
            'short_description_bn' => ['nullable', 'string'],
            'description_en' => ['nullable', 'string'],
            'description_bn' => ['nullable', 'string'],

            // What the parts add up to, and what the shop actually charges.
            'compare_price' => ['required', 'numeric', 'min:0'],
            'price' => ['required', 'numeric', 'min:0'],

            'components' => ['required', 'array', 'min:1'],
            'components.*.variant_id' => ['required', 'exists:product_variants,id'],
            'components.*.quantity' => ['required', 'integer', 'min:1'],

            'images' => ['nullable', 'array', 'max:'.Product::MAX_IMAGES],
            'images.*' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'remove_images' => ['nullable', 'array'],
            'remove_images.*' => ['integer'],
            'thumbnail_id' => ['nullable', 'integer'],
            'is_active' => ['nullable', 'boolean'],
        ];
    }

    /** @param  array<int, array{variant_id: mixed, quantity: mixed}>  $components */
    private function syncComponents(ProductVariant $combo, array $components): void
    {
        $ids = array_map(fn ($c) => (int) $c['variant_id'], $components);

        if (count($ids) !== count(array_unique($ids))) {
            throw ValidationException::withMessages([
                'components' => 'The same product is listed twice — raise its quantity instead.',
            ]);
        }

        if (in_array($combo->id, $ids, true)) {
            throw ValidationException::withMessages([
                'components' => 'A combo cannot contain itself.',
            ]);
        }

        // Nesting bundles would make the stock maths recursive.
        if (ComboItem::whereIn('combo_variant_id', $ids)->exists()) {
            throw ValidationException::withMessages([
                'components' => 'A combo cannot contain another combo.',
            ]);
        }

        $combo->comboItems()->delete();

        foreach ($components as $component) {
            $combo->comboItems()->create([
                'component_variant_id' => (int) $component['variant_id'],
                'quantity' => (int) $component['quantity'],
            ]);
        }
    }

    private function formData(?Product $product = null): array
    {
        $variant = $product?->variants->first();

        return [
            'product' => $product,
            'categories' => Category::active()->sorted()->get(),
            'options' => $this->componentOptions($product),
            'components' => $variant
                ? $variant->comboItems->map(fn (ComboItem $item) => [
                    'variant_id' => $item->component_variant_id,
                    'quantity' => $item->quantity,
                ])->values()
                : collect(),
            'price' => $variant?->sale_price,
            'comparePrice' => $variant?->price,
            'galleryImages' => $product
                ? $product->images->map(fn ($image) => ['id' => $image->id, 'url' => $image->url])->values()
                : collect(),
            'thumbnailId' => $product
                ? optional($product->images->firstWhere('path', $product->getRawOriginal('image')))->id
                : null,
        ];
    }

    /** Everything sellable that is not itself a bundle. */
    private function componentOptions(?Product $product)
    {
        return ProductVariant::with('product')
            ->when($product, fn ($query) => $query->where('product_id', '!=', $product->id))
            ->whereDoesntHave('comboItems')
            ->orderBy('product_id')
            ->orderBy('sort_order')
            ->get()
            ->filter(fn (ProductVariant $variant) => $variant->product !== null)
            ->map(fn (ProductVariant $variant) => [
                'id' => $variant->id,
                'product_name' => $variant->product->name,
                'variant_name' => $variant->name,
                'sku' => $variant->sku,
                'stock' => (int) $variant->stock,
                'price' => (float) ($variant->sale_price ?? $variant->price),
                'image' => $variant->product->image_url,
            ])
            ->values();
    }
}
