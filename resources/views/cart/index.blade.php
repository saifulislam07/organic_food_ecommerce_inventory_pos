@extends('layouts.frontend')

@section('title', 'Cart – Mango Hut')

@section('content')
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-cart3"></i> {{ app()->getLocale() == 'bn' ? 'শপিং কার্ট' : 'Shopping Cart' }}</h1>
        <ul class="breadcrumb-custom">
            <li><a href="{{ route('home') }}">{{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}</a></li>
            <li><span>/</span></li>
            <li>{{ app()->getLocale() == 'bn' ? 'কার্ট' : 'Cart' }}</li>
        </ul>
    </div>
</div>

<section class="section">
    <div class="container">
        @if(count($items) > 0)
        <div class="row g-4">
            <div class="col-lg-8">
                <div class="table-responsive">
                    <table class="table cart-table">
                        <thead>
                            <tr>
                                <th>{{ app()->getLocale() == 'bn' ? 'পণ্য' : 'Product' }}</th>
                                <th>{{ app()->getLocale() == 'bn' ? 'মূল্য' : 'Price' }}</th>
                                <th>{{ app()->getLocale() == 'bn' ? 'পরিমান' : 'Quantity' }}</th>
                                <th>{{ app()->getLocale() == 'bn' ? 'মোট' : 'Subtotal' }}</th>
                                <th></th>
                            </tr>
                        </thead>
                        <tbody id="cartItems">
                            @foreach($items as $key => $item)
                            <tr id="cart-row-{{ $key }}">
                                <td>
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $item['image'] ? asset('storage/' . $item['image']) : asset('images/product-placeholder.jpg') }}"
                                             alt="{{ $item['product_name'] }}" class="cart-item-img">
                                        <div>
                                            <strong>{{ $item['product_name'] }}</strong>
                                            <br><small class="text-muted">{{ $item['variant_name'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td>৳{{ number_format($item['price']) }}</td>
                                <td>
                                    <div class="qty-control">
                                        <button class="qty-btn" onclick="updateCartQty('{{ $key }}', {{ $item['quantity'] - 1 }})">−</button>
                                        <input type="text" class="qty-value" value="{{ $item['quantity'] }}" readonly>
                                        <button class="qty-btn" onclick="updateCartQty('{{ $key }}', {{ $item['quantity'] + 1 }})">+</button>
                                    </div>
                                </td>
                                <td class="fw-bold">৳{{ number_format($item['subtotal']) }}</td>
                                <td>
                                    <button class="cart-remove" onclick="removeFromCart('{{ $key }}')">
                                        <i class="bi bi-trash"></i>
                                    </button>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
                <a href="{{ route('shop') }}" class="btn btn-outline-secondary">
                    <i class="bi bi-arrow-left"></i> {{ app()->getLocale() == 'bn' ? 'আরো কেনাকাটা করুন' : 'Continue Shopping' }}
                </a>
            </div>
            <div class="col-lg-4">
                <div class="cart-summary">
                    <h4><i class="bi bi-receipt"></i> {{ app()->getLocale() == 'bn' ? 'অর্ডার সামারি' : 'Order Summary' }}</h4>
                    <div class="summary-row">
                        <span>{{ app()->getLocale() == 'bn' ? 'সাবটোটাল' : 'Subtotal' }}</span>
                        <span id="cartSubtotal">৳{{ number_format($subtotal) }}</span>
                    </div>
                    <div class="summary-row">
                        <span>{{ app()->getLocale() == 'bn' ? 'ডেলিভারি' : 'Delivery' }}</span>
                        <span id="cartDelivery">
                            @if($delivery == 0)
                                <span class="free-delivery-badge">{{ app()->getLocale() == 'bn' ? 'ফ্রি' : 'FREE' }}</span>
                            @else
                                ৳{{ number_format($delivery) }}
                            @endif
                        </span>
                    </div>
                    <div class="summary-row total">
                        <span>{{ app()->getLocale() == 'bn' ? 'সর্বমোট' : 'Total' }}</span>
                        <span id="cartTotal">৳{{ number_format($total) }}</span>
                    </div>
                    @if($subtotal < 2000)
                    <div class="alert alert-info mt-3 mb-0" style="font-size:0.85rem;">
                        <i class="bi bi-info-circle"></i> {{ app()->getLocale() == 'bn' ? '৳'.number_format(2000 - $subtotal).' আরো অর্ডার করলে ফ্রি ডেলিভারি পাবেন!' : 'Order ৳'.number_format(2000 - $subtotal).' more for FREE delivery!' }}
                    </div>
                    @endif
                    <a href="{{ route('checkout') }}" class="btn-primary-custom w-100 justify-content-center mt-3">
                        <i class="bi bi-lock"></i> {{ app()->getLocale() == 'bn' ? 'চেকআউট করুন' : 'Proceed to Checkout' }}
                    </a>
                </div>
            </div>
        </div>
        @else
        <div class="empty-state">
            <div class="empty-state-icon"><i class="bi bi-cart-x"></i></div>
            <h3>{{ app()->getLocale() == 'bn' ? 'আপনার কার্ট এখন খালি' : 'Your cart is empty' }}</h3>
            <p class="text-muted">{{ app()->getLocale() == 'bn' ? 'মনে হচ্ছে আপনি এখনো কোনো পণ্য যোগ করেননি' : "Looks like you haven't added anything yet." }}</p>
            <a href="{{ route('shop') }}" class="btn-primary-custom mt-3">
                <i class="bi bi-shop"></i> {{ app()->getLocale() == 'bn' ? 'কেনাকাটা শুরু করুন' : 'Start Shopping' }}
            </a>
        </div>
        @endif
    </div>
</section>
@endsection

@push('scripts')
<script>
function updateCartQty(key, qty) {
    if (qty < 1) { removeFromCart(key); return; }
    $.post('{{ route("cart.update") }}', { key: key, quantity: qty }, function(data) {
        if (data.success) { location.reload(); }
    });
}

function removeFromCart(key) {
    $.post('{{ route("cart.remove") }}', { key: key }, function(data) {
        if (data.success) { location.reload(); }
    });
}
</script>
@endpush
