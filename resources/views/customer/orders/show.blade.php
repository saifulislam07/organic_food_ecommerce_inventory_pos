@extends('layouts.frontend')

@section('title', (app()->getLocale() == 'bn' ? 'অর্ডার ডিটেইলস' : 'Order Details') . ' – Mango Hut')

@push('styles')
<style>
    .dashboard-wrapper {
        padding: 60px 0;
        background-color: #f8f9fa;
    }
    .dashboard-sidebar {
        background: white;
        border-radius: var(--radius-lg);
        padding: 20px;
        box-shadow: var(--shadow-sm);
        border: 1px solid var(--gray-100);
        height: fit-content;
    }
    .sidebar-link {
        display: flex;
        align-items: center;
        gap: 12px;
        padding: 12px 15px;
        border-radius: var(--radius-sm);
        color: var(--gray-600);
        text-decoration: none;
        transition: var(--transition);
        font-weight: 600;
        margin-bottom: 5px;
    }
    .sidebar-link i {
        font-size: 1.2rem;
    }
    .sidebar-link:hover, .sidebar-link.active {
        background-color: rgba(45, 106, 79, 0.05);
        color: var(--primary);
    }

    /* Order Tracker */
    .order-tracker {
        display: flex;
        justify-content: space-between;
        margin-bottom: 40px;
        position: relative;
        padding: 0 20px;
    }
    .order-tracker::before {
        content: '';
        position: absolute;
        top: 25px;
        left: 50px;
        right: 50px;
        height: 4px;
        background: var(--gray-200);
        z-index: 1;
    }
    .tracker-step {
        position: relative;
        z-index: 2;
        text-align: center;
        flex: 1;
    }
    .step-icon {
        width: 50px;
        height: 50px;
        background: white;
        border: 4px solid var(--gray-200);
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 10px;
        font-size: 1.2rem;
        color: var(--gray-400);
        transition: var(--transition);
    }
    .tracker-step.active .step-icon {
        border-color: var(--primary);
        color: var(--primary);
        background: white;
    }
    .tracker-step.completed .step-icon {
        background: var(--primary);
        border-color: var(--primary);
        color: white;
    }
    .step-label {
        font-size: 0.75rem;
        font-weight: 700;
        text-transform: uppercase;
        color: var(--gray-500);
    }
    .tracker-step.active .step-label, .tracker-step.completed .step-label {
        color: var(--dark);
    }

    .tracker-line-fill {
        position: absolute;
        top: 25px;
        left: 50px;
        height: 4px;
        background: var(--primary);
        z-index: 1;
        transition: width 0.5s ease;
    }

    .order-summary-box {
        background: white;
        border-radius: var(--radius-lg);
        padding: 25px;
        border: 1px solid var(--gray-100);
    }
</style>
@endpush

