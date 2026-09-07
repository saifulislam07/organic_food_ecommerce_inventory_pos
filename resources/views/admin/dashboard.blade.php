@extends('admin.layouts.app')
@section('page_title', 'Dashboard')

@php
    $taka = fn ($amount) => '৳'.number_format((float) $amount);
    $signed = fn ($amount) => ($amount < 0 ? '−' : '').'৳'.number_format(abs((float) $amount));
@endphp

@section('content')

{{-- ------------------------------------------------------------- trading --}}
<div class="row g-4 mb-4">
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-success">
            <i class="bi bi-graph-up-arrow stat-icon"></i>
            <div class="stat-value">{{ $taka($sales['today']['revenue']) }}</div>
            <div class="stat-label">Today · {{ $sales['today']['orders'] }} orders</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-primary">
            <i class="bi bi-calendar3 stat-icon"></i>
            <div class="stat-value">{{ $taka($sales['month']['revenue']) }}</div>
            <div class="stat-label">{{ $monthLabel }} · {{ $sales['month']['orders'] }} orders</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-info">
            <i class="bi bi-receipt stat-icon"></i>
            <div class="stat-value">{{ $sales['all']['orders'] }}</div>
            <div class="stat-label">Orders all time</div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="admin-stat-card bg-gradient-warning">
            <i class="bi bi-currency-exchange stat-icon"></i>
            <div class="stat-value">{{ $taka($sales['all']['revenue']) }}</div>
            <div class="stat-label">Revenue all time</div>
        </div>
    </div>
</div>

{{--
    What wants doing. Every tile is a link, because a count nobody can act on
    from here is a number that gets ignored after the first week.
--}}
<div class="row g-3 mb-4">
    @php
        $todo = [
            ['count' => $attention['pending_orders'], 'label' => 'Pending orders', 'icon' => 'bi-hourglass-split',
             'tone' => 'warning', 'route' => 'admin.orders.index', 'can' => 'orders.view'],
            ['count' => $attention['confirmed_orders'], 'label' => 'Confirmed, not shipped', 'icon' => 'bi-box-arrow-right',
             'tone' => 'info', 'route' => 'admin.orders.index', 'can' => 'orders.view'],
            ['count' => $attention['low_stock'], 'label' => 'Running low', 'icon' => 'bi-exclamation-triangle',
             'tone' => 'warning', 'route' => 'admin.inventory.index', 'can' => 'inventory.view'],
            ['count' => $attention['out_of_stock'], 'label' => 'Out of stock', 'icon' => 'bi-x-octagon',
             'tone' => 'danger', 'route' => 'admin.inventory.index', 'can' => 'inventory.view'],
        ];
    @endphp

    @foreach($todo as $item)
        @continue(! auth()->user()->can($item['can']))
        <div class="col-xl-3 col-md-6">
            <a href="{{ route($item['route']) }}" class="text-decoration-none">
                <div class="card border-0 shadow-sm bg-white p-3 d-flex flex-row align-items-center gap-3 h-100">
                    <div class="bg-{{ $item['tone'] }}-subtle text-{{ $item['tone'] }} p-3 rounded-circle"
                         style="font-size: 1.25rem; line-height: 1;">
                        <i class="bi {{ $item['icon'] }}"></i>
                    </div>
                    <div>
                        <h4 class="mb-0 fw-bold {{ $item['count'] ? 'text-'.$item['tone'] : 'text-muted' }}">
                            {{ $item['count'] }}
                        </h4>
                        <small class="text-muted">{{ $item['label'] }}</small>
                    </div>
                </div>
            </a>
        </div>
    @endforeach
</div>

