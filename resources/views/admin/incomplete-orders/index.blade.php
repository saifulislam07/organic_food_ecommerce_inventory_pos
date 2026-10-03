@extends('admin.layouts.app')
@section('page_title', 'Incomplete Orders')

@section('content')
@php
    $openCount = $counts->except('converted')->sum();
    $tabs = ['open' => ['To Follow Up', $openCount]]
        + collect(\App\Models\IncompleteOrder::STATUSES)->map(fn ($s, $key) => [$s[0], $counts[$key] ?? 0])->all()
        + ['all' => ['All', $counts->sum()]];
@endphp

<div class="alert alert-light border small mb-3">
    <i class="bi bi-info-circle me-1"></i>
    Customers who typed a valid phone number at checkout or on a landing page but never placed the order.
    Call them, note what stopped them, and use <strong>Convert</strong> when they agree to order.
    Leads that place the order themselves are marked <em>Converted</em> automatically.
</div>

<div class="admin-toolbar">
    <ul class="nav nav-pills gap-1 flex-wrap">
        @foreach($tabs as $key => [$label, $count])
            <li class="nav-item">
                <a class="nav-link {{ $status === $key ? 'active' : '' }}"
                   href="{{ route('admin.incomplete-orders.index', array_filter(['status' => $key, 'source' => request('source')])) }}">
                    {{ $label }}
                    @if($count)<span class="badge {{ $key === 'new' || $key === 'open' ? 'bg-danger' : 'bg-secondary' }} ms-1">{{ $count }}</span>@endif
                </a>
            </li>
        @endforeach
    </ul>
</div>

<div class="admin-toolbar">
    <form action="{{ route('admin.incomplete-orders.index') }}" method="GET" class="admin-toolbar__filters">
        <input type="hidden" name="status" value="{{ $status }}">
        @foreach(['sort', 'dir', 'search'] as $carry)
            @if(request()->filled($carry))<input type="hidden" name="{{ $carry }}" value="{{ request($carry) }}">@endif
        @endforeach
        <select name="source" class="form-select @if(request('source')) is-filtering @endif" onchange="this.form.submit()">
            <option value="">All Sources</option>
            <option value="website" @selected(request('source') === 'website')>Website checkout</option>
            <option value="landing" @selected(request('source') === 'landing')>Landing page</option>
        </select>
        <input type="date" name="from" value="{{ request('from') }}" class="form-control @if(request('from')) is-filtering @endif" onchange="this.form.submit()" title="From">
        <input type="date" name="to" value="{{ request('to') }}" class="form-control @if(request('to')) is-filtering @endif" onchange="this.form.submit()" title="To">
    </form>
    @include('admin.partials.search', ['route' => route('admin.incomplete-orders.index'), 'placeholder' => 'Name, phone or address'])
</div>

@can('incomplete-orders.delete')
<form id="bulk-incomplete-orders" method="POST" action="{{ route('admin.incomplete-orders.bulkDestroy') }}"
      data-bulk data-bulk-noun="leads">
    @csrf
    @method('DELETE')
    @include('admin.partials.bulk-bar')
