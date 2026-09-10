@extends('admin.layouts.app')
@section('page_title', 'Orders')

@php
    $flat = fn ($n) => '৳' . number_format((float) $n, 0);
    $pendingSettlement = request('settlement') === 'pending';
    $preorderFilter = request('preorder');
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

{{-- Orders the shop has taken money for but cannot ship until stock lands. --}}
@if($awaitingPreorder > 0 && ! $preorderFilter)
<div class="alert alert-warning d-flex align-items-center gap-3 border-0 shadow-sm">
    <i class="bi bi-clock-history fs-4"></i>
    <div class="flex-grow-1">
        <strong>{{ $awaitingPreorder }}</strong> order(s) are waiting on pre-ordered stock. They cannot be
        shipped until the goods arrive.
    </div>
    <a href="{{ route('admin.orders.index', ['preorder' => 'waiting']) }}"
       class="btn btn-sm btn-warning fw-bold text-nowrap">Show them</a>
</div>
@endif

<div class="admin-toolbar">
    <form action="{{ route('admin.orders.index') }}" method="GET" class="admin-toolbar__filters">
        @if($pendingSettlement)<input type="hidden" name="settlement" value="pending">@endif
        {{-- Sorting is a link, not a field, so it has to survive a filter submit. --}}
        @foreach(['sort', 'dir'] as $carry)
            @if(request()->filled($carry))<input type="hidden" name="{{ $carry }}" value="{{ request($carry) }}">@endif
        @endforeach

        <select name="status" class="form-select @if(request('status')) is-filtering @endif" onchange="this.form.submit()">
            <option value="">All Statuses</option>
            @foreach(\App\Models\Order::STATUSES as $key => [$label, $colour])
                <option value="{{ $key }}" @selected(request('status') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="source" class="form-select @if(request('source')) is-filtering @endif" onchange="this.form.submit()">
            <option value="">All Channels</option>
            @foreach(\App\Models\Order::SOURCES as $key => $label)
                <option value="{{ $key }}" @selected(request('source') === $key)>{{ $label }}</option>
            @endforeach
        </select>
        <select name="courier" class="form-select @if(request('courier')) is-filtering @endif" onchange="this.form.submit()">
            <option value="">All Couriers</option>
            @foreach($courierOptions as $key => $class)
                <option value="{{ $key }}" @selected(request('courier') === $key)>{{ $class::label() }}</option>
            @endforeach
        </select>

        <select name="preorder" class="form-select @if($preorderFilter) is-filtering @endif" onchange="this.form.submit()">
            <option value="">All Orders</option>
            <option value="waiting" @selected($preorderFilter === 'waiting')>Pre-order — waiting</option>
            <option value="all" @selected($preorderFilter === 'all')>Pre-order — any</option>
        </select>

        <div class="input-group admin-toolbar__search">
            <span class="input-group-text"><i class="bi bi-search"></i></span>
            <input type="search" name="search" class="form-control @if(request('search')) is-filtering @endif"
                   placeholder="Order, name, phone or tracking" value="{{ request('search') }}">
        </div>
    </form>

    <div class="admin-toolbar__actions">
        <span class="admin-toolbar__count">{{ number_format($orders->total()) }} order(s)</span>
        @if($pendingSettlement)
            <a href="{{ route('admin.orders.index') }}" class="btn btn-sm btn-outline-secondary">
                <i class="bi bi-x-lg"></i> Clear "awaiting settlement"
            </a>
        @endif
    </div>
</div>

<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table table-hover admin-table">
                <thead>
                    <tr>
                        @include('admin.partials.sort', ['key' => 'order_number', 'label' => 'Order #'])
                        @include('admin.partials.sort', ['key' => 'created_at', 'label' => 'Date'])
                        @include('admin.partials.sort', ['key' => 'customer_name', 'label' => 'Customer'])
                        @include('admin.partials.sort', ['key' => 'total', 'label' => 'Total', 'class' => 'text-end'])
                        @include('admin.partials.sort', ['key' => 'collected_amount', 'label' => 'Settled', 'class' => 'text-end'])
                        @include('admin.partials.sort', ['key' => 'courier', 'label' => 'Courier'])
                        @include('admin.partials.sort', ['key' => 'source', 'label' => 'Source'])
                        @include('admin.partials.sort', ['key' => 'status', 'label' => 'Status'])
                        <th class="text-end">Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($orders as $order)
                <tr>
                    <td>
                        <a href="{{ route('admin.orders.show', $order) }}" class="fw-bold text-decoration-none">
                            {{ $order->order_number }}
                        </a>
                        @if($order->has_preorder)
                            <br><span class="badge bg-warning text-dark" style="font-size:0.65rem;">
                                <i class="bi bi-clock-history"></i> Pre-order
                            </span>
                        @endif
                    </td>
                    <td class="text-nowrap small">{{ $order->created_at->format('d M Y, h:i A') }}</td>
                    <td>
                        {{ $order->customer_name }}
                        <br><small class="text-muted">{{ $order->customer_phone }}</small>
                    </td>
                    <td class="fw-bold text-nowrap text-end">
                        {{ $flat($order->total) }}
                        @if($order->discount_amount > 0)
                            <br><small class="text-danger fw-normal">(-{{ $flat($order->discount_amount) }})</small>
                        @endif
                    </td>
                    <td class="text-nowrap text-end">
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
                    <td class="text-nowrap text-end">
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
                    <td colspan="9" class="admin-table__empty">
                        <i class="bi bi-receipt"></i>
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
<div class="admin-pager">{{ $orders->links() }}</div>
@endsection
