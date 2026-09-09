@extends('layouts.frontend')
@section('title', 'About Us – BaburhashiBD')

@push('styles')
<style>
    .about-brand-panel {
        display: flex;
        align-items: center;
        justify-content: center;
        padding: 56px 32px;
        border-radius: var(--radius-lg);
        background: linear-gradient(135deg, var(--leaf-tint), var(--gold-tint));
        box-shadow: var(--shadow-sm);
    }
    .about-brand-panel .brand-logo { height: clamp(48px, 12vw, 96px); }
</style>
@endpush
@section('content')
@include('partials.page-head', [
    'title' => app()->getLocale() == 'bn' ? 'আমাদের সম্পর্কে' : 'About Us',
    'icon' => 'info-circle',
    'crumbs' => [app()->getLocale() == 'bn' ? 'সম্পর্কে' : 'About'],
])
<section class="section">
    <div class="container">
        <div class="row align-items-center g-5">
            <div class="col-lg-6">
                <div class="about-brand-panel">
                    @include('partials.brand', ['size' => 'lg'])
                </div>
            </div>
            <div class="col-lg-6">
                <div class="section-badge"><i class="bi bi-info-circle"></i> About Us</div>
                <h2 class="section-title text-start">আমাদের সম্পর্কে</h2>
                <p class="text-muted" style="line-height: 1.8;">
                    <strong>BaburhashiBD</strong> একটি অনলাইন প্ল্যাটফর্ম যেখানে আপনি পাবেন শিশুদের পোশাক,
                    ডায়াপার ও ওয়াইপস, ফিডিং সামগ্রী, খেলনা, বেবি কেয়ার পণ্য এবং স্কুল সামগ্রী — সবকিছু এক জায়গায়।
                </p>
                <p class="text-muted" style="line-height: 1.8;">
                    আমাদের লক্ষ্য হলো বাংলাদেশের প্রতিটি ঘরে নিরাপদ ও মানসম্পন্ন শিশু পণ্য পৌঁছে দেওয়া। আমরা মান যাচাই করে পণ্য সংগ্রহ করি এবং সারাদেশে ডেলিভারি দিই।
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
                        <small class="text-muted">Trusted Brands</small>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>
@endsection
