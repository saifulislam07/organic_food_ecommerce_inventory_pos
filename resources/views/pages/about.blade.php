@extends('layouts.frontend')
@section('title', 'About Us – Mango Hut')
@section('content')
<div class="page-header">
    <div class="container">
        <h1>About Us</h1>
        <ul class="breadcrumb-custom">
            <li><a href="{{ route('home') }}">Home</a></li>
            <li><span>/</span></li>
            <li>About</li>
        </ul>
    </div>
</div>
<section class="section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div style="font-size: 10rem; text-align: center;">🥭</div>
            </div>
            <div class="col-lg-6">
                <div class="section-badge"><i class="bi bi-info-circle"></i> About Us</div>
                <h2 class="section-title text-start">আমাদের সম্পর্কে</h2>
                <p class="text-muted" style="line-height: 1.8;">
                    <strong>Mango Hut</strong> একটি অনলাইন প্ল্যাটফর্ম যেখানে আপনি পাবেন চাঁপাই নবাবগঞ্জের সেরা আম,
                    রাজশাহীর খাঁটি খেজুর গুড়, সুন্দরবনের মধু, ঘানিভাঙ্গা সরিষার তেল এবং আরও অনেক প্রাকৃতিক ও অর্গানিক পণ্য।
                </p>
                <p class="text-muted" style="line-height: 1.8;">
                    আমাদের লক্ষ্য হলো বাংলাদেশের প্রতিটি ঘরে খাঁটি ও মেশালমুক্ত পণ্য পৌঁছে দেওয়া। আমরা সরাসরি কৃষকদের কাছ থেকে পণ্য সংগ্রহ করি এবং সারাদেশে ডেলিভারি দিই।
                </p>
                <div class="d-flex flex-wrap gap-4 mt-4">
                    <div class="text-center">
                        <div style="font-size: 2rem; color: var(--primary); font-weight: 700;">500+</div>
                        <small class="text-muted">Happy Customers</small>
                    </div>
                    <div class="text-center">
                        <div style="font-size: 2rem; color: var(--primary); font-weight: 700;">50+</div>
                        <small class="text-muted">Products</small>
                    </div>
                    <div class="text-center">
                        <div style="font-size: 2rem; color: var(--primary); font-weight: 700;">100%</div>
                        <small class="text-muted">Organic</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
