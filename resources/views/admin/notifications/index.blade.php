@extends('admin.layouts.app')

@section('title', 'Notifications')
@section('page_title', 'Notifications')

@section('content')
<div class="card border-0 shadow-sm">
    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
        <h5 class="mb-0 fw-bold text-dark">
            Notifications
            @if(auth()->user()->unreadNotifications->count())
                <span class="badge bg-danger ms-1">{{ auth()->user()->unreadNotifications->count() }} unread</span>
            @endif
        </h5>
        @if(auth()->user()->unreadNotifications->count())
        <form action="{{ route('admin.notifications.readAll') }}" method="POST">
            @csrf
            <button class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-check2-all"></i> Mark all read
            </button>
        </form>
        @endif
    </div>

    <div class="card-body p-0">
        <div class="list-group list-group-flush">
            @forelse($notifications as $notification)
                @php
                    $data = $notification->data;
                    // Every alert here happens to be about an order, but they
                    // do not all mean the same thing.
                    $isPreorder = ($data['type'] ?? '') === 'preorder_stock_arrived';
                @endphp
                <form action="{{ route('admin.notifications.read', $notification->id) }}" method="POST" class="m-0">
                    @csrf
                    <button type="submit"
                            class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 w-100 text-start border-0 {{ $notification->read_at ? '' : 'bg-warning-subtle' }}">
                        <span class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0
                                     {{ $notification->read_at
                                        ? 'bg-light text-muted'
                                        : ($isPreorder ? 'bg-warning text-dark' : 'bg-primary text-white') }}"
                              style="width:40px;height:40px;">
                            <i class="bi {{ $isPreorder ? 'bi-box-seam' : 'bi-receipt' }}"></i>
                        </span>
                        <span class="flex-grow-1">
                            @if($isPreorder)
                                <span class="fw-bold text-dark d-block">
                                    Pre-order ready to fill — {{ $data['order_number'] ?? '' }}
                                </span>
                                <span class="text-muted small">
                                    {{ $data['product_name'] ?? 'Product' }}
                                    @if($data['variant_name'] ?? null)({{ $data['variant_name'] }})@endif
                                    is back in stock for {{ $data['customer_name'] ?? 'the customer' }}.
                                </span>
                            @else
                                <span class="fw-bold text-dark d-block">
                                    New order {{ $data['order_number'] ?? '' }}
                                    @if(($data['source'] ?? '') === 'pos')
                                        <span class="badge" style="background-color:#6f42c1;">POS</span>
                                    @endif
                                </span>
                                <span class="text-muted small">
                                    {{ $data['customer_name'] ?? 'Customer' }} — ৳{{ number_format($data['total'] ?? 0) }}
                                </span>
                            @endif
                        </span>
                        <span class="text-muted small flex-shrink-0">{{ $notification->created_at->diffForHumans() }}</span>
                    </button>
                </form>
            @empty
                <div class="text-center py-5 text-muted">
                    <i class="bi bi-bell-slash fs-1 d-block mb-2 opacity-25"></i>
                    Nothing yet. New orders will show up here.
                </div>
            @endforelse
        </div>
    </div>

    @if($notifications->hasPages())
    <div class="card-footer bg-white admin-pager">{{ $notifications->links() }}</div>
    @endif
</div>
@endsection
