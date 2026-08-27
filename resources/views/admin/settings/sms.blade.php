@extends('admin.layouts.app')

@section('title', 'SMS Settings')
@section('page_title', 'SMS Gateway Setup')

@php
    $driver = old('sms_driver', $sms['sms_driver'] ?? 'log');
@endphp

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">Gateway</h5>
                @if($isConfigured)
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i> Live</span>
                @else
                    <span class="badge bg-secondary-subtle text-secondary"><i class="bi bi-journal-text"></i> Log only</span>
                @endif
            </div>

            <div class="card-body p-4">
                <form action="{{ route('admin.settings.sms.update') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Provider *</label>
                            <select name="sms_driver" class="form-select @error('sms_driver') is-invalid @enderror" required>
                                <option value="log" @selected($driver === 'log')>Log only (nothing is sent)</option>
                                <option value="bulksmsbd" @selected($driver === 'bulksmsbd')>BulkSMSBD</option>
                            </select>
                            @error('sms_driver') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Keep this on <strong>Log only</strong> while testing — no credit is spent.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Sender ID</label>
                            <input type="text" name="sms_sender_id" class="form-control @error('sms_sender_id') is-invalid @enderror"
                                   value="{{ old('sms_sender_id', $sms['sms_sender_id'] ?? '') }}" placeholder="MangoHut">
                            @error('sms_sender_id') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">The approved masking name from your provider.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">API Key</label>
                            <input type="password" name="sms_api_key" class="form-control @error('sms_api_key') is-invalid @enderror"
                                   placeholder="{{ filled($sms['sms_api_key'] ?? null) ? '•••••••• (saved)' : 'Paste your API key' }}"
                                   autocomplete="new-password">
                            @error('sms_api_key') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Stored encrypted. Leave blank to keep the saved one.</div>
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">API Endpoint</label>
                            <input type="url" name="sms_endpoint" class="form-control @error('sms_endpoint') is-invalid @enderror"
                                   value="{{ old('sms_endpoint', $sms['sms_endpoint'] ?? '') }}"
                                   placeholder="{{ config('sms.drivers.bulksmsbd.endpoint') }}">
                            @error('sms_endpoint') <div class="invalid-feedback">{{ $message }}</div> @enderror
                            <div class="form-text">Leave blank for the default. Change it if your account document differs.</div>
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Save SMS Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">Send a Test SMS</h6>
                <form action="{{ route('admin.settings.sms.test') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <input type="text" name="test_number" class="form-control @error('test_number') is-invalid @enderror"
                               value="{{ old('test_number', auth()->user()->mobile) }}" placeholder="01712345678" required>
                        @error('test_number') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100">
                        <i class="bi bi-chat-dots"></i> Send Test
                    </button>
                </form>
                @unless($isConfigured)
                    <p class="text-muted small mb-0 mt-3">
                        With <strong>Log only</strong> selected the message goes to
                        <code>storage/logs</code> instead of a phone.
                    </p>
                @endunless
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">Number format</h6>
                <p class="text-muted small mb-2">
                    Numbers are converted to international form before sending, so any of these work:
                </p>
                <ul class="text-muted small mb-0 ps-3">
                    <li><code>01712345678</code></li>
                    <li><code>+8801712345678</code></li>
                    <li><code>8801712345678</code></li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
