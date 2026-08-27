@extends('layouts.frontend')

@section('title', 'Cart – Mango Hut')

@push('styles')
<style>
    .page-header {
        background-color: var(--primary-dark);
        padding: 60px 0;
        color: white;
    }
    .page-header h1 {
        color: white !important;
        margin-bottom: 10px;
    }
    .breadcrumb-custom {
        display: flex;
        align-items: center;
        gap: 10px;
        list-style: none;
        padding: 0;
        margin: 0;
        font-size: 0.9rem;
    }
    .breadcrumb-custom a {
        color: rgba(255, 255, 255, 0.8);
        text-decoration: none;
        transition: var(--transition);
    }
    .breadcrumb-custom a:hover {
        color: white;
    }
    .breadcrumb-custom span {
        color: rgba(255, 255, 255, 0.5);
    }
    .breadcrumb-custom li:last-child {
        color: white;
        font-weight: 600;
    }

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

    /* Custom Secondary Button */
    .btn-secondary-custom {
        display: inline-flex;
        align-items: center;
        gap: 10px;
        background: #f8fafc;
        color: #475569;
        border: 1px solid #e2e8f0;
        padding: 12px 24px;
        border-radius: var(--radius-md);
        font-weight: 700;
        text-decoration: none;
        transition: all 0.3s ease;
    }
    .btn-secondary-custom:hover {
        background: #f1f5f9;
        color: var(--primary);
        border-color: var(--primary-light);
        transform: translateX(-5px);
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
        <div
            data-vue="CartPage"
            data-props="{{ json_encode([
                'items' => (object) $items,
                'subtotal' => (float) $subtotal,
                'delivery' => (float) $delivery,
                'total' => (float) $total,
                'shopUrl' => route('shop'),
                'checkoutUrl' => route('checkout'),
                'labels' => [
                    'product' => app()->getLocale() == 'bn' ? 'পণ্য' : 'Product',
                    'price' => app()->getLocale() == 'bn' ? 'মূল্য' : 'Price',
                    'quantity' => app()->getLocale() == 'bn' ? 'পরিমান' : 'Quantity',
                    'subtotal' => app()->getLocale() == 'bn' ? 'মোট' : 'Subtotal',
                    'remove' => app()->getLocale() == 'bn' ? 'সরান' : 'Remove',
                    'continue' => app()->getLocale() == 'bn' ? 'আরো কেনাকাটা করুন' : 'Continue Shopping',
                    'summary' => app()->getLocale() == 'bn' ? 'অর্ডার সামারি' : 'Order Summary',
                    'delivery' => app()->getLocale() == 'bn' ? 'ডেলিভারি' : 'Delivery',
                    'total' => app()->getLocale() == 'bn' ? 'সর্বমোট' : 'Total',
                    'free' => app()->getLocale() == 'bn' ? 'ফ্রি' : 'FREE',
                    'freeDeliveryHint' => app()->getLocale() == 'bn' ? '{amount} আরো অর্ডার করলে ফ্রি ডেলিভারি পাবেন!' : 'Order {amount} more for FREE delivery!',
                    'checkout' => app()->getLocale() == 'bn' ? 'চেকআউট করুন' : 'Proceed to Checkout',
                    'emptyTitle' => app()->getLocale() == 'bn' ? 'আপনার কার্ট এখন খালি' : 'Your cart is empty',
                    'emptyBody' => app()->getLocale() == 'bn' ? 'মনে হচ্ছে আপনি এখনো কোনো পণ্য যোগ করেননি' : "Looks like you haven't added anything yet.",
                    'startShopping' => app()->getLocale() == 'bn' ? 'কেনাকাটা শুরু করুন' : 'Start Shopping',
                ],
            ], JSON_UNESCAPED_UNICODE) }}"
        ></div>

    </div>
</section>
@endsection
