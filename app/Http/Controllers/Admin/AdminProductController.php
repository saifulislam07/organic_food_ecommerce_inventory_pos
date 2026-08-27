<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\GeneratesUniqueSlug;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Product;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class AdminProductController extends Controller
{
    use GeneratesUniqueSlug;

    public function index(Request $request)
    {
        $query = Product::with('category', 'variants');

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
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_trending' => 'boolean',
            'is_preorder' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.unit_id' => 'nullable|exists:units,id',
            'variants.*.unit_value' => 'nullable|numeric|min:0',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        $data = collect($validated)->except(['image', 'variants'])->toArray();
        // products.name is the non-localised fallback Product::getNameAttribute() reads.
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'products');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_bestseller'] = $request->boolean('is_bestseller');
        $data['is_trending'] = $request->boolean('is_trending');
        $data['is_preorder'] = $request->boolean('is_preorder');

        if ($request->hasFile('image')) {
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product = Product::create($data);

        foreach ($request->variants as $i => $variantData) {
            $product->variants()->create([
                'name' => $variantData['name'],
                'unit_id' => ($variantData['unit_id'] ?? null) ?: null,
                'unit_value' => ($variantData['unit_value'] ?? null) ?: null,
                'price' => $variantData['price'],
                'sale_price' => $variantData['sale_price'] ?? null,
                'stock' => $variantData['stock'],
                'sku' => strtoupper(Str::slug($data['slug'].'-'.($i + 1))),
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product created successfully!');
    }

    public function edit(Product $product)
    {
        $product->load('variants');
        $categories = Category::active()->sorted()->get();
        $units = Unit::active()->sorted()->get(['id', 'name', 'short_code']);

        return view('admin.products.edit', compact('product', 'categories', 'units'));
    }

    public function update(Request $request, Product $product)
    {
        $validated = $request->validate([
            'name_en' => 'required|string|max:255',
            'name_bn' => 'required|string|max:255',
            'category_id' => 'required|exists:categories,id',
            'short_description_en' => 'nullable|string',
            'short_description_bn' => 'nullable|string',
            'description_en' => 'nullable|string',
            'description_bn' => 'nullable|string',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'is_featured' => 'boolean',
            'is_bestseller' => 'boolean',
            'is_trending' => 'boolean',
            'is_preorder' => 'boolean',
            'meta_title' => 'nullable|string|max:255',
            'meta_description' => 'nullable|string',
            'variants' => 'required|array|min:1',
            'variants.*.name' => 'required|string|max:255',
            'variants.*.unit_id' => 'nullable|exists:units,id',
            'variants.*.unit_value' => 'nullable|numeric|min:0',
            'variants.*.price' => 'required|numeric|min:0',
            'variants.*.sale_price' => 'nullable|numeric|min:0',
            'variants.*.stock' => 'required|integer|min:0',
        ]);

        $data = collect($validated)->except(['image', 'variants'])->toArray();
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'products', $product->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_featured'] = $request->boolean('is_featured');
        $data['is_bestseller'] = $request->boolean('is_bestseller');
        $data['is_trending'] = $request->boolean('is_trending');
        $data['is_preorder'] = $request->boolean('is_preorder');

        if ($request->hasFile('image')) {
            if ($product->image) {
                Storage::disk('public')->delete($product->image);
            }
            $data['image'] = $request->file('image')->store('products', 'public');
        }

        $product->update($data);

        // Sync variants
        $product->variants()->delete();
        foreach ($request->variants as $i => $variantData) {
            $product->variants()->create([
                'name' => $variantData['name'],
                'unit_id' => ($variantData['unit_id'] ?? null) ?: null,
                'unit_value' => ($variantData['unit_value'] ?? null) ?: null,
                'price' => $variantData['price'],
                'sale_price' => $variantData['sale_price'] ?? null,
                'stock' => $variantData['stock'],
                'sku' => strtoupper(Str::slug($data['slug'].'-'.($i + 1))),
                'sort_order' => $i,
            ]);
        }

        return redirect()->route('admin.products.index')->with('success', 'Product updated successfully!');
    }

    public function destroy(Product $product)
    {
        if ($product->image) {
            Storage::disk('public')->delete($product->image);
        }
        $product->delete();

        return redirect()->route('admin.products.index')->with('success', 'Product deleted!');
    }
}
