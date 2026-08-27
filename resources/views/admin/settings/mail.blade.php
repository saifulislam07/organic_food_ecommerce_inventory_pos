@extends('admin.layouts.app')

@section('title', 'Mail Settings')
@section('page_title', 'Email / SMTP Setup')

@section('content')
<div class="row g-4">
    <div class="col-lg-8">
        <div class="card border-0 shadow-sm">
            <div class="card-header bg-white py-3 d-flex justify-content-between align-items-center">
                <h5 class="mb-0 fw-bold text-dark">SMTP Server</h5>
                @if($isConfigured)
                    <span class="badge bg-success-subtle text-success"><i class="bi bi-check-circle"></i> Configured</span>
                @else
                    <span class="badge bg-warning-subtle text-warning"><i class="bi bi-exclamation-triangle"></i> Not configured</span>
                @endif
            </div>

            <div class="card-body p-4">
                <form action="{{ route('admin.settings.mail.update') }}" method="POST">
                    @csrf

                    <div class="row g-3">
                        <div class="col-md-8">
                            <label class="form-label fw-bold">SMTP Host *</label>
                            <input type="text" name="mail_host" class="form-control @error('mail_host') is-invalid @enderror"
                                   value="{{ old('mail_host', $mail['mail_host'] ?? '') }}"
                                   placeholder="smtp.gmail.com" required>
                            @error('mail_host') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Port *</label>
                            <input type="number" name="mail_port" class="form-control @error('mail_port') is-invalid @enderror"
                                   value="{{ old('mail_port', $mail['mail_port'] ?? 587) }}" required>
                            @error('mail_port') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Username</label>
                            <input type="text" name="mail_username" class="form-control"
                                   value="{{ old('mail_username', $mail['mail_username'] ?? '') }}"
                                   placeholder="you@gmail.com" autocomplete="off">
                        </div>

                        <div class="col-md-6">
                            <label class="form-label fw-bold">Password</label>
                            <input type="password" name="mail_password" class="form-control"
                                   placeholder="{{ filled($mail['mail_password'] ?? null) ? '•••••••• (saved)' : 'App password' }}"
                                   autocomplete="new-password">
                            <div class="form-text">
                                Stored encrypted. Leave blank to keep the saved one.
                            </div>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">Encryption</label>
                            @php $enc = old('mail_encryption', $mail['mail_encryption'] ?? 'tls'); @endphp
                            <select name="mail_encryption" class="form-select">
                                <option value="tls" @selected($enc === 'tls')>TLS (port 587)</option>
                                <option value="ssl" @selected($enc === 'ssl')>SSL (port 465)</option>
                                <option value="none" @selected($enc === 'none')>None</option>
                            </select>
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">From Address *</label>
                            <input type="email" name="mail_from_address" class="form-control @error('mail_from_address') is-invalid @enderror"
                                   value="{{ old('mail_from_address', $mail['mail_from_address'] ?? '') }}"
                                   placeholder="orders@mangohut.com.bd" required>
                            @error('mail_from_address') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>

                        <div class="col-md-4">
                            <label class="form-label fw-bold">From Name *</label>
                            <input type="text" name="mail_from_name" class="form-control @error('mail_from_name') is-invalid @enderror"
                                   value="{{ old('mail_from_name', $mail['mail_from_name'] ?? 'Mango Hut') }}" required>
                            @error('mail_from_name') <div class="invalid-feedback">{{ $message }}</div> @enderror
                        </div>
                    </div>

                    <div class="mt-4 pt-3 border-top">
                        <button type="submit" class="btn btn-primary px-4">
                            <i class="bi bi-save"></i> Save Mail Settings
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="col-lg-4">
        <div class="card border-0 shadow-sm mb-4">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">Send a Test Email</h6>
                <form action="{{ route('admin.settings.mail.test') }}" method="POST">
                    @csrf
                    <div class="mb-3">
                        <input type="email" name="test_email" class="form-control @error('test_email') is-invalid @enderror"
                               value="{{ old('test_email', auth()->user()->email) }}" placeholder="you@example.com" required>
                        @error('test_email') <div class="invalid-feedback">{{ $message }}</div> @enderror
                    </div>
                    <button type="submit" class="btn btn-outline-primary w-100" @disabled(! $isConfigured)>
                        <i class="bi bi-send"></i> Send Test
                    </button>
                </form>
            </div>
        </div>

        <div class="card border-0 shadow-sm">
            <div class="card-body p-4">
                <h6 class="fw-bold mb-3 text-dark">Gmail?</h6>
                <p class="text-muted small mb-2">
                    Google blocks ordinary passwords. Turn on 2-step verification, then create an
                    <strong>App password</strong> and paste that here.
                </p>
                <ul class="text-muted small mb-0 ps-3">
                    <li>Host <code>smtp.gmail.com</code></li>
                    <li>Port <code>587</code>, TLS</li>
                    <li>Username = your full Gmail address</li>
                </ul>
            </div>
        </div>
    </div>
</div>
@endsection
