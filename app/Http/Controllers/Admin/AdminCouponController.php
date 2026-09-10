<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Admin\Concerns\SortsRecords;
use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Coupon;
use App\Models\Product;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AdminCouponController extends Controller
{
    use BulkDeletes, SearchesRecords;
    use SortsRecords;

    public function index(Request $request)
    {
        $coupons = $this->applySearch(
            Coupon::query()->withCount('orders'),
            $request->input('search'),
            ['code', 'label_en', 'label_bn']
        );

        $this->applySort($coupons, $request, [
            'code' => 'code',
            'discount' => ['type', 'value'],
            'window' => 'starts_at',
            'used' => 'orders_count',
            'status' => 'is_active',
            'created_at' => 'created_at',
        ], 'created_at');

        $coupons = $coupons->paginate(20)->withQueryString();

        return view('admin.coupons.index', compact('coupons'));
    }

    public function create()
    {
        return view('admin.coupons.create', $this->formData());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);

        $coupon = Coupon::create($data);
        $this->syncScope($coupon, $request);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon created!');
    }

    public function edit(Coupon $coupon)
    {
        $coupon->load('categories:id', 'products:id');

        return view('admin.coupons.edit', array_merge($this->formData(), compact('coupon')));
    }

    public function update(Request $request, Coupon $coupon)
    {
        $coupon->update($this->validated($request, $coupon));
        $this->syncScope($coupon, $request);

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon updated!');
    }

    public function destroy(Coupon $coupon)
    {
        // Orders keep their coupon_code snapshot; the foreign key is nulled.
        $coupon->delete();

        return redirect()->route('admin.coupons.index')->with('success', 'Coupon deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, Coupon::class);

        return $this->bulkResponse($result, 'coupons', 'admin.coupons.index');
    }

    /** The pickers the scope fields need. */
    private function formData(): array
    {
        return [
            'categories' => Category::active()->sorted()->get(['id', 'name_en', 'name_bn', 'name']),
            'products' => Product::active()->with('category:id,name_en,name_bn,name')
                ->orderBy('name_en')->get(['id', 'name_en', 'name_bn', 'name', 'category_id']),
        ];
    }

    private function validated(Request $request, ?Coupon $coupon = null): array
    {
        // Codes are stored upper-cased, so "save20" has to be compared against
        // the stored "SAVE20" — normalise before the unique rule looks.
        $request->merge(['code' => strtoupper(trim((string) $request->input('code')))]);

        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:40', 'regex:/^[A-Z0-9_-]+$/',
                Rule::unique('coupons', 'code')->ignore($coupon),
            ],
            'label_en' => ['nullable', 'string', 'max:120'],
            'label_bn' => ['nullable', 'string', 'max:120'],
            'type' => ['required', Rule::in(array_keys(Coupon::TYPES))],
            'value' => ['required', 'numeric', 'min:0.01'],
            'max_discount' => ['nullable', 'numeric', 'min:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'applies_to' => ['required', Rule::in(array_keys(Coupon::SCOPES))],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'usage_limit_per_user' => ['nullable', 'integer', 'min:1'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ], [
            'code.regex' => 'The code may only hold letters, digits, hyphens and underscores.',
        ]);

        // A percentage above 100 would make the shop pay the customer.
        if ($data['type'] === Coupon::TYPE_PERCENT && $data['value'] > 100) {
            $data['value'] = 100;
        }

        // A ceiling only means anything on a percentage.
        if ($data['type'] !== Coupon::TYPE_PERCENT) {
            $data['max_discount'] = null;
        }

        $data['is_active'] = $request->boolean('is_active', true);

        return $data;
    }

    /**
     * Store the scope lists, and clear the one the chosen scope does not use so
     * a coupon switched back to "every product" cannot keep a stale list.
     */
    private function syncScope(Coupon $coupon, Request $request): void
    {
        $coupon->categories()->sync(
            $coupon->applies_to === 'categories' ? $request->input('category_ids', []) : []
        );

        $coupon->products()->sync(
            $coupon->applies_to === 'products' ? $request->input('product_ids', []) : []
        );
    }
}
