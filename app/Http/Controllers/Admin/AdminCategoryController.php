<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\GeneratesUniqueSlug;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\LandingPage;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Validation\Rule;

class AdminCategoryController extends Controller
{
    use BulkDeletes, GeneratesUniqueSlug;
    use SearchesRecords;
    use SortsRecords;

    /** Posted fields that belong in the landing_defaults json, not in a column. */
    private const DRAFT_FIELDS = ['sections', 'features', 'faqs', 'specs', 'cta_text'];

    public function index(Request $request)
    {
        $categories = $this->applySearch(
            Category::withCount('products'),
            $request->input('search'),
            ['name', 'name_en', 'name_bn', 'slug']
        );

        $this->applySort($categories, $request, [
            'name' => 'name',
            'products' => 'products_count',
            'status' => 'is_active',
            // The hand-arranged storefront order, and the list's resting state.
            'sort_order' => ['sort_order', 'name'],
        ], 'sort_order', 'asc');

        $categories = $categories->paginate(15)->withQueryString();

        return view('admin.categories.index', compact('categories'));
    }

    public function create()
    {
        return view('admin.categories.create', $this->formData());
    }

    public function store(Request $request)
    {
        $validated = $request->validate($this->rules());

        $data = Arr::except($validated, self::DRAFT_FIELDS);
        $data['landing_defaults'] = $this->landingDefaults($validated);
        // categories.name is the non-localised fallback Category::getNameAttribute() reads.
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'categories');
        $data['is_active'] = $request->boolean('is_active', true);
        $data['theme'] = filled($validated['theme'] ?? null) ? $validated['theme'] : null;
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
        return view('admin.categories.edit', $this->formData($category) + compact('category'));
    }

    /**
     * What the content island renders from.
     *
     * Old input wins after a failed save, so a rejected form comes back with
     * the draft the admin typed rather than the one on the row.
     */
    private function formData(?Category $category = null): array
    {
        $draft = $category?->landingDefaults() ?? [];

        return [
            'draft' => [
                'sections' => old('sections', $draft['sections'] ?? []),
                'features' => old('features', $draft['features'] ?? []),
                'faqs' => old('faqs', $draft['faqs'] ?? []),
                'specs' => old('specs', $draft['specs'] ?? []),
                'cta_text' => old('cta_text', $draft['cta_text'] ?? ''),
            ],
        ];
    }

    public function update(Request $request, Category $category)
    {
        $validated = $request->validate($this->rules());

        $data = Arr::except($validated, self::DRAFT_FIELDS);
        $data['landing_defaults'] = $this->landingDefaults($validated);
        $data['name'] = $validated['name_en'];
        $data['slug'] = $this->uniqueSlug($validated['name_en'], 'categories', $category->id);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['theme'] = filled($validated['theme'] ?? null) ? $validated['theme'] : null;
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

    /**
     * Fold the repeaters into the one json column they live in.
     *
     * The category form reuses the campaign form's content island, which posts
     * `features[]` and `faqs[i][q]` because that is what the campaign form
     * needs. Nesting happens here rather than by renaming every field on a
     * component two screens share.
     */
    private function landingDefaults(array $validated): ?array
    {
        $draft = array_filter([
            'sections' => array_values($validated['sections'] ?? []),

            'features' => array_values(array_filter(
                array_map('trim', $validated['features'] ?? []),
                fn ($line) => $line !== ''
            )),

            'faqs' => array_values(array_filter(
                array_map(
                    fn ($row) => ['q' => trim($row['q'] ?? ''), 'a' => trim($row['a'] ?? '')],
                    $validated['faqs'] ?? []
                ),
                fn ($row) => $row['q'] !== ''
            )),

            'specs' => array_values(array_filter(
                array_map(
                    fn ($row) => ['label' => trim($row['label'] ?? ''), 'value' => trim($row['value'] ?? '')],
                    $validated['specs'] ?? []
                ),
                fn ($row) => $row['label'] !== ''
            )),

            'cta_text' => trim($validated['cta_text'] ?? ''),
        ]);

        // Nothing filled in is null, not an object of empty arrays — otherwise
        // every category would claim to have a draft worth offering.
        return $draft ?: null;
    }

    /** A category is a name and a picture — the shop never prints a description. */
    private function rules(): array
    {
        return [
            'name_en' => 'required|string|max:255',
            'name_bn' => 'required|string|max:255',
            // The skin every landing page in this category inherits.
            'theme' => ['nullable', Rule::in(array_keys(LandingPage::THEMES))],

            // The starting draft every promotion in this category opens with.
            'sections' => ['nullable', 'array'],
            'sections.*' => [Rule::in(array_keys(LandingPage::BLOCKS))],
            'features' => ['nullable', 'array', 'max:20'],
            'features.*' => ['nullable', 'string', 'max:255'],
            'faqs' => ['nullable', 'array', 'max:20'],
            'faqs.*.q' => ['nullable', 'string', 'max:255'],
            'faqs.*.a' => ['nullable', 'string', 'max:2000'],
            'specs' => ['nullable', 'array', 'max:30'],
            'specs.*.label' => ['nullable', 'string', 'max:100'],
            'specs.*.value' => ['nullable', 'string', 'max:255'],
            'cta_text' => ['nullable', 'string', 'max:100'],
            'image' => 'nullable|image|max:2048',
            'is_active' => 'boolean',
            'sort_order' => 'nullable|integer|min:0',
        ];
    }
}
