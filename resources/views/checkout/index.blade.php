@extends('layouts.frontend')

@section('title', 'Checkout – MohiPure')

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

    .admin-card {
        border-radius: var(--radius-lg);
        border: 1px solid var(--gray-100);
        box-shadow: var(--shadow-sm);
    }
    .form-label {
        font-weight: 600;
        color: var(--dark);
        margin-bottom: 8px;
        font-size: 0.9rem;
    }
    .form-control, .form-select {
        border-radius: var(--radius-sm);
        padding: 12px 15px;
        border: 1px solid var(--gray-200);
        background-color: var(--gray-100);
        transition: var(--transition);
        font-size: 0.95rem;
    }
    .form-control:focus, .form-select:focus {
        background-color: white;
        border-color: var(--primary);
        box-shadow: var(--shadow-sm);
    }

    .custom-radio-group {
        display: flex;
        gap: 15px;
        background: var(--light);
        padding: 15px;
        border-radius: var(--radius-md);
        margin-bottom: 25px;
    }
    .custom-radio {
        flex: 1;
        background: white;
        padding: 12px;
        border-radius: var(--radius-sm);
        border: 1.5px solid transparent;
        transition: var(--transition);
        cursor: pointer;
        display: flex;
        align-items: center;
        gap: 10px;
    }
    .custom-radio:has(input:checked) {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.05);
    }
    .custom-radio input { margin: 0; }
    .custom-radio label { margin: 0; cursor: pointer; flex-grow: 1; }

    @media (max-width: 576px) {
        .custom-radio-group { flex-direction: column; }
        .checkout-form .card { padding: 20px !important; }
    }

    .address-card {
        border: 2px solid var(--gray-200);
        border-radius: var(--radius-md);
        padding: 15px;
        cursor: pointer;
        transition: var(--transition);
        position: relative;
        overflow: hidden;
    }
    .address-card:hover {
        border-color: var(--primary-light);
        background: rgba(var(--primary-rgb), 0.02);
    }
    .address-card.active {
        border-color: var(--primary);
        background: rgba(var(--primary-rgb), 0.05);
    }
    .address-card.active::after {
        content: "\F272";
        font-family: "bootstrap-icons";
        position: absolute;
        top: 10px;
        right: 10px;
        color: var(--primary);
        font-size: 1.2rem;
    }
</style>
@endpush

@section('content')
<div class="page-header">
    <div class="container">
        <h1><i class="bi bi-lock"></i> {{ app()->getLocale() == 'bn' ? 'চেকআউট' : 'Checkout' }}</h1>
        <ul class="breadcrumb-custom">
            <li><a href="{{ route('home') }}">{{ app()->getLocale() == 'bn' ? 'হোম' : 'Home' }}</a></li>
            <li><span>/</span></li>
            <li><a href="{{ route('cart.index') }}">{{ app()->getLocale() == 'bn' ? 'কার্ট' : 'Cart' }}</a></li>
            <li><span>/</span></li>
            <li>{{ app()->getLocale() == 'bn' ? 'চেকআউট' : 'Checkout' }}</li>
        </ul>
    </div>
</div>

