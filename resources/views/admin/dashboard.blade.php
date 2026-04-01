@extends('admin.layouts.app')
@section('page_title', 'Dashboard')

@section('content')
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-primary">
            <i class="bi bi-box-seam stat-icon"></i>
            <div class="stat-value">{{ $stats['total_products'] }}</div>
            <div class="stat-label">Total Products</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-info">
            <i class="bi bi-receipt stat-icon"></i>
            <div class="stat-value">{{ $stats['total_orders'] }}</div>
            <div class="stat-label">Total Orders</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-warning">
            <i class="bi bi-clock stat-icon"></i>
            <div class="stat-value text-danger">{{ $stats['low_stock_count'] }}</div>
            <div class="stat-label">Low Stock Alerts</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-success">
            <i class="bi bi-currency-exchange stat-icon"></i>
            <div class="stat-value">৳{{ number_format($stats['total_revenue']) }}</div>
            <div class="stat-label">Total Revenue</div>
        </div>
    </div>
</div>

<div class="row g-4 mb-4">
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-white p-3 d-flex flex-row align-items-center gap-3">
            <div class="bg-primary-subtle text-primary p-3 rounded-circle" style="font-size: 1.5rem;">
                <i class="bi bi-safe"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Inventory Asset Value</small>
                <h4 class="mb-0 fw-bold">৳{{ number_format($stats['stock_value']) }}</h4>
            </div>
        </div>
    </div>
    <div class="col-md-6">
        <div class="card border-0 shadow-sm bg-white p-3 d-flex flex-row align-items-center gap-3">
            <div class="bg-danger-subtle text-danger p-3 rounded-circle" style="font-size: 1.5rem;">
                <i class="bi bi-cash-stack"></i>
            </div>
            <div>
                <small class="text-muted text-uppercase fw-bold" style="font-size: 0.7rem;">Total Business Expenses</small>
                <h4 class="mb-0 fw-bold">৳{{ number_format($stats['total_expenses']) }}</h4>
            </div>
        </div>
    </div>
</div>

<div class="row g-4">
    <div class="col-lg-8">
        <div class="card admin-card">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold" style="color: var(--primary-dark);">Recent Orders</h5>
            </div>
            <div class="card-body px-4">
                @if($stats['recent_orders']->count())
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        @foreach($stats['recent_orders'] as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td>৳{{ number_format($order->total) }}</td>
                            <td>{!! $order->status_badge !!}</td>
                            <td>{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-4">No orders yet.</p>
                @endif
            </div>
        </div>
    </div>
    <div class="col-lg-4">
        <div class="card admin-card p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Quick Actions</h5>
            <div class="d-grid gap-2">
                <a href="{{ route('admin.pos.index') }}" class="btn btn-primary"><i class="bi bi-calculator"></i> POS system</a>
                <a href="{{ route('admin.purchases.create') }}" class="btn btn-outline-success"><i class="bi bi-cart-plus"></i> New Purchase</a>
                <a href="{{ route('admin.adjustments.create') }}" class="btn btn-outline-warning"><i class="bi bi-tools"></i> Adjust Stock</a>
                <a href="{{ route('admin.products.create') }}" class="btn btn-outline-dark"><i class="bi bi-plus-circle"></i> Add Product</a>
            </div>
        </div>
    </div>
</div>
@endsection
