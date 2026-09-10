@extends('layouts.frontend')

@section('title', 'Cart – BaburhashiBD')

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
@include('partials.page-head', [
    'title' => app()->getLocale() == 'bn' ? 'শপিং কার্ট' : 'Shopping Cart',
    'icon' => 'cart3',
    'crumbs' => [app()->getLocale() == 'bn' ? 'কার্ট' : 'Cart'],
])

<section class="section">
    <div class="container">
        <div
            data-vue="CartPage"
            data-props="{{ json_encode([
                'items' => (object) $items,
                'subtotal' => (float) $subtotal,
                'discount' => (float) $discount,
                'delivery' => (float) $delivery,
                'total' => (float) $total,
                'coupon' => $coupon ? ['code' => $coupon->code, 'label' => $coupon->label] : null,
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

                    // Coupon box. The coupon_* keys answer the reason the server
                    // sends back when it turns a code down.
                    'couponPlaceholder' => app()->getLocale() == 'bn' ? 'ডিসকাউন্ট কোড' : 'Discount code',
                    'couponApply' => app()->getLocale() == 'bn' ? 'প্রয়োগ' : 'Apply',
                    'couponRemove' => app()->getLocale() == 'bn' ? 'সরান' : 'Remove',
                    'couponDiscount' => app()->getLocale() == 'bn' ? 'কুপন ছাড়' : 'Coupon discount',
                    'couponOnLine' => app()->getLocale() == 'bn' ? 'কুপন প্রযোজ্য' : 'Coupon applied',
                    'preorderOnLine' => app()->getLocale() == 'bn' ? 'প্রি-অর্ডার' : 'Pre-order',
                    'coupon_not_found' => app()->getLocale() == 'bn' ? 'এই কোডটি সঠিক নয়।' : 'That code is not valid.',
                    'coupon_inactive' => app()->getLocale() == 'bn' ? 'কোডটি এখন চালু নেই।' : 'That code is not active right now.',
                    'coupon_expired' => app()->getLocale() == 'bn' ? 'কোডটির মেয়াদ শেষ।' : 'That code has expired.',
                    'coupon_exhausted' => app()->getLocale() == 'bn' ? 'কোডটি আর ব্যবহার করা যাবে না।' : 'That code has been fully used.',
                    'coupon_min_order' => app()->getLocale() == 'bn' ? 'এই অর্ডারের পরিমাণ কোডটির জন্য যথেষ্ট নয়।' : 'Your order is below this code’s minimum.',
                    'coupon_per_user' => app()->getLocale() == 'bn' ? 'আপনি কোডটি ইতিমধ্যে ব্যবহার করেছেন।' : 'You have already used this code.',
                    'coupon_no_saving' => app()->getLocale() == 'bn'
                        ? 'কার্টের পণ্যে ইতিমধ্যেই এর চেয়ে ভালো ছাড় চলছে।'
                        : 'Your items already have a better discount than this code.',
                    'coupon_error' => app()->getLocale() == 'bn' ? 'কোডটি ব্যবহার করা যাচ্ছে না।' : 'This code cannot be used.',
                ],
            ], JSON_UNESCAPED_UNICODE) }}"
        ></div>

    </div>
</section>
@endsection
