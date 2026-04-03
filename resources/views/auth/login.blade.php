@extends('layouts.auth')

@section('title', app()->getLocale() == 'bn' ? 'লগইন' : 'Login')

@section('content')
<div class="mb-4">
    <h2 class="fw-black text-dark mb-1">{{ app()->getLocale() == 'bn' ? 'স্বাগতম' : 'Welcome back' }}</h2>
    <p class="text-muted">{{ app()->getLocale() == 'bn' ? 'আপনার অ্যাকাউন্টে লগইন করুন' : 'Please sign in to your account' }}</p>
</div>

<!-- Session Status -->
@if (session('status'))
    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
        {{ session('status') }}
    </div>
@endif

<form method="POST" action="{{ route('login') }}">
    @csrf

    <!-- Login ID (Mobile or Email) -->
    <div class="mb-3">
        <label for="login_id" class="form-label">{{ app()->getLocale() == 'bn' ? 'মোবাইল নম্বর বা ইমেল' : 'Mobile Number or Email' }}</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input id="login_id" name="login_id" type="text" 
                class="form-control border-start-0 @error('login_id') is-invalid @enderror" 
                placeholder="017XXXXXXXX / example@mail.com" 
                value="{{ old('login_id') }}" required autofocus>
            @error('login_id')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Password -->
    <div class="mb-3">
        <div class="d-flex justify-content-between align-items-center mb-2">
            <label for="password" class="form-label mb-0">{{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড' : 'Password' }}</label>
            @if (Route::has('password.request'))
                <a href="{{ route('password.request') }}" class="link-custom small">
                    {{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড ভুলে গেছেন?' : 'Forgot password?' }}
                </a>
            @endif
        </div>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input id="password" name="password" type="password" 
                class="form-control border-start-0 @error('password') is-invalid @enderror" 
                placeholder="••••••••" 
                required autocomplete="current-password">
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Remember Me -->
    <div class="mb-4 d-flex align-items-center">
        <div class="form-check">
            <input class="form-check-input" type="checkbox" name="remember" id="remember_me">
            <label class="form-check-label text-muted small ms-1" for="remember_me">
                {{ app()->getLocale() == 'bn' ? 'আমাকে মনে রাখুন' : 'Remember me' }}
            </label>
        </div>
    </div>

    <button type="submit" class="btn btn-primary-custom">
        {{ app()->getLocale() == 'bn' ? 'লগইন করুন' : 'Sign In' }}
        <i class="bi bi-arrow-right"></i>
    </button>
</form>

<div class="mt-4 pt-3 border-top text-center">
    <p class="text-muted small">
        {{ app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট নেই?' : "Don't have an account?" }} 
        <a href="{{ route('register') }}" class="link-custom ms-1">
            {{ app()->getLocale() == 'bn' ? 'নতুন অ্যাকাউন্ট খুলুন' : 'Create an account' }}
        </a>
    </p>
</div>
@endsection
