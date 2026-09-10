@extends('admin.layouts.app')
@section('page_title', 'Orders')

@php
    $flat = fn ($n) => '৳' . number_format((float) $n, 0);
    $pendingSettlement = request('settlement') === 'pending';
@endphp

@section('content')

{{-- The queue that costs money: delivered, but nobody has said what came in. --}}
@if($awaitingSettlement > 0 && ! $pendingSettlement)
<div class="alert alert-warning d-flex align-items-center gap-3 border-0 shadow-sm">
    <i class="bi bi-cash-coin fs-4"></i>
    <div class="flex-grow-1">
        <strong>{{ $awaitingSettlement }}</strong> delivered order(s) have no settlement recorded, so the
        accounts are still counting them at face value.
    </div>
    <a href="{{ route('admin.orders.index', ['settlement' => 'pending']) }}"
       class="btn btn-sm btn-warning fw-bold text-nowrap">Show them</a>
</div>
@endif

<div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-2">
    <form action="{{ route('admin.orders.index') }}" method="GET" class="d-flex gap-2 flex-wrap">
        @if($pendingSettlement)<input type="hidden" name="settlement" value="pending">@endif
        <select name="status" class="form-select" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            @foreach(\App\Models\Order::STATUSES as $key => [$label, $colour])
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="source" class="form-select" onchange="this.form.submit()">
            <option value="">All Channels</option>
            @foreach(\App\Models\Order::SOURCES as $key => $label)
                <option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="courier" class="form-select" onchange="this.form.submit()">
            <option value="">All Couriers</option>
            @foreach($courierOptions as $key => $class)
                <option value="{{ $key }}" @selected(request('courier') === $key)>{{ $class::label() }}</option>
            @endforeach
        </select>
        <input type="text" name="search" class="form-control" placeholder="Order, name, phone or tracking"
               value="{{ request('search') }}">
        <button class="btn btn-outline-secondary"><i class="bi bi-search"></i></button>
    </form>

    @if($pendingSettlement)
        <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
            <i class="bi bi-x-lg"></i> Clear "awaiting settlement"
        </a>
    @endif
</div>

<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover mb-0">
                <thead style="background: var(--gray-100);">
                    <tr>
                        <th style="padding: 14px 16px;">Order #</th>
                        <th>Date</th>
                        <th>Customer</th>
                        <th>Total</th>
                        <th>Settled</th>
                        <th>Courier</th>
                        <th>Source</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                <tr>
                    <td style="padding: 16px;">
                        <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-decoration-none">
                            {{ $order->order_number }}
                        </a>
                    </td>
                    <td class="text-nowrap small">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                    <td>
                        {{ $order->customer_name }}
                        <br><small class="text-muted">{{ $order->customer_phone }}</small>
                    </td>
                    <td class="fw-bold text-nowrap">
                        {{ $flat($order->total) }}
                        @if($order->discount_amount > 0)
                            <br><small class="text-danger">(-{{ $flat($order->discount_amount) }})</small>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($order->isSettled())
                            <span class="text-success fw-bold">{{ $flat($order->collected_amount) }}</span>
                            @if($order->courier_charge > 0)
                                <br><small class="text-muted">courier {{ $flat($order->courier_charge) }}</small>
                            @endif
                        @elseif($order->status === 'delivered')
                            <span class="badge bg-warning text-dark">Not recorded</span>
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        @if($order->hasCourier())
                            <span class="badge bg-light text-dark border">{{ $couriers->label($order->courier) }}</span>
                            @if($order->courier_status)
                                <br><small class="text-muted">{{ Str::limit($order->courier_status, 20) }}</small>
                            @endif
                        @else
                            <span class="text-muted">—</span>
                        @endif
                    </td>
                    <td>
                        @if($order->isCounterSale())
                            <span class="badge" style="background-color: #6f42c1;">
                                {{ \App\Models\Order::SOURCES[$order->source] ?? 'POS' }}
                            </span>
                        @elseif($order->source === 'landing')
                            <span class="badge" style="background-color: #d6336c;">Landing</span>
                            @if($order->landingPage)
                                <br><small class="text-muted">{{ Str::limit($order->landingPage->internal_name, 22) }}</small>
                            @endif
                        @else
                            <span class="badge" style="background-color: #0d6efd;">Web</span>
                        @endif
                    </td>
                    <td>{!! $order->status_badge !!}</td>
                    <td class="text-nowrap">
                        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-sm btn-outline-primary">
                            <i class="bi bi-eye"></i>
                        </a>
                        @can('orders.edit')
                        <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-sm btn-outline-secondary"
                           title="Edit this order">
                            <i class="bi bi-pencil"></i>
                        </a>
                        @endcan
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center py-4">
                        No orders found.
                        @if($pendingSettlement)
                            <br><span class="text-success small">Every delivered order has a settlement recorded.</span>
                        @endif
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="mt-3">{{ $orders->links() }}</div>
@endsection
