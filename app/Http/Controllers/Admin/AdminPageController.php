<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\GeneratesUniqueSlug;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Page;
use App\Support\RichText;
use Illuminate\Http\Request;

class AdminPageController extends Controller
{
    use BulkDeletes, GeneratesUniqueSlug;
    use SearchesRecords;

    public function index(Request $request)
    {
        $pages = $this->applySearch(
            Page::query(),
            $request->input('search'),
            ['slug', 'title_en', 'title_bn']
        )->orderBy('slug')->paginate(20)->withQueryString();

        return view('admin.pages.index', compact('pages'));
    }

    public function create()
    {
        return view('admin.pages.create');
    }

    public function store(Request $request)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_bn' => 'required|string|max:255',
            'content_en' => 'required',
            'content_bn' => 'required',
            'slug' => 'nullable|string|unique:pages,slug',
        ]);

        // An auto-generated slug still has to clear the UNIQUE index.
        // A nullable field that was not posted is absent from $validated entirely.
        $validated['slug'] = ($validated['slug'] ?? null) ?: $this->uniqueSlug($validated['title_en'], 'pages');

        Page::create(RichText::cleanKeys($validated, ['content_en', 'content_bn']));

        return redirect()->route('admin.pages.index')->with('success', 'Page created successfully');
    }

    public function edit(Page $page)
    {
        return view('admin.pages.edit', compact('page'));
    }

    public function update(Request $request, Page $page)
    {
        $validated = $request->validate([
            'title_en' => 'required|string|max:255',
            'title_bn' => 'required|string|max:255',
            'content_en' => 'required',
            'content_bn' => 'required',
            'slug' => "required|string|unique:pages,slug,{$page->id}",
        ]);

        $page->update(RichText::cleanKeys($validated, ['content_en', 'content_bn']));

        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully');
    }

    public function destroy(Page $page)
    {
        $page->delete();

        return redirect()->route('admin.pages.index')->with('success', 'Page deleted.');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete(
            $request, Page::class
        );

        return $this->bulkResponse($result, 'pages', 'admin.pages.index');
    }
}