{{-- --------------------------------------------------------------- money --}}
@if($money)
<div class="card admin-card mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Money — {{ $monthLabel }}</h5>
            <small class="text-muted">Profit is trade only; investor capital sits in the accounts below it.</small>
        </div>
        <a href="{{ route('admin.reports.profitLoss') }}" class="btn btn-sm btn-outline-secondary">
            Full report <i class="bi bi-arrow-right"></i>
        </a>
    </div>
    <div class="card-body px-4">
        <div class="row g-3 mb-4">
            @php
                $figures = [
                    ['label' => 'Revenue', 'value' => $money['revenue'], 'tone' => 'dark'],
                    ['label' => 'Cost of goods', 'value' => -$money['cost_of_goods'], 'tone' => 'muted'],
                    ['label' => 'Expenses', 'value' => -$money['expenses'], 'tone' => 'muted'],
                    ['label' => 'Damage', 'value' => -$money['damage'], 'tone' => 'muted'],
                    ['label' => 'Net profit', 'value' => $money['net_profit'],
                     'tone' => $money['net_profit'] < 0 ? 'danger' : 'success'],
                ];
            @endphp
            @foreach($figures as $figure)
                <div class="col-6 col-lg">
                    <div class="border rounded p-3 h-100 {{ $figure['label'] === 'Net profit' ? 'bg-light' : '' }}">
                        <small class="text-muted text-uppercase fw-bold" style="font-size: .68rem;">
                            {{ $figure['label'] }}
                        </small>
                        <div class="h5 fw-bold mb-0 text-{{ $figure['tone'] }}">
                            {{ $signed($figure['value']) }}
                        </div>
                    </div>
                </div>
            @endforeach
        </div>

        <div class="row g-4">
            <div class="col-lg-7">
                <h6 class="fw-bold text-muted text-uppercase small mb-3">Where the money sits</h6>
                <div class="table-responsive">
                    <table class="table table-sm align-middle mb-0">
                        <thead class="text-muted small text-uppercase">
                            <tr>
                                <th>Account</th>
                                <th class="text-end">In</th>
                                <th class="text-end">Out</th>
                                <th class="text-end">Net</th>
                            </tr>
                        </thead>
                        <tbody>
                            @foreach($money['accounts'] as $head)
                                {{-- A head nothing went through this month is noise. --}}
                                @continue(! $head['in'] && ! $head['out'])
                                <tr>
                                    <td>
                                        <span class="badge bg-{{ $head['colour'] }}-subtle text-{{ $head['colour'] }}">
                                            <i class="bi {{ $head['icon'] }}"></i> {{ $head['label'] }}
                                        </span>
                                    </td>
                                    <td class="text-end text-success">{{ $taka($head['in']) }}</td>
                                    <td class="text-end text-danger">{{ $taka($head['out']) }}</td>
                                    <td class="text-end fw-bold {{ $head['net'] < 0 ? 'text-danger' : '' }}">
                                        {{ $signed($head['net']) }}
                                    </td>
                                </tr>
                            @endforeach
                            <tr class="table-light">
                                <th>Net cash movement</th>
                                <th colspan="3" class="text-end">{{ $signed($money['cash_net']) }}</th>
                            </tr>
                        </tbody>
                    </table>
                </div>
                @if($money['invested'] || $money['withdrawn'])
                    <p class="small text-muted mb-0 mt-2">
                        Includes {{ $taka($money['invested']) }} of investor capital in and
                        {{ $taka($money['withdrawn']) }} out — neither counts as profit or loss.
                    </p>
                @endif
            </div>

            <div class="col-lg-5">
                <h6 class="fw-bold text-muted text-uppercase small mb-3">Also this month</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Stock bought</span>
                    <strong>{{ $taka($money['purchases']) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Gross margin</span>
                    <strong>{{ number_format($money['gross_margin'], 1) }}%</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted">Delivery collected</span>
                    <strong>{{ $taka($money['delivery']) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2">
                    <span class="text-muted">Discounts given</span>
                    <strong class="text-danger">{{ $taka($money['discount']) }}</strong>
                </div>
            </div>
        </div>
    </div>
</div>
@endif

{{-- ------------------------------------------------- assets and capital --}}
<div class="row g-4 mb-4">
    <div class="col-lg-{{ $capital ? 6 : 12 }}">
        <div class="card admin-card h-100">
            <div class="card-body px-4 py-4">
                <h6 class="fw-bold text-muted text-uppercase small mb-3">What the shop holds</h6>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><i class="bi bi-safe"></i> Stock at cost</span>
                    <strong>{{ $taka($standing['stock_cost']) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><i class="bi bi-tags"></i> Stock at retail</span>
                    <strong>{{ $taka($standing['stock_retail']) }}</strong>
                </div>
                <div class="d-flex justify-content-between py-2 border-bottom">
                    <span class="text-muted"><i class="bi bi-cash-stack"></i> Expenses all time</span>
                    <strong class="text-danger">{{ $taka($standing['expenses_all_time']) }}</strong>
                </div>
                <div class="row g-2 mt-2 text-center">
                    @foreach([
                        ['Products', $catalogue['products'], 'bi-box-seam'],
                        ['Categories', $catalogue['categories'], 'bi-diagram-3'],
                        ['Suppliers', $catalogue['suppliers'], 'bi-truck'],
                        ['Customers', $catalogue['customers'], 'bi-people'],
                    ] as [$label, $count, $icon])
                        <div class="col-3">
                            <div class="border rounded py-2">
                                <i class="bi {{ $icon }} text-muted"></i>
                                <div class="fw-bold">{{ $count }}</div>
                                <small class="text-muted" style="font-size: .7rem;">{{ $label }}</small>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>

    @if($capital)
    <div class="col-lg-6">
        <div class="card admin-card h-100">
            <div class="card-body px-4 py-4">
                <div class="d-flex justify-content-between align-items-start mb-3">
                    <h6 class="fw-bold text-muted text-uppercase small mb-0">Investor capital</h6>
                    <a href="{{ route('admin.investors.index') }}" class="small text-decoration-none">All investors</a>
                </div>

                <div class="row g-2 text-center mb-3">
                    <div class="col-4">
                        <div class="border rounded py-2">
                            <div class="fw-bold text-success">{{ $taka($capital['invested']) }}</div>
                            <small class="text-muted" style="font-size: .7rem;">Put in</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded py-2">
                            <div class="fw-bold text-danger">{{ $taka($capital['withdrawn']) }}</div>
                            <small class="text-muted" style="font-size: .7rem;">Taken out</small>
                        </div>
                    </div>
                    <div class="col-4">
                        <div class="border rounded py-2 bg-light">
                            <div class="fw-bold" style="color: var(--primary);">{{ $taka($capital['in_business']) }}</div>
                            <small class="text-muted" style="font-size: .7rem;">In business</small>
                        </div>
                    </div>
                </div>

                @forelse($capital['investors'] as $investor)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <a href="{{ route('admin.investors.show', $investor) }}" class="text-decoration-none">
                            {{ $investor->name }}
                            @unless($investor->is_active)
                                <span class="badge bg-secondary ms-1" style="font-size: .6rem;">Inactive</span>
                            @endunless
                        </a>
                        {{-- Negative is profit drawn, not an error. --}}
                        <strong class="{{ $investor->balance() < 0 ? 'text-warning' : '' }}">
                            {{ $signed($investor->balance()) }}
                        </strong>
                    </div>
                @empty
                    <p class="text-muted small mb-0 py-3">
                        No investors recorded yet.
                        <a href="{{ route('admin.investors.create') }}">Add one</a>.
                    </p>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ------------------------------------------------- pipeline and channels --}}
@if($pipeline)
<div class="row g-4 mb-4">
    <div class="col-lg-7">
        <div class="card admin-card h-100">
            <div class="card-body px-4 py-4">
                <h6 class="fw-bold text-muted text-uppercase small mb-3">Orders by status</h6>
                <div class="row g-2 text-center">
                    @foreach($pipeline as $status => $count)
                        <div class="col-4 col-md-2">
                            <a href="{{ route('admin.orders.index', ['status' => $status]) }}"
                               class="d-block border rounded py-2 text-decoration-none">
                                <div class="fw-bold text-dark">{{ $count }}</div>
                                <small class="text-muted text-capitalize" style="font-size: .7rem;">{{ $status }}</small>
                            </a>
                        </div>
                    @endforeach
                </div>
            </div>
        </div>
    </div>
    <div class="col-lg-5">
        <div class="card admin-card h-100">
            <div class="card-body px-4 py-4">
                <h6 class="fw-bold text-muted text-uppercase small mb-3">Where {{ $monthLabel }}'s orders came from</h6>
                @foreach($channels as $channel)
                    <div class="d-flex justify-content-between py-2 border-bottom">
                        <span class="text-muted">{{ $channel['label'] }}</span>
                        <span>
                            <span class="text-muted small me-2">{{ $channel['orders'] }} orders</span>
                            <strong>{{ $taka($channel['revenue']) }}</strong>
                        </span>
                    </div>
                @endforeach
            </div>
        </div>
    </div>
</div>
@endif

{{-- ---------------------------------------------------- stock and selling --}}
<div class="row g-4 mb-4">
    @if($lowStockItems)
    <div class="col-lg-6">
        <div class="card admin-card h-100">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Running out</h5>
                <a href="{{ route('admin.inventory.index') }}" class="small text-decoration-none">Inventory</a>
            </div>
            <div class="card-body px-4">
                @forelse($lowStockItems as $variant)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>
                            {{ $variant->product?->name ?? 'Product' }}
                            <small class="text-muted">— {{ $variant->name }}</small>
                        </span>
                        <span class="badge bg-{{ $variant->stock <= 0 ? 'danger' : 'warning' }}">
                            {{ $variant->stock }} left
                        </span>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">Nothing is running low.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif

    @if($topProducts)
    <div class="col-lg-6">
        <div class="card admin-card h-100">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Best sellers — {{ $monthLabel }}</h5>
            </div>
            <div class="card-body px-4">
                @forelse($topProducts as $product)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>{{ $product->name }}</span>
                        <span>
                            <span class="text-muted small me-2">{{ $product->units }} sold</span>
                            <strong>{{ $taka($product->revenue) }}</strong>
                        </span>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">Nothing sold yet this month.</p>
                @endforelse
            </div>
        </div>
    </div>
    @endif
</div>

{{-- ----------------------------------------------------------- campaigns --}}
@if($campaigns && $campaigns['pages'])
<div class="card admin-card mb-4">
    <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
        <div>
            <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Campaign pages</h5>
            <small class="text-muted">
                {{ $campaigns['live'] }} live of {{ $campaigns['pages'] }} ·
                {{ number_format($campaigns['views']) }} views
            </small>
        </div>
        <a href="{{ route('admin.landing-pages.index') }}" class="small text-decoration-none">All pages</a>
    </div>
    <div class="card-body px-4">
        <div class="table-responsive">
            <table class="table table-sm align-middle mb-0">
                <thead class="text-muted small text-uppercase">
                    <tr>
                        <th>Page</th>
                        <th class="text-end">Views</th>
                        <th class="text-end">Orders</th>
                        <th class="text-end">Conversion</th>
                        <th class="text-end">Revenue</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($campaigns['top'] as $page)
                        <tr>
                            <td>
                                <a href="{{ route('admin.landing-pages.edit', $page) }}" class="text-decoration-none">
                                    {{ $page->internal_name }}
                                </a>
                                @unless($page->is_active)
                                    <span class="badge bg-secondary ms-1" style="font-size: .6rem;">Off</span>
                                @endunless
                            </td>
                            <td class="text-end">{{ number_format($page->views) }}</td>
                            <td class="text-end">{{ $page->orders_count }}</td>
                            <td class="text-end">
                                {{-- Views can be zero while orders are not: an old
                                     link still converts after a counter reset. --}}
                                {{ $page->views > 0 ? number_format($page->orders_count / $page->views * 100, 1).'%' : '—' }}
                            </td>
                            <td class="text-end fw-bold">{{ $taka($page->revenue) }}</td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    </div>
</div>
@endif

{{-- ------------------------------------------------- recent and shortcuts --}}
<div class="row g-4">
    <div class="col-lg-8">
        @if($recentOrders)
        <div class="card admin-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Recent Orders</h5>
                <a href="{{ route('admin.orders.index') }}" class="small text-decoration-none">All orders</a>
            </div>
            <div class="card-body px-4">
                @if($recentOrders->count())
                <div class="table-responsive">
                    <table class="table">
                        <thead><tr><th>Order #</th><th>Customer</th><th>Total</th><th>Status</th><th>Date</th></tr></thead>
                        <tbody>
                        @foreach($recentOrders as $order)
                        <tr>
                            <td><a href="{{ route('admin.orders.show', $order) }}">{{ $order->order_number }}</a></td>
                            <td>{{ $order->customer_name }}</td>
                            <td>{{ $taka($order->total) }}</td>
                            <td>{!! $order->status_badge !!}</td>
                            <td>{{ $order->created_at->format('d M Y') }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                    </table>
                </div>
                @else
                <p class="text-muted text-center py-4">No orders yet.</p>
                @endif
            </div>
        </div>
        @endif

        @if($recentExpenses)
        <div class="card admin-card">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Recent Expenses</h5>
                <a href="{{ route('admin.expenses.index') }}" class="small text-decoration-none">All expenses</a>
            </div>
            <div class="card-body px-4">
                @forelse($recentExpenses as $expense)
                    <div class="d-flex justify-content-between align-items-center py-2 border-bottom">
                        <span>
                            {{ $expense->title }}
                            <small class="text-muted d-block">
                                {{ $expense->category }} · {{ $expense->expense_date->format('d M Y') }}
                            </small>
                        </span>
                        <strong class="text-danger">{{ $taka($expense->amount) }}</strong>
                    </div>
                @empty
                    <p class="text-muted text-center py-4 mb-0">Nothing recorded yet.</p>
                @endforelse
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        <div class="card admin-card p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Quick Actions</h5>
            <div class="d-grid gap-2">
                @php
                    // Gated one by one: a button that only ever 403s is worse
                    // than no button.
                    $actions = [
                        ['admin.pos.index', 'pos.view', 'btn-brand', 'bi-calculator', 'POS system'],
                        ['admin.orders.index', 'orders.view', 'btn-outline-primary', 'bi-receipt', 'Orders'],
                        ['admin.purchases.create', 'purchases.create', 'btn-outline-success', 'bi-cart-plus', 'New Purchase'],
                        ['admin.expenses.create', 'expenses.create', 'btn-outline-danger', 'bi-cash-stack', 'Add Expense'],
                        ['admin.investments.create', 'investments.create', 'btn-outline-success', 'bi-piggy-bank', 'Record Investment'],
                        ['admin.withdrawals.create', 'withdrawals.create', 'btn-outline-warning', 'bi-box-arrow-up', 'Record Withdrawal'],
                        ['admin.adjustments.create', 'adjustments.create', 'btn-outline-warning', 'bi-tools', 'Adjust Stock'],
                        ['admin.products.create', 'products.create', 'btn-outline-dark', 'bi-plus-circle', 'Add Product'],
                        ['admin.landing-pages.create', 'landing-pages.create', 'btn-outline-info', 'bi-megaphone', 'New Landing Page'],
                    ];
                @endphp
                @foreach($actions as [$route, $permission, $class, $icon, $label])
                    @can($permission)
                        <a href="{{ route($route) }}" class="btn {{ $class }}">
                            <i class="bi {{ $icon }}"></i> {{ $label }}
                        </a>
                    @endcan
                @endforeach
            </div>
        </div>
    </div>
</div>
@endsection
