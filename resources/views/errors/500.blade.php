@extends('errors.layout')

@section('title', app()->getLocale() == 'bn' ? 'সার্ভার সমস্যা' : 'Server Error')
@section('code', '500')
@section('image', asset('images/errors/500.png'))
@section('message', app()->getLocale() == 'bn' ? 'বাগানে কিছু একটা সমস্যা হয়েছে!' : 'Something went wrong on our side')
@section('description', app()->getLocale() == 'bn'
    ? 'আমাদের সার্ভারে সাময়িক কারিগরি সমস্যা দেখা দিয়েছে। আমরা দ্রুত এটি সমাধানের চেষ্টা করছি। কিছুক্ষণ পর আবার চেষ্টা করুন।'
    : 'Our server hit a temporary problem. We are working on it — please try again in a moment.')

@section('actions')
    <div class="d-flex gap-3 justify-content-center flex-wrap">
        <button type="button" onclick="window.location.reload()" class="btn-premium">
            <i class="bi bi-arrow-clockwise"></i>
            {{ app()->getLocale() == 'bn' ? 'আবার চেষ্টা করুন' : 'Try Again' }}
        </button>
        <a href="{{ url('/') }}" class="btn-premium">
            <i class="bi bi-house-door"></i>
            {{ app()->getLocale() == 'bn' ? 'হোম পেজে যান' : 'Go Home' }}
        </a>
    </div>
@endsection

@section('footnote')
    <p>
        {{ app()->getLocale() == 'bn' ? 'আমাদের দল এখনই কাজ করছে।' : 'Our team is already on it.' }}
        <a href="{{ route('contact') }}" class="text-primary text-decoration-none fw-bold">
            {{ app()->getLocale() == 'bn' ? 'যোগাযোগ করুন' : 'Contact us' }}
        </a>
    </p>
@endsection
