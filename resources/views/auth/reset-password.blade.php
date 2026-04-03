@extends('layouts.auth')

@section('title', app()->getLocale() == 'bn' ? 'পাসওয়ার্ড রিসেট' : 'Reset Password')

@section('content')
<div class="mb-4">
    <h2 class="fw-black text-dark mb-1">{{ app()->getLocale() == 'bn' ? 'নতুন পাসওয়ার্ড সেট করুন' : 'Reset Password' }}</h2>
    <p class="text-muted small">
        {{ app()->getLocale() == 'bn' 
           ? 'আপনার অ্যাকাউন্টের জন্য একটি শক্তিশালী নতুন পাসওয়ার্ড তৈরি করুন।' 
           : 'Please create a strong new password for your account to ensure security.' }}
    </p>
</div>

<form method="POST" action="{{ route('password.store') }}">
    @csrf

    <!-- Password Reset Token -->
    <input type="hidden" name="token" value="{{ $request->route('token') }}">

    <!-- Email Address -->
    <div class="mb-3">
        <label for="email" class="form-label">{{ app()->getLocale() == 'bn' ? 'ইমেল অ্যাড্রেস' : 'Email Address' }}</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input id="email" name="email" type="email" 
                class="form-control border-start-0 @error('email') is-invalid @enderror" 
                placeholder="example@mail.com" 
                value="{{ old('email', $request->email) }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Password -->
        <div class="col-md-6">
            <label for="password" class="form-label">{{ app()->getLocale() == 'bn' ? 'নতুন পাসওয়ার্ড' : 'New Password' }}</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
                <input id="password" name="password" type="password" 
                    class="form-control border-start-0 @error('password') is-invalid @enderror" 
                    placeholder="••••••••" 
                    required autocomplete="new-password">
                @error('password')
                    <div class="invalid-feedback">{{ $message }}</div>
                @enderror
            </div>
        </div>

        <!-- Confirm Password -->
        <div class="col-md-6">
            <label for="password_confirmation" class="form-label">{{ app()->getLocale() == 'bn' ? 'নিশ্চিত করুন' : 'Confirm' }}</label>
            <div class="input-group">
                <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock-check"></i></span>
                <input id="password_confirmation" name="password_confirmation" type="password" 
                    class="form-control border-start-0" 
                    placeholder="••••••••" 
                    required autocomplete="new-password">
            </div>
        </div>
    </div>

    <button type="submit" class="btn btn-primary-custom">
        {{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড রিসেট করুন' : 'Reset Password' }}
        <i class="bi bi-shield-check"></i>
    </button>
</form>
@endsection
