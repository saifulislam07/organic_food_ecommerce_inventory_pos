<section>
    <p class="text-muted small mb-4">
        {{ (app()->getLocale() == 'bn' ? 'আপনার অ্যাকাউন্টের প্রোফাইল তথ্য এবং ইমেল ঠিকানা আপডেট করুন।' : "Update your account's profile information and email address.") }}
    </p>

    <form id="send-verification" method="post" action="{{ route('verification.send') }}">
        @csrf
    </form>

    <form method="post" action="{{ route('profile.update') }}" class="mt-4">
        @csrf
        @method('patch')

        <div class="mb-3">
            <label for="name" class="form-label fw-bold small text-uppercase tracking-wider">{{ app()->getLocale() == 'bn' ? 'নাম' : 'Name' }}</label>
            <input id="name" name="name" type="text" class="form-control @error('name') is-invalid @enderror" value="{{ old('name', $user->name) }}" required autofocus autocomplete="name" />
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="email" class="form-label fw-bold small text-uppercase tracking-wider">{{ app()->getLocale() == 'bn' ? 'ইমেইল' : 'Email' }}</label>
            <input id="email" name="email" type="email" class="form-control @error('email') is-invalid @enderror" value="{{ old('email', $user->email) }}" required autocomplete="username" />
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror

            @if ($user instanceof \Illuminate\Contracts\Auth\MustVerifyEmail && ! $user->hasVerifiedEmail())
                <div class="mt-2">
                    <p class="text-sm text-dark">
                        {{ __('Your email address is unverified.') }}
                        <button form="send-verification" class="btn btn-link p-0 text-decoration-underline text-muted small">
                            {{ __('Click here to re-send the verification email.') }}
                        </button>
                    </p>

                    @if (session('status') === 'verification-link-sent')
                        <p class="text-success small fw-bold">
                            {{ __('A new verification link has been sent to your email address.') }}
                        </p>
                    @endif
                </div>
            @endif
        </div>

        <div class="mb-4">
            <label for="mobile" class="form-label fw-bold small text-uppercase tracking-wider">{{ app()->getLocale() == 'bn' ? 'মোবাইল নম্বর' : 'Mobile Number' }}</label>
            <input id="mobile" name="mobile" type="text" class="form-control @error('mobile') is-invalid @enderror" value="{{ old('mobile', $user->mobile) }}" required />
            @error('mobile')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary px-4 fw-bold">
                {{ app()->getLocale() == 'bn' ? 'সংরক্ষণ করুন' : 'Save' }}
            </button>

            @if (session('status') === 'profile-updated')
                <span class="text-success small fw-bold animate__animated animate__fadeIn">
                    <i class="bi bi-check-circle"></i> {{ app()->getLocale() == 'bn' ? 'সংরক্ষিত হয়েছে' : 'Saved.' }}
                </span>
            @endif
        </div>
    </form>
</section>
