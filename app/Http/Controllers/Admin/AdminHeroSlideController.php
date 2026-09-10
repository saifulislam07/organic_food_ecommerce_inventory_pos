<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\HeroSlide;
use App\Support\ImageStore;
use Illuminate\Http\Request;

class AdminHeroSlideController extends Controller
{
    use BulkDeletes, SearchesRecords;
    use SortsRecords;

    public function index(Request $request)
    {
        $slides = $this->applySearch(
            HeroSlide::query(),
            $request->input('search'),
            ['title_en', 'title_bn']
        );

        $this->applySort($slides, $request, [
            'title' => 'title_en',
            'status' => 'is_active',
            'sort_order' => ['sort_order', 'id'],
        ], 'sort_order', 'asc');

        $slides = $slides->paginate(20)->withQueryString();

        return view('admin.sliders.index', compact('slides'));
    }

    public function create()
    {
        return view('admin.sliders.create');
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            $data['image'] = ImageStore::put($request->file('image'), 'sliders');
        }

        HeroSlide::create($data);

        return redirect()->route('admin.sliders.index')->with('success', 'Slide created!');
    }

    public function edit(HeroSlide $slider)
    {
        return view('admin.sliders.edit', ['slide' => $slider]);
    }

    public function update(Request $request, HeroSlide $slider)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image')) {
            // Replacing the picture should not leave the old file behind.
            ImageStore::delete($slider->image);
            $data['image'] = ImageStore::put($request->file('image'), 'sliders');
        }

        $slider->update($data);

        return redirect()->route('admin.sliders.index')->with('success', 'Slide updated!');
    }

    public function destroy(HeroSlide $slider)
    {
        // The model's deleting hook removes the file.
        $slider->delete();

        return redirect()->route('admin.sliders.index')->with('success', 'Slide deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, HeroSlide::class);

        return $this->bulkResponse($result, 'slides', 'admin.sliders.index');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'badge_en' => ['nullable', 'string', 'max:120'],
            'badge_bn' => ['nullable', 'string', 'max:120'],
            // Carries the <br> and <span> the hero headline is styled with.
            'title_en' => ['required', 'string', 'max:255'],
            'title_bn' => ['nullable', 'string', 'max:255'],
            'subtitle_en' => ['nullable', 'string', 'max:500'],
            'subtitle_bn' => ['nullable', 'string', 'max:500'],
            'button_text_en' => ['nullable', 'string', 'max:60'],
            'button_text_bn' => ['nullable', 'string', 'max:60'],
            'button_url' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        unset($data['image']);

        $data['is_active'] = $request->boolean('is_active', true);
        // The column is NOT NULL: a cleared box has to land as 0, not null.
        $data['sort_order'] = (int) $request->input('sort_order', 0);

        return $data;
    }
}
