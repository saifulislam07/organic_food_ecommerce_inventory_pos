@extends('layouts.frontend')

@section('title', 'Order Confirmed – Mango Hut')

@section('content')
<section class="success-page">
    <div class="container">
        <div class="row justify-content-center">
            <div class="col-lg-6">
                <div class="success-icon">
                    <i class="bi bi-check-lg"></i>
                </div>
                <h2 style="color: var(--primary-dark); font-weight: 700;">অর্ডার সফল হয়েছে!</h2>
                <p class="text-muted mb-4">আপনার অর্ডার সফলভাবে গ্রহণ করা হয়েছে। আমরা শীঘ্রই আপনার সাথে যোগাযোগ করবো।</p>

                <div class="card admin-card p-4 text-start mb-4">
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Order Number:</span>
                        <strong style="color: var(--primary);">{{ $order->order_number }}</strong>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Name:</span>
                        <span>{{ $order->customer_name }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Phone:</span>
                        <span>{{ $order->customer_phone }}</span>
                    </div>
                    <div class="d-flex justify-content-between mb-2">
                        <span class="text-muted">Total:</span>
                        <strong style="font-size: 1.2rem; color: var(--primary);">৳{{ number_format($order->total) }}</strong>
                    </div>
                    <div class="d-flex justify-content-between">
                        <span class="text-muted">Payment:</span>
                        <span class="badge bg-warning text-dark">Cash on Delivery</span>
                    </div>

                    <hr>
                    <h6 class="fw-bold mb-2">Order Items:</h6>
                    @foreach($order->items as $item)
                    <div class="d-flex justify-content-between py-1" style="font-size: 0.9rem;">
                        <span>{{ $item->product_name }} ({{ $item->variant_name }}) × {{ $item->quantity }}</span>
                        <span>৳{{ number_format($item->total) }}</span>
                    </div>
                    @endforeach
                </div>

                <div class="d-flex flex-wrap justify-content-center gap-3">
                    <a href="{{ route('shop') }}" class="btn-primary-custom">
                        <i class="bi bi-shop"></i> Continue Shopping
                    </a>
                    <a href="https://wa.me/8801716952365?text=আমার অর্ডার নম্বর: {{ $order->order_number }}" target="_blank" class="btn-whatsapp">
                        <i class="bi bi-whatsapp"></i> Track via WhatsApp
                    </a>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
