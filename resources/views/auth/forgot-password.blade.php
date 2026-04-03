@extends('layouts.auth')

@section('title', app()->getLocale() == 'bn' ? 'পাসওয়ার্ড পুনরুদ্ধার' : 'Forgot Password')

@section('content')
<div class="mb-4">
    <h2 class="fw-black text-dark mb-1">{{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড ভুলে গেছেন?' : 'Forgot Password?' }}</h2>
    <p class="text-muted small">
        {{ app()->getLocale() == 'bn' 
           ? 'চিন্তা করবেন না! আপনার ইমেল ঠিকানাটি দিন এবং আমরা আপনাকে একটি পাসওয়ার্ড রিসেট লিঙ্ক পাঠিয়ে দেব।' 
           : 'No problem. Just let us know your email address and we will email you a password reset link to choose a new one.' }}
    </p>
</div>

<!-- Session Status -->
@if (session('status'))
    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('password.email') }}">
    @csrf

    <!-- Email Address -->
    <div class="mb-4">
        <label for="email" class="form-label">{{ app()->getLocale() == 'bn' ? 'ইমেল অ্যাড্রেস' : 'Email Address' }}</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input id="email" name="email" type="email" 
                class="form-control border-start-0 @error('email') is-invalid @enderror" 
                placeholder="example@mail.com" 
                value="{{ old('email') }}" required autofocus autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <button type="submit" class="btn btn-primary-custom">
        {{ app()->getLocale() == 'bn' ? 'রিসেট লিঙ্ক পাঠান' : 'Email Password Reset Link' }}
        <i class="bi bi-send"></i>
    </button>
</form>

<div class="mt-4 pt-3 border-top text-center">
    <p class="text-muted small">
        {{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড মনে পড়েছে?' : 'Remember your password?' }} 
        <a href="{{ route('login') }}" class="link-custom ms-1">
            {{ app()->getLocale() == 'bn' ? 'লগইন করুন' : 'Back to login' }}
        </a>
    </p>
</div>
@endsection
