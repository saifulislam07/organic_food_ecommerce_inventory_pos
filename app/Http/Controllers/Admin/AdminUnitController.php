<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Unit;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminUnitController extends Controller
{
    use BulkDeletes, SearchesRecords;

    public function index(Request $request)
    {
        $units = $this->applySearch(
            Unit::withCount('variants'),
            $request->input('search'),
            ['name', 'name_bn', 'short_code']
        )->sorted()->paginate(30)->withQueryString();

        return view('admin.units.index', compact('units'));
    }

    public function create()
    {
        return view('admin.units.create');
    }

    public function store(Request $request)
    {
        Unit::create($this->validated($request));

        return redirect()->route('admin.units.index')->with('success', 'Unit created.');
    }

    public function edit(Unit $unit)
    {
        return view('admin.units.edit', compact('unit'));
    }

    public function update(Request $request, Unit $unit)
    {
        $unit->update($this->validated($request, $unit));

        return redirect()->route('admin.units.index')->with('success', 'Unit updated.');
    }

    public function destroy(Unit $unit)
    {
        // Variants keep pointing at a unit; deleting one would blank them silently.
        if ($unit->variants()->exists()) {
            return back()->withErrors([
                'unit' => "\"{$unit->name}\" is used by {$unit->variants()->count()} product variant(s) and cannot be deleted.",
            ]);
        }

        $unit->delete();

        return redirect()->route('admin.units.index')->with('success', 'Unit deleted.');
    }

    private function validated(Request $request, ?Unit $unit = null): array
    {
        $data = $request->validate([
            'name' => ['required', 'string', 'max:50'],
            'name_bn' => ['nullable', 'string', 'max:50'],
            'short_code' => [
                'required', 'string', 'max:10',
                Rule::unique('units', 'short_code')->ignore($unit?->id),
            ],
            'sort_order' => ['nullable', 'integer', 'min:0'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $data['sort_order'] = $data['sort_order'] ?? 0;

        return $data;
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete(
            $request, Unit::class,
            fn ($unit) => $unit->variants()->exists()
                ? "\"{$unit->name}\" is used by product variants."
                : null

        );

        return $this->bulkResponse($result, 'units', 'admin.units.index');
    }
}