@section('content')
<div class="dashboard-wrapper">
    <div class="container">
        <div class="row g-4">
            <!-- Sidebar -->
            <div class="col-lg-3">
                <div class="dashboard-sidebar">
                    <div class="text-center mb-4 pb-4 border-bottom">
                        <div class="mb-2">
                            <div class="avatar-placeholder bg-primary-light d-inline-flex align-items-center justify-content-center rounded-circle text-primary fw-bold fs-3" style="width: 70px; height: 70px;">
                                {{ substr(auth()->user()->name, 0, 1) }}
                            </div>
                        </div>
                        <h5 class="mb-0 fw-bold">{{ auth()->user()->name }}</h5>
                        <small class="text-muted">{{ auth()->user()->email }}</small>
                    </div>
                    
                    <nav>
                        <a href="{{ route('customer.dashboard') }}" class="sidebar-link {{ request()->routeIs('customer.dashboard') ? 'active' : '' }}">
                            <i class="bi bi-speedometer2"></i> {{ app()->getLocale() == 'bn' ? 'ড্যাশবোর্ড' : 'Dashboard' }}
                        </a>
                        <a href="{{ route('cart.index') }}" class="sidebar-link">
                            <i class="bi bi-cart"></i> {{ app()->getLocale() == 'bn' ? 'আমার কার্ট' : 'My Cart' }}
                        </a>
                        <a href="{{ route('profile.edit') }}" class="sidebar-link">
                            <i class="bi bi-person-gear"></i> {{ app()->getLocale() == 'bn' ? 'প্রোফাইল সেটিংস' : 'Profile Settings' }}
                        </a>
                        <form method="POST" action="{{ route('logout') }}">
                            @csrf
                            <button type="submit" class="sidebar-link text-danger border-0 bg-transparent w-100">
                                <i class="bi bi-box-arrow-right"></i> {{ app()->getLocale() == 'bn' ? 'লগআউট' : 'Logout' }}
                            </button>
                        </form>
                    </nav>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h4 class="fw-bold mb-1">{{ app()->getLocale() == 'bn' ? 'অর্ডার নং' : 'Order' }} #{{ $order->order_number }}</h4>
                        <p class="text-muted small mb-0">{{ app()->getLocale() == 'bn' ? 'অর্ডার করা হয়েছে:' : 'Placed on:' }} {{ $order->created_at->format('d M, Y') }}</p>
                    </div>
                    <a href="{{ route('customer.orders.invoice', $order->order_number) }}" target="_blank" class="btn btn-primary btn-sm rounded-pill px-4 fw-bold shadow-sm">
                        <i class="bi bi-printer me-1"></i> {{ app()->getLocale() == 'bn' ? 'প্রিন্ট রশিদ' : 'Print Invoice' }}
                    </a>
                </div>

                <!-- Tracker Card -->
                <div class="admin-card p-5 mb-4">
                    @php
                        $statuses = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
                        $currentIndex = array_search($order->status, $statuses);
                        if ($currentIndex === false) $currentIndex = -1; // For cancelled or others
                        
                        $progressWidth = ($currentIndex >= 0) ? ($currentIndex / (count($statuses) - 1)) * 100 : 0;
                    @endphp

                    <div class="order-tracker">
                        @if($currentIndex >= 0)
                            <div class="tracker-line-fill" style="width: calc({{ $progressWidth }}% - 50px);"></div>
                        @endif

                        @foreach($statuses as $index => $status)
                            @php
                                $stepClass = '';
                                if ($index < $currentIndex) $stepClass = 'completed';
                                elseif ($index == $currentIndex) $stepClass = 'active';

                                $icon = match($status) {
                                    'pending' => 'bi-clock',
                                    'confirmed' => 'bi-check-circle',
                                    'processing' => 'bi-gear',
                                    'shipped' => 'bi-truck',
                                    'delivered' => 'bi-box-seam',
                                };

                                $label = match($status) {
                                    'pending' => (app()->getLocale() == 'bn' ? 'পেন্ডিং' : 'Pending'),
                                    'confirmed' => (app()->getLocale() == 'bn' ? 'নিশ্চিত' : 'Confirmed'),
                                    'processing' => (app()->getLocale() == 'bn' ? 'প্রসেসিং' : 'Processing'),
                                    'shipped' => (app()->getLocale() == 'bn' ? 'শিফট' : 'Shipped'),
                                    'delivered' => (app()->getLocale() == 'bn' ? 'ডেলিভারড' : 'Delivered'),
                                };
                            @endphp
                            <div class="tracker-step {{ $stepClass }}">
                                <div class="step-icon">
                                    <i class="bi {{ $icon }}"></i>
                                </div>
                                <div class="step-label">{{ $label }}</div>
                            </div>
                        @endforeach
                    </div>

                    @if($order->status == 'cancelled')
                        <div class="alert alert-danger text-center fw-bold">
                            <i class="bi bi-x-circle"></i> {{ app()->getLocale() == 'bn' ? 'এই অর্ডারটি বাতিল করা হয়েছে।' : 'This order has been cancelled.' }}
                        </div>
                    @endif
                </div>

                <div class="row g-4">
                    <!-- Items -->
                    <div class="col-md-8">
                        <div class="admin-card overflow-hidden">
                            <div class="card-header bg-white py-3 px-4 border-bottom">
                                <h6 class="mb-0 fw-bold">{{ app()->getLocale() == 'bn' ? 'অর্ডার আইটেম' : 'Order Items' }}</h6>
                            </div>
                            <div class="table-responsive">
                                <table class="table order-table mb-0">
                                    <thead>
                                        <tr>
                                            <th>{{ app()->getLocale() == 'bn' ? 'প্রোডাক্ট' : 'Product' }}</th>
                                            <th class="text-center">{{ app()->getLocale() == 'bn' ? 'পিক' : 'Qty' }}</th>
                                            <th class="text-end">{{ app()->getLocale() == 'bn' ? 'মূল্য' : 'Price' }}</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        @foreach($order->items as $item)
                                        <tr>
                                            <td>
                                                <div class="d-flex align-items-center gap-3">
                                                    <div class="rounded border p-1" style="width: 50px; height: 50px;">
                                                        @if($item->product && $item->product->image_url)
                                                            <img src="{{ $item->product->image_url }}" class="w-100 h-100 object-fit-contain">
                                                        @else
                                                            <i class="bi bi-image text-muted"></i>
                                                        @endif
                                                    </div>
                                                    <div>
                                                        <div class="fw-bold small">{{ $item->product_name }}</div>
                                                        <div class="text-muted" style="font-size: 0.7rem;">{{ $item->variant_name }}</div>
                                                    </div>
                                                </div>
                                            </td>
                                            <td class="text-center fw-bold">×{{ $item->quantity }}</td>
                                            <td class="text-end fw-bold">৳{{ number_format($item->total) }}</td>
                                        </tr>
                                        @endforeach
                                    </tbody>
                                </table>
                            </div>
                        </div>

                        <div class="row g-3 mt-1">
                            <div class="col-md-6">
                                <div class="order-summary-box h-100">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">{{ app()->getLocale() == 'bn' ? 'ডেলিভারি ঠিকানা' : 'Shipping Address' }}</h6>
                                    <p class="mb-1 fw-bold">{{ $order->customer_name }}</p>
                                    <p class="text-muted small mb-2">{{ $order->customer_address }}</p>
                                    <div class="fw-bold small"><i class="bi bi-telephone text-primary"></i> {{ $order->customer_phone }}</div>
                                </div>
                            </div>
                            <div class="col-md-6">
                                <div class="order-summary-box h-100">
                                    <h6 class="fw-bold mb-3 border-bottom pb-2">{{ app()->getLocale() == 'bn' ? 'পেমেন্ট তথ্য' : 'Payment Info' }}</h6>
                                    <p class="mb-1 small"><strong>{{ app()->getLocale() == 'bn' ? 'পদ্ধতি:' : 'Method:' }}</strong> {{ strtoupper($order->payment_method) }}</p>
                                    <p class="text-muted small mb-0">{{ $order->notes ?? (app()->getLocale() == 'bn' ? 'কোনো নোট নেই' : 'No notes provided') }}</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Summary -->
                    <div class="col-md-4">
                        <div class="order-summary-box shadow-sm">
                            <h6 class="fw-bold mb-4 border-bottom pb-2">{{ app()->getLocale() == 'bn' ? 'অর্ডার সামারি' : 'Order Summary' }}</h6>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">{{ app()->getLocale() == 'bn' ? 'সাবটোটাল' : 'Subtotal' }}</span>
                                <span class="fw-bold">৳{{ number_format($order->subtotal) }}</span>
                            </div>
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">{{ app()->getLocale() == 'bn' ? 'ডেলিভারি' : 'Delivery' }}</span>
                                <span class="fw-bold">৳{{ number_format($order->delivery_charge) }}</span>
                            </div>
                            @if($order->discount_amount > 0)
                            <div class="d-flex justify-content-between mb-2">
                                <span class="text-muted small">{{ app()->getLocale() == 'bn' ? 'ডিসকাউন্ট' : 'Discount' }}</span>
                                <span class="text-danger fw-bold">-৳{{ number_format($order->discount_amount) }}</span>
                            </div>
                            @endif
                            <hr>
                            <div class="d-flex justify-content-between mb-0">
                                <span class="fw-black text-primary">{{ app()->getLocale() == 'bn' ? 'সর্বমোট' : 'Total' }}</span>
                                <span class="fw-black text-primary fs-5">৳{{ number_format($order->total) }}</span>
                            </div>
                        </div>
                        
                        <div class="mt-4 text-center p-3 bg-white admin-card">
                             <p class="small text-muted mb-3">{{ app()->getLocale() == 'bn' ? 'অর্ডার সম্পর্কিত কোনো প্রশ্ন থাকলে যোগাযোগ করুন' : 'Need help with this order?' }}</p>
                             <a href="https://wa.me/8801700000000" class="btn btn-success btn-sm w-100 fw-bold">
                                 <i class="bi bi-whatsapp"></i> WhatsApp Support
                             </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