<section class="section">
    <div class="container">
        <form action="{{ route('checkout.store') }}" method="POST" class="checkout-form">
            @csrf
            @php
                $pickupPoints = [
                    ['value' => 'Main Branch - Dhaka', 'label' => app()->getLocale() == 'bn' ? 'প্রধান শাখা - ঢাকা (চাঁপাই নবাবগঞ্জ বাগান)' : 'Main Branch - Dhaka (Chapainawabganj Garden)'],
                    ['value' => 'Uttara Pickup Point', 'label' => app()->getLocale() == 'bn' ? 'উত্তরা পিকআপ পয়েন্ট' : 'Uttara Pickup Point'],
                    ['value' => 'Dhanmondi Pickup Point', 'label' => app()->getLocale() == 'bn' ? 'ধানমন্ডি পিকআপ পয়েন্ট' : 'Dhanmondi Pickup Point'],
                ];
            @endphp
            <div
                data-vue="CheckoutForm"
                data-props="{{ json_encode([
                    'items' => array_values($items),
                    'subtotal' => (float) $subtotal,
                    'freeDeliveryThreshold' => (float) $threshold,
                    'feeInside' => (float) $shippingFeeInside,
                    'feeOutside' => (float) $shippingFeeOutside,
                    'pickupPoints' => $pickupPoints,
                    'savedAddresses' => $userAddresses->map(fn ($a) => [
                        'id' => $a->id,
                        'name' => $a->name,
                        'phone' => $a->phone,
                        'area' => $a->area,
                        'address' => $a->address,
                    ])->values(),
                    'defaultAddressId' => $defaultAddress->id ?? null,
                    'authenticated' => auth()->check(),
                    'user' => [
                        'name' => auth()->user()->name ?? '',
                        'mobile' => auth()->user()->mobile ?? '',
                    ],
                    'old' => (object) old(),
                    'errors' => $errors->toArray(),
                    'labels' => [
                        'deliveryInfo' => app()->getLocale() == 'bn' ? 'ডেলিভারি তথ্য' : 'Delivery Information',
                        'name' => app()->getLocale() == 'bn' ? 'নাম *' : 'Full Name *',
                        'namePlaceholder' => app()->getLocale() == 'bn' ? 'আপনার নাম' : 'Your name',
                        'phone' => app()->getLocale() == 'bn' ? 'মোবাইল নাম্বার *' : 'Phone Number *',
                        'savedAddresses' => app()->getLocale() == 'bn' ? 'সেভ করা ঠিকানা থেকে বেছে নিন' : 'Choose from Saved Addresses',
                        'newAddress' => app()->getLocale() == 'bn' ? 'নতুন ঠিকানা' : 'New Address',
                        'home' => app()->getLocale() == 'bn' ? 'হোম ডেলিভারি' : 'Home',
                        'pickup' => app()->getLocale() == 'bn' ? 'পিকআপ পয়েন্ট' : 'Pickup',
                        'area' => app()->getLocale() == 'bn' ? 'এলাকা' : 'Delivery Area',
                        'areaInside' => app()->getLocale() == 'bn' ? 'ঢাকা (ভিতরে)' : 'Dhaka (Inside)',
                        'areaOutside' => app()->getLocale() == 'bn' ? 'ঢাকা (বাইরে)' : 'Dhaka (Outside)',
                        'address' => app()->getLocale() == 'bn' ? 'সম্পূর্ণ ঠিকানা *' : 'Full Address *',
                        'addressPlaceholder' => app()->getLocale() == 'bn' ? 'সম্পূর্ণ ঠিকানা লিখুন' : 'Enter full address',
                        'saveAddress' => app()->getLocale() == 'bn' ? 'ভবিষ্যতের জন্য এই ঠিকানা সেভ করে রাখুন' : 'Save this address for future use',
                        'pickupPoint' => app()->getLocale() == 'bn' ? 'পিকআপ পয়েন্ট সিলেক্ট করুন *' : 'Select Pickup Point *',
                        'notes' => app()->getLocale() == 'bn' ? 'অর্ডার নোট (ঐচ্ছিক)' : 'Order Notes (Optional)',
                        'notesPlaceholder' => app()->getLocale() == 'bn' ? 'বিশেষ কোনো নির্দেশনা থাকলে লিখুন' : 'Enter any special instructions',
                        'paymentMethod' => app()->getLocale() == 'bn' ? 'পেমেন্ট মেথড:' : 'Payment Method:',
                        'yourOrder' => app()->getLocale() == 'bn' ? 'আপনার অর্ডার' : 'Your Order',
                        'subtotal' => app()->getLocale() == 'bn' ? 'সাবটোটাল' : 'Subtotal',
                        'delivery' => app()->getLocale() == 'bn' ? 'ডেলিভারি' : 'Delivery',
                        'total' => app()->getLocale() == 'bn' ? 'সর্বমোট' : 'Total',
                        'free' => app()->getLocale() == 'bn' ? 'ফ্রি' : 'FREE',
                        'placeOrder' => app()->getLocale() == 'bn' ? 'অর্ডার প্লেস করুন' : 'Place Order',
                    ],
                ], JSON_UNESCAPED_UNICODE) }}"
            ></div>
        </form>
    </div>
</section>
@endsection
