@extends('layouts.auth')

@section('title', app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট তৈরি করুন' : 'Create Account')

@section('content')
<div class="mb-4">
    <h2 class="fw-black text-dark mb-1">{{ app()->getLocale() == 'bn' ? 'নতুন অ্যাকাউন্ট' : 'Create Account' }}</h2>
    <p class="text-muted">{{ app()->getLocale() == 'bn' ? 'ম্যাংগো হাটে যোগ দিতে আপনার তথ্য দিন' : 'Join Mango Hut and start shopping today' }}</p>
</div>

<form method="POST" action="{{ route('register') }}">
    @csrf

    <!-- Name -->
    <div class="mb-3">
        <label for="name" class="form-label">{{ app()->getLocale() == 'bn' ? 'পুরো নাম' : 'Full Name' }} <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-person"></i></span>
            <input id="name" name="name" type="text" 
                class="form-control border-start-0 @error('name') is-invalid @enderror" 
                placeholder="{{ app()->getLocale() == 'bn' ? 'আপনার নাম' : 'Your name' }}" 
                value="{{ old('name') }}" required autofocus autocomplete="name">
            @error('name')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Mobile -->
    <div class="mb-3">
        <label for="mobile" class="form-label">{{ app()->getLocale() == 'bn' ? 'মোবাইল নম্বর' : 'Mobile Number' }} <span class="text-danger">*</span></label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-telephone"></i></span>
            <input id="mobile" name="mobile" type="text" 
                class="form-control border-start-0 @error('mobile') is-invalid @enderror" 
                placeholder="017XXXXXXXX" 
                value="{{ old('mobile') }}" required autocomplete="tel">
            @error('mobile')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <!-- Email (Optional) -->
    <div class="mb-3">
        <label for="email" class="form-label">{{ app()->getLocale() == 'bn' ? 'ইমেল (ঐচ্ছিক)' : 'Email address (Optional)' }}</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-envelope"></i></span>
            <input id="email" name="email" type="email" 
                class="form-control border-start-0 @error('email') is-invalid @enderror" 
                placeholder="example@mail.com" 
                value="{{ old('email') }}" autocomplete="username">
            @error('email')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <div class="row g-3 mb-4">
        <!-- Password -->
        <div class="col-md-6">
            <label for="password" class="form-label">{{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড' : 'Password' }} <span class="text-danger">*</span></label>
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
            <label for="password_confirmation" class="form-label">{{ app()->getLocale() == 'bn' ? 'নিশ্চিত করুন' : 'Confirm' }} <span class="text-danger">*</span></label>
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
        {{ app()->getLocale() == 'bn' ? 'অ্যাকাউন্ট খুলুন' : 'Create Account' }}
        <i class="bi bi-person-plus"></i>
    </button>
</form>

<div class="mt-4 pt-3 border-top text-center">
    <p class="text-muted small">
        {{ app()->getLocale() == 'bn' ? 'আগে থেকেই অ্যাকাউন্ট আছে?' : 'Already have an account?' }} 
        <a href="{{ route('login') }}" class="link-custom ms-1">
            {{ app()->getLocale() == 'bn' ? 'লগইন করুন' : 'Sign in instead' }}
        </a>
    </p>
</div>
@endsection
