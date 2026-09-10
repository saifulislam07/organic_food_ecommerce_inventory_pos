@extends('admin.layouts.app')
@section('page_title', 'Order ' . $order->order_number)

@php
    use App\Support\PaymentAccounts;

    $money = fn ($n) => '৳' . number_format((float) $n, 2);
    $flat = fn ($n) => '৳' . number_format((float) $n, 0);

    // The order's life, so the header can show at a glance how far along it is
    // rather than making someone read a dropdown to find out.
    $steps = ['pending', 'confirmed', 'processing', 'shipped', 'delivered'];
    $reached = $order->status === 'cancelled' ? -1 : array_search($order->status, $steps, true);
@endphp

@section('content')

{{-- What needs doing, before anything else on the page. --}}
@if($order->status === 'delivered' && ! $order->isSettled())
<div class="alert alert-warning d-flex align-items-center gap-3 border-0 shadow-sm">
    <i class="bi bi-cash-coin fs-4"></i>
    <div class="flex-grow-1">
        <strong>This delivery has not been settled.</strong>
        Until you record what came in and what the courier kept, the accounts count it at
        its full {{ $flat($order->total) }}.
    </div>
    <a href="#settlement" class="btn btn-sm btn-warning fw-bold text-nowrap">Record it</a>
</div>
@endif

{{-- Header --}}
<div class="card admin-card mb-4">
    <div class="card-body p-4">
        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
            <div>
                <div class="d-flex align-items-center gap-2 flex-wrap mb-1">
                    <h4 class="fw-bold mb-0" style="color: var(--primary-dark);">{{ $order->order_number }}</h4>
                    {!! $order->status_badge !!}
                    @if($order->has_preorder)
                        <span class="badge bg-warning text-dark">
                            <i class="bi bi-clock-history"></i> Pre-order
                        </span>
                    @endif
                    @php
                        // The counter can log a sale as phone, Facebook or WhatsApp,
                        // so the badge follows the list rather than three fixed cases.
                        $sourceColour = match (true) {
                            $order->isCounterSale() => '#6f42c1',
                            $order->source === 'landing' => '#d6336c',
                            default => '#0d6efd',
                        };
                    @endphp
                    <span class="badge" style="background-color: {{ $sourceColour }};">
                        {{ \App\Models\Order::SOURCES[$order->source] ?? ucfirst((string) $order->source) }}
                    </span>
                </div>
                <div class="text-muted small">
                    Placed {{ $order->created_at->format('d M Y, h:i A') }}
                    @if($order->delivered_at)
                        · delivered {{ $order->delivered_at->format('d M Y, h:i A') }}
                    @endif
                </div>
            </div>
            <div class="d-flex gap-2 flex-wrap">
                @can('orders.edit')
                <a href="{{ route('admin.orders.edit', $order) }}" class="btn btn-primary btn-sm">
                    <i class="bi bi-pencil-square"></i> Edit Order
                </a>
                @endcan
                <a href="{{ route('admin.orders.invoice', $order) }}" class="btn btn-outline-success btn-sm">
                    <i class="bi bi-printer"></i> Invoice
                </a>
                <a href="{{ \App\Support\Whatsapp::url($order->customer_phone) }}" target="_blank"
                   class="btn btn-outline-success btn-sm">
                    <i class="bi bi-whatsapp"></i> WhatsApp
                </a>
            </div>
        </div>

        {{-- Progress --}}
        @if($order->status === 'cancelled')
            <div class="alert alert-danger mt-3 mb-0 py-2 small">
                <i class="bi bi-x-circle"></i> This order was cancelled.
            </div>
        @else
            <div class="d-flex align-items-center gap-1 mt-4 flex-wrap">
                @foreach($steps as $i => $step)
                    @php $done = $reached !== false && $i <= $reached; @endphp
                    <div class="d-flex align-items-center gap-1">
                        <span class="badge rounded-pill {{ $done ? 'bg-success' : 'bg-light text-muted border' }}"
                              style="min-width: 88px;">
                            @if($done)<i class="bi bi-check-lg"></i>@endif
                            {{ \App\Models\Order::STATUSES[$step][0] }}
                        </span>
                        @if(! $loop->last)
                            <span class="flex-grow-1" style="height:2px; width:18px; background: {{ $done && $i < $reached ? 'var(--bs-success)' : '#dee2e6' }};"></span>
                        @endif
                    </div>
                @endforeach
            </div>
        @endif
    </div>
