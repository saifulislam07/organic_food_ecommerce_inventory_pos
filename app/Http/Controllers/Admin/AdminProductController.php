<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\GeneratesUniqueSlug;
use App\Http\Controllers\Admin\Concerns\HandlesProductImages;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\ComboItem;
use App\Models\Product;
use App\Models\Unit;
use App\Support\RichText;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class AdminProductController extends Controller
{
    use BulkDeletes, GeneratesUniqueSlug, HandlesProductImages;

    public function index(Request $request)
    {
        // Combos are products too, but they are built and edited on their own
        // screen — saving one here would strip the parts out of the bundle.
        $query = Product::where('is_combo', false)->with('category', 'variants');

        if ($request->filled('search')) {
            $query->where('name', 'like', '%'.$request->search.'%');
        }

        $products = $query->latest()->paginate(15);

        return view('admin.products.index', compact('products'));
    }

    public function create()
    {
        $categories = Category::active()->sorted()->get();
        $units = Unit::active()->sorted()->get(['id', 'name', 'short_code']);

        return view('admin.products.create', compact('categories', 'units'));
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'images' => 'nullable|array|max:'.Product::MAX_IMAGES,
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
            'thumbnail_id' => 'nullable|integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_trending' => 'boolean',
            'is_preorder' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.unit_id' => 'nullable|exists:units,id',
            'variants.*.unit_value' => 'nullable|numeric|min:0',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        $data = collect($validated)->except(['images', 'remove_images', 'thumbnail_id', 'variants'])->toArray();
        $data = RichText::cleanKeys($data, ['description_en', 'description_bn']);
        // products.name is the non-localised fallback Product::getNameAttribute() reads.
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'products');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_bestseller'] = $request->boolean('is_bestseller');
        $data['is_trending'] = $request->boolean('is_trending');
        $data['is_preorder'] = $request->boolean('is_preorder');

        $this->guardImageCount($request);

        $product = Product::create($data);

        $this->syncProductImages($request, $product);

        $this->syncVariants($product, $request->variants);

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
    }

    public function edit(Product $product)
    {
        if ($product->is_combo) {
            return redirect()->route('admin.combos.edit', $product);
        }

        $product->load('variants', 'images');
        $categories = Category::active()->sorted()->get();
        $units = Unit::active()->sorted()->get(['id', 'name', 'short_code']);

        return view('admin.products.edit', compact('product', 'categories', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        if ($product->is_combo) {
            return redirect()->route('admin.combos.edit', $product);
        }

        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'images' => 'nullable|array|max:'.Product::MAX_IMAGES,
            'images.*' => 'nullable|image|mimes:jpg,jpeg,png,webp|max:2048',
            'remove_images' => 'nullable|array',
            'remove_images.*' => 'integer',
            'thumbnail_id' => 'nullable|integer',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_trending' => 'boolean',
            'is_preorder' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'variants' => 'required|array|min:1',
            'variants.*.id' => 'nullable|integer',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.unit_id' => 'nullable|exists:units,id',
            'variants.*.unit_value' => 'nullable|numeric|min:0',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        $data = collect($validated)->except(['images', 'remove_images', 'thumbnail_id', 'variants'])->toArray();
        $data = RichText::cleanKeys($data, ['description_en', 'description_bn']);
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'products', $product->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_bestseller'] = $request->boolean('is_bestseller');
        $data['is_trending'] = $request->boolean('is_trending');
        $data['is_preorder'] = $request->boolean('is_preorder');

        $this->guardImageCount($request, $product);
        $this->guardVariantRemovals($product, $request->variants);

        $product->update($data);

        $this->syncProductImages($request, $product);

        $this->syncVariants($product, $request->variants);

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        // The model's deleting hook removes the files.
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete(
            $request, Product::class
        );

        return $this->bulkResponse($result, 'products', 'admin.products.index');
    }

    /**
     * Writes the posted rows onto the product's variants.
     *
     * Rows are matched by the id the form posts back and updated in place. The
     * old delete-everything-and-recreate emptied combo_items (which cascades
     * from the variant) and blanked product_variant_id on past order lines, so
     * simply editing a price used to break bundles and order history.
     *
     * @param  array<int, array<string, mixed>>  $rows
     */
    private function syncVariants(Product $product, array $rows): void
    {
        $kept = [];

        foreach (array_values($rows) as $i => $row) {
            $attributes = [
                'name' => $row['name'],
                'unit_id' => ($row['unit_id'] ?? null) ?: null,
                'unit_value' => ($row['unit_value'] ?? null) ?: null,
                'price' => $row['price'],
                'sale_price' => $row['sale_price'] ?? null,
                'stock' => $row['stock'],
                'sort_order' => $i,
            ];

            $variant = ($row['id'] ?? null)
                ? $product->variants()->whereKey($row['id'])->first()
                : null;

            if ($variant) {
                $variant->update($attributes);
            } else {
                $variant = $product->variants()->create($attributes + [
                    'sku' => strtoupper(Str::slug($product->slug.'-'.($i + 1))),
                ]);
            }

            $kept[] = $variant->id;
        }

        $product->variants()->whereNotIn('id', $kept)->get()->each->delete();
    }

    /**
     * Refuses a save that would drop a variant some combo is built on.
     *
     * combo_items restricts that delete at the database level, so without this
     * the driver raises a 500 halfway through the save. Checked before anything
     * is written, like guardImageCount.
     *
     * @param  array<int, array<string, mixed>>  $rows
     *
     * @throws ValidationException
     */
    private function guardVariantRemovals(Product $product, array $rows): void
    {
        $posted = collect($rows)->pluck('id')->filter()->map(fn ($id) => (int) $id)->all();

        $dropped = $product->variants()->whereNotIn('id', $posted)->pluck('id');

        if ($dropped->isEmpty()) {
            return;
        }

        $combos = ComboItem::whereIn('component_variant_id', $dropped)
            ->with('combo.product')
            ->get()
            ->map(fn (ComboItem $item) => $item->combo?->product?->name)
            ->filter()
            ->unique()
            ->values();

        if ($combos->isNotEmpty()) {
            throw ValidationException::withMessages([
                'variants' => 'That variant is part of the combo "'.$combos->implode('", "').'". '
                    .'Take it out of the combo before removing it here.',
            ]);
        }
    }
}
