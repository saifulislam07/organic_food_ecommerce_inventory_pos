<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\GeneratesUniqueSlug;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class AdminCategoryController extends Controller
{
    use BulkDeletes, GeneratesUniqueSlug;
    use SearchesRecords;

    public function index(Request $request)
    {
        $categories = $this->applySearch(
            Category::withCount('products'),
            $request->input('search'),
            ['name', 'name_en', 'name_bn', 'slug']
        )->sorted()->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $data = $validated;
        // categories.name is the non-localised fallback Category::getNameAttribute() reads.
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'categories');
        $data['is_active'] = $request->boolean('is_active', true);
        // The column is NOT NULL: a cleared box has to land as 0, not null.
        $data['sort_order'] = (int) $request->input('sort_order', 0);

        if ($request->hasFile('image')) {
            $data['image'] = ImageStore::put($request->file('image'), 'categories');
        }

        Category::create($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category created!');
    }

    public function edit(Category $category)
    {
        return view('admin.categories.edit', compact('category'));
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate($this->rules());

        $data = $validated;
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'categories', $category->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = (int) $request->input('sort_order', 0);

        if ($request->hasFile('image')) {
            // Replacing the picture should not leave the old file behind.
            ImageStore::delete($category->getRawOriginal('image'));
            $data['image'] = ImageStore::put($request->file('image'), 'categories');
        }

        $category->update($data);

        return redirect()->route('admin.categories.index')->with('success', 'Category updated!');
    }

    public function destroy(Category $category)
    {
        // The model's deleting hook removes the files.
        $category->delete();

        return redirect()->route('admin.categories.index')->with('success', 'Category deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete(
            $request, Category::class,
            fn ($category) => $category->products()->exists()
                ? "\"{$category->name}\" still has products."
                : null

        );

        return $this->bulkResponse($result, 'categories', 'admin.categories.index');
    }

    /** A category is a name and a picture — the shop never prints a description. */
    private function rules(): array
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_bn' => 'required|string|max:255',
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
