@extends('admin.layouts.app')

@section('title', 'Customer: ' . $customer->name)
@section('page_title', $customer->name)

@php
    $paidOrders = $customer->orders->where('status', '!=', 'cancelled');
@endphp

@section('content')
<div class="row g-4">
    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-dark">Customer Details</h5>
                <div class="d-flex flex-column gap-3">
                    <div>
                        <span class="text-muted small d-block">Name</span>
                        <span class="fw-bold">{{ $customer->name }}</span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Mobile</span>
                        @if($customer->mobile)
                            <a href="tel:{{ $customer->mobile }}" class="fw-bold text-decoration-none">{{ $customer->mobile }}</a>
                            <a href="https://wa.me/{{ preg_replace('/[^0-9]/', '', $customer->mobile) }}"
                               target="_blank" class="btn btn-sm btn-outline-success ms-2">
                                <i class="bi bi-whatsapp"></i>
                            </a>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </div>
                    <div>
                        <span class="text-muted small d-block">Email</span>
                        <span class="fw-bold">{{ $customer->email ?? '—' }}</span>
                    </div>
                    <div>
                        <span class="text-muted small d-block">Joined</span>
                        <span>{{ $customer->created_at->format('d M Y, h:i A') }}</span>
                    </div>
                </div>
            </div>
        </div>

        <div class="row g-3 mb-4">
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:.7rem;">Orders</div>
                        <h3 class="mb-0 fw-bold">{{ $customer->orders->count() }}</h3>
                    </div>
                </div>
            </div>
            <div class="col-6">
                <div class="card border-0 shadow-sm h-100">
                    <div class="card-body text-center p-3">
                        <div class="text-muted small text-uppercase fw-bold" style="font-size:.7rem;">Lifetime</div>
                        <h3 class="mb-0 fw-bold" style="color:#2d6a4f;">৳{{ number_format($paidOrders->sum('total')) }}</h3>
                    </div>
                </div>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h5 class="fw-bold mb-3 text-dark">Saved Addresses</h5>
                @forelse($customer->addresses as $address)
                    <div class="border rounded p-3 mb-2">
                        <div class="fw-bold">{{ $address->name }}</div>
                        <div class="small text-muted"><i class="bi bi-telephone"></i> {{ $address->phone }}</div>
                        <div class="small"><i class="bi bi-geo-alt"></i> {{ $address->address }}</div>
                        @if($address->is_default)
                            <span class="badge bg-primary-subtle text-primary mt-2">Default</span>
                        @endif
                    </div>
                @empty
                    <p class="text-muted small mb-0">No saved addresses.</p>
                @endforelse
            </div>
        </div>
    </div>

    <div class="col-lg-8">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">Order History</h5>
            </div>
            <div class="card-body p-0">
                @include('admin.customers._orders', ['orders' => $customer->orders, 'empty' => 'This customer has not placed an order while signed in.'])
            </div>
        </div>

        @if($guestOrders->isNotEmpty())
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3">
                <h5 class="mb-0 fw-bold text-dark">
                    Guest Orders <span class="text-muted small fw-normal">— same mobile number, placed without signing in</span>
                </h5>
            </div>
            <div class="card-body p-0">
                @include('admin.customers._orders', ['orders' => $guestOrders, 'empty' => ''])
            </div>
        </div>
        @endif
    </div>
</div>

<div class="mt-4">
    <a href="{{ route('admin.customers.index') }}" class="btn btn-light">
        <i class="bi bi-arrow-left"></i> Back to customers
    </a>
</div>
@endsection
