@extends('admin.layouts.app')
@section('page_title', 'Orders')

@section('content')
<div class="d-flex justify-content-between align-items-center mb-4">
    <form action="{{ route('admin.orders.index') }}" method="GET" class="d-flex gap-2">
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            <option value="pending" {{ request('status') == 'pending' ? 'selected' : '' }}>Pending</option>
            <option value="confirmed" {{ request('status') == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
            <option value="processing" {{ request('status') == 'processing' ? 'selected' : '' }}>Processing</option>
            <option value="shipped" {{ request('status') == 'shipped' ? 'selected' : '' }}>Shipped</option>
            <option value="delivered" {{ request('status') == 'delivered' ? 'selected' : '' }}>Delivered</option>
            <option value="cancelled" {{ request('status') == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
        </select>
        <input type="text" name="search" class="form-control" placeholder="Order ID, Name or Phone" value="{{ request('search') }}">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>
</div>

<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background: var(--gray-100);">
                    <tr>
                        <th style="padding: 14px 16px;">Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Phone</th>
                        <th>Total</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                <tr>
                    <td style="padding: 16px;">
                        <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-decoration-none">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td>{{ $order->created_at->format('d M Y, h:i A') }}</td>
                    <td>{{ $order->customer_name }}</td>
                    <td>{{ $order->customer_phone }}</td>
                    <td class="fw-bold">
                        ৳{{ number_format($order->total) }}
                        @if($order->discount_amount > 0)
                            <br><small class="text-danger">(-৳{{ number_format($order->discount_amount) }})</small>
                        @endif
                    </td>
                    <td>
                        @if($order->source === 'pos')
                            <span class="badge bg-purple" style="background-color: #6f42c1;">POS</span>
                        @else
                            <span class="badge bg-blue" style="background-color: #0d6efd;">Web</span>
                        @endif
                    </td>
                    <td>{!! $order->status_badge !!}</td>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i> View
                        </a>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="7" class="text-center py-4">No orders found.</td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $orders->links() }}</div>
@endsection
