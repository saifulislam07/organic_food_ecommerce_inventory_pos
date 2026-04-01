@extends('layouts.frontend')

@section('title', 'Checkout – Mango Hut')

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
        background: rgba(45, 106, 79, 0.05);
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
        background: rgba(45, 106, 79, 0.02);
    }
    .address-card.active {
        border-color: var(--primary);
        background: rgba(45, 106, 79, 0.05);
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
            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="card admin-card p-4">
                        <h4 class="mb-4" style="color: var(--primary-dark);">
                            <i class="bi bi-person"></i> {{ app()->getLocale() == 'bn' ? 'ডেলিভারি তথ্য' : 'Delivery Information' }}
                        </h4>

                        <div class="mb-3">
                            <label for="customer_name" class="form-label">{{ app()->getLocale() == 'bn' ? 'নাম *' : 'Full Name *' }}</label>
                            <input type="text" name="customer_name" id="customer_name" class="form-control @error('customer_name') is-invalid @enderror"
                                   value="{{ old('customer_name', auth()->user()->name ?? '') }}" placeholder="{{ app()->getLocale() == 'bn' ? 'আপনার নাম' : 'Your name' }}" required>
                            @error('customer_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="mb-3">
                            <label for="customer_phone" class="form-label">{{ app()->getLocale() == 'bn' ? 'মোবাইল নাম্বার *' : 'Phone Number *' }}</label>
                            <input type="text" name="customer_phone" id="customer_phone" class="form-control @error('customer_phone') is-invalid @enderror"
                                   value="{{ old('customer_phone', auth()->user()->mobile ?? '') }}" placeholder="01XXXXXXXXX" required>
                            @error('customer_phone') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        @if(auth()->check() && $userAddresses->count() > 0)
                        <div class="mb-4">
                            <label class="form-label">{{ app()->getLocale() == 'bn' ? 'সেভ করা ঠিকানা থেকে বেছে নিন' : 'Choose from Saved Addresses' }}</label>
                            <div class="row g-3">
                                @foreach($userAddresses as $addr)
                                <div class="col-md-6">
                                    <div class="address-card {{ $defaultAddress && $defaultAddress->id == $addr->id ? 'active' : '' }}" 
                                         onclick="selectSavedAddress(this)"
                                         data-name="{{ $addr->name }}"
                                         data-phone="{{ $addr->phone }}"
                                         data-area="{{ $addr->area }}"
                                         data-address="{{ $addr->address }}">
                                        <div class="fw-bold">{{ $addr->name }}</div>
                                        <div class="small text-muted mb-1"><i class="bi bi-telephone"></i> {{ $addr->phone }}</div>
                                        <div class="small text-truncate" title="{{ $addr->address }}"><i class="bi bi-geo-alt"></i> {{ $addr->address }}</div>
                                    </div>
                                </div>
                                @endforeach
                                <div class="col-md-6">
                                    <div class="address-card d-flex align-items-center justify-content-center h-100" onclick="resetAddressForm()">
                                        <div class="text-center">
                                            <i class="bi bi-plus-circle fs-4 text-primary"></i>
                                            <div class="small fw-bold mt-1">{{ app()->getLocale() == 'bn' ? 'নতুন ঠিকানা' : 'New Address' }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                        @endif

                        <div class="custom-radio-group shadow-sm">
                            <label class="custom-radio" for="delivery_home">
                                <input class="form-check-input" type="radio" name="delivery_type" id="delivery_home" value="home" checked>
                                <span class="fw-bold"><i class="bi bi-truck"></i> {{ app()->getLocale() == 'bn' ? 'হোম ডেলিভারি' : 'Home' }}</span>
                            </label>
                            <label class="custom-radio" for="delivery_pickup">
                                <input class="form-check-input" type="radio" name="delivery_type" id="delivery_pickup" value="pickup">
                                <span class="fw-bold"><i class="bi bi-geo-alt"></i> {{ app()->getLocale() == 'bn' ? 'পিকআপ পয়েন্ট' : 'Pickup' }}</span>
                            </label>
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
                                          rows="3" placeholder="{{ app()->getLocale() == 'bn' ? 'সম্পূর্ণ ঠিকানা লিখুন' : 'Enter full address' }}">{{ old('customer_address', $defaultAddress->address ?? '') }}</textarea>
                                @error('customer_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            </div>

                            @if(auth()->check())
                            <div class="form-check mb-3">
                                <input class="form-check-input" type="checkbox" name="save_address" id="save_address" checked>
                                <label class="form-check-label small fw-bold" for="save_address">
                                    {{ app()->getLocale() == 'bn' ? 'ভবিষ্যতের জন্য এই ঠিকানা সেভ করে রাখুন' : 'Save this address for future use' }}
                                </label>
                            </div>
                            @endif
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
    const subtotal = {{ $subtotal }};
    const freeThreshold = {{ $threshold }};
    const feeInside = {{ $shippingFeeInside }};
    const feeOutside = {{ $shippingFeeOutside }};
    
    const totalElement = document.querySelector('.summary-row.total span:last-child');
    const deliveryElement = document.querySelector('.summary-row:nth-last-child(2) span:last-child');
    const areaSelect = document.getElementById('customer_area');

    function updateSummary() {
        if (!totalElement || !deliveryElement || !areaSelect) return;

        const typeInput = document.querySelector('input[name="delivery_type"]:checked');
        const type = typeInput ? typeInput.value : 'home';
        const area = areaSelect.value;
        
        let currentFee = 0;
        if (type === 'home') {
            if (subtotal < freeThreshold) {
                currentFee = (area === 'dhaka_inside') ? feeInside : feeOutside;
            }
        }

        if (currentFee === 0) {
            deliveryElement.innerHTML = '<span class="free-delivery-badge">{{ app()->getLocale() == "bn" ? "ফ্রি" : "FREE" }}</span>';
        } else {
            deliveryElement.textContent = '৳' + currentFee.toLocaleString();
        }
        
        totalElement.textContent = '৳' + (subtotal + currentFee).toLocaleString();
    }

    document.querySelectorAll('input[name="delivery_type"]').forEach(radio => {
        radio.addEventListener('change', function() {
            if (this.value === 'pickup') {
                document.getElementById('home_delivery_fields').classList.add('d-none');
                document.getElementById('pickup_fields').classList.remove('d-none');
                document.getElementById('customer_address').required = false;
            } else {
                document.getElementById('home_delivery_fields').classList.remove('d-none');
                document.getElementById('pickup_fields').classList.add('d-none');
                document.getElementById('customer_address').required = true;
            }
            updateSummary();
        });
    });

    if (areaSelect) {
        areaSelect.addEventListener('change', updateSummary);
    }

    // Initialize summary on page load
    document.addEventListener('DOMContentLoaded', function() {
        updateSummary();
        
        // If there's a default address, apply it to the dropdown
        @if($defaultAddress)
            if (areaSelect) {
                areaSelect.value = "{{ $defaultAddress->area }}";
                updateSummary();
            }
        @endif
    });

    function selectSavedAddress(element) {
        document.querySelectorAll('.address-card').forEach(card => card.classList.remove('active'));
        element.classList.add('active');

        const name = element.dataset.name;
        const phone = element.dataset.phone;
        const area = element.dataset.area;
        const address = element.dataset.address;

        document.getElementById('customer_name').value = name;
        document.getElementById('customer_phone').value = phone;
        document.getElementById('customer_address').value = address;
        if (areaSelect) {
            areaSelect.value = area;
        }
        
        // Hide save checkbox when selecting saved address
        const saveCheckbox = document.getElementById('save_address');
        if (saveCheckbox) saveCheckbox.closest('.form-check').classList.add('d-none');

        updateSummary();
    }

    function resetAddressForm() {
        document.querySelectorAll('.address-card').forEach(card => card.classList.remove('active'));
        
        document.getElementById('customer_name').value = "{{ auth()->user()->name ?? '' }}";
        document.getElementById('customer_phone').value = "{{ auth()->user()->mobile ?? '' }}";
        document.getElementById('customer_address').value = "";
        if (areaSelect) areaSelect.value = "dhaka_inside";

        // Show save checkbox for new address
        const saveCheckbox = document.getElementById('save_address');
        if (saveCheckbox) saveCheckbox.closest('.form-check').classList.remove('d-none');
        
        updateSummary();
    }
</script>
@endpush
@endsection
