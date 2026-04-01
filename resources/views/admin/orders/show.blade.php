@extends('admin.layouts.app')
@section('page_title', 'Order Details: ' . $order->order_number)

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <!-- Order Items -->
        <div class="card admin-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold" style="color: var(--primary-dark);">Order Items</h5>
                <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-printer"></i> Print Invoice
                </a>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Quantity</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <div>
                                    <strong class="text-dark">{{ $item->product_name }}</strong><br>
                                    <small class="text-muted">{{ $item->variant_name }}</small>
                                </div>
                            </td>
                            <td>৳{{ number_format($item->unit_price) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-end fw-bold">৳{{ number_format($item->total) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="3" class="text-end">Subtotal</td>
                                <td class="text-end">৳{{ number_format($order->subtotal) }}</td>
                            </tr>
                            @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end text-danger">Discount</td>
                                <td class="text-end text-danger">-৳{{ number_format($order->discount_amount) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end">Delivery Charge</td>
                                <td class="text-end">৳{{ number_format($order->delivery_charge) }}</td>
                            </tr>
                            <tr style="font-size: 1.1rem;">
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold" style="color: var(--primary);">৳{{ number_format($order->total) }}</td>
                            </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        <!-- Order Notes -->
        @if($order->notes)
        <div class="card admin-card">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold" style="color: var(--primary-dark);">Order Notes</h5>
            </div>
            <div class="card-body px-4">
                <p class="text-muted mb-0">{{ $order->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <!-- Status Control -->
        <div class="card admin-card mb-4 p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Order Status</h5>
            <div class="mb-3">
                <div class="d-flex align-items-center gap-2">
                    {!! $order->status_badge !!}
                    <span class="text-muted small">Updated on {{ $order->updated_at->format('d M Y, h:i A') }}</span>
                </div>
            </div>
            <form action="{{ route('admin.orders.updateStatus', $order) }}" method="POST">
                @csrf
                @method('PATCH')
                <div class="input-group">
                    <select name="status" class="form-select">
                        <option value="pending" {{ $order->status == 'pending' ? 'selected' : '' }}>Pending</option>
                        <option value="confirmed" {{ $order->status == 'confirmed' ? 'selected' : '' }}>Confirmed</option>
                        <option value="processing" {{ $order->status == 'processing' ? 'selected' : '' }}>Processing</option>
                        <option value="shipped" {{ $order->status == 'shipped' ? 'selected' : '' }}>Shipped</option>
                        <option value="delivered" {{ $order->status == 'delivered' ? 'selected' : '' }}>Delivered</option>
                        <option value="cancelled" {{ $order->status == 'cancelled' ? 'selected' : '' }}>Cancelled</option>
                    </select>
                    <button class="btn btn-primary" type="submit">Update</button>
                </div>
            </form>
        </div>

        <!-- Customer Info -->
        <div class="card admin-card mb-4 p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Customer Details</h5>
            <div class="d-flex flex-column gap-2">
                <div>
                    <span class="text-muted small d-block">Full Name</span>
                    <span class="fw-bold">{{ $order->customer_name }}</span>
                </div>
                <div>
                    <span class="text-muted small d-block">Phone Number</span>
                    <a href="tel:{{ $order->customer_phone }}" class="fw-bold text-decoration-none">
                        {{ $order->customer_phone }}
                    </a>
                </div>
                <div>
                    <span class="text-muted small d-block">Delivery Area</span>
                    <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $order->customer_area ?? 'N/A')) }}</span>
                </div>
                <div>
                    <span class="text-muted small d-block">Order Source</span>
                    @if($order->source === 'pos')
                        <span class="badge" style="background-color: #6f42c1;">POS System</span>
                    @else
                        <span class="badge" style="background-color: #0d6efd;">Website Order</span>
                    @endif
                </div>
                <div>
                    <span class="text-muted small d-block">Delivery Type</span>
                    @if($order->pickup_point)
                        <span class="badge bg-success">Store Pickup</span>
                    @else
                        <span class="badge bg-secondary">Home Delivery</span>
                    @endif
                </div>
                @if($order->pickup_point)
                <div>
                    <span class="text-muted small d-block">Pickup Point</span>
                    <span class="fw-bold text-success"><i class="bi bi-geo-alt"></i> {{ $order->pickup_point }}</span>
                </div>
                @endif
                <div>
                    <span class="text-muted small d-block">Full Address</span>
                    <span class="text-muted" style="font-size: 0.9rem;">{{ $order->customer_address }}</span>
                </div>
                <div class="mt-2 pt-2 border-top">
                    <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $order->customer_phone) }}" target="_blank" class="btn btn-sm btn-outline-success w-100">
                        <i class="bi bi-whatsapp"></i> Chat on WhatsApp
                    </a>
                </div>
            </div>
        </div>

        <!-- Payment Info -->
        <div class="card admin-card p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Payment Info</h5>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Payment Method:</span>
                <span class="badge bg-warning text-dark text-uppercase">{{ $order->payment_method }}</span>
            </div>
        </div>
    </div>
</div>
@endsection
