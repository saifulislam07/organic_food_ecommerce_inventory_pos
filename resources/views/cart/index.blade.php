@extends('layouts.frontend')

@section('title', 'Cart – Mango Hut')

@push('styles')
<style>
    .cart-table th {
        background: var(--gray-100);
        color: var(--dark);
        font-weight: 700;
        text-transform: uppercase;
        font-size: 0.8rem;
        letter-spacing: 1px;
        border: none;
        padding: 15px;
    }
    .cart-item-img {
        width: 80px;
        height: 80px;
        object-fit: contain;
        border-radius: var(--radius-sm);
        background: var(--gray-100);
    }
    .qty-control {
        display: flex;
        align-items: center;
        border: 1px solid var(--gray-300);
        border-radius: var(--radius-sm);
        overflow: hidden;
        width: fit-content;
    }
    .qty-btn {
        background: var(--gray-100);
        border: none;
        width: 30px;
        height: 35px;
        font-weight: 700;
        transition: var(--transition);
    }
    .qty-btn:hover { background: var(--gray-200); }
    .qty-value {
        width: 40px;
        text-align: center;
        border: none;
        border-left: 1px solid var(--gray-300);
        border-right: 1px solid var(--gray-300);
        font-size: 0.9rem;
    }
    .cart-remove {
        color: var(--accent);
        background: none;
        border: none;
        font-size: 1.2rem;
        transition: var(--transition);
    }
    .cart-remove:hover { color: #c32f27; transform: scale(1.1); }

    .cart-summary {
        background: white;
        padding: 30px;
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-100);
        box-shadow: var(--shadow-sm);
    }
    .summary-row {
        display: flex;
        justify-content: space-between;
        margin-bottom: 15px;
        color: #555;
    }
    .summary-row.total {
        margin-top: 20px;
        padding-top: 20px;
        border-top: 2px solid var(--gray-100);
        color: var(--primary);
        font-weight: 800;
        font-size: 1.3rem;
    }
    .free-delivery-badge {
        background: var(--primary);
        color: white;
        padding: 2px 8px;
        border-radius: 4px;
        font-size: 0.75rem;
    }

    @media (max-width: 768px) {
        .cart-table thead { display: none; }
        .cart-table, .cart-table tbody, .cart-table tr, .cart-table td { display: block; width: 100%; }
        .cart-table tr {
            margin-bottom: 20px;
            background: white;
            border: 1px solid var(--gray-100);
            border-radius: var(--radius-md);
            padding: 15px;
            position: relative;
        }
        .cart-table td {
            border: none;
            padding: 8px 0;
            display: flex;
            justify-content: space-between;
            align-items: center;
            text-align: right;
        }
        .cart-table td:first-child {
            display: block;
            text-align: left;
            margin-bottom: 15px;
            border-bottom: 1px solid var(--gray-100);
            padding-bottom: 15px;
        }
        .cart-table td:first-child .d-flex { gap: 15px; }
        .cart-table td::before {
            content: attr(data-label);
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            color: var(--gray-500);
        }
        .cart-table td:first-child::before, .cart-table td:last-child::before { content: none; }
        .cart-remove { position: absolute; top: 15px; right: 15px; }
    }
</style>
@endpush

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
                                <td data-label="{{ app()->getLocale() == 'bn' ? 'পণ্য' : 'Product' }}">
                                    <div class="d-flex align-items-center gap-3">
                                        <img src="{{ $item['image'] ? asset('storage/' . $item['image']) : asset('images/product-placeholder.jpg') }}"
                                             alt="{{ $item['product_name'] }}" class="cart-item-img">
                                        <div>
                                            <strong class="d-block">{{ $item['product_name'] }}</strong>
                                            <small class="text-muted">{{ $item['variant_name'] }}</small>
                                        </div>
                                    </div>
                                </td>
                                <td data-label="{{ app()->getLocale() == 'bn' ? 'মূল্য' : 'Price' }}">৳{{ number_format($item['price']) }}</td>
                                <td data-label="{{ app()->getLocale() == 'bn' ? 'পরিমান' : 'Quantity' }}">
                                    <div class="qty-control">
                                        <button class="qty-btn" onclick="updateCartQty('{{ $key }}', {{ $item['quantity'] - 1 }})">−</button>
                                        <input type="text" class="qty-value" value="{{ $item['quantity'] }}" readonly>
                                        <button class="qty-btn" onclick="updateCartQty('{{ $key }}', {{ $item['quantity'] + 1 }})">+</button>
                                    </div>
                                </td>
                                <td data-label="{{ app()->getLocale() == 'bn' ? 'মোট' : 'Subtotal' }}" class="fw-bold text-primary">৳{{ number_format($item['subtotal']) }}</td>
                                <td>
                                    <button class="cart-remove" onclick="removeFromCart('{{ $key }}')" title="Remove">
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
