@extends('admin.layouts.app')
@section('page_title', 'Edit Order ' . $order->order_number)

@php
    use App\Support\PaymentAccounts;

    // old() wins so a rejected save comes back with what was typed, not with
    // what is still in the database.
    $lines = old('items')
        ? collect(old('items'))->map(fn ($line) => [
            'variant_id' => (int) ($line['variant_id'] ?? 0),
            'product_name' => $line['product_name'] ?? 'Product #' . ($line['variant_id'] ?? '?'),
            'variant_name' => $line['variant_name'] ?? '',
            'quantity' => (int) ($line['quantity'] ?? 1),
            'unit_price' => (float) ($line['unit_price'] ?? 0),
            'stock' => null,
        ])->values()->all()
        : $lines->all();
@endphp

@section('content')

<form action="{{ route('admin.orders.update', $order) }}" method="POST">
    @csrf
    @method('PUT')

    <div class="d-flex flex-wrap justify-content-between align-items-center gap-2 mb-4">
        <div>
            <h4 class="fw-bold mb-0" style="color: var(--primary-dark);">Edit {{ $order->order_number }}</h4>
            <span class="text-muted small">
                Placed {{ $order->created_at->format('d M Y, h:i A') }} · {!! $order->status_badge !!}
            </span>
        </div>
        <div class="d-flex gap-2">
            <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
            <button class="btn btn-primary px-4"><i class="bi bi-save"></i> Save changes</button>
        </div>
    </div>

    @if($errors->any())
        <div class="alert alert-danger">
            <strong>That could not be saved.</strong>
            <ul class="mb-0 mt-1 small">
                @foreach($errors->all() as $message)<li>{{ $message }}</li>@endforeach
            </ul>
        </div>
    @endif

    <div class="row g-4">
        <div class="col-lg-8">
            <div class="card admin-card mb-4">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Items &amp; totals</h5>
                    <small class="text-muted">
                        Changing a line puts its stock back and takes the new quantity, in one go.
                    </small>
                </div>
                <div class="card-body px-4">
                    <div
                        data-vue="OrderEditor"
                        data-props="{{ json_encode([
                            'lines' => $lines,
                            'searchUrl' => route('admin.orders.products'),
                            'deliveryCharge' => (float) old('delivery_charge', $order->delivery_charge),
                            'discountAmount' => (float) old('discount_amount', $order->discount_amount),
                            'discountType' => old('discount_type', 'flat'),
                            'errors' => $errors->messages(),
                        ], JSON_UNESCAPED_UNICODE) }}"
                    ></div>
                </div>
            </div>

            <div class="card admin-card">
                <div class="card-header bg-white border-0 pt-4 px-4">
                    <h5 class="fw-bold mb-0" style="color: var(--primary-dark);">Customer &amp; delivery</h5>
                </div>
                <div class="card-body px-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Name *</label>
                            <input type="text" name="customer_name"
                                   class="form-control @error('customer_name') is-invalid @enderror"
                                   value="{{ old('customer_name', $order->customer_name) }}" required>
                            @error('customer_name')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Phone *</label>
                            <input type="text" name="customer_phone"
                                   class="form-control @error('customer_phone') is-invalid @enderror"
                                   value="{{ old('customer_phone', $order->customer_phone) }}" required>
                            @error('customer_phone')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Address *</label>
                            <textarea name="customer_address" rows="2"
                                      class="form-control @error('customer_address') is-invalid @enderror"
                                      required>{{ old('customer_address', $order->customer_address) }}</textarea>
                            @error('customer_address')<div class="invalid-feedback">{{ $message }}</div>@enderror
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Area</label>
                            <input type="text" name="customer_area" class="form-control"
                                   value="{{ old('customer_area', $order->customer_area) }}"
                                   placeholder="inside_dhaka">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Pickup point</label>
                            <input type="text" name="pickup_point" class="form-control"
                                   value="{{ old('pickup_point', $order->pickup_point) }}"
                                   placeholder="Leave blank for home delivery">
                            <div class="form-text">Filling this in makes it a store pickup.</div>
                        </div>
                        <div class="col-12">
                            <label class="form-label fw-bold">Notes</label>
                            <textarea name="notes" rows="2" class="form-control"
                                      placeholder="Anything the packer or rider needs to know">{{ old('notes', $order->notes) }}</textarea>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="col-lg-4">
            <div class="card admin-card mb-4 p-4">
                <h5 class="fw-bold mb-3" style="color: var(--primary-dark);">Status &amp; payment</h5>

                <label class="form-label fw-bold">Status *</label>
                <select name="status" class="form-select mb-3 @error('status') is-invalid @enderror">
                    @foreach(\App\Models\Order::STATUSES as $key => [$label, $colour])
                        <option value="{{ $key }}" @selected(old('status', $order->status) === $key)>{{ $label }}</option>
                    @endforeach
                </select>
                @error('status')<div class="text-danger small mb-2">{{ $message }}</div>@enderror
                <div class="form-text mb-3">
                    Moving this to a status the customer is told about will send them a message, exactly as the
                    dropdown on the order page does.
                </div>

                <label class="form-label fw-bold">Payment method *</label>
                <select name="payment_method" class="form-select @error('payment_method') is-invalid @enderror">
                    @php $method = old('payment_method', $order->payment_method); @endphp
                    @foreach(PaymentAccounts::HEADS as $key => $head)
                        <option value="{{ $key }}" @selected($method === $key)>{{ $head[0] }} — {{ $head[1] }}</option>
                    @endforeach
                </select>
                @error('payment_method')<div class="text-danger small">{{ $message }}</div>@enderror
            </div>

            {{-- Things this form deliberately does not touch, said out loud so
                 nobody goes looking for them among the fields above. --}}
            <div class="card admin-card p-4">
                <h6 class="fw-bold mb-3 text-muted text-uppercase small">Not editable here</h6>
                <ul class="small text-muted mb-0 ps-3">
                    <li>
                        <strong>Settlement</strong> — what came in and what the courier kept is recorded on the
                        <a href="{{ route('admin.orders.show', $order) }}#settlement">order page</a>.
                    </li>
                    <li class="mt-2">
                        <strong>Courier</strong> — booking and tracking live on the order page too.
                    </li>
                    @if($order->coupon_code)
                    <li class="mt-2">
                        <strong>Coupon {{ $order->coupon_code }}</strong> stays on the record as history. Change the
                        discount above if the amount needs to differ.
                    </li>
                    @endif
                    <li class="mt-2">
                        <strong>Order number</strong> and where the order came from
                        ({{ \App\Models\Order::SOURCES[$order->source] ?? $order->source }}).
                    </li>
                </ul>
            </div>
        </div>
    </div>

    <div class="d-flex justify-content-end gap-2 mt-4">
        <a href="{{ route('admin.orders.show', $order) }}" class="btn btn-outline-secondary">Cancel</a>
        <button class="btn btn-primary px-4"><i class="bi bi-save"></i> Save changes</button>
    </div>
</form>
@endsection
