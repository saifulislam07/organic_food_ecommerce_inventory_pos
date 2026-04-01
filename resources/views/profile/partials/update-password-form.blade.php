<section>
    <p class="text-muted small mb-4">
        {{ (app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট সুরক্ষিত রাখতে একটি দীর্ঘ, শক্তিশালী পাসওয়ার্ড ব্যবহার করুন।' : 'Ensure your account is using a long, random password to stay secure.') }}
    </p>

    <form method="post" action="{{ route('password.update') }}" class="mt-4">
        @csrf
        @method('put')

        <div class="mb-3">
            <label for="update_password_current_password" class="form-label fw-bold small text-uppercase tracking-wider">{{ app()->getLocale() == 'bn' ? 'বর্তমান পাসওয়ার্ড' : 'Current Password' }}</label>
            <input id="update_password_current_password" name="current_password" type="password" class="form-control @error('current_password', 'updatePassword') is-invalid @enderror" autocomplete="current-password" />
            @error('current_password', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-3">
            <label for="update_password_password" class="form-label fw-bold small text-uppercase tracking-wider">{{ app()->getLocale() == 'bn' ? 'নতুন পাসওয়ার্ড' : 'New Password' }}</label>
            <input id="update_password_password" name="password" type="password" class="form-control @error('password', 'updatePassword') is-invalid @enderror" autocomplete="new-password" />
            @error('password', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="mb-4">
            <label for="update_password_password_confirmation" class="form-label fw-bold small text-uppercase tracking-wider">{{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড নিশ্চিত করুন' : 'Confirm Password' }}</label>
            <input id="update_password_password_confirmation" name="password_confirmation" type="password" class="form-control @error('password_confirmation', 'updatePassword') is-invalid @enderror" autocomplete="new-password" />
            @error('password_confirmation', 'updatePassword')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>

        <div class="d-flex align-items-center gap-3">
            <button type="submit" class="btn btn-primary px-4 fw-bold">
                {{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড পরিবর্তন করুন' : 'Save' }}
            </button>

            @if (session('status') === 'password-updated')
                <span class="text-success small fw-bold animate__animated animate__fadeIn">
                    <i class="bi bi-check-circle"></i> {{ app()->getLocale() == 'bn' ? 'সংরক্ষিত হয়েছে' : 'Saved.' }}
                </span>
            @endif
        </div>
    </form>
</section>
