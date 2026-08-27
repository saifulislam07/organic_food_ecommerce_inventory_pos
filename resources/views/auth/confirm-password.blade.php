@extends('layouts.auth')

@section('title', app()->getLocale() == 'bn' ? 'পাসওয়ার্ড নিশ্চিত করুন' : 'Confirm Password')

@section('content')
<div class="mb-4">
    <h2 class="fw-black text-dark mb-1">
        {{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড নিশ্চিত করুন' : 'Confirm Password' }}
    </h2>
    <p class="text-muted small">
        {{ app()->getLocale() == 'bn'
           ? 'এটি অ্যাপ্লিকেশনের একটি সুরক্ষিত অংশ। এগিয়ে যাওয়ার আগে অনুগ্রহ করে আপনার পাসওয়ার্ড নিশ্চিত করুন।'
           : 'This is a secure area of the application. Please confirm your password before continuing.' }}
    </p>
</div>

<form method="POST" action="{{ route('password.confirm') }}">
    @csrf

    <div class="mb-4">
        <label for="password" class="form-label">{{ app()->getLocale() == 'bn' ? 'পাসওয়ার্ড' : 'Password' }}</label>
        <div class="input-group">
            <span class="input-group-text bg-light border-end-0 text-muted"><i class="bi bi-lock"></i></span>
            <input id="password" name="password" type="password"
                class="form-control border-start-0 @error('password') is-invalid @enderror"
                placeholder="••••••••" required autocomplete="current-password" autofocus>
            @error('password')
                <div class="invalid-feedback">{{ $message }}</div>
            @enderror
        </div>
    </div>

    <button type="submit" class="btn btn-primary-custom">
        {{ app()->getLocale() == 'bn' ? 'নিশ্চিত করুন' : 'Confirm' }}
        <i class="bi bi-shield-check"></i>
    </button>
</form>
@endsection