</div>

@if($order->has_preorder)
{{-- The terms as they were shown at checkout, not as they read today: the
     admin can edit the note at any time, and this is the record of what this
     customer actually agreed to. --}}
<div class="card admin-card mb-4 border-warning-subtle">
    <div class="card-body p-4">
        <h5 class="fw-bold mb-1" style="color: var(--primary-dark);">
            <i class="bi bi-clock-history text-warning"></i> Pre-order
        </h5>
        <p class="text-muted small mb-3">
            এই অর্ডারে এমন পণ্য আছে যা অর্ডারের সময় স্টকে ছিল না। ওই লাইনগুলোর স্টক কাটা হয়নি —
            মাল আসার পর পাঠাতে হবে।
            @if($order->preorder_accepted_at)
                কাস্টমার শর্তে রাজি হয়েছে {{ $order->preorder_accepted_at->format('d M Y, h:i A') }}-এ।
            @endif
        </p>

        @if($order->preorder_terms)
            <div class="preorder-terms">{{ $order->preorder_terms }}</div>
        @else
            <p class="text-muted small fst-italic mb-0">No terms were recorded with this order.</p>
        @endif
    </div>
</div>
@endif

<div class="row g-4">
    <div class="col-lg-8">
        {{-- Items --}}
        <div class="card admin-card mb-4">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">
                    Items <span class="text-muted fw-normal small">({{ $order->items->count() }})</span>
                </h5>
            </div>
            <div class="card-body px-4">
                <div class="table-responsive">
                    <table class="table align-middle">
                        <thead>
                            <tr>
                                <th>Product</th>
                                <th>Price</th>
                                <th>Qty</th>
                                <th class="text-end">Subtotal</th>
                            </tr>
                        </thead>
                        <tbody>
                        @foreach($order->items as $item)
                        <tr>
                            <td>
                                <strong class="text-dark">{{ $item->product_name }}</strong><br>
                                <small class="text-muted">{{ $item->variant_name }}</small>
                                @if($item->is_preorder)
                                    {{-- No stock was taken for this line; it is drawn
                                         down when the goods arrive. --}}
                                    <br><span class="badge bg-warning text-dark" style="font-size:0.65rem;">
                                        <i class="bi bi-clock-history"></i> Pre-order — stock not deducted
                                    </span>
                                @endif
                            </td>
                            <td>{{ $flat($item->unit_price) }}</td>
                            <td>{{ $item->quantity }}</td>
                            <td class="text-end fw-bold">{{ $flat($item->total) }}</td>
                        </tr>
                        @endforeach
                        </tbody>
                        <tfoot class="border-top">
                            <tr>
                                <td colspan="3" class="text-end">Subtotal</td>
                                <td class="text-end">{{ $flat($order->subtotal) }}</td>
                            </tr>
                            @if($order->discount_amount > 0)
                            <tr>
                                <td colspan="3" class="text-end text-danger">
                                    Discount
                                    @if($order->coupon_code)<span class="badge bg-warning text-dark ms-1">{{ $order->coupon_code }}</span>@endif
                                </td>
                                <td class="text-end text-danger">-{{ $flat($order->discount_amount) }}</td>
                            </tr>
                            @endif
                            <tr>
                                <td colspan="3" class="text-end">Delivery Charge</td>
                                <td class="text-end">{{ $flat($order->delivery_charge) }}</td>
                            </tr>
                            <tr style="font-size: 1.1rem;">
                                <td colspan="3" class="text-end fw-bold">Total</td>
                                <td class="text-end fw-bold" style="color: var(--primary);">{{ $flat($order->total) }}</td>
                            </tr>
                            @if($order->paid_amount !== null)
                            <tr class="text-muted small">
                                <td colspan="3" class="text-end">Paid</td>
                                <td class="text-end">{{ $flat($order->paid_amount) }}</td>
                            </tr>
                            <tr class="text-muted small">
                                <td colspan="3" class="text-end">Change given</td>
                                <td class="text-end">{{ $flat($order->change_due) }}</td>
                            </tr>
                            @endif
                            @if($order->amount_due > 0 && ! $order->isSettled())
                            <tr class="small">
                                <td colspan="3" class="text-end fw-bold">Still to collect</td>
                                <td class="text-end fw-bold text-danger">{{ $flat($order->amount_due) }}</td>
                            </tr>
                            @endif
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>

        {{-- Delivery settlement: what the order was really worth. --}}
        <div class="card admin-card mb-4" id="settlement">
            <div class="card-header bg-white border-0 pt-4 px-4 d-flex justify-content-between align-items-center">
                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">
                    <i class="bi bi-cash-coin"></i> Delivery Settlement
                </h5>
                @if($order->isSettled())
                    <span class="badge bg-success"><i class="bi bi-check-circle"></i> Recorded</span>
                @else
                    <span class="badge bg-light text-muted border">Not recorded</span>
                @endif
            </div>
            <div class="card-body px-4">
                @if($order->isSettled())
                    <div class="row g-3 mb-4">
                        <div class="col-6 col-md-3">
                            <div class="text-muted small text-uppercase fw-bold">Money in</div>
                            <div class="h5 fw-bold text-success mb-0">{{ $money($order->collected_amount) }}</div>
                            <div class="small">
                                <span class="badge bg-{{ PaymentAccounts::colour($order->collected_in) }}-subtle text-{{ PaymentAccounts::colour($order->collected_in) }}">
                                    <i class="bi {{ PaymentAccounts::icon($order->collected_in) }}"></i>
                                    {{ PaymentAccounts::label($order->collected_in) }}
                                </span>
                            </div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small text-uppercase fw-bold">Courier kept</div>
                            <div class="h5 fw-bold text-danger mb-0">{{ $money($order->courier_charge) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small text-uppercase fw-bold">Order total</div>
                            <div class="h5 fw-bold text-dark mb-0">{{ $money($order->total) }}</div>
                        </div>
                        <div class="col-6 col-md-3">
                            <div class="text-muted small text-uppercase fw-bold">Difference</div>
                            @php $variance = $order->settlement_variance; @endphp
                            <div class="h5 fw-bold mb-0 {{ abs($variance) < 0.01 ? 'text-muted' : ($variance < 0 ? 'text-danger' : 'text-warning') }}">
                                {{ ($variance > 0 ? '+' : '') . $money($variance) }}
                            </div>
                            <div class="small text-muted">against expected</div>
                        </div>
                    </div>
                    @if($order->settlement_note)
                        <p class="text-muted small mb-3"><i class="bi bi-sticky"></i> {{ $order->settlement_note }}</p>
                    @endif
                @else
                    <p class="text-muted small">
                        The courier keeps its fee out of what it collects, so the money that reaches you is less than
                        the order total. Record both figures and the accounts follow — until then this order is counted
                        at its face value of {{ $flat($order->total) }}.
                    </p>
                @endif

                @can('orders.edit')
                <form action="{{ route('admin.orders.settle', $order) }}" method="POST" class="row g-3 align-items-end">
                    @csrf
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Courier charge</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" name="courier_charge"
                                   class="form-control @error('courier_charge') is-invalid @enderror"
                                   value="{{ old('courier_charge', $order->courier_charge > 0 ? $order->courier_charge : $order->delivery_charge) }}">
                        </div>
                        @error('courier_charge')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Cash received</label>
                        <div class="input-group">
                            <span class="input-group-text">৳</span>
                            <input type="number" step="0.01" min="0" name="collected_amount"
                                   class="form-control @error('collected_amount') is-invalid @enderror"
                                   value="{{ old('collected_amount', $order->collected_amount ?? $order->expected_collection) }}">
                        </div>
                        @error('collected_amount')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Into which account</label>
                        <select name="collected_in" class="form-select @error('collected_in') is-invalid @enderror">
                            @php $chosen = old('collected_in', $order->collected_in ?? PaymentAccounts::DEFAULT_POS); @endphp
                            @foreach(PaymentAccounts::HEADS as $key => $head)
                                <option value="{{ $key }}" @selected($chosen === $key)>{{ $head[0] }} — {{ $head[1] }}</option>
                            @endforeach
                        </select>
                        @error('collected_in')<div class="text-danger small">{{ $message }}</div>@enderror
                    </div>
                    <div class="col-6 col-md-3">
                        <label class="form-label small fw-bold text-muted mb-1">Note <span class="fw-normal">(optional)</span></label>
                        <input type="text" name="settlement_note" class="form-control"
                               value="{{ old('settlement_note', $order->settlement_note) }}"
                               placeholder="Partial return, damaged…">
                    </div>
                    <div class="col-12">
                        <button class="btn btn-success">
                            <i class="bi bi-check2-circle"></i>
                            {{ $order->isSettled() ? 'Update settlement' : 'Record settlement & mark delivered' }}
                        </button>
                        <span class="text-muted small ms-2">
                            Expected: {{ $flat($order->expected_collection) }}
                            ({{ $flat($order->amount_due) }} due − courier charge)
                        </span>
                    </div>
                </form>
                @endcan
            </div>
        </div>

        {{-- Order Notes --}}
        @if($order->notes)
        <div class="card admin-card">
            <div class="card-header bg-white border-0 pt-4 px-4">
                <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Order Notes</h5>
            </div>
            <div class="card-body px-4">
                <p class="text-muted mb-0">{{ $order->notes }}</p>
            </div>
        </div>
        @endif
    </div>

    <div class="col-lg-4">
        {{-- Status --}}
        <div class="card admin-card mb-4 p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Order Status</h5>
            <div
                data-vue="OrderStatusControl"
                data-props="{{ json_encode([
                    'current' => $order->status,
                    'updateUrl' => route('admin.orders.updateStatus', $order),
                    'updatedAt' => $order->updated_at->format('d M Y, h:i A'),
                    'settled' => $order->isSettled(),
                ], JSON_UNESCAPED_UNICODE) }}"
            ></div>
        </div>

        {{-- Courier --}}
        <div class="card admin-card mb-4 p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">
                <i class="bi bi-truck"></i> Courier
            </h5>

            @if($order->hasCourier())
                <div class="d-flex flex-column gap-2 mb-3">
                    <div>
                        <span class="text-muted small d-block">Carried by</span>
                        <span class="fw-bold">{{ $couriers->label($order->courier) }}</span>
                    </div>
                    @if($order->courier_tracking_code || $order->courier_consignment_id)
                    <div>
                        <span class="text-muted small d-block">Tracking</span>
                        <code class="user-select-all">{{ $order->courier_tracking_code ?: $order->courier_consignment_id }}</code>
                    </div>
                    @endif
                    <div>
                        <span class="text-muted small d-block">Courier says</span>
                        @if($order->courier_status)
                            <span class="badge bg-info-subtle text-info">{{ $order->courier_status }}</span>
                        @else
                            <span class="text-muted small">Nothing yet</span>
                        @endif
                        @if($order->courier_status_note)
                            <div class="small text-muted mt-1">{{ $order->courier_status_note }}</div>
                        @endif
                    </div>
                    @if($order->courier_synced_at)
                    <div class="small text-muted">
                        Last checked {{ $order->courier_synced_at->diffForHumans() }}
                    </div>
                    @endif
                </div>

                @can('orders.edit')
                <form action="{{ route('admin.orders.syncCourier', $order) }}" method="POST">
                    @csrf
                    <button class="btn btn-outline-primary btn-sm w-100">
                        <i class="bi bi-arrow-repeat"></i> Check with courier
                    </button>
                </form>
                @endcan
            @elseif($usableCouriers === [])
                <p class="text-muted small mb-2">No courier is set up yet.</p>
                @can('settings.edit')
                    <a href="{{ route('admin.settings.couriers.edit') }}" class="btn btn-outline-secondary btn-sm w-100">
                        <i class="bi bi-gear"></i> Set one up
                    </a>
                @endcan
            @else
                @if($order->courier_status_note)
                    {{-- A booking that was refused leaves nothing on the order
                         but its reason, so the reason is what gets shown. --}}
                    <div class="alert alert-warning small py-2">
                        <i class="bi bi-exclamation-triangle"></i> Last attempt: {{ $order->courier_status_note }}
                    </div>
                @endif

                @can('orders.edit')
                <form action="{{ route('admin.orders.sendToCourier', $order) }}" method="POST">
                    @csrf
                    <label class="form-label small fw-bold text-muted mb-1">Send with</label>
                    @php $chosenCourier = old('courier'); @endphp
                    <select name="courier" class="form-select form-select-sm mb-2">
                        @foreach($usableCouriers as $courier)
                            <option value="{{ $courier['key'] }}"
                                @selected($chosenCourier ? $chosenCourier === $courier['key'] : $courier['is_default'])>
                                {{ $courier['label'] }}{{ $courier['is_default'] ? ' (default)' : '' }}
                            </option>
                        @endforeach
                    </select>

                    {{-- Only a manual courier needs these typed in; an API issues
                         its own. Shown always rather than hidden behind JS, so a
                         consignment number from a phone call is never unrecordable. --}}
                    <input type="text" name="consignment_id" class="form-control form-control-sm mb-2"
                           value="{{ old('consignment_id', $order->courier_consignment_id) }}"
                           placeholder="Consignment no. (manual couriers)">

                    <div class="alert alert-light border small py-2 mb-2">
                        Courier collects <strong>{{ $flat($order->amount_due) }}</strong> from the customer.
                    </div>

                    <button class="btn btn-primary btn-sm w-100">
                        <i class="bi bi-box-seam"></i> Send to courier
                    </button>
                </form>
                @endcan
            @endif

            @error('courier')<div class="text-danger small mt-2">{{ $message }}</div>@enderror
        </div>

        {{-- Customer --}}
        <div class="card admin-card mb-4 p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Customer</h5>
            <div class="d-flex flex-column gap-2">
                <div>
                    <span class="text-muted small d-block">Full Name</span>
                    <span class="fw-bold">{{ $order->customer_name }}</span>
                    @if($order->user)
                        @can('customers.view')
                            <a href="{{ route('admin.customers.show', $order->user) }}" class="small ms-1">(account)</a>
                        @endcan
                    @endif
                </div>
                <div>
                    <span class="text-muted small d-block">Phone Number</span>
                    <a href="tel:{{ $order->customer_phone }}" class="fw-bold text-decoration-none">
                        {{ $order->customer_phone }}
                    </a>
                </div>
                <div>
                    <span class="text-muted small d-block">Delivery Area</span>
                    <span class="badge bg-light text-dark">{{ ucfirst(str_replace('_', ' ', $order->customer_area ?? 'N/A')) }}</span>
                </div>

                @if($order->landingPage || $order->utm_campaign || $order->utm_source)
                <div>
                    <span class="text-muted small d-block">Campaign</span>
                    @if($order->landingPage)
                        <a href="{{ route('admin.landing-pages.edit', $order->landingPage) }}" class="fw-bold text-decoration-none">
                            {{ $order->landingPage->internal_name }}
                        </a>
                    @endif
                    <div class="small text-muted">
                        @if($order->utm_campaign)
                            <div><i class="bi bi-megaphone"></i> {{ $order->utm_campaign }}</div>
                        @endif
                        @if($order->utm_source)
                            <div>{{ $order->utm_source }}{{ $order->utm_medium ? ' / '.$order->utm_medium : '' }}</div>
                        @endif
                    </div>
                </div>
                @endif

                <div>
                    <span class="text-muted small d-block">Delivery Type</span>
                    @if($order->pickup_point)
                        <span class="badge bg-success">Store Pickup</span>
                    @else
                        <span class="badge bg-secondary">Home Delivery</span>
                    @endif
                </div>
                @if($order->pickup_point)
                <div>
                    <span class="text-muted small d-block">Pickup Point</span>
                    <span class="fw-bold text-success"><i class="bi bi-geo-alt"></i> {{ $order->pickup_point }}</span>
                </div>
                @endif
                <div>
                    <span class="text-muted small d-block">Full Address</span>
                    <span class="text-muted" style="font-size: 0.9rem;">{{ $order->customer_address }}</span>
                </div>
            </div>
        </div>

        {{-- Payment --}}
        <div class="card admin-card p-4">
            <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Payment</h5>
            <div class="d-flex justify-content-between align-items-center mb-2">
                <span class="text-muted">Method</span>
                <span class="badge bg-{{ PaymentAccounts::colour($order->payment_method) }}-subtle text-{{ PaymentAccounts::colour($order->payment_method) }}">
                    <i class="bi {{ PaymentAccounts::icon($order->payment_method) }}"></i>
                    {{ PaymentAccounts::label($order->payment_method) }}
                </span>
            </div>
            <div class="d-flex justify-content-between">
                <span class="text-muted">Outstanding</span>
                <span class="fw-bold {{ $order->amount_due > 0 ? 'text-danger' : 'text-success' }}">
                    {{ $flat($order->amount_due) }}
                </span>
            </div>
        </div>
    </div>
</div>
@endsection