</form>
@endcan
<div class="card admin-card">
    <div class="card-body p-0">
        <div class="table-responsive">
            <table class="table admin-table align-middle">
                <thead>
                    <tr>
                        @can('incomplete-orders.delete')<th style="width:38px;" class="ps-4"><input type="checkbox" class="form-check-input" data-bulk-all form="bulk-incomplete-orders"></th>@endcan
                        @include('admin.partials.sort', ['key' => 'customer_name', 'label' => 'Customer', 'first' => 'asc'])
                        <th>Phone</th>
                        <th>Source</th>
                        <th>Products</th>
                        @include('admin.partials.sort', ['key' => 'total', 'label' => 'Total'])
                        @include('admin.partials.sort', ['key' => 'updated_at', 'label' => 'Last Active'])
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody>
                @forelse($leads as $lead)
                @php
                    $items = collect($lead->items ?? []);
                    $waNumber = '88'.$lead->customer_phone;
                @endphp
                <tr class="{{ $lead->status === 'new' ? 'fw-semibold' : '' }}">
                    @can('incomplete-orders.delete')<td class="ps-4"><input type="checkbox" class="form-check-input" form="bulk-incomplete-orders" name="ids[]" value="{{ $lead->id }}"></td>@endcan
                    <td style="padding: 8px 16px;">
                        {{ $lead->customer_name ?: '—' }}
                        @if($lead->customer_address)
                            <div class="small text-muted fw-normal text-truncate" style="max-width:220px;" title="{{ $lead->customer_address }}">{{ $lead->customer_address }}</div>
                        @endif
                    </td>
                    <td class="text-nowrap">
                        <a href="tel:{{ $lead->customer_phone }}" class="btn btn-sm btn-success" title="Call">
                            <i class="bi bi-telephone"></i> {{ $lead->customer_phone }}
                        </a>
                        <a href="https://wa.me/{{ $waNumber }}" target="_blank" rel="noopener" class="btn btn-sm btn-outline-success" title="WhatsApp">
                            <i class="bi bi-whatsapp"></i>
                        </a>
                    </td>
                    <td class="small fw-normal">
                        @if($lead->source === 'landing')
                            <span class="badge bg-light text-dark border">Landing</span>
                            <div class="text-muted">{{ $lead->landingPage->internal_name ?? 'deleted page' }}</div>
                        @else
                            <span class="badge bg-light text-dark border">Website</span>
                        @endif
                    </td>
                    <td class="small fw-normal" style="max-width:260px;">
                        @foreach($items->take(2) as $item)
                            <div class="text-truncate">{{ $item['product_name'] }}@if(! empty($item['variant_name'])) ({{ $item['variant_name'] }})@endif × {{ $item['quantity'] }}</div>
                        @endforeach
                        @if($items->count() > 2)
                            <div class="text-muted">+{{ $items->count() - 2 }} more</div>
                        @endif
                    </td>
                    <td class="text-nowrap">৳{{ number_format($lead->total, 0) }}</td>
                    <td class="small text-muted fw-normal text-nowrap" title="{{ $lead->updated_at }}">{{ $lead->updated_at->diffForHumans() }}</td>
                    <td>
                        <span class="badge bg-{{ $lead->status_colour }} fw-normal">{{ $lead->status_label }}</span>
                        @if($lead->order)
                            <div class="small"><a href="{{ route('admin.orders.show', $lead->order_id) }}">{{ $lead->order->order_number }}</a></div>
                        @endif
                    </td>
                    <td>
                        <div class="d-flex gap-1">
                            <button type="button" class="btn btn-sm btn-outline-primary" data-bs-toggle="collapse"
                                    data-bs-target="#lead-{{ $lead->id }}" title="Details & call note">
                                <i class="bi bi-chat-left-text"></i>
                            </button>
                            @can('incomplete-orders.edit')
                                @unless($lead->isConverted())
                                <form action="{{ route('admin.incomplete-orders.convert', $lead) }}" method="POST"
                                      data-confirm="Create an order from this checkout? Stock will be deducted.">
                                    @csrf
                                    <button class="btn btn-sm btn-outline-success" title="Convert to order"><i class="bi bi-bag-check"></i></button>
                                </form>
                                @endunless
                            @endcan
                            @can('incomplete-orders.delete')
                            <form action="{{ route('admin.incomplete-orders.destroy', $lead) }}" method="POST" data-confirm="Delete this lead?">
                                @csrf @method('DELETE')
                                <button class="btn btn-sm btn-outline-danger"><i class="bi bi-trash"></i></button>
                            </form>
                            @endcan
                        </div>
                    </td>
                </tr>
                <tr class="collapse" id="lead-{{ $lead->id }}">
                    <td colspan="9" class="bg-light fw-normal">
                        <div class="row g-3 p-2">
                            <div class="col-md-6">
                                <table class="table table-sm mb-2 bg-white">
                                    <thead><tr><th>Product</th><th class="text-end">Qty</th><th class="text-end">Price</th><th class="text-end">Total</th></tr></thead>
                                    <tbody>
                                    @foreach($items as $item)
                                        <tr>
                                            <td>{{ $item['product_name'] }}@if(! empty($item['variant_name'])) <span class="text-muted">({{ $item['variant_name'] }})</span>@endif
                                                @if(! empty($item['is_preorder']))<span class="badge bg-warning text-dark ms-1">Pre-order</span>@endif</td>
                                            <td class="text-end">{{ $item['quantity'] }}</td>
                                            <td class="text-end">৳{{ number_format($item['price'], 0) }}</td>
                                            <td class="text-end">৳{{ number_format($item['subtotal'], 0) }}</td>
                                        </tr>
                                    @endforeach
                                    </tbody>
                                </table>
                                <div class="small">
                                    Subtotal ৳{{ number_format($lead->subtotal, 0) }}
                                    @if($lead->discount_amount > 0) · Discount −৳{{ number_format($lead->discount_amount, 0) }}@if($lead->coupon_code) ({{ $lead->coupon_code }})@endif @endif
                                    · Delivery ৳{{ number_format($lead->delivery_charge, 0) }}
                                    · <strong>Total ৳{{ number_format($lead->total, 0) }}</strong>
                                </div>
                                <div class="small text-muted mt-2">
                                    @if($lead->customer_email)<div><i class="bi bi-envelope"></i> {{ $lead->customer_email }}</div>@endif
                                    <div><i class="bi bi-geo-alt"></i>
                                        {{ $lead->delivery_type === 'pickup' ? 'Pickup: '.$lead->pickup_point : ($lead->customer_address ?: 'No address yet') }}
                                        @if($lead->customer_area) ({{ $lead->customer_area === 'dhaka_outside' ? 'Outside Dhaka' : 'Inside Dhaka' }})@endif
                                    </div>
                                    @if($lead->notes)<div><i class="bi bi-sticky"></i> {{ $lead->notes }}</div>@endif
                                    <div><i class="bi bi-clock"></i> Started {{ $lead->created_at->format('d M Y, h:i A') }}</div>
                                    @if($lead->handler)<div><i class="bi bi-person-check"></i> Last handled by {{ $lead->handler->name }}@if($lead->called_at), {{ $lead->called_at->diffForHumans() }}@endif</div>@endif
                                </div>
                            </div>
                            <div class="col-md-6">
                                @can('incomplete-orders.edit')
                                    @if($lead->isConverted())
                                        <div class="small text-muted mb-2">Converted — no further follow-up needed.</div>
                                        @if($lead->admin_note)<div class="small" style="white-space:pre-wrap;">{{ $lead->admin_note }}</div>@endif
                                    @else
                                    <form action="{{ route('admin.incomplete-orders.update', $lead) }}" method="POST">
                                        @csrf @method('PUT')
                                        <label class="form-label small fw-semibold">Call result</label>
                                        <select name="status" class="form-select form-select-sm mb-2">
                                            @foreach(\App\Models\IncompleteOrder::MANUAL_STATUSES as $key)
                                                <option value="{{ $key }}" @selected($lead->status === $key)>{{ \App\Models\IncompleteOrder::STATUSES[$key][0] }}</option>
                                            @endforeach
                                        </select>
                                        <label class="form-label small fw-semibold">What did the customer say?</label>
                                        <textarea name="admin_note" rows="3" class="form-control form-control-sm mb-2"
                                                  placeholder="e.g. delivery charge too high, payment confusion, will order tomorrow">{{ $lead->admin_note }}</textarea>
                                        <button class="btn btn-sm btn-primary"><i class="bi bi-save"></i> Save</button>
                                    </form>
                                    @endif
                                @else
                                    @if($lead->admin_note)<div class="small" style="white-space:pre-wrap;">{{ $lead->admin_note }}</div>@endif
                                @endcan
                            </div>
                        </div>
                    </td>
                </tr>
                @empty
                <tr>
                    <td colspan="9" class="text-center text-muted py-5">
                        <i class="bi bi-cart-x d-block mb-2" style="font-size:2rem;"></i>
                        No incomplete orders here.
                    </td>
                </tr>
                @endforelse
                </tbody>
            </table>
        </div>
    </div>
</div>
<div class="admin-pager">{{ $leads->links() }}</div>
@endsection
