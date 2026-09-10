@extends('admin.layouts.app')
@section('page_title', 'Reviews')

@section('content')
<div class="admin-toolbar">
    <ul class="nav nav-pills gap-1">
        <li class="nav-item">
            <a class="nav-link {{ $status === 'all' ? 'active' : '' }}"
               href="{{ route('admin.reviews.index', ['status' => 'all']) }}">All</a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'pending' ? 'active' : '' }}"
               href="{{ route('admin.reviews.index', ['status' => 'pending']) }}">
                Pending @if($pendingCount)<span class="badge bg-danger ms-1">{{ $pendingCount }}</span>@endif
            </a>
        </li>
        <li class="nav-item">
            <a class="nav-link {{ $status === 'approved' ? 'active' : '' }}"
               href="{{ route('admin.reviews.index', ['status' => 'approved']) }}">Approved</a>
        </li>
    </ul>
    @include('admin.partials.search', ['route' => route('admin.reviews.index'), 'placeholder' => 'Customer, title or review text'])
    @can('reviews.create')
    <a href="{{ route('admin.reviews.create') }}" class="btn btn-success"><i class="bi bi-plus-circle"></i> Add Review</a>
    @endcan
</div>

@can('reviews.delete')
<form id="bulk-reviews" method="POST" action="{{ route('admin.reviews.bulkDestroy') }}"
      data-bulk data-bulk-noun="reviews">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        @can('reviews.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-reviews"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'product', 'label' => 'Product', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'customer', 'label' => 'Customer', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'rating', 'label' => 'Rating'])
                        <th>Review</th>
                        <th>Source</th>
                        @include('admin.partials.sort', ['key' => 'status', 'label' => 'Status'])
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($reviews as $review)
                <tr>
                    @can('reviews.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-reviews" name="ids[]" value="{{ $review->id }}"></td>@endcan
                    <td style="padding: 8px 16px;">{{ $review->product->name ?? '—' }}</td>
                    <td>
                        {{ $review->customer_name }}
                        @if($review->order_id)<i class="bi bi-patch-check-fill text-success" title="Verified purchase"></i>@endif
                    </td>
                    <td class="text-warning" style="letter-spacing:1px;">{{ $review->stars }}</td>
                    <td class="small text-muted" style="max-width:280px;">
                        @if($review->title)<strong class="d-block text-dark">{{ $review->title }}</strong>@endif
                        {{ \Illuminate\Support\Str::limit($review->body, 80) }}
                    </td>
                    <td class="small text-muted">{{ $review->user_id ? 'Customer' : 'Admin' }}</td>
                    <td>
                        @if($review->is_approved) <span class="badge bg-success">Approved</span>
                        @else <span class="badge bg-warning">Pending</span> @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            @can('reviews.edit')
                                @unless($review->is_approved)
                                <form action="{{ route('admin.reviews.approve', $review) }}" method="POST">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success" title="Approve"><i class="bi bi-check-lg"></i></button>
                                </form>
                                @endunless
                                <a href="{{ route('admin.reviews.edit', $review) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            @endcan
                            @can('reviews.delete')
                            <form action="{{ route('admin.reviews.destroy', $review) }}" method="POST" data-confirm="Delete this review?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-chat-square-heart d-block mb-2" style="font-size:2rem;"></i>
                        No reviews yet.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="admin-pager">{{ $reviews->links() }}</div>
@endsection
