@extends('admin.layouts.app')

@section('title', 'Couriers')
@section('page_title', 'Courier Setup')

@section('content')
<form action="{{ route('admin.settings.couriers.update') }}" method="POST">
    @csrf

    <div class="row g-4">
        <div class="col-lg-8">
            @foreach($couriers as $courier)
                @php
                    $key = $courier['key'];
                    $enabled = old("couriers.{$key}.enabled", $courier['enabled'] ? '1' : '0') == '1';
                @endphp

                <div class="card border-0 shadow-sm mb-4">
                    <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                        <div class="d-flex align-items-center gap-2">
                            <h5 class="mb-0 fw-bold text-dark">{{ $courier['label'] }}</h5>
                            @if($courier['is_default'])
                                <span class="badge bg-primary-subtle text-primary">Default</span>
                            @endif
                        </div>
                        <div class="d-flex align-items-center gap-3">
                            @if($courier['configured'])
                                <span class="badge bg-success-subtle text-success">
                                    <i class="bi bi-check-circle"></i> Ready
                                </span>
                            @else
                                <span class="badge bg-secondary-subtle text-secondary">
                                    <i class="bi bi-dash-circle"></i> Needs credentials
                                </span>
                            @endif

                            <div class="form-check form-switch mb-0">
                                {{-- The hidden 0 makes an unchecked switch post
                                     something, so turning a courier off is a
                                     value rather than a missing key. --}}
                                <input type="hidden" name="couriers[{{ $key }}][enabled]" value="0">
                                <input class="form-check-input" type="checkbox" role="switch"
                                       id="enable-{{ $key }}"
                                       name="couriers[{{ $key }}][enabled]" value="1"
                                       @checked($enabled)>
                                <label class="form-check-label small fw-bold" for="enable-{{ $key }}">Use</label>
                            </div>
                        </div>
                    </div>

                    <div class="card-body p-4">
                        <div class="row g-3">
                            @foreach($courier['fields'] as $field => $definition)
                                @php
                                    $name = "couriers[{$key}][{$field}]";
                                    $errorKey = "couriers.{$key}.{$field}";
                                    $type = $definition['type'] ?? 'text';
                                    $isSecret = $type === 'secret';
                                    $stored = $saved[$key][$field] ?? false;
                                @endphp
                                <div class="col-md-6">
                                    <label class="form-label fw-bold">
                                        {{ $definition['label'] }}
                                        @if($definition['required'] ?? true)<span class="text-danger">*</span>@endif
                                    </label>
                                    <input
                                        type="{{ $isSecret ? 'password' : 'text' }}"
                                        name="{{ $name }}"
                                        class="form-control @error($errorKey) is-invalid @enderror"
                                        value="{{ old($errorKey, $isSecret ? '' : ($values[$key][$field] ?? '')) }}"
                                        placeholder="{{ $isSecret && $stored ? '•••••••• (saved)' : ($definition['placeholder'] ?? '') }}"
                                        @if($isSecret) autocomplete="new-password" @endif
                                    >
                                    @error($errorKey)<div class="invalid-feedback">{{ $message }}</div>@enderror
                                    @if(! empty($definition['help']))
                                        <div class="form-text">{{ $definition['help'] }}</div>
                                    @endif
                                    @if($isSecret)
                                        <div class="form-text">Stored encrypted. Leave blank to keep the saved one.</div>
                                    @endif
                                </div>
                            @endforeach
                        </div>

                        <div class="mt-3 pt-3 border-top">
                            <div class="form-check">
                                <input class="form-check-input" type="radio" name="courier_default"
                                       id="default-{{ $key }}" value="{{ $key }}"
                                       @checked(old('courier_default', $default) === $key)>
                                <label class="form-check-label small" for="default-{{ $key }}">
                                    Reach for this one first when sending a parcel
                                </label>
                            </div>
                        </div>
                    </div>
                </div>
            @endforeach

            <button type="submit" class="btn btn-primary px-4 mb-4">
                <i class="bi bi-save"></i> Save courier settings
            </button>
        </div>

        <div class="col-lg-4">
            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-3 text-dark">How this works</h6>
                    <ol class="small text-muted ps-3 mb-0">
                        <li class="mb-2">
                            Paste the credentials from your merchant panel and switch the courier on.
                        </li>
                        <li class="mb-2">
                            On any order, <strong>Send to courier</strong> books the parcel and stores the
                            tracking number.
                        </li>
                        <li class="mb-2">
                            <strong>Check with courier</strong> asks where it is and moves the order status to
                            match — you stop typing statuses in by hand.
                        </li>
                        <li>
                            Once delivered, record what actually came in on the order's
                            <strong>settlement</strong> panel, so the accounts show the money net of the
                            courier's fee.
                        </li>
                    </ol>
                </div>
            </div>

            <div class="card border-0 shadow-sm mb-4">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 text-dark">Keep it in sync automatically</h6>
                    <p class="small text-muted mb-2">
                        Run this on a schedule and the orders list keeps itself current:
                    </p>
                    <pre class="bg-light border rounded p-2 small mb-2"><code>php artisan couriers:sync</code></pre>
                    <p class="small text-muted mb-0">
                        It only asks about parcels that are still in flight, and a courier being unreachable is
                        recorded rather than treated as a failure.
                    </p>
                </div>
            </div>

            <div class="card border-0 shadow-sm">
                <div class="card-body p-4">
                    <h6 class="fw-bold mb-2 text-dark">Before you go live</h6>
                    <p class="small text-muted mb-0">
                        Check each base URL and any ID fields against the API document on your own merchant
                        account. Providers in this market change hosts and rename fields between accounts, which
                        is why those are settings here rather than fixed in the code.
                    </p>
                </div>
            </div>
        </div>
    </div>
</form>
@endsection
