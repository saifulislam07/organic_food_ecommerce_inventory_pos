@extends('layouts.frontend')

@section('title', 'Checkout – Mango Hut')

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
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card admin-card p-4">
                        <h4 class="mb-4" style="color: var(--primary-dark);">
                            <i class="bi bi-person"></i> {{ app()->getLocale() == 'bn' ? 'ডেলিভারি তথ্য' : 'Delivery Information' }}
                        </h4>

                        <div class="mb-3">
                            <label for="customer_name" class="form-label">{{ app()->getLocale() == 'bn' ? 'নাম *' : 'Full Name *' }}</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                                   value="{{ old('customer_name') }}" placeholder="{{ app()->getLocale() == 'bn' ? 'আপনার নাম' : 'Your name' }}" required>
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">{{ app()->getLocale() == 'bn' ? 'মোবাইল নাম্বার *' : 'Phone Number *' }}</label>
                            <input type="text" name="customer_phone" id="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror"
                                   value="{{ old('customer_phone') }}" placeholder="01XXXXXXXXX" required>
                            @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="card bg-light border-0 p-3 mb-4 rounded-3 d-flex flex-row justify-content-around gap-4 shadow-sm">
                            <div class="form-check custom-radio">
                                <input class="form-check-input" type="radio" name="delivery_type" id="delivery_home" value="home" checked>
                                <label class="form-check-label fw-bold d-block" for="delivery_home">
                                    <i class="bi bi-truck me-1"></i> {{ app()->getLocale() == 'bn' ? 'হোম ডেলিভারি' : 'Home Delivery' }}
                                </label>
                            </div>
                            <div class="form-check custom-radio">
                                <input class="form-check-input" type="radio" name="delivery_type" id="delivery_pickup" value="pickup">
                                <label class="form-check-label fw-bold d-block" for="delivery_pickup">
                                    <i class="bi bi-geo-alt me-1"></i> {{ app()->getLocale() == 'bn' ? 'পিকআপ পয়েন্ট' : 'Store Pickup' }}
                                </label>
                            </div>
                        </div>

                        <div id="home_delivery_fields">
                            <div class="mb-3">
                                <label for="customer_area" class="form-label">{{ app()->getLocale() == 'bn' ? 'এলাকা' : 'Delivery Area' }}</label>
                                <select name="customer_area" id="customer_area" class="form-select border-0 shadow-sm">
                                    <option value="dhaka_inside">{{ app()->getLocale() == 'bn' ? 'ঢাকা (ভিতরে)' : 'Dhaka (Inside)' }}</option>
                                    <option value="dhaka_outside">{{ app()->getLocale() == 'bn' ? 'ঢাকা (বাইরে)' : 'Dhaka (Outside)' }}</option>
                                </select>
                            </div>

                            <div class="mb-3">
                                <label for="customer_address" class="form-label">{{ app()->getLocale() == 'bn' ? 'সম্পূর্ণ ঠিকানা *' : 'Full Address *' }}</label>
                                <textarea name="customer_address" id="customer_address" class="form-control border-0 shadow-sm @error('customer_address') is-invalid @enderror"
                                          rows="3" placeholder="{{ app()->getLocale() == 'bn' ? 'সম্পূর্ণ ঠিকানা লিখুন' : 'Enter full address' }}">{{ old('customer_address') }}</textarea>
                                @error('customer_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>
                        </div>

                        <div id="pickup_fields" class="d-none">
                            <div class="mb-3">
                                <label for="pickup_point" class="form-label">{{ app()->getLocale() == 'bn' ? 'পিকআপ পয়েন্ট সিলেক্ট করুন *' : 'Select Pickup Point *' }}</label>
                                <select name="pickup_point" id="pickup_point" class="form-select border-0 shadow-sm">
                                    <option value="Main Branch - Dhaka">{{ app()->getLocale() == 'bn' ? 'প্রধান শাখা - ঢাকা (চাঁপাই নবাবগঞ্জ বাগান)' : 'Main Branch - Dhaka (Chapainawabganj Garden)' }}</option>
                                    <option value="Uttara Pickup Point">{{ app()->getLocale() == 'bn' ? 'উত্তরা পিকআপ পয়েন্ট' : 'Uttara Pickup Point' }}</option>
                                    <option value="Dhanmondi Pickup Point">{{ app()->getLocale() == 'bn' ? 'ধানমন্ডি পিকআপ পয়েন্ট' : 'Dhanmondi Pickup Point' }}</option>
                                </select>
                            </div>
                        </div>

                        <div class="mb-3">
                            <label for="notes" class="form-label">{{ app()->getLocale() == 'bn' ? 'অর্ডার নোট (ঐচ্ছিক)' : 'Order Notes (Optional)' }}</label>
                            <textarea name="notes" id="notes" class="form-control border-0 shadow-sm" rows="2"
                                      placeholder="{{ app()->getLocale() == 'bn' ? 'বিশেষ কোনো নির্দেশনা থাকলে লিখুন' : 'Enter any special instructions' }}">{{ old('notes') }}</textarea>
                        </div>

                        <div class="alert alert-info py-2" style="font-size: 0.9rem;">
                            <i class="bi bi-info-circle"></i> {{ app()->getLocale() == 'bn' ? 'পেমেন্ট মেথড:' : 'Payment Method:' }} <strong>Cash on Delivery (COD)</strong>
                        </div>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="cart-summary">
                        <h4><i class="bi bi-receipt"></i> {{ app()->getLocale() == 'bn' ? 'আপনার অর্ডার' : 'Your Order' }}</h4>

                        @foreach($items as $item)
                        <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                            <div>
                                <strong style="font-size:0.9rem;">{{ $item['product_name'] }}</strong>
                                <br><small class="text-muted">{{ $item['variant_name'] }} × {{ $item['quantity'] }}</small>
                            </div>
                            <span class="fw-bold">৳{{ number_format($item['subtotal']) }}</span>
                        </div>
                        @endforeach

                        <div class="summary-row mt-3">
                            <span>{{ app()->getLocale() == 'bn' ? 'সাবটোটাল' : 'Subtotal' }}</span>
                            <span>৳{{ number_format($subtotal) }}</span>
                        </div>
                        <div class="summary-row">
                            <span>{{ app()->getLocale() == 'bn' ? 'ডেলিভারি' : 'Delivery' }}</span>
                            <span>
                                @if($delivery == 0)
                                    <span class="free-delivery-badge">{{ app()->getLocale() == 'bn' ? 'ফ্রি' : 'FREE' }}</span>
                                @else
                                    ৳{{ number_format($delivery) }}
                                @endif
                            </span>
                        </div>
                        <div class="summary-row total">
                            <span>{{ app()->getLocale() == 'bn' ? 'সর্বমোট' : 'Total' }}</span>
                            <span>৳{{ number_format($total) }}</span>
                        </div>

                        <button type="submit" class="btn-primary-custom w-100 justify-content-center mt-4" style="font-size: 1.1rem; padding: 16px;">
                            <i class="bi bi-check-circle"></i> {{ app()->getLocale() == 'bn' ? 'অর্ডার প্লেস করুন' : 'Place Order' }}
                        </button>
                    </div>
                </div>
            </div>
        </form>
    </div>
</section>
@push('scripts')
<script>
    const deliveryCharge = {{ $delivery }};
    const subtotal = {{ $subtotal }};
    const totalElement = document.querySelector('.summary-row.total span:last-child');
    const deliveryElement = document.querySelector('.summary-row:nth-last-child(2) span:last-child');

    document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'pickup') {
                document.getElementById('home_delivery_fields').classList.add('d-none');
                document.getElementById('pickup_fields').classList.remove('d-none');
                document.getElementById('customer_address').required = false;
                
                deliveryElement.innerHTML = '<span class="free-delivery-badge">FREE</span>';
                totalElement.textContent = '৳' + subtotal.toLocaleString();
            } else {
                document.getElementById('home_delivery_fields').classList.remove('d-none');
                document.getElementById('pickup_fields').classList.add('d-none');
                document.getElementById('customer_address').required = true;

                deliveryElement.textContent = '৳' + deliveryCharge.toLocaleString();
                totalElement.textContent = '৳' + (subtotal + deliveryCharge).toLocaleString();
            }
        });
    });
</script>
@endpush
@endsection
