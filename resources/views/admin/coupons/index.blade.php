@extends('admin.layouts.app')
@section('page_title', 'Coupons')

@section('content')
<p class="text-muted small">
    ডিসকাউন্ট কোড। কোনো পণ্যে আগে থেকেই অফার থাকলে দুটো ছাড় যোগ হয় না —
    প্রতি ইউনিটে <strong>যেটার ছাড় বেশি সেটাই</strong> বসে।
</p>
<div class="admin-toolbar">
    <h6 class="admin-toolbar__title">Coupons <span class="admin-toolbar__count">{{ number_format($coupons->total()) }}</span></h6>
    @include('admin.partials.search', ['route' => route('admin.coupons.index'), 'placeholder' => 'Code or label'])
    <a href="{{ route('admin.coupons.create') }}" class="btn btn-success flex-shrink-0">
        <i class="bi bi-plus-circle"></i> Add Coupon
    </a>
</div>

@can('coupons.delete')
<form id="bulk-coupons" method="POST" action="{{ route('admin.coupons.bulkDestroy') }}"
      data-bulk data-bulk-noun="coupons">
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
                        @can('coupons.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-coupons"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'code', 'label' => 'Code', 'first' => 'asc'])
                        @include('admin.partials.sort', ['key' => 'discount', 'label' => 'Discount'])
                        <th>Applies to</th>
                        @include('admin.partials.sort', ['key' => 'window', 'label' => 'Window'])
                        @include('admin.partials.sort', ['key' => 'used', 'label' => 'Used'])
                        @include('admin.partials.sort', ['key' => 'status', 'label' => 'Status'])
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($coupons as $coupon)
                <tr>
                    @can('coupons.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-coupons" name="ids[]" value="{{ $coupon->id }}"></td>@endcan
                    <td style="padding: 12px 16px;">
                        <strong class="font-monospace">{{ $coupon->code }}</strong>
                        @if($coupon->label_en)<div class="text-muted small">{{ $coupon->label_en }}</div>@endif
                    </td>
                    <td>
                        @if($coupon->type === \App\Models\Coupon::TYPE_PERCENT)
                            <strong>{{ rtrim(rtrim(number_format($coupon->value, 2), '0'), '.') }}%</strong>
                            @if($coupon->max_discount)
                                <div class="text-muted small">সর্বোচ্চ ৳{{ number_format($coupon->max_discount) }}/ইউনিট</div>
                            @endif
                        @else
                            <strong>৳{{ number_format($coupon->value) }}</strong>
                            <div class="text-muted small">প্রতি ইউনিটে</div>
                        @endif
                        @if($coupon->min_order_amount)
                            <div class="text-muted small">ন্যূনতম ৳{{ number_format($coupon->min_order_amount) }}</div>
                        @endif
                    </td>
                    <td class="small">
                        {{ \App\Models\Coupon::SCOPES[$coupon->applies_to] ?? $coupon->applies_to }}
                        @if($coupon->applies_to === 'categories')
                            <div class="text-muted">{{ $coupon->categories()->count() }} categories</div>
                        @elseif($coupon->applies_to === 'products')
                            <div class="text-muted">{{ $coupon->products()->count() }} products</div>
                        @endif
                    </td>
                    <td class="small text-muted">
                        {{ $coupon->starts_at?->format('d M Y') ?? '—' }}
                        →
                        {{ $coupon->ends_at?->format('d M Y') ?? '—' }}
                    </td>
                    <td>
                        {{ $coupon->used_count }}@if($coupon->usage_limit)<span class="text-muted"> / {{ $coupon->usage_limit }}</span>@endif
                    </td>
                    <td>
                        @if($coupon->hasExpired())
                            <span class="badge bg-secondary">Expired</span>
                        @elseif($coupon->isExhausted())
                            <span class="badge bg-secondary">Used up</span>
                        @elseif(! $coupon->is_active)
                            <span class="badge bg-secondary">Inactive</span>
                        @elseif(! $coupon->hasStarted())
                            <span class="badge bg-info">Scheduled</span>
                        @else
                            <span class="badge bg-success">Active</span>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <a href="{{ route('admin.coupons.edit', $coupon) }}" class="btn btn-sm btn-outline-primary"><i class="bi bi-pencil"></i></a>
                            <form action="{{ route('admin.coupons.destroy', $coupon) }}" method="POST" data-confirm="Delete this coupon?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="8" class="text-center text-muted py-5">
                        <i class="bi bi-ticket-perforated d-block mb-2" style="font-size:2rem;"></i>
                        এখনো কোনো কুপন তৈরি করা হয়নি।
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="admin-pager">{{ $coupons->links() }}</div>
@endsection
