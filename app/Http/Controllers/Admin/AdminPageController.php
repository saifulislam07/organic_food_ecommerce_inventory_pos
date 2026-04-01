<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\Page;
use Illuminate\Support\Str;

class AdminPageController extends Controller
{
    public function index()
    {
        $pages = Page::all();
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

        $validated['slug'] = $validated['slug'] ?: Str::slug($validated['title_en']);

        Page::create($validated);
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

        $page->update($validated);
        return redirect()->route('admin.pages.index')->with('success', 'Page updated successfully');
    }
}
