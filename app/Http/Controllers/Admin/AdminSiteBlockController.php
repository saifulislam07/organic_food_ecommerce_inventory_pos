<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\SiteBlock;
use App\Support\ImageStore;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * The storefront's small ordered lists — header menu, service strip, promo
 * tiles, payment chips, footer links. One screen for all of them, filtered by
 * group, because they differ only in which fields they use.
 */
class AdminSiteBlockController extends Controller
{
    use BulkDeletes, SearchesRecords;

    public function index(Request $request)
    {
        $group = $this->currentGroup($request->input('group'));

        $blocks = $this->applySearch(
            SiteBlock::query()->group($group),
            $request->input('search'),
            ['title_en', 'title_bn']
        )->sorted()->paginate(30)->withQueryString();

        return view('admin.blocks.index', compact('blocks', 'group'));
    }

    public function create(Request $request)
    {
        return view('admin.blocks.create', ['group' => $this->currentGroup($request->input('group'))]);
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        if ($request->hasFile('image') && SiteBlock::groupHasField($data['group'], 'image')) {
            $data['image'] = ImageStore::put($request->file('image'), 'blocks');
        }

        $block = SiteBlock::create($data);

        return redirect()->route('admin.blocks.index', ['group' => $block->group])
            ->with('success', SiteBlock::groupLabel($block->group).' item created!');
    }

    public function edit(SiteBlock $block)
    {
        return view('admin.blocks.edit', compact('block'));
    }

    public function update(Request $request, SiteBlock $block)
    {
        $data = $this->validated($request, $block);

        if ($request->hasFile('image') && SiteBlock::groupHasField($data['group'], 'image')) {
            // Replacing the picture should not leave the old file behind.
            ImageStore::delete($block->image);
            $data['image'] = ImageStore::put($request->file('image'), 'blocks');
        }

        $block->update($data);

        return redirect()->route('admin.blocks.index', ['group' => $block->group])
            ->with('success', SiteBlock::groupLabel($block->group).' item updated!');
    }

    public function destroy(SiteBlock $block)
    {
        $group = $block->group;

        // The model's deleting hook removes the file.
        $block->delete();

        return redirect()->route('admin.blocks.index', ['group' => $group])
            ->with('success', 'Item deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, SiteBlock::class);

        return $this->bulkResponse($result, 'items', 'admin.blocks.index');
    }

    /** An unknown or missing ?group falls back to the first one defined. */
    private function currentGroup(?string $group): string
    {
        return array_key_exists((string) $group, SiteBlock::GROUPS)
            ? $group
            : array_key_first(SiteBlock::GROUPS);
    }

    /**
     * Only the fields the chosen group declares are accepted. Anything else is
     * dropped rather than rejected, so switching a row from one group to
     * another clears what the new group has no place for.
     */
    private function validated(Request $request, ?SiteBlock $block = null): array
    {
        $group = $this->currentGroup($request->input('group', $block?->group));

        $rules = [
            'group' => ['required', Rule::in(array_keys(SiteBlock::GROUPS))],
            'title_en' => ['required', 'string', 'max:120'],
            'title_bn' => ['nullable', 'string', 'max:120'],
            'subtitle_en' => ['nullable', 'string', 'max:160'],
            'subtitle_bn' => ['nullable', 'string', 'max:160'],
            'icon' => ['nullable', 'string', 'max:64', 'regex:/^[a-z0-9-]+$/'],
            'url' => ['nullable', 'string', 'max:255'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ];

        $data = $request->validate($rules, [
            'icon.regex' => 'The icon must be a Bootstrap Icons name such as truck or cash-coin.',
        ]);

        unset($data['image']);
        $data['group'] = $group;

        // A field this group has no place for is cleared rather than kept, so
        // moving a row between groups cannot leave a stale link or icon behind.
        if (! SiteBlock::groupHasField($group, 'subtitle')) {
            $data['subtitle_en'] = null;
            $data['subtitle_bn'] = null;
        }

        if (! SiteBlock::groupHasField($group, 'icon')) {
            $data['icon'] = null;
        }

        if (! SiteBlock::groupHasField($group, 'url')) {
            $data['url'] = null;
        }

        // The columns are NOT NULL: a cleared box has to land as 0, not null.
        $data['sort_order'] = (int) $request->input('sort_order', 0);
        $data['is_active'] = $request->boolean('is_active', true);
        $data['is_highlighted'] = SiteBlock::groupHasField($group, 'highlight')
            && $request->boolean('is_highlighted');

        return $data;
    }
}
