@extends('layouts.auth')

@section('title', app()->getLocale() == 'bn' ? 'ইমেল যাচাই করুন' : 'Verify Email')

@section('content')
<div class="mb-4">
    <h2 class="fw-black text-dark mb-1">
        {{ app()->getLocale() == 'bn' ? 'ইমেল যাচাই করুন' : 'Verify Your Email' }}
    </h2>
    <p class="text-muted small">
        {{ app()->getLocale() == 'bn'
           ? 'সাইন আপ করার জন্য ধন্যবাদ! শুরু করার আগে, আমরা আপনাকে যে লিঙ্কটি ইমেল করেছি সেটিতে ক্লিক করে আপনার ইমেল ঠিকানাটি যাচাই করুন। ইমেলটি না পেলে আমরা আনন্দের সাথে আরেকটি পাঠাব।'
           : "Thanks for signing up! Before getting started, could you verify your email address by clicking on the link we just emailed to you? If you didn't receive the email, we will gladly send you another." }}
    </p>
</div>

@if (session('status') == 'verification-link-sent')
    <div class="alert alert-success border-0 shadow-sm mb-4" role="alert">
        <i class="bi bi-check-circle me-1"></i>
        {{ app()->getLocale() == 'bn'
           ? 'রেজিস্ট্রেশনের সময় দেওয়া ইমেল ঠিকানায় একটি নতুন যাচাইকরণ লিঙ্ক পাঠানো হয়েছে।'
           : 'A new verification link has been sent to the email address you provided during registration.' }}
    </div>
@endif

<form method="POST" action="{{ route('verification.send') }}">
    @csrf
    <button type="submit" class="btn btn-primary-custom">
        {{ app()->getLocale() == 'bn' ? 'যাচাইকরণ ইমেল আবার পাঠান' : 'Resend Verification Email' }}
        <i class="bi bi-envelope-arrow-up"></i>
    </button>
</form>

<div class="mt-4 pt-3 border-top text-center">
    <form method="POST" action="{{ route('logout') }}">
        @csrf
        <button type="submit" class="btn btn-link link-custom text-decoration-none p-0 small">
            {{ app()->getLocale() == 'bn' ? 'লগআউট করুন' : 'Log Out' }}
        </button>
    </form>
</div>
@endsection
