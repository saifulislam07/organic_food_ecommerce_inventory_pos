<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Admin\Concerns\BulkDeletes;
use App\Http\Controllers\Admin\Concerns\SearchesRecords;
use App\Http\Controllers\Controller;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Http\Request;

class AdminReviewController extends Controller
{
    use BulkDeletes, SearchesRecords;

    public function index(Request $request)
    {
        $status = $request->input('status', 'all');

        $reviews = $this->applySearch(
            Review::query()->with(['product', 'user']),
            $request->input('search'),
            ['customer_name', 'title', 'body', 'product.name']
        )
            ->when($status === 'pending', fn ($q) => $q->pending())
            ->when($status === 'approved', fn ($q) => $q->approved())
            ->latest()
            ->paginate(20)
            ->withQueryString();

        $pendingCount = Review::pending()->count();

        return view('admin.reviews.index', compact('reviews', 'status', 'pendingCount'));
    }

    public function create()
    {
        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('admin.reviews.create', compact('products'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        // Written by an admin, not a customer purchase — nothing to link.
        $data['user_id'] = null;
        $data['order_id'] = null;

        Review::create($data);

        return redirect()->route('admin.reviews.index')->with('success', 'Review added!');
    }

    public function edit(Review $review)
    {
        $products = Product::orderBy('name')->get(['id', 'name']);

        return view('admin.reviews.edit', compact('review', 'products'));
    }

    public function update(Request $request, Review $review)
    {
        $review->update($this->validated($request));

        return redirect()->route('admin.reviews.index')->with('success', 'Review updated!');
    }

    public function destroy(Review $review)
    {
        $review->delete();

        return redirect()->route('admin.reviews.index')->with('success', 'Review deleted!');
    }

    public function bulkDestroy(Request $request)
    {
        $result = $this->bulkDelete($request, Review::class);

        return $this->bulkResponse($result, 'reviews', 'admin.reviews.index');
    }

    /** One-click moderation from the list, without opening the edit form. */
    public function approve(Review $review)
    {
        $review->update(['is_approved' => true]);

        return back()->with('success', 'Review approved.');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'product_id' => ['required', 'exists:products,id'],
            'customer_name' => ['required', 'string', 'max:120'],
            'rating' => ['required', 'integer', 'min:1', 'max:5'],
            'title' => ['nullable', 'string', 'max:150'],
            'body' => ['required', 'string', 'max:2000'],
        ]);

        $data['is_approved'] = $request->boolean('is_approved', true);

        return $data;
    }
}
